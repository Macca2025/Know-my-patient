# PHPMailer Setup Guide

## Overview
PHPMailer v6.11.1 is installed and configured with a custom `EmailService` class for easy email sending throughout the application.

## Configuration

### 1. Environment Variables
Update your `.env` file with SMTP credentials:

```bash
# SMTP Configuration
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_AUTH=true
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
SMTP_ENCRYPTION=tls

# Email Addresses
MAIL_FROM_ADDRESS=noreply@knowmypatient.nhs.uk
MAIL_FROM_NAME="Know My Patient"
MAIL_REPLY_TO=support@knowmypatient.nhs.uk
```

### 2. SMTP Provider Configuration

#### Gmail
1. Enable 2-Step Verification in your Google Account
2. Go to Security > App passwords
3. Generate a new app password for "Mail"
4. Use the 16-character password in `SMTP_PASSWORD`

```bash
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=xxxx-xxxx-xxxx-xxxx
SMTP_ENCRYPTION=tls
```

#### Outlook/Office 365
```bash
SMTP_HOST=smtp.office365.com
SMTP_PORT=587
SMTP_USERNAME=your-email@outlook.com
SMTP_PASSWORD=your-password
SMTP_ENCRYPTION=tls
```

#### SendGrid
```bash
SMTP_HOST=smtp.sendgrid.net
SMTP_PORT=587
SMTP_USERNAME=apikey
SMTP_PASSWORD=your-sendgrid-api-key
SMTP_ENCRYPTION=tls
```

#### Mailgun
```bash
SMTP_HOST=smtp.mailgun.org
SMTP_PORT=587
SMTP_USERNAME=postmaster@yourdomain.mailgun.org
SMTP_PASSWORD=your-mailgun-password
SMTP_ENCRYPTION=tls
```

#### AWS SES
```bash
SMTP_HOST=email-smtp.us-east-1.amazonaws.com
SMTP_PORT=587
SMTP_USERNAME=your-ses-smtp-username
SMTP_PASSWORD=your-ses-smtp-password
SMTP_ENCRYPTION=tls
```

#### Mailtrap (For Testing)
```bash
SMTP_HOST=smtp.mailtrap.io
SMTP_PORT=2525
SMTP_USERNAME=your-mailtrap-username
SMTP_PASSWORD=your-mailtrap-password
SMTP_ENCRYPTION=tls
```

## Testing

### Test Email Configuration
Run the test script to verify your setup:

```bash
php test_email.php your-email@example.com
```

This will:
- Display your SMTP configuration
- Send a test email
- Report success or failure with detailed error messages

## Usage

### Basic Usage in Controllers

#### Inject EmailService via DI Container
```php
use App\Application\Services\EmailService;

class YourController
{
    private EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    public function sendEmail()
    {
        $success = $this->emailService->send(
            'recipient@example.com',
            'Subject Line',
            '<h1>HTML Content</h1><p>Your email body</p>',
            'Plain text fallback'
        );

        if ($success) {
            // Email sent successfully
        } else {
            // Email failed - check logs
        }
    }
}
```

### Available Methods

#### 1. Send Simple Email
```php
$emailService->send(
    string $to,                    // Recipient email
    string $subject,               // Email subject
    string $htmlBody,              // HTML body content
    ?string $textBody = null,      // Plain text body (optional)
    ?string $recipientName = null, // Recipient name (optional)
    array $additionalHeaders = []  // Custom headers (optional)
): bool
```

#### 2. Send Bulk Email
```php
$recipients = ['user1@example.com', 'user2@example.com'];
$emailService->sendBulk(
    array $recipients,
    string $subject,
    string $htmlBody,
    ?string $textBody = null
): bool
```

#### 3. Send Email with Attachment
```php
$emailService->sendWithAttachment(
    string $to,
    string $subject,
    string $htmlBody,
    string $attachmentPath,        // Path to file
    ?string $attachmentName = null, // Custom filename (optional)
    ?string $textBody = null
): bool
```

#### 4. Send Password Reset Email
```php
$emailService->sendPasswordReset(
    string $to,
    string $recipientName,
    string $resetLink
): bool
```

#### 5. Send Welcome Email
```php
$emailService->sendWelcomeEmail(
    string $to,
    string $recipientName
): bool
```

#### 6. Test Connection
```php
$emailService->testConnection(string $to): bool
```

### Example: Update PasswordResetController

Replace the existing PHPMailer code with EmailService:

