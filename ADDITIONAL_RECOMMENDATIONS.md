# Additional Recommendations for Know My Patient

**Date**: 12 October 2025  
**Status**: Post PHPStan Level 6 Compliance

---

## 🎉 What's Already Excellent

✅ **PHPStan Level 6**: Zero errors - 100% compliant  
✅ **Type Safety**: All arrays and properties fully typed  
✅ **Query Optimization**: SELECT * replaced with explicit columns  
✅ **Rate Limiting**: Login route protected (5 attempts/15 min)  
✅ **CSRF Protection**: All forms secured  
✅ **Structured Logging**: Monolog with context  
✅ **Audit Trail**: Complete activity logging with IP tracking  
✅ **Code Quality**: Clean separation of concerns, DI container  

---

## 🔒 Security Enhancements

### 1. Upgrade Password Hashing Algorithm ⭐ HIGH PRIORITY

**Current Implementation:**
```php
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
```

**Issue**: `PASSWORD_DEFAULT` currently uses BCRYPT, which is secure but slower and less memory-hard than Argon2.

**Recommendation**: Upgrade to Argon2id (most secure option)

**Files to Update:**
- `src/Application/Actions/AuthController.php` (lines 96, 190)
- `src/Application/Actions/DashboardController.php` (line 254)

**Implementation:**

```php
// Option 1: Use PASSWORD_ARGON2ID (recommended if available)
if (defined('PASSWORD_ARGON2ID')) {
    $hashedPassword = password_hash($password, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536,  // 64 MB
        'time_cost'   => 4,      // 4 iterations
        'threads'     => 2       // 2 parallel threads
    ]);
} else {
    // Fallback to BCRYPT with high cost
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

// Option 2: Simple upgrade (if PHP 7.2+)
$hashedPassword = password_hash($password, PASSWORD_ARGON2ID);
```

**Benefits:**
- ✅ Resistant to GPU attacks
- ✅ Memory-hard (prevents ASIC attacks)
- ✅ Recommended by OWASP
- ✅ Already supported in PHP 7.2+

**Migration Strategy:**
```php
// Passwords will be upgraded on next login automatically
if (password_verify($password, $hash)) {
    // If old algorithm, rehash
    if (password_needs_rehash($hash, PASSWORD_ARGON2ID)) {
        $newHash = password_hash($password, PASSWORD_ARGON2ID);
        $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
        $stmt->execute([$newHash, $userId]);
    }
    // Continue with login...
}
```

---

### 2. Apply Database Indexes 🚀 HIGH PRIORITY

**Status**: Indexes file created but **NOT YET APPLIED**

**File**: `database_indexes.sql`

**Action Required:**
```bash
# Connect to MySQL
mysql -u root -p know_my_patient

# Apply indexes
source database_indexes.sql

# Or via command line
mysql -u root -p know_my_patient < database_indexes.sql
```

**Expected Performance Gains:**
- User lookups by email: **80% faster**
- Audit log queries: **90% faster**
- Patient profile searches: **70% faster**
- Support message filtering: **60% faster**

**Verify Indexes:**
```sql
SHOW INDEX FROM users;
SHOW INDEX FROM patient_profiles;
SHOW INDEX FROM audit_log;
SHOW INDEX FROM card_requests;
SHOW INDEX FROM support_messages;
SHOW INDEX FROM onboarding_enquiries;
```

---

### 3. Extend Rate Limiting to Other Routes ⭐ MEDIUM PRIORITY

**Currently Protected:**
- ✅ `/login` - 5 attempts per 15 minutes

**Recommendations:**

#### A. Protect Registration Endpoint
```php
// In app/routes.php
$app->post('/register', \App\Application\Actions\AuthController::class . ':register')
    ->add(\App\Application\Middleware\RateLimitMiddleware::class);
```

