# Changelog

All notable changes to AI Comment Moderator will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.4] - 2025-01-13

### Added
- **Configurable sync batch size**: Choose 500/1000/2000/5000/10000 comments per sync in Settings
  - Default increased from 500 to 1,000 comments for faster syncing
  - Higher values reduce clicks needed but may timeout on slow servers
- **Full remote site editing interface** with:
  - Edit site name, URL, username, and application password
  - Toggle site active/inactive without deleting
  - View site statistics (total comments, pending, last sync, created date)
  - Advanced actions: Reset pagination, Clear cache, Delete site
  - Security: Application password can be updated without showing current value
- **Clear comment cache button**: Delete all cached comments from a site to re-sync fresh

### Changed
- Default sync batch size: 500 → 1,000 comments (10 pages instead of 5)
- Remote site edit page now fully functional (was placeholder "coming soon")
- Edit page includes comprehensive site management tools

### Technical
- New setting: `ai_comment_moderator_sync_pages_per_batch` (default: 10)
- New AJAX handler: `ai_moderator_clear_site_cache`
- Site editing validates all fields and only updates password if provided

## [1.0.3] - 2025-01-13

### Fixed
- **🎯 MAJOR**: Sync now tracks pagination state - each click fetches NEXT batch, not same batch
  - Previously: Clicked sync 3 times, only worked once (fetched pages 1-5 repeatedly)
  - Now: Click 1 = pages 1-5, Click 2 = pages 6-10, Click 3 = pages 11-15, etc.
  - Pagination state persists between syncs using `wp_options` table
  - Auto-resets to page 1 when sync completes or reaches end

### Added
- **Reset button** for each remote site to manually restart pagination from page 1
- **Update tracking**: Now reports both new inserts and existing comment updates
- **Local cache count**: Shows total comments in local database
- **Better progress feedback**: "Synced pages 6-10. 500 new, 0 updated. Total in cache: 1,000"
- **Completion detection**: Message shows "✓ All synced!" when complete

### Changed
- `store_remote_comments()` now returns both `stored` and `updated` counts
- Sync message format completely redesigned for clarity
- Shows exact page range synced, new vs updated counts, and remaining count

### Technical
- Added `ai_moderator_last_sync_page_site_{site_id}` option for state tracking
- New AJAX handler: `ai_moderator_reset_sync_pagination`
- Pagination resets automatically when fewer than 100 comments returned
- See `SYNC_FIX_V2.md` for detailed technical explanation

## [1.0.2] - 2025-01-13

### Fixed
- **🐛 CRITICAL**: Remote site sync now properly paginates through comments
  - Was fetching the same 100 comments repeatedly
  - Now correctly fetches unique comments across multiple pages
  - Added `$page` parameter to `fetch_comments()` function
  - AJAX sync loop now passes page numbers to API

### Enhanced
- Sync feedback now shows total comments available on remote site
- Shows remaining comment count after each sync
- Prompts user to sync again if more comments exist
- Added pagination metadata (X-WP-Total, X-WP-TotalPages) to response
- Created diagnostic test script (`test-sync-debug.php`) for troubleshooting

### Technical Details
- `fetch_comments()` signature: Added `$page = 1` parameter
- API URL now includes `page` query parameter
- Return data includes: `total_available`, `total_pages`, `current_page`
- See `PAGINATION_FIX.md` for detailed technical explanation

## [1.0.1] - 2025-01-13

### Fixed
- **Remote Site Sync**: Converted sync operation to AJAX to prevent "headers already sent" error
- **Comment Fetching**: Now fetches up to 500 comments per sync (5 pages × 100) instead of just 100
- **Batch Output**: Enhanced processing log to show comment author, site name, and content snippet

### Changed
- Sync button now provides real-time status updates without page reload
- Batch processing output now includes:
  - Comment author name
  - Source site name for remote comments
  - First 20 words of comment content
  - Better formatting with colored borders
  - Improved visual hierarchy with indentation

### Improved
- Remote site sync now automatically loops through multiple pages
- Better user feedback during sync operations
- More informative batch processing logs for easier monitoring

## [1.0.0] - 2025-01-13

### Added
- Initial release of AI Comment Moderator
- Ollama AI integration for comment moderation
- Customizable prompt system with template variables
- Batch processing with configurable sizes (1-1000 comments)
- Real-time progress tracking with detailed logs
- Multi-site management via WordPress REST API
- Application Password authentication for remote sites
- Automatic decision syncing to remote sites
- User reputation system (0-100 scale)
- Confidence threshold controls
- Moderation queue dashboard with tabbed interface
- Analytics dashboard with Chart.js visualizations
- Export functionality (CSV, JSON, PDF)
- Webhook notifications (Slack, Discord, custom)
- Multi-model consensus voting system
- Background job processing for large batches
- Data preservation option on plugin uninstall
- GitHub automatic updates integration
- Test connection feature for Ollama and remote sites
- Re-processing option for already reviewed comments
- Comment status filtering (all, approved, pending)
- Context-aware prompts with post/author data
- Keyboard shortcuts for moderation queue (J/K navigation)
- Bulk operations in moderation queue
- Activity logs for webhooks and processing
- Health check system status indicators

### Features by Category

#### Core AI Moderation
- Custom prompt creation and management
- Template variable system for context
- AI decision parsing (approve/spam/toxic)
- Action configuration per decision type
- Processing time tracking
- Error handling with automatic retries

#### Multi-Site Management
- Add/remove remote WordPress sites
- Secure credential storage (encrypted)
- Manual and automatic comment syncing
- Per-site statistics tracking
- Batch operations across sites
- Site health monitoring

#### User Reputation
- Automatic score adjustments
- Approval/spam count tracking
- Threshold-based auto-approval
- Manual reputation overrides
- Historical comment tracking
- Whitelist management

#### Analytics
- Processing trends over time
- Decision breakdown charts
- Top flagged authors
- Accuracy metrics (override rate)
- Custom date range filtering
- Real-time statistics

#### Admin Interface
- Clean, intuitive dashboard
- Responsive design
- AJAX-powered interactions
- Real-time progress updates
- Inline comment preview
- Quick action buttons

### Security
- WordPress nonce verification
- Capability checks on all admin actions
- Input sanitization
- Output escaping
- Encrypted password storage
- Secure API communication

### Performance
- Lazy loading of classes
- Database query optimization
- Caching of AI responses
- Background processing for heavy tasks
- Transient use for temporary data
- Efficient batch processing

### Documentation
- Comprehensive README.md
- Usage guides for all features
- Troubleshooting documentation
- API integration guides
- Template variable reference
- Security best practices

## [Unreleased]

### Planned
- Machine learning from manual overrides
- Sentiment analysis integration
- Custom AI model training support
- Anti-spam plugin integration
- Mobile app for moderation
- Scheduled auto-moderation
- A/B testing for prompts
- Language detection
- Automatic translation support
- Advanced filtering options

---

## Version Numbering

- **Major** (x.0.0): Breaking changes, major features
- **Minor** (1.x.0): New features, backwards compatible
- **Patch** (1.0.x): Bug fixes, minor improvements

[1.0.0]: https://github.com/DroppedLink/ai-comment-moderator/releases/tag/v1.0.0
[Unreleased]: https://github.com/DroppedLink/ai-comment-moderator/compare/v1.0.0...HEAD

