# Log Rotation Setup Guide

## Overview
This guide explains how to set up automatic log rotation for Know My Patient application.

---

## Quick Setup (Recommended for Development/MAMP)

### 1. Make the rotation script executable
```bash
cd /Applications/MAMP/htdocs/know_my_patient
chmod +x rotate_logs.sh
```

### 2. Test the configuration
```bash
./rotate_logs.sh --test
```

### 3. Run manual rotation
```bash
./rotate_logs.sh
```

### 4. Force rotation (for testing)
```bash
./rotate_logs.sh --force
```

---

## Automatic Rotation with Cron (Recommended for Production)

### Option 1: User Crontab (No sudo required)

1. Open your crontab:
```bash
crontab -e
```

2. Add this line to rotate logs daily at 2 AM:
```cron
0 2 * * * /Applications/MAMP/htdocs/know_my_patient/rotate_logs.sh >> /Applications/MAMP/htdocs/know_my_patient/logs/rotation.log 2>&1
```

3. Save and exit (`:wq` in vim)

4. Verify cron job is installed:
```bash
crontab -l
```

### Option 2: System-wide Logrotate (Requires sudo)

1. Install logrotate if not already installed:
```bash
brew install logrotate
```

2. Copy configuration to system directory:
```bash
sudo cp logrotate.conf /etc/logrotate.d/know-my-patient
```

3. Test configuration:
```bash
sudo logrotate -d /etc/logrotate.d/know-my-patient
```

4. Run manually to verify:
```bash
sudo logrotate -f /etc/logrotate.d/know-my-patient
```

5. System logrotate will run automatically via cron

---

## Configuration Details

### Current Settings

**Application Logs** (`logs/*.log`):
- Rotation: Daily
- Retention: 30 days
- Compression: Yes (gzip)
- Date format: YYYYMMDD

**Error Logs** (`logs/error*.log`):
- Rotation: Daily
- Retention: 60 days (longer for debugging)
- Compression: Yes

**Audit Logs** (`logs/audit*.log`):
- Rotation: Weekly
- Retention: 52 weeks (1 year for compliance)
- Compression: Yes

### Customizing Rotation

Edit `logrotate.conf` to change:

```conf
# Change rotation frequency
daily        # Options: daily, weekly, monthly
rotate 30    # Number of old logs to keep

# Change when to compress
compress           # Compress old logs
delaycompress     # Don't compress yesterday's log
nocompress        # Don't compress at all

# Change file permissions
create 0644 www-data www-data
```

---

## Log File Management

### Check Log Sizes
```bash
du -sh logs/
ls -lh logs/*.log
```

### View Compressed Logs
```bash
# View compressed log
zcat logs/app-20251012.log.gz

# Search in compressed log
zgrep "ERROR" logs/app-20251012.log.gz
```

### Manual Cleanup
```bash
# Remove logs older than 90 days
find logs/ -name "*.log.gz" -mtime +90 -delete

# Remove all compressed logs
rm logs/*.gz
```

---

## Monitoring

### Check Last Rotation
```bash
cat var/logrotate.state
```

### View Rotation Log
```bash
tail -f logs/rotation.log
```

### Check Disk Space
```bash
df -h /Applications/MAMP/htdocs/know_my_patient
```

---

## Troubleshooting

### Logrotate not found
```bash
brew install logrotate
```

### Permission denied
```bash
chmod +x rotate_logs.sh
chmod 644 logrotate.conf
```

### Logs not rotating
```bash
# Test configuration
./rotate_logs.sh --test

# Check for errors
./rotate_logs.sh --force

# Verify cron is running
crontab -l
```

### Too many old logs
```bash
# Adjust retention in logrotate.conf
rotate 30  # Keep only 30 days

# Manual cleanup
find logs/ -name "*.log.gz" -mtime +30 -delete
```

---

## Best Practices

1. **Test First**: Always use `--test` before forcing rotation
2. **Monitor Disk Space**: Set up alerts when disk usage > 80%
3. **Backup Logs**: Before major changes, backup important logs
4. **Compliance**: Keep audit logs for required retention period (1 year+)
5. **Regular Checks**: Review log sizes weekly
6. **Compression**: Always compress old logs to save space

---

## Integration with Monitoring

### Example: Send Alert on Rotation Failure

Add to `postrotate` section in `logrotate.conf`:

```conf
postrotate
    if [ $? -ne 0 ]; then
        echo "Log rotation failed at $(date)" | mail -s "Log Rotation Alert" admin@example.com
    fi
endscript
```

### Example: Log Rotation Metrics

Add to `rotate_logs.sh`:

```bash
# Send metrics to monitoring system
curl -X POST https://monitoring.example.com/metrics \
  -d "log_rotation_success=1" \
  -d "timestamp=$(date +%s)"
```

---

## Scheduled Tasks Summary

| Task | Frequency | Command |
|------|-----------|---------|
| Rotate app logs | Daily 2 AM | `./rotate_logs.sh` |
| Rotate error logs | Daily 2 AM | (included) |
| Rotate audit logs | Weekly | (included) |
| Cleanup old logs | Daily | (automatic in script) |

---

## Files

- `logrotate.conf` - Configuration file
- `rotate_logs.sh` - Rotation script
- `var/logrotate.state` - State file (tracks last rotation)
- `logs/rotation.log` - Rotation activity log

---

## Questions?

Review the comments in `logrotate.conf` and `rotate_logs.sh` for more details.
