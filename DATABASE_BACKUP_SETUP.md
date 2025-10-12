# Database Backup System - Setup Guide

**Created:** 12 October 2025  
**NHS DCB0129 Compliance:** Hazard H-005 (Data Loss Protection)  
**Status:** ✅ Ready to Use

---

## 📋 Overview

The database backup system provides automated, compressed, and optionally encrypted backups of your MySQL database with:

- ✅ Automated daily backups via cron
- ✅ Compression (gzip) to save disk space
- ✅ Optional encryption (GPG/AES256)
- ✅ 30-day retention policy
- ✅ Backup verification
- ✅ Email notifications
- ✅ Multiple backup modes (full, structure-only, data-only)
- ✅ Comprehensive logging

---

## 🚀 Quick Start (5 Minutes)

### Step 1: Update .env Configuration

Add these lines to your `.env` file:

```bash
# Database Backup Configuration
BACKUP_RETENTION_DAYS=30
ENABLE_BACKUP_ENCRYPTION=false
BACKUP_NOTIFICATION_EMAIL=your-email@example.com

# Optional: Encryption key (if ENABLE_BACKUP_ENCRYPTION=true)
# BACKUP_ENCRYPTION_KEY=your-strong-passphrase-here
```

### Step 2: Test the Backup Script

```bash
# Run a test backup
/Applications/MAMP/htdocs/know_my_patient/bin/backup_database.sh

# You should see output like:
# [INFO] === Database Backup Started ===
# [SUCCESS] Database connection successful
# [SUCCESS] Database dump created successfully
# [SUCCESS] Backup compressed successfully
# [SUCCESS] Backup integrity verified
# [SUCCESS] === Database Backup Completed Successfully ===
```

### Step 3: Set Up Automated Daily Backups

```bash
# Open crontab editor
crontab -e

# Add this line for daily backups at 2 AM:
0 2 * * * /Applications/MAMP/htdocs/know_my_patient/bin/backup_database.sh >> /Applications/MAMP/htdocs/know_my_patient/logs/backup.log 2>&1

# Save and exit (press ESC, type :wq, press ENTER in vim)
```

### Step 4: Verify Setup

```bash
# List current cron jobs
crontab -l

# Check if backup was created
ls -lh /Applications/MAMP/htdocs/know_my_patient/backups/

# View backup log
tail -f /Applications/MAMP/htdocs/know_my_patient/logs/backup.log
```

---

## 📖 Usage Examples

### Basic Usage

```bash
# Full backup (default)
./bin/backup_database.sh

# Show help
./bin/backup_database.sh --help

# List all backups
./bin/backup_database.sh --list

# Verify latest backup
./bin/backup_database.sh --verify-only

# Clean up old backups
./bin/backup_database.sh --cleanup
```

### Advanced Usage

```bash
# Structure-only backup (no data)
./bin/backup_database.sh --type structure

# Data-only backup (no structure)
./bin/backup_database.sh --type data

# Backup without encryption (even if configured)
./bin/backup_database.sh --no-encrypt

# Force backup even if recent one exists
./bin/backup_database.sh --force
```

---

## 🔧 Configuration Options

### Environment Variables (.env)

| Variable | Default | Description |
|----------|---------|-------------|
| `DB_HOST` | `localhost` | Database host |
| `DB_PORT` | `8889` | Database port (MAMP default) |
| `DB_USER` | `root` | Database username |
| `DB_PASS` | `root` | Database password |
| `DB_NAME` | `know_my_patient` | Database name |
| `BACKUP_RETENTION_DAYS` | `30` | Days to keep backups |
| `ENABLE_BACKUP_ENCRYPTION` | `false` | Enable GPG encryption |
| `BACKUP_ENCRYPTION_KEY` | `` | Encryption passphrase |
| `BACKUP_NOTIFICATION_EMAIL` | `` | Email for notifications |

### Backup Modes

1. **Full Backup** (default)
   - Complete database structure and data
   - Recommended for production
   - Command: `./bin/backup_database.sh`

2. **Structure-Only Backup**
   - Database schema without data
   - Useful for development setup
   - Command: `./bin/backup_database.sh --type structure`

3. **Data-Only Backup**
   - Data without schema
   - For data migrations
   - Command: `./bin/backup_database.sh --type data`

