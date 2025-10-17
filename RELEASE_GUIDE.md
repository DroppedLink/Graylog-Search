# Release Guide for Graylog Search Plugin

This guide explains how to create and publish new releases of the Graylog Search WordPress plugin.

## Overview

The plugin uses GitHub Releases for distribution and automatic updates. When you create a release on GitHub, a GitHub Action automatically builds and attaches the plugin ZIP file.

## Version Numbering

We follow [Semantic Versioning](https://semver.org/):

- **MAJOR.MINOR.PATCH** (e.g., 1.2.3)
  - **MAJOR**: Breaking changes or major rewrites (1.0.0 → 2.0.0)
  - **MINOR**: New features, backwards-compatible (1.0.0 → 1.1.0)
  - **PATCH**: Bug fixes, backwards-compatible (1.0.0 → 1.0.1)

### Examples

- `1.0.0` - Initial release
- `1.1.0` - Added new search features
- `1.1.1` - Fixed bug in search
- `2.0.0` - Complete rewrite with breaking changes

## Release Process

### Step 1: Prepare the Code

1. **Update the version number** in `graylog-search.php`:

```php
/**
 * Version: 1.1.0
 */

define('GRAYLOG_SEARCH_VERSION', '1.1.0');
```

2. **Update CHANGELOG.md** (if you maintain one):

```markdown
## [1.1.0] - 2025-10-17
### Added
- New feature description
### Fixed
- Bug fix description
```

3. **Test thoroughly**:
   - Verify plugin works in WordPress
   - Test all features
   - Check for PHP errors
   - Test with different WordPress versions if possible

4. **Commit changes**:

```bash
git add .
git commit -m "Version 1.1.0 - Description of changes"
git push origin master
```

### Step 2: Create GitHub Release (Web Interface - Recommended)

This is the easiest method for most users:

1. **Go to GitHub Repository**
   - Visit https://github.com/DroppedLink/Graylog-Search

2. **Navigate to Releases**
   - Click "Releases" in the right sidebar
   - Or go directly to: https://github.com/DroppedLink/Graylog-Search/releases

3. **Create New Release**
   - Click "Draft a new release" button

4. **Fill in Release Details**:
   
   **Choose a tag:**
   - Enter tag name: `v1.1.0` (always prefix with 'v')
   - Target: `master` (or your main branch)
   - Click "Create new tag: v1.1.0 on publish"
   
   **Release title:**
   - `v1.1.0 - Brief Description`
   - Example: `v1.1.0 - Added Advanced Search`
   
   **Release description:**
   ```markdown
   ## What's New
   
   - Added advanced search with regex support
   - Improved DNS lookup performance
   - Fixed timezone conversion bug
   
   ## Changes
   
   - Feature: Advanced regex search helper
   - Enhancement: DNS lookup caching
   - Fix: Timezone conversion for DST
   
   ## Installation
   
   Download `graylog-search.zip` below and install via WordPress admin.
   ```

5. **Publish Release**
   - Click "Publish release" button
   - GitHub Actions will automatically build the ZIP (takes 1-2 minutes)

6. **Verify ZIP was Created**
   - Refresh the release page after 1-2 minutes
   - You should see `graylog-search.zip` in the "Assets" section
   - Download and verify it installs correctly in WordPress

### Step 3: Test Auto-Updates

1. **On a test WordPress site** with an older version:
   - Go to **Plugins** page
   - You should see an update notification
   - Click "Update now" to test the auto-update

2. **Manual check** (if notification doesn't appear):
   - Go to **Graylog Search → Settings**
   - Click "Check for Updates" button
   - Verify latest version is detected

## Alternative Method: GitHub CLI

If you prefer command line:

```bash
# Make sure gh CLI is installed: https://cli.github.com/

# Create release with auto-generated notes
gh release create v1.1.0 \
  --title "v1.1.0 - Brief Description" \
  --notes "Release notes here" \
  --target master

# GitHub Actions will build and upload the ZIP automatically
```

## Troubleshooting

### ZIP Not Created

If the ZIP file doesn't appear in the release assets:

1. Go to **Actions** tab in GitHub
2. Find the "Build and Release Plugin" workflow
3. Check the logs for errors
4. Common issues:
   - Invalid tag format (must be `vX.Y.Z`)
   - Workflow file syntax error
   - Permissions issue

### Auto-Updates Not Working

If users don't see update notifications:

1. **Check plugin headers** in `graylog-search.php`:
   - Version number must match release tag (without 'v')
   - Update URI must be correct

2. **Force cache clear** on user's WordPress site:
   ```php
   // In WordPress admin, run this via plugin or theme:
   delete_site_transient('update_plugins');
   wp_update_plugins();
   ```

3. **Check GitHub API**:
   - Visit: `https://api.github.com/repos/DroppedLink/Graylog-Search/releases/latest`
   - Verify it returns the latest release

### Version Mismatch

If version numbers get out of sync:

- Plugin file shows: `1.0.0`
- Release tag shows: `v1.1.0`
- Users will see the update available

**Always ensure** the version in `graylog-search.php` matches your release tag (without the 'v' prefix).

## Release Checklist

Use this checklist for each release:

- [ ] Updated version in `graylog-search.php` (two places)
- [ ] Updated CHANGELOG.md or release notes
- [ ] Tested plugin functionality
- [ ] Committed and pushed all changes
- [ ] Created GitHub release with correct tag format (`vX.Y.Z`)
- [ ] Waited 2 minutes for GitHub Actions to complete
- [ ] Verified ZIP file appears in release assets
- [ ] Downloaded ZIP and tested installation
- [ ] Tested auto-update on a WordPress site
- [ ] Announced release (if applicable)

## Best Practices

1. **Never delete releases** - Users may be on older versions
2. **Use clear release notes** - Help users understand changes
3. **Test before releasing** - Broken releases affect all users
4. **Keep version numbers consistent** - Plugin file must match release tag
5. **Tag format matters** - Always use `vX.Y.Z` format
6. **Check GitHub Actions** - Ensure ZIP builds successfully

## Getting Help

- **GitHub Issues**: https://github.com/DroppedLink/Graylog-Search/issues
- **GitHub Actions Logs**: Check the Actions tab for build errors
- **WordPress Plugin Handbook**: https://developer.wordpress.org/plugins/

## Additional Resources

- [Semantic Versioning](https://semver.org/)
- [GitHub Releases Documentation](https://docs.github.com/en/repositories/releasing-projects-on-github/managing-releases-in-a-repository)
- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [WordPress Plugin Update Checker](https://developer.wordpress.org/plugins/plugin-basics/including-a-software-license/)

