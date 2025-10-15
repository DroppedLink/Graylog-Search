# Fallback Templates

Professional HTML templates for custom fallback messages when content cannot be embedded.

## Table of Contents

1. [Using Custom Fallbacks](#using-custom-fallbacks)
2. [Template 1: Simple Launch](#template-1-simple-launch)
3. [Template 2: Feature Card](#template-2-feature-card)
4. [Template 3: Security Notice](#template-3-security-notice)
5. [Template 4: Minimal Modern](#template-4-minimal-modern)
6. [Template 5: App Grid Card](#template-5-app-grid-card)
7. [Template 6: Portal Entry](#template-6-portal-entry)
8. [Customization Tips](#customization-tips)

## Using Custom Fallbacks

Add the `fallback` parameter to your shortcode:

```
[web_embed url="https://example.com" fallback="YOUR_HTML_HERE"]
```

**Note:** Keep HTML simple and use inline styles for best compatibility.

## Template 1: Simple Launch

Clean, minimalist button design.

### Preview
```
┌─────────────────────────────────────┐
│                                     │
│          Dashboard Name             │
│       Open your dashboard           │
│                                     │
│        [ Launch Dashboard ]         │
│                                     │
└─────────────────────────────────────┘
```

### Code
```html
<div style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 300px; padding: 40px; background: #f8f9fa; border-radius: 8px; text-align: center;">
    <h3 style="margin: 0 0 10px 0; font-size: 24px; color: #333;">Dashboard Name</h3>
    <p style="margin: 0 0 25px 0; color: #666; font-size: 16px;">Open your dashboard</p>
    <a href="https://dashboard.company.com" target="_blank" rel="noopener noreferrer" style="display: inline-block; padding: 12px 30px; background: #0073aa; color: white; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px; transition: background 0.3s;">Launch Dashboard</a>
</div>
```

### Usage
```
[web_embed url="https://dashboard.company.com" fallback="<div style='display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 300px; padding: 40px; background: #f8f9fa; border-radius: 8px; text-align: center;'><h3 style='margin: 0 0 10px 0; font-size: 24px; color: #333;'>Dashboard Name</h3><p style='margin: 0 0 25px 0; color: #666; font-size: 16px;'>Open your dashboard</p><a href='https://dashboard.company.com' target='_blank' rel='noopener noreferrer' style='display: inline-block; padding: 12px 30px; background: #0073aa; color: white; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px;'>Launch Dashboard</a></div>"]
```

## Template 2: Feature Card

Professional card with icon and features list.

### Preview
```
┌─────────────────────────────────────┐
│              [Icon]                 │
│                                     │
│        Analytics Dashboard          │
│                                     │
│    ✓ Real-time metrics              │
│    ✓ Custom reports                 │
│    ✓ Data export                    │
│                                     │
│      [ Open Dashboard → ]           │
└─────────────────────────────────────┘
```

### Code
```html
<div style="max-width: 400px; margin: 0 auto; padding: 40px; background: white; border: 2px solid #e1e8ed; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center;">
    <div style="width: 64px; height: 64px; margin: 0 auto 20px; background: #0073aa; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; color: white;">📊</div>
    <h3 style="margin: 0 0 20px 0; font-size: 22px; color: #333;">Analytics Dashboard</h3>
    <ul style="list-style: none; padding: 0; margin: 0 0 25px 0; text-align: left;">
        <li style="padding: 8px 0; color: #666; font-size: 15px;">✓ Real-time metrics</li>
        <li style="padding: 8px 0; color: #666; font-size: 15px;">✓ Custom reports</li>
        <li style="padding: 8px 0; color: #666; font-size: 15px;">✓ Data export</li>
    </ul>
    <a href="https://analytics.company.com" target="_blank" rel="noopener noreferrer" style="display: inline-block; padding: 12px 28px; background: #0073aa; color: white; text-decoration: none; border-radius: 6px; font-weight: 600;">Open Dashboard →</a>
</div>
```

## Template 3: Security Notice

Professional notice for secure/internal applications.

### Preview
```
┌─────────────────────────────────────┐
│              [Lock]                 │
│                                     │
│     Secure Application Access       │
│                                     │
│  This application requires direct   │
│  access for security reasons.       │
│                                     │
│    [ Access Securely → ]            │
└─────────────────────────────────────┘
```

### Code
```html
<div style="max-width: 500px; margin: 0 auto; padding: 50px 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; text-align: center; color: white;">
    <div style="font-size: 48px; margin-bottom: 20px;">🔒</div>
    <h3 style="margin: 0 0 15px 0; font-size: 24px; color: white;">Secure Application Access</h3>
    <p style="margin: 0 0 30px 0; font-size: 16px; line-height: 1.6; color: rgba(255,255,255,0.9);">This application requires direct access for security reasons.</p>
    <a href="https://secure.company.com" target="_blank" rel="noopener noreferrer" style="display: inline-block; padding: 14px 32px; background: white; color: #667eea; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px;">Access Securely →</a>
</div>
```

## Template 4: Minimal Modern

Ultra-clean, modern design.

### Preview
```
┌─────────────────────────────────────┐
│                                     │
│                                     │
│         Application Name            │
│                                     │
│          Launch →                   │
│                                     │
│                                     │
└─────────────────────────────────────┘
```

### Code
```html
<div style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 400px; padding: 60px; background: #ffffff; border: 1px solid #e5e5e5; text-align: center;">
    <h2 style="margin: 0 0 30px 0; font-size: 32px; font-weight: 300; color: #333; letter-spacing: -0.5px;">Application Name</h2>
    <a href="https://app.company.com" target="_blank" rel="noopener noreferrer" style="padding: 0; background: none; color: #0073aa; text-decoration: none; font-size: 18px; font-weight: 500; border-bottom: 2px solid #0073aa;">Launch →</a>
</div>
```

## Template 5: App Grid Card

Perfect for dashboard grids with multiple apps.

### Preview
```
┌─────────────────────┐
│   [Icon]            │
│                     │
│   Grafana           │
│   Monitoring        │
│                     │
│   Open →            │
└─────────────────────┘
```

### Code
```html
<div style="padding: 30px; background: linear-gradient(to bottom right, #f8f9fa, #e9ecef); border-radius: 8px; text-align: center; min-height: 200px; display: flex; flex-direction: column; justify-content: space-between;">
    <div>
        <div style="font-size: 40px; margin-bottom: 15px;">📈</div>
        <h4 style="margin: 0 0 5px 0; font-size: 18px; color: #333;">Grafana</h4>
        <p style="margin: 0 0 20px 0; font-size: 14px; color: #666;">Monitoring</p>
    </div>
    <a href="https://grafana.company.com" target="_blank" rel="noopener noreferrer" style="color: #0073aa; text-decoration: none; font-weight: 600; font-size: 14px;">Open →</a>
</div>
```

### Grid Layout Example

Use multiple cards in a grid:

```html
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">
    [web_embed url="https://grafana.company.com" fallback="...CARD 1..."]
    [web_embed url="https://kibana.company.com" fallback="...CARD 2..."]
    [web_embed url="https://jenkins.company.com" fallback="...CARD 3..."]
    [web_embed url="https://sonar.company.com" fallback="...CARD 4..."]
</div>
```

## Template 6: Portal Entry

Large, prominent portal-style entry.

### Preview
```
┌─────────────────────────────────────────┐
│                                         │
│            [Large Icon]                 │
│                                         │
│        Employee Portal                  │
│                                         │
│    Access your dashboard, reports,      │
│    and internal tools                   │
│                                         │
│     [ Enter Portal → ]                  │
│                                         │
└─────────────────────────────────────────┘
```

### Code
```html
<div style="min-height: 500px; padding: 80px 40px; background: linear-gradient(to bottom, #0073aa, #005a87); border-radius: 12px; text-align: center; color: white; display: flex; flex-direction: column; align-items: center; justify-content: center;">
    <div style="width: 100px; height: 100px; margin-bottom: 30px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 50px;">🏢</div>
    <h2 style="margin: 0 0 20px 0; font-size: 36px; color: white; font-weight: 600;">Employee Portal</h2>
    <p style="margin: 0 0 40px 0; font-size: 18px; line-height: 1.6; color: rgba(255,255,255,0.9); max-width: 500px;">Access your dashboard, reports, and internal tools</p>
    <a href="https://portal.company.com" target="_blank" rel="noopener noreferrer" style="display: inline-block; padding: 16px 40px; background: white; color: #0073aa; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 18px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">Enter Portal →</a>
</div>
```

## Customization Tips

### Colors

Replace these hex codes with your brand colors:

- **Primary:** `#0073aa` → Your primary color
- **Dark:** `#005a87` → Darker shade
- **Light:** `#f8f9fa` → Light background
- **Text:** `#333` → Dark text
- **Muted:** `#666` → Muted text

### Icons

Replace emoji icons with:

- Font Awesome classes
- Custom image URLs
- SVG code
- Unicode symbols

Examples:
- 📊 Dashboard
- 📈 Analytics
- 🔒 Security
- 🏢 Portal
- ⚙️ Settings
- 📁 Files
- 📧 Email
- 👤 Profile

### Sizes

Adjust these values:

- **Padding:** `padding: 40px;` → More/less space
- **Font Size:** `font-size: 24px;` → Bigger/smaller text
- **Height:** `min-height: 300px;` → Taller/shorter
- **Width:** `max-width: 400px;` → Wider/narrower

### Animations

Add hover effects:

```css
transition: transform 0.2s, box-shadow 0.2s;

/* Add to anchor tags */
&:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
}
```

Note: Inline styles don't support `:hover`, add to theme CSS instead.

### Responsive Design

Templates use flexible units:
- `padding: 40px` → `padding: 20px` on mobile (add via theme CSS)
- `font-size: 24px` → `font-size: 20px` on mobile

Add to your theme's CSS:
```css
@media (max-width: 768px) {
    .web-embed-fallback {
        padding: 20px !important;
        font-size: 16px !important;
    }
}
```

## Best Practices

### ✅ Do

1. **Keep it simple** - Simple HTML works best
2. **Use inline styles** - Ensures compatibility
3. **Test on mobile** - Check responsive behavior
4. **Match your brand** - Use brand colors and fonts
5. **Provide context** - Explain why user needs to click
6. **Use clear CTAs** - "Launch Dashboard" not just "Click Here"

### ❌ Don't

1. **Don't use external CSS** - May not load
2. **Don't use complex JavaScript** - Won't execute in fallback
3. **Don't use external images** - May be slow/broken
4. **Don't make it too tall** - Keep under 600px
5. **Don't forget the link** - Always provide a way to access content

## Testing Your Fallback

1. Create shortcode with custom fallback
2. Test on a site that blocks embedding (e.g., google.com)
3. Verify fallback displays correctly
4. Test on mobile devices
5. Check all links work

Example test:
```
[web_embed url="https://www.google.com" fallback="YOUR_TEMPLATE_HERE"]
```

Google blocks embedding, so fallback will always show.

## Dynamic Content

### PHP Variables

Use PHP to make dynamic fallbacks:

```php
<?php
$app_name = "Dashboard";
$app_url = "https://dashboard.company.com";
$fallback = "<div style='...'>
    <h3>{$app_name}</h3>
    <a href='{$app_url}'>Launch</a>
</div>";

echo do_shortcode("[web_embed url='{$app_url}' fallback='{$fallback}']");
?>
```

### User-Specific

```php
<?php
$user = wp_get_current_user();
$user_dashboard = "https://app.com/user/" . $user->ID;
$fallback = "<div style='...'>
    <h3>Welcome, {$user->display_name}</h3>
    <a href='{$user_dashboard}'>Your Dashboard</a>
</div>";
?>
```

## More Resources

- [USAGE_GUIDE.md](USAGE_GUIDE.md) - Advanced shortcode usage
- [ENTERPRISE_APPS_GUIDE.md](ENTERPRISE_APPS_GUIDE.md) - Configure your apps
- [EMBEDDING_GUIDE.md](EMBEDDING_GUIDE.md) - What works and why

## Questions?

**Q: Can I use external stylesheets?**
A: Not recommended. Inline styles are more reliable.

**Q: Can I add JavaScript?**
A: Not in fallback content. Use theme JavaScript instead.

**Q: How do I test without a blocked site?**
A: Test with google.com or any site that blocks embedding.

**Q: Can I use shortcodes in fallback?**
A: No, shortcodes are not processed in fallback content.

**Q: What about accessibility?**
A: Ensure sufficient color contrast and use semantic HTML.

