# Quick Start Guide

Get started with Web Embed in 5 minutes!

## Step 1: Install & Activate

1. Upload the plugin to WordPress
2. Activate it from the Plugins page
3. You'll see a new "Web Embed" menu item in your admin sidebar

## Step 2: Create Your First Embed

### Using the Builder (Easiest!)

1. Go to **Web Embed → Builder**
2. Enter a URL (try: `https://www.youtube.com/embed/dQw4w9WgXcQ`)
3. Click **"Generate and Preview"**
4. See the live preview below
5. Click **"Copy to Clipboard"**
6. Paste the shortcode into any post or page

**That's it!** Your embed is live.

### Manual Method

If you prefer typing shortcodes directly:

```
[web_embed url="https://example.com"]
```

## Step 3: Configure Settings (Optional)

Go to **Web Embed → Settings** to:

- Enable domain whitelist for security
- Set HTTPS-only mode
- Configure default dimensions
- Adjust cache duration

## Common First Embeds

### YouTube Video
```
[web_embed url="https://www.youtube.com/embed/VIDEO_ID" responsive="true"]
```

Get the embed URL from YouTube's "Share → Embed" option.

### Google Maps
```
[web_embed url="https://www.google.com/maps/embed?pb=..." responsive="true" height="450px"]
```

Get the embed URL from Google Maps' "Share → Embed a map" option.

### Internal Dashboard
```
[web_embed url="https://dashboard.yourcompany.com" width="100%" height="800px"]
```

## Quick Tips

✅ **Use the Builder** - It's the easiest way to create embeds with preview

✅ **Enable Responsive** - Makes embeds mobile-friendly automatically

✅ **Test URLs** - Some sites block embedding (Google.com, Facebook, etc.)

✅ **Use HTTPS** - More secure and required by default

✅ **Check Whitelist** - If a URL is blocked, add it to the whitelist in settings

## Troubleshooting

### "Empty Preview"

**Problem:** The preview is blank after clicking Generate.

**Cause:** The site blocks embedding (X-Frame-Options header).

**Solutions:**
- For YouTube/Maps: Use their embed URLs (not watch/view URLs)
- For your apps: See ENTERPRISE_APPS_GUIDE.md
- For external sites: They may not allow embedding

The plugin will show a fallback message on your site with a link.

### "URL Blocked"

**Problem:** Error message says URL is blocked.

**Cause:** Domain whitelist is enabled and domain not in list.

**Solution:** Go to Settings → Add domain to "Allowed Domains"

### "HTTPS Required"

**Problem:** Error about HTTP not allowed.

**Cause:** HTTPS-only mode is enabled (recommended).

**Solution:** 
- Use HTTPS version of URL
- OR disable "HTTPS Only" in Settings (not recommended)

## Next Steps

- Read [USAGE_GUIDE.md](USAGE_GUIDE.md) for advanced examples
- Check [ENTERPRISE_APPS_GUIDE.md](ENTERPRISE_APPS_GUIDE.md) for internal apps
- See [EMBEDDING_GUIDE.md](EMBEDDING_GUIDE.md) to understand what works

## Need Help?

1. Check the built-in tips in the Builder
2. Review the Settings page descriptions
3. Read the other documentation files
4. Visit the support forum

**Enjoy embedding!** 🎉

