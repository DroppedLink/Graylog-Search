# StackPilot (for Portainer)

Manage Portainer environments and containers from the WordPress admin: list containers, quick actions (start/stop/restart), and view logs with multi-environment support.

## Features
- Multiple environments (API URL, token, endpoint ID, TLS verify)
- Endpoint discovery: load endpoints via API and pick from a list
- List containers with status and quick actions
- Start/Stop/Restart containers
- View logs (tail N lines)
- Caching and basic rate limiting

## Setup
1. Upload to `wp-content/plugins/stackpilot` and activate.
2. Go to Admin → StackPilot → Settings.
3. Enter API URL and token, click "Load Endpoints", pick an endpoint, and save.

## Get a Portainer API key (token)
1. Sign in to Portainer → user menu (top-right) → My account → API keys.
2. Create API key and copy token (shown once). Paste into Settings.

### Find your Endpoint ID
- Use "Load Endpoints" in Settings to discover and select.
- Or in Portainer, the URL often includes `endpointId=NUMBER`.

## Notes
- Header used: `X-API-Key: <token>`.
- Cache TTL defaults to 30s; set 0 to disable.
- Logs tail defaults to 200 lines.

## Security
- Admin-only (`manage_options`), nonces for AJAX, sanitized inputs, escaped outputs.

## Uninstall
- Deletes options: `sp_*` and legacy `pv_*`.


