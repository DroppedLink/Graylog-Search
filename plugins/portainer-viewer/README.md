# Portainer Viewer

View Portainer environments and manage containers (start/stop/restart) with logs from the WordPress admin.

## Features
- Multiple environments (API URL, token, endpoint ID, TLS verify)
- List containers with status and quick actions
- Start/Stop/Restart containers
- View logs (tail N lines)
- Caching and basic rate limiting

## Requirements
- WordPress 5.0+
- PHP 7.4+
- Portainer API access token per environment

## Setup
1. Upload or place the plugin in `wp-content/plugins/portainer-viewer`.
2. Activate the plugin.
3. Go to Admin → Portainer Viewer → Settings.
4. Add environment(s): Name, API URL, API Token, Endpoint ID, TLS verify.
5. Use Admin → Portainer Viewer to view containers.

## Get a Portainer API key (token)
1. Sign in to Portainer with an account that has access to the target environment.
2. Open the user menu (top‑right avatar) → My account → API keys.
3. Click "Create API key", give it a name, then create.
4. Copy the token shown once and store it securely. Paste it into the plugin's API Token field.

Notes:
- If you don't see API keys, an admin may need to enable them in Settings → Security → API keys.
- The header used is `X-API-Key: <token>`.

### Find your Endpoint ID
- In the Portainer UI, select the environment; the URL often contains the numeric endpoint ID (e.g., `endpointId=2`).
- Or via API (replace values accordingly):
  - `GET https://<PORTAINER_URL>/api/endpoints` with header `X-API-Key: <token>` and find the `Id` of your environment.

## Notes
- Headers: `X-API-Key` is used for authentication.
- Cache TTL defaults to 30s; set 0 to disable.
- Logs tail defaults to 200 lines.

## Security
- Admin-only (`manage_options`).
- Nonces for AJAX, sanitized inputs, escaped outputs.

## Uninstall
- Deletes options: `pv_environments`, `pv_cache_ttl`, `pv_logs_tail`.


