# Fallback Templates - For Apps You Can't Embed

When you can't modify the remote application to allow embedding, use these professional fallback templates instead.

## Why Use Fallbacks?

**Reality:** Many enterprise apps block embedding and you can't change their configuration.

**Solution:** Make the "blocked" experience actually useful with professional fallback messages.

## Template 1: Simple Launch Button

**Best for:** Quick access to single apps

```
[web_embed url="https://your-enterprise-app.com" 
          fallback="<div style='text-align:center; padding:50px 20px; background:#f8f9fa; border:2px solid #dee2e6; border-radius:8px;'>
              <h2 style='margin-top:0; color:#333;'>Enterprise Dashboard</h2>
              <p style='font-size:16px; color:#666; margin:20px 0;'>This application requires direct access.</p>
              <a href='https://your-enterprise-app.com' target='_blank' rel='noopener' style='display:inline-block; padding:15px 40px; background:#0073aa; color:white; text-decoration:none; border-radius:5px; font-weight:600; font-size:16px;'>
                Open Dashboard →
              </a>
          </div>"]
```

## Template 2: Informative Card

**Best for:** Explaining why direct access is needed

```
[web_embed url="https://monitoring.internal.com"
          fallback="<div style='max-width:600px; margin:0 auto; padding:30px; background:white; border:1px solid #ddd; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.1);'>
              <div style='text-align:center; margin-bottom:20px;'>
                  <span style='font-size:48px;'>📊</span>
              </div>
              <h2 style='text-align:center; color:#2c3e50; margin:0 0 10px 0;'>Monitoring Dashboard</h2>
              <p style='text-align:center; color:#7f8c8d; margin:0 0 25px 0;'>Real-time system metrics and alerts</p>
              <div style='background:#f8f9fa; padding:15px; border-radius:5px; margin-bottom:20px;'>
                  <p style='margin:0; font-size:14px; color:#666;'>
                      <strong>Note:</strong> This dashboard must be accessed directly for security and authentication purposes.
                  </p>
              </div>
              <div style='text-align:center;'>
                  <a href='https://monitoring.internal.com' target='_blank' rel='noopener' style='display:inline-block; padding:12px 35px; background:#27ae60; color:white; text-decoration:none; border-radius:5px; font-weight:600;'>
                      Launch Monitoring →
                  </a>
              </div>
              <p style='text-align:center; margin:20px 0 0 0; font-size:12px; color:#95a5a6;'>
                  Login with your enterprise credentials
              </p>
          </div>"]
```

## Template 3: Multi-Feature Card

**Best for:** Apps with multiple features to highlight

```
[web_embed url="https://analytics.internal.com"
          fallback="<div style='max-width:700px; margin:0 auto; padding:40px 30px; background:linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius:12px; color:white;'>
              <h2 style='margin:0 0 15px 0; font-size:28px;'>Analytics Platform</h2>
              <p style='margin:0 0 30px 0; font-size:16px; opacity:0.9;'>Access powerful data insights and reporting tools</p>
              
              <div style='display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:30px;'>
                  <div style='background:rgba(255,255,255,0.1); padding:15px; border-radius:8px;'>
                      <div style='font-size:24px; margin-bottom:8px;'>📈</div>
                      <div style='font-weight:600; margin-bottom:5px;'>Real-time Reports</div>
                      <div style='font-size:14px; opacity:0.8;'>Live data dashboards</div>
                  </div>
                  <div style='background:rgba(255,255,255,0.1); padding:15px; border-radius:8px;'>
                      <div style='font-size:24px; margin-bottom:8px;'>🎯</div>
                      <div style='font-weight:600; margin-bottom:5px;'>Custom Queries</div>
                      <div style='font-size:14px; opacity:0.8;'>Build your own views</div>
                  </div>
                  <div style='background:rgba(255,255,255,0.1); padding:15px; border-radius:8px;'>
                      <div style='font-size:24px; margin-bottom:8px;'>📊</div>
                      <div style='font-weight:600; margin-bottom:5px;'>Data Export</div>
                      <div style='font-size:14px; opacity:0.8;'>Download reports</div>
                  </div>
                  <div style='background:rgba(255,255,255,0.1); padding:15px; border-radius:8px;'>
                      <div style='font-size:24px; margin-bottom:8px;'>🔔</div>
                      <div style='font-weight:600; margin-bottom:5px;'>Alerts</div>
                      <div style='font-size:14px; opacity:0.8;'>Set up notifications</div>
                  </div>
              </div>
              
              <div style='text-align:center;'>
                  <a href='https://analytics.internal.com' target='_blank' rel='noopener' style='display:inline-block; padding:15px 45px; background:white; color:#667eea; text-decoration:none; border-radius:8px; font-weight:700; font-size:16px; box-shadow:0 4px 12px rgba(0,0,0,0.2);'>
                      Open Analytics Platform
                  </a>
              </div>
          </div>"]
```

