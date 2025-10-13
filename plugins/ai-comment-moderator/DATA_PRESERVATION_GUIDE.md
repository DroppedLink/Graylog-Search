# Data Preservation Guide

## Overview

The AI Comment Moderator includes a **"Keep Data on Uninstall"** option that allows you to preserve all your settings, prompts, remote sites, and processing history when deleting and reinstalling the plugin.

This is especially useful during development or when testing updates.

## How It Works

### Default Behavior (Recommended)

✅ **"Keep Data on Uninstall" is ENABLED by default**

When you delete the plugin, WordPress will:
- ✅ Remove the plugin files from `wp-content/plugins/`
- ✅ Keep ALL your data in the database
- ✅ Preserve your configuration

When you reinstall:
- ✅ All settings automatically restored
- ✅ Prompts are still there
- ✅ Remote sites still configured
- ✅ Processing history intact
- ✅ No need to reconfigure anything!

### Complete Removal

❌ **Uncheck "Keep Data on Uninstall" to delete everything**

When you delete the plugin, WordPress will:
- ❌ Remove plugin files
- ❌ Delete all database tables
- ❌ Remove all settings
- ❌ Wipe all data completely

This is a **clean slate** - useful when completely removing the plugin.

## What Data Gets Preserved

When "Keep Data on Uninstall" is enabled, the following are preserved:

### Settings
- Ollama URL and selected model
- Batch size preferences
- Auto-process configuration
- Rate limits
- Confidence thresholds
- Reputation thresholds
- Multi-model settings
- Webhook URLs and events

### Database Tables
- `wp_ai_comment_reviews` - All comment moderation history
- `wp_ai_comment_prompts` - Your custom prompts
- `wp_ai_comment_logs` - Processing logs
- `wp_ai_comment_reputation` - User reputation scores
- `wp_ai_background_jobs` - Batch job history
- `wp_ai_webhook_log` - Webhook call logs
- `wp_ai_remote_sites` - Remote site configurations (including encrypted passwords)
- `wp_ai_remote_comments` - Cached remote comments

## Configuration

### Enable/Disable Data Preservation

1. Go to **AI Moderator → Settings**
2. Scroll to **Data Management** section
3. Check/uncheck **"Preserve all data when plugin is deleted"**
4. Click **Save Settings**

### Default Setting

- **Fresh Install**: Data preservation is ENABLED by default (✅)
- **Upgrades**: Your existing setting is preserved

## Use Cases

### Development Workflow (Keep Data ENABLED ✅)

Perfect for active development:

```
1. Install plugin
2. Configure Ollama, add remote sites, create prompts
3. Test features
4. Find a bug
5. Delete plugin
6. Fix bug in code
7. Reinstall plugin
8. Everything still configured! ✅
9. Continue testing
```

### Testing Updates (Keep Data ENABLED ✅)

When testing new versions:

```
1. Have working setup with data
2. Delete old version
3. Install new version
4. All data preserved ✅
5. Test new features immediately
```

### Production Cleanup (Keep Data DISABLED ❌)

When permanently removing:

```
1. Disable "Keep Data on Uninstall"
2. Save settings
3. Delete plugin
4. All data removed ❌
5. Database is clean
```

## Technical Details

### How WordPress Handles Uninstall

WordPress has two hooks:
1. **Deactivation Hook** - Runs when you click "Deactivate"
   - Does NOT delete data
   - Plugin is still installed

2. **Uninstall Hook** - Runs when you click "Delete"
   - This is where our data preservation logic runs
   - Checks the `keep_data_on_uninstall` option

### The Code

When you delete the plugin, this code runs:

```php
function ai_comment_moderator_uninstall() {
    // Check user preference
    $keep_data = get_option('ai_comment_moderator_keep_data_on_uninstall', '0');
    
    if ($keep_data === '1') {
        // KEEP DATA - exit early
        delete_option('ai_comment_moderator_keep_data_on_uninstall');
        return; 
    }
    
    // DELETE DATA - drop tables, delete options
    // ...
}
```

### Storage Location

The preference is stored as a WordPress option:
- **Option Name**: `ai_comment_moderator_keep_data_on_uninstall`
- **Value**: `'1'` (keep) or `'0'` (delete)
- **Location**: `wp_options` table

## Security Considerations

### Encrypted Data

Even when preserved, sensitive data remains secure:
- ✅ Remote site application passwords are encrypted
- ✅ Only accessible to WordPress administrators
- ✅ No plaintext passwords in database

### Manual Cleanup

If you want to manually clean up later:

**Option 1 - Via phpMyAdmin:**
```sql
-- Drop all plugin tables
DROP TABLE IF EXISTS wp_ai_comment_reviews;
DROP TABLE IF EXISTS wp_ai_comment_prompts;
DROP TABLE IF EXISTS wp_ai_comment_logs;
DROP TABLE IF EXISTS wp_ai_comment_reputation;
DROP TABLE IF EXISTS wp_ai_background_jobs;
DROP TABLE IF EXISTS wp_ai_webhook_log;
DROP TABLE IF EXISTS wp_ai_remote_sites;
DROP TABLE IF EXISTS wp_ai_remote_comments;

-- Delete all plugin options
DELETE FROM wp_options WHERE option_name LIKE 'ai_comment_moderator_%';
```

**Option 2 - Via WP-CLI:**
```bash
wp db query "DROP TABLE IF EXISTS wp_ai_comment_reviews"
wp db query "DROP TABLE IF EXISTS wp_ai_comment_prompts"
# ... repeat for all tables

wp option delete ai_comment_moderator_ollama_url
wp option delete ai_comment_moderator_ollama_model
# ... repeat for all options
```

## Frequently Asked Questions

### Q: Does deactivating delete my data?
**A:** No! Deactivating just turns off the plugin. Your data is safe.

### Q: I deleted the plugin but want my data back?
**A:** If "Keep Data" was enabled, just reinstall! Everything will be there.

### Q: I forgot to uncheck "Keep Data" before deleting. How do I clean up?
**A:** Use the manual cleanup SQL commands above, or reinstall the plugin, uncheck the setting, save, then delete again.

### Q: How much database space does preserved data use?
**A:** Depends on your usage:
- Settings: ~2 KB
- 100 prompts: ~50 KB
- 10 remote sites: ~5 KB
- 10,000 processed comments: ~5 MB
- Total for typical setup: < 10 MB

### Q: Can I backup just the plugin data?
**A:** Yes! Use the **Export Reports** feature or backup these tables in phpMyAdmin.

### Q: Will this work with WordPress multisite?
**A:** Yes, but settings are per-site. Each site in a network maintains its own configuration.

## Best Practices

### During Development
- ✅ Keep "Preserve data" ENABLED
- ✅ Delete and reinstall freely
- ✅ No need to reconfigure each time

### For Production Sites
- ✅ Keep "Preserve data" ENABLED for updates
- ❌ Only disable if permanently removing plugin
- ✅ Always backup database before major changes

### Before Sharing/Deploying
- ✅ Export your prompts and settings
- ✅ Document remote site configurations
- ✅ Test on staging site first

## Summary

| Action | Keep Data ON ✅ | Keep Data OFF ❌ |
|--------|----------------|------------------|
| **Deactivate** | Data preserved | Data preserved |
| **Delete Plugin** | Data preserved | Data deleted |
| **Reinstall** | All settings restored | Start from scratch |
| **Database** | Tables remain | Tables dropped |
| **Best For** | Development, Testing | Final removal |

---

**Recommendation**: Unless you're permanently removing the plugin, keep "Preserve all data when plugin is deleted" **CHECKED** ✅

This saves time and prevents configuration loss during development and updates.

