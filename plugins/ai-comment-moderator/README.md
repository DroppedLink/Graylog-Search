# AI Comment Moderator WordPress Plugin

A powerful WordPress plugin that uses AI (via Ollama) to automatically moderate comments, detect spam, manage multiple remote WordPress sites, and provide comprehensive analytics.

## Features

### 🤖 AI-Powered Moderation

* **Ollama Integration**: Uses local AI models for privacy and control
* **Customizable Prompts**: Create and manage multiple moderation prompts
* **Template Variables**: Include context like post content, author history, categories
* **Multi-Model Consensus**: Optional voting system using multiple AI models
* **Confidence Thresholds**: Granular control over auto-approve/reject decisions

### 🔄 Batch Processing

* **Configurable Batch Sizes**: Process 1-1000 comments at once
* **Status Filtering**: Process approved, pending, or all comments
* **Re-processing**: Option to re-evaluate already reviewed comments
* **Progress Tracking**: Real-time progress indicators with detailed logs
* **Background Jobs**: Handle large batches without browser timeouts

### 🌐 Multi-Site Management

* **Remote Sites**: Connect to multiple WordPress sites via REST API
* **Application Passwords**: Secure authentication using WordPress native tokens
* **Centralized Moderation**: Process comments from all sites in one place
* **Automatic Sync**: AI decisions automatically pushed back to remote sites
* **Site Statistics**: Track pending comments and sync status per site

### 📊 Analytics & Reporting

* **Dashboard Charts**: Visualize moderation trends over time
* **Decision Breakdown**: See AI decisions (approve/spam/trash) with pie charts
* **Top Flagged Authors**: Identify problematic commenters
* **Export Reports**: CSV, JSON, and PDF exports for compliance
* **Custom Date Ranges**: Filter analytics by specific time periods

### 👥 User Reputation System

* **Reputation Scores**: Track commenters (0-100 scale)
* **Auto-Trust**: Users above threshold skip AI moderation
* **Historical Tracking**: Approved/spam counts per user
* **Manual Adjustments**: Admin can modify reputation scores
* **Smart Whitelisting**: Good users get faster approvals

### 🔔 Webhooks & Notifications

* **Real-Time Alerts**: Send notifications to Slack, Discord, or custom endpoints
* **Event Triggers**: Toxic content, high spam volume, low confidence decisions
* **Activity Logs**: Track all webhook calls with responses
* **Test Mode**: Verify webhook configuration before going live
* **Retry Logic**: Automatic retries for failed webhook calls

### 🎯 Moderation Queue

* **Dedicated Review Interface**: Tabbed views for flagged, low-confidence comments
* **Inline Actions**: Approve/reject without leaving the page
* **Bulk Operations**: Process multiple comments at once
* **Keyboard Shortcuts**: J/K navigation for efficient reviewing
* **Post Context**: See full post details when reviewing comments

### 💾 Data Management

* **Preserve on Uninstall**: Option to keep data when deleting plugin
* **Database Migration**: Smooth updates with schema versioning
* **No Duplication**: Smart activation that prevents duplicate data
* **Cleanup Tools**: Manual database cleanup options available

## Installation

### From GitHub Release

