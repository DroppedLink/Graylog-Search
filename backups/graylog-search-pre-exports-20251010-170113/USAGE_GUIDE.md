# Graylog Search Plugin - Usage Guide

## Quick Start

### Step 1: Activate the Plugin

1. Go to WordPress Admin → **Plugins**
2. Find **Graylog Search**
3. Click **Activate**

### Step 2: Configure API Settings

1. Go to **Graylog Search → Settings** in the WordPress admin menu
2. Enter your **Graylog API URL**:
   - Example: `https://graylog.example.com:9000/api`
   - Or: `http://localhost:9000/api` for local development
3. Enter your **API Token**:
   - Generate in Graylog: System → Users → [Your User] → Edit Tokens → Create Token
   - Copy the entire token string
4. Click **Save Settings**

### Step 3: Search Logs

1. Go to **Graylog Search** in the WordPress admin menu
2. Fill in your search criteria (explained below)
3. Click **Search Logs**

## Search Fields Explained

### FQDN (Hostname)
- Search for logs from a specific server or hostname
- Example: `server01.example.com` or `web-prod-01`
- Searches the `source` field in Graylog

### Additional Search Terms
- Add keywords to search within log messages
- Space-separated: `error exception failed`
- Searches across all message content
- Case-sensitive (depends on Graylog configuration)

### Filter Out (Exclude)
- Exclude messages containing certain terms
- Space-separated: `debug info`
- Useful for reducing noise
- Uses `NOT` operator in Graylog query

### Time Range
- **Last Hour**: Searches last 60 minutes
- **Last Day**: Searches last 24 hours (default)
- **Last Week**: Searches last 7 days

### Result Limit
- Choose how many results to return
- Options: 50, 100, 250, 500
- Higher limits may take longer to load

## Example Searches

### Find all errors from a specific server
- **FQDN**: `web-server-01.example.com`
- **Additional Search Terms**: `error`
- **Time Range**: Last Day

### Find database connection issues (excluding debug messages)
- **Additional Search Terms**: `database connection`
- **Filter Out**: `debug info`
- **Time Range**: Last Hour

### Find all logs from a server except routine checks
- **FQDN**: `app-server-02.example.com`
- **Filter Out**: `health_check heartbeat`
- **Time Range**: Last Week

### Search for authentication failures
- **Additional Search Terms**: `authentication failed login`
- **Time Range**: Last Day

## Understanding Results

The results table shows:

### Timestamp
- When the log entry was created
- Displayed in your local timezone
- Format: MM/DD/YYYY, HH:MM:SS

### Source
- The hostname/server that generated the log
- Usually the FQDN of the machine

### Level
- Log severity level
- Color-coded for easy identification:
  - **Red**: ERROR/ERR
  - **Yellow**: WARNING/WARN
  - **Blue**: INFO
  - **Purple**: DEBUG
  - **Cyan**: NOTICE

### Message
- The actual log message content
- Scrollable if long
- Monospace font for readability

## Tips & Best Practices

### 1. Start Broad, Then Narrow
- Start with fewer search terms
- Add more filters if you get too many results

### 2. Use Time Ranges Wisely
- Recent issues? Use "Last Hour"
- Trend analysis? Use "Last Week"
- Longer ranges return more data and take more time

### 3. Combine FQDN with Keywords
- Searching one server with specific terms is fastest
- Example: FQDN + "error" for that server's errors

### 4. Filter Out Noise
- Use "Filter Out" to exclude routine logs
- Common exclusions: `debug`, `info`, `heartbeat`, `health_check`

### 5. Result Limits
- Start with 100 results (default)
- Increase if you need more data
- Large limits may slow down the search

## Troubleshooting

### "Configuration Required" Message
- Go to Settings and configure API URL and Token
- Make sure URL doesn't end with a slash
- Verify token is valid in Graylog

### "No results found"
- Check your time range (maybe nothing in that period)
- Try broader search terms
- Verify the FQDN exists in Graylog

### "Graylog API returned status code: 401"
- Your API token is invalid or expired
- Generate a new token in Graylog
- Update it in Settings

### "Graylog API returned status code: 404"
- Your API URL might be incorrect
- Check the URL format: should end with `/api`
- Don't include `/search` or other endpoints

### "Network error"
- Graylog server might be down
- Check if you can access Graylog web interface
- Verify firewall rules allow API access
- Check WordPress server can reach Graylog

### Search is slow
- Reduce the time range
- Reduce the result limit
- Add more specific search terms
- Use FQDN to limit to one server

## API Token Permissions

Your Graylog API token needs:
- Read access to search
- Access to the streams you want to search
- If searches fail, check token permissions in Graylog

## Security Notes

- Only WordPress administrators can use this plugin (by default)
- API credentials are stored securely in WordPress database
- All searches are logged in WordPress (if logging enabled)
- API token is never exposed to the browser

## Advanced: Graylog Query Syntax

The plugin builds queries like this:
```
source:"hostname" AND error AND NOT debug
```

Understanding this helps you craft better searches:
- Terms are AND'd together
- Filter Out uses NOT
- FQDN searches the `source` field
- Empty fields are omitted from query

## Need Help?

Contact your WordPress or Graylog administrator if:
- You need access to different log streams
- You need a new API token
- You have permission issues
- You need help interpreting logs

