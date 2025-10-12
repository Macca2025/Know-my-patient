# PHPStan Array Type Docblocks - Quick Reference

## 📚 Common Patterns Used in This Project

### 1. Simple String-Mixed Arrays
**Use case:** General purpose arrays with string keys and mixed values
```php
/** @var array<string, mixed> */
private array $data;

/** @param array<string, mixed> $params */
public function process(array $params): void

/** @return array<string, mixed> */
public function getData(): array
```

### 2. String-Only Arrays
**Use case:** Arrays with string keys and string values (or null)
```php
/** @return array<string, string|null> */
public static function getIpDebugInfo(): array
{
    return [
        'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? null,
        'HTTP_X_FORWARDED_FOR' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
    ];
}
```

### 3. Nullable Array Returns
**Use case:** Methods that may return null instead of an array
```php
/** @return array<string, mixed>|null */
public function findByUid(string $uid): ?array
{
    $result = $this->db->fetch($uid);
    return $result ?: null;
}
```

### 4. Array or Object Returns
**Use case:** Parsed body or data that could be array or object
```php
/** @return array<string, mixed>|object */
protected function getFormData()
{
    return $this->request->getParsedBody();
}
```

### 5. Arrays with Specific Value Types
**Use case:** Arrays with known value types
```php
/** @return array<string, int|string|null> */
public function jsonSerialize(): array
{
    return [
        'id' => $this->id,           // int
        'username' => $this->username, // string
        'firstName' => $this->firstName, // string|null
    ];
}
```

### 6. Mixed Types (Flexible)
**Use case:** When you truly need any type
```php
/** @param mixed $value */
public function set(string $key, $value): void

/** @param mixed $default */
public function get(string $key, $default = null)
```

### 7. Nullable Array Parameters
**Use case:** Optional array parameters
```php
/**
 * @param array<string, mixed>|null $currentUser
 */
private function handleSubmission(Request $request, ?array $currentUser = null): Response
```

### 8. Union Types in Returns
**Use case:** Multiple possible return types
```php
/**
 * @return array<string, mixed>|object|null
 */
public function getData()
```

---

## 🎯 Where to Use What

### Controllers
```php
// User data from database
/** @return array<string, mixed>|null */

// Request data
/** @param array<string, mixed> $data */

// Uploaded files
/** @param array<string, mixed> $uploadedFiles */

// Audit log details
/** @param array<string, mixed> $details */
```

### Services
```php
// Session data (can be anything)
/** @return array<string, mixed> */
public function all(): array

// Generic getters/setters
/** @param mixed $value */
public function set(string $key, $value): void
```

### Repositories
```php
// Database results
/** @return array<string, mixed>|null */
public function find(int $id): ?array

// Audit data
/** @param array<string, mixed> $data */
public function log(array $data): void
```

### Base Classes
```php
// Route args
/** @param array<string, mixed> $args */
public function __invoke(Request $request, Response $response, array $args): Response

// JSON serialization
/** @return array<string, mixed> */
public function jsonSerialize(): array
```

---

## ⚡ Quick Decision Tree

```
Is it an array?
└─ YES
   ├─ Can it be null?
   │  └─ YES → Use array<...>|null
   │  └─ NO  → Use array<...>
   │
   ├─ What are the keys?
   │  └─ Strings → array<string, ...>
   │  └─ Integers → array<int, ...>
   │  └─ Mixed → array<...>
   │
   └─ What are the values?
      ├─ All mixed → array<string, mixed>
      ├─ Specific types → array<string, int|string|null>
      ├─ Only strings → array<string, string>
      └─ Could be object → array<string, mixed>|object
```

---

## 🚫 Common Mistakes

### ❌ Don't Do This
```php
/** @return array */
public function getData(): array

/** @param array $data */
public function process(array $data): void
```

### ✅ Do This Instead
```php
/** @return array<string, mixed> */
public function getData(): array

/** @param array<string, mixed> $data */
public function process(array $data): void
```

---

## 💡 Pro Tips

1. **Default to `array<string, mixed>`** for most use cases
2. **Use nullable returns** (`|null`) when appropriate
3. **Be specific** when you know exact value types
4. **Use union types** (`|object`) when data could be multiple types
5. **Document** why you chose specific types in complex cases

---

## 🔍 Examples from Our Codebase

### Action.php
```php
/** @var array<string, mixed> */
protected array $args;

/** @return array<string, mixed>|object */
protected function getFormData()
```

### SessionService.php
```php
/** @return array<string, mixed> */
public function all(): array
```

### AddPatientController.php
```php
/**
 * @param array<string, mixed> $data
 * @return array<string, mixed>
 */
private function buildUpdateFields(array $data): array
```

### PatientProfileRepository.php
```php
/** @return array<string, mixed>|null */
public function findByUid(string $uid): ?array
```

---

## 📖 Further Reading

- PHPStan Docs: https://phpstan.org/writing-php-code/phpdoc-types
- Array Shapes: https://phpstan.org/writing-php-code/phpdoc-types#array-shapes
- Generics: https://phpstan.org/blog/generics-in-php-using-phpdocs

---

**Last Updated**: 12 October 2025  
**PHPStan Level**: 6  
**Status**: All array types documented ✅
