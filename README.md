# WordPress Plugin Development Environment

A Docker-based WordPress development environment for creating and testing WordPress plugins locally.

## Quick Start

### Prerequisites

- Docker Desktop installed on your Mac
- Basic knowledge of WordPress plugin development

### Setup

1. **Run the setup script (first time only):**
   ```bash
   ./setup.sh
   ```
   This creates the `.env` configuration file and prepares directories.

2. **Start the environment:**
   ```bash
   docker compose up -d
   ```

3. **Wait 30 seconds** for WordPress and MySQL to initialize (first time only)

4. **Access WordPress:**
   - WordPress Site: http://localhost:8080
   - phpMyAdmin: http://localhost:8081
   
5. **Complete WordPress installation:**
   - Open http://localhost:8080 in your browser
   - Follow the WordPress installation wizard
   - Choose your site title, username, and password

6. **Start developing:**
   - Your plugins go in the `plugins/` directory
   - Changes are instantly reflected in WordPress
   - Navigate to Plugins → Installed Plugins → Custom Plugins folder

### Stop the environment

```bash
docker compose down
```

### Stop and remove all data (fresh start)

```bash
docker compose down -v
```

## Plugin Development Workflow

### 1. Create a New Plugin

**Easy Way (Recommended):**

```bash
./scripts/add-plugin.sh my-awesome-plugin
```

This script will:
- Create the plugin directory
- Generate the main plugin file with proper headers
- The plugin appears **instantly** in WordPress (no restart needed!)

**Manual Way:**

Create a new folder in the `plugins/` directory:

```bash
mkdir plugins/my-awesome-plugin
```

Create the main plugin file with the required header:

```php
<?php
/**
 * Plugin Name: My Awesome Plugin
 * Plugin URI: https://example.com/my-awesome-plugin
 * Description: Description of what your plugin does
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: my-awesome-plugin
 */

// Your plugin code here
```

The plugin will appear **instantly** in WordPress - just refresh the Plugins page!

### 2. Develop Your Plugin

- Edit files directly in `plugins/your-plugin-name/`
- Changes appear **immediately** in WordPress (just refresh your browser)
- New plugins appear **instantly** (no restart needed!)
- Check WordPress Admin → Plugins → Installed Plugins to activate your plugin

### 3. Package Your Plugin

When ready to create a .zip file for distribution:

```bash
./scripts/zip-plugin.sh my-awesome-plugin
```

The .zip file will be created in the `dist/` directory and is ready to upload to any WordPress site.

## Directory Structure

```
wordpress/
├── docker-compose.yml          # Docker services configuration
├── .env                        # Environment variables (ports, passwords)
├── .gitignore                  # Git ignore rules
├── README.md                   # This file
├── plugins/                    # Your custom plugins
│   ├── example-plugin/         # Example plugin template
│   │   ├── example-plugin.php  # Main plugin file
│   │   └── readme.txt          # Plugin readme
│   ├── graylog-search/         # Graylog log search interface
│   └── web-embed/              # URL embedding with security & caching
├── scripts/                    # Utility scripts
│   ├── add-plugin.sh          # Create and register a new plugin
│   └── zip-plugin.sh          # Package plugin as .zip
└── dist/                       # Generated .zip files (created automatically)
```

## Configuration

All configuration is in the `.env` file:

- **WORDPRESS_PORT**: WordPress site port (default: 8080)
- **PHPMYADMIN_PORT**: phpMyAdmin port (default: 8081)
- **MYSQL_ROOT_PASSWORD**: MySQL root password
- **MYSQL_DATABASE**: WordPress database name
- **MYSQL_USER**: WordPress database user
- **MYSQL_PASSWORD**: WordPress database password

You can change any of these values and restart the containers:

```bash
docker compose down
docker compose up -d
```

## Common Commands

### View logs
```bash
docker compose logs -f wordpress
```

### Access WordPress container shell
```bash
docker compose exec wordpress bash
```

### Access MySQL database
```bash
docker compose exec db mysql -u wordpress -pwordpress wordpress
```

### Restart services
```bash
docker compose restart
```

## Available Plugins

### Web Embed
A modern plugin for embedding external URLs into pages using shortcodes with object/embed tags.

**Features:**
- Modern object/embed tag rendering (better than iframes)
- Security controls: whitelist domains, enforce HTTPS
- Built-in caching for performance
- Responsive design support
- Customizable styling (borders, dimensions, CSS classes)
- Fallback support for blocked content

**Usage:**
```
[web_embed url="https://example.com" width="100%" height="600px" responsive="true"]
```

See `plugins/web-embed/README.md` for full documentation.

### Graylog Search
Simple interface for non-technical users to search Graylog logs via API.

**Package a plugin:**
```bash
./scripts/zip-plugin.sh web-embed
# or
./scripts/zip-plugin.sh graylog-search
```

## Plugin Development Tips

### WordPress Plugin File Structure

A typical plugin structure:

```
my-plugin/
├── my-plugin.php           # Main plugin file (required)
├── readme.txt              # Plugin readme for WordPress.org
├── uninstall.php           # Cleanup code when plugin is deleted
├── includes/               # PHP classes and functions
│   ├── class-main.php
│   └── functions.php
├── admin/                  # Admin-specific functionality
│   ├── class-admin.php
│   └── css/
├── public/                 # Public-facing functionality
│   ├── class-public.php
│   ├── css/
│   └── js/
└── languages/              # Translation files
```

### Useful WordPress Hooks

```php
// Run on plugin activation
register_activation_hook(__FILE__, 'my_plugin_activate');

// Run on plugin deactivation
register_deactivation_hook(__FILE__, 'my_plugin_deactivate');

// Initialize your plugin
add_action('init', 'my_plugin_init');

// Add admin menu
add_action('admin_menu', 'my_plugin_menu');

// Enqueue scripts and styles
add_action('wp_enqueue_scripts', 'my_plugin_enqueue_scripts');
```

### Debugging

WordPress debugging is enabled by default. Check the WordPress debug log:

```bash
docker compose exec wordpress tail -f /var/www/html/wp-content/debug.log
```

## Troubleshooting

### Port already in use

If port 8080 or 8081 is already in use, edit `.env` and change the ports:

```
WORDPRESS_PORT=8090
PHPMYADMIN_PORT=8091
```

Then restart: `docker compose down && docker compose up -d`

### Plugin not showing in WordPress

1. Make sure your plugin folder is in `plugins/`
2. Check that your main PHP file has the proper plugin header
3. Look in WordPress Admin → Plugins → Custom Plugins folder
4. Check file permissions: `chmod -R 755 plugins/your-plugin`

### Cannot access WordPress

1. Check if containers are running: `docker compose ps`
2. Check logs: `docker compose logs wordpress`
3. Wait a minute after first start (MySQL needs time to initialize)

### Reset everything

To start completely fresh:

```bash
docker compose down -v
docker compose up -d
```

This removes all data including the WordPress installation and database.

## Resources

- [WordPress Plugin Developer Handbook](https://developer.wordpress.org/plugins/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- [Plugin Handbook: Headers](https://developer.wordpress.org/plugins/plugin-basics/header-requirements/)
- [WordPress Hook Reference](https://developer.wordpress.org/reference/hooks/)

## License

This development environment setup is provided as-is for plugin development purposes.

