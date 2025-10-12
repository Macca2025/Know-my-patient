#!/bin/bash

##############################################################################
# Database Backup Script for Know My Patient
# NHS DCB0129 Compliance: H-005 (Data Loss Protection)
# 
# Features:
# - Automated MySQL database backups
# - Compression (gzip)
# - Optional encryption (GPG)
# - 30-day retention policy
# - Error notification
# - Backup verification
# - Multiple backup modes (full, structure-only, data-only)
##############################################################################

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration - Read from .env if available
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

# Load environment variables from .env if it exists
if [ -f "$PROJECT_DIR/.env" ]; then
    export $(grep -v '^#' "$PROJECT_DIR/.env" | xargs)
fi

# Database credentials (with fallbacks)
DB_HOST="${DB_HOST:-localhost}"
DB_PORT="${DB_PORT:-8889}"  # MAMP default
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-root}"  # MAMP default
DB_NAME="${DB_NAME:-know_my_patient}"

# Backup configuration
BACKUP_DIR="${BACKUP_DIR:-$PROJECT_DIR/backups}"
RETENTION_DAYS="${BACKUP_RETENTION_DAYS:-30}"
ENABLE_ENCRYPTION="${ENABLE_BACKUP_ENCRYPTION:-false}"
NOTIFICATION_EMAIL="${BACKUP_NOTIFICATION_EMAIL:-}"
LOG_FILE="$PROJECT_DIR/logs/backup.log"

# Backup metadata
DATE=$(date +"%Y%m%d_%H%M%S")
BACKUP_FILE="${BACKUP_DIR}/backup_${DATE}.sql"
COMPRESSED_FILE="${BACKUP_FILE}.gz"
ENCRYPTED_FILE="${COMPRESSED_FILE}.gpg"

##############################################################################
# Functions
##############################################################################

log_message() {
    local level=$1
    shift
    local message="$@"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    
    # Ensure log directory exists
    mkdir -p "$(dirname "$LOG_FILE")"
    
    # Color for console output
    case $level in
        ERROR)   color=$RED ;;
        SUCCESS) color=$GREEN ;;
        WARNING) color=$YELLOW ;;
        INFO)    color=$BLUE ;;
        *)       color=$NC ;;
    esac
    
    # Console output with color
    echo -e "${color}[${timestamp}] [${level}] ${message}${NC}"
    
    # Log file without color
    echo "[${timestamp}] [${level}] ${message}" >> "$LOG_FILE"
}

check_dependencies() {
    log_message "INFO" "Checking dependencies..."
    
    # Add MAMP MySQL to PATH if it exists
    if [ -d "/Applications/MAMP/Library/bin/mysql80/bin" ]; then
        export PATH="/Applications/MAMP/Library/bin/mysql80/bin:$PATH"
        log_message "INFO" "Added MAMP MySQL 8.0 to PATH"
    elif [ -d "/Applications/MAMP/Library/bin" ]; then
        export PATH="/Applications/MAMP/Library/bin:$PATH"
        log_message "INFO" "Added MAMP MySQL to PATH"
    fi
    
    # Check if mysqldump is available
    if ! command -v mysqldump &> /dev/null; then
        log_message "ERROR" "mysqldump not found. Please install MySQL client tools."
        log_message "ERROR" "For MAMP, ensure /Applications/MAMP/Library/bin/mysql80/bin is in PATH"
        exit 1
    fi
    
    # Check if gzip is available
    if ! command -v gzip &> /dev/null; then
        log_message "ERROR" "gzip not found. Please install gzip."
        exit 1
    fi
    
    # Check if GPG is available (if encryption enabled)
    if [ "$ENABLE_ENCRYPTION" = "true" ] && ! command -v gpg &> /dev/null; then
        log_message "WARNING" "GPG not found. Encryption disabled."
        ENABLE_ENCRYPTION=false
    fi
    
    log_message "SUCCESS" "All dependencies satisfied"
}

test_database_connection() {
    log_message "INFO" "Testing database connection..."
    
    if mysql --host="$DB_HOST" \
             --port="$DB_PORT" \
             --user="$DB_USER" \
             --password="$DB_PASS" \
             -e "USE ${DB_NAME};" 2>/dev/null; then
        log_message "SUCCESS" "Database connection successful"
        return 0
    else
        log_message "ERROR" "Cannot connect to database ${DB_NAME}"
        log_message "ERROR" "Host: ${DB_HOST}:${DB_PORT}, User: ${DB_USER}"
        return 1
    fi
}

