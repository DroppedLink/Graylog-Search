<?php
require_once('../../../wp-load.php');
if (!current_user_can('manage_options')) { die('Access denied'); }

echo "<h1>Portainer Viewer - API Test</h1>\n";
$envs = get_option('pv_environments', array());
echo '<pre>' . esc_html(print_r($envs, true)) . '</pre>';

if (!empty($envs)) {
	$env = $envs[0];
	$endpointId = intval($env['id'] ?? 0);
	$res = pv_api_request($env, '/api/endpoints/' . $endpointId, 'GET');
	if (is_wp_error($res)) {
		echo '✗ Failed: ' . esc_html($res->get_error_message());
	} else {
		echo '✓ Success';
	}
}


