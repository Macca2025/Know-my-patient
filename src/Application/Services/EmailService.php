<?php

declare(strict_types=1);

namespace App\Application\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use Psr\Log\LoggerInterface;

/**
 * Email Service using PHPMailer
 * 
 * Provides a centralized email sending service with HTML and plain text support
 */
class EmailService
{
    private PHPMailer $mailer;
    private LoggerInterface $logger;
    private bool $isConfigured = false;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->mailer = new PHPMailer(true);
        $this->configure();
    }

    /**
     * Configure PHPMailer with environment settings
     */
    private function configure(): void
    {
        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host       = $_ENV['SMTP_HOST'] ?? 'localhost';
            $this->mailer->SMTPAuth   = filter_var($_ENV['SMTP_AUTH'] ?? true, FILTER_VALIDATE_BOOLEAN);
            $this->mailer->Username   = $_ENV['SMTP_USERNAME'] ?? '';
            $this->mailer->Password   = $_ENV['SMTP_PASSWORD'] ?? '';
            $this->mailer->Port       = (int) ($_ENV['SMTP_PORT'] ?? 587);

            // Set encryption
            $encryption = strtolower($_ENV['SMTP_ENCRYPTION'] ?? 'tls');
            if ($encryption === 'ssl') {
                $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($encryption === 'tls') {
                $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }

            // Set default from address
            $fromAddress = $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@knowmypatient.nhs.uk';
            $fromName = $_ENV['MAIL_FROM_NAME'] ?? 'Know My Patient';
            $this->mailer->setFrom($fromAddress, $fromName);

            // Set default reply-to if provided
            $replyTo = $_ENV['MAIL_REPLY_TO'] ?? null;
            if ($replyTo) {
                $this->mailer->addReplyTo($replyTo, 'Support Team');
            }

            // Character set
            $this->mailer->CharSet = PHPMailer::CHARSET_UTF8;

            // Enable debug in development
            if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
                $this->mailer->SMTPDebug = 0; // Set to 2 for verbose debug
            }

            $this->isConfigured = true;

            $this->logger->info('EmailService configured successfully', [
                'host' => $this->mailer->Host,
                'port' => $this->mailer->Port,
                'auth' => $this->mailer->SMTPAuth,
                'encryption' => $encryption,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to configure EmailService', [
                'error' => $e->getMessage(),
            ]);
            $this->isConfigured = false;
        }
    }

    /**
     * Send an email
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $htmlBody HTML body content
     * @param string|null $textBody Plain text body (optional)
     * @param string|null $recipientName Recipient name (optional)
     * @param array<string, string> $additionalHeaders Additional headers (optional)
     * @return bool True if sent successfully, false otherwise
     */
    public function send(
        string $to,
        string $subject,
        string $htmlBody,
        ?string $textBody = null,
        ?string $recipientName = null,
        array $additionalHeaders = []
    ): bool {
        if (!$this->isConfigured) {
            $this->logger->error('Cannot send email - EmailService not configured');
            return false;
        }

        try {
            // Reset recipients for fresh send
            $this->mailer->clearAddresses();
            $this->mailer->clearCCs();
            $this->mailer->clearBCCs();
            $this->mailer->clearAttachments();
            $this->mailer->clearCustomHeaders();

            // Set recipient
            $this->mailer->addAddress($to, $recipientName ?? '');

            // Set subject and body
            $this->mailer->Subject = $subject;
            $this->mailer->isHTML(true);
            $this->mailer->Body = $htmlBody;
            $this->mailer->AltBody = $textBody ?? strip_tags($htmlBody);

            // Add custom headers
            foreach ($additionalHeaders as $name => $value) {
                $this->mailer->addCustomHeader($name, $value);
            }

            // Send email
            $this->mailer->send();

            $this->logger->info('Email sent successfully', [
                'to' => $to,
                'subject' => $subject,
            ]);

            return true;
        } catch (PHPMailerException $e) {
            $this->logger->error('Failed to send email', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
                'mailer_error' => $this->mailer->ErrorInfo,
            ]);

            return false;
        }
    }

    /**
     * Send email to multiple recipients
     *
     * @param array<string> $recipients Array of email addresses
     * @param string $subject Email subject
     * @param string $htmlBody HTML body content
     * @param string|null $textBody Plain text body (optional)
     * @return bool True if sent successfully, false otherwise
     */
    public function sendBulk(
        array $recipients,
        string $subject,
        string $htmlBody,
        ?string $textBody = null
    ): bool {
        if (!$this->isConfigured) {
            $this->logger->error('Cannot send bulk email - EmailService not configured');
            return false;
        }

        try {
            // Reset recipients
            $this->mailer->clearAddresses();

            // Add all recipients
            foreach ($recipients as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $this->mailer->addAddress($email);
                }
            }

            // Set subject and body
            $this->mailer->Subject = $subject;
            $this->mailer->isHTML(true);
            $this->mailer->Body = $htmlBody;
            $this->mailer->AltBody = $textBody ?? strip_tags($htmlBody);

            // Send email
            $this->mailer->send();

            $this->logger->info('Bulk email sent successfully', [
                'recipients_count' => count($recipients),
                'subject' => $subject,
            ]);

            return true;
        } catch (PHPMailerException $e) {
            $this->logger->error('Failed to send bulk email', [
                'recipients_count' => count($recipients),
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send email with attachment
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $htmlBody HTML body content
     * @param string $attachmentPath Path to attachment file
     * @param string|null $attachmentName Custom name for attachment (optional)
     * @param string|null $textBody Plain text body (optional)
     * @return bool True if sent successfully, false otherwise
     */
    public function sendWithAttachment(
        string $to,
        string $subject,
        string $htmlBody,
        string $attachmentPath,
        ?string $attachmentName = null,
        ?string $textBody = null
    ): bool {
        if (!$this->isConfigured) {
            $this->logger->error('Cannot send email with attachment - EmailService not configured');
            return false;
        }

        try {
            // Reset recipients
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();

            // Set recipient
            $this->mailer->addAddress($to);

            // Add attachment
            if (file_exists($attachmentPath)) {
                $this->mailer->addAttachment($attachmentPath, $attachmentName ?? basename($attachmentPath));
            } else {
                throw new \RuntimeException("Attachment file not found: {$attachmentPath}");
            }

            // Set subject and body
            $this->mailer->Subject = $subject;
            $this->mailer->isHTML(true);
            $this->mailer->Body = $htmlBody;
            $this->mailer->AltBody = $textBody ?? strip_tags($htmlBody);

            // Send email
            $this->mailer->send();

            $this->logger->info('Email with attachment sent successfully', [
                'to' => $to,
                'subject' => $subject,
                'attachment' => $attachmentPath,
            ]);

            return true;
        } catch (PHPMailerException | \RuntimeException $e) {
            $this->logger->error('Failed to send email with attachment', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send a password reset email (helper method)
     *
     * @param string $to Recipient email address
     * @param string $recipientName Recipient name
     * @param string $resetLink Password reset link
     * @return bool True if sent successfully, false otherwise
     */
    public function sendPasswordReset(string $to, string $recipientName, string $resetLink): bool
    {
        $htmlBody = $this->getPasswordResetHtml($recipientName, $resetLink);
        $textBody = $this->getPasswordResetText($recipientName, $resetLink);

        return $this->send(
            $to,
            'Password Reset Request - Know My Patient',
            $htmlBody,
            $textBody,
            $recipientName
        );
    }

    /**
     * Send a welcome email (helper method)
     *
     * @param string $to Recipient email address
     * @param string $recipientName Recipient name
     * @return bool True if sent successfully, false otherwise
     */
    public function sendWelcomeEmail(string $to, string $recipientName): bool
    {
        $htmlBody = $this->getWelcomeEmailHtml($recipientName);
        $textBody = $this->getWelcomeEmailText($recipientName);

        return $this->send(
            $to,
            'Welcome to Know My Patient',
            $htmlBody,
            $textBody,
            $recipientName
        );
    }

    /**
     * Test email configuration by sending a test email
     *
     * @param string $to Test recipient email address
     * @return bool True if test passed, false otherwise
     */
    public function testConnection(string $to): bool
    {
        return $this->send(
            $to,
            'Test Email - Know My Patient',
            '<h1>Test Email</h1><p>This is a test email from Know My Patient.</p>',
            'Test Email - This is a test email from Know My Patient.'
        );
    }

    /**
     * Send NHS verification email with token
     */
    public function sendNhsVerificationEmail(string $email, string $token): bool
    {
        try {
            $this->mailer->setFrom('no-reply@knowmypatient.com', 'Know My Patient');
            $this->mailer->addAddress($email);
            $this->mailer->Subject = 'NHS Email Verification';
            $verificationUrl = $_ENV['APP_URL'] . '/nhsverify/confirm?token=' . urlencode($token);
            $body = "<p>Please verify your NHS email by clicking the link below:</p>"
                . "<p><a href='$verificationUrl'>$verificationUrl</a></p>";
            $this->mailer->isHTML(true);
            $this->mailer->Body = $body;
            $this->mailer->AltBody = "Please verify your NHS email: $verificationUrl";
            $this->mailer->send();
            return true;
        } catch (PHPMailerException $e) {
            $this->logger->error('Failed to send NHS verification email: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get password reset email HTML template
     *
     * @param string $recipientName Recipient name
     * @param string $resetLink Reset link
     * @return string HTML content
     */
    private function getPasswordResetHtml(string $recipientName, string $resetLink): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #007bff; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .button { display: inline-block; padding: 12px 24px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 20px 0; }
                .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Password Reset Request</h1>
                </div>
                <div class="content">
                    <p>Hello {$recipientName},</p>
                    <p>We received a request to reset your password for your Know My Patient account.</p>
                    <p>Click the button below to reset your password:</p>
                    <p style="text-align: center;">
                        <a href="{$resetLink}" class="button">Reset Password</a>
                    </p>
                    <p>Or copy and paste this link into your browser:</p>
                    <p style="word-break: break-all;">{$resetLink}</p>
                    <p><strong>This link will expire in 1 hour.</strong></p>
                    <p>If you didn't request a password reset, please ignore this email or contact support if you have concerns.</p>
                </div>
                <div class="footer">
                    <p>&copy; 2025 Know My Patient. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }

    /**
     * Get password reset email plain text template
     *
     * @param string $recipientName Recipient name
     * @param string $resetLink Reset link
     * @return string Plain text content
     */
    private function getPasswordResetText(string $recipientName, string $resetLink): string
    {
        return <<<TEXT
        Hello {$recipientName},

        We received a request to reset your password for your Know My Patient account.

        To reset your password, please visit the following link:
        {$resetLink}

        This link will expire in 1 hour.

        If you didn't request a password reset, please ignore this email or contact support if you have concerns.

        Best regards,
        Know My Patient Team
        TEXT;
    }

    /**
     * Get welcome email HTML template
     *
     * @param string $recipientName Recipient name
     * @return string HTML content
     */
    private function getWelcomeEmailHtml(string $recipientName): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #007bff; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Welcome to Know My Patient!</h1>
                </div>
                <div class="content">
                    <p>Hello {$recipientName},</p>
                    <p>Thank you for registering with Know My Patient.</p>
                    <p>Your account has been successfully created and you can now access all features.</p>
                    <p>If you have any questions, please don't hesitate to contact our support team.</p>
                </div>
                <div class="footer">
                    <p>&copy; 2025 Know My Patient. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }

    /**
     * Get welcome email plain text template
     *
     * @param string $recipientName Recipient name
     * @return string Plain text content
     */
    private function getWelcomeEmailText(string $recipientName): string
    {
        return <<<TEXT
        Hello {$recipientName},

        Thank you for registering with Know My Patient.

        Your account has been successfully created and you can now access all features.

        If you have any questions, please don't hesitate to contact our support team.

        Best regards,
        Know My Patient Team
        TEXT;
    }

    /**
     * Check if email service is configured
     *
     * @return bool True if configured, false otherwise
     */
    public function isConfigured(): bool
    {
        return $this->isConfigured;
    }
}
