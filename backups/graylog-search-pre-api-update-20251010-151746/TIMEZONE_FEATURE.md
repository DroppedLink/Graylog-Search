# Timezone Conversion Feature

## Overview
Convert log timestamps and embedded timestamps within message text to your preferred timezone. Perfect for distributed teams or reviewing logs from different regions.

## Supported Timezones
- **US Timezones**: Eastern, Central, Mountain, Arizona, Pacific, Alaska, Hawaii
- **UTC/GMT**: Universal Time (Zulu)
- **India**: IST

## Features

### 1. Timezone Selector
Located in the results toolbar (top-right), next to "Resolve All IPs" button.
- Select your preferred timezone from dropdown
- Preference is saved per WordPress user
- Automatically applied to all future searches

### 2. Toggle Button
Switch between original (UTC) and converted timestamps.
- **"Show Original"** (default): Display times in selected timezone
- **"Show Converted"**: Display times in UTC/original format
- Instant toggle - no page refresh needed

### 3. Main Timestamp Conversion
The timestamp column (Info column) automatically converts to your selected timezone:
- Shows converted time with 🌐 indicator
- Hover to see original UTC time
- Subtle blue background indicates conversion

### 4. Embedded Timestamp Conversion
Automatically detects and converts timestamps WITHIN log messages. Supports 10 common formats:

1. **ISO8601**: `2025-10-10T14:30:00Z`
2. **ISO8601 with TZ**: `2025-10-10T14:30:00-05:00`
3. **RFC2822**: `Wed, 10 Oct 2025 14:30:00 GMT`
4. **Unix Timestamp**: `1728565800`
5. **Syslog**: `Oct 10 14:30:00`
6. **Apache/Nginx**: `10/Oct/2025:14:30:00 +0000`
7. **Windows Event**: `10/10/2025 2:30:00 PM`
8. **MySQL**: `2025-10-10 14:30:00`
9. **Custom UTC**: `2025-10-10 14:30:00 UTC`
10. **Custom TZ**: `2025-10-10 14:30:00 EST`

Converted timestamps:
- Highlighted with light blue background
- Shows 🌐 icon
- Hover to see original format

### 5. Search Time Range Adjustment
When you search for "last hour" in EST, the plugin searches for the last EST hour (not UTC hour).
- Automatically adjusts time range based on selected timezone
- Ensures you see logs from YOUR local time perspective
- No manual calculations needed

## Usage Example

### Scenario: User in Eastern Time (EST)

**1. Select Timezone**
```
Results toolbar → Dropdown → "Eastern Time (EST/EDT)"
Notification: "Timezone updated to EST/EDT"
```

**2. Search Results**
```
Before (UTC):
10/10 18:30 PM | firewall | Connection from 10.0.0.21 at 2025-10-10 18:30:00 UTC

After (EST):
10/10 2:30 PM 🌐 | firewall | Connection from 10.0.0.21 at 2025-10-10 14:30:00 🌐
(hover shows: Original (UTC): 10/10 6:30 PM UTC)
```

**3. Toggle to Original**
```
Click "Show Original" button → Timestamps revert to UTC
Button text changes to "Show Converted"
```

**4. Search "Last Hour"**
```
With EST selected at 2:30 PM EST:
- Searches 1:30 PM - 2:30 PM EST
- NOT 6:30 PM - 7:30 PM UTC
- Gets logs from YOUR last hour
```

## Visual Indicators

### Converted Timestamp
```css
Light blue background
🌐 globe icon
Tooltip with original time
```

### Original Timestamp
```css
No background
No icon
Normal display
```

### Toggle Button States
```
Inactive: "Show Original" (gray button)
Active: "Show Converted" (blue button)
```

## Technical Details

### Backend
- Timezone preference saved to WordPress user meta
- AJAX handlers for save/load operations
- No external libraries required

### Frontend
- Uses JavaScript `Intl.DateTimeFormat` API (built-in)
- Client-side conversion (fast, no server load)
- Regex patterns for timestamp detection
- Results cached for instant toggle

### Performance
- Zero impact on search performance
- Conversions happen client-side after results load
- Toggle is instant (no re-fetch needed)
- User preference persists across sessions

## Browser Compatibility
Works in all modern browsers supporting:
- JavaScript Intl API (2015+)
- All major browsers (Chrome, Firefox, Safari, Edge)

## Tips

1. **Set timezone once**: Your preference is saved automatically
2. **Hover for details**: See original time on any converted timestamp
3. **Toggle anytime**: Switch between original and converted without refreshing
4. **Embedded timestamps**: Watch for 🌐 icon in message text - those are converted too!
5. **Time range searches**: Remember your search times are in YOUR timezone

## Troubleshooting

**Timestamps not converting?**
- Check timezone selector shows your timezone (not UTC)
- Ensure "Show Original" button is NOT active (blue)
- Hard refresh browser (Cmd+Shift+R / Ctrl+Shift+R)

**Embedded timestamps not detected?**
- Format must match one of the 10 supported patterns
- Very custom formats may not be recognized
- Original timestamps still visible, just not converted

**Wrong time showing?**
- Verify correct timezone selected
- Check if your browser timezone is set correctly
- DST changes may affect display (EST vs EDT)

## Version
Added in version 1.2.0

