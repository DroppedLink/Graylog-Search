# Remote Sites Management Guide

## Overview

The AI Comment Moderator now supports managing comments across multiple WordPress sites from a central location. You can add remote WordPress sites, fetch their pending comments, process them with AI, and automatically sync moderation decisions back to the remote sites.

## Features

- **Multi-Site Management**: Add and manage unlimited remote WordPress sites
- **Centralized Moderation**: Process comments from all sites in one place
- **Automatic Syncing**: AI decisions are automatically synced back to remote sites
- **Secure Authentication**: Uses WordPress Application Passwords for secure API access
- **Status Tracking**: Monitor sync status and pending comments per site
- **Batch Processing**: Process comments from specific sites or all sites at once

## Setup Instructions

### Step 1: Generate Application Password on Remote Site

On each remote WordPress site you want to manage:

1. Log in to the WordPress admin panel
2. Go to **Users → Profile** (or **Users → Your Profile**)
3. Scroll down to the **Application Passwords** section
4. Enter a name for the application password (e.g., "AI Comment Moderator")
5. Click **Add New Application Password**
6. Copy the generated password (it will look like: `xxxx xxxx xxxx xxxx xxxx xxxx`)
7. Save this password securely - you'll need it to add the site

> **Note**: Application Passwords require WordPress 5.6+ and your site must be using HTTPS or a localhost environment.

### Step 2: Add Remote Site to Central Dashboard

On your main WordPress site (where AI Comment Moderator is installed):

