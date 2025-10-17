# Changelog

All notable changes to the Graylog Search WordPress Plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Planned
- Multi-stream search support
- Advanced query builder
- Custom field mappings
- Email alerts for saved searches

## [1.0.0] - 2025-10-17

### Added
- **Core Search Functionality**
  - Simple search interface for Graylog logs
  - FQDN/hostname search
  - Multi-value input support
  - Time range picker (hour/day/week)
  - Dynamic results table with pagination
  - Color-coded log levels

- **IP Enrichment**
  - Automatic IP detection and highlighting
  - Click-to-DNS lookup for IP addresses
  - Batch "Resolve All IPs" functionality
  - Color-coded DNS resolution states

- **Timezone Features**
  - Smart timezone selector with auto-detection
  - Toggle between original and converted timestamps
  - Timestamp detection within log messages
  - Per-user timezone preferences

- **Saved Searches & History**
  - Save frequently used searches
  - Automatic tracking of last 10 searches
  - Quick filters for common scenarios
  - One-click search loading

- **User Experience**
  - Keyboard shortcuts (Ctrl+Enter, Esc, /, ?)
  - Dark mode with system preference support
  - Interactive text filtering (highlight, filter, exclude)
  - Active filters display and management
  - Responsive design for mobile/tablet

- **Export & Analysis**
  - Export results to CSV, JSON, and plain text
  - Copy to clipboard functionality
  - Row actions menu (filter, exclude, copy, details)
  - Auto-parse JSON, key=value, CEF, LEEF formats
  - Field extraction and display

- **Performance**
  - API response caching (5-minute cache)
  - Offset-based pagination for large results
  - Smart cache keys
  - Auto-refresh with configurable intervals

- **WordPress Integration**
  - Admin menu page with full interface
  - Shortcode support for embedding `[graylog_search]`
  - Shortcode attributes (height, capability)
  - WordPress nonces and capability checks
  - User preferences storage

- **Auto-Updates**
  - GitHub-based update system
  - Automatic update notifications
  - One-click updates from WordPress admin
  - Manual update check button
  - Release notes display

- **Developer Features**
  - Modern OOP structure in `src/` directory
  - GitHub Actions workflow for releases
  - Automated ZIP building
  - Clean git export via `.gitattributes`

### Technical Details
- Compatible with Graylog 6.1+ API
- Handles new schema/datarows format
- Includes required `X-Requested-By` header
- WordPress 5.0+ compatibility
- PHP 7.2+ requirement
- GPL v2 license

### Security
- WordPress nonce verification on all AJAX requests
- Capability checks for all admin functions
- Sanitized input/output
- Escaped SQL queries (none used - uses WordPress transients)
- Optional SSL verification disable for development

---

## Version History

- **v1.0.0** - Initial release with full feature set

[Unreleased]: https://github.com/DroppedLink/Graylog-Search/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/DroppedLink/Graylog-Search/releases/tag/v1.0.0

