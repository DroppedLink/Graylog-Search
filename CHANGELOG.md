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

## [1.0.5] - 2025-10-17

### Changed
- **Repository Restructure**: Complete repository reorganization
  - Moved all plugin files from `plugins/graylog-search/` to root directory
  - Clean structure ensures proper WordPress plugin installation
  - Downloads now extract to correct `graylog-search/` folder
  - GitHub releases and clones work correctly

### Removed
- Backup folders and development artifacts
- Unrelated plugin folders (ai-comment-moderator, stackpilot, web-embed)
- Development documentation files that cluttered the repository

## [1.0.4] - 2025-10-17

### Fixed
- **Critical:** Fixed WordPress plugin folder structure issue
  - GitHub auto-generated zips extract to `Graylog-Search-X.X.X/` (wrong)
  - WordPress requires consistent `graylog-search/` folder name
  - Created build script to generate properly structured release zips
  - Release assets now include correctly structured `graylog-search-X.X.X.zip`

### Added
- `build-release.sh` script for creating properly structured release zips
- Automated build process excludes development files
- Clear installation instructions in release notes

### Improved
- Release process now produces WordPress-compatible zip files
- Users no longer need to manually rename folders after extraction
- GitHub Updater plugin can now properly detect and install updates

### Documentation
- Added comprehensive README.md
- Cleaned up old documentation files
- Updated release notes with proper installation instructions

## [1.0.3] - 2025-10-17

### Added
- **Uninstall Options**: New setting to control data deletion when uninstalling the plugin
  - Option in Settings → Advanced Settings → "Delete all plugin data when uninstalling"
  - When disabled (default): Plugin data is preserved for potential reinstallation
  - When enabled: Complete cleanup including database table, settings, and user data
  - Provides clear warning about permanent data deletion

### Changed
- Updated uninstall.php to respect the new delete-on-uninstall setting
- By default, plugin data is now preserved when uninstalling (safer behavior)

## [1.0.2] - 2025-10-17

### Changed
- **Simplified Query Generation**: Removed field-specific query syntax for Simple mode
  - Before: `(message:term OR fqdn:term OR source:term)` per search term
  - After: `term*` - let Graylog search all fields automatically
  - Queries are now cleaner, faster, and more flexible
  - Searches ALL fields instead of just message/fqdn/source

### Added
- Debug logging in QueryBuilder to help troubleshoot search parsing issues
- Updated UI help text to clarify multi-line and comma-separated input

### Improved
- Query performance by reducing complexity
- Search coverage by removing field restrictions

## [1.0.1] - 2025-10-17

### Fixed
- **Critical Bug Fix**: Fixed newline sanitization breaking multi-line search queries
  - WordPress's `sanitize_textarea_field()` was converting newlines to spaces
  - Users entering newline-separated terms (e.g., "error\nwarning") were getting no results
  - Replaced with `wp_strip_all_tags()` to preserve newlines while maintaining security
  - Now properly supports newline-separated, comma-separated, and mixed formats

### Changed
- Updated Security helper class with `sanitize_multiline_input()` method
- Fixed legacy AJAX handlers and saved search functionality

## [1.0.0] - 2025-10-17

### Added
- Initial release
- Simple, Advanced, and Query Builder search modes
- Search history with database storage
- Saved searches functionality
- DNS lookup for IP addresses
- Timezone conversion support
- Export to CSV, JSON, TXT, PDF
- Interactive text filtering and highlighting
- GitHub Updater integration
- Shortcode support for frontend display
- SSL verification options
- Auto-refresh capability
- Result parsing (JSON, key=value, CEF, LEEF)
- Keyboard shortcuts
- Connection testing

