# GitHub Setup Guide

This guide will help you push your plugin to GitHub and set up releases for the first time.

## Prerequisites

- Git installed on your system
- GitHub account
- Access to https://github.com/DroppedLink/Graylog-Search repository

## Step 1: Verify Your Current Location

Make sure you're in the plugin directory:

```bash
cd /Users/stephenwhite/Code/wordpress/plugins/graylog-search
pwd  # Should show: /Users/stephenwhite/Code/wordpress/plugins/graylog-search
```

## Step 2: Check Current Git Status

```bash
# Check if git is already initialized
git status

# If you see errors, initialize git:
git init
```

## Step 3: Check/Set Git Remote

```bash
# Check current remote
git remote -v

# If no remote exists, add it:
git remote add origin https://github.com/DroppedLink/Graylog-Search.git

# If remote exists but is wrong, update it:
git remote set-url origin https://github.com/DroppedLink/Graylog-Search.git
```

## Step 4: Review Files to Commit

```bash
# See what will be committed
git status

# Review specific file changes
git diff graylog-search.php
```

## Step 5: Stage All Files

```bash
# Add all files (respects .gitignore)
git add .

# Verify what's staged
git status
```

Expected staged files:
- `graylog-search.php` (updated headers)
- `.gitattributes` (new)
- `.gitignore` (new)
- `.github/workflows/release.yml` (new)
- `README.md` (new)
- `readme.txt` (new)
- `CHANGELOG.md` (new)
- `RELEASE_GUIDE.md` (new)
- All plugin files (includes/, assets/, src/, etc.)

## Step 6: Commit Changes

```bash
git commit -m "Initial release v1.0.0

- Complete WordPress plugin structure
- GitHub updater integration
- Automated release workflow
- Comprehensive documentation"
```

## Step 7: Push to GitHub

### Option A: Fresh Start (Recommended)

This will **delete everything** in the current GitHub repo and replace it with your local version:

```bash
# Force push to master (WARNING: Destructive!)
git push -f origin master
```

⚠️ **WARNING**: This deletes all existing releases, branches, and content in the GitHub repo!

### Option B: Safe Push (If You Want to Keep Existing Content)

```bash
# Try normal push first
git push origin master

# If rejected due to conflicts, pull and merge first:
git pull origin master --allow-unrelated-histories
# Resolve any conflicts
git push origin master
```

## Step 8: Verify Push

1. Visit https://github.com/DroppedLink/Graylog-Search
2. Verify files are present at the **root level**:
   - `graylog-search.php`
   - `README.md`
   - `includes/`, `assets/`, `src/` directories
   - `.github/workflows/release.yml`

## Step 9: Create First Release

Now create your first release on GitHub:

### Using GitHub Website (Easiest)

1. Go to https://github.com/DroppedLink/Graylog-Search/releases
2. Click **"Draft a new release"**
3. Fill in:
   - **Tag**: `v1.0.0`
   - **Target**: `master`
   - **Title**: `v1.0.0 - Initial Release`
   - **Description**:
     ```markdown
     ## 🎉 Initial Release
     
     First public release of Graylog Search WordPress Plugin.
     
     ### Features
     
     - Complete Graylog log search interface
     - IP enrichment with DNS lookups
     - Timezone conversion
     - Saved searches and history
     - Keyboard shortcuts
     - Dark mode
     - Export to CSV/JSON/TXT
     - Auto-updates from GitHub
     - WordPress shortcode support
     
     ### Installation
     
     1. Download `graylog-search.zip` below
     2. Upload to WordPress via Plugins → Add New → Upload
     3. Activate and configure at Graylog Search → Settings
     
     ### Requirements
     
     - WordPress 5.0+
     - PHP 7.2+
     - Graylog 6.1+ with API access
     ```
4. Click **"Publish release"**
5. Wait 1-2 minutes for GitHub Actions to build the ZIP
6. Refresh the page - you should see `graylog-search.zip` in Assets

### Using GitHub CLI (Alternative)

