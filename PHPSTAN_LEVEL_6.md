# PHPStan Level 6 - Analysis Report

## Summary

PHPStan has been upgraded from level 4 to level 6, enforcing stricter type checking rules.

**Configuration**: `phpstan.neon.dist`
```neon
parameters:
  level: 6
  paths:
    - src
  excludePaths:
    - src/Infrastructure/Persistence
```

## Analysis Results

**Total Issues Found**: 62 errors
**Status**: ⚠️ Level 6 active, issues need gradual resolution

## Issue Categories

### 1. Array Type Specifications (Most Common - ~45 issues)
Arrays without value type specifications (e.g., `array` should be `array<string, mixed>`)

**Examples**:
```php
// Issue
public function __invoke(array $args): Response

// Fix
public function __invoke(array<string, mixed> $args): Response

// Or be more specific
public function respondWithData(array<string, int|string> $data): Response
```

### 2. Missing Property Types (~12 issues)
Properties without type declarations

**Examples**:
```php
// Issue
private $logger;
private $sessionService;

// Fix
private LoggerInterface $logger;
private SessionService $sessionService;
```

### 3. Unused Properties/Parameters (~5 issues)
Properties that are written but never read

**Examples**:
- `AddPatientController::$logger` - written but never read
- `DashboardController::__construct()` has unused parameter `$logger`

### 4. Logic Issues (Minor)
- Always true/false conditions
- Dead code detection

## Detailed Breakdown by File

### High Priority (Controllers)

#### AddPatientController.php (8 issues)
```
Line 15:  Property $logger is never read, only written
Line 129: Missing array value type in $currentUser parameter
Line 148: Negated boolean expression is always false
Line 257: Missing array types in buildUpdateFields()
Line 289: Missing array types in handleFileUploads()
Line 511: Missing array types in logAction() $details parameter
```

#### DashboardController.php (9 issues)
```
Line 18:  Constructor has unused $logger parameter
Line 317: If condition is always true
Line 460: Missing array value type in $currentUser parameter
Line 479: Negated boolean expression is always false
Line 588: Missing array types in buildUpdateFields()
Line 620: Missing array types in handleFileUploads()
Line 842: Missing array types in logAction() $details parameter
```

#### AdminController.php (1 issue)
```
Line 236: Right side of && is always true
```

### Medium Priority (Services & Middleware)

#### SessionService.php (4 issues)
```
Line 6:  get() missing return type
Line 6:  get() parameter $default has no type
Line 11: set() parameter $value has no type
Line 26: all() return type needs array value specification
```

#### IpAddressService.php (1 issue)
```
Line 118: getIpDebugInfo() return type needs array specification
```

#### All Middleware Files (~10 issues)
Missing type specifications on `$sessionService` and `$twig` properties

### Low Priority (Base Classes & Interfaces)

#### Action.php (4 issues)
- Array type specifications for base action methods

#### ActionPayload.php (4 issues)
- Array type specifications for payload data

#### Domain Models (3 issues)
- User.php: jsonSerialize() array types
- Repositories: array return types

## Recommended Fix Strategy

### Phase 1: Quick Wins (30 mins)
1. ✅ Add property types to middleware classes
2. ✅ Fix SessionService method signatures
3. ✅ Remove unused logger parameters
4. ✅ Fix always true/false conditions

### Phase 2: Array Type Hints (1-2 hours)
1. Add `@param array<string, mixed>` docblocks where full typing is complex
2. Use specific array types where possible (e.g., `array<int, string>`)
3. Start with controllers, then services

### Phase 3: Architectural (2-3 hours)
1. Consider creating DTOs for complex array parameters
2. Add proper return types to all methods
3. Review and fix logic issues (dead code, always true conditions)

## Fixing Examples

### Example 1: Add Property Types
```php
// Before
class AuthMiddleware implements MiddlewareInterface
{
    private $sessionService;
    
    public function __construct($sessionService)
    {
        $this->sessionService = $sessionService;
    }
}

// After
use App\Application\Services\SessionService;

class AuthMiddleware implements MiddlewareInterface
{
    private SessionService $sessionService;
    
    public function __construct(SessionService $sessionService)
    {
        $this->sessionService = $sessionService;
    }
}
```

### Example 2: Array Type Specifications
```php
// Before
private function logAction($userId, $action, $details = [])

// After
/**
 * @param array<string, mixed> $details
 */
private function logAction(string|int $userId, string $action, array $details = []): void

// Or with PHP 8+ syntax (if supported)
private function logAction(
    string|int $userId, 
    string $action, 
    array<string, mixed> $details = []
): void
```

### Example 3: Fix Unused Properties
```php
// Before
private LoggerInterface $logger; // Assigned but never used

public function __construct(LoggerInterface $logger) {
    $this->logger = $logger;
}

// Option 1: Use it
$this->logger->info('Action performed', ['user' => $userId]);

// Option 2: Remove it (if not needed)
public function __construct() {
    // Removed logger parameter
}
```

## Ignoring Specific Issues (Temporary)

You can temporarily ignore specific issues in `phpstan.neon.dist`:

```neon
parameters:
  level: 6
  paths:
    - src
  excludePaths:
    - src/Infrastructure/Persistence
  ignoreErrors:
    # Temporarily ignore array type issues in base classes
    - '#has no value type specified in iterable type array#'
    # Ignore specific files
    - 
      message: '#Property .* is never read#'
      path: src/Application/Actions/*Controller.php
```

## Benefits of Level 6

✅ **Type Safety**: Catches type-related bugs before runtime
✅ **Better IDE Support**: Improved autocomplete and refactoring
✅ **Documentation**: Code is self-documenting with proper types
✅ **Maintainability**: Easier to understand and modify code
✅ **Fewer Bugs**: Many common errors caught during development

## Current Status

✅ **PHPStan Level 6 Active**
⚠️ **62 issues to resolve** (non-blocking, code still works)
🎯 **Goal**: Reduce to 0 errors for maximum type safety

## Next Steps

### Immediate (Optional - Not Blocking)
1. Review unused logger instances
2. Fix always true/false conditions
3. Add property types to middleware

### Short Term (Recommended)
1. Add array type hints with docblocks
2. Fix method return types
3. Test thoroughly after changes

### Long Term (Best Practice)
1. Maintain level 6 compliance for new code
2. Gradually fix existing issues
3. Consider DTOs for complex data structures
4. Add PHPStan to CI/CD pipeline

## Resources

- [PHPStan Documentation](https://phpstan.org/user-guide/getting-started)
- [Array Type Hints Guide](https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type)
- [Generics in PHP](https://phpstan.org/blog/generics-in-php-using-phpdocs)

---

**Date**: 12 October 2025
**PHPStan Version**: Latest
**PHP Version**: 8.4.11
**Status**: ✅ Level 6 active, issues catalogued for gradual resolution
