# AI Comment Moderator v2.0.0 Release Notes

## 🎉 Major Release: Multi-Provider AI Support

We're excited to announce version 2.0.0 of AI Comment Moderator, a major architectural upgrade that brings support for multiple AI providers while maintaining full backward compatibility with existing installations.

## What's New

### Multi-Provider Architecture

The plugin now supports **four AI providers out of the box**:

1. **Ollama** (self-hosted, free)
   - Continue using your local Ollama installation
   - No API costs
   - Complete privacy and data control

2. **OpenAI** (GPT models)
   - GPT-3.5 Turbo, GPT-4, GPT-4 Turbo, GPT-4o
   - Industry-leading accuracy
   - Automatic cost tracking

3. **Claude** (Anthropic)
   - Claude 3 Opus, Sonnet, Haiku
   - Claude 3.5 Sonnet
   - Optimized for safety and nuance

4. **OpenRouter** (100+ models)
   - Single API key for multiple providers
   - Automatic fallback if primary model fails
   - Access to latest models from all major providers

### Key Features

#### Seamless Provider Switching
- Switch between providers with a single dropdown
- No code changes required
- Settings automatically adapt to selected provider

#### Cost Tracking & Budgets
- Real-time token and cost tracking
- Set monthly budget alerts
- Per-model cost analytics
- Historical usage reports

#### Automatic Fallback (OpenRouter)
- Configure backup models
- If primary fails, automatically try fallback
- Never lose moderation capability

#### Enhanced Analytics
- Track which provider/model made each decision
- Compare accuracy across providers
- Monitor correction rates (when you override AI)
- Identify best models for your content

## Installation

### New Installations
1. Download `ai-comment-moderator.zip`
2. Upload to WordPress → Plugins → Add New
3. Activate the plugin
4. Choose your preferred AI provider in Settings

### Upgrading from v1.x
1. Backup your database (recommended)
2. Deactivate and delete old plugin
3. Install v2.0.0
4. Activate plugin
5. Your existing Ollama setup will continue working
6. Optionally add other providers in Settings

**Note**: All your existing prompts, settings, and remote sites will be preserved!

## Getting Started

### Using OpenAI

