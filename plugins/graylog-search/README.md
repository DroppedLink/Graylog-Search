# Graylog Search WordPress Plugin

Simple interface for non-technical users to search Graylog logs via API.

## Features

- **Simple Search Mode** - Enter search terms, plugin searches all fields automatically
- **Advanced Mode** - Full Lucene query syntax support
- **Query Builder** - Visual query building (Beta)
- **Multi-line Input** - Newline or comma-separated search terms
- **Search History** - Track and re-run previous searches
- **Saved Searches** - Save frequently used searches
- **DNS Lookup** - Click IP addresses to resolve hostnames
- **Timezone Support** - Convert timestamps to your timezone
- **Export** - CSV, JSON, TXT, PDF formats
- **Interactive Filtering** - Click to filter or highlight text
- **GitHub Updates** - Automatic updates via GitHub Updater plugin

## Installation

### Via WordPress Admin

1. Download `graylog-search-X.X.X.zip` from [Releases](https://github.com/DroppedLink/Graylog-Search/releases)
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Choose the downloaded zip file
4. Click Install Now and Activate

### Via GitHub Updater

Install [GitHub Updater](https://github.com/afragen/github-updater) plugin and this plugin will auto-update from GitHub releases.

## Configuration

1. Go to **Graylog Search → Settings**
2. Enter your Graylog API URL (e.g., `https://graylog.example.com:9000`)
3. Enter your Graylog API Token
4. Click **Test Connection** to verify
5. Save Settings

## Usage

### Admin Interface

Go to **Graylog Search** in WordPress admin menu to search logs.

### Shortcode

Add search interface to any page or post:

```
[graylog_search]
```

Optional attributes:
```
[graylog_search height="800px"]
[graylog_search capability="edit_posts"]
```

## Search Examples

**Simple Mode:**
```
error
warning
192.168.1.1
```

**Advanced Mode:**
```
level:ERROR AND source:apache
message:"connection refused" AND timestamp:[2024-01-01 TO 2024-01-31]
```

## Requirements

- WordPress 5.0+
- PHP 7.2+
- Graylog 6.0+ (tested with 6.1)

## Support

- [GitHub Issues](https://github.com/DroppedLink/Graylog-Search/issues)
- [Changelog](CHANGELOG.md)

## License

GPL v2 or later

