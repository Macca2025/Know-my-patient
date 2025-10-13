# Configuration Directory

This directory contains project configuration files for development tools, testing, and containerization.

## 🔧 Configuration Files

### `phpunit.xml`
PHPUnit testing framework configuration.

**Settings:**
- Bootstrap file: `tests/bootstrap.php`
- Test suite: `Application Tests`
- Code coverage settings
- Error handling configuration

**Usage:**
```bash
# Via composer scripts (recommended)
composer test

# Or directly
./vendor/bin/phpunit -c config/phpunit.xml
```

### `phpstan.neon.dist`
PHPStan static analysis configuration (Level 6).

**Features:**
- Level 6 strict type checking
- Custom paths and excludes
- Symfony and PHPUnit extensions
- Zero errors in production

**Usage:**
```bash
# Via composer scripts (recommended)
composer phpstan

# Or directly
./vendor/bin/phpstan analyse -c config/phpstan.neon.dist
```

### `phpcs.xml`
PHP_CodeSniffer coding standards configuration.

**Standards:**
- PSR-12 coding style
- Custom ruleset configuration
- File/directory excludes

**Usage:**
```bash
# Check code style (via composer)
composer phpcs

# Auto-fix code style (via composer)
composer phpcbf

# Or directly
./vendor/bin/phpcs --standard=config/phpcs.xml
./vendor/bin/phpcbf --standard=config/phpcs.xml
```

### `docker-compose.yml`
Docker containerization configuration (development only).

**Services:**
- PHP application container
- MySQL database
- Redis cache (if configured)
- Volume mappings

**Usage:**
```bash
docker-compose -f config/docker-compose.yml up -d
```

---

## � Composer Scripts

For convenience, use these composer commands:

```bash
composer test        # Run PHPUnit tests
composer phpstan     # Run static analysis
composer phpcs       # Check code style
composer phpcbf      # Auto-fix code style
composer start       # Start development server
```

---

## 📝 Environment Configuration

Main environment configuration is in the root directory:
- `.env` - Environment variables (not in git)
- `.env.example` - Example environment template

---

## 📚 Related Documentation

- [Testing Guide](/docs/testing/UNIT_TESTS.md)
- [PHPStan Implementation](/docs/implementation/PHPSTAN_LEVEL_6.md)
- [Deployment Guide](/docs/DEPLOYMENT.md)

---

*Last Updated: 13 October 2025*
