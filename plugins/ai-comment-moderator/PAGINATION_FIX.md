# Pagination Fix for Remote Site Sync

## 🐛 Bug Identified

### The Problem
The remote site sync was **NOT actually paginating** through comments. It was fetching the **same 100 comments 5 times** instead of fetching 500 unique comments.

### Root Cause
Two critical issues in `includes/remote-site-manager.php`:

1. **Missing Page Parameter**: The `fetch_comments()` function signature didn't accept a `$page` parameter
2. **No Pagination in Loop**: The AJAX sync handler looped 5 times but never passed different page numbers

### What Was Happening

```php
// BEFORE (BROKEN):
for ($page = 1; $page <= 5; $page++) {
    // ❌ Always fetches page 1 because no $page parameter passed!
    $result = fetch_comments($site_id, 100, 'hold');
    
    // Result: Gets comments 1-100, 1-100, 1-100, 1-100, 1-100
    // NOT: 1-100, 101-200, 201-300, 301-400, 401-500
}
```

The WordPress REST API **requires** the `page` parameter in the query string:
```
/wp-json/wp/v2/comments?per_page=100&page=1  ← Page 1
/wp-json/wp/v2/comments?per_page=100&page=2  ← Page 2
/wp-json/wp/v2/comments?per_page=100&page=3  ← Page 3
```

Without the `page` parameter, it defaults to page 1 every time.

---

## ✅ Fix Applied

### 1. Updated `fetch_comments()` Function Signature

**File**: `includes/remote-site-manager.php` (Line ~145)

```php
// BEFORE:
public static function fetch_comments($site_id, $limit = 100, $status = 'hold') {

// AFTER:
public static function fetch_comments($site_id, $limit = 100, $status = 'hold', $page = 1) {
```

### 2. Added Page Parameter to API URL

```php
$api_url = add_query_arg(array(
    'status' => $status,
    'per_page' => min($limit, 100),
    'page' => $page,  // ✅ NOW INCLUDES PAGE!
    'order' => 'desc',
    'orderby' => 'date'
), $api_url);
```

### 3. Updated AJAX Sync Loop

**File**: `includes/remote-site-manager.php` (Line ~687)

```php
// BEFORE:
for ($page = 1; $page <= $pages; $page++) {
    $result = fetch_comments($site_id, 100, 'hold');  // ❌ No page param
}

// AFTER:
for ($page = 1; $page <= $pages; $page++) {
    $result = fetch_comments($site_id, 100, 'hold', $page);  // ✅ Passes page number
}
```

### 4. Enhanced Return Data with Pagination Metadata

Now captures total available comments from WordPress API headers:

```php
$headers = wp_remote_retrieve_headers($response);
$total_comments = isset($headers['x-wp-total']) ? intval($headers['x-wp-total']) : null;
$total_pages = isset($headers['x-wp-totalpages']) ? intval($headers['x-wp-totalpages']) : null;

return array(
    'success' => true,
    'comments' => $comments,
    'count' => count($comments),
    'stored' => $stored,
    'total_available' => $total_comments,  // ✅ NEW
    'total_pages' => $total_pages,         // ✅ NEW
    'current_page' => $page                // ✅ NEW
);
```

### 5. Better User Feedback

The sync success message now includes:
- Total comments available on remote site
- How many remain to be fetched
- Prompt to click "Sync" again if more exist

Example:
```
Successfully synced 427 new comment(s) from remote site. 
Total fetched: 500. 
Total pending on remote site: 3,247 
(2,747 remaining - click Sync again to fetch more)
```

---

## 🧪 How to Test

### Option 1: Use the Test Script

1. Access: `https://your-site.com/wp-content/plugins/ai-comment-moderator/test-sync-debug.php`
2. The script will:
   - Show total comments available on remote site
   - Test fetching 3 different pages
   - Verify each page returns unique comment IDs
   - Compare old vs new behavior
   - Display local database cache state

### Option 2: Manual Testing

1. **Clear existing remote comments** (optional, for clean test):
   ```sql
   DELETE FROM wp_ai_remote_comments WHERE site_id = 1;
   ```

2. **First sync**:
   - Go to AI Moderator → Remote Sites
   - Click "Sync Now"
   - Note the number stored (should be up to 500)

3. **Second sync** (if remote site has 500+ comments):
   - Click "Sync Now" again
   - Should fetch the NEXT 500 (501-1000)
   - Watch the "remaining" count decrease

4. **Check local database**:
   ```sql
   SELECT COUNT(*) FROM wp_ai_remote_comments WHERE site_id = 1;
   ```
   - First sync: ~500 (or less if remote has fewer)
   - Second sync: ~1000 (or less)
   - Each sync should add NEW unique comments

### Option 3: Monitor API Calls

Enable WordPress debug logging:

```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Add debug logging to `fetch_comments()`:
```php
error_log("Fetching page {$page} from {$api_url}");
```

Check `wp-content/debug.log` to see different URLs being called:
```
Fetching page 1 from https://remote-site.com/wp-json/wp/v2/comments?page=1&per_page=100...
Fetching page 2 from https://remote-site.com/wp-json/wp/v2/comments?page=2&per_page=100...
Fetching page 3 from https://remote-site.com/wp-json/wp/v2/comments?page=3&per_page=100...
```

---

## 📊 Expected Results

### Before Fix
- Remote site has: **3,247 comments**
- First sync fetches: **100 comments** (same 100, 5 times)
- Second sync fetches: **0 new comments** (already have those 100)
- Database shows: **100 total comments**

### After Fix
- Remote site has: **3,247 comments**
- First sync fetches: **500 unique comments** (pages 1-5)
- Second sync fetches: **500 new comments** (pages 6-10)
- Third sync fetches: **500 new comments** (pages 11-15)
- Continue until all 3,247 are synced
- Database shows: **3,247 total comments** after 7 syncs

---

## 🔧 Additional Notes

### Increasing Fetch Limit

If you want to fetch more than 500 per sync, edit line ~694:

```php
$pages = 5;  // Current: 5 × 100 = 500

// For more:
$pages = 10;  // 10 × 100 = 1,000 comments per sync
$pages = 50;  // 50 × 100 = 5,000 comments per sync
```

**Caution**: Higher values increase:
- Memory usage
- Request time (may timeout)
- Server load on remote site

### Why Not Fetch All at Once?

1. **Browser Timeouts**: PHP execution limits (30-60s default)
2. **Memory Limits**: Processing 10,000+ comments at once could exceed PHP memory
3. **Remote Server Protection**: Prevents overwhelming the remote site's API
4. **User Experience**: Provides incremental progress feedback

### Rate Limiting

The 0.1s delay between pages (`usleep(100000)`) prevents hammering the remote API. Adjust if needed:

```php
usleep(100000);  // 0.1 seconds (current)
usleep(500000);  // 0.5 seconds (slower, gentler)
usleep(50000);   // 0.05 seconds (faster)
```

---

## 🎯 Summary

**Issue**: Only fetching 100 comments instead of paginating through all comments  
**Cause**: Missing `page` parameter in API calls  
**Fix**: Added `$page` parameter to function and API URL  
**Result**: Now properly fetches 500 comments per sync, with clear feedback about remaining comments

The fix is **backward compatible** - the `$page` parameter defaults to `1`, so any existing code calling `fetch_comments()` without the page param will still work.

