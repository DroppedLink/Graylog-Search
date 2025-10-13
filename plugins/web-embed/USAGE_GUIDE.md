# Web Embed Plugin - Usage Guide

Complete guide with examples and best practices for using the Web Embed plugin.

## Table of Contents

1. [Basic Usage](#basic-usage)
2. [Responsive Embeds](#responsive-embeds)
3. [Styling Options](#styling-options)
4. [Security Configuration](#security-configuration)
5. [Performance Optimization](#performance-optimization)
6. [Common Use Cases](#common-use-cases)
7. [Advanced Techniques](#advanced-techniques)

## Basic Usage

### Simple Embed

The most basic usage requires only a URL:

```
[web_embed url="https://example.com"]
```

This will create an embed using your default settings (configured in Settings → Web Embed).

### Specifying Dimensions

Control the size of your embed:

```
[web_embed url="https://example.com" width="800px" height="600px"]
```

You can use any valid CSS units:
- Pixels: `800px`
- Percentages: `100%`
- Viewport units: `80vw`, `90vh`
- Other CSS units: `30em`, `50rem`

## Responsive Embeds

### Enable Responsive Mode

Make your embed automatically adjust to container width:

```
[web_embed url="https://example.com" responsive="true"]
```

The embed will maintain a 4:3 aspect ratio by default.

### Responsive Full Width

Perfect for mobile-friendly pages:

```
[web_embed url="https://example.com" width="100%" responsive="true"]
```

### Disable Responsive Mode

If you need fixed dimensions:

```
[web_embed url="https://example.com" width="800px" height="600px" responsive="false"]
```

## Styling Options

### Adding Borders

Create a bordered embed:

```
[web_embed url="https://example.com" border="2px solid #cccccc"]
```

Common border styles:
- Solid: `1px solid #000`
- Dashed: `2px dashed #666`
- Dotted: `1px dotted #999`
- Multiple: `2px solid #ccc`

### Rounded Corners

Add border radius for modern look:

```
[web_embed url="https://example.com" border_radius="10px"]
```

Examples:
- Subtle rounding: `border_radius="5px"`
- Heavy rounding: `border_radius="20px"`
- Circular (if square): `border_radius="50%"`

### Combined Styling

Use multiple style options together:

```
[web_embed url="https://example.com" 
          width="100%" 
          height="600px"
          border="1px solid #e0e0e0"
          border_radius="8px"
          responsive="true"]
```

### Custom CSS Classes

Add your own CSS classes for advanced styling:

```
[web_embed url="https://example.com" class="my-embed-style shadow-large"]
```

Then in your theme's CSS:

```css
.my-embed-style {
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    margin: 30px 0;
}

.shadow-large {
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}
```

## Security Configuration

### Whitelist Mode

Restrict embeds to trusted domains:

1. Go to Settings → Web Embed
2. Enable "Whitelist Mode"
3. Add trusted domains (one per line):

```
example.com
trusted-site.org
maps.google.com
youtube.com
```

### HTTPS Only Mode

Force secure connections:

1. Enable "HTTPS Only" in settings
2. All HTTP URLs will be rejected
3. Users see error message: "Only HTTPS URLs are allowed"

### Per-Domain Configuration

You can use whitelist mode strategically:

**Allow all Google services:**
```
google.com
```

This automatically allows:
- maps.google.com
- docs.google.com
- drive.google.com
- etc.

**Specific subdomain only:**
```
maps.google.com
```

This allows only Google Maps, not other Google services.

## Performance Optimization

### Enable Caching

In Settings → Web Embed:
1. Enable "Enable Caching"
2. Set cache duration (e.g., 3600 = 1 hour)

### Lazy Loading

Defer loading until embed is visible:

```
[web_embed url="https://example.com" loading="lazy"]
```

This is enabled by default and improves page load times.

### Eager Loading

For above-the-fold content:

```
[web_embed url="https://example.com" loading="eager"]
```

Use this for embeds that should load immediately.

### Clear Cache

When content updates frequently:
1. Go to Settings → Web Embed
2. Click "Clear All Cache"

Or clear cache for a specific URL by updating the shortcode parameters.

## Common Use Cases

### Google Maps

```
[web_embed url="https://www.google.com/maps/embed?pb=..." 
          width="100%" 
          height="450px"
          responsive="true"
          border="1px solid #ddd"
          title="Office Location"]
```

### External Dashboard

```
[web_embed url="https://dashboard.example.com" 
          width="100%" 
          height="800px"
          border="none"
          class="dashboard-embed"]
```

### Monitoring Tools

```
[web_embed url="https://grafana.example.com/d/dashboard" 
          width="100%" 
          height="600px"
          responsive="true"
          title="System Metrics"]
```

### Documentation Sites

```
[web_embed url="https://docs.example.com/page" 
          width="100%" 
          height="700px"
          border="2px solid #e8e8e8"
          border_radius="6px"]
```

### External Tools

```
[web_embed url="https://tool.example.com" 
          width="800px" 
          height="600px"
          responsive="false"
          fallback="<p>Please <a href='https://tool.example.com'>click here</a> to access the tool directly.</p>"]
```

## Advanced Techniques

### Custom Fallback Messages

Provide helpful fallback content when embedding fails:

```
[web_embed url="https://example.com" 
          fallback="<div class='custom-fallback'><h3>Content Not Available</h3><p>This content cannot be embedded. <a href='https://example.com' target='_blank'>Visit the site directly</a></p></div>"]
```

### Accessibility Features

Improve accessibility with descriptive titles:

```
[web_embed url="https://example.com" 
          title="Interactive Sales Dashboard"
          class="accessible-embed"]
```

Screen readers will announce the title to users.

### Multiple Embeds on One Page

Use different styles for different embeds:

```
[web_embed url="https://site1.com" class="embed-primary" width="100%" height="500px"]

[web_embed url="https://site2.com" class="embed-secondary" width="100%" height="400px"]
```

Then style them differently:

```css
.embed-primary {
    border: 3px solid #0066cc;
    margin-bottom: 40px;
}

.embed-secondary {
    border: 1px solid #cccccc;
    opacity: 0.9;
}
```

### Conditional Embeds

Use WordPress conditionals to show different embeds:

```php
<?php if (is_user_logged_in()): ?>
    [web_embed url="https://internal-dashboard.com" width="100%" height="700px"]
<?php else: ?>
    <p>Please log in to view the dashboard.</p>
<?php endif; ?>
```

### Dynamic URLs

Generate embed URLs programmatically:

```php
<?php 
$report_id = get_query_var('report');
$embed_url = "https://reports.example.com/{$report_id}";
echo do_shortcode("[web_embed url='{$embed_url}' width='100%' height='600px']");
?>
```

### Responsive Breakpoints

Use CSS to adjust embeds at different screen sizes:

```css
/* Desktop */
.web-embed-container {
    height: 800px;
}

/* Tablet */
@media (max-width: 768px) {
    .web-embed-container {
        height: 600px;
    }
}

/* Mobile */
@media (max-width: 480px) {
    .web-embed-container {
        height: 400px;
    }
}
```

## Troubleshooting Tips

### Embed Shows Fallback Instead of Content

**Possible causes:**
1. Target site blocks embedding (X-Frame-Options)
2. CORS policy prevents loading
3. URL is incorrect or inaccessible
4. Whitelist is blocking the domain

**Solutions:**
- Test URL directly in browser
- Check browser console for errors
- Verify whitelist settings
- Contact target site about embedding permissions

### Embed is Too Small/Large

**Solutions:**
- Adjust `width` and `height` parameters
- Enable/disable `responsive` mode
- Check parent container CSS
- Use viewport units: `width="90vw" height="80vh"`

### Embed Not Loading on Mobile

**Solutions:**
- Enable responsive mode: `responsive="true"`
- Use percentage width: `width="100%"`
- Test with eager loading: `loading="eager"`
- Check mobile viewport meta tag in theme

### Performance Issues

**Solutions:**
- Enable caching in settings
- Use lazy loading (default)
- Increase cache duration
- Limit number of embeds per page

## Best Practices

1. **Always use HTTPS** for embedded URLs when possible
2. **Enable whitelist mode** for public-facing sites
3. **Use responsive mode** for mobile compatibility
4. **Provide fallback messages** for better user experience
5. **Enable caching** for frequently accessed embeds
6. **Test on multiple devices** before publishing
7. **Use descriptive titles** for accessibility
8. **Monitor performance** with multiple embeds
9. **Keep allowed domains list** up to date
10. **Clear cache** when updating embedded content

## Getting Help

If you encounter issues:

1. Check Settings → Web Embed configuration
2. Review this usage guide
3. Test URL directly in browser
4. Check browser console for errors
5. Try disabling whitelist mode temporarily
6. Clear plugin cache
7. Test with minimal shortcode parameters

For security questions, refer to the [README.md](README.md) security section.

