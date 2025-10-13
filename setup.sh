#!/bin/bash

# WordPress Plugin Development Environment Setup Script

set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Setting up WordPress Plugin Development Environment...${NC}"
echo ""

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    echo "Creating .env file..."
    cat > .env << 'EOF'
# WordPress Development Environment Configuration

# Port Configuration
WORDPRESS_PORT=8080
PHPMYADMIN_PORT=8081

# Database Configuration
MYSQL_ROOT_PASSWORD=rootpassword
MYSQL_DATABASE=wordpress
MYSQL_USER=wordpress
MYSQL_PASSWORD=wordpress

# WordPress Configuration
WORDPRESS_DB_HOST=db:3306
WORDPRESS_DB_USER=wordpress
WORDPRESS_DB_PASSWORD=wordpress
WORDPRESS_DB_NAME=wordpress
WORDPRESS_DEBUG=1
EOF
    echo -e "${GREEN}✓ Created .env file${NC}"
else
    echo ".env file already exists, skipping..."
fi

# Create dist directory for plugin packages
mkdir -p dist
echo -e "${GREEN}✓ Created dist directory${NC}"

# Make scripts executable
chmod +x scripts/zip-plugin.sh
echo -e "${GREEN}✓ Made scripts executable${NC}"

echo ""
echo -e "${GREEN}Setup complete!${NC}"
echo ""
echo "Next steps:"
echo "1. Start the environment: docker compose up -d"
echo "2. Wait 30 seconds for WordPress to initialize"
echo "3. Open http://localhost:8080 in your browser"
echo "4. Complete the WordPress installation wizard"
echo ""
echo "Happy plugin development!"

