# Graylog Search Plugin - Comprehensive Testing Guide

**Version**: 1.11.0  
**Date**: October 15, 2025  
**Purpose**: Complete testing checklist before production deployment

---

## 🎯 Overview

This guide covers testing for all implemented features through **Phase 2 + PDF Export**:

- ✅ **Phase 1**: Core improvements & performance
- ✅ **Phase 2**: Advanced search capabilities
- ✅ **Phase 3** (partial): PDF Export

**Total Features to Test**: 50+ features across 15 categories

---

## 📋 Pre-Testing Setup

### 1. Environment Checklist
- [ ] Docker containers running (`docker-compose ps`)
- [ ] WordPress accessible at http://localhost:8080
- [ ] Plugin activated in WordPress Admin
- [ ] Graylog API configured (URL + Token in Settings)
- [ ] Test data available in Graylog (at least 100+ log entries)

### 2. Browser Testing Matrix
Test in these browsers:
- [ ] **Chrome** (latest)
- [ ] **Firefox** (latest)
- [ ] **Safari** (if on Mac)
- [ ] **Edge** (optional)

### 3. User Accounts
- [ ] Admin user (full permissions)
- [ ] Regular user (if testing permissions)

---

## 🧪 Testing Categories

## 1. Basic Search Functionality

### Test: Simple Hostname Search
- [ ] Enter a hostname in "Hostname" field
- [ ] Click "Search"
- [ ] **Expected**: Results appear with matching hostnames
- [ ] **Verify**: Trailing wildcard applied automatically (partial matches work)

### Test: Search Terms
- [ ] Enter terms in "Search Terms" field (try: "error", "warning")
- [ ] Click "Search"
- [ ] **Expected**: Results contain those terms in message field
- [ ] **Verify**: Multiple terms work (comma/newline separated)

### Test: Multi-Value Input
- [ ] Enter multiple hostnames (one per line OR comma-separated)
- [ ] **Expected**: Results match ANY of the hostnames (OR logic)
- [ ] Try with 3-5 different hostnames

### Test: Filter Out
- [ ] Perform a search
- [ ] Enter terms to filter out
- [ ] **Expected**: Results dynamically hide matching rows
- [ ] Clear filter
- [ ] **Expected**: Rows reappear

### Test: Time Range
- [ ] Try all time ranges: Last hour, 4 hours, 8 hours, 12 hours, 24 hours, 3 days, 7 days
- [ ] **Expected**: Results filtered to selected time range
- [ ] **Verify**: Timestamps are within range

---

## 2. Interactive Filtering

### Test: Filter Out (Highlight & Click)
- [ ] Perform a search with results
- [ ] Highlight some text in a result row
- [ ] **Expected**: Popup appears with "Filter Out", "Filter In", "Highlight", "Copy"
- [ ] Click "Filter Out"
- [ ] **Expected**: Rows with that text disappear instantly
- [ ] **Verify**: Badge appears in filters section

### Test: Filter In (Keep Only)
- [ ] Highlight text in results
- [ ] Click "Filter In" from popup
- [ ] **Expected**: Only rows with that text remain visible
- [ ] **Verify**: Non-matching rows hidden
- [ ] **Verify**: Matched text is highlighted in yellow
- [ ] **Verify**: "Filter In" badge appears

### Test: Highlight
- [ ] Highlight text in results
- [ ] Click "Highlight" from popup
- [ ] **Expected**: All occurrences of that text highlighted in yellow
- [ ] **Verify**: "Highlight" badge appears
- [ ] Remove highlight
- [ ] **Expected**: Yellow highlighting removed

### Test: Copy
- [ ] Highlight text
- [ ] Click "Copy" from popup
- [ ] Paste into notepad
- [ ] **Expected**: Text copied to clipboard

### Test: Filter Badge Removal
- [ ] Add multiple filters (Filter Out, Filter In, Highlight)
- [ ] **Expected**: Badges appear for each
- [ ] Click X on each badge
- [ ] **Expected**: Filter removed, results update

### Test: Auto-Highlight Search Terms
- [ ] Search with specific terms (e.g., "error connection")
- [ ] **Expected**: Search terms automatically highlighted in results

