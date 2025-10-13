#!/bin/bash

# Production Deployment Script for Know My Patient
# This script prepares files for production deployment

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}╔══════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║                                                              ║${NC}"
echo -e "${BLUE}║     Know My Patient - Production Deployment Preparation      ║${NC}"
echo -e "${BLUE}║                                                              ║${NC}"
echo -e "${BLUE}╚══════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Configuration
BUILD_DIR="./build-production"
EXCLUDES=(
    "config"
    "tests"
    "docs"
    ".git"
    ".github"
    "database_migrations"
    "DEV_TOOLS_GUIDE.md"
    ".phpunit.result.cache"
    ".DS_Store"
    "build-production"
    "node_modules"
)

# Step 1: Clean previous build
echo -e "${YELLOW}[1/6]${NC} Cleaning previous build directory..."
if [ -d "$BUILD_DIR" ]; then
    rm -rf "$BUILD_DIR"
    echo -e "${GREEN}✓${NC} Previous build cleaned"
else
    echo -e "${GREEN}✓${NC} No previous build found"
fi

# Step 2: Create build directory
echo -e "${YELLOW}[2/6]${NC} Creating build directory..."
mkdir -p "$BUILD_DIR"
echo -e "${GREEN}✓${NC} Build directory created: $BUILD_DIR"

# Step 3: Copy files (excluding dev files)
echo -e "${YELLOW}[3/6]${NC} Copying production files..."

# Build rsync exclude arguments
EXCLUDE_ARGS=""
for exclude in "${EXCLUDES[@]}"; do
    EXCLUDE_ARGS="$EXCLUDE_ARGS --exclude='$exclude'"
done

# Copy files
eval "rsync -av $EXCLUDE_ARGS --exclude='vendor/' ./ $BUILD_DIR/"
echo -e "${GREEN}✓${NC} Production files copied"

# Step 4: Install production dependencies
echo -e "${YELLOW}[4/6]${NC} Installing production dependencies (no dev packages)..."
cd "$BUILD_DIR"
composer install --no-dev --optimize-autoloader --no-interaction
cd ..
echo -e "${GREEN}✓${NC} Production dependencies installed"

# Step 5: Create production environment template
echo -e "${YELLOW}[5/6]${NC} Setting up environment configuration..."
if [ -f "$BUILD_DIR/.env.example" ]; then
    cp "$BUILD_DIR/.env.example" "$BUILD_DIR/.env"
    echo -e "${YELLOW}⚠${NC}  Remember to configure $BUILD_DIR/.env with production values!"
else
    echo -e "${RED}✗${NC} .env.example not found!"
fi

# Step 6: Set recommended permissions
echo -e "${YELLOW}[6/6]${NC} Setting file permissions..."
chmod -R 755 "$BUILD_DIR/public"
chmod -R 777 "$BUILD_DIR/var/cache" 2>/dev/null || mkdir -p "$BUILD_DIR/var/cache" && chmod -R 777 "$BUILD_DIR/var/cache"
chmod -R 777 "$BUILD_DIR/logs" 2>/dev/null || mkdir -p "$BUILD_DIR/logs" && chmod -R 777 "$BUILD_DIR/logs"
echo -e "${GREEN}✓${NC} Permissions set"

# Summary
echo ""
echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}✓ Production build ready in: ${YELLOW}$BUILD_DIR${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
echo ""

# File counts
TOTAL_FILES=$(find "$BUILD_DIR" -type f | wc -l | tr -d ' ')
echo -e "${BLUE}📊 Build Statistics:${NC}"
echo -e "   Total files: ${GREEN}$TOTAL_FILES${NC}"
echo -e "   Size: ${GREEN}$(du -sh "$BUILD_DIR" | cut -f1)${NC}"
echo ""

# Important files check
echo -e "${BLUE}✅ Critical Files Check:${NC}"
[ -f "$BUILD_DIR/composer.json" ] && echo -e "   ${GREEN}✓${NC} composer.json" || echo -e "   ${RED}✗${NC} composer.json"
[ -f "$BUILD_DIR/composer.lock" ] && echo -e "   ${GREEN}✓${NC} composer.lock" || echo -e "   ${RED}✗${NC} composer.lock"
[ -f "$BUILD_DIR/public/index.php" ] && echo -e "   ${GREEN}✓${NC} public/index.php" || echo -e "   ${RED}✗${NC} public/index.php"
[ -d "$BUILD_DIR/src" ] && echo -e "   ${GREEN}✓${NC} src/" || echo -e "   ${RED}✗${NC} src/"
[ -d "$BUILD_DIR/vendor" ] && echo -e "   ${GREEN}✓${NC} vendor/" || echo -e "   ${RED}✗${NC} vendor/"
[ -f "$BUILD_DIR/.env" ] && echo -e "   ${GREEN}✓${NC} .env" || echo -e "   ${RED}✗${NC} .env"
echo ""

# Excluded files check
echo -e "${BLUE}🚫 Excluded Dev Files (should not exist):${NC}"
[ ! -d "$BUILD_DIR/tests" ] && echo -e "   ${GREEN}✓${NC} tests/ excluded" || echo -e "   ${YELLOW}⚠${NC} tests/ still present"
[ ! -d "$BUILD_DIR/config" ] && echo -e "   ${GREEN}✓${NC} config/ excluded" || echo -e "   ${YELLOW}⚠${NC} config/ still present"
[ ! -d "$BUILD_DIR/docs" ] && echo -e "   ${GREEN}✓${NC} docs/ excluded" || echo -e "   ${YELLOW}⚠${NC} docs/ still present"
echo ""

# Next steps
echo -e "${BLUE}📋 Next Steps:${NC}"
echo -e "   1. Edit ${YELLOW}$BUILD_DIR/.env${NC} with production values"
echo -e "   2. Test the build locally:"
echo -e "      ${YELLOW}cd $BUILD_DIR && php -S localhost:8080 -t public${NC}"
echo -e "   3. Deploy to production server:"
echo -e "      ${YELLOW}rsync -avz $BUILD_DIR/ user@server:/var/www/your-app/${NC}"
echo -e "   4. On server, verify .env and set proper permissions"
echo ""

echo -e "${GREEN}✅ Production build complete!${NC}"
