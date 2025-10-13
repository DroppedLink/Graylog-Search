# Graylog Search WordPress Plugin

A user-friendly WordPress plugin that allows non-technical users to search Graylog logs via the API.

## Features

- **Simple Search Interface**: Easy-to-use form for searching logs
- **FQDN Search**: Search by hostname/FQDN
- **Additional Search Terms**: Add multiple keywords
- **Filter Out Messages**: Exclude unwanted log entries
- **Time Range Picker**: Search last hour, day, or week
- **Dynamic Results Table**: Beautiful, responsive table with results
- **Color-coded Log Levels**: Visual indicators for ERROR, WARNING, INFO, etc.
- **Secure**: Uses WordPress nonces and capability checks

## Installation

1. Download `graylog-search.zip` from [GitHub Releases](https://github.com/DroppedLink/Graylog-Search/releases)
2. Go to WordPress Admin → Plugins → Add New
3. Click "Upload Plugin"
4. Choose the `graylog-search.zip` file
5. Click "Install Now" and then "Activate"
6. Go to **Graylog Search → Settings**
7. Enter your Graylog API URL (e.g., `https://graylog.example.com:9000`)
8. Enter your Graylog API token
9. Save settings

### Auto-Updates

The plugin includes a built-in GitHub updater. When new versions are released:
- Update notifications appear in WordPress admin
- One-click updates from the Plugins page
- Manual check available in **Graylog Search → Settings**

See [AUTO_UPDATE_GUIDE.md](AUTO_UPDATE_GUIDE.md) for complete details.

## Getting a Graylog API Token

1. Log into your Graylog web interface
2. Go to **System → Users**
3. Click on your username
4. Go to **Edit Tokens** tab
5. Create a new token
6. Copy the token and paste it into the plugin settings

## Usage

### Admin Interface

1. Go to **Graylog Search** in the WordPress admin menu
2. Fill in your search criteria:
   - **FQDN**: Enter the hostname you want to search for
   - **Additional Search Terms**: Add keywords like "error", "warning", etc.
   - **Filter Out**: Exclude terms like "debug" or "info"
   - **Time Range**: Select how far back to search
   - **Result Limit**: Choose how many results to return
3. Click **Search Logs**
4. Results will appear in a nice table below

### Shortcode (Add to Any Page)

Add the search interface to any page or post:

```
[graylog_search]
```

**With custom height:**
```
[graylog_search height="800px"]
```

**Admin-only:**
```
[graylog_search capability="manage_options"]
```

See `SHORTCODE_GUIDE.md` for complete shortcode documentation.

## File Structure

```
graylog-search/
├── graylog-search.php          # Main plugin file
├── includes/
│   ├── settings.php            # Settings page
│   ├── search-page.php         # Search interface
│   └── ajax-handler.php        # API communication
├── assets/
│   ├── css/
│   │   └── style.css          # Plugin styles
│   └── js/
│       └── search.js          # Search functionality
└── README.md                   # This file
```

## Requirements

- WordPress 5.0+
- PHP 7.4+
- Access to Graylog API
- Valid Graylog API token

## Security

- All API requests are authenticated
- User capabilities checked (manage_options)
- Nonces used for AJAX requests
- Input sanitization and output escaping
- XSS protection

## Support

For issues or questions, contact your WordPress administrator.

