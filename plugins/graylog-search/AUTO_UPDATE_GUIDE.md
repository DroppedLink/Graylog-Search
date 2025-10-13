# Auto-Update Feature Guide

The Graylog Search plugin includes a built-in auto-updater that checks for new versions on GitHub and enables one-click updates.

## How It Works

The plugin automatically integrates with WordPress's native update system to:

1. **Check for Updates**: Periodically checks the GitHub repository for new releases
2. **Display Notifications**: Shows update notifications in the WordPress admin
3. **One-Click Install**: Allows you to update directly from the Plugins page

## Checking for Updates

### Automatic Checks

WordPress automatically checks for plugin updates periodically (usually twice daily). The Graylog Search plugin hooks into this system to check GitHub for new releases.

### Manual Check

You can manually check for updates at any time:

1. Go to **Graylog Search → Settings**
2. Scroll to the **Plugin Updates** section
3. Click **"Check for Updates Now"**
4. The page will refresh and show if an update is available

## Installing Updates

When an update is available, you have two ways to install it:

### Method 1: From Settings Page

1. Go to **Graylog Search → Settings**
2. If an update is available, you'll see a notification showing the new version
3. Click **"Go to Plugins Page to Update"**
4. Click **"Update Now"** on the Plugins page

### Method 2: From Plugins Page

1. Go to **Plugins → Installed Plugins**
2. Look for the update notification on the Graylog Search plugin
3. Click **"Update Now"**
4. WordPress will download and install the update from GitHub

## Update Notifications

The plugin displays update status in two places:

### Settings Page
- Shows current version
- Displays green "✓ You're running the latest version!" if up to date
- Displays red "🎉 Update Available!" with version number if update exists

### WordPress Plugins Page
- Standard WordPress update notification appears in the plugin list
- Shows changelog from the GitHub release

## GitHub Token (Optional)

GitHub limits anonymous API requests to 60 per hour. If you're checking for updates frequently or have multiple installations, you can add a GitHub Personal Access Token to increase this limit to 5,000 per hour.

### Creating a GitHub Token

1. Go to https://github.com/settings/tokens
2. Click **"Generate new token"** → **"Generate new token (classic)"**
3. Give it a name like "WordPress Graylog Plugin"
4. **No scopes needed** - leave all checkboxes unchecked (public repo access only)
5. Click **"Generate token"**
6. Copy the token (starts with `ghp_`)

### Adding Token to Plugin

1. Go to **Graylog Search → Settings**
2. Scroll to **Advanced Settings**
3. Paste your token in the **"GitHub Token"** field
4. Click **"Save Settings"**

**Note**: This is optional and not required for normal use.

## Troubleshooting

### Update Check Fails

If the manual update check fails:

1. **Check Internet Connection**: Ensure your WordPress server can reach GitHub
2. **SSL Certificate Issues**: If you see "cURL error 60", enable the SSL verification bypass option in settings (see below)
3. **GitHub Rate Limit**: Wait an hour or add a GitHub token (see above)
4. **Firewall**: Ensure your firewall allows outbound HTTPS to `api.github.com`

### SSL Certificate Errors

If you see: `cURL error 60: SSL certificate problem: self-signed certificate in certificate chain`

**Solution**:
1. Go to **Graylog Search → Settings**
2. Check **"Disable SSL Certificate Verification"**
3. Click **"Save Settings"**

⚠️ **Security Warning**: Only enable this in trusted environments with self-signed certificates.

### Update Download Fails

If the update notification appears but download fails:

1. Check that your WordPress installation has write permissions to the `wp-content/plugins/` directory
2. Temporarily deactivate the plugin, delete it, and reinstall from the `.zip` file
3. Check WordPress debug logs for specific errors

### Plugin Doesn't Show Update Available

The plugin might not immediately show updates because:

1. **Cache**: WordPress caches update checks. Use "Check for Updates Now" button to force refresh
2. **Transient**: The GitHub release info is cached for 1 hour. Wait an hour or clear WordPress transients
3. **No Release**: Check that a GitHub release was created (not just a tag)

To manually clear cache:
```bash
# In WordPress admin, or via WP-CLI:
wp transient delete graylog_search_github_release
wp transient delete update_plugins
```

## GitHub Releases

Updates are delivered through GitHub Releases. When you push a new version:

1. **Tag the Release**: Use semantic versioning (e.g., `v1.5.0`)
2. **Create Release**: GitHub → Releases → New Release
3. **Attach .zip**: Upload the `graylog-search.zip` file as an asset
4. **Publish**: Plugin will detect it within the hour

### Release Checklist

- [ ] Update version in `graylog-search.php` header
- [ ] Update `GRAYLOG_SEARCH_VERSION` constant
- [ ] Update `CHANGELOG.md`
- [ ] Create `.zip` file: `./scripts/zip-plugin.sh graylog-search`
- [ ] Commit and push to GitHub
- [ ] Create GitHub release with version tag (e.g., `v1.5.0`)
- [ ] Upload `dist/graylog-search.zip` as release asset
- [ ] Publish release

## Technical Details

### How Updates Are Detected

1. Plugin queries GitHub API: `https://api.github.com/repos/DroppedLink/Graylog-Search/releases/latest`
2. Compares remote version tag with local `GRAYLOG_SEARCH_VERSION`
3. If remote is higher, registers update with WordPress
4. WordPress displays standard update notification

### Update Installation Process

1. WordPress downloads the `.zip` file from GitHub release assets (or zipball URL)
2. Extracts to temporary directory
3. Fixes directory name (GitHub uses `repo-name` format, WordPress expects `plugin-slug`)
4. Moves to `wp-content/plugins/graylog-search/`
5. Reactivates plugin if it was active

### Files Involved

- `includes/github-updater.php`: Main updater class
- `graylog-search.php`: Includes updater, defines version constant
- `includes/settings.php`: Update checker UI and AJAX handler

### Hooks Used

- `pre_set_site_transient_update_plugins`: Inject update data
- `plugins_api`: Provide plugin info for update screen
- `upgrader_source_selection`: Fix directory name after download
- `wp_ajax_graylog_check_updates`: Manual update check

## Security

- Update checks use HTTPS only
- GitHub token is stored in WordPress options (database)
- Token is optional and only needed for rate limit increases
- No authentication required for public repository
- SSL verification can be bypassed if needed (with warnings)

## Benefits

- **No WordPress.org submission** required
- **Instant updates** as soon as you create a release
- **Full control** over release timing
- **Standard WordPress UX** - users don't need to learn new processes
- **Automatic notifications** - just like official WordPress plugins

