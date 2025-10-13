# Automated Tasks Setup Guide

**Last Updated:** 13 October 2025  
**Status:** Ready for Production

---

## 📋 Overview

This guide covers setting up automated tasks for the Know My Patient application:
- **Log Rotation**: Daily cleanup of application logs
- **Database Backups**: Automated database backups with compression
- **Health Monitoring**: Optional uptime checks

---

## 🚀 Quick Setup (Automated)

### One-Command Setup

```bash
cd /Applications/MAMP/htdocs/know_my_patient
chmod +x setup_cron.sh
./setup_cron.sh
```

This script will:
1. ✅ Install logrotate (if needed via Homebrew)
2. ✅ Make backup script executable
3. ✅ Test both configurations
4. ✅ Install cron jobs automatically
5. ✅ Run initial test backups
6. ✅ Display setup summary

**Estimated Time:** 2-3 minutes

---

## 📅 Scheduled Tasks

After running `setup_cron.sh`, these tasks will run automatically:

### 1. Log Rotation
- **Schedule:** Daily at 1:00 AM
- **What it does:**
  - Rotates all `.log` files in `logs/` directory
  - Compresses old logs (saves 70-80% disk space)
  - Keeps 30 days of history
  - Deletes logs older than 30 days
- **Config:** `logrotate.conf`
- **Logs:** `logs/cron.log`

### 2. Database Backup (Daily)
- **Schedule:** Daily at 2:00 AM
- **What it does:**
  - Full MySQL database dump
  - Gzip compression (reduces size by 80-90%)
  - 30-day retention policy
  - Automatic cleanup of old backups
- **Script:** `bin/backup_database.sh`
- **Backups:** `backups/` directory
- **Logs:** `logs/backup.log`

### 3. Database Backup (Weekly Full)
- **Schedule:** Every Sunday at 3:00 AM
- **What it does:**
  - Full database backup with verification
  - Extra safety net for weekly archives
- **Same script as daily backup**

---

## 🛠️ Manual Setup (Alternative)

If you prefer manual setup or the automated script doesn't work:

### Step 1: Install logrotate (macOS)

```bash
# Install via Homebrew
brew install logrotate
```

### Step 2: Make Backup Script Executable

```bash
chmod +x /Applications/MAMP/htdocs/know_my_patient/bin/backup_database.sh
```

### Step 3: Test Configurations

```bash
# Test log rotation
logrotate -d /Applications/MAMP/htdocs/know_my_patient/logrotate.conf

# Test backup script
bash /Applications/MAMP/htdocs/know_my_patient/bin/backup_database.sh
```

### Step 4: Add Cron Jobs

```bash
# Edit crontab
crontab -e

# Add these lines (adjust paths if needed):
# Log Rotation - Daily at 1:00 AM
0 1 * * * /usr/local/bin/logrotate -s /Applications/MAMP/htdocs/know_my_patient/var/logrotate.state /Applications/MAMP/htdocs/know_my_patient/logrotate.conf >> /Applications/MAMP/htdocs/know_my_patient/logs/cron.log 2>&1

# Database Backup - Daily at 2:00 AM
0 2 * * * bash /Applications/MAMP/htdocs/know_my_patient/bin/backup_database.sh >> /Applications/MAMP/htdocs/know_my_patient/logs/backup.log 2>&1

# Database Backup - Weekly full backup every Sunday at 3:00 AM
0 3 * * 0 bash /Applications/MAMP/htdocs/know_my_patient/bin/backup_database.sh --mode full >> /Applications/MAMP/htdocs/know_my_patient/logs/backup.log 2>&1
```

---

## 📊 Monitoring & Verification

### Check Cron Status

```bash
# View all cron jobs
crontab -l

# View cron jobs for this project only
crontab -l | grep -A 10 "Know My Patient"
```

### Check Log Rotation

```bash
# View log rotation status
cat /Applications/MAMP/htdocs/know_my_patient/var/logrotate.state

# View cron logs
tail -50 /Applications/MAMP/htdocs/know_my_patient/logs/cron.log

# List rotated logs
ls -lh /Applications/MAMP/htdocs/know_my_patient/logs/*.gz
```