**Configuration** (in `app/dependencies.php`):
```php
// Create separate middleware instance for registration
'RegistrationRateLimitMiddleware' => function (ContainerInterface $c) {
    $cacheDir = __DIR__ . '/../var/cache/rate_limit';
    // 3 registrations per 60 minutes (prevent spam accounts)
    return new \App\Application\Middleware\RateLimitMiddleware(3, 60, $cacheDir);
}
```

#### B. Protect Password Reset (Future Feature)
```php
$app->post('/forgot-password', ...)
    ->add(\App\Application\Middleware\RateLimitMiddleware::class);
```

#### C. Protect Patient Profile API
```php
// 100 lookups per 60 minutes per IP
$app->get('/api/patient/{uid}', ...)
    ->add(new RateLimitMiddleware(100, 60, $cacheDir));
```

---

### 4. Environment-Based Configuration 🔧 MEDIUM PRIORITY

**Current Issue**: `displayErrorDetails` hardcoded to `true` in production

**File**: `app/settings.php`

**Current Code:**
```php
return new Settings([
    'displayErrorDetails' => true, // Should be set to false in production
    'logError'            => true,
    'logErrorDetails'     => true,
```

**Recommended Fix:**

1. **Create `.env` file:**
```bash
# .env (add to .gitignore)
APP_ENV=development
APP_DEBUG=true
DATABASE_HOST=localhost
DATABASE_NAME=know_my_patient
DATABASE_USER=root
DATABASE_PASS=your_password
```

2. **Install vlucas/phpdotenv** (already in composer.json):
```bash
composer require vlucas/phpdotenv
```

3. **Update app/settings.php:**
```php
use Dotenv\Dotenv;

// Load .env file
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        SettingsInterface::class => function () {
            $env = $_ENV['APP_ENV'] ?? 'production';
            $debug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);
            
            return new Settings([
                'displayErrorDetails' => $debug && $env !== 'production',
                'logError'            => true,
                'logErrorDetails'     => $env !== 'production',
                'logger' => [
                    'name' => 'slim-app',
                    'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
                    'level' => $env === 'production' ? Logger::WARNING : Logger::DEBUG,
                ],
            ]);
        }
    ]);
};
```

4. **Update .gitignore:**
```
.env
.env.local
```

5. **Create .env.example:**
```bash
APP_ENV=production
APP_DEBUG=false
DATABASE_HOST=localhost
DATABASE_NAME=know_my_patient
DATABASE_USER=dbuser
DATABASE_PASS=changeme
```

---

### 5. Add Password Reset Functionality 📧 MEDIUM PRIORITY

**Status**: Currently missing (mentioned in templates but not implemented)

**Required Components:**

1. **Database table:**
```sql
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_token (token)
);
```

2. **Route:**
```php
$app->get('/forgot-password', [AuthController::class, 'forgotPasswordForm']);
$app->post('/forgot-password', [AuthController::class, 'forgotPasswordSubmit'])
    ->add(RateLimitMiddleware::class); // 3 attempts per hour
$app->get('/reset-password/{token}', [AuthController::class, 'resetPasswordForm']);
$app->post('/reset-password', [AuthController::class, 'resetPasswordSubmit']);
```

3. **Email Service Integration:**
- Use PHPMailer (already in composer.json)
- Send secure reset links with token expiry (15-60 minutes)
- Log all reset attempts to audit_log

---

### 6. Implement HTTPS Enforcement 🔐 HIGH PRIORITY (Production)

**Current**: HTTP allowed

**Recommendation**: Force HTTPS in production

**Implementation:**

1. **Add middleware** (`src/Application/Middleware/HttpsMiddleware.php`):
```php
<?php
namespace App\Application\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class HttpsMiddleware implements MiddlewareInterface
{
    private bool $enforceHttps;

    public function __construct(bool $enforceHttps = true)
    {
        $this->enforceHttps = $enforceHttps;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        if ($this->enforceHttps && $request->getUri()->getScheme() !== 'https') {
            $httpsUri = $request->getUri()->withScheme('https')->withPort(443);
            $response = new \Slim\Psr7\Response();
            return $response
                ->withHeader('Location', (string) $httpsUri)
                ->withStatus(301);
        }

        return $handler->handle($request);
    }
}
```

