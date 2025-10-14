<?php
// Prevent direct access
if (!defined('WPINC')) {
	die;
}

function pv_dashboard_page() {
	if (!pv_admin_can_manage()) {
		wp_die('Insufficient permissions');
	}
	$envs = pv_get_environments();
	?>
	<div class="wrap">
		<h1>Portainer Viewer</h1>
		<p>Select an environment to view containers and perform quick actions.</p>

		<label for="pv-env">Environment:</label>
		<select id="pv-env">
			<option value="">Select environment...</option>
			<?php foreach ($envs as $env): ?>
				<option value="<?php echo esc_attr($env['key'] ?? ''); ?>"><?php echo esc_html(($env['name'] ?? '') . ' (ID ' . ($env['id'] ?? '') . ')'); ?></option>
			<?php endforeach; ?>
		</select>
		<button class="button" id="pv-refresh">Refresh</button>

		<table class="widefat fixed striped" id="pv-table" style="margin-top:12px;">
			<thead>
				<tr>
					<th>Name</th>
					<th>Image</th>
					<th>Status</th>
					<th>State</th>
					<th>Created</th>
					<th style="width:220px;">Actions</th>
				</tr>
			</thead>
			<tbody>
				<tr><td colspan="6">No environment selected. Add one under Settings.</td></tr>
			</tbody>
		</table>

		<div id="pv-logs-modal" style="display:none; position:fixed; left:5%; top:5%; width:90%; height:80%; background:#fff; border:1px solid #ddd; box-shadow:0 2px 10px rgba(0,0,0,.2); z-index:10000;">
			<div style="padding:8px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
				<strong>Container Logs</strong>
				<div>
					<button class="button" id="pv-logs-refresh">Refresh</button>
					<button class="button" id="pv-logs-close">Close</button>
				</div>
			</div>
			<pre id="pv-logs-pre" style="margin:0; padding:12px; height:calc(100% - 50px); overflow:auto; background:#111; color:#ddd;"></pre>
		</div>
	</div>

	<?php
}


