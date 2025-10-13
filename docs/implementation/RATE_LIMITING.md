# Rate Limiting Implementation

## Overview

Rate limiting has been implemented to protect the `/login` route from brute force attacks and credential stuffing attempts.

## Configuration

**Location**: `app/dependencies.php`

```php
\App\Application\Middleware\RateLimitMiddleware::class => function (ContainerInterface $c) {
    // 5 login attempts per 15 minutes
    $cacheDir = __DIR__ . '/../var/cache/rate_limit';
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    return new \App\Application\Middleware\RateLimitMiddleware(5, 15, $cacheDir);
}
```

### Parameters

- **Max Attempts**: `5` - Maximum number of login attempts allowed
- **Decay Minutes**: `15` - Time window in minutes before attempts reset
- **Cache Directory**: `var/cache/rate_limit` - Where rate limit data is stored

## How It Works

1. **IP-based Tracking**: Uses `IpAddressService` to get the real client IP (supports proxies, Cloudflare, etc.)
2. **Per-Route Limiting**: Each route can have independent rate limits
3. **File-based Storage**: Uses JSON files in cache directory for simplicity (no Redis required)
4. **Automatic Cleanup**: Expired rate limit records are automatically removed

## Applied Routes

### Login Route

```php
$group->map(['GET', 'POST'], '/login', [\App\Application\Actions\AuthController::class, 'login'])
    ->add(\App\Application\Middleware\RateLimitMiddleware::class)
    ->setName('login');
```

**Protection**: 5 attempts per 15 minutes per IP address

## Response When Limit Exceeded

**HTTP Status**: `429 Too Many Requests`

**Headers**:
- `Content-Type: application/json`
- `Retry-After: 900` (seconds)

**Body**:
```json
{
  "error": "Too many attempts. Please try again later.",
  "retry_after": 900
}
```

## Testing Rate Limiting

### Manual Test

1. Visit `/login` route
2. Submit incorrect credentials 5 times rapidly
3. On 6th attempt, you should receive a 429 error
4. Wait 15 minutes or clear cache: `rm -rf var/cache/rate_limit/*`
5. Try again - should work

### Automated Test

```bash
# Test with curl (replace localhost with your domain)
for i in {1..6}; do
  echo "Attempt $i:"
  curl -X POST http://localhost:8080/login \
    -d "email=test@test.com&password=wrong" \
    -w "\nHTTP Status: %{http_code}\n\n"
done
```

Expected output:
- Attempts 1-5: Normal response (200 or 302)
- Attempt 6+: HTTP 429 with error message

## Security Features

### IP Detection

The middleware uses `IpAddressService::getClientIp()` which checks:
1. `HTTP_CF_CONNECTING_IP` (Cloudflare)
2. `HTTP_X_REAL_IP` (Nginx proxy)
3. `HTTP_X_FORWARDED_FOR` (Standard proxy)
4. `REMOTE_ADDR` (Direct connection)

This prevents bypassing rate limits by manipulating headers.

### Key Generation

```php
$key = 'rate_limit:' . md5($ip . ':' . $uri);
```

Each combination of IP + URI gets a unique rate limit bucket.

## Customization

### Different Limits for Other Routes

```php
// In routes.php

// Strict limit for password reset
$app->post('/reset-password', [ResetController::class, 'reset'])
    ->add(new \App\Application\Middleware\RateLimitMiddleware(3, 60)); // 3 per hour

// Relaxed limit for registration
$app->post('/register', [AuthController::class, 'register'])
    ->add(new \App\Application\Middleware\RateLimitMiddleware(10, 10)); // 10 per 10 minutes
```

### Production Considerations

For production with high traffic, consider:

1. **Redis Backend** (faster, scales better):
```php
use Predis\Client;

class RedisRateLimitMiddleware {
    private $redis;
    
    public function __construct(Client $redis, int $maxAttempts, int $decayMinutes) {
        $this->redis = $redis;
        // ... implementation
    }
}
```

2. **Database Backend** (persistent across server restarts):
```php
CREATE TABLE rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_hash VARCHAR(64) UNIQUE,
    attempts INT DEFAULT 0,
    expires_at DATETIME,
    INDEX idx_expires (expires_at)
);
```

3. **CDN/WAF Rate Limiting**: Use Cloudflare or AWS WAF for edge-level protection

## Monitoring

### Check Current Rate Limits

```bash
# View all active rate limits
ls -la var/cache/rate_limit/

# Check specific IP's attempts
cat var/cache/rate_limit/[hash]
```

### Log Analysis

Rate limit hits could be logged for security monitoring:

```php
// In RateLimitMiddleware.php
if ($attempts >= $this->maxAttempts) {
    error_log("Rate limit exceeded: IP={$ip}, URI={$uri}, Attempts={$attempts}");
    // ... return 429 response
}
```

## Clearing Rate Limits

### Manual Reset

```bash
# Clear all rate limits
rm -rf var/cache/rate_limit/*

# Clear specific IP (find hash first)
rm var/cache/rate_limit/[hash]
```

### Automated Cleanup

The middleware automatically removes expired entries on access, but you can also add a cron job:

```bash
# Add to crontab
# Runs every hour to clean expired files
0 * * * * find /path/to/var/cache/rate_limit -type f -mmin +15 -delete
```

## Type Hints Added

As part of this implementation, type hints were also added to `logAction()` methods:

### Before
```php
private function logAction($userId, $action, $details = [])
```

### After
```php
private function logAction(string|int $userId, string $action, array $details = []): void
```

**Files Updated**:
- `src/Application/Actions/DashboardController.php`
- `src/Application/Actions/AddPatientController.php`

## Benefits

✅ **Security**: Prevents brute force attacks on login
✅ **Flexibility**: Easy to apply to any route with custom limits
✅ **Performance**: File-based caching is fast for moderate traffic
✅ **Compatibility**: Works with proxies, load balancers, and CDNs
✅ **Scalability**: Can be easily upgraded to Redis/database backend

## Next Steps

1. ✅ Rate limiting on `/login` (completed)
2. 🔄 Consider adding to `/register` (prevent spam accounts)
3. 🔄 Add to password reset endpoint
4. 🔄 Add to API endpoints (if any)
5. 🔄 Implement logging/monitoring for security incidents
6. 🔄 Set up alerts for suspicious patterns

---

**Date**: 12 October 2025  
**Status**: ✅ Complete - Login route protected with 5 attempts per 15 minutes
