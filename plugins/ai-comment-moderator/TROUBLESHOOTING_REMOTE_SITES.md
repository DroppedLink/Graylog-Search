# Troubleshooting Remote Site Connections

## Your Connection Attempt

You tried to add:
- **Site URL**: `https://www.liirk.com`
- **Username**: `sdkjfkjhfd0239q2324jkklhj3rwerjkfh`
- **Password**: `ygXQ VPTH UldY NSH0 tr8z ZPs4`

## Potential Issues to Check

### 1. Username Format
The username `sdkjfkjhfd0239q2324jkklhj3rwerjkfh` looks unusual. WordPress usernames are typically:
- Alphanumeric characters
- Can include underscores, hyphens, and periods
- Case-sensitive
- Usually human-readable (e.g., "admin", "john_doe", "editor123")

**Action**: Verify this is the actual WordPress username on liirk.com, not an API key or other credential.

### 2. Application Password Format
The password `ygXQ VPTH UldY NSH0 tr8z ZPs4` looks correct (24 characters in 6 groups of 4). Application passwords:
- Are exactly 24 characters
- Contain uppercase, lowercase, and numbers
- Are shown with spaces for readability (spaces are optional when entering)

**Action**: This looks valid, but ensure it hasn't been revoked on the remote site.

### 3. Common Connection Issues

#### Issue: HTTP 401 (Authentication Failed)
**Causes**:
- Incorrect username or password
- Application password was revoked
- User doesn't exist on remote site

**Solutions**:
1. Double-check the username (case-sensitive)
2. Generate a new application password on the remote site
3. Verify you're using the WordPress username, not email

#### Issue: HTTP 403 (Forbidden)
**Causes**:
- User lacks sufficient permissions
- REST API is blocked by security plugin
- User role doesn't allow API access

**Solutions**:
1. Use an Administrator account
2. Check security plugins (WordFence, Sucuri, etc.)
3. Verify REST API is accessible at `https://www.liirk.com/wp-json/`

#### Issue: Connection Timeout or DNS Error
**Causes**:
- Site is down or unreachable
- DNS not resolving
- Firewall blocking outbound connections from your server

**Solutions**:
1. Visit `https://www.liirk.com` in a browser to confirm it's online
2. Check your server can make outbound HTTPS connections
3. Contact your hosting provider about firewall rules

#### Issue: SSL Certificate Error
**Causes**:
- Invalid SSL certificate on remote site
- Self-signed certificate
- Expired certificate

**Solutions**:
1. Verify SSL at https://www.ssllabs.com/ssltest/
2. Ensure remote site has valid HTTPS
3. For self-signed certs (dev only), you may need special configuration

## Enhanced Testing Features

The updated plugin now includes:

### 1. Test Connection Button
Before adding a site, click **"Test Connection First"** to verify:
- Site is accessible
- REST API is enabled
- Credentials are valid
- User has proper permissions

### 2. Detailed Error Messages
When a connection fails, you'll see:
- Specific error message (HTTP code, timeout, auth failure, etc.)
- Troubleshooting tips
- Links to documentation

### 3. Connection Success Confirmation
When successful, you'll see:
- Username of connected account
- User's roles on remote site
- Confirmation before site is added

## Testing Your Connection

### Option 1: Use the Test Connection Button (Recommended)
1. Fill in all fields in the "Quick Add Site" form
2. Click **"Test Connection First"** button
3. Wait for the result
4. If successful, click "Add Remote Site"
5. If failed, review the error and make corrections

### Option 2: Use the Standalone Test Script
A diagnostic script is available at:
`plugins/ai-comment-moderator/test-remote-connection.php`

**To use it**:
1. Edit the file and update the credentials at the top
2. Access it via browser (one-time use)
3. Review the detailed test results
4. **DELETE the file after testing** (contains credentials)

The script tests:
- Basic site accessibility
- WordPress REST API availability
- Authentication with your credentials
- Comments API access
- Pending comments count

## How to Generate an Application Password

On the **remote site** (https://www.liirk.com):

1. Log in to WordPress admin
2. Go to **Users → Profile** (or **Users → Your Profile**)
3. Scroll to **Application Passwords** section
4. In the "New Application Password Name" field, enter: `AI Moderator`
5. Click **Add New Application Password**
6. Copy the generated password (shown once only)
7. The password will look like: `xxxx xxxx xxxx xxxx xxxx xxxx`

**Important Notes**:
- Requires WordPress 5.6 or higher
- Requires HTTPS (or localhost for testing)
- Password is shown only once - copy it immediately
- You can revoke it anytime from the same page

## Verifying Your Remote Site

Before trying to connect, manually verify:

### Check 1: Site is Online
```bash
curl -I https://www.liirk.com
# Should return HTTP 200 or 301/302
```

### Check 2: REST API is Enabled
Visit in browser: `https://www.liirk.com/wp-json/`

Should show JSON response like:
```json
{
  "name": "Site Name",
  "description": "...",
  "url": "https://www.liirk.com",
  "home": "https://www.liirk.com",
  ...
}
```

### Check 3: Application Passwords are Enabled
Visit: `https://www.liirk.com/wp-json/wp/v2/users/me`

Without authentication, should return:
```json
{
  "code": "rest_not_logged_in",
  "message": "You are not currently logged in.",
  ...
}
```

This confirms the endpoint exists.

## WordPress Version Requirements

### Remote Site Must Have:
- WordPress 5.6+ (for Application Passwords)
- HTTPS enabled (unless localhost)
- REST API enabled (default, but can be disabled by plugins)
- Comments API accessible (default)

### This Central Site Must Have:
- Ability to make outbound HTTPS connections
- wp_remote_get() and wp_remote_post() not blocked
- cURL or similar enabled

## Security Considerations

### Safe Practices:
✅ Use dedicated user account for API access  
✅ Use Application Passwords (not main password)  
✅ Revoke unused application passwords regularly  
✅ Monitor webhook logs for suspicious activity  
✅ Use HTTPS always  

### Avoid:
❌ Don't use your main administrator account  
❌ Don't share application passwords  
❌ Don't commit passwords to version control  
❌ Don't use HTTP (non-secure)  

## Still Having Issues?

If you've verified all of the above and still can't connect:

1. **Check WordPress Debug Log**:
   - Enable debug logging in `wp-config.php`:
     ```php
     define('WP_DEBUG', true);
     define('WP_DEBUG_LOG', true);
     define('WP_DEBUG_DISPLAY', false);
     ```
   - Check `wp-content/debug.log` for detailed errors

2. **Test with curl** (from command line):
   ```bash
   curl -u "username:app_password" https://www.liirk.com/wp-json/wp/v2/users/me
   ```

3. **Contact Remote Site Admin**:
   - Confirm your username
   - Verify your account has Administrator role
   - Check if they have security restrictions on REST API

4. **Check Your Hosting**:
   - Verify outbound HTTPS is allowed
   - Check if there are IP restrictions
   - Confirm cURL is enabled

## Getting Help

When reporting issues, include:
- Error message from test connection
- WordPress version of both sites
- Any relevant entries from debug.log
- Results from manual REST API test (via browser)
- Your hosting provider (if relevant)

---

**Next Steps**: Try the "Test Connection First" button with your updated plugin and see what specific error message you get. That will help us narrow down the exact issue!