---

## 🔐 Security Features

### Encryption (Optional)

Enable encryption for highly sensitive data:

```bash
# In .env
ENABLE_BACKUP_ENCRYPTION=true
BACKUP_ENCRYPTION_KEY=your-very-strong-passphrase-minimum-20-characters

# Generate strong passphrase
openssl rand -base64 32
```

**Encrypted backups:**
- Use AES256 symmetric encryption
- Require passphrase to decrypt
- Original unencrypted files are deleted
- File extension: `.sql.gz.gpg`

**To decrypt a backup:**
```bash
gpg --decrypt backup_20251012_140530.sql.gz.gpg > backup_20251012_140530.sql.gz
gunzip backup_20251012_140530.sql.gz
```

### Backup File Permissions

```bash
# Secure the backups directory
chmod 700 /Applications/MAMP/htdocs/know_my_patient/backups
chown $(whoami) /Applications/MAMP/htdocs/know_my_patient/backups
```

---

## 📊 Backup Management

### View Backup Statistics

```bash
# List all backups with details
./bin/backup_database.sh --list

# Output:
# Available backups in: /path/to/backups
# 
# Filename                       Size       Date
# --------                       ----       ----
# backup_20251012_140530.sql.gz  2.3M       2025-10-12 14:05
# backup_20251011_020000.sql.gz  2.1M       2025-10-11 02:00
```

### Check Backup Size

```bash
# Total size of all backups
du -sh /Applications/MAMP/htdocs/know_my_patient/backups

# Individual backup sizes
ls -lh /Applications/MAMP/htdocs/know_my_patient/backups/
```

### Verify Backup Integrity

```bash
# Verify latest backup
./bin/backup_database.sh --verify-only

# Manual verification
gzip -t /path/to/backup.sql.gz && echo "✅ Backup is valid" || echo "❌ Backup is corrupted"
```

---

## 🔄 Restore from Backup

### Standard Restore Process

```bash
# 1. Navigate to backups directory
cd /Applications/MAMP/htdocs/know_my_patient/backups

# 2. List available backups
ls -lt backup_*.sql.gz

# 3. Decompress the backup you want to restore
gunzip -k backup_20251012_140530.sql.gz
# (-k keeps the original compressed file)

# 4. IMPORTANT: Create a backup of current database first!
mysqldump -u root -p --port=8889 know_my_patient > current_backup.sql

# 5. Restore the backup
mysql -u root -p --port=8889 know_my_patient < backup_20251012_140530.sql

# 6. Verify restoration
mysql -u root -p --port=8889 -e "SELECT COUNT(*) FROM users;" know_my_patient
```

### Emergency Restore Script

Create a quick restore script:

```bash
#!/bin/bash
# restore_backup.sh

BACKUP_FILE=$1
DB_NAME="know_my_patient"
DB_USER="root"
DB_PASS="root"
DB_PORT="8889"

if [ -z "$BACKUP_FILE" ]; then
    echo "Usage: $0 <backup_file.sql.gz>"
    exit 1
fi

echo "⚠️  WARNING: This will overwrite the current database!"
read -p "Are you sure? (yes/no): " confirm

if [ "$confirm" != "yes" ]; then
    echo "Restore cancelled."
    exit 0
fi

echo "📦 Decompressing backup..."
gunzip -c "$BACKUP_FILE" > /tmp/restore_temp.sql

echo "🔄 Restoring database..."
mysql --user="$DB_USER" --password="$DB_PASS" --port="$DB_PORT" "$DB_NAME" < /tmp/restore_temp.sql

if [ $? -eq 0 ]; then
    echo "✅ Database restored successfully!"
    rm /tmp/restore_temp.sql
else
    echo "❌ Restore failed!"
    exit 1
fi
```

---

## 📧 Email Notifications

### Setup (macOS)

```bash
# Install mailutils if needed
brew install mailutils

# Configure in .env
BACKUP_NOTIFICATION_EMAIL=admin@knowmypatient.nhs.uk

# Test email
echo "Test backup notification" | mail -s "Test Subject" admin@knowmypatient.nhs.uk
```

### Notification Content

