<?php
if (!defined('WPINC')) { die; }

// List containers (new)
add_action('wp_ajax_sp_list_containers', 'sp_list_containers');
function sp_list_containers() {
	check_ajax_referer('sp_nonce', 'nonce');
	if (!sp_admin_can_manage()) { wp_send_json_error(array('message' => 'Insufficient permissions')); }
	$envId = sanitize_text_field($_POST['envId'] ?? '');
	$env = sp_get_env($envId);
	if (!$env) { wp_send_json_error(array('message' => 'Invalid environment')); }
	$cacheKey = 'sp_containers_' . ($env['key'] ?? $envId);
	$ttl = intval(get_option('sp_cache_ttl', get_option('pv_cache_ttl', 30)));
	if ($ttl > 0) {
		$cached = get_transient($cacheKey);
		if ($cached !== false) { wp_send_json_success($cached); }
	}
	$endpointId = intval($env['id']);
	$res = sp_api_request($env, '/api/endpoints/' . $endpointId . '/docker/containers/json', 'GET', array('all' => 1));
	if (is_wp_error($res)) { wp_send_json_error(array('message' => $res->get_error_message())); }
	if ($ttl > 0) { set_transient($cacheKey, $res, $ttl); }
	wp_send_json_success($res);
}

// Container quick action
add_action('wp_ajax_sp_container_action', 'sp_container_action');
function sp_container_action() {
	check_ajax_referer('sp_nonce', 'nonce');
	if (!sp_admin_can_manage()) { wp_send_json_error(array('message' => 'Insufficient permissions')); }
	if (!sp_rate_limit_check('container_action', 60)) { wp_send_json_error(array('message' => 'Rate limit exceeded. Try again later.')); }
	$envId = sanitize_text_field($_POST['envId'] ?? '');
	$containerId = sanitize_text_field($_POST['containerId'] ?? '');
	$op = sanitize_text_field($_POST['op'] ?? '');
	$env = sp_get_env($envId);
	if (!$env || !$containerId) { wp_send_json_error(array('message' => 'Invalid request')); }
	$endpointId = intval($env['id']);
	$pathBase = '/api/endpoints/' . $endpointId . '/docker/containers/' . rawurlencode($containerId);
	$validOps = array('start', 'stop', 'restart');
	if (!in_array($op, $validOps, true)) { wp_send_json_error(array('message' => 'Unsupported operation')); }
	// Protection: block stop/restart for protected containers
	$inspect = sp_api_request($env, $pathBase . '/json', 'GET');
	if (!is_wp_error($inspect) && is_array($inspect)) {
		$names = array();
		if (!empty($inspect['Name'])) { $names[] = $inspect['Name']; }
		if (!empty($inspect['Names']) && is_array($inspect['Names'])) { $names = array_merge($names, $inspect['Names']); }
		foreach ($names as $nm) {
			if (sp_is_container_protected($nm) && in_array($op, array('stop','restart'), true)) {
				wp_send_json_error(array('message' => 'This container is protected and cannot be ' . $op . 'ed.'));
			}
		}
	}
	$res = sp_api_request($env, $pathBase . '/' . $op, 'POST');
	if (is_wp_error($res)) { wp_send_json_error(array('message' => $res->get_error_message())); }
	delete_transient('sp_containers_' . ($env['key'] ?? $envId));
	wp_send_json_success(array('ok' => true));
}

// Fetch logs
add_action('wp_ajax_sp_fetch_logs', 'sp_fetch_logs');
function sp_fetch_logs() {
	check_ajax_referer('sp_nonce', 'nonce');
	if (!sp_admin_can_manage()) { wp_send_json_error(array('message' => 'Insufficient permissions')); }
	$envId = sanitize_text_field($_POST['envId'] ?? '');
	$containerId = sanitize_text_field($_POST['containerId'] ?? '');
	$env = sp_get_env($envId);
	if (!$env || !$containerId) { wp_send_json_error(array('message' => 'Invalid request')); }
	$endpointId = intval($env['id']);
	$tail = max(1, intval(get_option('sp_logs_tail', get_option('pv_logs_tail', 200))));
	$query = array('stdout' => 1, 'stderr' => 1, 'tail' => $tail);
	$path = '/api/endpoints/' . $endpointId . '/docker/containers/' . rawurlencode($containerId) . '/logs';
	$res = sp_api_request($env, $path, 'GET', $query);
	if (is_wp_error($res)) { wp_send_json_error(array('message' => $res->get_error_message())); }
	$logs = is_array($res) ? wp_json_encode($res) : (string) $res;
	wp_send_json_success(array('logs' => $logs));
}

// Test connection
add_action('wp_ajax_sp_test_connection', 'sp_test_connection_ajax');
function sp_test_connection_ajax() {
	check_ajax_referer('sp_nonce', 'nonce');
	if (!sp_admin_can_manage()) { wp_send_json_error(array('message' => 'Insufficient permissions')); }
	$envId = sanitize_text_field($_POST['envId'] ?? '');
	$env = sp_get_env($envId);
	if (!$env) { wp_send_json_error(array('message' => 'Invalid environment')); }
	$res = sp_test_connection($env);
	if (is_wp_error($res)) { wp_send_json_error(array('message' => $res->get_error_message())); }
	wp_send_json_success(array('ok' => true));
}

// Fetch endpoints (for settings endpoint selector)
add_action('wp_ajax_sp_fetch_endpoints', 'sp_fetch_endpoints_ajax');
function sp_fetch_endpoints_ajax() {
	check_ajax_referer('sp_nonce', 'nonce');
	if (!sp_admin_can_manage()) { wp_send_json_error(array('message' => 'Insufficient permissions')); }
	$api_url = esc_url_raw($_POST['api_url'] ?? '');
	$api_token = sanitize_text_field($_POST['api_token'] ?? '');
	$tls_verify = !empty($_POST['tls_verify']);
	if (!$api_url || !$api_token) { wp_send_json_error(array('message' => 'API URL and token required')); }
	$temp_env = array('api_url' => $api_url, 'api_token' => $api_token, 'tls_verify' => $tls_verify);
	$res = sp_api_request($temp_env, '/api/endpoints', 'GET');
	if (is_wp_error($res)) { wp_send_json_error(array('message' => $res->get_error_message())); }
	$endpoints = array();
	if (is_array($res)) {
		foreach ($res as $endpoint) {
			$endpoints[] = array(
				'id' => intval($endpoint['Id'] ?? 0),
				'name' => sanitize_text_field($endpoint['Name'] ?? 'Unnamed'),
				'type' => sanitize_text_field($endpoint['Type'] ?? 'Unknown'),
				'url' => sanitize_text_field($endpoint['URL'] ?? '')
			);
		}
	}
	wp_send_json_success($endpoints);
}

// No legacy aliases


