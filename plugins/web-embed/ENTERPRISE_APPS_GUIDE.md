# Enterprise Apps Configuration Guide

Complete guide to configuring your internal applications to work with Web Embed.

## The Problem

Most enterprise web frameworks block embedding by default for security. They send HTTP headers that prevent your content from being displayed in `<iframe>`, `<object>`, or `<embed>` tags.

The two main headers are:
- **X-Frame-Options** (older, simpler)
- **Content-Security-Policy** (modern, more flexible)

## Quick Diagnosis

### Test if Your App Blocks Embedding

1. Try embedding your URL in the Web Embed builder
2. If you see an empty preview, check headers:

```bash
curl -I https://your-app.company.com
```

Look for:
```
X-Frame-Options: DENY
X-Frame-Options: SAMEORIGIN
Content-Security-Policy: frame-ancestors 'none'
Content-Security-Policy: frame-ancestors 'self'
```

### Browser Console Check

1. Open browser DevTools (F12)
2. Try to load the embed
3. Look for errors like:
   ```
   Refused to display 'https://app.company.com' in a frame
   because it set 'X-Frame-Options' to 'DENY'.
   ```

## Solutions by Platform

### Spring Boot (Java)

#### Option 1: Configuration Class

Create or update security configuration:

```java
import org.springframework.security.config.annotation.web.builders.HttpSecurity;
import org.springframework.security.config.annotation.web.configuration.WebSecurityConfigurerAdapter;

@Configuration
public class SecurityConfig extends WebSecurityConfigurerAdapter {
    
    @Override
    protected void configure(HttpSecurity http) throws Exception {
        http
            .headers()
                .frameOptions()
                    .sameOrigin()  // Allow same domain
                    // OR
                    .disable()      // Allow all (use with caution)
                ;
    }
}
```

#### Option 2: Allow Specific Origin

```java
http
    .headers()
        .addHeaderWriter(new XFrameOptionsHeaderWriter(
            new WhiteListedAllowFromStrategy(
                Arrays.asList("https://wordpress.company.com")
            )
        ));
```

#### Option 3: Application Properties

```properties
# Spring Boot 2.x+
spring.security.headers.frame=false

# Or more specific
server.servlet.session.cookie.same-site=none
```

### Django (Python)

#### Option 1: Settings.py

```python
# settings.py

# Disable X-Frame-Options (allow all)
X_FRAME_OPTIONS = None

# OR allow same origin only
X_FRAME_OPTIONS = 'SAMEORIGIN'

# OR use CSP for more control
CSP_FRAME_ANCESTORS = ("'self'", "https://wordpress.company.com")
```

#### Option 2: Middleware

```python
# middleware.py
class XFrameOptionsMiddleware:
    def __init__(self, get_response):
        self.get_response = get_response
    
    def __call__(self, request):
        response = self.get_response(request)
        # Remove X-Frame-Options header
        if 'X-Frame-Options' in response:
            del response['X-Frame-Options']
        # Add CSP header
        response['Content-Security-Policy'] = (
            "frame-ancestors 'self' https://wordpress.company.com"
        )
        return response

# settings.py
MIDDLEWARE = [
    # ... other middleware
    'yourapp.middleware.XFrameOptionsMiddleware',
]
```

#### Option 3: Decorator for Specific Views

```python
from django.views.decorators.clickjacking import xframe_options_exempt

@xframe_options_exempt
def dashboard_view(request):
    # This view allows embedding
    return render(request, 'dashboard.html')
```

### ASP.NET / .NET Core (C#)

#### Option 1: Web.config

```xml
<system.webServer>
  <httpProtocol>
    <customHeaders>
      <!-- Remove X-Frame-Options -->
      <remove name="X-Frame-Options" />
      
      <!-- Or set to SAMEORIGIN -->
      <add name="X-Frame-Options" value="SAMEORIGIN" />
      
      <!-- Modern CSP approach -->
      <add name="Content-Security-Policy" 
           value="frame-ancestors 'self' https://wordpress.company.com" />
    </customHeaders>
  </httpProtocol>
</system.webServer>
```

