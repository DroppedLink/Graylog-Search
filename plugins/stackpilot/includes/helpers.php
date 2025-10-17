<?php
if (!defined('WPINC')) { die; }

function sp_admin_can_manage() { return current_user_can('manage_options'); }

function sp_get_environments() {
	$envs = get_option('sp_environments', array());
	return is_array($envs) ? $envs : array();
}

function sp_save_environments($environments) {
	if (!is_array($environments)) { return false; }
	return update_option('sp_environments', array_values($environments));
}

function sp_generate_env_key() { return 'env_' . wp_generate_uuid4(); }

function sp_get_env($keyOrId) {
	foreach (sp_get_environments() as $env) {
		if (!empty($env['key']) && $env['key'] === $keyOrId) return $env;
		if ((string)($env['id'] ?? '') === (string)$keyOrId) return $env;
	}
	return null;
}

function sp_replace_env($env) {
	$envs = sp_get_environments();
	$replaced = false;
	foreach ($envs as $i => $e) {
		if (($e['key'] ?? '') === ($env['key'] ?? '')) { $envs[$i] = $env; $replaced = true; break; }
	}
	if (!$replaced) { $envs[] = $env; }
	return sp_save_environments($envs);
}

function sp_delete_env($key) {
	$envs = array_values(array_filter(sp_get_environments(), function ($e) use ($key) { return ($e['key'] ?? '') !== $key; }));
	return sp_save_environments($envs);
}

function sp_sanitize_env($input) {
	$env = array();
	$env['key'] = !empty($input['key']) ? sanitize_text_field($input['key']) : sp_generate_env_key();
	$env['name'] = sanitize_text_field($input['name'] ?? '');
	$env['api_url'] = esc_url_raw($input['api_url'] ?? '');
	$env['api_token'] = sanitize_text_field($input['api_token'] ?? '');
	$env['id'] = intval($input['id'] ?? 0);
	$env['tls_verify'] = !empty($input['tls_verify']) ? 1 : 0;
	return $env;
}

function sp_rate_limit_check($action = 'default', $limit = 60) {
	$key = 'sp_rl_' . $action . '_' . get_current_user_id();
	$count = get_transient($key);
	if ($count === false) { set_transient($key, 1, 60); return true; }
	if ($count >= $limit) { return false; }
	set_transient($key, $count + 1, 60);
	return true;
}

function sp_is_container_protected($containerName) {
	if (!get_option('sp_protect_enabled', 1)) { return false; }
	$name = ltrim((string) $containerName, '/');
	$patterns = (array) get_option('sp_protect_patterns', array('portainer','traefik','nginx-proxy','caddy'));
	foreach ($patterns as $pattern) {
		$pattern = trim($pattern);
		if ($pattern === '') { continue; }
		if (stripos($name, $pattern) !== false) { return true; }
	}
	return false;
}

// No legacy wrappers