---

## 3. IP Address Enrichment

### Test: IP Detection
- [ ] Search for logs with IP addresses
- [ ] **Expected**: IPs highlighted in blue and clickable

### Test: DNS Lookup (Single IP)
- [ ] Click on an IP address
- [ ] **Expected**: Shows "Resolving..." spinner
- [ ] **Expected** (if resolvable): IP replaced with hostname/FQDN
- [ ] **Expected** (if not resolvable): IP turns orange with strikethrough, notification shows "No DNS record"

### Test: Resolve All IPs
- [ ] Click "Resolve All IPs" button
- [ ] **Expected**: All IPs in visible results resolved
- [ ] **Expected**: Progress indicated
- [ ] **Expected**: Mix of resolved (blue) and unresolved (orange) IPs

### Test: DNS Caching
- [ ] Resolve an IP
- [ ] Perform another search that returns the same IP
- [ ] Click the IP again
- [ ] **Expected**: Instant resolution (cached)

---

## 4. Timezone Conversion

### Test: Timezone Selection
- [ ] Open timezone dropdown
- [ ] Select different timezones (try: America/New_York, Asia/Tokyo, Europe/London)
- [ ] **Expected**: All timestamps convert to selected timezone
- [ ] **Verify**: Timestamps show selected timezone label

### Test: Show Original Times
- [ ] Convert to a different timezone
- [ ] Click "Show Original UTC"
- [ ] **Expected**: Timestamps revert to original UTC
- [ ] Click "Convert to [Timezone]"
- [ ] **Expected**: Timestamps convert again

### Test: Timezone Persistence
- [ ] Select a timezone
- [ ] Refresh the page
- [ ] **Expected**: Selected timezone remembered

---

## 5. Auto-Refresh

### Test: Enable Auto-Refresh
- [ ] Perform a search
- [ ] Check "Auto-Refresh" checkbox
- [ ] Select interval (15s, 30s, 60s, 5min)
- [ ] **Expected**: Search re-runs automatically at selected interval
- [ ] **Verify**: Results update (look for new entries if available)
- [ ] **Verify**: Timer countdown visible

### Test: Disable Auto-Refresh
- [ ] Uncheck "Auto-Refresh"
- [ ] **Expected**: Auto-refresh stops
- [ ] **Verify**: Timer disappears

---

## 6. Export Functions

### Test: Export CSV
- [ ] Perform a search
- [ ] Click "Export" → "CSV"
- [ ] **Expected**: CSV file downloads (`graylog-export.csv`)
- [ ] Open in Excel/spreadsheet
- [ ] **Verify**: Columns: Timestamp, Source, Level, Message
- [ ] **Verify**: All visible rows included (respects filters)

### Test: Export JSON
- [ ] Click "Export" → "JSON"
- [ ] **Expected**: JSON file downloads
- [ ] Open in text editor
- [ ] **Verify**: Valid JSON array
- [ ] **Verify**: All fields included

### Test: Export Text
- [ ] Click "Export" → "Text"
- [ ] **Expected**: TXT file downloads
- [ ] Open in text editor
- [ ] **Verify**: Human-readable format
- [ ] **Verify**: Blank line between entries

### Test: Export PDF
- [ ] Click "Export" → "📄 PDF Report"
- [ ] **Expected**: New window opens with formatted report
- [ ] **Expected**: Browser print dialog appears
- [ ] **Verify**: Report includes:
  - [ ] Header with title and date
  - [ ] Search parameters section
  - [ ] Summary statistics (total results, unique sources, time span)
  - [ ] Results table (timestamp, source, message)
  - [ ] Footer with page numbers
- [ ] Use browser "Print → Save as PDF" to save
- [ ] **Expected**: Professional PDF report generated

### Test: Copy to Clipboard
- [ ] Click "Export" → "📋 Copy"
- [ ] Paste into notepad
- [ ] **Expected**: All visible results copied in readable format

---

## 7. Row Actions

### Test: Include Source Filter
- [ ] Click "..." menu on a result row
- [ ] Click "Include source: [hostname]"
- [ ] **Expected**: Hostname auto-fills in search field
- [ ] **Expected**: New search triggered
- [ ] **Expected**: Results only from that source

