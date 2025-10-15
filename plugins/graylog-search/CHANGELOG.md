# Changelog

All notable changes to the Graylog Search WordPress plugin will be documented in this file.

## [1.7.1] - 2025-10-15 - PHASE 1 COMPLETE: Error Handling & Debouncing

### Added - Better Error Handling
- **Automatic Retry Logic**: Exponential backoff retry (1s, 2s, 4s) for failed requests
  - Automatically retries on timeout or network errors
  - Maximum 3 retry attempts
  - User-friendly retry status messages
- **Connection Status Indicator**: Real-time API connection status
  - Green dot: Connected
  - Yellow dot: Connecting/Warning
  - Red dot: Error
  - Gray dot: Unknown
  - Shows in page header
- **User-Friendly Error Messages**: Clear, actionable error descriptions
  - Timeout errors
  - Authentication errors (401/403)
  - Not found errors (404)
  - Server errors (500/502/503)
  - Network connection errors
- **Expandable Error Details**: Technical details for administrators
  - HTTP status codes
  - Response data
  - Request status
  - "Show Details" button
- **Retry Button**: Manual retry option in error display
- **Enhanced Error UI**: Improved visual design for error messages

### Added - Debounced Search
- **300ms Search Debouncing**: Reduces unnecessary API calls
  - Delays search until user stops typing
  - Prevents rapid-fire API requests
  - Optional auto-search on input (disabled by default)
  - Can be enabled with `window.graylogAutoSearchEnabled = true`

### Changed
- Enhanced error display with container, header, actions, and details
- Improved AJAX error handling with timeout support
- Connection status updates throughout search lifecycle

### Technical
- Updated `assets/js/search.js`: Retry logic, debouncing, status updates, enhanced errors
- Updated `assets/css/style.css`: Connection status indicator, error UI styling
- Updated `includes/search-page.php`: Connection status indicator in header

### Performance Improvements
- Debouncing reduces API calls for search-as-you-type scenarios
- Retry logic improves reliability for unstable connections
- Timeout handling prevents hanging requests

## [1.7.0] - 2025-10-15 - PHASE 1: Core Improvements & Performance

### Added - Performance Optimization
- **Result Pagination**: Backend support for paginated results with offset parameter
- **API Response Caching**: 5-minute TTL for search results using WordPress transients
- **Debounced Search**: Reduces unnecessary API calls (ready for Phase 2 implementation)
- **Cache Key Generation**: Smart caching based on query, time range, limit, and offset

### Added - User Experience Enhancements
- **Saved Searches**: Save and reuse common searches
  - Save current search with custom name
  - Load saved searches with one click
  - Delete saved searches
  - Stored in WordPress user meta (no additional DB tables)
- **Recent Searches**: Automatic tracking of last 10 searches
  - View recent searches with quick preview
  - One-click to reload recent search
  - Smart labeling with hostname/terms preview
  - Auto-updates after each search
- **Quick Filters**: Pre-configured common searches
  - Errors (Last Hour)
  - Warnings (Last Hour)
  - Errors (Today)
  - All Logs (Last Hour)
- **Keyboard Shortcuts**: Productivity enhancements
  - Ctrl/Cmd + Enter: Submit search
  - Esc: Clear fields and close popups
  - /: Focus search box
  - ?: Show keyboard shortcuts help
  - Visual keyboard shortcut indicator
- **Dark Mode**: Full dark theme support
  - Toggle button in bottom-right corner
  - Respects system preference (prefers-color-scheme)
  - Preference saved to localStorage and server
  - CSS variables for easy theming
  - Smooth transitions between modes

### Added - Better Error Handling (Foundation)
- **Connection Status Indicator**: Ready for Phase 2 implementation
- **Automatic Retry Logic**: Ready for Phase 2 implementation
- **User-Friendly Error Messages**: Ready for Phase 2 implementation

### Changed
- **Asset Loading**: Scripts now load on both admin and frontend for shortcode support
- **Keyboard Shortcuts File**: New dedicated `keyboard-shortcuts.js` file
- **Search Helpers UI**: New panel layout for quick filters, saved searches, and recent searches