### Check Database Backups

```bash
# View backup logs
tail -50 /Applications/MAMP/htdocs/know_my_patient/logs/backup.log

# List all backups
ls -lh /Applications/MAMP/htdocs/know_my_patient/backups/

# Check latest backup
ls -lt /Applications/MAMP/htdocs/know_my_patient/backups/ | head -5

# Verify backup size and date
du -h /Applications/MAMP/htdocs/know_my_patient/backups/*.gz
```

---

## 🧪 Manual Testing

### Test Log Rotation Now

```bash
cd /Applications/MAMP/htdocs/know_my_patient

# Force log rotation
logrotate -f logrotate.conf

# Check results
ls -lh logs/
```

### Test Database Backup Now

```bash
cd /Applications/MAMP/htdocs/know_my_patient

# Run backup manually
bash bin/backup_database.sh

# Run backup with full mode
bash bin/backup_database.sh --mode full

# Run backup with verification
bash bin/backup_database.sh --verify
```

---

## 🔧 Customization

### Change Backup Schedule

Edit your crontab:
```bash
crontab -e
```

**Cron Schedule Format:**
```
* * * * * command
│ │ │ │ │
│ │ │ │ └─── Day of week (0-7, Sunday = 0 or 7)
│ │ │ └───── Month (1-12)
│ │ └─────── Day of month (1-31)
│ └───────── Hour (0-23)
└─────────── Minute (0-59)
```

**Examples:**
```bash
# Every hour at minute 0
0 * * * * command

# Every 6 hours
0 */6 * * * command

# Every day at 3:30 AM
30 3 * * * command

# Every Monday at 4:00 AM
0 4 * * 1 command

# First day of every month at midnight
0 0 1 * * command
```

### Change Log Retention

Edit `logrotate.conf`:
```bash
nano logrotate.conf

# Change this line:
rotate 30  # Change 30 to desired number of days
```

### Change Backup Retention

Edit `.env`:
```bash
nano .env

# Add or modify:
BACKUP_RETENTION_DAYS=30  # Change to desired number of days
```

---

## 📧 Email Notifications (Optional)

### Enable Backup Notifications

1. Edit `.env`:
```bash
BACKUP_NOTIFICATION_EMAIL=admin@example.com
```

2. Ensure system can send email (requires postfix/sendmail):
```bash
# Test email
echo "Test" | mail -s "Test Subject" admin@example.com
```

3. Alternative: Use a monitoring service (see `UPTIMEROBOT_SETUP.md`)

---

## 🚨 Troubleshooting

### Cron Jobs Not Running

**Check if cron service is running:**
```bash
# macOS
sudo launchctl list | grep cron

# If not running, load it:
sudo launchctl load -w /System/Library/LaunchDaemons/com.vix.cron.plist
```

**Check cron logs:**
```bash
# macOS system cron log
log show --predicate 'process == "cron"' --last 1h

# Project cron log
tail -f /Applications/MAMP/htdocs/know_my_patient/logs/cron.log
```

**Common Issues:**
1. **PATH not set in cron:** Add full paths to commands
2. **Permissions:** Ensure scripts are executable (`chmod +x`)
3. **MAMP MySQL not in PATH:** Script auto-detects MAMP paths

### Log Rotation Not Working

**Test configuration:**
```bash
logrotate -d logrotate.conf
```

**Force rotation:**
```bash
logrotate -f logrotate.conf
```

**Check permissions:**
```bash
ls -l logs/
# Should be writable by your user
```

### Backup Script Failing

**Check backup logs:**
```bash
tail -50 logs/backup.log
```

**Test database connection:**
```bash
# MAMP MySQL
/Applications/MAMP/Library/bin/mysql80/bin/mysql -u root -p -e "SELECT 1"
```

**Check .env configuration:**
```bash
cat .env | grep DB_
```

**Common Issues:**
1. **MySQL not found:** Add MAMP MySQL to PATH
2. **Wrong credentials:** Check DB_USER and DB_PASS in .env
3. **Disk space:** Check `df -h`

