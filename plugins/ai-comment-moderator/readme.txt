=== AI Comment Moderator ===
Contributors: cse
Tags: comments, moderation, ai, spam, ollama, openai, claude
Requires at least: 5.9
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 2.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Intelligent AI-powered comment moderation supporting Ollama, OpenAI, Claude, and OpenRouter. Smart, multilingual, and context-aware.

== Description ==

**AI Comment Moderator** brings the power of artificial intelligence to WordPress comment moderation. Choose from multiple AI providers and let intelligent algorithms help you manage comments more efficiently.

= 🎯 Why AI Comment Moderator? =

* **Multi-Provider Support**: Use Ollama (free), OpenAI, Claude, or OpenRouter
* **Smart Context Analysis**: AI understands sentiment, language, user history, and thread context
* **Batch Processing**: Moderate hundreds of comments in minutes
* **Cost Tracking**: Monitor API usage and set budget alerts
* **Remote Sites**: Manage comments from multiple WordPress sites in one dashboard
* **Customizable Prompts**: 28+ variables for intelligent decision-making

= 🚀 Key Features =

**AI Providers**
* **Ollama** - Self-hosted, free, privacy-first
* **OpenAI** - GPT-3.5, GPT-4, GPT-4 Turbo, GPT-4o
* **Claude** - Anthropic Claude 3 family (Haiku, Sonnet, Opus)
* **OpenRouter** - 100+ models with automatic fallback

**Intelligent Analysis**
* Sentiment detection (positive, negative, neutral, toxic)
* Language detection (13 languages supported)
* Thread analysis (conversation depth and context)
* Time patterns (day/night, weekday/weekend)
* Site context awareness
* User reputation tracking

**Advanced Features**
* Batch process pending or approved comments
* Custom AI prompts with 28+ variables
* Import/Export prompts in JSON format
* Real-time progress tracking
* Remote WordPress site management
* Detailed analytics dashboard
* Budget alerts for paid APIs

= 💰 Cost-Effective =

**Free Option**: Use Ollama for completely free, self-hosted AI moderation.

**Paid Options**: Starting from $0.0005 per comment with Claude Haiku.

Process 1,000 comments/month for as little as $0.50!

= 🌍 Multilingual =

Automatically detects and moderates comments in:
English, Spanish, French, German, Portuguese, Italian, Chinese, Japanese, Korean, Arabic, Russian, and more.

= 🔒 Privacy & Security =

* API keys encrypted using WordPress standards
* All data processed securely
* No comment data leaves your server (with Ollama)
* GDPR compliant

== Installation ==

= Automatic Installation =

1. Go to Plugins → Add New
2. Search for "AI Comment Moderator"
3. Click "Install Now"
4. Activate the plugin

= Manual Installation =

1. Download the plugin ZIP file
2. Go to Plugins → Add New → Upload Plugin
3. Choose the downloaded file
4. Click "Install Now" and "Activate"

= Quick Setup =

1. **Choose Provider**: Go to AI Moderator → Settings
2. **Configure**: Enter API key (or Ollama URL)
3. **Test**: Click "Test Connection & Load Models"
4. **Select Model**: Choose your preferred AI model
5. **Start**: Go to Batch Process and moderate!

== Frequently Asked Questions ==

= Do I need an API key? =

Only if you choose OpenAI, Claude, or OpenRouter. Ollama is completely free and doesn't require an API key.

= How much does it cost? =

* **Ollama**: Free (self-hosted)
* **Claude Haiku**: ~$0.0005 per comment
* **OpenAI GPT-3.5**: ~$0.001 per comment
* **OpenAI GPT-4**: ~$0.02-0.05 per comment

= Can I use multiple AI providers? =

Yes! You can switch between providers anytime in Settings. The multi-model consensus feature can even use multiple providers simultaneously for more accurate decisions.

= Is my data safe? =

Yes. API keys are encrypted. If you use Ollama, all data stays on your server. With cloud providers, only comment text is sent via secure HTTPS connections.

= Does it work with non-English comments? =

Absolutely! The plugin automatically detects 13 languages and provides appropriate context to the AI.

= Can I moderate comments from multiple WordPress sites? =

Yes! The Remote Sites feature lets you manage comments from multiple WordPress installations in one dashboard.

= What if the AI makes a mistake? =

You have full control. Review AI decisions, approve/reject manually, and the plugin tracks corrections to monitor accuracy over time.

= Does it work with custom comment plugins? =

It works with standard WordPress comments. Compatibility with plugins like Disqus or wpDiscuz depends on whether they use WordPress's native comment system.

== Screenshots ==

