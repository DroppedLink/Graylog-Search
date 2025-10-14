<?php
// Prevent direct access
if (!defined('WPINC')) {
	die;
}

// List containers
add_action('wp_ajax_pv_list_containers', 'pv_list_containers');
function pv_list_containers() {
	check_ajax_referer('pv_nonce', 'nonce');
	if (!pv_admin_can_manage()) {
		wp_send_json_error(array('message' => 'Insufficient permissions'));
	}
	$envId = sanitize_text_field($_POST['envId'] ?? '');
	$env = pv_get_env($envId);
	if (!$env) {
		wp_send_json_error(array('message' => 'Invalid environment'));
	}
	$cacheKey = 'pv_containers_' . ($env['key'] ?? $envId);
	$ttl = intval(get_option('pv_cache_ttl', 30));
	if ($ttl > 0) {
		$cached = get_transient($cacheKey);
		if ($cached !== false) {
			wp_send_json_success($cached);
		}
	}
	$endpointId = intval($env['id']);
	$res = pv_api_request($env, '/api/endpoints/' . $endpointId . '/docker/containers/json', 'GET', array('all' => 1));
	if (is_wp_error($res)) {
		wp_send_json_error(array('message' => $res->get_error_message()));
	}
	if ($ttl > 0) {
		set_transient($cacheKey, $res, $ttl);
	}
	wp_send_json_success($res);
}

// Container quick action: start/stop/restart
add_action('wp_ajax_pv_container_action', 'pv_container_action');
function pv_container_action() {
	check_ajax_referer('pv_nonce', 'nonce');
	if (!pv_admin_can_manage()) {
		wp_send_json_error(array('message' => 'Insufficient permissions'));
	}
	if (!pv_rate_limit_check('container_action', 60)) {
		wp_send_json_error(array('message' => 'Rate limit exceeded. Try again later.'));
	}
	$envId = sanitize_text_field($_POST['envId'] ?? '');
	$containerId = sanitize_text_field($_POST['containerId'] ?? '');
	$op = sanitize_text_field($_POST['op'] ?? '');
	$env = pv_get_env($envId);
	if (!$env || !$containerId) {
		wp_send_json_error(array('message' => 'Invalid request'));
	}
	$endpointId = intval($env['id']);
	$pathBase = '/api/endpoints/' . $endpointId . '/docker/containers/' . rawurlencode($containerId);
	$validOps = array('start', 'stop', 'restart');
	if (!in_array($op, $validOps, true)) {
		wp_send_json_error(array('message' => 'Unsupported operation'));
	}
	$res = pv_api_request($env, $pathBase . '/' . $op, 'POST');
	if (is_wp_error($res)) {
		wp_send_json_error(array('message' => $res->get_error_message()));
	}
	// Invalidate cache
	delete_transient('pv_containers_' . ($env['key'] ?? $envId));
	wp_send_json_success(array('ok' => true));
}

// Fetch logs
add_action('wp_ajax_pv_fetch_logs', 'pv_fetch_logs');
function pv_fetch_logs() {
	check_ajax_referer('pv_nonce', 'nonce');
	if (!pv_admin_can_manage()) {
		wp_send_json_error(array('message' => 'Insufficient permissions'));
	}
	$envId = sanitize_text_field($_POST['envId'] ?? '');
	$containerId = sanitize_text_field($_POST['containerId'] ?? '');
	$env = pv_get_env($envId);
	if (!$env || !$containerId) {
		wp_send_json_error(array('message' => 'Invalid request'));
	}
	$endpointId = intval($env['id']);
	$tail = max(1, intval(get_option('pv_logs_tail', 200)));
	$query = array('stdout' => 1, 'stderr' => 1, 'tail' => $tail);
	$path = '/api/endpoints/' . $endpointId . '/docker/containers/' . rawurlencode($containerId) . '/logs';
	$res = pv_api_request($env, $path, 'GET', $query);
	if (is_wp_error($res)) {
		wp_send_json_error(array('message' => $res->get_error_message()));
	}
	// Logs may be a string; normalize to string
	$logs = is_array($res) ? wp_json_encode($res) : (string) $res;
	wp_send_json_success(array('logs' => $logs));
}

// Test connection (settings page button)
add_action('wp_ajax_pv_test_connection', 'pv_test_connection_ajax');
function pv_test_connection_ajax() {
	check_ajax_referer('pv_nonce', 'nonce');
	if (!pv_admin_can_manage()) {
		wp_send_json_error(array('message' => 'Insufficient permissions'));
	}
	$envId = sanitize_text_field($_POST['envId'] ?? '');
	$env = pv_get_env($envId);
	if (!$env) {
		wp_send_json_error(array('message' => 'Invalid environment'));
	}
	$res = pv_test_connection($env);
	if (is_wp_error($res)) {
		wp_send_json_error(array('message' => $res->get_error_message()));
	}
	wp_send_json_success(array('ok' => true));
}

// Fetch available endpoints for dropdown
add_action('wp_ajax_pv_fetch_endpoints', 'pv_fetch_endpoints_ajax');
function pv_fetch_endpoints_ajax() {
	check_ajax_referer('pv_nonce', 'nonce');
	if (!pv_admin_can_manage()) {
		wp_send_json_error(array('message' => 'Insufficient permissions'));
	}
	$api_url = esc_url_raw($_POST['api_url'] ?? '');
	$api_token = sanitize_text_field($_POST['api_token'] ?? '');
	$tls_verify = !empty($_POST['tls_verify']);
	
	if (!$api_url || !$api_token) {
		wp_send_json_error(array('message' => 'API URL and token required'));
	}
	
	$temp_env = array(
		'api_url' => $api_url,
		'api_token' => $api_token,
		'tls_verify' => $tls_verify
	);
	
	$res = pv_api_request($temp_env, '/api/endpoints', 'GET');
	if (is_wp_error($res)) {
		wp_send_json_error(array('message' => $res->get_error_message()));
	}
	
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


