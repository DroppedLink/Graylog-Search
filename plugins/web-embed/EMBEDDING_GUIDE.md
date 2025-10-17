# Embedding Guide: What Works and Why

Understanding which sites can be embedded and why some can't.

## The Short Version

**✅ Works:** Sites designed for embedding (YouTube embed URLs, Google Maps embed, your own internal tools)

**❌ Doesn't Work:** Major sites blocking embedding for security (google.com, facebook.com, twitter.com, etc.)

**🔧 Fixable:** Your internal enterprise applications (see ENTERPRISE_APPS_GUIDE.md)

## Why Some Sites Block Embedding

### Security Reasons

Sites block embedding to prevent:

1. **Clickjacking Attacks** - Tricking users into clicking hidden elements
2. **Phishing** - Displaying legitimate site in malicious context
3. **Content Theft** - Unauthorized use of their interface
4. **Session Hijacking** - Stealing user sessions
5. **UI Redressing** - Overlaying fake UI elements

### How They Block It

Two HTTP headers:

**X-Frame-Options:**
```
X-Frame-Options: DENY           # Block all embedding
X-Frame-Options: SAMEORIGIN     # Only allow same domain
X-Frame-Options: ALLOW-FROM uri # Allow specific domain (deprecated)
```

**Content-Security-Policy (Modern):**
```
Content-Security-Policy: frame-ancestors 'none'  # Block all
Content-Security-Policy: frame-ancestors 'self'  # Only same domain
Content-Security-Policy: frame-ancestors 'self' https://example.com  # Specific domains
```

## What Works: The Embed-Friendly List

### Video Platforms

