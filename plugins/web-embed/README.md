# Web Embed Plugin

A modern WordPress plugin for embedding external URLs into pages and posts using shortcodes with advanced security, caching, and styling options.

## Features

- **Modern Embedding**: Uses object/embed tags instead of iframes for better browser support
- **Security Controls**: Whitelist domains, enforce HTTPS, and control what can be embedded
- **Caching System**: Built-in caching to improve performance and reduce server load
- **Responsive Design**: Automatic responsive embeds with aspect ratio preservation
- **Customizable Styling**: Control dimensions, borders, border-radius, and custom CSS classes
- **Accessibility**: Proper ARIA labels and keyboard navigation support
- **Fallback Support**: Graceful degradation when content cannot be embedded

## Installation

1. Upload the `web-embed` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure settings in Settings → Web Embed

## Quick Start

### Basic Usage

```
[web_embed url="https://example.com"]
```

### With Custom Dimensions

```
[web_embed url="https://example.com" width="800px" height="600px"]
```

### Responsive Embed

```
[web_embed url="https://example.com" responsive="true"]
```

### Full Customization

```
[web_embed url="https://example.com" 
          width="100%" 
          height="600px" 
          responsive="true" 
          border="2px solid #ccc" 
          border_radius="10px"
          class="my-custom-class"
          title="My Embedded Content"
          fallback="Content unavailable"]
```

## Shortcode Builder

**NEW!** Visual shortcode builder with live preview!

Go to **Settings → Web Embed Builder** to:
- Build shortcodes visually with a form interface
- Preview embeds in real-time before using them
- Copy generated shortcodes with one click
- Test URLs against your security settings
- See exactly how your embed will appear

Perfect for creating shortcodes with confidence!

## Configuration

### Security Settings

**Whitelist Mode**: Enable to restrict embedding to approved domains only.

**Allowed Domains**: Add trusted domains (one per line):
```
example.com
trusted-site.org
another-domain.net
```

**HTTPS Only**: Force all embedded URLs to use HTTPS protocol.

### Caching Options

**Enable Caching**: Improve performance by caching embed HTML.

**Cache Duration**: Set how long embeds are cached (in seconds).

**Clear Cache**: Manually clear all cached embeds when needed.

### Advanced Options

- **Default Width/Height**: Set default dimensions for all embeds
- **Responsive by Default**: Enable responsive mode globally
- **Custom CSS Classes**: Add default CSS classes to all embeds

## Shortcode Parameters

| Parameter | Description | Default | Example |
|-----------|-------------|---------|---------|
| `url` | URL to embed (required) | - | `https://example.com` |
| `width` | Width of embed | From settings | `800px` or `100%` |
| `height` | Height of embed | From settings | `600px` or `80vh` |
| `responsive` | Enable responsive mode | From settings | `true` or `false` |
| `border` | CSS border style | `none` | `2px solid #ccc` |
| `border_radius` | CSS border radius | `0` | `10px` |
| `class` | Custom CSS classes | From settings | `my-class another-class` |
| `title` | Accessibility title | `Embedded Content` | `Interactive Map` |
| `loading` | Loading strategy | `lazy` | `lazy` or `eager` |
| `fallback` | Fallback message | Auto-generated | Custom HTML |

## Security Considerations

### X-Frame-Options

Some websites prevent embedding using the `X-Frame-Options` header. These sites will display your fallback message instead. This is a security feature of those websites and cannot be bypassed.

### CORS (Cross-Origin Resource Sharing)

Modern browsers implement CORS policies. If a website doesn't allow cross-origin embedding, the embed may not display correctly.

### Content Security Policy

Your WordPress site's CSP headers should allow embedding external content. Add appropriate `frame-src` or `object-src` directives if needed.

## Troubleshooting

### Embed Not Displaying

1. **Check URL**: Ensure the URL is valid and accessible
2. **Check Whitelist**: If whitelist mode is enabled, verify the domain is allowed
3. **HTTPS Requirement**: Check if HTTPS-only mode is blocking HTTP URLs
4. **X-Frame-Options**: The target site may prevent embedding
5. **Clear Cache**: Try clearing the plugin cache

### Performance Issues

1. **Enable Caching**: Turn on caching in settings
2. **Increase Cache Duration**: Set a longer cache duration for static content
3. **Use Lazy Loading**: Set `loading="lazy"` in shortcode

### Responsive Issues

1. **Enable Responsive Mode**: Add `responsive="true"` to shortcode
2. **Check Container Width**: Ensure parent elements don't restrict width
3. **Test Different Aspect Ratios**: Adjust height parameter for better fit

## Examples

### Embed Google Maps

```
[web_embed url="https://www.google.com/maps/embed?..." 
          width="100%" 
          height="450px" 
          responsive="true"
          title="Google Maps Location"]
```

### Embed External Dashboard

```
[web_embed url="https://dashboard.example.com" 
          width="100%" 
          height="800px" 
          border="1px solid #ddd"
          border_radius="8px"]
```

### Embed with Custom Fallback

```
[web_embed url="https://external-tool.com" 
          fallback="<p>This tool requires direct access. <a href='https://external-tool.com'>Click here to open</a></p>"]
```

## Development

### File Structure

```
web-embed/
├── web-embed.php           # Main plugin file
├── includes/
│   ├── settings.php        # Admin settings
│   ├── shortcode.php       # Shortcode handler
│   ├── cache-handler.php   # Caching functions
│   └── security.php        # Security validation
├── assets/
│   ├── css/style.css       # Plugin styles
│   └── js/embed.js         # Plugin JavaScript
├── README.md               # This file
└── USAGE_GUIDE.md         # Detailed usage guide
```

### WordPress Standards

This plugin follows WordPress coding standards and best practices:
- Proper sanitization and validation
- Nonce verification for forms
- Capability checks for admin functions
- Transient API for caching
- Localization ready (i18n)

## Support

For detailed usage examples and advanced configuration, see [USAGE_GUIDE.md](USAGE_GUIDE.md).

## Version

Current version: 1.0.0

## License

This plugin is provided as-is for use in WordPress installations.

