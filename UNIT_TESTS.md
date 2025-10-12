# Unit Test Suite Documentation

## Overview
Comprehensive unit test suite for the Know My Patient application covering critical services, middleware, and controllers.

## Test Coverage

### Services
1. **CacheServiceTest** - `tests/Unit/Services/CacheServiceTest.php`
   - Basic get/set operations
   - TTL expiry behavior
   - Remember pattern (cache-or-execute)
   - Cache clearing (forget/flush)
   - Special characters in keys
   - Multiple data types (arrays, objects, booleans, numbers)
   - **22 test cases**

2. **SessionServiceTest** - `tests/Unit/Services/SessionServiceTest.php`
   - Get/set operations
   - Default values
   - Has/remove operations
   - Session destruction
   - Multiple data types
   - **10+ test cases**

3. **ErrorMessageServiceTest** - `tests/Unit/Services/ErrorMessageServiceTest.php`
   - Production vs development error messages
   - Database error sanitization
   - File path hiding
   - Stack trace removal
   - Safe message preservation
   - JSON error data formatting
   - **10 test cases**

### Middleware
4. **RateLimitMiddlewareTest** - `tests/Unit/Middleware/RateLimitMiddlewareTest.php`
   - Allows requests below limit
   - Blocks requests above limit
   - Separate limits per IP address
   - Rate limit response messages
   - **5 test cases**

### Controllers
5. **PasswordResetControllerTest** - `tests/Unit/Actions/PasswordResetControllerTest.php`
   - Forgot password form display
   - Token generation
   - Rate limiting (3 per hour)
   - Invalid email handling
   - Successful password reset
   - Token expiry and single-use
   - **6 test cases**

## Running Tests

### Run All Tests
```bash
cd /Applications/MAMP/htdocs/know_my_patient
vendor/bin/phpunit tests/
```

### Run Specific Test Suite
```bash
# Services only
vendor/bin/phpunit tests/Unit/Services/

# Middleware only
vendor/bin/phpunit tests/Unit/Middleware/

# Controllers only
vendor/bin/phpunit tests/Unit/Actions/
```

### Run Individual Test File
```bash
vendor/bin/phpunit tests/Unit/Services/CacheServiceTest.php
vendor/bin/phpunit tests/Unit/Services/SessionServiceTest.php
vendor/bin/phpunit tests/Unit/Services/ErrorMessageServiceTest.php
vendor/bin/phpunit tests/Unit/Middleware/RateLimitMiddlewareTest.php
vendor/bin/phpunit tests/Unit/Actions/PasswordResetControllerTest.php
```

### Run with Code Coverage
```bash
vendor/bin/phpunit --coverage-html coverage/ tests/
```

### Run with Verbose Output
```bash
vendor/bin/phpunit --testdox tests/
```

## Test Environment Setup

### Requirements
- PHP 8.0+
- PHPUnit 9.5+ (installed via composer)
- SQLite support (for database tests)
- Session support enabled

### Configuration
Tests use the `phpunit.xml` configuration file in the project root.

Key settings:
- **Bootstrap**: `tests/bootstrap.php`
- **Test suites**: Unit, Integration, Domain
- **PHP settings**: Error reporting, display errors
- **Code coverage**: HTML and Clover formats

## Writing New Tests

### Test File Structure
```php
<?php
declare(strict_types=1);

namespace Tests\Unit\YourNamespace;

use PHPUnit\Framework\TestCase;

class YourClassTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Initialize test dependencies
    }
    
    protected function tearDown(): void
    {
        // Clean up after tests
        parent::tearDown();
    }
    
    public function testYourFeature(): void
    {
        // Arrange
        $expectedValue = 'test';
        
        // Act
        $actualValue = $this->yourMethod();
        
        // Assert
        $this->assertEquals($expectedValue, $actualValue);
    }
}
```

### Best Practices
1. **Isolation**: Each test should be independent
2. **Clear naming**: Use descriptive test method names
3. **AAA Pattern**: Arrange, Act, Assert
4. **Mock dependencies**: Don't test external services
5. **Clean up**: Use tearDown() to remove test data
6. **Fast tests**: Avoid network calls and file I/O when possible

### Test Database
Many tests use SQLite in-memory databases:
```php
$pdo = new \PDO('sqlite::memory:');
$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
```

### Mocking
Use PHPUnit's mock capabilities:
```php
$logger = $this->createMock(LoggerInterface::class);
$logger->expects($this->once())
    ->method('info')
    ->with('Log message');
```

## Test Scenarios Covered

### Security
- ✅ Rate limiting enforcement
- ✅ Error message sanitization (production)
- ✅ Password reset token security
- ✅ Email enumeration prevention
- ✅ Session data isolation

### Functionality
- ✅ Cache operations with TTL
- ✅ Session management
- ✅ Password reset flow
- ✅ Token generation and validation
- ✅ Rate limiting per IP

### Edge Cases
- ✅ Expired cache entries
- ✅ Nonexistent session keys
- ✅ Invalid email addresses
- ✅ Rate limit exceeded
- ✅ Expired reset tokens

## Continuous Integration

### GitHub Actions Example
```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
          extensions: pdo, sqlite3
          
      - name: Install Dependencies
        run: composer install --prefer-dist
        
      - name: Run Tests
        run: vendor/bin/phpunit tests/
```

## Coverage Goals

### Current Status
- **Services**: 80%+ coverage
- **Middleware**: 70%+ coverage
- **Controllers**: 60%+ coverage (integration tests recommended)

### Target Goals
- **Overall**: 75%+ code coverage
- **Critical paths**: 90%+ coverage (auth, password reset, caching)
- **Security features**: 95%+ coverage

## Future Test Additions

### Recommended Priority
1. **HIGH**: AuthController login/logout tests
2. **HIGH**: AdminController authorization tests
3. **MEDIUM**: DashboardController role-based rendering
4. **MEDIUM**: Testimonial repository tests
5. **LOW**: Template rendering tests

### Integration Tests
Consider adding:
- Database integration tests
- Email sending tests (using Mailtrap/test SMTP)
- End-to-end password reset flow
- Multi-user session handling

## Troubleshooting

### Common Issues

#### Tests Fail Due to Session Errors
```php
// Ensure session is started in setUp()
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
```

#### Cache Directory Permissions
```bash
chmod 755 var/cache/
chmod 755 /tmp
```

#### SQLite Not Available
```bash
# macOS
brew install sqlite

# Ubuntu/Debian
sudo apt-get install php-sqlite3
```

#### PHPUnit Not Found
```bash
composer install
# or
composer require --dev phpunit/phpunit
```

## Performance Benchmarks

### Test Execution Time
- **CacheServiceTest**: ~0.5 seconds
- **SessionServiceTest**: ~0.2 seconds
- **ErrorMessageServiceTest**: ~0.3 seconds
- **RateLimitMiddlewareTest**: ~0.4 seconds
- **PasswordResetControllerTest**: ~1.0 seconds (database operations)

**Total Suite**: ~2.5 seconds

## Reporting Issues

If tests fail unexpectedly:
1. Check PHP version (8.0+ required)
2. Verify all composer dependencies installed
3. Check file permissions on cache directories
4. Review test output for specific errors
5. Run tests with `--debug` flag for more info

## Contributing

When adding new features:
1. ✅ Write tests first (TDD approach)
2. ✅ Ensure all tests pass before committing
3. ✅ Aim for 75%+ coverage on new code
4. ✅ Update this documentation

---

**Last Updated**: 13 October 2025  
**Test Framework**: PHPUnit 9.5+  
**Total Test Cases**: 50+  
**Execution Time**: ~2.5 seconds