create_backup() {
    local backup_type=${1:-full}
    
    log_message "INFO" "Starting ${backup_type} backup of database: ${DB_NAME}"
    log_message "INFO" "Backup file: ${BACKUP_FILE}"
    
    # Ensure backup directory exists
    mkdir -p "$BACKUP_DIR"
    
    # Build mysqldump command based on backup type
    local dump_options="--host=$DB_HOST \
                        --port=$DB_PORT \
                        --user=$DB_USER \
                        --password=$DB_PASS \
                        --single-transaction \
                        --routines \
                        --triggers \
                        --events"
    
    case $backup_type in
        full)
            dump_options="$dump_options --complete-insert"
            ;;
        structure)
            dump_options="$dump_options --no-data"
            ;;
        data)
            dump_options="$dump_options --no-create-info"
            ;;
        *)
            log_message "ERROR" "Invalid backup type: $backup_type"
            return 1
            ;;
    esac
    
    # Create the backup
    if mysqldump $dump_options "$DB_NAME" > "$BACKUP_FILE" 2>> "$LOG_FILE"; then
        log_message "SUCCESS" "Database dump created successfully"
        
        # Get file size
        local file_size=$(du -h "$BACKUP_FILE" | cut -f1)
        log_message "INFO" "Backup size: ${file_size}"
        
        return 0
    else
        log_message "ERROR" "Failed to create database dump"
        return 1
    fi
}

compress_backup() {
    log_message "INFO" "Compressing backup..."
    
    if gzip -f "$BACKUP_FILE" 2>> "$LOG_FILE"; then
        local original_size=$(stat -f%z "$COMPRESSED_FILE" 2>/dev/null || stat -c%s "$COMPRESSED_FILE" 2>/dev/null)
        local compressed_size=$(du -h "$COMPRESSED_FILE" | cut -f1)
        
        log_message "SUCCESS" "Backup compressed successfully"
        log_message "INFO" "Compressed size: ${compressed_size}"
        
        return 0
    else
        log_message "ERROR" "Failed to compress backup"
        return 1
    fi
}

encrypt_backup() {
    if [ "$ENABLE_ENCRYPTION" != "true" ]; then
        return 0
    fi
    
    log_message "INFO" "Encrypting backup..."
    
    # Use symmetric encryption with passphrase from environment or prompt
    if [ -n "$BACKUP_ENCRYPTION_KEY" ]; then
        echo "$BACKUP_ENCRYPTION_KEY" | gpg --batch --yes --passphrase-fd 0 \
                                           --symmetric \
                                           --cipher-algo AES256 \
                                           --output "$ENCRYPTED_FILE" \
                                           "$COMPRESSED_FILE" 2>> "$LOG_FILE"
    else
        gpg --symmetric --cipher-algo AES256 --output "$ENCRYPTED_FILE" "$COMPRESSED_FILE" 2>> "$LOG_FILE"
    fi
    
    if [ $? -eq 0 ]; then
        log_message "SUCCESS" "Backup encrypted successfully"
        
        # Remove unencrypted file
        rm -f "$COMPRESSED_FILE"
        log_message "INFO" "Unencrypted backup removed"
        
        return 0
    else
        log_message "ERROR" "Failed to encrypt backup"
        return 1
    fi
}

verify_backup() {
    local file_to_verify
    
    if [ "$ENABLE_ENCRYPTION" = "true" ] && [ -f "$ENCRYPTED_FILE" ]; then
        file_to_verify="$ENCRYPTED_FILE"
    elif [ -f "$COMPRESSED_FILE" ]; then
        file_to_verify="$COMPRESSED_FILE"
    else
        log_message "ERROR" "No backup file found to verify"
        return 1
    fi
    
    log_message "INFO" "Verifying backup integrity..."
    
    # Check if file is not empty
    if [ ! -s "$file_to_verify" ]; then
        log_message "ERROR" "Backup file is empty"
        return 1
    fi
    
    # Verify gzip integrity (if not encrypted)
    if [[ "$file_to_verify" == *.gz ]]; then
        if gzip -t "$file_to_verify" 2>> "$LOG_FILE"; then
            log_message "SUCCESS" "Backup integrity verified"
            return 0
        else
            log_message "ERROR" "Backup file is corrupted"
            return 1
        fi
    fi
    
    log_message "SUCCESS" "Backup file exists and is not empty"
    return 0
}

