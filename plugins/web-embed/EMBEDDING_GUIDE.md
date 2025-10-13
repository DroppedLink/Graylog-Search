# Web Embed - Understanding What Can Be Embedded

## Why Some Sites Don't Embed

Many websites actively prevent embedding for security and branding reasons using HTTP headers like `X-Frame-Options` or `Content-Security-Policy`.

### Common Blocked Sites

These major sites typically **cannot** be embedded:

❌ **Google.com** - Main search page blocks embedding
❌ **Facebook.com** - Social network pages blocked
❌ **Twitter.com** - Main site blocked (use embed API instead)
❌ **Instagram.com** - Main site blocked (use embed API instead)
❌ **GitHub.com** - Main site blocked (but GitHub Pages work)
❌ **LinkedIn.com** - Professional network blocked
❌ **Amazon.com** - E-commerce site blocked
❌ **Banking sites** - Almost all block embedding for security

### Why They Block Embedding

**Security Reasons:**
- Prevent clickjacking attacks
- Protect user data and privacy
- Prevent unauthorized use of their interface
- Maintain control over user experience

**What Happens:**
- The `X-Frame-Options: DENY` or `SAMEORIGIN` header blocks the embed
- Browser refuses to display the content
- Your fallback message appears instead
- The shortcode works, but shows fallback instead of content

## What Works: Embed-Friendly URLs

### ✅ Google Services (Embed URLs)

**Google Maps** (use embed URL, not regular URL):
```
✅ https://www.google.com/maps/embed?pb=!1m18!1m12...
❌ https://www.google.com/maps/@37.7749,-122.4194
```

**How to get Google Maps embed URL:**
1. Go to Google Maps
2. Find your location
3. Click "Share"
4. Click "Embed a map"
5. Copy the iframe src URL

**Google Calendar:**
```
✅ https://calendar.google.com/calendar/embed?src=...
```

### ✅ YouTube (Embed URLs)

**YouTube Videos** (use embed URL):
```
✅ https://www.youtube.com/embed/VIDEO_ID
❌ https://www.youtube.com/watch?v=VIDEO_ID
```

**Convert YouTube URL:**
- Regular: `https://www.youtube.com/watch?v=dQw4w9WgXcQ`
- Embed: `https://www.youtube.com/embed/dQw4w9WgXcQ`

### ✅ Your Own Sites

**Internal Dashboards:**
```
✅ https://dashboard.yourcompany.com
✅ https://monitoring.yoursite.com
✅ https://analytics.internal.net
```

**Documentation Sites:**
```
✅ https://docs.yourproduct.com
✅ https://yoursite.github.io/project
✅ https://yourproject.readthedocs.io
```

**Monitoring & Tools:**
```
✅ Grafana dashboards
✅ Kibana visualizations
✅ Custom admin panels
✅ Internal tools
```

### ✅ Embed-Designed Services

**Presentation Tools:**
```
✅ Google Slides (published)
✅ SlideShare embeds
✅ Prezi embeds
```

**Forms & Surveys:**
```
✅ Google Forms
✅ Typeform embeds
✅ JotForm embeds
```

**Interactive Content:**
```
✅ CodePen embeds
✅ JSFiddle embeds
✅ Observable notebooks
```

**Media Players:**
```
✅ Vimeo embeds
✅ SoundCloud embeds
✅ Spotify embeds
```

## Testing URLs

### ✅ Safe Test URLs

Want to test the plugin? Try these:

**Example.com** (always allows embedding):
```
[web_embed url="https://example.com"]
```

**W3C Validator** (allows embedding):
```
[web_embed url="https://validator.w3.org"]
```

**HTTPBin** (testing service):
```
[web_embed url="https://httpbin.org"]
```

### Your Own Content

The best content to embed is usually:
1. **Your own websites** - You control the headers
2. **Internal tools** - Company dashboards, monitoring
3. **Third-party tools** - Services designed for embedding

## How to Check If a Site Allows Embedding