2. **Register in middleware.php:**
```php
use App\Application\Middleware\HttpsMiddleware;

return function (App $app) {
    $settings = $app->getContainer()->get(SettingsInterface::class);
    $env = $_ENV['APP_ENV'] ?? 'production';
    
    // Force HTTPS in production
    if ($env === 'production') {
        $app->add(new HttpsMiddleware(true));
    }
    
    // ... other middleware
};
```

---

## 🎯 Code Quality Improvements

### 7. Add Unit Tests 🧪 MEDIUM PRIORITY

**Status**: Test files exist but minimal coverage

**Recommendation**: Create comprehensive test suite

**Priority Test Cases:**

```php
// tests/Unit/Services/SessionServiceTest.php
class SessionServiceTest extends TestCase
{
    public function testGetReturnsDefaultWhenKeyNotSet(): void
    {
        $service = new SessionService();
        $result = $service->get('nonexistent', 'default');
        $this->assertEquals('default', $result);
    }
    
    public function testSetStoresValue(): void
    {
        $service = new SessionService();
        $service->set('key', 'value');
        $this->assertEquals('value', $service->get('key'));
    }
}

// tests/Unit/Services/RateLimitMiddlewareTest.php
class RateLimitMiddlewareTest extends TestCase
{
    public function testAllowsRequestBelowLimit(): void
    {
        // Test rate limiting logic
    }
    
    public function testBlocksRequestAboveLimit(): void
    {
        // Test blocking behavior
    }
}

// tests/Unit/Actions/AuthControllerTest.php
class AuthControllerTest extends TestCase
{
    public function testLoginWithValidCredentials(): void
    {
        // Mock PDO, test successful login
    }
    
    public function testLoginWithInvalidCredentials(): void
    {
        // Test failed login
    }
}
```

**Run Tests:**
```bash
vendor/bin/phpunit tests/
```

---

### 8. Implement Caching Layer 🚀 LOW PRIORITY (Optimization)

**Status**: CacheService created but not actively used

**File**: `src/Application/Services/CacheService.php`

**Recommendations:**

#### A. Cache Testimonials on Home Page
```php
// In HomeController.php
public function home(Request $request, Response $response): Response
{
    // Cache for 1 hour
    $testimonials = $this->cacheService->remember('testimonials_homepage', function() {
        return $this->testimonialRepo->getAllApproved();
    }, 3600);
    
    // ... render
}
```

#### B. Cache User Permissions/Roles
```php
// In middleware
$userRole = $cacheService->remember("user_role_{$userId}", function() use ($userId) {
    $stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    return $stmt->fetchColumn();
}, 900); // 15 minutes
```

#### C. Cache Static Content
```php
// Resources, FAQs, etc.
$resources = $cacheService->remember('admin_resources', function() {
    return $this->resourceRepo->getAll();
}, 1800); // 30 minutes
```

---

### 9. Add API Rate Limiting Headers 📡 LOW PRIORITY

**Current**: Rate limiting works but no feedback to clients

**Recommendation**: Add headers to responses

**Implementation:**

```php
// In RateLimitMiddleware.php
$response = $handler->handle($request);

// Add rate limit headers
$response = $response
    ->withHeader('X-RateLimit-Limit', (string) $this->maxAttempts)
    ->withHeader('X-RateLimit-Remaining', (string) ($this->maxAttempts - $attempts))
    ->withHeader('X-RateLimit-Reset', (string) $resetTime);

return $response;
```

**Benefits:**
- Clients can see their limits
- Better API documentation
- Debugging assistance

---

## 📊 Monitoring & Logging

### 10. Set Up Error Monitoring Service 📈 MEDIUM PRIORITY

**Options:**
1. **Sentry** (recommended) - Real-time error tracking
2. **Rollbar** - Error monitoring and alerting
3. **Bugsnag** - Error reporting platform

**Implementation (Sentry):**