## Template 4: Security-Focused Message

**Best for:** Sensitive applications where security is paramount

```
[web_embed url="https://secure-app.internal.com"
          fallback="<div style='max-width:600px; margin:0 auto; padding:35px; background:#fff; border:2px solid #f39c12; border-radius:10px;'>
              <div style='text-align:center; margin-bottom:20px;'>
                  <span style='display:inline-block; width:60px; height:60px; background:#f39c12; border-radius:50%; line-height:60px; font-size:30px;'>🔒</span>
              </div>
              <h2 style='text-align:center; color:#2c3e50; margin:0 0 15px 0;'>Secure Access Required</h2>
              <p style='text-align:center; color:#7f8c8d; margin:0 0 25px 0; font-size:16px;'>
                  This application requires secure, direct access for compliance and security reasons.
              </p>
              <div style='background:#fff3cd; border-left:4px solid #f39c12; padding:15px; margin-bottom:25px;'>
                  <p style='margin:0; font-size:14px; color:#856404;'>
                      <strong>Security Notice:</strong> Authentication and session management require direct browser access. Embedded access is not supported.
                  </p>
              </div>
              <div style='text-align:center;'>
                  <a href='https://secure-app.internal.com' target='_blank' rel='noopener' style='display:inline-block; padding:14px 40px; background:#e74c3c; color:white; text-decoration:none; border-radius:6px; font-weight:600; font-size:15px;'>
                      Launch Secure App →
                  </a>
              </div>
              <p style='text-align:center; margin:25px 0 0 0; font-size:13px; color:#95a5a6;'>
                  ✓ Single Sign-On (SSO) enabled<br>
                  ✓ Multi-factor authentication (MFA) required
              </p>
          </div>"]
```

## Template 5: Grid of Related Apps

**Best for:** Portal pages with multiple apps

```
[web_embed url="https://portal.internal.com"
          fallback="<div style='padding:40px 20px; background:#f8f9fa;'>
              <h2 style='text-align:center; margin:0 0 10px 0; color:#2c3e50;'>Enterprise Applications</h2>
              <p style='text-align:center; margin:0 0 40px 0; color:#7f8c8d;'>Click to launch any application</p>
              
              <div style='display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:20px; max-width:900px; margin:0 auto;'>
                  
                  <a href='https://monitoring.internal.com' target='_blank' rel='noopener' style='display:block; padding:25px; background:white; border-radius:10px; text-decoration:none; color:#2c3e50; box-shadow:0 2px 8px rgba(0,0,0,0.1); transition:transform 0.2s;'>
                      <div style='font-size:40px; margin-bottom:15px;'>📊</div>
                      <div style='font-weight:700; font-size:18px; margin-bottom:8px;'>Monitoring</div>
                      <div style='font-size:14px; color:#7f8c8d;'>System metrics</div>
                  </a>
                  
                  <a href='https://analytics.internal.com' target='_blank' rel='noopener' style='display:block; padding:25px; background:white; border-radius:10px; text-decoration:none; color:#2c3e50; box-shadow:0 2px 8px rgba(0,0,0,0.1);'>
                      <div style='font-size:40px; margin-bottom:15px;'>📈</div>
                      <div style='font-weight:700; font-size:18px; margin-bottom:8px;'>Analytics</div>
                      <div style='font-size:14px; color:#7f8c8d;'>Data insights</div>
                  </a>
                  
                  <a href='https://logs.internal.com' target='_blank' rel='noopener' style='display:block; padding:25px; background:white; border-radius:10px; text-decoration:none; color:#2c3e50; box-shadow:0 2px 8px rgba(0,0,0,0.1);'>
                      <div style='font-size:40px; margin-bottom:15px;'>📝</div>
                      <div style='font-weight:700; font-size:18px; margin-bottom:8px;'>Logs</div>
                      <div style='font-size:14px; color:#7f8c8d;'>Search logs</div>
                  </a>
                  
                  <a href='https://admin.internal.com' target='_blank' rel='noopener' style='display:block; padding:25px; background:white; border-radius:10px; text-decoration:none; color:#2c3e50; box-shadow:0 2px 8px rgba(0,0,0,0.1);'>
                      <div style='font-size:40px; margin-bottom:15px;'>⚙️</div>
                      <div style='font-weight:700; font-size:18px; margin-bottom:8px;'>Admin</div>
                      <div style='font-size:14px; color:#7f8c8d;'>Settings</div>
                  </a>
                  
              </div>
              
              <p style='text-align:center; margin:40px 0 0 0; font-size:14px; color:#95a5a6;'>
                  All applications require authentication
              </p>
          </div>"]
```