1. Go to **AI Moderator → Remote Sites**
2. Fill in the "Quick Add Site" form:
   - **Site Name**: A friendly name for the site (e.g., "My Blog")
   - **Site URL**: The full URL of the remote site (e.g., https://example.com)
   - **Username**: Your WordPress username on the remote site
   - **Application Password**: Paste the password you generated in Step 1
3. Click **Add Remote Site**

The plugin will test the connection and add the site if credentials are valid.

### Step 3: Fetch Comments from Remote Site

Once a site is added:

1. Click **Sync Now** next to the site in the Remote Sites list
2. The plugin will fetch pending comments from the remote site
3. Comments are stored locally for processing

### Step 4: Process Remote Comments

To moderate remote comments:

1. Go to **AI Moderator → Batch Processing**
2. In the **Comment Source** dropdown, select:
   - **All Remote Sites** to process comments from all remote sites
   - **[Specific Site Name]** to process comments from a single site
3. Choose your moderation prompt
4. Set the number of comments to process
5. Click **Start Processing**

The AI will review the comments and automatically sync decisions back to the remote sites.

## How It Works

### Architecture

```
┌─────────────────────────────────────────────────────┐
│         Central WordPress Site                       │
│  (AI Comment Moderator Installed)                   │
│                                                      │
│  ┌──────────────┐      ┌──────────────┐            │
│  │  Remote Site │      │   Local AI   │            │
│  │   Manager    │─────>│   Processing │            │
│  └──────────────┘      └──────────────┘            │
│         │                      │                     │
└─────────┼──────────────────────┼─────────────────────┘
          │                      │
          ↓                      ↓
    ┌─────────────┐        ┌─────────────┐
    │  Remote     │        │  Ollama AI  │
    │  WP Site 1  │        │   Server    │
    └─────────────┘        └─────────────┘
    ┌─────────────┐
    │  Remote     │
    │  WP Site 2  │
    └─────────────┘
```

### Data Flow

1. **Fetch**: Plugin connects to remote sites via WordPress REST API
2. **Store**: Comments are cached locally in `wp_ai_remote_comments` table
3. **Process**: AI analyzes comments using configured prompts
4. **Sync**: Moderation decisions are sent back via REST API
5. **Update**: Remote comment status is updated (approve/spam/trash)

## Database Tables

### wp_ai_remote_sites

Stores remote site configuration:

| Field | Type | Description |
|-------|------|-------------|
| id | mediumint | Primary key |
| site_name | varchar(255) | Display name |
| site_url | varchar(255) | Full site URL |
| username | varchar(100) | WordPress username |
| app_password | text | Encrypted app password |
| is_active | tinyint | Active status |
| last_sync | datetime | Last sync timestamp |
| total_comments | int | Total cached comments |
| pending_moderation | int | Unmoderated count |

### wp_ai_remote_comments

Caches remote comments locally:

| Field | Type | Description |
|-------|------|-------------|
| id | mediumint | Primary key |
| site_id | mediumint | FK to remote_sites |
| remote_comment_id | bigint | Comment ID on remote site |
| comment_author | varchar(255) | Author name |
| comment_author_email | varchar(100) | Author email |
| comment_content | text | Comment text |
| comment_date | datetime | Posted date |
| post_id | bigint | Post ID on remote |
| post_title | varchar(255) | Post title |
| comment_status | varchar(20) | hold/approved/spam |
| moderation_status | varchar(20) | pending/processed |
| ai_decision | varchar(20) | AI's decision |
| synced_back | tinyint | Sync status flag |

## API Endpoints Used

### Fetch Comments
```
GET /wp-json/wp/v2/comments?status=hold&per_page=100
Authorization: Basic base64(username:app_password)
```

### Update Comment Status
```
POST /wp-json/wp/v2/comments/{id}
Authorization: Basic base64(username:app_password)
Content-Type: application/json
Body: {"status": "approved"}
```

## Security Considerations

### Application Passwords
- Stored encrypted in database using WordPress AUTH_KEY
- Only transmitted over HTTPS connections
- Can be revoked anytime from remote site

### API Access
- Uses WordPress REST API authentication
- Requires valid user credentials on remote site
- Subject to remote site's permission settings

### Best Practices
1. Always use HTTPS for remote sites
2. Use dedicated user accounts with minimal permissions
3. Regularly rotate application passwords
4. Monitor webhook logs for unauthorized access
5. Keep WordPress and plugins updated on all sites

## Troubleshooting

### Connection Failed
- **Check URL**: Ensure the site URL is correct and includes https://
- **Verify Credentials**: Double-check username and application password
- **Test API**: Visit `https://yoursite.com/wp-json/wp/v2/` to ensure REST API is enabled
- **Check Permissions**: User must have permission to manage comments

### Comments Not Syncing
- **Check Site Status**: Ensure remote site is marked as "Active"
- **Verify Network**: Confirm your server can reach the remote site
- **Review Logs**: Check WordPress debug.log for detailed error messages
- **Test Manually**: Try the "Sync Now" button to force a sync

### Sync Failures
- **Rate Limiting**: Remote site may be rate-limiting API requests
- **Timeouts**: Large batches may time out (reduce batch size)
- **API Disabled**: Some hosts disable REST API
- **Authentication**: Application password may have been revoked

## Advanced Usage

### Custom Sync Intervals

You can set up automatic syncing using WordPress cron:

```php
add_action('wp', function() {
    if (!wp_next_scheduled('ai_moderator_auto_sync')) {
        wp_schedule_event(time(), 'hourly', 'ai_moderator_auto_sync');
    }
});

add_action('ai_moderator_auto_sync', function() {
    $sites = AI_Comment_Moderator_Remote_Site_Manager::get_sites(true);
    foreach ($sites as $site) {
        AI_Comment_Moderator_Remote_Site_Manager::fetch_comments($site->id, 100);
    }
});
```

### Webhook Integration

For real-time notifications when comments need moderation, configure webhooks in **AI Moderator → Settings** to notify you when:
- Remote sites have new pending comments
- Sync failures occur
- High spam volume detected

### Bulk Site Management

Use the dashboard to:
- Sync all sites at once
- Deactivate sites temporarily
- Export reports across all sites
- View aggregated statistics

## Performance Tips

1. **Batch Size**: Start with smaller batches (10-20) for remote comments
2. **Sync Frequency**: Don't sync more than once per hour per site
3. **Cache Duration**: Comments are cached locally to reduce API calls
4. **Background Processing**: Enable background processing for large batches

## Limitations

- Remote sites must run WordPress 5.6+ (for Application Passwords)
- Remote sites must have REST API enabled
- Maximum 100 comments per sync (REST API limit)
- Application Passwords require HTTPS or localhost

## Support

If you encounter issues:
1. Enable WordPress debug logging
2. Check error logs at `wp-content/debug.log`
3. Verify remote site API is accessible
4. Test credentials manually using curl or Postman
5. Review server firewall rules for outbound connections

## Future Enhancements

Planned features for remote site management:
- Real-time push notifications from remote sites
- Bulk site import via CSV
- Site groups for batch operations
- Custom sync schedules per site
- Site health monitoring
- Comment preview before sync