Emails include:
- Backup status (SUCCESS/FAILED)
- Database name and host
- Backup file name and size
- Encryption status
- List of recent backups

---

## 🔍 Monitoring & Troubleshooting

### Check Backup Logs

```bash
# View all backup logs
cat /Applications/MAMP/htdocs/know_my_patient/logs/backup.log

# Watch logs in real-time
tail -f /Applications/MAMP/htdocs/know_my_patient/logs/backup.log

# Check for errors
grep ERROR /Applications/MAMP/htdocs/know_my_patient/logs/backup.log

# Check for successful backups
grep "Backup Completed Successfully" /Applications/MAMP/htdocs/know_my_patient/logs/backup.log
```

### Common Issues & Solutions

#### Issue 1: "mysqldump: command not found"

**Solution:**
```bash
# Add MySQL to PATH (add to ~/.zshrc or ~/.bash_profile)
export PATH="/Applications/MAMP/Library/bin:$PATH"

# Reload shell
source ~/.zshrc
```

#### Issue 2: "Access denied for user"

**Solution:**
```bash
# Verify credentials in .env
echo $DB_USER
echo $DB_PASS

# Test connection manually
mysql -u root -p --port=8889 -e "SHOW DATABASES;"
```

#### Issue 3: "Permission denied"

**Solution:**
```bash
# Make script executable
chmod +x /Applications/MAMP/htdocs/know_my_patient/bin/backup_database.sh

# Fix backups directory permissions
chmod 755 /Applications/MAMP/htdocs/know_my_patient/backups
```

#### Issue 4: "Disk space full"

**Solution:**
```bash
# Check disk space
df -h

# Clean up old backups manually
find /path/to/backups -name "backup_*.sql.gz" -mtime +30 -delete

# Or use cleanup command
./bin/backup_database.sh --cleanup
```

#### Issue 5: Cron job not running

**Solution:**
```bash
# Check cron service is running
ps aux | grep cron

# Check crontab entry
crontab -l

# Check cron logs (macOS)
log show --predicate 'process == "cron"' --last 1h

# Test manual run
/Applications/MAMP/htdocs/know_my_patient/bin/backup_database.sh
```

---

## 📈 Best Practices

### 1. Regular Testing (Monthly)

```bash
# Test backup restoration monthly
# 1. Create test backup
./bin/backup_database.sh

# 2. Create test database
mysql -u root -p --port=8889 -e "CREATE DATABASE test_restore;"

# 3. Restore to test database
gunzip -c backups/backup_latest.sql.gz | \
    mysql -u root -p --port=8889 test_restore

# 4. Verify data
mysql -u root -p --port=8889 -e "SELECT COUNT(*) FROM users;" test_restore

# 5. Drop test database
mysql -u root -p --port=8889 -e "DROP DATABASE test_restore;"
```

### 2. Offsite Backups (Recommended)

```bash
# Copy backups to cloud storage (example: AWS S3)
aws s3 sync /path/to/backups s3://your-bucket/database-backups/

# Or use rsync to remote server
rsync -avz /path/to/backups/ user@remote-server:/backups/know_my_patient/
```

### 3. Retention Strategy

| Backup Type | Frequency | Retention |
|-------------|-----------|-----------|
| Daily | Every 2 AM | 30 days |
| Weekly | Sunday 2 AM | 12 weeks |
| Monthly | 1st of month | 12 months |
| Yearly | Jan 1st | 7 years (NHS requirement) |

### 4. Backup Verification Checklist

Monthly verification:
- [ ] Check backup exists
- [ ] Verify file is not empty
- [ ] Test gzip integrity
- [ ] Perform test restoration
- [ ] Check logs for errors
- [ ] Verify disk space available
- [ ] Test email notifications

---

## 🏥 NHS DCB0129 Compliance

### Hazard H-005: Data Loss or Corruption

**Mitigation:**
- ✅ Automated daily backups
- ✅ 30-day retention (configurable)
- ✅ Backup verification
- ✅ Comprehensive logging
- ✅ Email notifications on failure

**Evidence:**
- Backup logs in `logs/backup.log`
- Backup files in `backups/` directory
- Crontab entry for automation
- Testing records (maintain monthly)

