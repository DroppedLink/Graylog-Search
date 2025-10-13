# Graylog Search Shortcode Guide

## Basic Usage

Add the search interface to any page or post:

```
[graylog_search]
```

## Shortcode Parameters

### height
Set the height of the results container (default: 600px)

```
[graylog_search height="800px"]
```

### capability  
Set who can use the search (default: 'read' - any logged-in user)

```
[graylog_search capability="manage_options"]
```

**Available capabilities:**
- `read` - Any logged-in user (default)
- `edit_posts` - Contributors and above
- `manage_options` - Administrators only

## Examples

### 1. Basic Search on Any Page
```
[graylog_search]
```

### 2. Taller Results Container
```
[graylog_search height="1000px"]
```

### 3. Admin-Only Search
```
[graylog_search capability="manage_options"]
```

### 4. Large Admin-Only Search
```
[graylog_search height="900px" capability="manage_options"]
```

## How to Add to a Page

1. **Create/Edit a Page:**
   - Go to Pages → Add New (or edit existing)
   
2. **Add the Shortcode:**
   - In the block editor, add a "Shortcode" block
   - Or in classic editor, just type the shortcode
   
3. **Paste the Shortcode:**
   ```
   [graylog_search]
   ```
   
4. **Publish/Update the Page**

5. **View the Page:**
   - The search interface will appear where you placed the shortcode

## Features

### Compact Layout
- All input fields consolidated at the top
- Two rows of inputs for compact presentation
- Search and Clear buttons inline with inputs

### Scrollable Results
- Results appear in a fixed-height container
- Scroll within the results box
- Page doesn't need to scroll
- Default height: 600px (customizable)

### Same Functionality
- All the same search features as admin interface
- FQDN search
- Additional search terms
- Filter out (exclude) terms
- Time range selection
- Result limit selection

## Styling

The shortcode includes its own styling that works on any WordPress theme. The interface has:

- Clean, modern design
- Responsive layout
- Color-coded log levels
- Professional typography
- Smooth animations

## Permissions

By default, any logged-in user can use the search. To restrict:

**Only Administrators:**
```
[graylog_search capability="manage_options"]
```

**Editors and Above:**
```
[graylog_search capability="edit_pages"]
```

## Multiple Shortcodes

You can use multiple shortcodes on different pages:

**Page 1 - General Logs:**
```
[graylog_search height="500px"]
```

**Page 2 - Security Logs:**
```
[graylog_search height="800px" capability="manage_options"]
```

Each instance works independently!

## Tips

1. **Page Width:** For best results, use full-width page templates
2. **Height:** Adjust height based on your content (default 600px)
3. **Mobile:** The interface is fully responsive
4. **Results:** Results scroll within the container, not the whole page

## Troubleshooting

### "You do not have permission"
- User needs to be logged in
- Check the capability parameter matches user role

### "Graylog API is not configured"
- Admin needs to configure API settings
- Go to WordPress Admin → Graylog Search → Settings

### Results not appearing
- Check browser console (F12) for errors
- Verify API settings are correct
- Check WordPress debug log

## Example Page Setup

Create a page called "Log Search" with:

```
<h1>Server Log Search</h1>
<p>Search through server logs below. Enter a hostname, keywords, or time range.</p>

[graylog_search height="700px"]

<h2>Need Help?</h2>
<ul>
<li>FQDN: Enter server hostname (e.g., web-01.example.com)</li>
<li>Search Terms: Add keywords like "error" or "warning"</li>
<li>Filter Out: Exclude terms like "debug" or "info"</li>
<li>Time Range: Select how far back to search</li>
</ul>
```

Result: A complete log search page for your users!

