# Embedding Enterprise Applications - Configuration Guide

## Common Issue: X-Frame-Options in Enterprise Apps

**Problem:** You try to embed your internal dashboard/app and get a blank preview.

**Cause:** Enterprise frameworks often enable `X-Frame-Options` by default for security.

**Solution:** Since you control these apps, you can configure them to allow embedding!

## How to Check If X-Frame-Options Is the Problem

### Method 1: Browser Console

1. Open your enterprise app in a browser
2. Press F12 (Developer Tools)
3. Go to **Console** tab
4. Try embedding: Look for error like:
   ```
   Refused to display 'https://your-app.com' in a frame because 
   it set 'X-Frame-Options' to 'deny'.
   ```

### Method 2: Check Headers

1. Open Developer Tools (F12)
2. Go to **Network** tab
3. Reload the page
4. Click on the first request (your app's HTML)
5. Look in **Response Headers** for:
   - `X-Frame-Options: DENY` ← Blocks all embedding
   - `X-Frame-Options: SAMEORIGIN` ← Only allows same domain
   - `Content-Security-Policy: frame-ancestors 'none'` ← Blocks embedding

### Method 3: Quick Test

Try this in browser console on the WordPress admin page:
```javascript
var iframe = document.createElement('iframe');
iframe.src = 'https://your-enterprise-app.com';
document.body.appendChild(iframe);
```

If it stays blank and shows console error → X-Frame-Options is blocking it.

## Solutions by Platform/Framework

### Spring Boot (Java)

**Problem:** Spring Security sets `X-Frame-Options: DENY` by default

**Solution 1: Allow Specific Domain (Recommended)**

```java
@Configuration
@EnableWebSecurity
public class SecurityConfig extends WebSecurityConfigurerAdapter {
    
    @Override
    protected void configure(HttpSecurity http) throws Exception {
        http
            .headers()
                .contentSecurityPolicy("frame-ancestors 'self' https://your-wordpress-site.com")
            .and()
            .frameOptions().disable(); // Disable old X-Frame-Options
    }
}
```

**Solution 2: Same Origin Only**

```java
http
    .headers()
        .frameOptions()
            .sameOrigin(); // Allow from same origin
```

**Solution 3: Disable Completely (Internal Apps Only)**

```java
http
    .headers()
        .frameOptions().disable();
```

### Django (Python)

**Problem:** Django sets `X-Frame-Options: DENY` by default

**Solution 1: Allow Specific Domain**

In `settings.py`:
```python
# Use Content Security Policy
CSP_FRAME_ANCESTORS = ["'self'", "https://your-wordpress-site.com"]

# Disable old X-Frame-Options
X_FRAME_OPTIONS = None
```

Install django-csp:
```bash
pip install django-csp
```

Add to middleware:
```python
MIDDLEWARE = [
    'csp.middleware.CSPMiddleware',
    # ... other middleware
]
```

**Solution 2: Same Origin**

```python
X_FRAME_OPTIONS = 'SAMEORIGIN'
```

**Solution 3: Allow All (Internal Only)**

```python
X_FRAME_OPTIONS = 'ALLOWALL'  # or None
```

### ASP.NET / .NET Core

**Problem:** Often configured with X-Frame-Options

**Solution 1: Configure in Startup.cs**

```csharp
public void Configure(IApplicationBuilder app)
{
    app.Use(async (context, next) =>
    {
        context.Response.Headers.Add(
            "Content-Security-Policy", 
            "frame-ancestors 'self' https://your-wordpress-site.com"
        );
        context.Response.Headers.Remove("X-Frame-Options");
        await next();
    });
    
    // ... rest of configuration
}
```

**Solution 2: Web.config**

```xml
<system.webServer>
  <httpProtocol>
    <customHeaders>
      <remove name="X-Frame-Options" />
      <add name="Content-Security-Policy" 
           value="frame-ancestors 'self' https://your-wordpress-site.com" />
    </customHeaders>
  </httpProtocol>
</system.webServer>
```

### Ruby on Rails

**Problem:** Rails 5+ sets `X-Frame-Options: SAMEORIGIN` by default

**Solution 1: Allow Specific Domain**

In `config/application.rb`:
```ruby
config.action_dispatch.default_headers = {
  'Content-Security-Policy' => "frame-ancestors 'self' https://your-wordpress-site.com"
}
```

**Solution 2: Remove Restriction**

```ruby
config.action_dispatch.default_headers.delete('X-Frame-Options')
```

### Apache Web Server

**Problem:** Security config may add X-Frame-Options

**Solution 1: Allow Specific Domain**

In `.htaccess` or Apache config:
```apache
Header set Content-Security-Policy "frame-ancestors 'self' https://your-wordpress-site.com"
Header unset X-Frame-Options
```

**Solution 2: Same Origin**

```apache
Header always set X-Frame-Options "SAMEORIGIN"
```

### Nginx

**Problem:** Security config may add X-Frame-Options

**Solution 1: Allow Specific Domain**

In nginx config:
```nginx
add_header Content-Security-Policy "frame-ancestors 'self' https://your-wordpress-site.com" always;
```

**Solution 2: Remove Header**

```nginx
add_header X-Frame-Options "" always;
```

Or simply don't add it.

### Node.js / Express

**Problem:** Helmet.js or security middleware sets X-Frame-Options

**Solution 1: Configure Helmet**

```javascript
const helmet = require('helmet');

app.use(
  helmet.contentSecurityPolicy({
    directives: {
      frameAncestors: ["'self'", "https://your-wordpress-site.com"]
    }
  })
);

// Disable X-Frame-Options (CSP replaces it)
app.use(helmet({ frameguard: false }));
```

**Solution 2: Custom Middleware**

```javascript
app.use((req, res, next) => {
  res.setHeader(
    'Content-Security-Policy',
    "frame-ancestors 'self' https://your-wordpress-site.com"
  );
  next();
});
```

### Kubernetes / Ingress

**Problem:** Ingress controller may inject security headers

**Solution: Configure Ingress Annotations**

```yaml
apiVersion: networking.k8s.io/v1
kind: Ingress
metadata:
  name: my-app-ingress
  annotations:
    nginx.ingress.kubernetes.io/configuration-snippet: |
      add_header Content-Security-Policy "frame-ancestors 'self' https://your-wordpress-site.com" always;
```

### WAF / Load Balancers

**Problem:** Web Application Firewall may inject headers

**Solution:** Configure WAF rules to:
1. Not inject X-Frame-Options for internal apps
2. Or whitelist your WordPress domain
3. Check AWS WAF, Cloudflare, F5, etc. documentation

## Best Practices

### ✅ Recommended Approach

**Use Content-Security-Policy (CSP) instead of X-Frame-Options:**

```
Content-Security-Policy: frame-ancestors 'self' https://your-wordpress.com https://www.your-wordpress.com
```

**Benefits:**
- ✅ More modern and flexible
- ✅ Can specify multiple allowed domains
- ✅ Better browser support for fine-grained control
- ✅ Doesn't break other security features

### 🔒 Security Considerations

**For Internal Apps Behind Firewall:**
- Okay to remove X-Frame-Options entirely
- Only accessible to authenticated users anyway
- Network security provides protection

**For Internet-Facing Apps:**
- Use CSP with specific domains
- Don't use `frame-ancestors *` (allows anyone)
- List only trusted domains

**For Sensitive Apps:**
- Keep X-Frame-Options if not embedding
- Only relax for specific routes if needed
- Consider authentication in addition to headers

## Testing Your Changes

### 1. Check Headers After Config Change

```bash
curl -I https://your-enterprise-app.com
```

Look for:
- `Content-Security-Policy: frame-ancestors ...` (good!)
- No `X-Frame-Options: DENY` (good!)

### 2. Test in Web Embed Builder

1. Go to WordPress → Web Embed → Builder
2. Enter your enterprise app URL
3. Click "Generate Preview & Shortcode"
4. You should now see your app in the preview!

### 3. Clear Browser Cache

After changing headers:
1. Hard refresh (Ctrl+F5 or Cmd+Shift+R)
2. Or clear browser cache
3. Headers are sometimes cached

## Common Mistakes

### ❌ Mistake 1: Setting Both Headers

Don't do this:
```
X-Frame-Options: ALLOW-FROM https://wordpress.com  ← Deprecated
Content-Security-Policy: frame-ancestors ...        ← Modern
```

**Fix:** Only use CSP, remove X-Frame-Options

### ❌ Mistake 2: Wrong CSP Syntax

Wrong:
```
Content-Security-Policy: X-Frame-Options: SAMEORIGIN
```

Right:
```
Content-Security-Policy: frame-ancestors 'self'
```

### ❌ Mistake 3: Not Restarting App

After config changes, remember to:
- Restart your application server
- Reload nginx/apache config
- Clear application cache

## Troubleshooting

### Still Not Working?

**Check 1: Multiple Headers**
```bash
curl -I https://your-app.com | grep -i frame
```

Make sure there aren't multiple conflicting headers.

**Check 2: Reverse Proxy**

If you have multiple layers (App → Nginx → Load Balancer):
- Each layer could add headers
- Check all configs
- Last header wins (usually)

**Check 3: Application Framework**

Some frameworks override server configs:
- Check application code
- Look for security middleware
- Search for "X-Frame-Options" in codebase

**Check 4: Browser Extension**

Some security extensions block embedding:
- Test in incognito/private mode
- Disable extensions temporarily

## Quick Reference

| Scenario | Recommended Setting |
|----------|---------------------|
| Internal dashboard (firewall-protected) | Remove X-Frame-Options entirely |
| Internet-facing tool | `CSP: frame-ancestors 'self' https://wordpress.com` |
| Multi-domain WordPress | `CSP: frame-ancestors 'self' https://wp1.com https://wp2.com` |
| Same domain only | `X-Frame-Options: SAMEORIGIN` |
| Development/testing | Remove all restrictions |

## Getting Help

### If You're Stuck:

1. **Check browser console** - Shows exact error
2. **Check network tab** - Shows actual headers received
3. **Search for your framework** - "[Framework] X-Frame-Options disable"
4. **Ask your DevOps team** - They may have set security policies
5. **Check WAF/CDN settings** - May be injecting headers

### Contact Your Enterprise App Vendor

If it's a third-party enterprise app:
1. Contact vendor support
2. Ask about "embedding in iframe/object"
3. Request documentation on X-Frame-Options configuration
4. Many vendors have "embedding guide" documentation

## Summary

✅ **Your enterprise apps CAN be embedded** - you just need to configure them
✅ **Use CSP frame-ancestors** - Modern, flexible, secure
✅ **Test with Web Embed Builder** - See exactly what users will see
✅ **Internal apps are safer** - Behind firewall, less risk
✅ **Document your changes** - Help future you remember the config

The Web Embed plugin will work great for your internal enterprise apps once you configure the headers! 🚀