1. Get an API key from [platform.openai.com](https://platform.openai.com/api-keys)
2. Go to AI Comment Moderator → Settings
3. Select "OpenAI" as provider
4. Enter your API key (starts with `sk-`)
5. Choose a model (GPT-3.5 Turbo recommended for cost)
6. Test connection
7. Set a monthly budget alert (optional)

### Using Claude

1. Get an API key from [console.anthropic.com](https://console.anthropic.com/)
2. Go to AI Comment Moderator → Settings
3. Select "Claude (Anthropic)" as provider
4. Enter your API key (starts with `sk-ant-`)
5. Choose a model (Haiku is fastest and cheapest)
6. Test connection
7. Set a monthly budget alert (optional)

### Using OpenRouter

1. Get an API key from [openrouter.ai/keys](https://openrouter.ai/keys)
2. Go to AI Comment Moderator → Settings
3. Select "OpenRouter" as provider
4. Enter your API key
5. Browse and select from 100+ models
6. (Optional) Configure fallback models
7. Test connection

## Cost Comparison

### OpenAI
- **GPT-3.5 Turbo**: ~$0.001 per comment
- **GPT-4 Turbo**: ~$0.02 per comment
- **GPT-4**: ~$0.05 per comment

### Claude
- **Haiku**: ~$0.0005 per comment
- **Sonnet**: ~$0.005 per comment
- **Opus**: ~$0.02 per comment

### OpenRouter
- Varies by model
- Average: ~$0.001 per comment
- Can use same pricing as source provider

### Ollama
- **Free** (self-hosted)
- You pay for hardware/hosting only

**Example**: Processing 1,000 comments/month:
- Ollama: $0
- Claude Haiku: ~$0.50/month
- GPT-3.5 Turbo: ~$1/month
- GPT-4 Turbo: ~$20/month

## Technical Details

### New Database Tables
- `wp_ai_provider_usage` - Track tokens, costs, usage per provider
- `wp_ai_corrections` - Record when admins override AI decisions
- `wp_ai_notifications` - Queue for email and webhook notifications

### New Configuration Options
- `ai_comment_moderator_active_provider` - Selected provider
- `ai_comment_moderator_openai_api_key` - OpenAI API key (encrypted)
- `ai_comment_moderator_claude_api_key` - Claude API key (encrypted)
- `ai_comment_moderator_openrouter_api_key` - OpenRouter API key (encrypted)
- Plus model selections and budget alerts for each provider

### API Security
- All API keys are encrypted using WordPress AUTH_KEY
- Keys stored as base64-encoded strings
- Never displayed in plain text after saving
- Secure transmission over HTTPS only

### Provider Architecture
- Implements `AI_Provider_Interface` for consistency
- Factory pattern for provider instantiation
- Lazy loading for performance
- Extensible - add custom providers via hooks

## Backward Compatibility

✅ **100% backward compatible with v1.x**

- Existing Ollama configurations automatically migrated
- All prompts, settings, and data preserved
- Default provider set to Ollama
- No manual migration required
- Database tables created automatically

## Known Issues & Limitations

1. **OpenRouter Model Cache**: Model list cached for 1 hour. Click "Refresh" to force update.
2. **API Key Requirements**: OpenAI, Claude, and OpenRouter require paid API keys.
3. **Budget Alerts**: Must be manually configured; not automatic.
4. **Cost Accuracy**: Depends on accurate token reporting from providers.
5. **Rate Limits**: Each provider has different rate limits; see their documentation.

## Troubleshooting

### "Invalid API Key" Error
- Verify key starts with correct prefix (sk- for OpenAI, sk-ant- for Claude)
- Check for extra spaces when pasting
- Ensure key hasn't been revoked in provider dashboard

### "Rate Limit Exceeded"
- Wait 60 seconds and try again
- Increase rate limit in Settings
- Consider upgrading API plan with provider

### Connection Test Fails
- Check your server can reach the internet
- Verify firewall doesn't block HTTPS requests
- Try a different provider to isolate issue

### Costs Higher Than Expected
- Check budget alert settings
- Review token usage in Analytics
- Consider using cheaper models (Haiku, GPT-3.5)
- Use Ollama for zero-cost alternative

## Upgrading Notes

### From v1.0.x to v2.0.0

**Before Upgrade:**
1. Backup your database
2. Export your settings (Settings → Data Management)
3. Note your current Ollama URL and model

**After Upgrade:**
1. Verify Ollama still works
2. Check all remote sites still sync
3. Test batch processing
4. Review analytics dashboard

**Rollback Plan:**
- Keep v1.0.6 ZIP file as backup
- Database tables are backward compatible
- Deactivate v2.0.0, upload v1.0.6, activate

## Support & Feedback

- **Issues**: [GitHub Issues](https://github.com/DroppedLink/ai-comment-moderator/issues)
- **Documentation**: See README.md in plugin directory
- **Updates**: Plugin checks for updates automatically

## What's Next?

### Coming in v2.1.0 (Phase 2)
- Redesigned settings UI with tabbed interface
- Prompt templates library
- Advanced context variables (sentiment, thread analysis)
- Import/export prompts
- Multi-model consensus improvements
- Context analyzer for smarter decisions

### Coming in v2.2.0 (Phase 3)
- Focused analytics dashboard with charts
- Email notification system
- Enhanced moderation queue
- Keyboard shortcuts (J/K navigation)
- Integrations: Akismet, WooCommerce, BuddyPress

### Coming in v3.0.0 (Phase 4)
- WordPress.org marketplace submission
- Professional documentation
- Video tutorials
- Internationalization (i18n)
- Accessibility improvements (WCAG 2.1 AA)

## Credits

Developed by **CSE**

Thanks to the community for feedback and testing!

## License

This plugin is released under the GPL v2 or later.

---

**Happy Moderating!** 🎉

If you find this plugin useful, please consider:
- ⭐ Starring the [GitHub repository](https://github.com/DroppedLink/ai-comment-moderator)
- 📝 Writing a review (coming to WordPress.org soon)
- 🐛 Reporting bugs to help us improve
- 💡 Suggesting new features

