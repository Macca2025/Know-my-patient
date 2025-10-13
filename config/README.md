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
composer test
# or
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
./vendor/bin/phpcs --standard=config/phpcs.xml
```

### `docker-compose.yml`
Docker containerization configuration.

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

## 🔗 Symlinks

For compatibility with tools that expect config files in the root directory, symlinks are created:

```
/phpunit.xml -> config/phpunit.xml
/phpstan.neon.dist -> config/phpstan.neon.dist
/phpcs.xml -> config/phpcs.xml
/docker-compose.yml -> config/docker-compose.yml
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