## Template 6: Minimal "Coming Soon" Style

**Best for:** Apps being set up or testing

```
[web_embed url="https://new-app.internal.com"
          fallback="<div style='text-align:center; padding:80px 20px; background:#ecf0f1; border-radius:10px;'>
              <div style='font-size:64px; margin-bottom:20px;'>🚀</div>
              <h2 style='margin:0 0 10px 0; color:#2c3e50; font-size:32px;'>Dashboard Access</h2>
              <p style='margin:0 0 35px 0; color:#7f8c8d; font-size:18px;'>Launch in new window</p>
              <a href='https://new-app.internal.com' target='_blank' rel='noopener' style='display:inline-block; padding:16px 50px; background:#3498db; color:white; text-decoration:none; border-radius:50px; font-weight:600; font-size:16px; letter-spacing:0.5px;'>
                  OPEN APP
              </a>
          </div>"]
```

## Usage Tips

### 1. Customize the Style

Match your WordPress theme colors:
```
background:#YOUR-THEME-COLOR;
color:#YOUR-TEXT-COLOR;
```

### 2. Add Your Branding

```html
<img src="https://yoursite.com/logo.png" style="width:120px; margin-bottom:20px;" />
```

### 3. Include Instructions

Add specific login or access instructions:
```html
<p style="...">
    <strong>Access Instructions:</strong><br>
    1. Click the button below<br>
    2. Log in with your corporate email<br>
    3. Use your authenticator app for 2FA
</p>
```

### 4. Track Clicks (Optional)

Add Google Analytics tracking:
```html
<a href="..." onclick="gtag('event', 'click', {'event_category': 'enterprise_apps', 'event_label': 'monitoring'});">
```

## Best Practices

### ✅ DO:
- Make buttons obvious and clickable
- Explain WHY direct access is needed
- Provide clear instructions
- Use professional styling
- Test on mobile devices
- Include app icons/emojis for visual interest

### ❌ DON'T:
- Make it look like an error message
- Use technical jargon
- Blame the app vendor
- Make it difficult to find the link
- Use tiny fonts or low contrast

## Real-World Example

**Corporate Intranet Portal:**

Create a page called "Enterprise Tools" with multiple fallback shortcodes:

```
<h1>Enterprise Tools</h1>
<p>Access your business applications below</p>

<div class="tools-grid">
  [web_embed url="https://monitoring.corp.com" fallback="...Template 1..."]
  [web_embed url="https://analytics.corp.com" fallback="...Template 2..."]
  [web_embed url="https://logs.corp.com" fallback="...Template 3..."]
</div>
```

Result: Professional portal page where clicking any "card" opens the actual app in a new tab!

## Summary

When you can't embed:
1. ✅ Use professional fallback templates
2. ✅ Make it look intentional, not like an error
3. ✅ Provide clear access path
4. ✅ Add helpful context
5. ✅ Style it to match your site

**Remember:** Sometimes NOT embedding is actually better UX - users get full app features, proper authentication, and better performance!

