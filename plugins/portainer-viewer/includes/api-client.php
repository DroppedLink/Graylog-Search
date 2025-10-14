<?php
// Prevent direct access
if (!defined('WPINC')) {
	die;
}

function pv_api_request($env, $path, $method = 'GET', $query = array(), $body = null) {
	$url = rtrim($env['api_url'] ?? '', '/') . '/' . ltrim($path, '/');
	if (!empty($query)) {
		$url .= '?' . http_build_query($query);
	}
	$args = array(
		'method' => $method,
		'timeout' => 30,
		'headers' => array(
			'X-API-Key' => $env['api_token'] ?? '',
			'Accept' => 'application/json',
		),
		'sslverify' => !empty($env['tls_verify']),
	);
	if ($body !== null) {
		$args['headers']['Content-Type'] = 'application/json';
		$args['body'] = wp_json_encode($body);
	}
	$response = wp_remote_request($url, $args);
	if (is_wp_error($response)) {
		return $response;
	}
	$code = wp_remote_retrieve_response_code($response);
	$text = wp_remote_retrieve_body($response);
	if ($code < 200 || $code >= 300) {
		return new WP_Error('api_error', 'HTTP ' . $code . ': ' . wp_strip_all_tags(substr($text, 0, 200)));
	}
	$data = json_decode($text, true);
	return (json_last_error() === JSON_ERROR_NONE) ? $data : $text; // logs may be plain text
}

function pv_api_request_with_retry($env, $path, $method = 'GET', $query = array(), $body = null, $max_retries = 3) {
	$attempt = 0;
	while ($attempt < $max_retries) {
		$result = pv_api_request($env, $path, $method, $query, $body);
		if (!is_wp_error($result)) {
			return $result;
		}
		$attempt++;
		if ($attempt < $max_retries) {
			sleep((int) pow(2, $attempt)); // 2s, 4s
		}
	}
	return new WP_Error('max_retries', 'Failed after ' . $max_retries . ' attempts');
}

function pv_test_connection($env) {
	$endpointId = intval($env['id'] ?? 0);
	if ($endpointId <= 0) {
		return new WP_Error('invalid_endpoint', 'Endpoint ID is required');
	}
	$res = pv_api_request($env, '/api/endpoints/' . $endpointId, 'GET');
	if (is_wp_error($res)) {
		return $res;
	}
	return is_array($res) ? $res : array('ok' => true);
}


