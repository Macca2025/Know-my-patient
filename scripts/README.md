# Scripts Directory

This directory contains executable shell scripts for setup, maintenance, and verification tasks.

## 🔧 Setup Scripts

### `setup_cron.sh`
Automated setup for cron jobs including:
- Log rotation (daily at 1:00 AM)
- Database backups (daily at 2:00 AM)
- Weekly backup verification (Sunday at 3:00 AM)

**Usage:**
```bash
./scripts/setup_cron.sh
```

### `setup_opcache.sh`
One-command OPcache configuration installer:
- Detects PHP configuration directory
- Installs optimized OPcache settings (256MB, 20K files, JIT enabled)
- Restarts PHP service
- Verifies installation

**Usage:**
```bash
./scripts/setup_opcache.sh
```

## ✅ Verification Scripts

### `check_index_status.sh`
Database index verification tool:
- Verifies all 45 recommended indexes are present
- Color-coded output (✅ present / ❌ missing)
- Checks indexes across 6 tables:
  - users (4 indexes)
  - patient_profiles (8 indexes)
  - audit_log (9 indexes)
  - card_requests (7 indexes)
  - support_messages (3 indexes)
  - onboarding_enquiries (14 indexes)

**Usage:**
```bash
./scripts/check_index_status.sh
```

---

## 📝 Notes

- All scripts are executable (`chmod +x`)
- Scripts require appropriate permissions for system operations
- Review script contents before running on production servers
- See `/docs/setup/` for detailed setup guides

---

*Last Updated: 13 October 2025*