### Test: Exclude Source Filter
- [ ] Click "..." → "Exclude source: [hostname]"
- [ ] **Expected**: Results from that source disappear
- [ ] **Verify**: Filter badge appears

### Test: Copy Row
- [ ] Click "..." → "Copy row"
- [ ] Paste into notepad
- [ ] **Expected**: Full row text copied

### Test: Expand Details
- [ ] Click "..." → "Expand details"
- [ ] **Expected**: Drawer slides out below row
- [ ] **Expected**: All fields shown (raw JSON or formatted)
- [ ] Click "Close" or click again
- [ ] **Expected**: Drawer closes

---

## 8. Log Parsing

### Test: Parse Toggle
- [ ] Perform search with structured logs (JSON, CEF, LEEF, or key=value)
- [ ] Click "Parse" toggle
- [ ] **Expected**: Logs parsed into fields
- [ ] **Expected**: Fields displayed as key-value pairs
- [ ] **Expected**: Search terms highlighted in parsed fields

### Test: Parse Format Selection
- [ ] Click format checkboxes (JSON, key=value, CEF, LEEF)
- [ ] Uncheck one format
- [ ] **Expected**: That format no longer parsed
- [ ] Re-check
- [ ] **Expected**: Format parsed again

### Test: JSON Parsing
- [ ] Search for logs with JSON content
- [ ] Enable Parse
- [ ] **Expected**: JSON pretty-printed
- [ ] **Expected**: Nested objects expanded

### Test: Key=Value Parsing
- [ ] Search for logs with `key=value` pairs
- [ ] Enable Parse
- [ ] **Expected**: Extracted into table

### Test: CEF/LEEF Parsing
- [ ] If you have CEF or LEEF logs, test parsing
- [ ] **Expected**: CEF/LEEF fields extracted

---

## 9. Pagination & Performance

### Test: Pagination
- [ ] Perform search with 100+ results
- [ ] **Expected**: "Load More" or pagination controls appear
- [ ] Click "Load More"
- [ ] **Expected**: Next 100 results append
- [ ] Scroll down
- [ ] **Expected**: Smooth scrolling, no lag

### Test: Large Result Sets
- [ ] Search with 500+ results (adjust time range if needed)
- [ ] **Expected**: Page loads without freezing
- [ ] **Expected**: Results load in batches
- [ ] Apply filters
- [ ] **Expected**: Filtering remains fast

### Test: Caching
- [ ] Perform a search
- [ ] Note the execution time
- [ ] Run the EXACT same search again within 5 minutes
- [ ] **Expected**: Much faster (cached)
- [ ] **Verify**: No new API call made

### Test: Debounced Search
- [ ] Type in search field rapidly
- [ ] **Expected**: Search doesn't trigger until you stop typing (300ms delay)

---

## 10. Saved Searches & History

### Test: Save Search
- [ ] Perform a search
- [ ] Look for "Save Search" option (if implemented in your saved-searches UI)
- [ ] Give it a name
- [ ] **Expected**: Search saved
- [ ] **Verify**: Appears in saved searches list

### Test: Load Saved Search
- [ ] Click on a saved search
- [ ] **Expected**: Form populates with saved parameters
- [ ] **Expected**: Search executes

### Test: Delete Saved Search
- [ ] Delete a saved search
- [ ] **Expected**: Removed from list

### Test: Recent Searches
- [ ] Perform multiple different searches
- [ ] Check recent searches dropdown
- [ ] **Expected**: Last 10 searches listed
- [ ] Click one
- [ ] **Expected**: Search loads and executes

### Test: Quick Filters
- [ ] Click quick filter buttons (Errors, Warnings, Last Hour, Today)
- [ ] **Expected**: Appropriate search executed

---

## 11. Search History (Database)

### Test: View Search History
- [ ] Click "Search History" button
- [ ] **Expected**: Modal opens with statistics and history
- [ ] **Verify**: Statistics show (total searches, today, this week, favorites, avg time)
- [ ] **Verify**: Recent searches listed

