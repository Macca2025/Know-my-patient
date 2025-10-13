#!/bin/bash
#
# Log Rotation Script for Know My Patient
# Rotates logs according to logrotate.conf
#
# Usage:
#   ./rotate_logs.sh           # Run rotation
#   ./rotate_logs.sh --force   # Force rotation even if not scheduled
#   ./rotate_logs.sh --test    # Test configuration without actually rotating

# Set project root directory
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOGROTATE_CONF="$PROJECT_ROOT/logrotate.conf"
LOGROTATE_STATE="$PROJECT_ROOT/var/logrotate.state"

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Check if logrotate is installed
if ! command -v logrotate &> /dev/null; then
    echo -e "${RED}Error: logrotate is not installed${NC}"
    echo "Install it with: brew install logrotate"
    exit 1
fi

# Create var directory if it doesn't exist
mkdir -p "$PROJECT_ROOT/var"

# Parse command line arguments
MODE="run"
if [ "$1" = "--force" ] || [ "$1" = "-f" ]; then
    MODE="force"
    echo -e "${YELLOW}Force mode: Will rotate logs regardless of schedule${NC}"
elif [ "$1" = "--test" ] || [ "$1" = "-t" ] || [ "$1" = "--debug" ] || [ "$1" = "-d" ]; then
    MODE="test"
    echo -e "${YELLOW}Test mode: Will not actually rotate logs${NC}"
fi

# Run logrotate
echo -e "${GREEN}Running log rotation...${NC}"
echo "Configuration: $LOGROTATE_CONF"
echo "State file: $LOGROTATE_STATE"
echo ""

case $MODE in
    test)
        # Test mode - show what would be done
        logrotate -d -s "$LOGROTATE_STATE" "$LOGROTATE_CONF"
        ;;
    force)
        # Force rotation
        logrotate -f -s "$LOGROTATE_STATE" "$LOGROTATE_CONF"
        if [ $? -eq 0 ]; then
            echo -e "${GREEN}✓ Logs rotated successfully (forced)${NC}"
        else
            echo -e "${RED}✗ Log rotation failed${NC}"
            exit 1
        fi
        ;;
    run)
        # Normal rotation
        logrotate -s "$LOGROTATE_STATE" "$LOGROTATE_CONF"
        if [ $? -eq 0 ]; then
            echo -e "${GREEN}✓ Logs rotated successfully${NC}"
        else
            echo -e "${RED}✗ Log rotation failed${NC}"
            exit 1
        fi
        ;;
esac

# Show log statistics
echo ""
echo -e "${GREEN}Log directory statistics:${NC}"
du -sh "$PROJECT_ROOT/logs"
echo ""
echo "Log files:"
ls -lh "$PROJECT_ROOT/logs/"*.log 2>/dev/null || echo "No log files found"
echo ""
echo "Compressed logs:"
ls -lh "$PROJECT_ROOT/logs/"*.gz 2>/dev/null || echo "No compressed logs found"

# Cleanup old compressed logs (older than 90 days)
echo ""
echo -e "${YELLOW}Cleaning up compressed logs older than 90 days...${NC}"
find "$PROJECT_ROOT/logs" -name "*.gz" -type f -mtime +90 -delete
echo -e "${GREEN}✓ Cleanup complete${NC}"

exit 0
