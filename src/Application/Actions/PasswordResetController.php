<?php

declare(strict_types=1);

namespace App\Application\Actions;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Application\Services\SessionService;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;
use Respect\Validation\Validator as v;

/**
 * Password Reset Controller
 *
 * NHS DCB0129 Compliance: Hazard H-003 (Unauthorized Access Prevention)
 *
 * Handles password reset functionality with:
 * - Secure token generation (256-bit random)
 * - 1-hour token expiry
 * - Single-use tokens
 * - Rate limiting (3 requests per hour per email)
 * - Email notifications via PHPMailer
 * - Audit logging
 */
class PasswordResetController
{
    private \PDO $pdo;
    private SessionService $sessionService;
    private Twig $twig;
    private LoggerInterface $logger;

    public function __construct(
        \PDO $pdo,
        SessionService $sessionService,
        Twig $twig,
        LoggerInterface $logger
    ) {
        $this->pdo = $pdo;
        $this->sessionService = $sessionService;
        $this->twig = $twig;
        $this->logger = $logger;
    }

    /**
     * Show forgot password form
     */
    public function showForgotPasswordForm(Request $request, Response $response): Response
    {
        // If already logged in, redirect to dashboard
        if ($this->sessionService->has('user_id')) {
            return $response->withHeader('Location', '/dashboard')->withStatus(302);
        }

        $csrf = [
            'name' => $request->getAttribute('csrf_name'),
            'value' => $request->getAttribute('csrf_value'),
            'keys' => [
                'name' => 'csrf_name',
                'value' => 'csrf_value'
            ]
        ];

        $html = $this->twig->fetch('forgot_password.html.twig', [
            'csrf' => $csrf,
            'session' => $_SESSION,
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Handle forgot password form submission
     */
    public function handleForgotPassword(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $email = trim($data['email'] ?? '');

        // Validate email
        $emailValidator = v::notEmpty()->email();
        if (!$emailValidator->validate($email)) {
            $this->sessionService->set('flash_message', 'Please enter a valid email address.');
            $this->sessionService->set('flash_type', 'danger');
            return $response->withHeader('Location', '/forgot-password')->withStatus(302);
        }

        // Check rate limiting (3 requests per hour per email)
        if ($this->isRateLimited($email)) {
            $this->logger->warning('Password reset rate limit exceeded', ['email' => $email]);

            // Log rate limit event to audit trail (if user exists)
            $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($user) {
                $this->logAuditEvent($user['id'], 'PASSWORD_RESET_RATE_LIMITED', [
                    'email' => $email,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                ]);
            }

            $this->sessionService->set('flash_message', 'Too many reset requests. Please try again in 1 hour.');
            $this->sessionService->set('flash_type', 'warning');
            return $response->withHeader('Location', '/forgot-password')->withStatus(302);
        }

        // Look up user
        $stmt = $this->pdo->prepare('SELECT id, email, first_name, active FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Always show success message (don't reveal if email exists)
        // This prevents email enumeration attacks
        if ($user) {
            // Check if account is active
            if ($user['active'] == 0) {
                $this->logger->warning('Password reset requested for suspended account', ['email' => $email]);

                // Log attempt for suspended account
                $this->logAuditEvent($user['id'], 'PASSWORD_RESET_SUSPENDED_ACCOUNT', [
                    'email' => $email,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                ]);
                // Still show generic success message
            } else {
                // Generate secure token
                $token = bin2hex(random_bytes(32)); // 256-bit token
                $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour from now

                // Store token in database
                $stmt = $this->pdo->prepare(
                    'INSERT INTO password_resets (user_id, email, token, expires_at, ip_address, user_agent) 
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $user['id'],
                    $email,
                    hash('sha256', $token), // Hash token before storing
                    $expiresAt,
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                ]);

                // Send reset email
                $resetLink = $this->getBaseUrl($request) . '/reset-password?token=' . $token;
                $this->sendResetEmail($user, $resetLink);

                $this->logger->info('Password reset email sent', [
                    'user_id' => $user['id'],
                    'email' => $email,
                    'expires_at' => $expiresAt,
                ]);
            }
        } else {
            $this->logger->warning('Password reset requested for non-existent email', ['email' => $email]);
        }

        // Always show success message (security best practice)
        $this->sessionService->set('flash_message', 'If an account exists with that email, a password reset link has been sent. Please check your email (including spam folder). The link expires in 1 hour.');
        $this->sessionService->set('flash_type', 'success');
        return $response->withHeader('Location', '/login')->withStatus(302);
    }

    /**
     * Show reset password form
     */
    public function showResetPasswordForm(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        $token = $queryParams['token'] ?? '';

        if (empty($token)) {
            $this->sessionService->set('flash_message', 'Invalid reset link.');
            $this->sessionService->set('flash_type', 'danger');
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        // Verify token exists and is not expired
        $hashedToken = hash('sha256', $token);
        $stmt = $this->pdo->prepare(
            'SELECT pr.id, pr.email, pr.expires_at, pr.used_at, u.first_name 
             FROM password_resets pr
             JOIN users u ON pr.user_id = u.id
             WHERE pr.token = ? AND pr.expires_at > NOW() AND pr.used_at IS NULL
             LIMIT 1'
        );
        $stmt->execute([$hashedToken]);
        $resetRecord = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$resetRecord) {
            $this->logger->warning('Invalid or expired reset token accessed', ['token_hash' => substr($hashedToken, 0, 10) . '...']);
            $this->sessionService->set('flash_message', 'This reset link is invalid or has expired. Please request a new one.');
            $this->sessionService->set('flash_type', 'danger');
            return $response->withHeader('Location', '/forgot-password')->withStatus(302);
        }

        $csrf = [
            'name' => $request->getAttribute('csrf_name'),
            'value' => $request->getAttribute('csrf_value'),
            'keys' => [
                'name' => 'csrf_name',
                'value' => 'csrf_value'
            ]
        ];

        $html = $this->twig->fetch('reset_password.html.twig', [
            'csrf' => $csrf,
            'token' => $token,
            'email' => $resetRecord['email'],
            'first_name' => $resetRecord['first_name'],
            'session' => $_SESSION,
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Handle reset password form submission
     */
    public function handleResetPassword(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $token = trim($data['token'] ?? '');
        $password = $data['password'] ?? '';
        $passwordConfirm = $data['password_confirm'] ?? '';

        // Validate inputs
        if (empty($token)) {
            $this->sessionService->set('flash_message', 'Invalid reset token.');
            $this->sessionService->set('flash_type', 'danger');
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        if (empty($password) || strlen($password) < 8) {
            $this->sessionService->set('flash_message', 'Password must be at least 8 characters long.');
            $this->sessionService->set('flash_type', 'danger');
            return $response->withHeader('Location', '/reset-password?token=' . $token)->withStatus(302);
        }

        if ($password !== $passwordConfirm) {
            $this->sessionService->set('flash_message', 'Passwords do not match.');
            $this->sessionService->set('flash_type', 'danger');
            return $response->withHeader('Location', '/reset-password?token=' . $token)->withStatus(302);
        }

        // Verify token
        $hashedToken = hash('sha256', $token);
        $stmt = $this->pdo->prepare(
            'SELECT pr.id, pr.user_id, pr.email, pr.expires_at, pr.used_at 
             FROM password_resets pr
             WHERE pr.token = ? AND pr.expires_at > NOW() AND pr.used_at IS NULL
             LIMIT 1'
        );
        $stmt->execute([$hashedToken]);
        $resetRecord = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$resetRecord) {
            $this->logger->warning('Attempted to use invalid/expired reset token', ['token_hash' => substr($hashedToken, 0, 10) . '...']);
            $this->sessionService->set('flash_message', 'This reset link is invalid or has expired.');
            $this->sessionService->set('flash_type', 'danger');
            return $response->withHeader('Location', '/forgot-password')->withStatus(302);
        }

        // Update password
        $hashedPassword = password_hash($password, PASSWORD_ARGON2ID);
        $stmt = $this->pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
        $stmt->execute([$hashedPassword, $resetRecord['user_id']]);

        // Mark token as used
        $stmt = $this->pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ?');
        $stmt->execute([$resetRecord['id']]);

        // Log the password reset
        $this->logger->info('Password reset successful', [
            'user_id' => $resetRecord['user_id'],
            'email' => $resetRecord['email'],
        ]);

        // Log to audit trail
        $this->logAuditEvent((int) $resetRecord['user_id'], 'PASSWORD_RESET_COMPLETED', [
            'email' => $resetRecord['email'],
            'reset_token_id' => $resetRecord['id'],
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        ]);

        // Invalidate all other reset tokens for this user
        $stmt = $this->pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL');
        $stmt->execute([$resetRecord['user_id']]);

        $this->sessionService->set('flash_message', 'Your password has been reset successfully. Please login with your new password.');
        $this->sessionService->set('flash_type', 'success');
        return $response->withHeader('Location', '/login')->withStatus(302);
    }

    /**
     * Check if email is rate limited (3 requests per hour)
     */
    private function isRateLimited(string $email): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) as count 
             FROM password_resets 
             WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)'
        );
        $stmt->execute([$email]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return ($result['count'] ?? 0) >= 3;
    }

    /**
     * Send password reset email
     *
     * @param array<string, mixed> $user User data array
     * @param string $resetLink Full reset URL
     */
    private function sendResetEmail(array $user, string $resetLink): void
    {
        try {
            // Use PHPMailer to send email
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

            // Server settings
            $mail->isSMTP();
            $mail->Host       = $_ENV['SMTP_HOST'] ?? 'localhost';
            $mail->SMTPAuth   = filter_var($_ENV['SMTP_AUTH'] ?? true, FILTER_VALIDATE_BOOLEAN);
            $mail->Username   = $_ENV['SMTP_USERNAME'] ?? '';
            $mail->Password   = $_ENV['SMTP_PASSWORD'] ?? '';
            $mail->SMTPSecure = $_ENV['SMTP_ENCRYPTION'] ?? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int) ($_ENV['SMTP_PORT'] ?? 587);

            // Recipients
            $mail->setFrom(
                $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@knowmypatient.nhs.uk',
                $_ENV['MAIL_FROM_NAME'] ?? 'Know My Patient'
            );
            $mail->addAddress($user['email'], $user['first_name'] ?? '');
            $mail->addReplyTo($_ENV['MAIL_REPLY_TO'] ?? 'support@knowmypatient.nhs.uk', 'Support Team');

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request - Know My Patient';
            $mail->Body    = $this->getResetEmailHtml($user, $resetLink);
            $mail->AltBody = $this->getResetEmailText($user, $resetLink);

            $mail->send();

            // Log successful email send
            $this->logger->info('Password reset email sent successfully', [
                'user_id' => $user['id'],
                'email' => $user['email'],
            ]);

            // Log to audit trail
            $this->logAuditEvent($user['id'], 'PASSWORD_RESET_EMAIL_SENT', [
                'email' => $user['email'],
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            ]);
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            // Log error but don't expose details to user
            $this->logger->error('Failed to send password reset email', [
                'email' => $user['email'],
                'error' => $e->getMessage(),
                'mailer_error' => $mail->ErrorInfo,
            ]);

            // Log to audit trail
            $this->logAuditEvent($user['id'], 'PASSWORD_RESET_EMAIL_FAILED', [
                'email' => $user['email'],
                'error' => $e->getMessage(),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ]);

            // In development, log the reset link as fallback
            if (($_ENV['APP_ENV'] ?? 'production') !== 'production') {
                $this->logger->warning('Development mode: Password reset link', [
                    'reset_link' => $resetLink,
                ]);
            }
        }
    }

    /**
     * Get HTML email body for password reset
     *
     * @param array<string, mixed> $user User data
     * @param string $resetLink Reset URL
     * @return string HTML email body
     */
    private function getResetEmailHtml(array $user, string $resetLink): string
    {
        $firstName = htmlspecialchars($user['first_name'] ?? 'User', ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #005EB8; color: white; padding: 20px; text-align: center;">
        <h1 style="margin: 0;">Know My Patient</h1>
        <p style="margin: 5px 0 0 0;">NHS Patient Passport System</p>
    </div>
    
    <div style="background-color: #f4f4f4; padding: 30px; margin-top: 0;">
        <h2 style="color: #005EB8; margin-top: 0;">Password Reset Request</h2>
        
        <p>Hello {$firstName},</p>
        
        <p>We received a request to reset your password for your Know My Patient account. If you made this request, click the button below to reset your password:</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{$resetLink}" style="background-color: #005EB8; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;">Reset Password</a>
        </div>
        
        <p>Or copy and paste this link into your browser:</p>
        <p style="background-color: white; padding: 10px; border-left: 4px solid #005EB8; word-break: break-all;">
            <a href="{$resetLink}" style="color: #005EB8;">{$resetLink}</a>
        </p>
        
        <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;">
            <strong>⚠️ Important:</strong>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>This link expires in <strong>1 hour</strong></li>
                <li>The link can only be used <strong>once</strong></li>
                <li>If you didn't request this, you can safely ignore this email</li>
            </ul>
        </div>
        
        <p style="color: #666; font-size: 14px; margin-top: 30px;">
            <strong>Security Notice:</strong> Never share this link with anyone. Our team will never ask for your password via email.
        </p>
    </div>
    
    <div style="background-color: #333; color: white; padding: 20px; text-align: center; font-size: 12px;">
        <p>Know My Patient - NHS Digital Health Platform</p>
        <p>This is an automated message. Please do not reply to this email.</p>
        <p>If you need assistance, contact support at <a href="mailto:support@knowmypatient.nhs.uk" style="color: #4da6ff;">support@knowmypatient.nhs.uk</a></p>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Get plain text email body for password reset
     *
     * @param array<string, mixed> $user User data
     * @param string $resetLink Reset URL
     * @return string Plain text email body
     */
    private function getResetEmailText(array $user, string $resetLink): string
    {
        $firstName = $user['first_name'] ?? 'User';

        return <<<TEXT
KNOW MY PATIENT - PASSWORD RESET REQUEST
========================================

Hello {$firstName},

We received a request to reset your password for your Know My Patient account.

To reset your password, click or copy this link into your browser:
{$resetLink}

IMPORTANT INFORMATION:
- This link expires in 1 hour
- The link can only be used once
- If you didn't request this, you can safely ignore this email

SECURITY NOTICE:
Never share this link with anyone. Our team will never ask for your password via email.

---
Know My Patient - NHS Digital Health Platform
This is an automated message. Please do not reply to this email.

For assistance, contact: support@knowmypatient.nhs.uk
TEXT;
    }

    /**
     * Log event to audit trail
     *
     * @param int $userId User ID
     * @param string $action Action type
     * @param array<string, mixed> $details Additional details
     */
    private function logAuditEvent(int $userId, string $action, array $details): void
    {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO audit_log (user_id, action, details, ip_address, user_agent, created_at) 
                 VALUES (?, ?, ?, ?, ?, NOW())'
            );
            $stmt->execute([
                $userId,
                $action,
                json_encode($details),
                $details['ip_address'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $details['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            ]);
        } catch (\PDOException $e) {
            $this->logger->error('Failed to log audit event', [
                'user_id' => $userId,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get base URL for reset links
     */
    private function getBaseUrl(Request $request): string
    {
        $uri = $request->getUri();
        $scheme = $uri->getScheme();
        $host = $uri->getHost();
        $port = $uri->getPort();

        $baseUrl = $scheme . '://' . $host;
        if (($scheme === 'http' && $port !== 80) || ($scheme === 'https' && $port !== 443)) {
            $baseUrl .= ':' . $port;
        }

        return $baseUrl;
    }
}