#### Option 2: Startup.cs (.NET Core)

```csharp
public void Configure(IApplicationBuilder app)
{
    app.Use(async (context, next) =>
    {
        // Remove X-Frame-Options
        context.Response.Headers.Remove("X-Frame-Options");
        
        // Add CSP header
        context.Response.Headers.Add(
            "Content-Security-Policy",
            "frame-ancestors 'self' https://wordpress.company.com"
        );
        
        await next();
    });
    
    // ... rest of configuration
}
```

#### Option 3: Controller Attribute

```csharp
[HttpGet]
[FrameOptions(FrameOptionsPolicy.AllowAll)]  // Or .SameOrigin
public IActionResult Dashboard()
{
    return View();
}
```

### Express.js (Node.js)

#### Option 1: Helmet Middleware

```javascript
const helmet = require('helmet');

// Remove X-Frame-Options
app.use(helmet({
    frameguard: false
}));

// Or allow same origin
app.use(helmet({
    frameguard: { action: 'sameorigin' }
}));

// Or use CSP
app.use(helmet.contentSecurityPolicy({
    directives: {
        frameAncestors: ["'self'", "https://wordpress.company.com"]
    }
}));
```

#### Option 2: Custom Middleware

```javascript
app.use((req, res, next) => {
    // Remove X-Frame-Options
    res.removeHeader('X-Frame-Options');
    
    // Set CSP
    res.setHeader(
        'Content-Security-Policy',
        "frame-ancestors 'self' https://wordpress.company.com"
    );
    
    next();
});
```

### Ruby on Rails

#### Option 1: Application Controller

```ruby
# app/controllers/application_controller.rb
class ApplicationController < ActionController::Base
  # Disable for all actions
  after_action :allow_iframe
  
  private
  
  def allow_iframe
    response.headers.delete('X-Frame-Options')
    # Or set to SAMEORIGIN
    # response.headers['X-Frame-Options'] = 'SAMEORIGIN'
    
    # Modern CSP approach
    response.headers['Content-Security-Policy'] = 
      "frame-ancestors 'self' https://wordpress.company.com"
  end
end
```

#### Option 2: Config File

```ruby
# config/application.rb
config.action_dispatch.default_headers = {
  'X-Frame-Options' => 'SAMEORIGIN'
  # Or remove it entirely:
  # 'X-Frame-Options' => nil
}
```

#### Option 3: Per-Controller

```ruby
class DashboardController < ApplicationController
  # Allow embedding only for this controller
  after_action :allow_iframe, only: [:show, :index]
  
  def allow_iframe
    response.headers.delete('X-Frame-Options')
  end
end
```

### PHP (Plain / Laravel)

#### Option 1: .htaccess (Apache)

```apache
# Remove X-Frame-Options
Header always unset X-Frame-Options

# Or set to SAMEORIGIN
Header always set X-Frame-Options "SAMEORIGIN"

# Modern CSP
Header always set Content-Security-Policy "frame-ancestors 'self' https://wordpress.company.com"
```

#### Option 2: PHP Headers

```php
<?php
// Remove X-Frame-Options
header_remove('X-Frame-Options');

// Or set it
header('X-Frame-Options: SAMEORIGIN');

// Modern CSP
header("Content-Security-Policy: frame-ancestors 'self' https://wordpress.company.com");
?>
```

#### Option 3: Laravel Middleware

```php
<?php
namespace App\Http\Middleware;

class AllowIframeEmbedding
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        
        // Remove X-Frame-Options
        $response->headers->remove('X-Frame-Options');
        
        // Add CSP
        $response->headers->set(
            'Content-Security-Policy',
            "frame-ancestors 'self' https://wordpress.company.com"
        );
        
        return $response;
    }
}

// Register in Kernel.php
protected $middleware = [
    \App\Http\Middleware\AllowIframeEmbedding::class,
];
```

### NGINX Configuration

If using NGINX as reverse proxy:

