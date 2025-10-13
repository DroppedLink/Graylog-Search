# AI Comment Moderator

An AI-powered WordPress plugin that automatically moderates comments using Ollama AI models to detect spam, toxic content, and inappropriate comments.

## Features

- **AI-Powered Moderation**: Uses Ollama AI models to automatically analyze and moderate comments
- **Flexible Prompt System**: Create custom prompts with variable substitution for different moderation scenarios
- **Batch Processing**: Process multiple comments at once with progress tracking
- **Real-time Processing**: Automatically process new comments as they are submitted
- **Configurable Actions**: Set different actions (approve, spam, trash, hold) based on AI decisions
- **Comprehensive Dashboard**: Monitor moderation statistics and recent activity
- **Rate Limiting**: Prevent API overload with configurable request limits

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Ollama instance running locally or remotely
- At least one Ollama model installed (e.g., llama2, mistral, etc.)

## Installation

1. Upload the plugin files to `/wp-content/plugins/ai-comment-moderator/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure your Ollama connection in the settings

## Configuration

### 1. Ollama Setup

1. Install Ollama on your server or use a remote instance
2. Pull at least one model: `ollama pull llama2`
3. Ensure Ollama is running and accessible

### 2. Plugin Configuration

1. Go to **AI Moderator > Settings** in your WordPress admin
2. Enter your Ollama URL (e.g., `http://localhost:11434`)
3. Test the connection and select a model
4. Configure batch size and rate limiting as needed
5. Enable auto-processing for new comments if desired

### 3. Prompt Management

1. Go to **AI Moderator > Prompts**
2. Review the default prompts or create custom ones
3. Configure actions for each AI decision type
4. Test prompts with sample data

## Usage

### Dashboard

The main dashboard provides:
- Overview of comment moderation statistics
- Recent AI moderation activity
- Quick access to batch processing and settings

### Batch Processing

1. Go to **AI Moderator > Batch Process**
2. Select a prompt to use for moderation
3. Set the number of comments to process
4. Click "Start Processing" and monitor progress

### Individual Comment Processing

Comments can be processed individually from:
- The comment edit screen (meta box)
- The comments list (bulk actions)

### Auto-Processing

When enabled, new comments are automatically processed using the default general moderation prompt.

## Prompt Variables

The following variables can be used in prompts:

- `{comment_content}` - The comment text
- `{author_name}` - Comment author name
- `{author_email}` - Comment author email
- `{author_url}` - Comment author URL
- `{comment_date}` - Comment date
- `{post_title}` - Title of the post being commented on
- `{post_url}` - URL of the post
- `{comment_id}` - Unique comment ID
- `{site_name}` - Your site name
- `{site_url}` - Your site URL

## Default Prompts

The plugin includes several default prompts:

### Spam Detection
Focuses on identifying promotional content, irrelevant links, and suspicious patterns.

### Toxicity Detection
Identifies harassment, hate speech, personal attacks, and offensive language.

### Quality Assessment
Evaluates whether comments add value to the discussion.

### General Moderation
Comprehensive moderation covering spam, toxicity, and quality.

## Actions

Based on AI analysis, the following actions can be taken:

- **Approve**: Allow the comment to be published
- **Spam**: Mark as spam (moves to spam folder)
- **Trash**: Move to trash
- **Hold**: Hold for manual moderation

## API Rate Limiting

To prevent overwhelming your Ollama instance:
- Configure requests per minute limit in settings
- The plugin automatically throttles requests
- Batch processing includes delays between requests

## Troubleshooting

### Connection Issues

1. Verify Ollama is running: `curl http://localhost:11434/api/tags`
2. Check firewall settings
3. Ensure the URL is correct (include http:// or https://)

### Model Issues

1. Verify models are installed: `ollama list`
2. Pull a model if needed: `ollama pull llama2`
3. Try a different model

### Performance Issues

1. Reduce batch size in settings
2. Increase rate limiting delay
3. Use a faster/smaller model
4. Check server resources

### Processing Errors

1. Check WordPress error logs
2. Verify prompt syntax
3. Test with simpler prompts
4. Check AI model response format

## Database Tables

The plugin creates three custom tables:

- `wp_ai_comment_reviews` - Tracks which comments have been reviewed
- `wp_ai_comment_prompts` - Stores custom prompts and configurations
- `wp_ai_comment_logs` - Logs all processing activity for audit purposes

## Security

- All AJAX requests are nonce-protected
- User capability checks ensure only administrators can access settings
- Input sanitization prevents XSS attacks
- SQL queries use prepared statements

## Performance Considerations

- Batch processing is chunked to prevent timeouts
- Rate limiting prevents API overload
- Database queries are optimized with proper indexing
- Transients are used for temporary batch data

## Hooks and Filters

### Actions

- `ai_comment_moderator_before_process` - Before processing a comment
- `ai_comment_moderator_after_process` - After processing a comment
- `ai_comment_moderator_batch_complete` - When batch processing completes

### Filters

- `ai_comment_moderator_prompt_variables` - Modify available prompt variables
- `ai_comment_moderator_ai_response` - Filter AI response before parsing
- `ai_comment_moderator_decision` - Modify AI decision before applying action

## Support

For support and bug reports, please check:
1. WordPress error logs
2. Plugin settings and configuration
3. Ollama connection and models
4. Server resources and performance

## Changelog

### Version 1.0.0
- Initial release
- Basic AI comment moderation
- Prompt management system
- Batch processing
- Dashboard and statistics
- Ollama integration

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Built with WordPress best practices and modern AI integration techniques.
