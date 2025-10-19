<?php

declare(strict_types=1);

namespace App\Application\Actions;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Application\Services\SessionService;
use App\Application\Services\EmailService;
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
    private EmailService $emailService;

    public function __construct(
        \PDO $pdo,
        SessionService $sessionService,
        Twig $twig,
        LoggerInterface $logger,
        EmailService $emailService
    ) {
        $this->pdo = $pdo;
        $this->sessionService = $sessionService;
        $this->twig = $twig;
        $this->logger = $logger;
        $this->emailService = $emailService;
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
                try {
                    $this->pdo->beginTransaction();
                    // Generate secure token ONCE
                    $token = bin2hex(random_bytes(32)); // 256-bit token
                    $tokenHash = hash('sha256', $token);
                    // Do not compute expires_at in PHP; use DB's NOW() + INTERVAL to avoid
                    // timezone mismatches and ensure consistency.

                    // Remove any previous unused tokens for this user/email
                    $stmt = $this->pdo->prepare('DELETE FROM password_resets WHERE user_id = ? AND email = ? AND used_at IS NULL');
                    $stmt->execute([$user['id'], $email]);

                    // Store token in database
                    $stmt = $this->pdo->prepare(
                        'INSERT INTO password_resets (user_id, email, token, expires_at, ip_address, user_agent) 
                         VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR), ?, ?)'
                    );
                    $stmt->execute([
                        $user['id'],
                        $email,
                        $tokenHash, // Hash token before storing
                        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                    ]);
                    // (No debug logging in production)
                    $this->pdo->commit();

                    // Send reset email with the same token (URL-encoded for safety)
                    $resetLink = $this->getBaseUrl($request) . '/reset-password?token=' . rawurlencode($token);
                    $this->sendResetEmail($user, $resetLink);

                    // Read back expires_at from DB for accurate logging
                    $loggedExpiresAt = null;
                    try {
                        $lastId = (int) $this->pdo->lastInsertId();
                        if ($lastId) {
                            $expiresStmt = $this->pdo->prepare('SELECT expires_at FROM password_resets WHERE id = ? LIMIT 1');
                            $expiresStmt->execute([$lastId]);
                            $expiresRow = $expiresStmt->fetch(\PDO::FETCH_ASSOC);
                            if ($expiresRow && isset($expiresRow['expires_at'])) {
                                $loggedExpiresAt = $expiresRow['expires_at'];
                            }
                        }
                    } catch (\Exception $e) {
                        $this->logger->warning('Unable to read back expires_at after insert', ['error' => $e->getMessage()]);
                    }

                    $this->logger->info('Password reset email sent', [
                        'user_id' => $user['id'],
                        'email' => $email,
                        'expires_at' => $loggedExpiresAt,
                    ]);
                } catch (\Exception $e) {
                    if ($this->pdo->inTransaction()) {
                        $this->pdo->rollBack();
                    }
                    $this->logger->error('Password reset transaction failed', [
                        'user_id' => $user['id'],
                        'email' => $email,
                        'error' => $e->getMessage(),
                    ]);
                }
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
    // Normalize token: trim whitespace and URL-decode (defensive)
    $token = trim($queryParams['token'] ?? '');
    $token = rawurldecode($token);

        if (empty($token)) {
            $this->sessionService->set('flash_message', 'Invalid reset link.');
            $this->sessionService->set('flash_type', 'danger');
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        // Verify token exists and is not expired
        $hashedToken = hash('sha256', $token);

        // Production: do not log sensitive token data or perform debug queries here.

        // Log a minimal, non-sensitive event for audit purposes. Avoid including
        // token values or hashes in logs.
        $this->logger->info('Password reset verification attempt', [
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        ]);

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
            // Attempt a fallback lookup that does not require the JOIN to users.
            // This can help if the users row is missing or the JOIN fails for any
            // reason (schema change, deleted user, etc.). If we find a matching
            // password_resets row, fetch the user separately.
            try {
                $fallbackStmt = $this->pdo->prepare('SELECT id, user_id, email, expires_at, used_at FROM password_resets WHERE token = ? AND expires_at > NOW() AND used_at IS NULL LIMIT 1');
                $fallbackStmt->execute([$hashedToken]);
                $pr = $fallbackStmt->fetch(\PDO::FETCH_ASSOC);
                if ($pr) {
                    $userStmt = $this->pdo->prepare('SELECT first_name, id, active FROM users WHERE id = ? LIMIT 1');
                    $userStmt->execute([$pr['user_id']]);
                    $userRow = $userStmt->fetch(\PDO::FETCH_ASSOC);
                    if ($userRow) {
                        $resetRecord = [
                            'id' => $pr['id'],
                            'email' => $pr['email'],
                            'expires_at' => $pr['expires_at'],
                            'used_at' => $pr['used_at'],
                            'first_name' => $userRow['first_name'] ?? '',
                        ];
                        $this->logger->info('Fallback password_resets lookup succeeded', ['password_reset_id' => $pr['id'], 'user_id' => $pr['user_id']]);
                    } else {
                        $this->logger->warning('Password reset token found but user record missing', ['password_reset_id' => $pr['id'], 'user_id' => $pr['user_id']]);
                    }
                }
            } catch (\Exception $e) {
                $this->logger->error('Error during fallback password_resets lookup', ['error' => $e->getMessage()]);
            }
        }

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
            // Use EmailService to send password reset email
            $success = $this->emailService->send(
                $user['email'],
                'Password Reset Request - Know My Patient',
                $this->getResetEmailHtml($user, $resetLink),
                $this->getResetEmailText($user, $resetLink),
                $user['first_name'] ?? 'User'
            );

            if ($success) {
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
            } else {
                // Log failure
                $this->logger->error('Failed to send password reset email', [
                    'user_id' => $user['id'],
                    'email' => $user['email'],
                ]);

                // Log to audit trail
                $this->logAuditEvent($user['id'], 'PASSWORD_RESET_EMAIL_FAILED', [
                    'email' => $user['email'],
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                ]);
            }

            // Do not log the reset link to protect sensitive data.
            // Defensive: ensure no logger context includes the reset link
            // in case other code mistakenly attempted to add it.
            if (method_exists($this->logger, 'info')) {
                // Log a minimal event without sensitive data
                $this->logger->debug('Password reset email flow completed (email send status recorded)', ['user_id' => $user['id']]);
            }
        } catch (\Exception $e) {
            // Log any unexpected errors
            $this->logger->error('Exception while sending password reset email', [
                'email' => $user['email'],
                'error' => $e->getMessage(),
            ]);

            // Log to audit trail
            $this->logAuditEvent($user['id'], 'PASSWORD_RESET_EMAIL_FAILED', [
                'email' => $user['email'],
                'error' => $e->getMessage(),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ]);
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
        // Render the HTML email using Twig template
        return $this->twig->fetch('email_templates/password_reset.html.twig', [
            'first_name' => $user['first_name'] ?? 'User',
            'reset_link' => $resetLink,
        ]);
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
                'INSERT INTO audit_log (user_id, activity_type, description, ip_address) 
                 VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([
                $userId,
                $action,
                json_encode($details),
                $details['ip_address'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown',
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
