# Web Embed - Professional URL Embedding for WordPress

A professional WordPress plugin for embedding external URLs using modern object/embed tags with visual builder, security controls, caching, and smart fallback handling.

## Features

- **Visual Shortcode Builder** - Create embeds with live preview, no coding required
- **Security Controls** - Domain whitelist, HTTPS-only mode, URL validation
- **Smart Caching** - WordPress transients-based caching for better performance
- **Responsive Design** - Automatic responsive embeds with aspect ratio control
- **Professional Fallbacks** - Beautiful fallback templates when content can't be embedded
- **Enterprise-Ready** - Designed for embedding internal tools and dashboards
- **Clean Admin Interface** - Top-level menu with tabs for easy navigation
- **Internationalization** - Fully translatable with text domain support

## Installation

1. Download the plugin zip file
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Choose the zip file and click "Install Now"
4. Activate the plugin
5. Go to **Web Embed** in your admin menu to start creating embeds

## Quick Start

### Using the Builder (Recommended)

1. Go to **Web Embed → Builder** in your WordPress admin
2. Enter the URL you want to embed
3. Configure dimensions, styling, and options
4. Click "Generate and Preview"
5. Copy the generated shortcode
6. Paste into any post or page

### Manual Shortcode

```
[web_embed url="https://example.com" width="100%" height="600px" responsive="true"]
```

## Basic Usage

### Simple Embed
```
[web_embed url="https://dashboard.company.com"]
```

### With Custom Dimensions
```
[web_embed url="https://grafana.company.com/dashboard" width="100%" height="800px"]
```

### Responsive with Styling
```
[web_embed url="https://maps.google.com/embed?..." responsive="true" border="2px solid #ccc" border_radius="10px"]
```

## Shortcode Parameters

| Parameter | Description | Default | Example |
|-----------|-------------|---------|---------|
| `url` | URL to embed (required) | - | `https://example.com` |
| `width` | Width of embed | `100%` | `800px`, `100%` |
| `height` | Height of embed | `600px` | `600px`, `50vh` |
| `responsive` | Make responsive | `true` | `true`, `false` |
| `border` | CSS border | `none` | `2px solid #ccc` |
| `border_radius` | Rounded corners | `0` | `10px` |
| `title` | Accessibility title | `Embedded Content` | `Dashboard` |
| `loading` | Lazy loading | `lazy` | `lazy`, `eager` |
| `class` | Custom CSS class | - | `my-embed` |
| `fallback` | Custom fallback HTML | - | `<p>Link...</p>` |

## Configuration

### Settings Page

Go to **Web Embed → Settings** to configure:

#### Security Settings
- **Domain Whitelist** - Restrict embeds to approved domains
- **Allowed Domains** - List of domains (one per line, supports `*.domain.com` wildcards)
- **HTTPS Only** - Require HTTPS URLs for security

#### Cache Settings
- **Cache Duration** - How long to cache embed HTML (default: 3600 seconds)
- **Clear Cache** - Button to clear all cached embeds

#### Default Shortcode Settings
- **Default Width** - Default width for new embeds
- **Default Height** - Default height for new embeds
- **Default Responsive** - Make embeds responsive by default
- **Custom CSS Class** - Add class to all embeds

## Common Use Cases

### Internal Dashboards
```
[web_embed url="https://grafana.company.com/d/dashboard-id" width="100%" height="800px" title="Grafana Dashboard"]
```

### Google Maps
```
[web_embed url="https://www.google.com/maps/embed?pb=..." responsive="true" height="450px" title="Office Location"]
```

### YouTube Videos
```
[web_embed url="https://www.youtube.com/embed/VIDEO_ID" responsive="true" title="Tutorial Video"]
```

### Enterprise Apps
```
[web_embed url="https://admin.company.com/reports" width="100%" height="900px" border="1px solid #ddd"]
```

## Troubleshooting

### Empty Preview / Blank Embed

**Cause:** The target site is blocking embedding using X-Frame-Options or Content-Security-Policy headers.

**Solutions:**
- For external sites (Google, Facebook, etc.): Use their embed-specific URLs
- For internal apps: Configure your application to allow embedding (see ENTERPRISE_APPS_GUIDE.md)
- The plugin will display a professional fallback message with a link to open in new tab

### URL Blocked by Whitelist

**Cause:** Domain whitelist is enabled and the URL's domain is not in the allowed list.

**Solution:** Go to Settings and add the domain to the "Allowed Domains" list

### HTTPS Error

**Cause:** HTTPS-only mode is enabled and you're trying to embed an HTTP URL.

**Solution:** 
- Use HTTPS version of the URL if available
- Disable "HTTPS Only" in settings if you trust the HTTP source

## Documentation

- [QUICK_START.md](QUICK_START.md) - Quick start guide for new users
- [USAGE_GUIDE.md](USAGE_GUIDE.md) - Detailed usage guide with examples
- [ENTERPRISE_APPS_GUIDE.md](ENTERPRISE_APPS_GUIDE.md) - Configure enterprise apps for embedding
- [EMBEDDING_GUIDE.md](EMBEDDING_GUIDE.md) - What works and why
- [FALLBACK_TEMPLATES.md](FALLBACK_TEMPLATES.md) - Custom fallback templates

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Modern browser for admin interface

## Support

For support:
1. Check the included documentation files
2. Review the FAQ in readme.txt
3. Visit the plugin support forum on WordPress.org

## Contributing

Contributions are welcome! This is an open-source project.

## License

GPL v2 or later. See LICENSE file for details.

## Credits

Built with care for WordPress users who need professional embedding solutions for enterprise applications, dashboards, and internal tools.

