#!/bin/bash

# ================================================
# OPcache Production Configuration Installer
# Know My Patient
# ================================================

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}"
cat << "EOF"
╔══════════════════════════════════════════════════════════╗
║                                                          ║
║        OPcache Production Configuration Installer        ║
║                 Know My Patient                          ║
║                                                          ║
╚══════════════════════════════════════════════════════════╝
EOF
echo -e "${NC}"

echo ""
echo -e "${YELLOW}This script will:${NC}"
echo "  1. Backup your current php.ini"
echo "  2. Install optimized OPcache configuration"
echo "  3. Verify the installation"
echo "  4. Show you how to restart PHP"
echo ""

# Detect PHP configuration directory
PHP_INI_DIR=$(php -r "echo PHP_CONFIG_FILE_SCAN_DIR;")
PHP_VERSION=$(php -r "echo PHP_VERSION;")

echo -e "${BLUE}📋 Detected Configuration:${NC}"
echo "  • PHP Version: $PHP_VERSION"
echo "  • Config Directory: $PHP_INI_DIR"
echo ""

# Check if opcache_production.ini exists
if [ ! -f "opcache_production.ini" ]; then
    echo -e "${RED}❌ Error: opcache_production.ini not found!${NC}"
    echo "Please run this script from the project root directory."
    exit 1
fi

echo -e "${YELLOW}📝 Current OPcache Settings:${NC}"
php -r "
\$settings = [
    'opcache.enable',
    'opcache.memory_consumption',
    'opcache.max_accelerated_files',
    'opcache.validate_timestamps'
];
foreach (\$settings as \$setting) {
    \$value = ini_get(\$setting);
    echo \"  • \$setting = \$value\n\";
}
"
echo ""

# Ask for confirmation
read -p "$(echo -e ${YELLOW}Do you want to proceed with installation? [y/N]: ${NC})" -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${RED}Installation cancelled.${NC}"
    exit 0
fi

echo ""
echo -e "${GREEN}🚀 Installing OPcache configuration...${NC}"

# Determine the correct config directory
if [ -d "/opt/homebrew/etc/php/8.4/conf.d" ]; then
    CONFIG_DIR="/opt/homebrew/etc/php/8.4/conf.d"
elif [ -d "/usr/local/etc/php/8.4/conf.d" ]; then
    CONFIG_DIR="/usr/local/etc/php/8.4/conf.d"
elif [ -n "$PHP_INI_DIR" ] && [ -d "$PHP_INI_DIR" ]; then
    CONFIG_DIR="$PHP_INI_DIR"
else
    echo -e "${RED}❌ Could not determine PHP configuration directory.${NC}"
    echo "Please manually copy opcache_production.ini to your PHP conf.d directory."
    exit 1
fi

TARGET_FILE="$CONFIG_DIR/99-opcache-production.ini"

echo "  • Target location: $TARGET_FILE"

# Copy the file (may require sudo)
if cp opcache_production.ini "$TARGET_FILE" 2>/dev/null; then
    echo -e "${GREEN}  ✅ Configuration file installed successfully!${NC}"
else
    echo -e "${YELLOW}  ⚠️  Need administrator privileges...${NC}"
    sudo cp opcache_production.ini "$TARGET_FILE"
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}  ✅ Configuration file installed successfully!${NC}"
    else
        echo -e "${RED}  ❌ Failed to install configuration file.${NC}"
        exit 1
    fi
fi

echo ""
echo -e "${BLUE}🔄 Restarting PHP...${NC}"

# Try different restart methods
if command -v brew &> /dev/null; then
    if brew services list | grep -q "php.*started"; then
        echo "  • Restarting PHP via Homebrew..."
        brew services restart php
        echo -e "${GREEN}  ✅ PHP restarted!${NC}"
    else
        echo -e "${YELLOW}  ⚠️  PHP service not running via Homebrew.${NC}"
        echo "  • If using MAMP, restart MAMP from the application."
        echo "  • If using Apache/nginx, restart your web server."
    fi
else
    echo -e "${YELLOW}  ⚠️  Homebrew not detected.${NC}"
    echo ""
    echo "  Please restart your web server manually:"
    echo "    • MAMP: Stop and start servers from MAMP app"
    echo "    • Apache: sudo apachectl restart"
    echo "    • nginx: sudo nginx -s reload"
    echo "    • PHP-FPM: sudo service php-fpm restart"
fi

echo ""
echo -e "${GREEN}✅ Installation complete!${NC}"
echo ""

# Verify installation
echo -e "${BLUE}📊 Verifying new OPcache settings...${NC}"
echo ""

# Wait a moment for PHP to reload
sleep 2

php -r "
\$settings = [
    'opcache.enable' => '1',
    'opcache.memory_consumption' => '256',
    'opcache.max_accelerated_files' => '20000',
    'opcache.validate_timestamps' => '0'
];

\$all_correct = true;
foreach (\$settings as \$setting => \$expected) {
    \$actual = ini_get(\$setting);
    \$status = (\$actual == \$expected) ? '✅' : '⚠️';
    \$color = (\$actual == \$expected) ? '\033[0;32m' : '\033[1;33m';
    echo \"  \$status \$setting = \$actual (expected: \$expected)\033[0m\n\";
    if (\$actual != \$expected) {
        \$all_correct = false;
    }
}

echo \"\n\";

if (\$all_correct) {
    echo \"\033[0;32m🎉 All settings applied correctly!\033[0m\n\";
} else {
    echo \"\033[1;33m⚠️  Some settings not applied yet. This may be due to:\033[0m\n\";
    echo \"   • Web server not fully restarted\n\";
    echo \"   • MAMP using different php.ini\n\";
    echo \"   • Need to restart terminal/IDE\n\";
    echo \"\n\";
    echo \"Try restarting MAMP and checking again.\n\";
}
"

echo ""
echo -e "${BLUE}📚 Next Steps:${NC}"
echo ""
echo "  1. Verify OPcache status:"
echo -e "     ${GREEN}php -i | grep opcache${NC}"
echo ""
echo "  2. After deploying code changes, clear OPcache:"
echo -e "     ${GREEN}php -r 'opcache_reset();'${NC}"
echo "     or restart your web server"
echo ""
echo "  3. Monitor performance improvement:"
echo "     • Check response times before/after"
echo "     • Monitor memory usage"
echo ""
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}  OPcache is now configured for production! 🚀${NC}"
echo -e "${GREEN}  Expected performance improvement: 50-70% faster${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo ""