cleanup_old_backups() {
    log_message "INFO" "Cleaning up backups older than ${RETENTION_DAYS} days..."
    
    local deleted_count=0
    
    # Find and delete old backup files
    while IFS= read -r -d '' file; do
        rm -f "$file"
        deleted_count=$((deleted_count + 1))
        log_message "INFO" "Deleted old backup: $(basename "$file")"
    done < <(find "$BACKUP_DIR" -name "backup_*.sql.gz*" -mtime +${RETENTION_DAYS} -print0)
    
    if [ $deleted_count -eq 0 ]; then
        log_message "INFO" "No old backups to delete"
    else
        log_message "SUCCESS" "Deleted ${deleted_count} old backup(s)"
    fi
}

send_notification() {
    local status=$1
    local message=$2
    
    if [ -z "$NOTIFICATION_EMAIL" ]; then
        return 0
    fi
    
    local subject="Database Backup ${status} - Know My Patient"
    
    if command -v mail &> /dev/null; then
        echo "$message" | mail -s "$subject" "$NOTIFICATION_EMAIL"
        log_message "INFO" "Notification sent to ${NOTIFICATION_EMAIL}"
    else
        log_message "WARNING" "mail command not found. Cannot send email notification."
    fi
}

generate_backup_report() {
    local status=$1
    
    echo "============================================"
    echo "  Database Backup Report"
    echo "============================================"
    echo "Date/Time:    $(date '+%Y-%m-%d %H:%M:%S')"
    echo "Database:     ${DB_NAME}"
    echo "Host:         ${DB_HOST}:${DB_PORT}"
    echo "Status:       ${status}"
    echo ""
    echo "Backup Details:"
    
    if [ "$ENABLE_ENCRYPTION" = "true" ] && [ -f "$ENCRYPTED_FILE" ]; then
        echo "  File:       $(basename "$ENCRYPTED_FILE")"
        echo "  Size:       $(du -h "$ENCRYPTED_FILE" | cut -f1)"
        echo "  Encrypted:  Yes"
    elif [ -f "$COMPRESSED_FILE" ]; then
        echo "  File:       $(basename "$COMPRESSED_FILE")"
        echo "  Size:       $(du -h "$COMPRESSED_FILE" | cut -f1)"
        echo "  Encrypted:  No"
    fi
    
    echo ""
    echo "Retention:    ${RETENTION_DAYS} days"
    echo "Location:     ${BACKUP_DIR}"
    echo ""
    
    # List recent backups
    echo "Recent Backups (last 5):"
    ls -lht "$BACKUP_DIR"/backup_*.sql.gz* 2>/dev/null | head -5 | while read -r line; do
        echo "  $line"
    done
    
    echo "============================================"
}

show_help() {
    cat << EOF
Database Backup Script for Know My Patient

Usage: $(basename "$0") [OPTIONS]

Options:
    -h, --help              Show this help message
    -t, --type TYPE         Backup type: full, structure, data (default: full)
    -n, --no-encrypt        Disable encryption even if configured
    -f, --force             Force backup even if recent backup exists
    -v, --verify-only       Only verify latest backup
    -l, --list              List all backups
    -c, --cleanup           Only run cleanup of old backups

Examples:
    $(basename "$0")                    # Full backup with default settings
    $(basename "$0") --type structure   # Backup database structure only
    $(basename "$0") --no-encrypt       # Backup without encryption
    $(basename "$0") --list             # List all backups
    $(basename "$0") --cleanup          # Clean up old backups

Configuration:
    Edit .env file in project root to configure:
    - DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_NAME
    - BACKUP_RETENTION_DAYS (default: 30)
    - ENABLE_BACKUP_ENCRYPTION (default: false)
    - BACKUP_ENCRYPTION_KEY (if using encryption)
    - BACKUP_NOTIFICATION_EMAIL

NHS DCB0129 Compliance:
    This backup system addresses Hazard H-005 (Data Loss or Corruption)
    by maintaining automated, verified backups with configurable retention.

EOF
}

