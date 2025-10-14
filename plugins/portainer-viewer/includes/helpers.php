<?php
// Prevent direct access
if (!defined('WPINC')) {
	die;
}

function pv_admin_can_manage() {
	return current_user_can('manage_options');
}

function pv_get_environments() {
	$envs = get_option('pv_environments', array());
	return is_array($envs) ? $envs : array();
}

function pv_save_environments($environments) {
	if (!is_array($environments)) {
		return false;
	}
	return update_option('pv_environments', array_values($environments));
}

function pv_generate_env_key() {
	return 'env_' . wp_generate_uuid4();
}

function pv_get_env($keyOrId) {
	$envs = pv_get_environments();
	foreach ($envs as $env) {
		if (!empty($env['key']) && $env['key'] === $keyOrId) {
			return $env;
		}
		if ((string)($env['id'] ?? '') === (string)$keyOrId) {
			return $env;
		}
	}
	return null;
}

function pv_replace_env($env) {
	$envs = pv_get_environments();
	$replaced = false;
	foreach ($envs as $i => $e) {
		if (($e['key'] ?? '') === ($env['key'] ?? '')) {
			$envs[$i] = $env;
			$replaced = true;
			break;
		}
	}
	if (!$replaced) {
		$envs[] = $env;
	}
	return pv_save_environments($envs);
}

function pv_delete_env($key) {
	$envs = pv_get_environments();
	$envs = array_values(array_filter($envs, function ($e) use ($key) {
		return ($e['key'] ?? '') !== $key;
	}));
	return pv_save_environments($envs);
}

function pv_sanitize_env($input) {
	$env = array();
	$env['key'] = !empty($input['key']) ? sanitize_text_field($input['key']) : pv_generate_env_key();
	$env['name'] = sanitize_text_field($input['name'] ?? '');
	$env['api_url'] = esc_url_raw($input['api_url'] ?? '');
	$env['api_token'] = sanitize_text_field($input['api_token'] ?? '');
	$env['id'] = intval($input['id'] ?? 0); // Portainer endpointId
	$env['tls_verify'] = !empty($input['tls_verify']) ? 1 : 0;
	return $env;
}

function pv_rate_limit_check($action = 'default', $limit = 60) {
	$key = 'pv_rl_' . $action . '_' . get_current_user_id();
	$count = get_transient($key);
	if ($count === false) {
		set_transient($key, 1, 60);
		return true;
	}
	if ($count >= $limit) {
		return false;
	}
	set_transient($key, $count + 1, 60);
	return true;
}