```nginx
server {
    listen 80;
    server_name app.company.com;
    
    location / {
        # Remove X-Frame-Options if set by backend
        proxy_hide_header X-Frame-Options;
        
        # Add header
        add_header X-Frame-Options "SAMEORIGIN" always;
        
        # Or use CSP
        add_header Content-Security-Policy "frame-ancestors 'self' https://wordpress.company.com" always;
        
        proxy_pass http://backend;
    }
}
```

### Apache Configuration

If using Apache:

```apache
<VirtualHost *:80>
    ServerName app.company.com
    
    # Remove header if set by application
    Header unset X-Frame-Options
    
    # Set header
    Header always set X-Frame-Options "SAMEORIGIN"
    
    # Or use CSP
    Header always set Content-Security-Policy "frame-ancestors 'self' https://wordpress.company.com"
    
    ProxyPass / http://backend:8080/
</VirtualHost>
```

## Security Considerations

### Option 1: SAMEORIGIN (Most Restrictive)

```
X-Frame-Options: SAMEORIGIN
```

**Allows:** Only your own domain
**Best for:** Internal apps that share the same domain as WordPress
**Example:** Both on `company.com`

### Option 2: Specific Origins (Recommended)

```
Content-Security-Policy: frame-ancestors 'self' https://wordpress.company.com https://wp.company.com
```

**Allows:** Specific domains you list
**Best for:** Internal apps on different subdomains
**Most Secure:** Only trusted domains can embed

### Option 3: Disable (Use with Caution)

```
# No X-Frame-Options header
# OR
Content-Security-Policy: frame-ancestors *
```

**Allows:** Any site to embed your content
**Best for:** Public content intentionally designed for embedding
**Warning:** Anyone can embed your app

## Recommended Approach

1. **Identify WordPress Domain:** e.g., `wordpress.company.com`
2. **Use CSP with Specific Origins:**
   ```
   Content-Security-Policy: frame-ancestors 'self' https://wordpress.company.com
   ```
3. **Test Thoroughly:** Verify embedding works
4. **Monitor:** Check for unauthorized embedding attempts

## Testing Your Configuration

### 1. Check Headers

```bash
curl -I https://your-app.company.com
```

Should NOT see:
```
X-Frame-Options: DENY
```

Should see (one of):
```
X-Frame-Options: SAMEORIGIN
# OR
Content-Security-Policy: frame-ancestors 'self' https://wordpress.company.com
```

### 2. Test in Browser

1. Create embed in Web Embed builder
2. Check preview
3. Should load successfully

### 3. Browser Console

Check for no frame errors:
- Open DevTools (F12)
- Look for "Refused to display" errors
- Should be none

## Common Issues

### "Still Not Working After Configuration"

**Checklist:**
- ✅ Restarted application after config change
- ✅ Cleared browser cache
- ✅ Verified headers with `curl -I`
- ✅ Checked for reverse proxy override
- ✅ Tested with HTTPS (not HTTP)

### "Works on Some Pages, Not Others"

**Cause:** Per-page or per-route configuration

**Solution:** Apply configuration globally, not per-route

### "Works Locally, Not in Production"

**Causes:**
- Load balancer adding headers
- CDN adding headers
- WAF (Web Application Firewall) blocking

**Solution:** Check infrastructure layer configurations

## Need More Help?

If your platform isn't listed or you need specific help:

1. Identify your web framework
2. Search for: "[framework name] disable X-Frame-Options"
3. Check your framework's security documentation
4. Test with simple curl commands first

## Security Best Practices

1. **Use HTTPS:** Always use HTTPS for both WordPress and embedded apps
2. **Specific Origins:** List exact domains, not wildcards
3. **Least Privilege:** Only allow embedding where needed
4. **Regular Audits:** Review embedded content periodically
5. **Authentication:** Ensure apps still require proper authentication
6. **Session Management:** Be careful with session cookies and embedding

Remember: Allowing embedding doesn't bypass authentication. Users still need proper credentials to see authenticated content.

