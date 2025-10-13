# Graylog 6.1 API Compatibility Update

## Version 1.3.0

This update makes the plugin compatible with Graylog 6.1+ API changes.

## What Changed in Graylog 6.1 API

### 1. Endpoint Change
**Old (deprecated):**
```
GET /api/search/universal/relative
```

**New (Graylog 6.1+):**
```
GET /api/search/messages
```

### 2. Required Header
Graylog 6.1+ requires the `X-Requested-By` header to prevent CSRF attacks:
```http
X-Requested-By: wordpress-plugin
```

### 3. Response Format Change

**Old Format:**
```json
{
  "messages": [
    {
      "message": {
        "timestamp": "2025-10-10T19:17:11.265Z",
        "source": "10.0.0.254",
        "message": "Log message text",
        "level": 6
      },
      "timestamp": "2025-10-10T19:17:11.265Z"
    }
  ]
}
```

**New Format (Graylog 6.1+):**
```json
{
  "schema": [
    {
      "column_type": "field",
      "type": "date",
      "field": "timestamp",
      "name": "field: timestamp"
    },
    {
      "column_type": "field",
      "type": "string",
      "field": "source",
      "name": "field: source"
    },
    {
      "column_type": "field",
      "type": "string",
      "field": "message",
      "name": "field: message"
    }
  ],
  "datarows": [
    [
      "2025-10-10T19:17:11.265Z",
      "10.0.0.254",
      "Log message text"
    ]
  ],
  "metadata": {
    "effective_timerange": {
      "from": "2025-10-09T19:17:33.611Z",
      "to": "2025-10-10T19:17:33.611Z",
      "type": "absolute"
    }
  }
}
```

## Plugin Changes

### File: `includes/ajax-handler.php`

**Updated `graylog_api_search()` function:**
- Changed endpoint from `/search/universal/relative` to `/search/messages`
- Added `X-Requested-By: wordpress-plugin` header
- Changed query parameters:
  - Old: `query`, `range`, `limit`, `sort`
  - New: `query`, `fields`, `size`
- Added better error logging

**New `graylog_convert_api_response()` function:**
- Converts Graylog 6.1+ response format (schema + datarows) to plugin's expected format
- Maps fields dynamically using schema
- Maintains backward compatibility

## Authentication

The authentication format remains unchanged:
```http
Authorization: Basic base64_encode(token + ':token')
```

## Testing Results

Tested with Graylog 6.1 and API token:
- ✅ `/api/search/messages` endpoint: **Working**
- ✅ Response parsing: **Working**
- ✅ All plugin features: **Working**

## Migration Notes

**For Users:**
1. No configuration changes needed
2. API URL and Token remain the same
3. Plugin automatically handles new API format

**For Developers:**
- The conversion function handles both old and new formats
- Backward compatibility maintained
- Extensive error logging added for debugging

## Backup

Pre-update backup created:
```
backups/graylog-search-pre-api-update-20251010-151746.zip
```

## Version History

- **1.3.0** - Graylog 6.1 API compatibility
- **1.2.0** - Timezone conversion feature
- **1.1.1** - Bug fixes
- **1.0.0** - Initial release

## Support

If you experience issues after updating:
1. Check WordPress debug log for error messages
2. Verify API token is valid in Graylog
3. Ensure Graylog user has search permissions
4. Check that Graylog is version 6.1 or higher

## Technical References

- Graylog 6.1 API Documentation: https://go2docs.graylog.org/6-1/
- Search Messages API: `/api/search/messages`
- Required Headers: `X-Requested-By` for CSRF protection