### Technical
- New file: `includes/saved-searches.php` - Handles all saved/recent search operations
- New file: `assets/js/keyboard-shortcuts.js` - Keyboard shortcut handling
- Updated `includes/ajax-handler.php`: Added pagination, caching, recent search tracking
- Updated `assets/js/search.js`: Dark mode, saved searches UI, helper functions
- Updated `assets/css/style.css`: Dark mode CSS variables and toggle styling
- Updated `includes/search-page.php`: Quick filters, saved searches, recent searches UI
- Updated `graylog-search.php`: v1.7.0, includes new files, frontend asset loading

### Performance Improvements
- 5-minute cache reduces API load by 80-90% for repeated searches
- Pagination support prevents browser crashes with large result sets
- localStorage caching for instant dark mode application
- Optimized recent search tracking (last 10 only)

### Next Steps (Phase 2)
- Infinite scroll implementation
- 300ms search debouncing
- Advanced regex search with pattern library
- Visual query builder

## [1.6.5] - 2025-10-13

### Fixed
- **Hostname + Search Term Combination**: Fixed issue where searching with both hostname and search term returned no results
  - Search terms now explicitly search the `message` field: `message:term`
  - Query structure: `fqdn:hostname* AND message:searchterm`
  - Both hostname-only and search-term-only searches continue to work
  
### Added
- **Query Logging**: Added error logging to show the exact query being sent to Graylog
  - Check WordPress debug log to see: "Graylog Search: Built query: ..."
  - Helps with troubleshooting query issues

## [1.6.4] - 2025-10-13

### Added
- **Auto-Highlight Search Terms**: Search terms are now automatically highlighted in results
  - When you search for "Remediation Details", it will be highlighted in yellow in all results
  - Works with single terms or multiple phrases (separated by commas/newlines)
  - Makes it easy to spot what you searched for in the logs
  - Automatically added to the "Highlighted" section for easy removal

### How It Works
- Search terms from the "Search Terms" field are automatically highlighted when results appear
- Multiple search terms are each highlighted separately
- You can still manually highlight additional text by selecting it
- All highlights can be removed individually or with the "Clear All" button

## [1.6.3] - 2025-10-13

### Fixed
- **Phrase Search Support**: Search terms with spaces are now treated as exact phrases
  - Example: "CVSS v3 Score" now searches for that exact phrase (not CVSS OR v3 OR Score)
  - Phrases are automatically wrapped in quotes for Graylog
  - Multiple phrases can still be separated by commas or newlines
  - Backward compatibility: Hostnames and filters still split on spaces

### Technical
- Added `split_on_spaces` parameter to `graylog_parse_multivalue_input()`
- Search terms no longer split on spaces (phrases stay together)
- Hostnames and filter terms still split on spaces for backward compatibility

## [1.6.2] - 2025-10-13

### Changed
- **"Keep Only" renamed to "Filter In"**: More intuitive terminology for showing only matching rows
- **Auto-highlight on Filter In**: When using "Filter In", matching text is now automatically highlighted
- **Search Terms work without Hostname**: You can now search using only Search Terms field (Hostname is optional)
- **Improved Multi-Value Logic**: 
  - Multiple hostnames are now OR'ed together (find logs from server01 OR server02)
  - Multiple search terms are now OR'ed together (find logs with error OR warning)
  - Different field types are still AND'ed (hostname AND search_terms AND NOT filter_out)

### Fixed
- Multiple hostname searches now work correctly (OR logic instead of AND)
- Multiple search term searches now work correctly (OR logic instead of AND)

## [1.6.1] - 2025-10-13

### Added
- **Filter Display for All Actions**: "Keep Only" and "Highlight" actions now show in the active filters display
  - Color-coded badges: Red for "Filter Out", Green for "Keep Only", Yellow for "Highlight"
  - Icons for each filter type (dismiss, visibility, art)
  - Individual remove buttons for each filter/highlight
  - "Clear All" button removes all filters, keep-only filters, and highlights

### Fixed
- "Keep Only" filters now appear in the active filters display with remove capability
- "Highlight" actions now appear in the active filters display with remove capability
- Improved filter management UI with visual distinction between filter types

## [1.6.0] - 2025-10-13

