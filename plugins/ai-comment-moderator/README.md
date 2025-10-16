# AI Comment Moderator

**Version:** 2.2.0  
**Author:** CSE  
**Requires:** WordPress 5.9+  
**Requires PHP:** 7.4+  
**License:** GPLv2 or later

AI-powered comment moderation plugin supporting multiple AI providers (Ollama, OpenAI, Claude, OpenRouter) with intelligent context analysis, structured reason codes, and advanced features.

## 🎯 Key Features

### Multi-Provider AI Support
- **Ollama** - Self-hosted, free, privacy-focused
- **OpenAI** - GPT-3.5, GPT-4, GPT-4 Turbo, GPT-4o
- **Claude** - Anthropic Claude 3 (Haiku, Sonnet, Opus, 3.5 Sonnet)
- **OpenRouter** - 100+ models with automatic fallback

### Intelligent Context Analysis
- **Sentiment Detection** - Positive, negative, neutral, toxic
- **Language Detection** - 13 languages supported
- **Thread Analysis** - Conversation depth and sentiment
- **Time Patterns** - Time of day, weekday/weekend detection
- **Site Context** - Auto-detects site category
- **User Reputation** - Tracks commenter history

### Advanced Features
- **Reason Codes** - Structured 1-10 codes for every moderation decision
- **Remote Site Management** - Moderate comments from multiple WordPress sites
- **Batch Processing** - Process hundreds of comments at once
- **Custom Prompts** - 28+ variables for intelligent decisions
- **Import/Export** - Share prompts between sites
- **Cost Tracking** - Monitor API usage and costs
- **Budget Alerts** - Set spending limits for paid providers
- **Analytics Dashboard** - Track performance and accuracy with reason code breakdown
- **Data Reset** - Clear processing history while preserving configuration

## 🚀 Quick Start

### 1. Installation

**Via WordPress Admin:**
1. Go to Plugins → Add New
2. Click "Upload Plugin"
3. Choose `ai-comment-moderator.zip`
4. Click "Install Now" and then "Activate"

**Manual Installation:**
1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate through the 'Plugins' menu in WordPress

### 2. Choose Your AI Provider

Navigate to **AI Moderator → Settings** and select a provider:

#### Option A: Ollama (Free, Self-Hosted)
1. [Install Ollama](https://ollama.ai/) on your server
2. Pull a model: `ollama pull llama2`
3. Enter Ollama URL (default: `http://localhost:11434`)
4. Click "Test Connection & Load Models"
5. Select your model
6. Save Settings

#### Option B: OpenAI
1. Get API key from [platform.openai.com](https://platform.openai.com/api-keys)
2. Select "OpenAI" as provider
3. Enter your API key (starts with `sk-`)
4. Select model (GPT-3.5 Turbo recommended for cost)
5. Set monthly budget alert
6. Save Settings

#### Option C: Claude (Anthropic)
1. Get API key from [console.anthropic.com](https://console.anthropic.com/)
2. Select "Claude (Anthropic)" as provider
3. Enter your API key (starts with `sk-ant-`)
4. Select model (Haiku is fastest and cheapest)
5. Set monthly budget alert
6. Save Settings

#### Option D: OpenRouter
1. Get API key from [openrouter.ai/keys](https://openrouter.ai/keys)
2. Select "OpenRouter" as provider
3. Enter your API key
4. Click "Test Connection" to load 100+ models
5. Select primary model and optional fallbacks
6. Save Settings

### 3. Configure Prompts

Go to **AI Moderator → Prompts**:
- Use default prompts or create custom ones
- Available variables: `{comment_content}`, `{author_name}`, `{comment_sentiment}`, `{user_reputation}`, and 24 more
- Import prompts from JSON or export to share

### 4. Start Moderating

**Batch Processing:**
1. Go to **AI Moderator → Batch Process**
2. Select comment status (pending, approved, or all)
3. Choose batch size (1-100 comments)
4. Click "Start Batch Processing"
5. Monitor real-time progress

**Remote Sites** (Optional):
1. Go to **AI Moderator → Remote Sites**
2. Add remote WordPress site with application password
3. Sync comments
4. Process comments from all sites in one place

## 📊 Cost Comparison

| Provider | Model | Cost per Comment* | Notes |
|----------|-------|------------------|-------|
| **Ollama** | Any | $0.00 | Self-hosted, you pay for hardware |
| **Claude** | Haiku | ~$0.0005 | Fastest, most affordable |
| **OpenAI** | GPT-3.5 Turbo | ~$0.001 | Good balance |
| **OpenRouter** | Varies | ~$0.001 avg | 100+ models to choose from |
| **Claude** | Sonnet | ~$0.005 | Better accuracy |
| **OpenAI** | GPT-4 Turbo | ~$0.02 | High accuracy |
| **Claude** | Opus | ~$0.02 | Most capable |
| **OpenAI** | GPT-4 | ~$0.05 | Premium accuracy |

*Estimates based on typical comment length (50-100 words)

**Example:** Processing 1,000 comments/month:
- Ollama: **$0** (free)
- Claude Haiku: **$0.50/month**
- GPT-3.5 Turbo: **$1/month**
- GPT-4 Turbo: **$20/month**

## 🎨 Context Variables

Use these in your prompts for smarter AI decisions:

### Basic Variables
- `{comment_content}` - The comment text
- `{author_name}` - Commenter's name
- `{author_email}` - Commenter's email
- `{post_title}` - Post title
- `{site_name}` - Your site name

### Context Intelligence (v2.1.0+)
- `{comment_sentiment}` - positive/negative/neutral/toxic
- `{comment_language}` - en/es/fr/de/etc (13 languages)
- `{thread_depth}` - Conversation nesting level
- `{thread_sentiment}` - Overall thread tone
- `{time_of_day}` - morning/afternoon/evening/night
- `{is_weekend}` - yes/no
- `{user_reputation}` - new/poor/neutral/good/excellent
- `{user_history}` - Comment statistics
- `{is_new_user}` - yes/no

[See all 28 variables →](docs/prompt-variables.md)

## 🏷️ Reason Codes

Every AI decision includes a structured reason code (1-10) for better analytics and transparency:

| Code | Reason | Category |
|------|--------|----------|
| 1 | Obvious spam - automated/bot content | 🚫 Critical |
| 2 | Malicious links detected | 🚫 Critical |
| 3 | Toxic/abusive language | 🚫 Critical |
| 4 | Off-topic or irrelevant | ⚠️ Warning |
| 5 | Multiple suspicious URLs | ⚠️ Warning |
| 6 | Low-quality content | ⚠️ Warning |
| 7 | Duplicate/repeated comment | ⚠️ Warning |
| 8 | Suspicious user patterns | ⚠️ Warning |
| 9 | Legitimate contribution | ✅ Approved |
| 10 | Approved - high quality content | ✅ Approved |

**Benefits:**
- **Better Analytics**: See which types of spam are most common
- **Improved Training**: Understand AI decision patterns
- **Enhanced Transparency**: Know exactly why each comment was moderated
- **Data-Driven Decisions**: Optimize your prompts based on reason code trends

View reason code breakdown in **AI Moderator → Analytics**.

## 📖 Documentation

- [Getting Started Guide](docs/getting-started.md)
- [Provider Setup Guides](docs/providers/)
  - [Ollama Setup](docs/providers/ollama.md)
  - [OpenAI Setup](docs/providers/openai.md)
  - [Claude Setup](docs/providers/claude.md)
  - [OpenRouter Setup](docs/providers/openrouter.md)
- [Prompt Writing Guide](docs/prompts.md)
- [Remote Sites Guide](docs/remote-sites.md)
- [Troubleshooting](docs/troubleshooting.md)
- [FAQ](docs/faq.md)

## 🔒 Security

- API keys encrypted using WordPress AUTH_KEY
- Nonce verification on all AJAX requests
- Capability checks on all admin pages
- Input sanitization and output escaping
- Prepared database statements
- HTTPS-only for API calls

## 🌍 Multilingual Support

- Plugin UI translatable (i18n ready)
- Detects comment language automatically
- Supports 13 languages for moderation
- RTL language support

## 🔧 System Requirements

### Minimum
- WordPress 5.9 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher
- 64MB PHP memory limit

### Recommended
- WordPress 6.4 or higher
- PHP 8.1 or higher
- MySQL 8.0 or higher
- 128MB PHP memory limit

### For Ollama
- Server with Docker support
- 8GB RAM minimum
- 4GB disk space for models

## 🤝 Contributing

We welcome contributions! Please:
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## 📝 Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.

### Latest: v2.1.0 (2025-01-15)
- ✨ Context-aware moderation with sentiment & language detection
- ✨ 13 new prompt variables for smarter decisions
- ✨ Import/Export prompts (JSON format)
- ✨ User reputation tracking
- ✨ Multilingual comment detection
- ✨ Thread-aware moderation

## 🆘 Support

- [GitHub Issues](https://github.com/DroppedLink/ai-comment-moderator/issues)
- [Documentation](docs/)

## 📜 License

This plugin is licensed under the GPL v2 or later.

```
Copyright (C) 2025 CSE

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## 🌟 Credits

Built by CSE with ❤️ for the WordPress community.

Special thanks to:
- Ollama team for local AI infrastructure
- OpenAI for GPT models
- Anthropic for Claude
- OpenRouter for unified API access

---

**Ready to moderate comments intelligently?** [Get Started →](#-quick-start)