```bash
composer require sentry/sentry
```

```php
// In app/dependencies.php
use Sentry\State\Hub;

Sentry\init([
    'dsn' => $_ENV['SENTRY_DSN'] ?? null,
    'environment' => $_ENV['APP_ENV'] ?? 'production',
    'traces_sample_rate' => 1.0,
]);

// In error handler
try {
    // ... code
} catch (\Exception $e) {
    Sentry\captureException($e);
    throw $e;
}
```

---

### 11. Add Health Check Endpoint 🏥 LOW PRIORITY

**Purpose**: Monitor application health for uptime services

**Implementation:**

```php
// In app/routes.php
$app->get('/health', function (Request $request, Response $response) use ($app) {
    $container = $app->getContainer();
    
    // Check database connection
    try {
        $pdo = $container->get(\PDO::class);
        $pdo->query('SELECT 1');
        $dbStatus = 'healthy';
    } catch (\Exception $e) {
        $dbStatus = 'unhealthy';
    }
    
    // Check writable directories
    $logsWritable = is_writable(__DIR__ . '/../logs');
    $cacheWritable = is_writable(__DIR__ . '/../var/cache');
    
    $status = [
        'status' => $dbStatus === 'healthy' && $logsWritable ? 'healthy' : 'unhealthy',
        'timestamp' => date('Y-m-d H:i:s'),
        'checks' => [
            'database' => $dbStatus,
            'logs_writable' => $logsWritable,
            'cache_writable' => $cacheWritable,
        ]
    ];
    
    $response->getBody()->write(json_encode($status));
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus($status['status'] === 'healthy' ? 200 : 503);
});
```

---

## 📦 Deployment & Infrastructure

### 12. Production Deployment Checklist ✅ HIGH PRIORITY

Before deploying to production:

#### Security
- [ ] Set `APP_ENV=production` in `.env`
- [ ] Set `APP_DEBUG=false`
- [ ] Force HTTPS (HttpsMiddleware)
- [ ] Use strong database credentials
- [ ] Enable password hashing with Argon2id
- [ ] Apply all database indexes
- [ ] Review and limit error messages to users
- [ ] Enable secure session cookies (`secure`, `httponly`, `samesite`)

#### Performance
- [ ] Apply database indexes (`database_indexes.sql`)
- [ ] Enable OPcache in `php.ini`:
  ```ini
  opcache.enable=1
  opcache.memory_consumption=256
  opcache.max_accelerated_files=20000
  opcache.validate_timestamps=0
  ```
- [ ] Enable rate limiting on all sensitive endpoints
- [ ] Implement caching where appropriate
- [ ] Minify CSS/JS assets

#### Monitoring
- [ ] Set up error monitoring (Sentry/Rollbar)
- [ ] Configure log rotation (`logrotate`)
- [ ] Set up uptime monitoring (UptimeRobot, Pingdom)
- [ ] Configure backup automation

#### Code
- [ ] Run `vendor/bin/phpstan analyse` (should show 0 errors ✅)
- [ ] Run unit tests: `vendor/bin/phpunit`
- [ ] Review `logs/app.log` for warnings
- [ ] Clear all caches: `rm -rf var/cache/*`

---

## 🔄 Future Enhancements

### 13. Consider Database Migrations Tool 🔧 LOW PRIORITY

**Current**: Manual SQL scripts

**Recommendation**: Use Doctrine Migrations or Phinx

**Benefits:**
- Version control for database schema
- Rollback capability
- Team synchronization
- Automated deployment

**Implementation:**
```bash
composer require phinx/phinx

# Create migration
vendor/bin/phinx create AddPasswordResetsTable

# Run migrations
vendor/bin/phinx migrate
```

---

### 14. Add API Versioning (If Building API) 🔮 FUTURE

**Current**: Single patient lookup endpoint

