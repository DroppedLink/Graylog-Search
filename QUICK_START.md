# Quick Start Guide

## First Time Setup

```bash
# 1. Run setup (creates .env and prepares directories)
./setup.sh

# 2. Start WordPress
docker compose up -d

# 3. Wait 30 seconds, then open browser
open http://localhost:8080
```

Complete the WordPress installation wizard in your browser.

## Daily Development

```bash
# Start environment
docker compose up -d

# Stop environment
docker compose down

# View logs
docker compose logs -f wordpress
```

## Create a Plugin

```bash
# Use the helper script (recommended)
./scripts/add-plugin.sh my-plugin
# Plugin appears instantly in WordPress! No restart needed!
```

Or manually create a folder in `plugins/` with a PHP file containing:

```php
<?php
/**
 * Plugin Name: My Plugin
 * Description: My awesome plugin
 * Version: 1.0.0
 * Author: Your Name
 */
```

## Package Plugin

```bash
./scripts/zip-plugin.sh my-plugin
```

Your .zip file will be in `dist/my-plugin.zip`

## Access Points

- **WordPress:** http://localhost:8080
- **phpMyAdmin:** http://localhost:8081
- **Plugin folder in WordPress:** wp-content/plugins/custom-plugins/

## Troubleshooting

**Can't access WordPress?**
```bash
docker compose ps        # Check if running
docker compose logs wordpress  # Check logs
```

**Start fresh?**
```bash
docker compose down -v   # Removes all data
docker compose up -d     # Start fresh
```

**Change ports?**
Edit `.env` file and change `WORDPRESS_PORT` or `PHPMYADMIN_PORT`, then restart.

