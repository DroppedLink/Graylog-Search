<?php
// Prevent direct access
if (!defined('WPINC')) {
	die;
}

add_action('admin_menu', 'pv_add_admin_menu');
function pv_add_admin_menu() {
	add_menu_page(
		'Portainer Viewer',
		'Portainer Viewer',
		'manage_options',
		'portainer-viewer',
		'pv_dashboard_page',
		'dashicons-visibility',
		30
	);

	add_submenu_page(
		'portainer-viewer',
		'Settings',
		'Settings',
		'manage_options',
		'portainer-viewer-settings',
		'pv_settings_page'
	);
}

function pv_settings_page() {
	if (!pv_admin_can_manage()) {
		wp_die('Insufficient permissions');
	}

	$edit_key = isset($_GET['edit']) ? sanitize_text_field($_GET['edit']) : '';
	$edit_env = $edit_key ? pv_get_env($edit_key) : null;

	// Handle add/update environment
	if (isset($_POST['pv_save_env'])) {
		check_admin_referer('pv_settings_nonce');
		$env = pv_sanitize_env($_POST);
		pv_replace_env($env);
		echo '<div class="notice notice-success"><p>Environment saved.</p></div>';
	}

	// Handle delete environment
	if (isset($_POST['pv_delete_env']) && !empty($_POST['env_key'])) {
		check_admin_referer('pv_settings_nonce');
		pv_delete_env(sanitize_text_field($_POST['env_key']));
		echo '<div class="notice notice-success"><p>Environment deleted.</p></div>';
	}

	$envs = pv_get_environments();
	?>
	<div class="wrap">
		<h1>Portainer Viewer Settings</h1>

		<div class="notice notice-info is-dismissible">
			<p><strong>How to get a Portainer API key</strong></p>
			<ol>
				<li>Sign in to Portainer with an account that can access the environment.</li>
				<li>Open the user menu (top-right avatar) → My account → API keys.</li>
				<li>Click Create API key, name it, then copy the token shown once and paste it below.</li>
			</ol>
			<p><strong>Find your Endpoint ID</strong></p>
			<ul>
				<li>Enter your API URL and token above, then click "Load Endpoints" to see available environments.</li>
				<li>Alternatively, in Portainer UI the URL often contains <code>endpointId=NUMBER</code>.</li>
			</ul>
		</div>

		<h2>Add / Edit Environment</h2>
		<form method="post">
			<?php wp_nonce_field('pv_settings_nonce'); ?>
			<input type="hidden" name="key" value="<?php echo esc_attr($edit_env['key'] ?? ''); ?>">
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="pv_env_name">Name</label></th>
					<td><input name="name" id="pv_env_name" type="text" class="regular-text" value="<?php echo esc_attr($edit_env['name'] ?? ''); ?>" required></td>
				</tr>
				<tr>
					<th scope="row"><label for="pv_env_api_url">API URL</label></th>
					<td><input name="api_url" id="pv_env_api_url" type="url" class="regular-text" placeholder="https://portainer.example.com" value="<?php echo esc_attr($edit_env['api_url'] ?? ''); ?>" required></td>
				</tr>
				<tr>
					<th scope="row"><label for="pv_env_api_token">API Token</label></th>
					<td><input name="api_token" id="pv_env_api_token" type="password" class="regular-text" value="<?php echo esc_attr($edit_env['api_token'] ?? ''); ?>" required></td>
				</tr>
				<tr>
					<th scope="row"><label for="pv_env_endpoint_id">Endpoint</label></th>
					<td>
						<select name="id" id="pv_env_endpoint_id" class="regular-text" required>
							<option value="">Select endpoint...</option>
							<?php if ($edit_env && !empty($edit_env['id'])): ?>
								<option value="<?php echo esc_attr($edit_env['id']); ?>" selected>ID <?php echo esc_html($edit_env['id']); ?> (current)</option>
							<?php endif; ?>
						</select>
						<button type="button" class="button" id="pv-fetch-endpoints">Load Endpoints</button>
						<p class="description">Enter API URL and token above, then click "Load Endpoints" to see available environments.</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Verify TLS</th>
					<td><label><input name="tls_verify" type="checkbox" value="1" <?php echo !empty($edit_env['tls_verify']) ? 'checked' : ''; ?>> Enable SSL verification</label></td>
				</tr>
			</table>
			<p class="submit">
				<input type="submit" name="pv_save_env" class="button button-primary" value="Save Environment">
			</p>
		</form>

		<hr>
		<h2>Configured Environments</h2>
		<table class="widefat fixed striped">
			<thead>
				<tr>
					<th>Name</th>
					<th>API URL</th>
					<th>Endpoint ID</th>
					<th>TLS</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
			<?php if (empty($envs)) : ?>
				<tr><td colspan="5">No environments configured. Use the form above to add one.</td></tr>
			<?php else: foreach ($envs as $env): ?>
				<tr>
					<td><?php echo esc_html($env['name'] ?? ''); ?></td>
					<td><?php echo esc_html($env['api_url'] ?? ''); ?></td>
					<td><?php echo esc_html((string)($env['id'] ?? '')); ?></td>
					<td><?php echo !empty($env['tls_verify']) ? 'Yes' : 'No'; ?></td>
					<td>
						<form method="post" style="display:inline">
							<?php wp_nonce_field('pv_settings_nonce'); ?>
							<input type="hidden" name="env_key" value="<?php echo esc_attr($env['key'] ?? ''); ?>">
							<input type="submit" name="pv_delete_env" class="button button-small" value="Delete" onclick="return confirm('Delete this environment?');">
						</form>
						<a class="button button-small" href="<?php echo esc_url(admin_url('admin.php?page=portainer-viewer-settings&edit=' . urlencode($env['key'] ?? ''))); ?>">Edit</a>
						<button class="button button-small pv-test-env" data-envkey="<?php echo esc_attr($env['key'] ?? ''); ?>">Test</button>
					</td>
				</tr>
			<?php endforeach; endif; ?>
			</tbody>
		</table>

		<hr>
		<h2>General Settings</h2>
		<form method="post">
			<?php wp_nonce_field('pv_settings_nonce'); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="pv_cache_ttl">Cache TTL (seconds)</label></th>
					<td><input name="pv_cache_ttl" id="pv_cache_ttl" type="number" class="small-text" min="0" value="<?php echo esc_attr((string) get_option('pv_cache_ttl', 30)); ?>"> <span class="description">0 to disable caching.</span></td>
				</tr>
				<tr>
					<th scope="row"><label for="pv_logs_tail">Logs tail (lines)</label></th>
					<td><input name="pv_logs_tail" id="pv_logs_tail" type="number" class="small-text" min="1" value="<?php echo esc_attr((string) get_option('pv_logs_tail', 200)); ?>"></td>
				</tr>
			</table>
			<p class="submit">
				<input type="submit" name="pv_save_general" class="button button-primary" value="Save Settings">
			</p>
		</form>
	</div>
	<script type="text/javascript">
	(function($){
		$(document).on('click', '.pv-test-env', function(){
			var key = $(this).data('envkey');
			$.post(ajaxurl, { action: 'pv_test_connection', nonce: '<?php echo esc_js(wp_create_nonce('pv_nonce')); ?>', envId: key }, function(r){
				if(!r || !r.success){ alert((r && r.data && r.data.message) ? r.data.message : 'Test failed'); return; }
				alert('Connection OK');
			});
		});
		
		$(document).on('click', '#pv-fetch-endpoints', function(){
			var apiUrl = $('#pv_env_api_url').val();
			var apiToken = $('#pv_env_api_token').val();
			var tlsVerify = $('#pv_env_tls_verify').is(':checked');
			
			if (!apiUrl || !apiToken) {
				alert('Please enter API URL and token first');
				return;
			}
			
			var btn = $(this);
			btn.prop('disabled', true).text('Loading...');
			
			$.post(ajaxurl, {
				action: 'pv_fetch_endpoints',
				nonce: '<?php echo esc_js(wp_create_nonce('pv_nonce')); ?>',
				api_url: apiUrl,
				api_token: apiToken,
				tls_verify: tlsVerify ? 1 : 0
			}, function(r){
				btn.prop('disabled', false).text('Load Endpoints');
				
				if (!r || !r.success) {
					alert((r && r.data && r.data.message) ? r.data.message : 'Failed to fetch endpoints');
					return;
				}
				
				var select = $('#pv_env_endpoint_id');
				var currentVal = select.val();
				select.empty().append('<option value="">Select endpoint...</option>');
				
				if (r.data && r.data.length > 0) {
					r.data.forEach(function(endpoint){
						var option = $('<option>').val(endpoint.id).text(endpoint.name + ' (ID ' + endpoint.id + ', ' + endpoint.type + ')');
						if (endpoint.id == currentVal) {
							option.prop('selected', true);
						}
						select.append(option);
					});
				} else {
					select.append('<option value="" disabled>No endpoints found</option>');
				}
			});
		});
	})(jQuery);
	</script>
	<?php
	// Handle general settings save post-output to avoid duplicate notices order
	if (isset($_POST['pv_save_general'])) {
		check_admin_referer('pv_settings_nonce');
		update_option('pv_cache_ttl', max(0, intval($_POST['pv_cache_ttl'] ?? 30)));
		update_option('pv_logs_tail', max(1, intval($_POST['pv_logs_tail'] ?? 200)));
		echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
	}
}