#### YouTube ✅
**Regular URL (doesn't work):**
```
https://www.youtube.com/watch?v=dQw4w9WgXcQ
```

**Embed URL (works):**
```
https://www.youtube.com/embed/dQw4w9WgXcQ
```

**Get it from:** YouTube → Share → Embed → Copy iframe src

**Example:**
```
[web_embed url="https://www.youtube.com/embed/VIDEO_ID" responsive="true"]
```

#### Vimeo ✅
**Embed URL:**
```
https://player.vimeo.com/video/VIDEO_ID
```

**Example:**
```
[web_embed url="https://player.vimeo.com/video/123456789" responsive="true"]
```

#### Wistia ✅
**Embed URL:**
```
https://fast.wistia.net/embed/iframe/VIDEO_ID
```

### Maps

#### Google Maps ✅
**Regular URL (doesn't work):**
```
https://www.google.com/maps/place/...
```

**Embed URL (works):**
```
https://www.google.com/maps/embed?pb=...
```

**Get it from:** Google Maps → Share → Embed a map

**Example:**
```
[web_embed url="https://www.google.com/maps/embed?pb=..." height="450px" responsive="true"]
```

#### Mapbox ✅
Works with proper configuration and API key in URL.

#### OpenStreetMap ✅
Most embed-friendly implementations work.

### Forms & Surveys

#### Google Forms ✅
**Get embed URL from:** Share → Get form link

```
https://docs.google.com/forms/d/e/FORM_ID/viewform?embedded=true
```

#### Typeform ✅
**Embed URL:**
```
https://form.typeform.com/to/FORM_ID
```

#### JotForm ✅
Get embed code from form settings.

### Documents & Collaboration

#### Google Docs (Published) ✅
**Must publish first:** File → Share → Publish to web

```
https://docs.google.com/document/d/DOCUMENT_ID/pub?embedded=true
```

#### Google Sheets (Published) ✅
```
https://docs.google.com/spreadsheets/d/SHEET_ID/pubhtml
```

#### Notion (Public Pages) ✅
Public Notion pages can be embedded.

### Code & Development

#### CodePen ✅
**Embed URL:**
```
https://codepen.io/username/embed/PEN_ID
```

#### JSFiddle ✅
Add `/embedded/` to URL:
```
https://jsfiddle.net/username/CODE/embedded/
```

#### CodeSandbox ✅
Embed URL from share menu.

#### GitHub Gist ✅
Use the embed version.

### Social Media (Limited)

#### Twitter ❌
Main site blocks embedding. Use Twitter's embed widgets instead.

#### Facebook ❌
Main site blocks embedding. Use Facebook's embed codes.

#### Instagram ❌
Blocks embedding. Use Instagram's embed codes.

#### LinkedIn ❌
Blocks embedding.

### Your Own Applications ✅

**Internal dashboards, monitoring tools, admin panels, etc.**

If you control the application, you can configure it to allow embedding.

See: [ENTERPRISE_APPS_GUIDE.md](ENTERPRISE_APPS_GUIDE.md)

## What Doesn't Work: The Blocked List

### Major Sites That Block Embedding

These sites explicitly block all embedding attempts:

- ❌ google.com (main site, not Maps/Docs embeds)
- ❌ facebook.com (main site)
- ❌ twitter.com (main site)
- ❌ instagram.com
- ❌ linkedin.com
- ❌ github.com (main site, Gists OK)
- ❌ amazon.com
- ❌ reddit.com
- ❌ wikipedia.org
- ❌ netflix.com
- ❌ banking sites (all)
- ❌ Most e-commerce sites
- ❌ Most news sites

### What Happens When Blocked

The plugin shows a professional fallback message:

```
┌─────────────────────────────────────────┐
│              [Link Icon]                │
│                                         │
│          Embedded Content               │
│                                         │
│  This content from example.com cannot   │
│  be displayed inline. It may be         │
│  protected by security settings.        │
│                                         │
│      [ Open in New Tab ↗ ]             │
└─────────────────────────────────────────┘
```

Users can click the button to open the content directly.

## Testing If a URL Will Work

### Method 1: Web Embed Builder

1. Go to **Web Embed → Builder**
2. Enter the URL
3. Click **Generate and Preview**
4. If preview shows content → Works! ✅
5. If preview is empty → Blocked ❌

### Method 2: Command Line

```bash
curl -I https://example.com
```

Look for these headers:

**Blocked:**
```
X-Frame-Options: DENY
X-Frame-Options: SAMEORIGIN
Content-Security-Policy: frame-ancestors 'none'
```

**Allowed:**
```
(no X-Frame-Options header)
X-Frame-Options: ALLOWALL
Content-Security-Policy: frame-ancestors *
```

### Method 3: Browser Console

1. Try to load the URL in an iframe
2. Open DevTools (F12)
3. Look for errors:
   ```
   Refused to display 'https://example.com' in a frame
   because it set 'X-Frame-Options' to 'deny'.
   ```

## Tips for Finding Embed URLs

### YouTube
- Go to video → Share → Embed
- Copy the URL from `<iframe src="...">`

### Google Maps
- Find location → Share → Embed a map
- Copy the URL from `<iframe src="...">`

### Most Platforms
- Look for "Embed" or "Share" button
- Choose "Embed" option
- Look for `<iframe>` code
- Copy the URL from `src="..."`

### Your Own Apps
- If you control the app, configure it to allow embedding
- See ENTERPRISE_APPS_GUIDE.md for instructions

## Alternatives for Blocked Sites

### Option 1: Use Their Embed Codes

Many sites provide their own embed codes:
- Twitter → Embed tweet widget
- Facebook → Embed post widget
- Instagram → Embed post code

These are designed for embedding and work outside of Web Embed.

### Option 2: Link Instead

```html
<a href="https://twitter.com/username/status/123" target="_blank">
    View on Twitter ↗
</a>
```

### Option 3: Screenshots

For static content:
1. Take screenshot
2. Upload to WordPress media library
3. Add image with link to original

### Option 4: API Integration

For dynamic content:
- Use the platform's API
- Pull data into WordPress
- Display with custom PHP/plugin

## Best Practices

### ✅ Do

1. **Test First** - Use builder to test before deploying
2. **Use Embed URLs** - YouTube embed, not watch URL
3. **Read Documentation** - Check platform's embed docs
4. **Configure Your Apps** - Control what you can
5. **Provide Context** - Use custom fallback messages

### ❌ Don't

1. **Don't Bypass Security** - Respect X-Frame-Options
2. **Don't Embed Banking** - Never embed sensitive sites
3. **Don't Assume It Works** - Always test first
4. **Don't Ignore Fallbacks** - Users need alternatives
5. **Don't Embed Everything** - Sometimes linking is better

## Summary

**Works Great:**
- ✅ YouTube (embed URLs)
- ✅ Google Maps (embed URLs)
- ✅ Your internal tools (with configuration)
- ✅ Public forms
- ✅ Code demos
- ✅ Published documents

**Use Alternatives:**
- ❌ Social media sites
- ❌ Banking sites
- ❌ E-commerce sites
- ❌ News sites

**The Rule:**
If a site provides an "embed" option, it will work.
If it doesn't, it probably won't.

## Questions?

**Q: Can I force embedding on blocked sites?**
A: No. Browsers enforce these security headers. You cannot override them.

**Q: What about iframes?**
A: Same restrictions apply. `<object>`, `<embed>`, and `<iframe>` all respect X-Frame-Options.

**Q: Will this change in the future?**
A: Unlikely. These security features protect users and will remain.

**Q: Can I embed my own site?**
A: Yes! Configure your application to allow it. See ENTERPRISE_APPS_GUIDE.md.

## More Resources

- [ENTERPRISE_APPS_GUIDE.md](ENTERPRISE_APPS_GUIDE.md) - Configure your internal apps
- [FALLBACK_TEMPLATES.md](FALLBACK_TEMPLATES.md) - Professional fallback designs
- [MDN: X-Frame-Options](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Frame-Options)
- [MDN: CSP frame-ancestors](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy/frame-ancestors)

