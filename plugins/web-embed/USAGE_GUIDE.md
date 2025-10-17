# Usage Guide

Comprehensive guide to using Web Embed effectively.

## Table of Contents

1. [Shortcode Basics](#shortcode-basics)
2. [All Parameters](#all-parameters)
3. [Common Examples](#common-examples)
4. [Advanced Usage](#advanced-usage)
5. [Security Best Practices](#security-best-practices)
6. [Performance Tips](#performance-tips)

## Shortcode Basics

The basic syntax is:

```
[web_embed url="https://example.com"]
```

You can add any combination of parameters:

```
[web_embed url="https://example.com" width="100%" height="600px" responsive="true"]
```

## All Parameters

### Required Parameters

**url** (string) - The URL to embed
```
url="https://dashboard.company.com"
```

### Dimension Parameters

**width** (string) - Width of the embed
```
width="100%"     (responsive, default)
width="800px"    (fixed width)
width="80vw"     (viewport width)
```

**height** (string) - Height of the embed
```
height="600px"   (default)
height="50vh"    (viewport height)
height="400px"   (fixed height)
```

**responsive** (boolean) - Make embed responsive
```
responsive="true"    (default, recommended)
responsive="false"   (use fixed dimensions)
```

### Styling Parameters

**border** (string) - CSS border
```
border="none"              (default, no border)
border="1px solid #ccc"    (light gray border)
border="2px solid #0073aa" (blue border)
border="3px dashed #999"   (dashed border)
```

**border_radius** (string) - Rounded corners
```
border_radius="0"      (default, square corners)
border_radius="5px"    (slightly rounded)
border_radius="15px"   (very rounded)
```

**class** (string) - Custom CSS class
```
class="my-custom-embed"
```

### Accessibility Parameters

**title** (string) - Accessible title for screen readers
```
title="Embedded Content"    (default)
title="Sales Dashboard"
title="Company Map"
```

### Performance Parameters

**loading** (string) - Lazy loading
```
loading="lazy"    (default, load when visible)
loading="eager"   (load immediately)
```

### Fallback Parameters

**fallback** (HTML) - Custom fallback content
```
fallback="<p>This content cannot be displayed. <a href='URL'>Open directly</a></p>"
```

## Common Examples

### Responsive Dashboard

Full-width, responsive dashboard embed:

```
[web_embed url="https://grafana.company.com/dashboard" responsive="true" height="800px" title="Sales Dashboard"]
```

### Styled Map

Google Maps with border and rounded corners:

```
[web_embed url="https://www.google.com/maps/embed?pb=..." height="450px" border="2px solid #ddd" border_radius="10px" title="Office Location"]
```

### Fixed Size Report

Fixed dimensions for a report:

```
[web_embed url="https://reports.company.com/monthly" width="1000px" height="700px" responsive="false" title="Monthly Report"]
```

### Grid Layout

Multiple embeds in a grid (use custom CSS):

```
<div class="embed-grid">
    [web_embed url="https://app1.company.com" height="400px"]
    [web_embed url="https://app2.company.com" height="400px"]
    [web_embed url="https://app3.company.com" height="400px"]
    [web_embed url="https://app4.company.com" height="400px"]
</div>
```

Add to your theme's CSS:
```css
.embed-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

@media (max-width: 768px) {
    .embed-grid {
        grid-template-columns: 1fr;
    }
}
```

### Custom Fallback

Provide custom fallback message:

```
[web_embed url="https://secure.company.com/admin" fallback="<div style='padding: 40px; text-align: center;'><h3>Admin Portal</h3><p>This portal requires direct access.</p><a href='https://secure.company.com/admin' class='button'>Launch Admin Portal</a></div>"]
```

## Advanced Usage

### Conditional Embeds

Show different embeds based on user role:

```php
<?php if (current_user_can('manage_options')): ?>
    [web_embed url="https://admin.company.com/dashboard"]
<?php else: ?>
    [web_embed url="https://company.com/user-dashboard"]
<?php endif; ?>
```

### Dynamic URLs

Use WordPress to generate dynamic URLs:

```php
<?php
$user_id = get_current_user_id();
$dashboard_url = "https://app.company.com/user/" . $user_id;
echo do_shortcode('[web_embed url="' . esc_url($dashboard_url) . '"]');
?>
```

### Shortcode in Templates

Add embeds directly in theme templates:

```php
<?php echo do_shortcode('[web_embed url="https://dashboard.company.com" height="600px"]'); ?>
```

### Multiple Embeds Per Page

No problem! Use as many as needed:

```
[web_embed url="https://grafana.company.com/dashboard1" height="500px" title="Dashboard 1"]

[web_embed url="https://grafana.company.com/dashboard2" height="500px" title="Dashboard 2"]

[web_embed url="https://grafana.company.com/dashboard3" height="500px" title="Dashboard 3"]
```

The plugin handles caching efficiently.

### Embed in Widgets

Add shortcodes to text widgets (if enabled in your theme):

1. Go to Appearance → Widgets
2. Add a Text widget
3. Paste your shortcode
4. Save

Note: Widget support depends on your theme.

## Security Best Practices

### 1. Enable Domain Whitelist

For production sites:

1. Go to **Web Embed → Settings**
2. Enable "Domain Whitelist"
3. Add only trusted domains:
   ```
   dashboard.company.com
   grafana.company.com
   *.internal.company.com
   maps.google.com
   youtube.com
   ```

### 2. Use HTTPS Only

Keep "HTTPS Only" enabled to prevent:
- Man-in-the-middle attacks
- Mixed content warnings
- Security vulnerabilities

### 3. Validate URLs

The plugin automatically validates URLs, but:
- Review URLs before embedding
- Don't embed untrusted sources
- Use domain whitelist for strict control

### 4. Limit User Permissions

Configure who can create embeds:
- Builders: `edit_posts` capability (editors+)
- Settings: `manage_options` (admins only)

### 5. Regular Security Audits

- Review embedded URLs periodically
- Remove unused embeds
- Check access logs for your embedded apps

## Performance Tips

### 1. Use Caching

Default cache (1 hour) is good for most uses:
```
Settings → Cache Duration: 3600 seconds
```

Adjust based on content:
- Static content: 86400 (1 day)
- Frequently updated: 1800 (30 minutes)
- Real-time dashboards: 0 (disable)

### 2. Lazy Loading

Keep lazy loading enabled for better performance:
```
loading="lazy"
```

Only use `loading="eager"` for above-the-fold embeds.

### 3. Responsive Embeds

Responsive embeds perform better on mobile:
```
responsive="true"
```

Mobile browsers can optimize the display.

### 4. Limit Embed Count

Too many embeds slow page load:
- Use pagination for lists
- Load embeds on separate tabs
- Consider "click to load" for heavy content

### 5. Monitor Cache Hit Rate

Check Settings page for cache statistics:
- High hit rate (>80%) = good
- Low hit rate = consider longer cache duration

### 6. Clear Cache After Updates

If embedded content changes:
1. Go to Settings
2. Click "Clear All Cache"
3. Embeds will refresh

## Troubleshooting

### Embed Not Displaying

**Symptoms:** Empty space where embed should be

**Causes & Solutions:**

1. **X-Frame-Options blocking**
   - See ENTERPRISE_APPS_GUIDE.md
   - Fallback will display instead

2. **Invalid URL**
   - Check URL is correct
   - Verify it loads in browser
   - Check for typos

3. **Whitelist enabled**
   - Add domain to whitelist
   - Or disable whitelist temporarily

4. **HTTPS requirement**
   - Use HTTPS version
   - Or disable in settings

### Slow Loading

**Solutions:**
- Enable caching
- Use lazy loading
- Reduce number of embeds per page
- Check your server's performance

### Cache Not Working

**Check:**
- Cache duration > 0
- WordPress object cache if available
- Server has write access to database

**Fix:**
- Clear cache and regenerate
- Check for plugin conflicts

### Responsive Not Working

**Check:**
- `responsive="true"` is set
- Theme CSS isn't overriding styles
- Browser supports CSS aspect-ratio

**Fix:**
- Check browser console for CSS errors
- Try different theme temporarily
- Clear browser cache

## Next Steps

- [ENTERPRISE_APPS_GUIDE.md](ENTERPRISE_APPS_GUIDE.md) - Configure your internal apps
- [EMBEDDING_GUIDE.md](EMBEDDING_GUIDE.md) - Understand what works and why
- [FALLBACK_TEMPLATES.md](FALLBACK_TEMPLATES.md) - Professional fallback templates

