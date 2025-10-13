# Web Embed Builder - Interface Preview

## Admin Page Location
**Settings → Web Embed Builder**

## Interface Layout

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│ Web Embed Shortcode Builder                                                     │
│ Create and test your embed shortcodes before adding them to your content.       │
├──────────────────────────────────┬──────────────────────────────────────────────┤
│                                  │                                              │
│  SHORTCODE PARAMETERS            │  GENERATED SHORTCODE                         │
│  ═══════════════════════         │  ═══════════════════                         │
│                                  │  ┌────────────────────────────────────────┐ │
│  URL * [https://example.com   ]  │  │[web_embed url="https://example.com"   │ │
│  The URL you want to embed       │  │ width="100%" height="600px"           │ │
│                                  │  │ responsive="true"]                    │ │
│  Width  [100%                 ]  │  └────────────────────────────────────────┘ │
│  Width (e.g., 100%, 800px)       │  [Copy Shortcode]                            │
│                                  │  ✓ Shortcode copied to clipboard!            │
│  Height [600px                ]  │                                              │
│  Height (e.g., 600px, 80vh)      │  ─────────────────────────────────────────  │
│                                  │                                              │
│  Responsive Mode [Enabled    ▼]  │  LIVE PREVIEW                                │
│  Makes embed responsive          │  ═══════════════                             │
│                                  │  ┌────────────────────────────────────────┐ │
│  Border [2px solid #ccc       ]  │  │                                        │ │
│  CSS border                      │  │  ┌──────────────────────────────────┐ │ │
│                                  │  │  │                                  │ │ │
│  Border Radius [10px          ]  │  │  │  [EMBEDDED CONTENT PREVIEW]     │ │ │
│  CSS border radius               │  │  │                                  │ │ │
│                                  │  │  │  Your URL content appears here   │ │ │
│  Custom CSS Classes [         ]  │  │  │                                  │ │ │
│  Space-separated class names     │  │  └──────────────────────────────────┘ │ │
│                                  │  │                                        │ │
│  Title [Embedded Content      ]  │  └────────────────────────────────────────┘ │
│  Descriptive title               │                                              │
│                                  │  ─────────────────────────────────────────  │
│  Loading Strategy [Lazy       ▼] │                                              │
│  When to load content            │  💡 QUICK TIPS                               │
│                                  │  ═══════════════                             │
│  Fallback Message                │  • Required: Only URL field is required      │
│  ┌────────────────────────────┐  │  • Responsive: Enable for mobile-friendly   │
│  │                            │  │  • Border: Use CSS syntax like "2px solid"  │
│  └────────────────────────────┘  │  • Testing: Preview shows exact appearance  │
│  Message to show if fails        │  • Whitelist: Check settings if URL blocked │
│                                  │                                              │
│  ───────────────────────────────  │                                              │
│  [Generate Preview & Shortcode]  │                                              │
│  [Clear Form]                    │                                              │
│                                  │                                              │
└──────────────────────────────────┴──────────────────────────────────────────────┘
```

## Key Features Visible

1. **Left Panel - Form**
   - All shortcode parameters as fields
   - Inline help text for guidance
   - Required field indicator (*)
   - Clear action buttons

2. **Right Panel - Output**
   - Generated shortcode display
   - One-click copy button
   - Success/error feedback
   - Live preview box
   - Quick tips section

## User Flow

```
1. User enters URL
   ↓
2. Adjusts optional parameters (width, height, style, etc.)
   ↓
3. Clicks "Generate Preview & Shortcode"
   ↓
4. System validates URL (security checks)
   ↓
5. Generates and displays:
   - Formatted shortcode
   - Live preview of embed
   ↓
6. User clicks "Copy Shortcode"
   ↓
7. Confirmation: "✓ Shortcode copied to clipboard!"
   ↓
8. User pastes into WordPress page/post
```

## Color Scheme

- **Form backgrounds**: White (#fff)
- **Borders**: Light gray (#ccd0d4)
- **Preview area**: Light gray background (#fafafa) with dashed border
- **Success messages**: Green (#d4edda)
- **Error messages**: Red (#f8d7da)
- **Primary buttons**: WordPress blue
- **Code display**: Light gray background (#f5f5f5)

## Responsive Behavior

**Desktop (>1280px):**
- Two-column layout
- Form on left, output on right
- Side-by-side preview

**Tablet/Mobile (<1280px):**
- Single column layout
- Form on top
- Output sections below
- Full-width elements

## Interactive Elements

1. **Generate Button**
   - Changes to "Generating..." during AJAX
   - Disabled during processing
   - Re-enables after completion

2. **Copy Button**
   - Disabled until shortcode generated
   - Shows success message on copy
   - Falls back for older browsers

3. **Clear Button**
   - Confirmation dialog before clearing
   - Resets all fields to defaults
   - Clears preview area

4. **URL Field**
   - Real-time validation
   - Visual feedback (red border if invalid)
   - Alert on invalid format

## Preview Area Features

- **Minimum height**: 400px
- **Dashed border**: Indicates preview zone
- **Placeholder text**: When empty
- **Loading spinner**: During generation
- **Error display**: If preview fails
- **Actual embed**: Shows real content

## Integration Points

- **Link from Settings**: Prominent button on main settings page
- **Security checks**: Uses same whitelist/HTTPS validation
- **Cache system**: Preview uses existing cache
- **Assets**: Loads same CSS/JS as frontend
- **Permissions**: Requires 'edit_posts' capability

## Error States

1. **Empty URL**: Alert before submission
2. **Invalid URL format**: Visual + alert
3. **Blocked by whitelist**: Preview shows error message
4. **HTTPS-only violation**: Error in preview
5. **AJAX failure**: Error message displayed
6. **Copy failure**: Fallback to manual selection

## Success States

1. **Valid preview**: Shows actual embedded content
2. **Shortcode generated**: Formatted in code box
3. **Copy successful**: Green checkmark message
4. **All validations passed**: Clean preview

This visual builder dramatically improves the user experience by eliminating the need to memorize shortcode syntax and allowing users to see exactly what they're creating before using it!

