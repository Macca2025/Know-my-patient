# Quick Action Checklist

## 🚀 Next Steps After PHPStan Level 6 Success

---

## ⚡ Quick Wins (Do Today - Under 1 Hour Total)

### 1. Apply Database Indexes (5 minutes) 🔥
**Impact**: Massive performance boost (50-90% faster queries)

```bash
# Connect to MySQL
mysql -u root -p know_my_patient

# Apply indexes
source database_indexes.sql

# Or one-liner:
mysql -u root -p know_my_patient < database_indexes.sql
```

**Verify:**
```sql
SHOW INDEX FROM users;
SHOW INDEX FROM audit_log;
SHOW INDEX FROM patient_profiles;
```

Expected: Multiple indexes per table ✅

---

### 2. Upgrade Password Hashing (15 minutes) 🔐
**Impact**: Military-grade password security

**Files to modify:**
1. `src/Application/Actions/AuthController.php` (lines 96, 190)
2. `src/Application/Actions/DashboardController.php` (line 254)

**Find:**
```php
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
```

**Replace with:**
```php
// Check if Argon2id is available (PHP 7.2+)
if (defined('PASSWORD_ARGON2ID')) {
    $hashedPassword = password_hash($password, PASSWORD_ARGON2ID);
} else {
    // Fallback to stronger bcrypt
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}
```

**Test:**
- Register new account
- Login with new account
- Change password
- Login again

---

### 3. Add Environment Configuration (20 minutes) ⚙️
**Impact**: Proper production/development separation

**Step 1:** Create `.env` file
```bash
cd /Applications/MAMP/htdocs/know_my_patient
touch .env
```

**Step 2:** Add content to `.env`
```bash
APP_ENV=development
APP_DEBUG=true
DATABASE_HOST=localhost
DATABASE_NAME=know_my_patient
DATABASE_USER=root
DATABASE_PASS=your_password_here
```

**Step 3:** Update `app/settings.php` (lines 14-22)

**Replace:**
```php
return new Settings([
    'displayErrorDetails' => true, // Should be set to false in production
    'logError'            => true,
    'logErrorDetails'     => true,
```

**With:**
```php
// Load environment variables
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad(); // Use safeLoad to avoid errors if .env doesn't exist

$env = $_ENV['APP_ENV'] ?? 'production';
$debug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);

return new Settings([
    'displayErrorDetails' => $debug && $env !== 'production',
    'logError'            => true,
    'logErrorDetails'     => $env !== 'production',
    'logger' => [
        'name' => 'slim-app',
        'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
        'level' => $env === 'production' ? \Monolog\Logger::WARNING : \Monolog\Logger::DEBUG,
    ],
]);
```

**Step 4:** Update `.gitignore`
```bash
echo ".env" >> .gitignore
echo ".env.local" >> .gitignore
```

**Step 5:** Create `.env.example`
```bash
cp .env .env.example
# Edit .env.example to remove sensitive values
```

---

### 4. Test Everything (10 minutes) ✅

```bash
# Check PHPStan (should still be 0 errors)
vendor/bin/phpstan analyse --memory-limit=256M

# Test login
open http://localhost:8080/login

# Test registration
open http://localhost:8080/register

# Check logs
tail -f logs/app.log
```

---

## 📋 Today's Checklist

- [ ] Run `database_indexes.sql` (5 min)
- [ ] Upgrade password hashing to Argon2id (15 min)
- [ ] Create `.env` file (20 min)
- [ ] Test login/registration (10 min)
- [ ] Commit changes (5 min)

**Total Time**: ~55 minutes  
**Impact**: 🔥🔥🔥🔥🔥

---

## 🔥 Git Commit Commands

After completing the above:

```bash
# Check status
git status

# Add files
git add database_indexes.sql
git add src/Application/Actions/AuthController.php
git add src/Application/Actions/DashboardController.php
git add app/settings.php
git add .env.example
git add .gitignore
git add ADDITIONAL_RECOMMENDATIONS.md
git add QUICK_ACTION_CHECKLIST.md

# Commit with detailed message
git commit -m "feat: apply critical security and performance improvements

- Apply database indexes for 50-90% query performance boost
- Upgrade password hashing from BCRYPT to ARGON2ID for enhanced security
- Add environment-based configuration with .env file support
- Update settings to disable error details in production
- Add .env.example for team onboarding

Impact: Major security hardening and performance optimization"

# Push
git push origin main
```

