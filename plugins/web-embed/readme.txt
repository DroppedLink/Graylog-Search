=== Web Embed - Professional URL Embedding ===
Contributors: (your-wordpress-username)
Tags: embed, iframe, shortcode, dashboard, enterprise, object, url, builder, security
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Professional URL embedding with visual builder, security controls, caching, and smart fallback handling for enterprise applications.

== Description ==

Web Embed is a professional WordPress plugin that makes it easy to embed external URLs into your pages and posts using modern object/embed tags. Perfect for enterprise dashboards, monitoring tools, internal applications, and any web content you want to display on your WordPress site.

= Key Features =

* **Visual Shortcode Builder** - Create embeds with live preview, no coding required
* **Security Controls** - Domain whitelist, HTTPS-only mode, URL validation
* **Smart Caching** - WordPress transients-based caching for better performance
* **Responsive Design** - Automatic responsive embeds with aspect ratio control
* **Professional Fallbacks** - Beautiful fallback templates when content can't be embedded
* **Enterprise-Ready** - Designed for embedding internal tools and dashboards
* **Clean Admin Interface** - Top-level menu with tabs for easy navigation

= Perfect For =

* Internal company dashboards (Grafana, Kibana, custom tools)
* Monitoring systems and analytics platforms
* Google Maps, YouTube videos, and other embed-friendly services
* Enterprise applications behind corporate firewalls
* Documentation sites and knowledge bases
* Any web content that supports embedding

= Visual Builder =

The included visual builder makes creating embeds simple:

* Live preview before adding to your site
* Test URLs to check if they're embeddable
* Copy generated shortcodes with one click
* All options available through simple form interface
* Smart warnings for commonly blocked sites

= Security Features =

* **Domain Whitelist** - Restrict embeds to approved domains only
* **HTTPS Enforcement** - Optional HTTPS-only mode for secure connections
* **URL Validation** - Automatic validation of all URLs before embedding
* **Role-Based Access** - Control who can create embeds and modify settings

= How It Works =

Web Embed uses modern `<object>` and `<embed>` HTML tags (not iframes) to display external content. When a site blocks embedding (using X-Frame-Options headers), the plugin gracefully displays a customizable fallback message with a link to open the content directly.

= Shortcode Examples =

Basic embed:
`[web_embed url="https://example.com"]`

With custom dimensions:
`[web_embed url="https://example.com" width="100%" height="600px"]`

Responsive with styling:
`[web_embed url="https://dashboard.company.com" responsive="true" border="2px solid #ccc" border_radius="10px"]`

= Documentation =

The plugin includes comprehensive documentation:

* Quick Start Guide
* Usage Guide with examples
* Enterprise Apps Configuration Guide
* Embedding Guide (what works and why)
* Fallback Templates
* Troubleshooting documentation

= Support for Enterprise Apps =

Many enterprise frameworks (Spring Boot, Django, Rails, ASP.NET) block embedding by default. The plugin includes detailed guides on how to configure your applications to allow embedding from your WordPress site.

= Privacy =

Web Embed respects user privacy:

* No tracking or analytics by default
* No external API calls
* No data sent to third parties
* All processing happens on your server

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Go to Plugins → Add New
3. Search for "Web Embed"
4. Click "Install Now" and then "Activate"

= Manual Installation =

1. Download the plugin zip file
2. Go to Plugins → Add New → Upload Plugin
3. Choose the zip file and click "Install Now"
4. Activate the plugin

= After Activation =

1. Go to **Web Embed** in your WordPress admin menu
2. Click the **Settings** tab
3. Configure security settings (optional):
   - Enable domain whitelist if needed
   - Add trusted domains
   - Enable HTTPS-only mode (recommended)
4. Start using the **Builder** tab to create embeds!

== Frequently Asked Questions ==

= Why is my preview empty? =

Many major websites (Google.com, Facebook, Twitter, etc.) block embedding for security reasons using X-Frame-Options headers. This is normal and expected. The plugin will display your fallback message instead. 

For Google Maps and YouTube, use their embed-specific URLs (available from their share buttons).

= How do I embed Google Maps? =

1. Go to Google Maps and find your location
2. Click "Share" → "Embed a map"
3. Copy the URL from the iframe src attribute
4. Use that URL in Web Embed

= How do I embed YouTube videos? =

Use the embed URL format:
`https://www.youtube.com/embed/VIDEO_ID`

Not the watch URL:
`https://www.youtube.com/watch?v=VIDEO_ID`

= Can I embed my internal company dashboards? =

Yes! The plugin is specifically designed for this. However, your internal applications may need to be configured to allow embedding. See the included Enterprise Apps Configuration Guide (ENTERPRISE_APPS_GUIDE.md) for platform-specific instructions.

= What's the difference between this and an iframe? =

Web Embed uses modern `<object>` and `<embed>` tags which provide better fallback handling and are more semantically correct. The behavior is similar to iframes but with better browser support for fallback content.

= Does this work with page builders? =

Yes! The shortcode works with any page builder. Simply insert the generated shortcode where you want the embed to appear.

= Can I customize the fallback message? =

Absolutely! Use the `fallback` parameter in the shortcode:

`[web_embed url="https://example.com" fallback="<p>Custom message here</p>"]`

The plugin also includes preset fallback templates (see FALLBACK_TEMPLATES.md).

= Is this plugin GDPR compliant? =

Yes. The plugin doesn't track users, collect personal data, or send information to external services. The embedded content is loaded directly from the source URL.

= What happens if the URL is broken? =

The fallback message will be displayed, typically with a link to attempt opening the URL directly.

= Can I restrict who can create embeds? =

Yes. In Settings, you can configure:
- Who can use the builder (default: editors and above)
- Who can modify settings (default: administrators only)

= Does it work on mobile? =

Yes! The embeds are responsive and work great on mobile devices. The admin interface is also mobile-friendly.

== Screenshots ==

1. Visual Builder - Create embeds with live preview
2. Settings Page - Configure security, caching, and defaults
3. Shortcode Builder - All parameters available through simple form
4. Example Embed - Dashboard embedded in WordPress page
5. Fallback Message - Professional fallback when embedding is blocked
6. URL Validation - Test URLs before embedding

== Changelog ==

= 1.0.0 =
* Initial release
* Visual shortcode builder with live preview
* Security features (whitelist, HTTPS-only, validation)
* Caching system
* Professional fallback templates
* Comprehensive documentation
* Enterprise-focused design
* Top-level admin menu with tabs

== Upgrade Notice ==

= 1.0.0 =
Initial release of Web Embed - Professional URL Embedding plugin.

== Additional Info ==

= Recommended Use Cases =

* **Internal Dashboards**: Grafana, Kibana, Tableau, custom admin panels
* **Monitoring Tools**: System monitoring, log analysis, metrics
* **Maps**: Google Maps, Mapbox, OpenStreetMap
* **Videos**: YouTube, Vimeo (using embed URLs)
* **Collaboration**: Google Docs/Sheets/Slides (published versions)
* **Forms**: Google Forms, Typeform, JotForm
* **Code Demos**: CodePen, JSFiddle, CodeSandbox

= Known Limitations =

* Sites that block embedding (like main google.com, facebook.com) will show fallback messages
* Requires the source URL to not block embedding via X-Frame-Options or CSP headers
* Some browsers may limit embed functionality for security reasons

= Support =

For support, please:
1. Check the included documentation files
2. Review the FAQ above
3. Visit the plugin support forum on WordPress.org

= Contributing =

Web Embed is open source! Contributions are welcome.

= Credits =

Built with care for WordPress users who need professional embedding solutions.