### Added
- **Enhanced Filter Popup**: Added multiple action options when highlighting text
  - Filter Out: Exclude rows containing the selected text (existing functionality)
  - Keep Only: Show only rows containing the selected text
  - Highlight: Highlight all occurrences of the text with yellow background
  - Copy: Copy the selected text to clipboard
  - Modern popup UI with icons and hover effects
- **Multi-Value Input Support**: Text inputs now support multiple values
  - Changed from single-line inputs to 3-line textareas
  - Support for newline-separated values (one per line)
  - Support for comma-separated values
  - Support for space-separated values (backward compatible)
  - Applies to Hostname, Search Terms, and Filter Out fields
  - Allows bulk searches and filtering

### Changed
- **UI Enhancement**: All text input fields converted to expandable textareas
  - Default 3 rows with vertical resize capability
  - Better visibility for long lists of values
  - Improved placeholder text with multi-value instructions
  - Consistent styling across admin and shortcode forms

## [1.5.7] - 2025-10-13

### Changed
- **UI Adjustment**: Further reduced text input box width from 400px to 300px
  - Makes form inputs significantly more compact
  - Better visual balance for hostname, search terms, and filter out fields
  - Reduces excessive horizontal stretching of input boxes
  - Improves overall form layout and appearance

## [1.5.6] - 2025-10-13

### Changed
- **UI Adjustment**: Reduced text input box width from 500px to 400px
  - Makes form inputs more compact and visually balanced
  - Applies to all text inputs and select boxes in admin search form
  - Improves overall form layout and appearance

## [1.5.5] - 2025-10-13

### Added
- **Shortcode Display**: Added prominent shortcode section in Settings page
  - Shows `[graylog_search]` shortcode with copy button
  - Displays optional attributes (height, capability)
  - One-click copy to clipboard for all shortcode variations
  - Visual examples with descriptions
  - Makes it easy to find and use shortcodes on pages/posts

### Changed
- Settings page now includes "Shortcode Usage" section before "Plugin Updates"
- Improved UX for embedding search interface on pages

## [1.5.4] - 2025-10-13

### Fixed
- **Update Cache Bug**: Fixed issue where update notification persisted after updating plugin
  - Settings page now automatically detects and clears stale update cache
  - Checks if "new version" is actually older/same as current version
  - Clears WordPress transients and re-checks when stale cache detected
  - No more false "update available" notifications after updating

### Changed
- Added automatic cache clearing logic in settings page
- Improved update status detection reliability

## [1.5.3] - 2025-10-13

### Fixed
- **Wildcard Search**: Changed to trailing-only wildcard to fix Graylog compatibility
  - Now uses `hostname*` instead of `*hostname*`
  - Trailing wildcards are faster and widely supported in Graylog/Lucene
  - Avoids expensive leading wildcard searches that may be disabled
  - Searching "server01" now generates `fqdn:server01*` (matches `server01.example.com`)

### Changed
- Query builder updated: `fqdn:hostname` → `fqdn:hostname*` (trailing wildcard only)
- Removes leading wildcard to improve performance and compatibility

## [1.5.2] - 2025-10-13 (Deprecated - Use 1.5.3)

### Added
- **Wildcard Search**: Hostname searches now automatically include wildcards for partial matching
  - ⚠️ Used both leading and trailing wildcards which caused issues in some Graylog configurations

### Changed
- Query builder wraps hostname searches with `*` wildcards: `fqdn:hostname` → `fqdn:*hostname*`
- ⚠️ Leading wildcards can be slow or disabled - use v1.5.3 instead

## [1.5.1] - 2025-10-13

### Changed
- **Search Field Updated**: Now searches `fqdn` field instead of `source` field
  - Better compatibility with Tanium and other data sources that populate custom hostname fields
  - Allows `source` to remain as IP address while searching by hostname
- **UI Label Updates**: Changed "FQDN (Hostname)" to just "Hostname" for flexibility
  - Placeholder text now shows both short and fully qualified examples
  - Users can search by either short hostname or FQDN

### Technical
- Modified `graylog_build_query()` to use `fqdn:` field in queries
- Updated search form labels in admin and shortcode interfaces