---

## 📦 Backup Recovery

### Restore from Backup

```bash
# Decompress backup
gunzip backups/backup_20251013_020000.sql.gz

# Restore to database
/Applications/MAMP/Library/bin/mysql80/bin/mysql -u root -p know_my_patient < backups/backup_20251013_020000.sql

# Or use the backup script's restore function
bash bin/backup_database.sh --restore backups/backup_20251013_020000.sql.gz
```

### Verify Backup Integrity

```bash
# Check if backup file is valid gzip
gunzip -t backups/backup_20251013_020000.sql.gz

# Check backup size (should be > 0 bytes)
ls -lh backups/backup_20251013_020000.sql.gz

# Verify SQL syntax (decompress and check first 50 lines)
gunzip -c backups/backup_20251013_020000.sql.gz | head -50
```

---

## 🔐 Security Considerations

### Backup Encryption (Optional)

Enable GPG encryption in `.env`:
```bash
ENABLE_BACKUP_ENCRYPTION=true
```

Generate GPG key:
```bash
gpg --gen-key
```

### Secure Backup Storage

**Recommendations:**
1. ✅ Keep backups outside web root (`backups/` is fine)
2. ✅ Use `.htaccess` to deny web access to `backups/`
3. ✅ Consider off-site backup copy (AWS S3, Dropbox, etc.)
4. ✅ Encrypt sensitive backups
5. ✅ Regularly test restore procedures

---

## 📊 Disk Space Management

### Monitor Disk Usage

```bash
# Check backup directory size
du -sh /Applications/MAMP/htdocs/know_my_patient/backups/

# List backups by size
ls -lhS /Applications/MAMP/htdocs/know_my_patient/backups/

# Check total project size
du -sh /Applications/MAMP/htdocs/know_my_patient/
```

### Manual Cleanup

```bash
# Remove backups older than 30 days
find /Applications/MAMP/htdocs/know_my_patient/backups/ -name "backup_*.gz" -mtime +30 -delete

# Remove compressed logs older than 60 days
find /Applications/MAMP/htdocs/know_my_patient/logs/ -name "*.gz" -mtime +60 -delete
```

---

## ✅ Verification Checklist

After setup, verify:

- [ ] `crontab -l` shows 3 entries for Know My Patient
- [ ] Log rotation test works: `logrotate -f logrotate.conf`
- [ ] Backup script runs: `bash bin/backup_database.sh`
- [ ] Backup files appear in `backups/` directory
- [ ] Log files appear in `logs/cron.log` and `logs/backup.log`
- [ ] Disk space is sufficient (check `df -h`)
- [ ] Wait 24 hours and verify automated runs

---

## 🎯 Production Recommendations

### Before Going Live

1. ✅ Run `setup_cron.sh` on production server
2. ✅ Test both scripts manually first
3. ✅ Verify database credentials in `.env`
4. ✅ Ensure adequate disk space (min 10GB free)
5. ✅ Set up off-site backup copy
6. ✅ Document restore procedure
7. ✅ Test restore from backup
8. ✅ Set up monitoring alerts

### Ongoing Maintenance

- **Weekly:** Check backup logs for errors
- **Monthly:** Verify backups are being created
- **Quarterly:** Test restore procedure
- **Annually:** Review retention policies

---

## 📚 Related Documentation

- `DATABASE_BACKUP_SETUP.md` - Detailed backup documentation
- `LOG_ROTATION_SETUP.md` - Detailed log rotation guide
- `DEPLOYMENT.md` - Production deployment checklist
- `RECOMMENDATIONS_STATUS.md` - Overall progress tracking

---

## 🆘 Support

**If you encounter issues:**

1. Check logs: `logs/backup.log` and `logs/cron.log`
2. Run scripts manually to test
3. Verify cron service is running
4. Check file permissions
5. Review this documentation

**For additional help:**
- macOS cron: `man crontab`
- logrotate: `man logrotate`
- Backup script: `bash bin/backup_database.sh --help`

---

**Last Verified:** 13 October 2025  
**Status:** ✅ Ready for Production
