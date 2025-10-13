# Web Embed Plugin - Quick Start Guide

## Installation

### Option 1: Install from ZIP (Production)
1. Download `dist/web-embed.zip`
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Upload the ZIP file and click "Install Now"
4. Activate the plugin

### Option 2: Development
The plugin is already in your `plugins/` directory and ready to activate.

## Initial Configuration

1. **Go to Settings → Web Embed** in WordPress Admin

2. **Configure Security Settings** (recommended):
   - Enable "Whitelist Mode" if you want to restrict which domains can be embedded
   - Add trusted domains (one per line), e.g.:
     ```
     example.com
     google.com
     youtube.com
     ```
   - Enable "HTTPS Only" for secure connections

3. **Configure Caching** (optional but recommended):
   - Keep caching enabled for better performance
   - Default cache duration: 3600 seconds (1 hour)

4. **Set Default Options** (optional):
   - Default width: 100%
   - Default height: 600px
   - Responsive by default: checked

5. Click **Save Settings**

## Shortcode Builder (Recommended)

**The easiest way to create shortcodes!**

1. Go to **Settings → Web Embed Builder** in WordPress Admin
2. Fill in the form with your URL and preferences
3. Click "Generate Preview & Shortcode"
4. See a live preview and copy the shortcode
5. Paste into any page or post

This visual builder helps you:
- ✅ Create shortcodes without memorizing syntax
- ✅ Preview embeds before publishing
- ✅ Test against whitelist settings
- ✅ Validate URLs in real-time
- ✅ Copy shortcodes with one click

## Basic Usage

### Simplest Example
```
[web_embed url="https://example.com"]
```

### With Custom Size
```
[web_embed url="https://example.com" width="800px" height="600px"]
```

### Responsive Embed
```
[web_embed url="https://example.com" width="100%" height="600px" responsive="true"]
```

### Styled Embed
```
[web_embed url="https://example.com" 
          border="2px solid #ccc" 
          border_radius="10px"
          class="my-custom-embed"]
```

## Common Use Cases

### Embed Google Maps
```
[web_embed url="https://www.google.com/maps/embed?pb=YOUR_MAP_CODE" 
          width="100%" 
          height="450px" 
          responsive="true"
          title="Office Location"]
```

### Embed External Dashboard
```
[web_embed url="https://dashboard.example.com" 
          width="100%" 
          height="800px"
          border="1px solid #e0e0e0"]
```

### Embed with Fallback Message
```
[web_embed url="https://tool.example.com" 
          fallback="Content unavailable. <a href='https://tool.example.com'>Visit directly</a>"]
```

## All Available Parameters

| Parameter | Description | Default |
|-----------|-------------|---------|
| url | URL to embed (required) | - |
| width | Width of embed | From settings |
| height | Height of embed | From settings |
| responsive | Enable responsive mode | From settings |
| border | CSS border style | none |
| border_radius | CSS border radius | 0 |
| class | Custom CSS classes | From settings |
| title | Accessibility title | Embedded Content |
| loading | Loading strategy (lazy/eager) | lazy |
| fallback | Fallback message if embed fails | Auto-generated |

## Troubleshooting

### Embed Not Showing?
1. Check that the URL is accessible
2. If whitelist is enabled, verify the domain is allowed
3. Check if HTTPS-only mode is blocking HTTP URLs
4. Some sites prevent embedding (X-Frame-Options) - they will show fallback

### Performance Issues?
1. Enable caching in settings
2. Use lazy loading (default)
3. Increase cache duration for static content

### Need Help?
- See `README.md` for security considerations
- See `USAGE_GUIDE.md` for detailed examples
- Check WordPress Admin → Settings → Web Embed for configuration

## Tips

1. Always test embedded URLs directly first
2. Use whitelist mode for public-facing sites
3. Enable HTTPS-only for better security
4. Provide custom fallback messages for better UX
5. Use responsive mode for mobile-friendly embeds
6. Clear cache when embedded content updates

## Next Steps

- Read the full [README.md](README.md) for features and security info
- Check the [USAGE_GUIDE.md](USAGE_GUIDE.md) for advanced examples
- Configure your security settings in Settings → Web Embed
- Test with a simple embed first, then customize as needed

That's it! You're ready to start embedding content. 🚀

