# Changelog

All notable changes to AI Comment Moderator will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