**If expanding API:**
```php
// Group API routes by version
$app->group('/api/v1', function (RouteCollectorProxy $group) {
    $group->get('/patient/{uid}', [PatientProfileApiAction::class, '__invoke']);
    $group->get('/health', [HealthCheckAction::class, '__invoke']);
})
->add(new ApiRateLimitMiddleware(100, 60))
->add(new ApiAuthenticationMiddleware());
```

---

## 📋 Priority Summary

### 🔴 HIGH PRIORITY (Do First)
1. ✅ **PHPStan Level 6** - COMPLETED ✨
2. ✅ **Query Optimization** - COMPLETED ✨
3. ✅ **Rate Limiting on Login** - COMPLETED ✨
4. 🔲 **Apply Database Indexes** - Run `database_indexes.sql`
5. 🔲 **Upgrade Password Hashing** - Switch to Argon2id
6. 🔲 **Environment Configuration** - Add `.env` file support
7. 🔲 **HTTPS Enforcement** - Production deployment

### 🟡 MEDIUM PRIORITY (Do Soon)
1. 🔲 **Rate Limit Registration** - Prevent spam accounts
2. 🔲 **Password Reset Feature** - User convenience
3. 🔲 **Unit Tests** - Improve test coverage
4. 🔲 **Error Monitoring** - Set up Sentry/Rollbar

### 🟢 LOW PRIORITY (Nice to Have)
1. 🔲 **Implement Caching** - Use existing CacheService
2. 🔲 **API Rate Limit Headers** - Better client feedback
3. 🔲 **Health Check Endpoint** - Monitoring integration
4. 🔲 **Database Migrations Tool** - Long-term maintainability

---

## 🎯 Quick Wins (Under 30 Minutes)

1. **Apply Database Indexes** (5 minutes)
   ```bash
   mysql -u root -p know_my_patient < database_indexes.sql
   ```

2. **Upgrade Password Hashing** (15 minutes)
   - Replace `PASSWORD_DEFAULT` with `PASSWORD_ARGON2ID` in 3 locations
   - Add rehashing logic to login

3. **Add .env Configuration** (20 minutes)
   - Create `.env` file
   - Update `app/settings.php`
   - Add `.env.example`

---

## 📊 Expected Impact

| Action | Security | Performance | Maintainability |
|--------|----------|-------------|-----------------|
| Database Indexes | - | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ |
| Argon2id Hashing | ⭐⭐⭐⭐⭐ | - | ⭐⭐⭐⭐ |
| Environment Config | ⭐⭐⭐ | - | ⭐⭐⭐⭐⭐ |
| HTTPS Enforcement | ⭐⭐⭐⭐⭐ | - | ⭐⭐⭐ |
| Extended Rate Limiting | ⭐⭐⭐⭐ | - | ⭐⭐⭐ |
| Unit Tests | ⭐⭐ | - | ⭐⭐⭐⭐⭐ |
| Caching Layer | - | ⭐⭐⭐⭐ | ⭐⭐⭐ |
| Error Monitoring | ⭐⭐⭐ | - | ⭐⭐⭐⭐ |

---

## ✅ Completed Improvements (Previous Work)

- ✅ PHPStan Level 6 compliance (62 → 0 errors)
- ✅ Array type docblocks across all classes
- ✅ Property type hints on all middleware and controllers
- ✅ Query optimization (SELECT * removed from 14 queries)
- ✅ Rate limiting on /login (5 attempts/15 min)
- ✅ Structured logging with Monolog
- ✅ CSRF protection on all forms
- ✅ Audit logging with IP tracking
- ✅ Documentation (9 MD files created)

---

## 🤝 Need Help?

For any of these recommendations, refer to:
- `README.md` - Project overview
- `DEPLOYMENT.md` - Production deployment guide
- `CONTRIBUTING.md` - Development guidelines
- `PHPSTAN_ZERO_ERRORS_SUMMARY.md` - Recent improvements
- `ARRAY_TYPE_REFERENCE.md` - Type hint patterns

---

**Next Review Date**: 12 November 2025  
**Recommended Priority**: HIGH items within 1 week, MEDIUM within 1 month
