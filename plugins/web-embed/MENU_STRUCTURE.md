# Web Embed Plugin - Menu Structure

## Admin Menu Organization

The Web Embed plugin appears as a **top-level menu item** in the WordPress admin sidebar, similar to Graylog Search and other major plugins.

### Menu Hierarchy

```
WordPress Admin Sidebar:
├── Dashboard
├── Posts
├── Media
├── Pages
├── ...
├── Graylog Search
│   ├── Graylog Search
│   └── Settings
├── Web Embed ← NEW TOP-LEVEL MENU
│   ├── Builder (default page)
│   └── Settings
├── Settings
│   ├── General
│   ├── Writing
│   └── ...
```

## Menu Details

### Top-Level Menu: Web Embed

- **Icon**: `dashicons-embed-generic` (embed/code icon)
- **Position**: 30 (after Comments, before Appearance)
- **Required Capability**: `edit_posts` (available to editors and above)
- **Default Page**: Shortcode Builder

### Submenu Structure

#### 1. Builder (Main Page)
- **URL**: `admin.php?page=web-embed`
- **Title**: "Shortcode Builder"
- **Submenu Label**: "Builder"
- **Capability**: `edit_posts`
- **Function**: `web_embed_main_page()`
- **Description**: Visual interface for creating and testing shortcodes with live preview

#### 2. Settings
- **URL**: `admin.php?page=web-embed-settings`
- **Title**: "Web Embed Settings"
- **Submenu Label**: "Settings"
- **Capability**: `manage_options` (administrators only)
- **Function**: `web_embed_settings_page()`
- **Description**: Configure security, caching, and default options

## Tab Navigation

Both pages feature consistent tab navigation at the top:

```
┌─────────────────────────────────────────────────┐
│ Web Embed                                       │
│ ┌───────────────┬────────┐                     │
│ │ Shortcode Builder │ Settings │                     │
│ └─────────▲───────┴────────┘                   │
│           └─ Active tab highlighted             │
│                                                 │
│ [Page Content Here]                            │
└─────────────────────────────────────────────────┘
```

### Tab Behavior

- **Builder Tab**: Links to `?page=web-embed`
- **Settings Tab**: Links to `?page=web-embed-settings`
- Active tab is highlighted with `nav-tab-active` class
- Clicking tabs maintains context within the Web Embed section

## User Access Flow

### For Content Editors (edit_posts capability):
1. Click "Web Embed" in sidebar
2. Access Builder page (can create shortcodes)
3. **Cannot** access Settings page (restricted to admins)

### For Administrators (manage_options capability):
1. Click "Web Embed" in sidebar
2. Access Builder page by default
3. Can switch to Settings via submenu or tab
4. Full access to all features

## Icon & Branding

- **Menu Icon**: Dashicons embed icon (`dashicons-embed-generic`)
- **Visual Identity**: Matches WordPress admin color scheme
- **Consistency**: Follows same pattern as Graylog Search and other first-class plugins

## Comparison to Previous Structure

### Old Structure (Settings submenu):
```
Settings
├── General
├── ...
├── Web Embed ← Hidden under Settings
└── Web Embed Builder ← Separate submenu item
```

**Issues:**
- Less discoverable
- Split between two separate menu items
- Buried under Settings menu
- Less prominent for primary feature (Builder)

### New Structure (Top-level menu):
```
Web Embed ← Top-level, prominent
├── Builder ← Primary feature
└── Settings ← Configuration
```

**Benefits:**
✅ More discoverable - visible in main sidebar
✅ Better organization - both features under one menu
✅ Prominent placement - matches importance of feature
✅ Tab navigation - easy switching
✅ Professional appearance - similar to other major plugins

## Implementation Details

### Menu Registration (includes/settings.php)

```php
add_action('admin_menu', 'web_embed_add_admin_menu');
function web_embed_add_admin_menu() {
    // Main menu item - Builder page
    add_menu_page(
        'Web Embed',           // Page title
        'Web Embed',           // Menu title
        'edit_posts',          // Capability
        'web-embed',           // Menu slug
        'web_embed_main_page', // Function
        'dashicons-embed-generic', // Icon
        30                     // Position
    );
    
    // Submenu - Builder (renames first submenu)
    add_submenu_page(
        'web-embed',           // Parent slug
        'Shortcode Builder',   // Page title
        'Builder',             // Menu title
        'edit_posts',          // Capability
        'web-embed',           // Menu slug (same as parent)
        'web_embed_main_page'  // Function
    );
    
    // Submenu - Settings
    add_submenu_page(
        'web-embed',              // Parent slug
        'Web Embed Settings',     // Page title
        'Settings',               // Menu title
        'manage_options',         // Capability
        'web-embed-settings',     // Menu slug
        'web_embed_settings_page' // Function
    );
}
```

### Asset Enqueuing (includes/shortcode-builder.php)

```php
add_action('admin_enqueue_scripts', 'web_embed_enqueue_builder_assets');
function web_embed_enqueue_builder_assets($hook) {
    // Load on our main pages
    if ($hook !== 'toplevel_page_web-embed' && 
        $hook !== 'web-embed_page_web-embed-settings') {
        return;
    }
    
    // Enqueue CSS for both pages
    wp_enqueue_style('web-embed-admin-style', ...);
    
    // Only enqueue builder JS on the builder page
    if ($hook === 'toplevel_page_web-embed') {
        wp_enqueue_script('web-embed-builder-script', ...);
    }
}
```

## Navigation Paths

### User Journey Examples

**Creating a Shortcode:**
1. Admin sidebar → Click "Web Embed"
2. Land on Builder page
3. Fill form, generate preview
4. Copy shortcode
5. Navigate to post/page → Paste

**Configuring Settings:**
1. Admin sidebar → Click "Web Embed"
2. Click "Settings" tab (or submenu)
3. Adjust security/caching options
4. Save settings
5. Click "Builder" tab to return

**Quick Access:**
- Bookmark `admin.php?page=web-embed` for direct Builder access
- Bookmark `admin.php?page=web-embed-settings` for direct Settings access

## Future Considerations

Potential additional menu items:
- [ ] History/Recent Embeds
- [ ] Documentation/Help
- [ ] Import/Export presets
- [ ] Analytics/Usage stats

Current structure easily accommodates additional submenu items while maintaining clean organization.

---

**Last Updated:** October 10, 2025
**Version:** 1.0.0
**Status:** Active ✅

