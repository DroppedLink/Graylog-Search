# Changelog

All notable changes to the Graylog Search WordPress plugin will be documented in this file.

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

