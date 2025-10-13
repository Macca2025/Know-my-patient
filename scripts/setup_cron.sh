#!/bin/bash

##############################################################################
# Cron Job Setup Script for Know My Patient
# Automates setup of log rotation and database backups
##############################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  Know My Patient - Cron Setup${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

##############################################################################
# Function to print colored messages
##############################################################################
print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

##############################################################################
# Check if running on macOS
##############################################################################
if [[ "$OSTYPE" != "darwin"* ]]; then
    print_warning "This script is optimized for macOS. Some commands may need adjustment for Linux."
fi

##############################################################################
# 1. Set up Log Rotation
##############################################################################
echo ""
echo -e "${BLUE}=== Setting up Log Rotation ===${NC}"
echo ""

# Check if logrotate is installed
if ! command -v logrotate &> /dev/null; then
    print_warning "logrotate not found. Installing via Homebrew..."
    
    if ! command -v brew &> /dev/null; then
        print_error "Homebrew not found. Please install Homebrew first:"
        echo "  /bin/bash -c \"\$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)\""
        exit 1
    fi
    
    brew install logrotate
    print_success "logrotate installed"
else
    print_success "logrotate already installed"
fi

# Create logrotate state directory
mkdir -p "${SCRIPT_DIR}/var"
LOGROTATE_STATE="${SCRIPT_DIR}/var/logrotate.state"

print_info "Logrotate state file: ${LOGROTATE_STATE}"

# Test logrotate configuration
print_info "Testing logrotate configuration..."
if logrotate -d "${SCRIPT_DIR}/logrotate.conf" &> /dev/null; then
    print_success "Logrotate configuration is valid"
else
    print_error "Logrotate configuration has errors. Please check logrotate.conf"
    exit 1
fi

##############################################################################
# 2. Set up Database Backup Script
##############################################################################
echo ""
echo -e "${BLUE}=== Setting up Database Backups ===${NC}"
echo ""

# Make backup script executable
BACKUP_SCRIPT="${SCRIPT_DIR}/bin/backup_database.sh"
if [ -f "$BACKUP_SCRIPT" ]; then
    chmod +x "$BACKUP_SCRIPT"
    print_success "Backup script is executable: ${BACKUP_SCRIPT}"
else
    print_error "Backup script not found: ${BACKUP_SCRIPT}"
    exit 1
fi

# Create backup directory
BACKUP_DIR="${SCRIPT_DIR}/backups"
mkdir -p "$BACKUP_DIR"
print_success "Backup directory ready: ${BACKUP_DIR}"

# Test backup script (dry run)
print_info "Testing backup script..."
if bash "$BACKUP_SCRIPT" --help &> /dev/null || bash "$BACKUP_SCRIPT" 2>&1 | grep -q "Database"; then
    print_success "Backup script is working"
else
    print_warning "Could not test backup script. Will proceed anyway."
fi

##############################################################################
# 3. Create Cron Jobs
##############################################################################
echo ""
echo -e "${BLUE}=== Creating Cron Jobs ===${NC}"
echo ""

# Create a temporary file for new cron entries
TEMP_CRON=$(mktemp)

# Get existing crontab (if any)
crontab -l > "$TEMP_CRON" 2>/dev/null || true

# Remove old entries for this project (if any)
sed -i.bak '/know_my_patient/d' "$TEMP_CRON"
sed -i.bak '/Know My Patient/d' "$TEMP_CRON"

# Add new cron entries
cat >> "$TEMP_CRON" << EOF

# ========================================
# Know My Patient - Automated Tasks
# ========================================

# Log Rotation - Daily at 1:00 AM
0 1 * * * /usr/local/bin/logrotate -s "${LOGROTATE_STATE}" "${SCRIPT_DIR}/logrotate.conf" >> "${SCRIPT_DIR}/logs/cron.log" 2>&1