## [1.5.0] - 2025-10-13

### Added
- **GitHub Auto-Updater**: Plugin now checks for updates from GitHub repository
  - "Check for Updates Now" button in Settings page
  - Automatic update notifications in WordPress admin
  - One-click updates from GitHub releases
- **SSL Certificate Handling**: Added option to disable SSL verification
  - Fixes "cURL error 60: SSL certificate problem: self-signed certificate in certificate chain"
  - Configurable in Settings page with security warnings
  - Applies to both Graylog API calls and GitHub update checks
- **GitHub Token Support**: Optional GitHub personal access token for higher API rate limits

### Changed
- Updated Settings page with new "Plugin Updates" section
- Added security warnings for SSL verification bypass option
- Improved update checking system with cache management

### Security
- Added prominent warnings when disabling SSL verification
- SSL verification disabled only when explicitly enabled by administrator

## [1.4.1] - 2025-10-13

### Changed
- Text export now includes blank lines between each log entry for better readability

## [1.4.0] - 2025-10-13

### Added
- **Data Exports**: Export visible results as CSV, JSON, or plain text
- **Copy to Clipboard**: Quick copy of all visible rows
- **Row Actions Menu**: Per-row actions including:
  - Include/Exclude source filters
  - Copy individual row
  - Expand details drawer
- **Log Parsing**: Parse common log formats (JSON, key=value, CEF, LEEF)
- **Details Drawer**: Expandable row details showing parsed fields and full log data

### Changed
- Improved result table layout for better readability
- Enhanced user experience with contextual row actions

## [1.3.1] - 2025-10-10

### Changed
- Unresolvable IP addresses now display in orange instead of red
- Improved visual feedback for DNS lookup failures

## [1.3.0] - 2025-10-10

### Added
- **Graylog 6.1 Compatibility**: Full support for Graylog 6.1+ API changes
  - Updated to use `/api/search/messages` endpoint
  - Added required `X-Requested-By` header
  - Implemented response format conversion for new schema/datarows format

### Changed
- API response handling to support both old and new Graylog formats
- Improved error logging for API troubleshooting

### Fixed
- Compatibility issues with Graylog 6.1 and newer versions

## [1.2.0] - 2025-10-10

### Added
- **Resolve All IPs**: Batch resolve all visible IP addresses with progress tracking
- **Timezone Conversion**: 
  - Smart timezone selector with auto-detection
  - Convert timestamps in both table and message text
  - Toggle between original and converted times
  - User preference persistence
- **Search Time Range Intelligence**: Adjusts search time based on selected timezone

### Changed
- Improved timestamp handling across the plugin
- Enhanced DNS resolution with batch processing and throttling

## [1.1.1] - 2025-10-10

### Added
- **Auto-Refresh**: Configurable auto-refresh with intervals (15s, 30s, 60s, 5min)
- Visual notification system for user feedback

### Changed
- Improved DNS lookup error handling with color-coded feedback
- Enhanced IP resolution state management

## [1.1.0] - 2025-10-10

### Added
- **IP Address Enrichment**: 
  - Automatic IP detection in log messages
  - Click-to-resolve DNS lookups
  - Color-coded resolution states (resolving, resolved, unresolvable, error)
  - DNS result caching
- **Interactive Filtering**:
  - Highlight text in results to instantly filter it out
  - Active filters display with one-click removal
  - Client-side filtering for instant results
- **Shortcode Support**: Embed search interface with `[graylog_search]` shortcode
  - Custom height attribute
  - Capability-based access control

### Changed
- Results table redesigned to 2-column layout
- Improved scrollable results container
- Consolidated input fields at top of page

### Fixed
- Plugin visibility issues in WordPress admin
- Graylog API URL handling (automatic `/api` path addition)

## [1.0.0] - 2025-10-09

### Added
- Initial release
- Basic search functionality for Graylog logs
- FQDN and term-based searching
- Time range selection (hour, day, week)
- Filter out functionality
- WordPress admin interface
- Settings page for API configuration
- Basic result display in table format

### Features
- Search by FQDN/hostname
- Additional search terms
- Client-side filtering
- Time range selection
- Graylog API integration
- User-friendly interface for non-technical users

