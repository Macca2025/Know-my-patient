# Database Directory

This directory contains SQL schema files and database-related scripts.

## 📊 SQL Files

### `database_indexes.sql`
Complete database index definitions for performance optimization.

**Contains 45 indexes across 6 tables:**
- **users** (4 indexes): email, role, active status, composite indexes
- **patient_profiles** (8 indexes): UID, user_id, NHS number, medical fields
- **audit_log** (9 indexes): user tracking, activity types, timestamps
- **card_requests** (7 indexes): status tracking, request dates
- **support_messages** (3 indexes): user messages, status, timestamps
- **onboarding_enquiries** (14 indexes): comprehensive query optimization

**Performance Impact:**
- 60-95% faster database queries
- Optimized for common query patterns
- Production-verified implementations

**Usage:**
```bash
mysql -u root -p know_my_patient < database/database_indexes.sql
```

### `database_password_resets.sql`
Password reset table schema and structure.

**Features:**
- Secure token storage (255 chars)
- 1-hour token expiry
- User association via foreign key
- Indexed for fast lookups

**Usage:**
```bash
mysql -u root -p know_my_patient < database/database_password_resets.sql
```

---

## 🗂️ Migrations

Database migrations are stored in `database_migrations/`:
- `add_respect_document_columns.sql` - ReSPECT document fields
- `migrate_patient_uids.sql` - Patient UID migration

---

## 🔍 Verification

To verify all indexes are installed:
```bash
./scripts/check_index_status.sh
```

---

## 📚 Related Documentation

- [Query Optimization Guide](/docs/implementation/QUERY_OPTIMIZATION.md)
- [Deployment Guide](/docs/DEPLOYMENT.md)
- [Database Backup Setup](/docs/setup/CRON_SETUP_GUIDE.md)

---

*Last Updated: 13 October 2025*