### Test: Favorite Search
- [ ] Click star icon on a search
- [ ] **Expected**: Star turns gold
- [ ] **Expected**: "Added to favorites" notification
- [ ] Click star again
- [ ] **Expected**: Unfavorited

### Test: Filter History
- [ ] Use date range filter
- [ ] **Expected**: History filtered to date range
- [ ] Toggle "Favorites only"
- [ ] **Expected**: Only favorited searches shown
- [ ] Use text search
- [ ] **Expected**: Searches matching text shown

### Test: Re-Run Search
- [ ] Click "▶️ Re-Run" on a history item
- [ ] **Expected**: Modal closes
- [ ] **Expected**: Search form fills with parameters
- [ ] **Expected**: Search executes immediately

### Test: Add Note to Search
- [ ] Click "+ Add note" on a search
- [ ] Enter a note
- [ ] **Expected**: Note saved and displayed

### Test: Delete Search from History
- [ ] Click "🗑️ Delete" on a search
- [ ] Confirm deletion
- [ ] **Expected**: Search removed from history

### Test: Pagination in History
- [ ] If you have 50+ searches, test pagination
- [ ] **Expected**: 50 searches per page
- [ ] Navigate pages
- [ ] **Expected**: Smooth navigation

---

## 12. Regex Search

### Test: Enable Regex Mode
- [ ] Check "Regex Mode" checkbox
- [ ] Enter a regex pattern (e.g., `error|warning|fail`)
- [ ] Search
- [ ] **Expected**: Results match regex pattern

### Test: Pattern Library
- [ ] Click "Pattern Library" button
- [ ] **Expected**: Modal with 18 pre-built patterns
- [ ] Click "Use" on an IPv4 pattern
- [ ] **Expected**: Pattern inserted into search field
- [ ] Search
- [ ] **Expected**: Results with IPv4 addresses

### Test: Regex Tester
- [ ] Click "Test Regex" button
- [ ] Enter a pattern
- [ ] Enter sample text
- [ ] **Expected**: Real-time validation
- [ ] **Expected**: Matches highlighted
- [ ] Click "Use in Search"
- [ ] **Expected**: Pattern inserted into search field

### Test: Syntax Helper
- [ ] Click "Syntax Help" button
- [ ] **Expected**: Modal with regex cheat sheet
- [ ] **Verify**: Shows basic patterns, quantifiers, groups, character classes

### Test: Custom Patterns
- [ ] In Pattern Library, add a custom pattern
- [ ] Give it a name and description
- [ ] **Expected**: Pattern saved
- [ ] **Verify**: Appears in library
- [ ] Delete custom pattern
- [ ] **Expected**: Removed from library

---

## 13. Visual Query Builder

### Test: Open Query Builder
- [ ] Click "Visual Query Builder" button
- [ ] **Expected**: Large modal opens

### Test: Add Query Group
- [ ] Click "Add Group"
- [ ] **Expected**: New group card appears

### Test: Add Condition
- [ ] In a group, click "Add Condition"
- [ ] Select field (e.g., "message")
- [ ] Select operator (e.g., "Contains")
- [ ] Enter value (e.g., "error")
- [ ] **Expected**: Condition added
- [ ] **Verify**: Query preview updates with Lucene syntax

### Test: Multiple Conditions
- [ ] Add 3-4 conditions to a group
- [ ] Change group operator (AND/OR)
- [ ] **Expected**: Query preview shows proper logic

### Test: Multiple Groups
- [ ] Add 2-3 groups
- [ ] Each with different conditions
- [ ] Change top-level operator (AND/OR)
- [ ] **Expected**: Groups combined correctly in preview

### Test: Remove Condition/Group
- [ ] Click X on a condition
- [ ] **Expected**: Condition removed
- [ ] Click "Remove Group"
- [ ] **Expected**: Entire group removed

### Test: Field Types & Operators
- [ ] Select "timestamp" field
- [ ] **Expected**: Operators like "Greater Than", "Less Than" available
- [ ] Select "level" field
- [ ] **Expected**: String operators like "Equals", "Contains"