1. Download the latest `ai-comment-moderator.zip` from [Releases](https://github.com/DroppedLink/ai-comment-moderator/releases)
2. Go to **WordPress Admin → Plugins → Add New**
3. Click **Upload Plugin**
4. Choose the downloaded .zip file
5. Click **Install Now** and then **Activate**

### From WordPress Admin (with auto-updates)

1. Install from the first method
2. Plugin will automatically check for updates from GitHub
3. Update notifications appear in WordPress admin
4. One-click updates like any WordPress.org plugin

## Requirements

* **WordPress**: 5.0 or higher
* **PHP**: 7.2 or higher
* **Ollama**: Running instance with API access
* **For Remote Sites**: WordPress 5.6+ with REST API enabled

## Configuration

### 1. Ollama Setup

1. Go to **AI Moderator → Settings**
2. Enter your Ollama URL (e.g., `http://localhost:11434`)
3. Click **Test Connection & Load Models**
4. Select your preferred AI model
5. Set batch size and rate limits
6. Save settings

### 2. Creating Prompts

1. Go to **AI Moderator → Prompts**
2. Click **Add New Prompt**
3. Enter prompt name and description
4. Write your moderation instructions with template variables:
   ```
   You are moderating comments on a WordPress site.
   
   Post Title: {post_title}
   Comment Author: {author_name}
   Comment Content: {comment_content}
   Author's Previous Comments: {author_previous_comments}
   
   Determine if this comment is:
   - APPROVE: Appropriate and constructive
   - SPAM: Spam or promotional
   - TOXIC: Rude, offensive, or harmful
   ```
5. Configure actions for each decision type
6. Save and activate prompt

### 3. Adding Remote Sites

1. On the **remote WordPress site**:
   * Go to **Users → Profile**
   * Scroll to **Application Passwords**
   * Create new password named "AI Moderator"
   * Copy the generated password

2. On your **central site** (where plugin is installed):
   * Go to **AI Moderator → Remote Sites**
   * Fill in the Quick Add form:
     - **Site Name**: Friendly name
     - **Site URL**: Full URL with https://
     - **Username**: WordPress username on remote site
     - **Application Password**: Paste the password
   * Click **Test Connection First** to verify
   * Click **Add Remote Site**
   * Click **Sync Now** to fetch comments

### 4. Processing Comments

**Local Comments:**
1. Go to **AI Moderator → Batch Processing**
2. Select "Local Site Comments" from source dropdown
3. Choose comment status filter (All, Approved, Pending)
4. Select your prompt
5. Set number of comments to process
6. Click **Start Processing**

**Remote Comments:**
1. Go to **AI Moderator → Batch Processing**
2. Select remote site from "Comment Source" dropdown
3. Select your prompt
4. Set batch size
5. Click **Start Processing**
6. Decisions automatically sync back to remote site

## Template Variables

Use these in your prompts for context-aware moderation:

| Variable | Description |
|----------|-------------|
| `{comment_content}` | The comment text |
| `{author_name}` | Commenter's name |
| `{author_email}` | Commenter's email |
| `{author_url}` | Commenter's website |
| `{comment_date}` | When comment was posted |
| `{post_title}` | Title of the post |
| `{post_content}` | Post content (trimmed to 100 words) |
| `{post_categories}` | Comma-separated categories |
| `{post_tags}` | Comma-separated tags |
| `{comment_count}` | Number of comments on post |
| `{author_previous_comments}` | How many comments author has made |
| `{comment_id}` | Comment ID |
| `{site_name}` | Site name (useful for remote sites) |
| `{site_url}` | Site URL |

## Auto-Updates Setup

The plugin automatically checks for updates from GitHub.

**Optional: GitHub Token for Higher Rate Limits**

1. Generate a GitHub personal access token (no special permissions needed)
2. Go to **AI Moderator → Settings**
3. Scroll to **GitHub Updates** section
4. Enter your token
5. Save settings

This increases API rate limits from 60 to 5000 requests/hour.

## Documentation

* **USAGE_GUIDE.md**: Detailed user guide
* **REMOTE_SITES_GUIDE.md**: Multi-site management guide
* **TROUBLESHOOTING_REMOTE_SITES.md**: Common issues and solutions
* **DATA_PRESERVATION_GUIDE.md**: Data management options
* **UPGRADE_NOTES.md**: Professional features overview

## Version History

### v1.0.0 (Latest)

* Initial release
* AI-powered comment moderation with Ollama
* Customizable prompts with template variables
* Batch processing with progress tracking
* Multi-site remote management
* User reputation system
* Confidence thresholds
* Moderation queue interface
* Analytics dashboard
* Export reports (CSV/JSON/PDF)
* Webhook notifications
* Multi-model consensus voting
* Background job processing
* Data preservation on uninstall
* Automatic GitHub updates

## Architecture

```
┌─────────────────────────────────────────┐
│     WordPress Central Site              │
│  (AI Comment Moderator Installed)       │
│                                          │
│  ┌────────────┐      ┌────────────┐     │
│  │  Comment   │      │    AI      │     │
│  │ Processor  │─────>│ Moderation │     │
│  └────────────┘      └────────────┘     │
│         │                    │           │
│         │                    ↓           │
│         │            ┌────────────┐      │
│         │            │   Ollama   │      │
│         │            │   Server   │      │
│         │            └────────────┘      │
│         │                                │
│         ↓                                │
│  ┌────────────┐                          │
│  │   Remote   │                          │
│  │   Site     │                          │
│  │  Manager   │                          │
│  └────────────┘                          │
│         │                                │
└─────────┼────────────────────────────────┘
          │
          ↓
    ┌─────────────┐
    │  Remote     │
    │  WP Site 1  │
    └─────────────┘
    ┌─────────────┐
    │  Remote     │
    │  WP Site 2  │
    └─────────────┘
```

## Database Tables

* `wp_ai_comment_reviews` - Comment moderation history
* `wp_ai_comment_prompts` - Custom prompts
* `wp_ai_comment_logs` - Processing logs
* `wp_ai_comment_reputation` - User reputation scores
* `wp_ai_background_jobs` - Batch job tracking
* `wp_ai_webhook_log` - Webhook activity log
* `wp_ai_remote_sites` - Remote site configurations
* `wp_ai_remote_comments` - Cached remote comments

## Security

* Application passwords encrypted using WordPress AUTH_KEY
* REST API authentication for remote sites
* Nonce verification on all AJAX requests
* Capability checks (`manage_options`) for admin features
* Sanitization of all user inputs
* Secure webhook payload delivery

## Support

For issues, questions, or feature requests:
* **GitHub Issues**: [https://github.com/DroppedLink/ai-comment-moderator/issues](https://github.com/DroppedLink/ai-comment-moderator/issues)
* **Documentation**: See `/docs` folder in plugin
* **WordPress Debug Log**: Enable `WP_DEBUG_LOG` for detailed error logging

## License

This plugin is provided for use with Ollama AI systems.

## Credits

**Author**: CSE  
**Repository**: [https://github.com/DroppedLink/ai-comment-moderator](https://github.com/DroppedLink/ai-comment-moderator)

Developed for administrators who need powerful, AI-driven comment moderation across single or multiple WordPress installations.

## Roadmap

**Planned Features:**
* Machine learning from manual overrides
* Sentiment analysis scoring
* Custom AI model training
* Integration with popular anti-spam plugins
* Mobile app for moderation queue
* Scheduled auto-moderation
* A/B testing for prompts
* Language detection and translation

## Contributing

Contributions welcome! Please:
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for detailed version history.
