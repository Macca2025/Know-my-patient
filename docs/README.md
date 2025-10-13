# Know My Patient - Documentation

This directory contains all comprehensive documentation for the Know My Patient application, organized by category for easy navigation.

## 📁 Directory Structure

### 📄 Root Documentation
- **[DEPLOYMENT.md](DEPLOYMENT.md)** - Production deployment guide
- **[RECOMMENDATIONS_STATUS.md](RECOMMENDATIONS_STATUS.md)** - Master status document tracking all 22 recommendations (100% complete)
- **[WEBSITE_BEST_PRACTICES.md](WEBSITE_BEST_PRACTICES.md)** - Web development best practices and guidelines

### 🔧 Setup Guides (`/setup`)
Configuration and installation guides for various components:
- **[CRON_SETUP_GUIDE.md](setup/CRON_SETUP_GUIDE.md)** - Automated log rotation and database backups
- **[OPCACHE_SETUP_GUIDE.md](setup/OPCACHE_SETUP_GUIDE.md)** - OPcache configuration and optimization
- **[SENTRY_SETUP_GUIDE.md](setup/SENTRY_SETUP_GUIDE.md)** - Error monitoring with Sentry
- **[UPTIMEROBOT_SETUP.md](setup/UPTIMEROBOT_SETUP.md)** - Uptime monitoring setup

### 📋 Compliance (`/compliance`)
Healthcare and regulatory compliance documentation:
- **[NHS_DCB0129_COMPLIANCE.md](compliance/NHS_DCB0129_COMPLIANCE.md)** - NHS DCB0129 clinical safety standards
- **[HAZARD_LOG.md](compliance/HAZARD_LOG.md)** - Clinical safety hazard log

### 🔨 Implementation (`/implementation`)
Technical implementation details and completed features:
- **[CACHESERVICE_INTEGRATION.md](implementation/CACHESERVICE_INTEGRATION.md)** - Caching implementation (25-98% query reduction)
- **[OPCACHE_COMPLETION_SUMMARY.md](implementation/OPCACHE_COMPLETION_SUMMARY.md)** - OPcache installation verification
- **[PASSWORD_HASHING_UPGRADE.md](implementation/PASSWORD_HASHING_UPGRADE.md)** - Argon2ID password hashing upgrade
- **[PHPSTAN_LEVEL_6.md](implementation/PHPSTAN_LEVEL_6.md)** - Static analysis implementation
- **[QUERY_OPTIMIZATION.md](implementation/QUERY_OPTIMIZATION.md)** - Database query optimization (45 indexes)
- **[RATE_LIMITING.md](implementation/RATE_LIMITING.md)** - Rate limiting implementation

### 🧪 Testing (`/testing`)
Testing documentation and results:
- **[TEST_RESULTS.md](testing/TEST_RESULTS.md)** - Unit test results (86 tests, 100% passing)
- **[UNIT_TESTS.md](testing/UNIT_TESTS.md)** - Testing documentation and guidelines

### 📚 Reference (`/reference`)
Technical reference documentation:
- **[ARRAY_TYPE_REFERENCE.md](reference/ARRAY_TYPE_REFERENCE.md)** - PHPStan array type annotations reference

---

## 🚀 Quick Start

For new developers or deployments, read these in order:

1. **[RECOMMENDATIONS_STATUS.md](RECOMMENDATIONS_STATUS.md)** - Overview of all completed features
2. **[DEPLOYMENT.md](DEPLOYMENT.md)** - Deployment procedures
3. **[setup/](setup/)** - Configure all required services
4. **[compliance/](compliance/)** - Understand regulatory requirements

---

## 📊 Project Status

**Production Ready:** ✅ All 22 recommendations complete (100%)

- ✅ Security hardening (Argon2ID, HTTPS, rate limiting)
- ✅ Performance optimization (OPcache, caching, database indexes)
- ✅ Quality assurance (86 unit tests, PHPStan Level 6)
- ✅ Automation (log rotation, database backups)
- ✅ Monitoring (Sentry, health checks)
- ✅ Compliance (NHS DCB0129, clinical safety)

---

## 🔗 Related Resources

- **Project Repository:** [Macca2025/Know-my-patient](https://github.com/Macca2025/Know-my-patient)
- **Main README:** [../README.md](../README.md)
- **Test Scripts:** [../tests/scripts/](../tests/scripts/)

---

*Last Updated: 13 October 2025*