# Database Backup - Daily at 2:00 AM
0 2 * * * bash "${BACKUP_SCRIPT}" >> "${SCRIPT_DIR}/logs/backup.log" 2>&1

# Database Backup - Weekly full backup every Sunday at 3:00 AM
0 3 * * 0 bash "${BACKUP_SCRIPT}" --mode full >> "${SCRIPT_DIR}/logs/backup.log" 2>&1

# Health Check - Every 5 minutes (optional, for monitoring)
# */5 * * * * curl -s http://localhost:8080/health > /dev/null 2>&1

EOF

# Install the new crontab
if crontab "$TEMP_CRON"; then
    print_success "Cron jobs installed successfully"
else
    print_error "Failed to install cron jobs"
    rm "$TEMP_CRON"
    exit 1
fi

# Clean up
rm "$TEMP_CRON"

##############################################################################
# 4. Display Summary
##############################################################################
echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  ✅ Setup Complete!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""

echo -e "${BLUE}Scheduled Tasks:${NC}"
echo ""
echo "  📋 Log Rotation:"
echo "     • Schedule: Daily at 1:00 AM"
echo "     • Config:   ${SCRIPT_DIR}/logrotate.conf"
echo "     • State:    ${LOGROTATE_STATE}"
echo "     • Logs:     ${SCRIPT_DIR}/logs/cron.log"
echo ""
echo "  💾 Database Backup:"
echo "     • Daily:    Every day at 2:00 AM"
echo "     • Weekly:   Every Sunday at 3:00 AM (full backup)"
echo "     • Script:   ${BACKUP_SCRIPT}"
echo "     • Backups:  ${BACKUP_DIR}"
echo "     • Logs:     ${SCRIPT_DIR}/logs/backup.log"
echo ""

echo -e "${BLUE}Current Crontab:${NC}"
echo ""
crontab -l | grep -A 10 "Know My Patient" || echo "  No entries found (this shouldn't happen)"
echo ""

echo -e "${BLUE}Useful Commands:${NC}"
echo ""
echo "  View cron jobs:"
echo "    crontab -l"
echo ""
echo "  Edit cron jobs:"
echo "    crontab -e"
echo ""
echo "  Remove all cron jobs (careful!):"
echo "    crontab -r"
echo ""
echo "  Test log rotation manually:"
echo "    logrotate -f ${SCRIPT_DIR}/logrotate.conf"
echo ""
echo "  Test backup manually:"
echo "    bash ${BACKUP_SCRIPT}"
echo ""
echo "  View backup logs:"
echo "    tail -f ${SCRIPT_DIR}/logs/backup.log"
echo ""
echo "  View cron logs:"
echo "    tail -f ${SCRIPT_DIR}/logs/cron.log"
echo ""

##############################################################################
# 5. Run Initial Test Backups
##############################################################################
echo -e "${BLUE}=== Running Initial Test ===${NC}"
echo ""

print_info "Running manual log rotation test..."
logrotate -f "${SCRIPT_DIR}/logrotate.conf" 2>&1 | head -10
print_success "Log rotation test complete"

echo ""
print_info "Creating test backup (this may take a moment)..."
if bash "$BACKUP_SCRIPT" 2>&1 | tail -10; then
    print_success "Test backup complete"
    
    # Show latest backup
    LATEST_BACKUP=$(ls -t "$BACKUP_DIR"/*.gz 2>/dev/null | head -1)
    if [ -n "$LATEST_BACKUP" ]; then
        BACKUP_SIZE=$(du -h "$LATEST_BACKUP" | cut -f1)
        print_success "Latest backup: $(basename "$LATEST_BACKUP") (${BACKUP_SIZE})"
    fi
else
    print_warning "Test backup may have encountered issues. Check logs/backup.log"
fi

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  All Done! 🎉${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
print_info "Your automated tasks are now scheduled."
print_info "Logs will be rotated daily at 1:00 AM."
print_info "Database backups will run daily at 2:00 AM."
echo ""
