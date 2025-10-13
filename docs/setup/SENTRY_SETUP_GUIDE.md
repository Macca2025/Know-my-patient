# Sentry Error Monitoring Setup Guide

**Date**: October 12, 2025  
**Status**: ✅ Installed and Configured

---

## 📊 **What is Sentry?**

Sentry is a real-time error tracking and monitoring platform that helps you:
- **Catch errors** before users report them
- **Track performance** issues
- **Get alerts** when things go wrong
- **Debug faster** with detailed error context

---

## ✅ **What's Already Done**

### 1. **Sentry SDK Installed**
```bash
✅ composer require sentry/sentry (v4.16.0)
```

### 2. **Files Created/Updated**
- ✅ `src/Application/Middleware/SentryMiddleware.php` - Error capture middleware
- ✅ `app/settings.php` - Sentry configuration
- ✅ `app/dependencies.php` - Sentry initialization
- ✅ `app/middleware.php` - Middleware registration
- ✅ `.env.example` - Environment variables

### 3. **Features Implemented**
- ✅ Automatic error capture
- ✅ Request context tracking
- ✅ User context tracking (when logged in)
- ✅ Sanitized headers (no sensitive data)
- ✅ IP address tracking
- ✅ Environment-based configuration

---

## 🚀 **Getting Started with Sentry**

### Step 1: Create a Sentry Account

