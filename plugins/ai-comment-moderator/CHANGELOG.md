# Changelog

All notable changes to AI Comment Moderator will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2025-01-15

### 🎉 MAJOR RELEASE - Multi-Provider AI Support

This is a major architectural upgrade that adds support for multiple AI providers while maintaining backward compatibility with existing Ollama setups.

### Added

#### Multi-Provider Architecture
- **Provider System**: Flexible plugin architecture supporting multiple AI providers
  - `AI_Provider_Interface`: Standard interface for all providers
  - `AI_Provider_Factory`: Centralized provider management
  - Hot-swappable providers without code changes
  - Provider-specific configuration and validation

#### New AI Providers
- **OpenAI (GPT)**: Full integration with OpenAI's GPT models
  - Supports: GPT-3.5 Turbo, GPT-4, GPT-4 Turbo, GPT-4o
  - Automatic token counting and cost tracking
  - Budget alerts when monthly spending exceeds threshold
  - Per-token pricing calculation
  - Usage analytics (tokens, cost per comment)
  
- **Claude (Anthropic)**: Integration with Claude models
  - Supports: Claude 3 Opus, Sonnet, Haiku, and Claude 3.5 Sonnet
  - Message format conversion (Claude-specific API)
  - Token counting and cost estimation
  - Budget monitoring
  - Optimized for accuracy and safety
  
- **OpenRouter**: Universal gateway to 100+ AI models
  - Single API key for multiple providers
  - Dynamic model list fetched from OpenRouter API
  - Automatic fallback system (try alternate models if primary fails)
  - Cost tracking across different models
  - Access to models from OpenAI, Anthropic, Meta, Google, and more
  - Model comparison with pricing information

#### Database Enhancements
- **Provider Usage Tracking** (`wp_ai_provider_usage`)
  - Track tokens used per provider/model
  - Cost accumulation in USD
  - Comments processed count
  - Daily aggregated statistics
  - Historical usage data for analytics
  
- **Correction Tracking** (`wp_ai_corrections`)
  - Record when admin overrides AI decisions
  - Track which provider/model made errors
  - Confidence score at time of decision
  - Accuracy metrics for future analysis
  
- **Notification Queue** (`wp_ai_notifications`)
  - Queued notification system
  - Email and webhook notifications
  - Retry logic for failed deliveries
  - Status tracking (pending/sent/failed)

#### Provider-Specific Features
- **Ollama** (refactored):
  - Maintains all existing functionality
  - Implements new provider interface
  - Backward compatible with existing setups
  - Cost: $0 (self-hosted)
  
- **OpenAI**:
  - Encrypted API key storage
  - Model auto-discovery
  - Rate limiting (respects OpenAI quotas)
  - Budget alerts
  - Cost per comment calculation
  
- **Claude**:
  - Anthropic API v2023-06-01 support
  - System message support
  - Per-million-token pricing
  - Model-specific cost tracking
  
- **OpenRouter**:
  - Dynamic model listing (cached 1 hour)
  - Fallback model configuration
  - Universal API compatibility
  - Cost tracking from response headers

### Changed
- **Version**: 1.0.6 → 2.0.0 (major version bump)
- **Description**: "Ollama-powered..." → "Multi-provider AI moderation..."
- **Architecture**: Monolithic → Provider-based architecture
- **Database**: Added 3 new tables for tracking and notifications
- **Settings**: New provider selection dropdown
- **Configuration**: Provider-specific settings sections

### Technical Details

#### New Files
```
includes/providers/
├── ai-provider-interface.php      # Interface definition
├── ollama-provider.php             # Refactored Ollama
├── openai-provider.php             # OpenAI GPT integration
├── claude-provider.php             # Anthropic Claude integration
└── openrouter-provider.php         # OpenRouter gateway

includes/
└── ai-provider-factory.php         # Provider factory & registry
```

#### Provider Interface Methods
- `test_connection()` - Validate API credentials
- `get_models()` - Fetch available models
- `process_comment()` - Send comment to AI
- `get_provider_name()` - Internal identifier
- `get_provider_display_name()` - User-friendly name
- `supports_streaming()` - Streaming capability check
- `get_config_fields()` - Dynamic settings fields
- `validate_config()` - Configuration validation
- `estimate_cost()` - Pre-processing cost estimate

#### Cost Tracking
- OpenAI: $0.0005-$0.06 per 1K tokens (model-dependent)
- Claude: $0.00025-$0.075 per 1K tokens (model-dependent)
- OpenRouter: Variable by model (~$0.001 average)
- Ollama: $0 (self-hosted)

#### Database Schema
```sql
-- Provider usage tracking
CREATE TABLE wp_ai_provider_usage (
  provider varchar(50),
  model varchar(100),
  tokens_used int,
  cost_usd decimal(10,6),
  comments_processed int,
  date date,
  KEY provider_date (provider, date)
);

-- Correction tracking
CREATE TABLE wp_ai_corrections (
  comment_id bigint(20),
  ai_decision varchar(20),
  admin_decision varchar(20),
  provider varchar(50),
  model varchar(100),
  confidence int,
  corrected_at datetime
);

-- Notification queue
CREATE TABLE wp_ai_notifications (
  type varchar(50),
  recipient varchar(255),
  subject text,
  message text,
  status varchar(20),
  sent_at datetime
);
```

### Backward Compatibility
- ✅ Existing Ollama setups continue to work
- ✅ All current settings preserved
- ✅ Default provider set to 'ollama'
- ✅ Legacy `ollama-client.php` still included
- ✅ Database migrations run automatically
- ✅ No manual intervention required

### Migration Notes
- Plugin automatically creates new database tables on activation
- Existing Ollama configuration migrated to new provider system
- No data loss during upgrade
- Settings UI updated with provider selector
- Old settings remain accessible

### Known Limitations
- OpenRouter model list cached for 1 hour (to avoid rate limits)
- OpenAI/Claude require paid API keys
- Budget alerts require manual configuration
- Cost tracking depends on provider API responses

### Developer Notes
- Provider system is extensible - custom providers can be registered
- Use `ai_moderator_register_providers` action to add providers
- See `ai-provider-interface.php` for implementation guide
- Factory pattern used for provider instantiation
- Providers are cached per request for performance

### Next Steps (Phase 2)
Coming in v2.1.0:
- Settings UI redesign with provider-specific sections
- Advanced prompt variables (sentiment, site_context, user_history)
- Prompt templates library
- Multi-model consensus improvements
- Context analyzer for better decisions

## [1.0.6] - 2025-01-13

### Fixed
- **CRITICAL**: Fatal error when updating remote site password
  - Changed `encrypt_password()` and `decrypt_password()` from `private` to `public`
  - Error: "Call to private method from global scope"
  - Now password updates work correctly on remote site edit page

## [1.0.5] - 2025-01-13

### Fixed
- **Clear cache network error**: Added comprehensive error logging and improved AJAX error handling
- Better error messages show HTTP status codes and detailed error information
- Console logging for debugging "Clear Comment Cache" issues

### Improved  
- AJAX requests now use `$.ajax()` instead of `$.post()` for better error handling
- Error responses include detailed diagnostics (XHR status, response text, error details)
- Server-side logging added to `wp-content/debug.log` (if `WP_DEBUG_LOG` enabled)
- Clear status messages during operations ("Clearing cache...")

### Technical
- Added try/catch wrapper around clear cache AJAX handler
- Added `error_log()` statements at each step of cache clearing process
- Enhanced JavaScript error handler to log full XHR object for debugging

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