If you have `gh` CLI installed:

```bash
gh release create v1.0.0 \
  --title "v1.0.0 - Initial Release" \
  --notes "First public release. See CHANGELOG.md for details." \
  --target master
```

## Step 10: Test the Release

1. **Download the ZIP**:
   - Go to https://github.com/DroppedLink/Graylog-Search/releases/latest
   - Download `graylog-search.zip`

2. **Verify ZIP contents**:
   ```bash
   unzip -l graylog-search.zip | head -20
   ```
   
   Should show:
   - `graylog-search/graylog-search.php`
   - `graylog-search/includes/`
   - `graylog-search/assets/`
   - Should NOT show: `.git`, `.github`, `*.md` files

3. **Test installation**:
   - Install on a WordPress test site
   - Verify it activates without errors
   - Check version shows 1.0.0

## Step 11: Test Auto-Updates

1. **On a test WordPress site** with the plugin installed:
   - Go to **Graylog Search → Settings**
   - Click **"Check for Updates"** button
   - Should show: "You have the latest version (1.0.0)"

2. **When you release v1.1.0** (in the future):
   - The same site should show an update notification
   - Test the one-click update

## Common Issues & Solutions

### Issue: "Failed to push some refs"

```bash
# Solution: Force push (if you want to overwrite remote)
git push -f origin master

# Or pull and merge first
git pull origin master --allow-unrelated-histories
git push origin master
```

### Issue: ZIP not appearing in release

1. Go to **Actions** tab in GitHub
2. Find the "Build and Release Plugin" workflow
3. Check the logs for errors
4. Common fix: Re-create the release (delete and recreate with same tag)

### Issue: Auto-updates not working

1. Verify plugin headers in `graylog-search.php`:
   - Version: 1.0.0
   - Update URI: https://github.com/DroppedLink/Graylog-Search

2. Check GitHub API:
   ```bash
   curl https://api.github.com/repos/DroppedLink/Graylog-Search/releases/latest
   ```
   Should return JSON with tag_name, assets, etc.

3. Clear WordPress update cache:
   - Go to WordPress admin
   - Go to Dashboard → Updates
   - WordPress will refresh plugin update data

### Issue: "Permission denied (publickey)"

If you get SSH key errors:

```bash
# Use HTTPS instead
git remote set-url origin https://github.com/DroppedLink/Graylog-Search.git

# Or set up SSH keys:
# https://docs.github.com/en/authentication/connecting-to-github-with-ssh
```

## Ongoing Workflow

After initial setup, for future releases:

1. **Make changes** to plugin code
2. **Update version** in `graylog-search.php`
3. **Update CHANGELOG.md** with changes
4. **Commit and push**:
   ```bash
   git add .
   git commit -m "Version 1.1.0 - New features"
   git push origin master
   ```
5. **Create release** on GitHub (same as Step 9)
6. **GitHub Actions builds ZIP automatically**
7. **Users get update notifications**

See [RELEASE_GUIDE.md](RELEASE_GUIDE.md) for detailed release procedures.

## Quick Reference Commands

```bash
# Check status
git status

# Stage all changes
git add .

# Commit
git commit -m "Your message"

# Push
git push origin master

# Force push (destructive!)
git push -f origin master

# View remote
git remote -v

# Check current branch
git branch

# Switch to master
git checkout master

# Pull latest
git pull origin master
```

## Need Help?

- GitHub Docs: https://docs.github.com/
- Git Docs: https://git-scm.com/doc
- GitHub Issues: https://github.com/DroppedLink/Graylog-Search/issues

## Next Steps

After completing this setup:

1. ✅ Plugin is on GitHub at root level
2. ✅ First release (v1.0.0) is created
3. ✅ ZIP file is available for download
4. ✅ Auto-update system is active
5. ✅ GitHub Actions workflow is running

You're ready to accept users and issue updates!

For future releases, see [RELEASE_GUIDE.md](RELEASE_GUIDE.md).