```php
// Old way (in PasswordResetController.php)
$mail = new \PHPMailer\PHPMailer\PHPMailer(true);
$mail->isSMTP();
// ... lots of configuration code ...

// New way - inject EmailService
private EmailService $emailService;

public function __construct(
    PDO $pdo,
    SessionService $sessionService,
    Twig $twig,
    LoggerInterface $logger,
    EmailService $emailService  // Add this
) {
    $this->emailService = $emailService;
    // ... other assignments
}

// Then use it:
private function sendResetEmail(array $user, string $resetLink): void
{
    $success = $this->emailService->sendPasswordReset(
        $user['email'],
        $user['first_name'] ?? 'User',
        $resetLink
    );

    if ($success) {
        $this->logAuditEvent($user['id'], 'PASSWORD_RESET_EMAIL_SENT', [
            'email' => $user['email'],
        ]);
    } else {
        $this->logAuditEvent($user['id'], 'PASSWORD_RESET_EMAIL_FAILED', [
            'email' => $user['email'],
        ]);
    }
}
```

## Email Templates

The EmailService includes built-in templates for:
- Password reset emails
- Welcome emails

To customize templates, edit the private methods in `EmailService.php`:
- `getPasswordResetHtml()` / `getPasswordResetText()`
- `getWelcomeEmailHtml()` / `getWelcomeEmailText()`

Or create your own templates and pass them to the `send()` method.

## Logging

All email operations are automatically logged:
- Successful sends
- Failed sends with error details
- Configuration status

Check logs at: `logs/app.log`

## Troubleshooting

### Common Issues

#### 1. Gmail "Less secure app" Error
**Solution:** Use App Passwords instead of your regular Gmail password
1. Enable 2-Step Verification
2. Generate App Password in Security settings
3. Use 16-character app password in `.env`

#### 2. Connection Timeout
**Solution:** 
- Check firewall settings (port 587 or 465)
- Try alternative port (465 for SSL, 587 for TLS)
- Verify SMTP host is correct

#### 3. Authentication Failed
**Solution:**
- Double-check username/password in `.env`
- Ensure no extra spaces in credentials
- Try without authentication: `SMTP_AUTH=false`

#### 4. SSL Certificate Issues
**Solution:**
- Update CA certificates on your server
- For development only: Set `$mail->SMTPOptions` to disable verification (not recommended for production)

#### 5. Emails Going to Spam
**Solution:**
- Set up SPF, DKIM, and DMARC records for your domain
- Use a verified sender email address
- Avoid spam trigger words in subject/body
- Use a dedicated email service (SendGrid, Mailgun, AWS SES)

### Debug Mode

Enable verbose SMTP debugging in development:

Edit `EmailService.php` line 69:
```php
$this->mailer->SMTPDebug = 2; // 0=off, 1=client, 2=client+server
```

This will output detailed SMTP conversation to logs.

## Security Best Practices

1. **Never commit `.env` file** - It contains sensitive credentials
2. **Use App Passwords** - Don't use your main email password
3. **Enable 2FA** - On your email account
4. **Use dedicated email service** - For production (SendGrid, AWS SES, etc.)
5. **Validate email addresses** - Before sending
6. **Rate limit** - Prevent email abuse
7. **Log all sends** - For audit trail

## Production Recommendations

For production environments:

1. **Use a dedicated email service** (SendGrid, AWS SES, Mailgun)
2. **Set up domain authentication** (SPF, DKIM, DMARC)
3. **Monitor sending reputation**
4. **Implement queue system** for bulk emails
5. **Set up bounce handling**
6. **Configure proper error alerting**
7. **Use TLS encryption** (SMTP_ENCRYPTION=tls)

## File Locations

- **EmailService**: `src/Application/Services/EmailService.php`
- **DI Configuration**: `app/dependencies.php`
- **Environment Config**: `.env`
- **Test Script**: `test_email.php`
- **This Guide**: `PHPMAILER_SETUP.md`

## Support

For issues or questions:
1. Check logs at `logs/app.log`
2. Run test script: `php test_email.php your-email@example.com`
3. Review PHPMailer documentation: https://github.com/PHPMailer/PHPMailer
4. Check your email provider's SMTP documentation

## Next Steps

1. ✅ Update `.env` with your SMTP credentials
2. ✅ Run test script: `php test_email.php your-email@example.com`
3. ✅ Update `PasswordResetController` to use `EmailService`
4. ✅ Add welcome emails to registration flow
5. ✅ Create custom email templates as needed
6. ✅ Set up monitoring and alerting for email failures

## Example: Complete Implementation

Here's a complete example for sending a welcome email on registration:

```php
// In AuthController.php or RegistrationController.php

use App\Application\Services\EmailService;

class AuthController
{
    private EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    public function register(Request $request, Response $response): Response
    {
        // ... registration logic ...

        // Send welcome email
        $emailSent = $this->emailService->sendWelcomeEmail(
            $email,
            $firstName
        );

        if ($emailSent) {
            $this->logger->info('Welcome email sent', ['email' => $email]);
        } else {
            $this->logger->warning('Failed to send welcome email', ['email' => $email]);
            // Don't fail registration if email fails
        }

        // ... rest of registration ...
    }
}
```

Remember to add `EmailService` to the controller's DI configuration in `app/dependencies.php`.