### Test: Use Query
- [ ] Build a complex query
- [ ] Click "Use This Query"
- [ ] **Expected**: Modal closes
- [ ] **Expected**: Query inserted into search field
- [ ] **Expected**: Regex mode enabled
- [ ] **Expected**: Search executes

### Test: Save Query Template
- [ ] Build a query
- [ ] Click "Save as Template"
- [ ] Enter name and description
- [ ] **Expected**: Template saved

### Test: Load Query Template
- [ ] Click "Load Template"
- [ ] **Expected**: List of saved templates
- [ ] Click "Load" on a template
- [ ] **Expected**: Query builder populates with template
- [ ] Delete a template
- [ ] **Expected**: Template removed

---

## 14. Keyboard Shortcuts

### Test: Submit Search
- [ ] Focus on a search field
- [ ] Press **Ctrl+Enter** (or **Cmd+Enter** on Mac)
- [ ] **Expected**: Search submits

### Test: Clear Form
- [ ] Fill search fields
- [ ] Press **Esc**
- [ ] **Expected**: Form clears

### Test: Focus Search
- [ ] Press **/** key
- [ ] **Expected**: Cursor moves to hostname search field

### Test: Show Help
- [ ] Press **?** key
- [ ] **Expected**: Help modal or tooltip appears with keyboard shortcuts

---

## 15. Dark Mode

### Test: Toggle Dark Mode
- [ ] Click dark mode toggle button (if visible)
- [ ] **Expected**: Interface switches to dark theme
- [ ] **Verify**: All elements readable (no white text on white background)
- [ ] Toggle off
- [ ] **Expected**: Switches to light theme

### Test: Dark Mode Persistence
- [ ] Enable dark mode
- [ ] Refresh page
- [ ] **Expected**: Dark mode remembered

### Test: Dark Mode in Modals
- [ ] Enable dark mode
- [ ] Open various modals (Query Builder, History, Regex Tester)
- [ ] **Verify**: All modals use dark theme

---

## 16. Error Handling & Connection

### Test: Connection Status
- [ ] Look for connection status indicator (dot in header)
- [ ] **Expected**: Green dot when connected
- [ ] Stop Graylog or break network
- [ ] **Expected**: Red dot, error message

### Test: Invalid API Credentials
- [ ] Go to Settings
- [ ] Enter invalid API token
- [ ] Try to search
- [ ] **Expected**: Clear error message ("Invalid API credentials")

### Test: Network Error
- [ ] Disconnect network
- [ ] Try to search
- [ ] **Expected**: Error with retry option
- [ ] **Expected**: "Retry" button appears
- [ ] Reconnect network
- [ ] Click "Retry"
- [ ] **Expected**: Search succeeds

### Test: Automatic Retry
- [ ] Simulate flaky connection (if possible)
- [ ] **Expected**: Automatic retry with exponential backoff (1s, 2s, 4s)

### Test: Expandable Error Details
- [ ] Cause an API error
- [ ] Click "Show Details" on error
- [ ] **Expected**: Technical error details shown (for admins)

---

## 17. Shortcode Embedding

### Test: Basic Shortcode
- [ ] Create a WordPress page/post
- [ ] Add shortcode: `[graylog_search]`
- [ ] Publish and view page
- [ ] **Expected**: Full search interface embedded
- [ ] **Verify**: All features work (search, filters, export)

### Test: Custom Height Shortcode
- [ ] Use shortcode: `[graylog_search height="800px"]`
- [ ] **Expected**: Results box taller

### Test: Capability Shortcode
- [ ] Use shortcode: `[graylog_search capability="read"]`
- [ ] **Expected**: Only users with 'read' capability can use search

---

## 18. Settings Page

### Test: Configure API
- [ ] Go to Settings → Graylog Search
- [ ] Enter Graylog API URL
- [ ] Enter API Token
- [ ] Save
- [ ] **Expected**: Settings saved
- [ ] **Verify**: Can perform searches

### Test: SSL Verification Option
- [ ] Enable/disable "Disable SSL Verification" checkbox
- [ ] **Expected**: Setting saved
- [ ] **Verify**: Works with self-signed certs when disabled

### Test: GitHub Token
- [ ] Enter GitHub token (optional)
- [ ] Save
- [ ] **Expected**: Token saved for auto-updater

### Test: Plugin Updates
- [ ] Check "Plugin Updates" section
- [ ] **Expected**: Shows current version
- [ ] Click "Check for Updates Now"
- [ ] **Expected**: Checks GitHub for new version
- [ ] If update available, update button appears

### Test: Shortcode Copy Buttons
- [ ] Look at "Shortcode Usage" section
- [ ] Click copy button next to each shortcode example
- [ ] Paste into notepad
- [ ] **Expected**: Shortcode copied correctly

---

## 19. Performance Benchmarks

### Test: Search Speed
- [ ] Run a search
- [ ] **Expected**: Results in < 3 seconds
- [ ] **Target**: < 2 seconds for cached searches

### Test: Page Load Time
- [ ] Refresh plugin page
- [ ] **Expected**: Page loads in < 1 second

### Test: Large Dataset
- [ ] Search returning 1000+ results
- [ ] **Expected**: UI remains responsive
- [ ] Apply filters
- [ ] **Expected**: Filtering instant (< 100ms)

### Test: Memory Usage
- [ ] Open browser dev tools → Performance/Memory
- [ ] Run searches and interact with plugin
- [ ] **Verify**: No significant memory leaks
- [ ] **Verify**: Memory usage stable

---

## 20. Cross-Browser Testing

### Test: Chrome
- [ ] Run all core tests in Chrome
- [ ] **Expected**: Everything works

### Test: Firefox
- [ ] Run all core tests in Firefox
- [ ] **Expected**: Everything works
- [ ] **Verify**: PDF export works

### Test: Safari (if available)
- [ ] Run core tests in Safari
- [ ] **Expected**: Everything works
- [ ] **Note**: Some CSS features may differ

### Test: Edge (optional)
- [ ] Run core tests in Edge
- [ ] **Expected**: Everything works

---

## 🐛 Bug Reporting Template

When you find a bug, document it like this:

**Bug**: [Short description]  
**Steps to Reproduce**:  
1. [Step 1]  
2. [Step 2]  

**Expected Behavior**: [What should happen]  
**Actual Behavior**: [What actually happens]  
**Browser**: [Chrome/Firefox/Safari/Edge + version]  
**Screenshots**: [If applicable]  
**Console Errors**: [Open browser dev tools → Console, copy any errors]

---

## ✅ Testing Sign-Off

Once all tests pass:

- [ ] **Core Search**: All basic search features work
- [ ] **Interactive Filters**: Filter Out, Filter In, Highlight working
- [ ] **IP Enrichment**: DNS lookups working
- [ ] **Exports**: CSV, JSON, TXT, PDF working
- [ ] **Row Actions**: All row actions working
- [ ] **Parsing**: Log parsing working for all formats
- [ ] **Performance**: Fast and responsive
- [ ] **Saved/Recent Searches**: Working correctly
- [ ] **Search History**: Database storage working
- [ ] **Regex Search**: Pattern library and tester working
- [ ] **Visual Query Builder**: All query building features working
- [ ] **Keyboard Shortcuts**: All shortcuts working
- [ ] **Dark Mode**: Switching and persistence working
- [ ] **Error Handling**: Graceful error handling and retry
- [ ] **Shortcode**: Embedding on pages working
- [ ] **Auto-Refresh**: Automatic search re-running
- [ ] **Timezone**: Conversion working
- [ ] **Cross-Browser**: Works in Chrome, Firefox, Safari (if tested)

**Tested by**: [Your name]  
**Date**: [Date]  
**Version**: 1.11.0  
**Overall Status**: [ ] PASS / [ ] FAIL / [ ] NEEDS FIXES

---

## 📞 Support

If you encounter issues:
1. Check browser console for JavaScript errors
2. Check WordPress debug log for PHP errors
3. Verify Graylog API is accessible
4. Review CHANGELOG.md for known issues
5. Create GitHub issue with bug report template

---

## 🎉 Happy Testing!

This plugin now has **50+ features** across **Phase 1, Phase 2, and Phase 3 (partial)**. Thorough testing ensures a rock-solid user experience!

**Good luck with your testing!** 🚀