1. Dashboard Overview - See processing stats, unreviewed comments, and recent activity at a glance
2. Provider Selection - Choose from Ollama, OpenAI, Claude, or OpenRouter with dynamic settings
3. Batch Processing - Process hundreds of comments with real-time progress tracking
4. Analytics Dashboard - Track performance, costs, and accuracy trends
5. Prompt Customization - Create intelligent prompts with 28+ context variables
6. Remote Sites - Manage comments from multiple WordPress sites in one place
7. Settings Page - Intuitive configuration with connection testing
8. Moderation Queue - Review AI decisions with full comment context

== Changelog ==

= 2.1.0 - 2025-01-15 =
**Context-Aware Moderation Release**

* Added: Context Analyzer with sentiment, language, and thread analysis
* Added: 13 new prompt variables (sentiment, language, reputation, time context, etc.)
* Added: Import/Export prompts in JSON format
* Added: User reputation tracking system
* Added: Multilingual comment detection (13 languages)
* Added: Thread-aware moderation
* Added: Time pattern recognition (day/night, weekday/weekend)
* Added: Site context detection
* Improved: Prompt system now supports 28+ variables
* Improved: Better decision accuracy with full context
* Performance: Context analysis adds <50ms per comment
* Compatibility: 100% backward compatible

= 2.0.1 - 2025-01-15 =
* Added: Complete settings UI with multi-provider support
* Added: Provider selection dropdown
* Added: Dynamic settings sections per provider
* Added: Connection testing for all providers
* Added: Model auto-population from APIs
* Added: Budget alert configuration
* Added: Cost estimates per model
* Improved: Better error messages
* Improved: Smooth provider switching
* Fixed: API key encryption during testing
* Fixed: Settings persistence during tests

= 2.0.0 - 2025-01-15 =
**Major Release: Multi-Provider AI Support**

* Added: AI Provider Interface and Factory pattern
* Added: OpenAI provider (GPT-3.5, GPT-4, GPT-4 Turbo, GPT-4o)
* Added: Claude provider (Anthropic Claude 3 family)
* Added: OpenRouter provider (100+ models with fallback)
* Added: Cost tracking per provider/model
* Added: Budget alerts for paid APIs
* Added: Provider usage analytics
* Added: Database tables for usage and corrections
* Refactored: Ollama client into provider architecture
* Improved: Settings page with provider switcher
* Improved: Better error handling across providers
* Compatibility: 100% backward compatible with v1.x

= 1.0.6 - 2025-01-13 =
* Fixed: Fatal error when updating remote site password
* Fixed: Encrypt/decrypt methods visibility issue

= 1.0.0 - 2025-01-13 =
* Initial release
* Ollama AI integration
* Batch processing
* Remote site management
* Custom prompts
* Analytics dashboard

== Upgrade Notice ==

= 2.1.0 =
Major update with context-aware moderation! AI now analyzes sentiment, language, user reputation, and more for smarter decisions. 100% backward compatible.

= 2.0.0 =
Massive upgrade adding OpenAI, Claude, and OpenRouter support! You can now choose from 4 different AI providers. Fully backward compatible with Ollama setups.

== Support ==

For support, feature requests, or bug reports:
* GitHub: [github.com/DroppedLink/ai-comment-moderator](https://github.com/DroppedLink/ai-comment-moderator)
* Issues: [github.com/DroppedLink/ai-comment-moderator/issues](https://github.com/DroppedLink/ai-comment-moderator/issues)

== Privacy Policy ==

AI Comment Moderator processes comment data to provide moderation services:

**Data Processed:**
* Comment content, author name, author email, post context
* User commenting history (stored locally)
* AI decisions and confidence scores

**Data Storage:**
* All data stored in your WordPress database
* API keys encrypted using WordPress AUTH_KEY
* No data shared with third parties except chosen AI provider

**Third-Party Services:**
When using cloud AI providers (OpenAI, Claude, OpenRouter):
* Comment text sent via HTTPS for analysis
* No personal data stored by providers (per their policies)
* You control which provider to use

**Ollama Users:**
* All data stays on your server
* No external services contacted
* Complete privacy and data control

== Developer Documentation ==

**Filters:**
* `ai_moderator_prompt_before_send` - Modify prompt before sending to AI
* `ai_moderator_decision_override` - Override AI decision
* `ai_moderator_providers` - Register custom providers
* `ai_moderator_cost_calculation` - Customize cost calculation

**Actions:**
* `ai_moderator_comment_processed` - Fires after comment processed
* `ai_moderator_spam_detected` - Fires when spam detected
* `ai_moderator_model_switched` - Fires when switching models

**REST API:**
* `GET /wp-json/ai-moderator/v1/providers` - List providers
* `POST /wp-json/ai-moderator/v1/process-comment` - Process comment
* `GET /wp-json/ai-moderator/v1/stats` - Get statistics

See full developer documentation on GitHub.