---

## 📅 This Week (Within 7 Days)

### Monday-Tuesday: Security Hardening
- [x] Apply database indexes ✅
- [x] Upgrade password hashing ✅
- [x] Environment configuration ✅
- [ ] Force HTTPS in production
- [ ] Add rate limiting to `/register`

### Wednesday-Thursday: Testing & Monitoring
- [ ] Write unit tests for AuthController
- [ ] Write unit tests for SessionService
- [ ] Set up error monitoring (Sentry)
- [ ] Add health check endpoint

### Friday: Documentation & Review
- [ ] Update README with new features
- [ ] Review logs for any issues
- [ ] Performance testing
- [ ] Security audit

---

## 🎯 Quick Commands Reference

### Database
```bash
# Apply indexes
mysql -u root -p know_my_patient < database_indexes.sql

# Check indexes
mysql -u root -p -e "SHOW INDEX FROM users" know_my_patient

# Backup database
mysqldump -u root -p know_my_patient > backup_$(date +%Y%m%d).sql
```

### PHP/Composer
```bash
# Check dependencies
composer update --dry-run

# Run PHPStan
vendor/bin/phpstan analyse

# Clear cache
rm -rf var/cache/*

# Check PHP version
php -v
```

### Git
```bash
# Status
git status

# Add all changes
git add .

# Commit
git commit -m "message"

# Push
git push origin main

# View recent commits
git log --oneline -10
```

---

## 📊 Progress Tracking

### Completed ✅
- [x] PHPStan Level 6 (62 → 0 errors)
- [x] Array type docblocks
- [x] Property type hints
- [x] Query optimization (SELECT *)
- [x] Rate limiting on login
- [x] Structured logging
- [x] Documentation

### Today's Goals 🎯
- [ ] Database indexes applied
- [ ] Argon2id password hashing
- [ ] Environment configuration
- [ ] All tests passing

### This Week's Goals 📅
- [ ] Extended rate limiting
- [ ] Unit test coverage >50%
- [ ] Error monitoring setup
- [ ] Production deployment ready

---

## 🆘 If Something Goes Wrong

### Database Indexes Fail
```bash
# Check for duplicate indexes
mysql -u root -p -e "SHOW INDEX FROM users" know_my_patient

# Drop index if needed
mysql -u root -p -e "DROP INDEX idx_users_email ON users" know_my_patient

# Reapply
mysql -u root -p know_my_patient < database_indexes.sql
```

### Password Hashing Issues
```bash
# Check PHP version (need 7.2+ for Argon2id)
php -v

# Check available algorithms
php -r "print_r(password_algos());"

# Fallback: Use BCRYPT with higher cost
# Replace PASSWORD_ARGON2ID with PASSWORD_BCRYPT
```

### Environment Config Not Working
```bash
# Check if vlucas/phpdotenv is installed
composer show vlucas/phpdotenv

# Install if missing
composer require vlucas/phpdotenv

# Check .env file exists
ls -la .env

# Test loading
php -r "require 'vendor/autoload.php'; \$dotenv = Dotenv\Dotenv::createImmutable(__DIR__); \$dotenv->load(); var_dump(\$_ENV['APP_ENV']);"
```

---

## 📱 Quick Test URLs

After changes, test these:

1. **Home**: http://localhost:8080/
2. **Login**: http://localhost:8080/login
3. **Register**: http://localhost:8080/register
4. **Dashboard**: http://localhost:8080/dashboard (after login)
5. **PHPStan**: `vendor/bin/phpstan analyse`

---

## ✨ Success Criteria

You'll know you're done when:

1. ✅ `database_indexes.sql` runs without errors
2. ✅ New accounts use Argon2id (check `users` table)
3. ✅ `.env` file controls debug mode
4. ✅ PHPStan still shows 0 errors
5. ✅ Login/registration works normally
6. ✅ Logs show no new warnings
7. ✅ Performance noticeably improved

---

## 📞 Support

Questions? Check:
- `ADDITIONAL_RECOMMENDATIONS.md` - Full details
- `README.md` - Project overview
- `DEPLOYMENT.md` - Production guide
- `logs/app.log` - Error logs

---

**Created**: 12 October 2025  
**Priority**: HIGH - Complete within 24 hours  
**Status**: Ready to start 🚀