list_backups() {
    echo "Available backups in: ${BACKUP_DIR}"
    echo ""
    
    if [ ! -d "$BACKUP_DIR" ] || [ -z "$(ls -A "$BACKUP_DIR" 2>/dev/null)" ]; then
        echo "No backups found."
        return
    fi
    
    printf "%-30s %-10s %-20s\n" "Filename" "Size" "Date"
    printf "%-30s %-10s %-20s\n" "--------" "----" "----"
    
    find "$BACKUP_DIR" -name "backup_*.sql.gz*" -type f | sort -r | while read -r file; do
        local filename=$(basename "$file")
        local size=$(du -h "$file" | cut -f1)
        local date=$(stat -f "%Sm" -t "%Y-%m-%d %H:%M" "$file" 2>/dev/null || \
                     stat -c "%y" "$file" 2>/dev/null | cut -d'.' -f1)
        printf "%-30s %-10s %-20s\n" "$filename" "$size" "$date"
    done
}

##############################################################################
# Main Script
##############################################################################

main() {
    local backup_type="full"
    local no_encrypt=false
    local force_backup=false
    local verify_only=false
    local list_only=false
    local cleanup_only=false
    
    # Parse command line arguments
    while [[ $# -gt 0 ]]; do
        case $1 in
            -h|--help)
                show_help
                exit 0
                ;;
            -t|--type)
                backup_type="$2"
                shift 2
                ;;
            -n|--no-encrypt)
                no_encrypt=true
                shift
                ;;
            -f|--force)
                force_backup=true
                shift
                ;;
            -v|--verify-only)
                verify_only=true
                shift
                ;;
            -l|--list)
                list_only=true
                shift
                ;;
            -c|--cleanup)
                cleanup_only=true
                shift
                ;;
            *)
                echo "Unknown option: $1"
                show_help
                exit 1
                ;;
        esac
    done
    
    # Handle list-only mode
    if [ "$list_only" = true ]; then
        list_backups
        exit 0
    fi
    
    # Handle cleanup-only mode
    if [ "$cleanup_only" = true ]; then
        log_message "INFO" "Running cleanup only..."
        cleanup_old_backups
        exit 0
    fi
    
    # Disable encryption if requested
    if [ "$no_encrypt" = true ]; then
        ENABLE_ENCRYPTION=false
    fi
    
    # Start backup process
    log_message "INFO" "=== Database Backup Started ==="
    log_message "INFO" "NHS DCB0129 Compliance: Hazard H-005 Mitigation"
    
    # Check dependencies
    check_dependencies || exit 1
    
    # Test database connection
    test_database_connection || exit 1
    
    # Handle verify-only mode
    if [ "$verify_only" = true ]; then
        log_message "INFO" "Verification mode - checking latest backup..."
        LATEST_BACKUP=$(ls -t "$BACKUP_DIR"/backup_*.sql.gz* 2>/dev/null | head -1)
        if [ -n "$LATEST_BACKUP" ]; then
            COMPRESSED_FILE="$LATEST_BACKUP"
            verify_backup
            exit $?
        else
            log_message "ERROR" "No backups found to verify"
            exit 1
        fi
    fi
    
    # Create backup
    if ! create_backup "$backup_type"; then
        log_message "ERROR" "Backup failed"
        send_notification "FAILED" "Database backup failed. Check logs at: $LOG_FILE"
        exit 1
    fi
    
    # Compress backup
    if ! compress_backup; then
        log_message "ERROR" "Compression failed"
        exit 1
    fi
    
    # Encrypt backup (if enabled)
    if ! encrypt_backup; then
        log_message "ERROR" "Encryption failed"
        exit 1
    fi
    
    # Verify backup
    if ! verify_backup; then
        log_message "ERROR" "Backup verification failed"
        send_notification "FAILED" "Backup verification failed. Check logs at: $LOG_FILE"
        exit 1
    fi
    
    # Cleanup old backups
    cleanup_old_backups
    
    # Generate report
    local report=$(generate_backup_report "SUCCESS")
    echo "$report"
    echo "$report" >> "$LOG_FILE"
    
    # Send success notification
    send_notification "SUCCESS" "$report"
    
    log_message "SUCCESS" "=== Database Backup Completed Successfully ==="
    
    exit 0
}

# Run main function
main "$@"