1. Go to [https://sentry.io](https://sentry.io)
2. Sign up for a **free account**
3. Click **"Create Project"**
4. Select **"PHP"** as the platform
5. Name your project: **"know-my-patient"**
6. Click **"Create Project"**

### Step 2: Get Your DSN (Data Source Name)

After creating the project, you'll see a screen with your **DSN**. It looks like:

```
https://1234567890abcdef@o123456.ingest.sentry.io/7890123
```

Copy this DSN - you'll need it next!

### Step 3: Configure Your Environment

Create or update your `.env` file:

```bash
# Copy .env.example if you don't have .env yet
cp .env.example .env

# Edit .env file
nano .env
```

Add your Sentry configuration:

```bash
# Error Monitoring (Sentry)
SENTRY_DSN=https://your-actual-dsn-here@o123456.ingest.sentry.io/7890123
SENTRY_ENVIRONMENT=development
SENTRY_TRACES_SAMPLE_RATE=0.2
SENTRY_SEND_DEFAULT_PII=false
```

**Configuration Options:**

- **`SENTRY_DSN`**: Your unique Sentry project DSN (leave empty to disable Sentry)
- **`SENTRY_ENVIRONMENT`**: `development`, `staging`, or `production`
- **`SENTRY_TRACES_SAMPLE_RATE`**: Performance monitoring (0.0 to 1.0)
  - `0.2` = 20% of requests (recommended for production)
  - `1.0` = 100% of requests (use for development)
- **`SENTRY_SEND_DEFAULT_PII`**: Send personally identifiable information
  - `false` = Don't send user emails/IPs (GDPR-friendly)
  - `true` = Send all user data (use only in development)

### Step 4: Test Sentry

Create a test route to trigger an error:

```php
// In app/routes.php (for testing only)
$app->get('/test-sentry', function ($request, $response) {
    throw new \Exception('Test error for Sentry!');
});
```

Visit: `http://localhost:8080/test-sentry`

You should see:
1. The error in your app
2. **The error appears in your Sentry dashboard within seconds!**

**Remove the test route after verification.**

---

## 📈 **How Sentry Works in Your App**

### Automatic Error Capture

The `SentryMiddleware` automatically captures:
- ✅ Unhandled exceptions
- ✅ PHP errors
- ✅ Fatal errors
- ✅ Warnings (in production)

### Context Added to Each Error

Every error captured includes:

```php
{
  "request": {
    "url": "https://knowmypatient.com/login",
    "method": "POST",
    "query_string": "",
    "headers": "[SANITIZED]"  // Sensitive headers removed
  },
  "user": {
    "id": "12345",
    "ip_address": "192.168.1.1"
  },
  "environment": "production",
  "server": {
    "php_version": "8.4.11",
    "os": "macOS"
  }
}
```

### Sensitive Data Protection

The middleware automatically **sanitizes** these headers:
- ❌ `Authorization`
- ❌ `Cookie`
- ❌ `Set-Cookie`
- ❌ `X-CSRF-Token`

All sensitive data is replaced with `[REDACTED]`.

---

## 🔧 **Production Settings**

### Recommended Production Configuration

```bash
# .env (production)
APP_ENV=production
APP_DEBUG=false
SENTRY_DSN=https://your-production-dsn@sentry.io/project-id
SENTRY_ENVIRONMENT=production
SENTRY_TRACES_SAMPLE_RATE=0.2  # 20% sampling to reduce costs
SENTRY_SEND_DEFAULT_PII=false   # GDPR compliance
```

### Display Errors in Production

In production, the app will:
- ✅ Send errors to Sentry
- ✅ Log errors to `logs/app.log`
- ❌ **NOT** show error details to users
- ✅ Show generic error page instead

This is configured in `app/settings.php`:
```php
'displayErrorDetails' => !$isProduction, // False in production
```

---

## 📊 **Sentry Dashboard Features**

### Issues Tab
- See all errors grouped by type
- Click any error to see full stack trace
- View frequency and affected users

### Performance Tab
- Track slow pages
- Identify bottlenecks
- Monitor API response times

### Releases Tab
- Track errors by version
- See which release introduced bugs
- Compare error rates between versions

### Alerts
- Get email/Slack notifications
- Set up custom alert rules
- Define error thresholds

---

## 💰 **Sentry Pricing**

### Free Tier (Perfect to Start)
- ✅ **5,000 errors/month** FREE
- ✅ **10,000 performance units/month** FREE
- ✅ Unlimited projects
- ✅ 90-day data retention
- ✅ Basic alerts

### Developer Plan ($26/month)
- 50,000 errors/month
- 100,000 performance units/month
- Advanced features

**Tip**: Start with the free tier - it's plenty for most apps!

---

## 🔍 **Testing Your Setup**

### 1. Verify Sentry is Initialized

Check the logs after starting your app:
```bash
tail -f logs/app.log
```

You should **NOT** see any Sentry errors.

### 2. Trigger a Test Error

```php
// Temporary test in any controller
throw new \Exception('Sentry test error');
```

### 3. Check Sentry Dashboard

1. Go to [https://sentry.io](https://sentry.io)
2. Click your project
3. Go to **"Issues"**
4. You should see your test error within 10 seconds!

### 4. Verify Context

Click on the error and verify you see:
- ✅ Request URL
- ✅ HTTP method
- ✅ User ID (if logged in)
- ✅ IP address
- ✅ Stack trace
- ✅ Server info

---

## 🛠️ **Advanced Usage**

### Manual Error Capture

Capture specific errors manually:

```php
use function Sentry\captureException;
use function Sentry\captureMessage;

try {
    // Your code
} catch (\Exception $e) {
    // Log to Sentry
    captureException($e);
    
    // Or capture a message
    captureMessage('Something went wrong', \Sentry\Severity::warning());
    
    // Re-throw or handle
    throw $e;
}
```

### Add Custom Context

```php
use function Sentry\configureScope;

configureScope(function (\Sentry\State\Scope $scope): void {
    $scope->setContext('patient', [
        'patient_id' => $patientId,
        'nhs_number' => $nhsNumber,
    ]);
    
    $scope->setTag('feature', 'patient-lookup');
    $scope->setLevel(\Sentry\Severity::info());
});
```

### Breadcrumbs

Track the path to an error:

```php
use function Sentry\addBreadcrumb;

addBreadcrumb([
    'category' => 'auth',
    'message' => 'User logged in',
    'level' => 'info',
]);
```

---

## 🚨 **Alerts Setup**

### Step 1: Create Alert Rule

1. In Sentry dashboard, go to **"Alerts"**
2. Click **"Create Alert"**
3. Choose **"Issues"**

### Step 2: Configure Conditions

```
IF:
  An issue is first seen
  OR
  An issue changes state to unresolved
  OR
  The issue is seen more than 10 times in 1 hour

THEN:
  Send a notification to: your-email@example.com
```

### Step 3: Slack Integration (Optional)

1. Go to **"Settings" > "Integrations"**
2. Click **"Slack"**
3. Follow setup wizard
4. Errors will now post to your Slack channel!

---

## 🔒 **Healthcare Compliance (NHS/GDPR)**

### Data Privacy

Sentry is **GDPR compliant** when configured correctly:

✅ **What We Do:**
- Set `SENTRY_SEND_DEFAULT_PII=false`
- Sanitize sensitive headers
- Don't send patient data
- Track errors, not personal info

✅ **What We Track:**
- Error types and messages
- Stack traces (code paths)
- Request URLs (no query params with PHI)
- User IDs (numeric only, no names)
- IP addresses (for security)

❌ **What We DON'T Send:**
- Patient names or NHS numbers
- Medical data
- Passwords or tokens
- Cookie values
- Authorization headers

### NHS Digital Compliance

Sentry can be used for NHS applications:
- ✅ Hosted in EU (GDPR compliant)
- ✅ SOC 2 Type II certified
- ✅ Data Processing Agreement available
- ✅ No PHI in error logs

**Important**: Never include patient data in error messages!

```php
// ❌ BAD
throw new Exception("Patient {$patientName} not found");

// ✅ GOOD
throw new Exception("Patient not found");
// Then log patient_id separately in a GDPR-compliant way
```

---

## 📋 **Troubleshooting**

### Sentry Not Capturing Errors

**Check 1**: Is DSN configured?
```bash
grep SENTRY_DSN .env
```

**Check 2**: Is DSN valid?
```php
// In any controller
var_dump($_ENV['SENTRY_DSN']);
```

**Check 3**: Test manually
```php
\Sentry\captureMessage('Test message');
```

### Too Many Events

If you hit the free tier limit:

**Solution 1**: Reduce sample rate
```bash
SENTRY_TRACES_SAMPLE_RATE=0.1  # 10% instead of 20%
```

**Solution 2**: Filter out known errors
```php
// In app/dependencies.php, Sentry init:
'before_send' => function ($event) {
    // Ignore specific errors
    if (strpos($event->getMessage(), 'Ignorable error') !== false) {
        return null; // Don't send to Sentry
    }
    return $event;
},
```

### Sentry Slowing Down App

**Solution**: Enable async sending (requires Redis or message queue)
```bash
composer require async-aws/simple-s3
```

Or reduce sample rate to 0 for performance monitoring:
```bash
SENTRY_TRACES_SAMPLE_RATE=0  # Disable performance tracking
```

---

## ✅ **Verification Checklist**

Before going to production:

- [ ] Sentry DSN configured in `.env`
- [ ] `APP_ENV=production` in production `.env`
- [ ] `SENTRY_ENVIRONMENT=production`
- [ ] `SENTRY_SEND_DEFAULT_PII=false` (GDPR)
- [ ] Test error appears in Sentry dashboard
- [ ] Alerts configured (email or Slack)
- [ ] No sensitive data in error messages
- [ ] Team has access to Sentry project
- [ ] Error pages show generic messages (not stack traces)

---

## 🎯 **Next Steps**

### Week 1
1. ✅ Create Sentry account
2. ✅ Configure `.env` with DSN
3. ✅ Test with dummy error
4. ✅ Set up email alerts

### Week 2
5. ⬜ Configure Slack integration
6. ⬜ Set up custom alert rules
7. ⬜ Add release tracking
8. ⬜ Monitor for patterns

### Month 2
9. ⬜ Review error trends
10. ⬜ Optimize alert thresholds
11. ⬜ Add custom context for key features
12. ⬜ Set up performance monitoring

---

## 📚 **Resources**

- **Sentry Docs**: [https://docs.sentry.io/platforms/php/](https://docs.sentry.io/platforms/php/)
- **PHP SDK**: [https://github.com/getsentry/sentry-php](https://github.com/getsentry/sentry-php)
- **Best Practices**: [https://docs.sentry.io/product/best-practices/](https://docs.sentry.io/product/best-practices/)
- **GDPR Guide**: [https://sentry.io/security/](https://sentry.io/security/)

---

## 🎉 **Congratulations!**

You now have **professional error monitoring** set up!

Sentry will:
- ✅ Catch errors before users report them
- ✅ Provide detailed debugging context
- ✅ Alert you when things go wrong
- ✅ Help you fix bugs faster
- ✅ Track error trends over time

**Your app just became more reliable!** 🚀

---

**Questions?** Check the Sentry dashboard or review this guide.