### Method 1: Browser Developer Tools

1. Open the URL in your browser
2. Open Developer Tools (F12)
3. Go to Network tab
4. Reload the page
5. Click on the main document
6. Look for `X-Frame-Options` header:
   - `DENY` = Cannot embed anywhere
   - `SAMEORIGIN` = Can only embed on same domain
   - No header = Usually can embed

### Method 2: Try It in the Builder

1. Go to Web Embed → Builder
2. Enter your URL
3. Click "Generate Preview & Shortcode"
4. If preview is empty, the site likely blocks embedding
5. The shortcode still works (shows fallback message)

## Fallback Behavior

When a site blocks embedding, your shortcode automatically shows the fallback message:

**Default Fallback:**
```
This content cannot be displayed. 
[View in new window] (link to original URL)
```

**Custom Fallback:**
```
[web_embed url="https://google.com" 
          fallback="<p>Google blocks embedding. <a href='https://google.com'>Visit Google</a></p>"]
```

## Solutions for Blocked Sites

### Option 1: Use Embed-Specific URLs

Many services offer embed-specific URLs:
- YouTube: `/embed/` instead of `/watch`
- Google Maps: `/maps/embed` instead of regular maps
- Twitter: Use embed API and get embed code

### Option 2: Use Fallback Messages

Accept that the site blocks embedding and provide a helpful fallback:

```
[web_embed url="https://blocked-site.com"
          fallback="<div class='custom-fallback'>
              <h3>Visit Our Tool</h3>
              <p>This tool requires direct access.</p>
              <a href='https://blocked-site.com' class='button' target='_blank'>
                  Open Tool
              </a>
          </div>"]
```

### Option 3: Screenshot or Image

For sites that block embedding, consider:
1. Taking a screenshot
2. Using an image instead
3. Linking to the site with `target="_blank"`

### Option 4: Contact the Site Owner

If it's a third-party service you use:
1. Contact their support
2. Ask if they offer embed URLs
3. Request they add your domain to their CSP whitelist

## Troubleshooting

### "Preview is Empty"

**Likely Cause:** The site blocks embedding

**Check:**
1. Is it a major website (Google, Facebook, etc.)? → They block embedding
2. Is it your own site? → Check your server's security headers
3. Is it an internal tool? → Ask your IT team about headers

**Solution:**
- Use embed-specific URLs if available
- Accept the fallback behavior
- Use alternative embedding methods

### "This content cannot be displayed"

**This is normal!** Many sites block embedding for security.

**What to do:**
1. ✅ The shortcode still works (you can use it)
2. ✅ The fallback message appears for visitors
3. ✅ They can click to open the site in a new window
4. Consider using a different URL or embedding method

## Best Practices

### ✅ DO:

1. **Test before deploying** - Use the builder to preview
2. **Use embed URLs** - Get proper embed links from services
3. **Provide fallbacks** - Help users when embedding fails
4. **Embed your own content** - You control the headers
5. **Check documentation** - Services often have embed docs

### ❌ DON'T:

1. **Assume any URL works** - Many major sites block embedding
2. **Use regular URLs for YouTube/Maps** - Use embed versions
3. **Ignore security headers** - They exist for good reasons
4. **Expect all sites to work** - Embedding is often blocked
5. **Fight X-Frame-Options** - It's a security feature

## Summary

**Key Takeaways:**

1. 🚫 **Many major sites block embedding** - This is normal and expected
2. ✅ **Use embed-specific URLs** - YouTube, Maps, etc. have embed versions
3. 🏢 **Your own sites work best** - You control the security headers
4. 💡 **Fallbacks are important** - Provide good user experience when blocked
5. 🧪 **Test in the builder** - See exactly what visitors will see

**Remember:** If the preview is empty, it doesn't mean the plugin is broken - it means the site is protecting itself, which is a good security practice!

---

**Need Help?**
- Try the URLs listed in "✅ What Works" section above
- Check the site's documentation for embed URLs
- Contact the site owner about embedding options