**Residual Risk:** LOW (Risk score: 2)
- Likelihood: 1 (Rare - with backups)
- Severity: 4 (Major - if it occurs)

### Audit Requirements

Document for compliance:
1. **Backup Schedule** - Daily at 2 AM (crontab entry)
2. **Retention Policy** - 30 days standard
3. **Testing Frequency** - Monthly restoration tests
4. **Verification Process** - Automated integrity checks
5. **Incident Response** - Email notifications on failure

---

## 📊 Backup Statistics Dashboard

Create a monitoring script:

```bash
#!/bin/bash
# backup_stats.sh

BACKUP_DIR="/Applications/MAMP/htdocs/know_my_patient/backups"

echo "=== Database Backup Statistics ==="
echo ""
echo "Total Backups: $(ls -1 "$BACKUP_DIR"/backup_*.sql.gz* 2>/dev/null | wc -l)"
echo "Total Size: $(du -sh "$BACKUP_DIR" 2>/dev/null | cut -f1)"
echo "Oldest Backup: $(ls -t "$BACKUP_DIR"/backup_*.sql.gz* 2>/dev/null | tail -1 | xargs basename)"
echo "Newest Backup: $(ls -t "$BACKUP_DIR"/backup_*.sql.gz* 2>/dev/null | head -1 | xargs basename)"
echo ""
echo "Last 5 Backups:"
ls -lht "$BACKUP_DIR"/backup_*.sql.gz* 2>/dev/null | head -5
echo ""
echo "Disk Space:"
df -h "$BACKUP_DIR"
```

---

## 🎯 Quick Reference

### Essential Commands

```bash
# Run backup now
./bin/backup_database.sh

# List backups
./bin/backup_database.sh --list

# Verify backup
./bin/backup_database.sh --verify-only

# Clean old backups
./bin/backup_database.sh --cleanup

# View logs
tail -f logs/backup.log

# Check cron
crontab -l

# Restore backup
gunzip -c backups/backup_YYYYMMDD_HHMMSS.sql.gz | \
    mysql -u root -p --port=8889 know_my_patient
```

### File Locations

```
/Applications/MAMP/htdocs/know_my_patient/
├── bin/
│   └── backup_database.sh          # Backup script
├── backups/                        # Backup files (created)
│   ├── backup_20251012_140530.sql.gz
│   └── backup_20251011_020000.sql.gz
├── logs/
│   └── backup.log                  # Backup logs
└── .env                            # Configuration
```

---

## 🚨 Emergency Procedures

### If Backup Fails

1. **Check logs immediately:**
   ```bash
   tail -50 /Applications/MAMP/htdocs/know_my_patient/logs/backup.log
   ```

2. **Verify database is running:**
   ```bash
   mysql -u root -p --port=8889 -e "SHOW DATABASES;"
   ```

3. **Check disk space:**
   ```bash
   df -h
   ```

4. **Run manual backup:**
   ```bash
   ./bin/backup_database.sh --force
   ```

5. **Contact DBA if issue persists**

### If Database Corruption Detected

1. **Stop application immediately**
2. **Identify last good backup:**
   ```bash
   ./bin/backup_database.sh --list
   ```
3. **Test backup before restoration:**
   ```bash
   ./bin/backup_database.sh --verify-only
   ```
4. **Follow restoration procedure above**
5. **Document incident for CSO review**

---

## ✅ Setup Completion Checklist

- [ ] Backup script created and executable
- [ ] `.env` configuration completed
- [ ] Test backup run successfully
- [ ] Cron job configured for daily backups
- [ ] Backup directory permissions set
- [ ] Email notifications tested (optional)
- [ ] Restoration process tested
- [ ] Logs directory exists
- [ ] Documentation reviewed
- [ ] Team trained on restore procedures

---

## 📞 Support

**Questions or Issues:**
- Check troubleshooting section above
- Review logs: `logs/backup.log`
- GitHub Issues: Macca2025/Know-my-patient

**Related Documentation:**
- NHS_DCB0129_COMPLIANCE.md (Hazard H-005)
- WEBSITE_BEST_PRACTICES.md (Section 9: Backup & Disaster Recovery)

---

**Last Updated:** 12 October 2025  
**Script Version:** 1.0  
**Status:** ✅ Production Ready
