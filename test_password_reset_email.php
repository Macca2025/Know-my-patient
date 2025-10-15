<?php

/**
 * Test Password Reset Email
 * 
 * This script tests the password reset email functionality
 * to ensure it uses EmailService with .env configuration
 * 
 * Usage: php test_password_reset_email.php
 */

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use App\Application\Services\EmailService;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Load environment variables
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

echo "Testing Password Reset Email Functionality\n";
echo "==========================================\n\n";

// Create logger
$logger = new Logger('password-reset-test');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::INFO));

// Display current SMTP configuration
echo "Current SMTP Configuration (from .env):\n";
echo "--------------------------------------\n";
echo "Host: " . ($_ENV['SMTP_HOST'] ?? 'not set') . "\n";
echo "Port: " . ($_ENV['SMTP_PORT'] ?? 'not set') . "\n";
echo "Username: " . ($_ENV['SMTP_USERNAME'] ?? 'not set') . "\n";
echo "From Address: " . ($_ENV['MAIL_FROM_ADDRESS'] ?? 'not set') . "\n";
echo "From Name: " . ($_ENV['MAIL_FROM_NAME'] ?? 'not set') . "\n\n";

// Create EmailService instance
echo "Creating EmailService...\n";
$emailService = new EmailService($logger);

if (!$emailService->isConfigured()) {
    echo "❌ EmailService is not configured properly!\n";
    echo "Please check your .env file and ensure all SMTP settings are correct.\n";
    exit(1);
}

echo "✅ EmailService configured successfully\n\n";

// Test data - simulating a password reset request
$testUser = [
    'id' => 999,
    'email' => $_ENV['SMTP_USERNAME'] ?? 'test@example.com', // Send to configured email
    'first_name' => 'Test User'
];

$testResetToken = bin2hex(random_bytes(32));
$testResetLink = "http://localhost:8080/reset-password?token={$testResetToken}";

echo "Test Data:\n";
echo "----------\n";
echo "Recipient: {$testUser['email']}\n";
echo "Name: {$testUser['first_name']}\n";
echo "Reset Link: {$testResetLink}\n\n";

// Create email templates (matching PasswordResetController)
$htmlBody = getPasswordResetHtml($testUser, $testResetLink);
$textBody = getPasswordResetText($testUser, $testResetLink);

echo "Sending password reset email...\n";
$success = $emailService->send(
    $testUser['email'],
    'Password Reset Request - Know My Patient',
    $htmlBody,
    $textBody,
    $testUser['first_name']
);

if ($success) {
    echo "\n✅ Password reset email sent successfully!\n\n";
    echo "Summary:\n";
    echo "--------\n";
    echo "✓ EmailService is working correctly\n";
    echo "✓ Using SMTP settings from .env file\n";
    echo "✓ Email sent to: {$testUser['email']}\n";
    echo "✓ PasswordResetController will use the same configuration\n\n";
    echo "Check your inbox at {$testUser['email']} to verify the email.\n";
    exit(0);
} else {
    echo "\n❌ Failed to send password reset email\n";
    echo "Check the logs above for error details.\n";
    exit(1);
}

/**
 * Get password reset HTML email (matching PasswordResetController)
 */
function getPasswordResetHtml(array $user, string $resetLink): string
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
 * Get password reset plain text email (matching PasswordResetController)
 */
function getPasswordResetText(array $user, string $resetLink): string
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
