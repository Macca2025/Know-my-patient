<?php

/**
 * PHPMailer Test Script
 * 
 * This script tests the EmailService configuration
 * Usage: php test_email.php your-email@example.com
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

// Get recipient email from command line argument
$recipientEmail = $argv[1] ?? null;

if (!$recipientEmail) {
    echo "Usage: php test_email.php your-email@example.com\n";
    exit(1);
}

if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
    echo "Error: Invalid email address\n";
    exit(1);
}

echo "Testing PHPMailer Configuration...\n\n";

// Create logger
$logger = new Logger('email-test');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

// Display configuration
echo "SMTP Configuration:\n";
echo "==================\n";
echo "Host: " . ($_ENV['SMTP_HOST'] ?? 'not set') . "\n";
echo "Port: " . ($_ENV['SMTP_PORT'] ?? 'not set') . "\n";
echo "Auth: " . ($_ENV['SMTP_AUTH'] ?? 'not set') . "\n";
echo "Username: " . ($_ENV['SMTP_USERNAME'] ?? 'not set') . "\n";
echo "Password: " . (isset($_ENV['SMTP_PASSWORD']) && !empty($_ENV['SMTP_PASSWORD']) ? '***set***' : 'not set') . "\n";
echo "Encryption: " . ($_ENV['SMTP_ENCRYPTION'] ?? 'not set') . "\n";
echo "From: " . ($_ENV['MAIL_FROM_ADDRESS'] ?? 'not set') . "\n";
echo "From Name: " . ($_ENV['MAIL_FROM_NAME'] ?? 'not set') . "\n\n";

// Check if SMTP credentials are configured
if (empty($_ENV['SMTP_USERNAME']) || empty($_ENV['SMTP_PASSWORD'])) {
    echo "⚠️  WARNING: SMTP username or password not configured in .env file\n";
    echo "Please update your .env file with valid SMTP credentials.\n\n";
    
    echo "For Gmail:\n";
    echo "1. Go to your Google Account settings\n";
    echo "2. Enable 2-Step Verification\n";
    echo "3. Generate an App Password (Security > App passwords)\n";
    echo "4. Use the app password in SMTP_PASSWORD\n\n";
    
    echo "Update your .env file:\n";
    echo "SMTP_USERNAME=your-email@gmail.com\n";
    echo "SMTP_PASSWORD=your-16-character-app-password\n\n";
}

// Create EmailService instance
echo "Creating EmailService...\n";
$emailService = new EmailService($logger);

if (!$emailService->isConfigured()) {
    echo "❌ EmailService failed to configure. Check the logs above.\n";
    exit(1);
}

echo "✅ EmailService configured successfully\n\n";

// Send test email
echo "Sending test email to: {$recipientEmail}...\n";
$result = $emailService->testConnection($recipientEmail);

if ($result) {
    echo "\n✅ Test email sent successfully!\n";
    echo "Check your inbox at: {$recipientEmail}\n\n";
    
    echo "EmailService is ready to use!\n";
    echo "You can now use it in your controllers:\n\n";
    echo "Example usage:\n";
    echo "-------------\n";
    echo "\$emailService = \$container->get(\\App\\Application\\Services\\EmailService::class);\n";
    echo "\$emailService->send(\n";
    echo "    'recipient@example.com',\n";
    echo "    'Subject',\n";
    echo "    '<h1>HTML Body</h1>',\n";
    echo "    'Plain text body'\n";
    echo ");\n";
} else {
    echo "\n❌ Failed to send test email\n";
    echo "Check the error logs above for details\n\n";
    
    echo "Common issues:\n";
    echo "-------------\n";
    echo "1. Invalid SMTP credentials\n";
    echo "2. Gmail requires App Password (not regular password)\n";
    echo "3. SMTP port blocked by firewall\n";
    echo "4. 'Less secure app access' needs to be enabled (deprecated by Gmail)\n";
    echo "5. Two-factor authentication not configured\n";
    exit(1);
}
