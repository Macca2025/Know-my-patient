# Development Tools Quick Reference

This guide shows how to use development tools now that config files are organized in the `config/` directory.

## 🚀 Quick Commands (Recommended)

Use these convenient composer scripts for all development tasks:

```bash
# Run unit tests
composer test

# Run static analysis (PHPStan Level 6)
composer phpstan

# Check code style (PSR-12)
composer phpcs

# Auto-fix code style issues
composer phpcbf

# Start development server
composer start
```

---

## 🔧 Direct Tool Usage (Advanced)

If you need to run tools directly with custom options:

### PHPUnit
```bash
# Run all tests
./vendor/bin/phpunit -c config/phpunit.xml

# Run specific test file
./vendor/bin/phpunit -c config/phpunit.xml tests/Application/Actions/User/ViewUserActionTest.php

# Run with coverage
./vendor/bin/phpunit -c config/phpunit.xml --coverage-html coverage/
```

### PHPStan
```bash
# Analyze with default config (includes 512MB memory limit)
./vendor/bin/phpstan analyse -c config/phpstan.neon.dist --memory-limit=512M

# Analyze specific directory
./vendor/bin/phpstan analyse -c config/phpstan.neon.dist --memory-limit=512M src/Domain/

# Generate baseline
./vendor/bin/phpstan analyse -c config/phpstan.neon.dist --memory-limit=512M --generate-baseline

# Increase memory limit if needed
./vendor/bin/phpstan analyse -c config/phpstan.neon.dist --memory-limit=1G
```

### PHP_CodeSniffer
```bash
# Check code style
./vendor/bin/phpcs --standard=config/phpcs.xml

# Check specific file
./vendor/bin/phpcs --standard=config/phpcs.xml src/Application/Actions/User/ListUsersAction.php

# Auto-fix issues
./vendor/bin/phpcbf --standard=config/phpcs.xml

# Show detailed report
./vendor/bin/phpcs --standard=config/phpcs.xml -v
```

### Docker (Development)
```bash
# Start containers
docker-compose -f config/docker-compose.yml up -d

# Stop containers
docker-compose -f config/docker-compose.yml down

# View logs
docker-compose -f config/docker-compose.yml logs -f

# Rebuild containers
docker-compose -f config/docker-compose.yml up -d --build
```

---

## 📂 Configuration Files Location

All development tool configuration files are in the `config/` directory:

```
config/
├── phpunit.xml           - PHPUnit test configuration
├── phpstan.neon.dist     - PHPStan static analysis rules
├── phpcs.xml             - PHP_CodeSniffer code style rules
├── docker-compose.yml    - Docker development environment
└── README.md             - Detailed configuration documentation
```

---

## ✅ Why This Structure?

### Benefits:
1. ✅ **Clean Root Directory** - Only production files in root
2. ✅ **Clear Separation** - Dev tools vs production code
3. ✅ **Production Ready** - Deploy without dev tool configs
4. ✅ **Professional** - Industry-standard structure
5. ✅ **Maintainable** - Easy to find and update configs

### Production Deployment:
These config files are **not needed in production**:
- Testing happens pre-deployment
- Static analysis runs in CI/CD pipeline
- Code style checks are pre-commit
- Docker may or may not be used in production

**Exclude from production:**
```bash
# In your deployment script or .gitignore
config/
tests/
.phpunit.result.cache
```

---

## 🔍 Troubleshooting

### Issue: "Cannot find config file"
**Solution:** Always run commands from project root directory:
```bash
cd /path/to/know_my_patient
composer test
```

### Issue: "Cannot find bootstrap.php"
**Solution:** Config files use relative paths (`../tests/`). Run from project root.

### Issue: "Tests not found"
**Solution:** Verify paths in `config/phpunit.xml` point to `../tests/`

---

## 📚 Related Documentation

- [Config Directory README](config/README.md) - Detailed config documentation
- [Testing Guide](docs/testing/UNIT_TESTS.md) - Unit testing best practices
- [PHPStan Implementation](docs/implementation/PHPSTAN_LEVEL_6.md) - Static analysis setup
- [Deployment Guide](docs/DEPLOYMENT.md) - Production deployment steps

---

*Last Updated: 13 October 2025*
