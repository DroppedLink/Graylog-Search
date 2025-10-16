<?php
if (!defined('WPINC')) { die; }

function sp_dashboard_page() {
	if (!sp_admin_can_manage()) { wp_die('Insufficient permissions'); }
	$envs = sp_get_environments();
	?>
	<div class="wrap">
		<h1>StackPilot</h1>
		<p>Select an environment to view containers and perform quick actions.</p>

		<label for="sp-env">Environment:</label>
		<select id="sp-env">
			<option value="">Select environment...</option>
			<?php foreach ($envs as $env): ?>
				<option value="<?php echo esc_attr($env['key'] ?? ''); ?>"><?php echo esc_html(($env['name'] ?? '') . ' (ID ' . ($env['id'] ?? '') . ')'); ?></option>
			<?php endforeach; ?>
		</select>
		<button class="button" id="sp-refresh">Refresh</button>
		<label style="margin-left:12px;">
			<input type="checkbox" id="sp-autorefresh"> Auto-refresh logs
		</label>

		<table class="widefat fixed striped" id="sp-table" style="margin-top:12px;">
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

		<div id="sp-logs-modal" style="display:none; position:fixed; left:5%; top:5%; width:90%; height:80%; background:#fff; border:1px solid #ddd; box-shadow:0 2px 10px rgba(0,0,0,.2); z-index:10000;">
			<div style="padding:8px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
				<strong>Container Logs</strong>
				<div>
					<button class="button" id="sp-logs-refresh">Refresh</button>
					<button class="button" id="sp-logs-close">Close</button>
				</div>
			</div>
			<pre id="sp-logs-pre" style="margin:0; padding:12px; height:calc(100% - 50px); overflow:auto; background:#111; color:#ddd;" role="document" aria-live="polite"></pre>
		</div>
	</div>

	<script type="text/javascript">
	(function($){
		var hasDashboard = $('#sp-table').length > 0;
		var autoRefresh = false;
		var autoRefreshTimer = null;

		function isProtectedName(name){
			var patterns = (sp && sp.protect && sp.protect.patterns) ? sp.protect.patterns : ['portainer','traefik','nginx-proxy','caddy'];
			name = (name||'').replace(/^\//,'').toLowerCase();
			for (var i=0;i<patterns.length;i++){ if (name.indexOf(String(patterns[i]).toLowerCase()) !== -1) return true; }
			return false;
		}

		function renderTable(data){
			var tbody = $('#sp-table tbody');
			tbody.empty();
			(data || []).forEach(function(c){
				var name = (c.Names && c.Names.length) ? c.Names[0].replace(/^\//,'') : (c.Names||'');
				var created = c.Created ? new Date(c.Created * 1000).toLocaleString() : '';
				var row = $('<tr>');
				row.append($('<td>').text(name));
				row.append($('<td>').text((c.Image||'')));
				row.append($('<td>').text((c.Status||'')));
				row.append($('<td>').text((c.State||'')));
				row.append($('<td>').text(created));
				var actions = $('<td>');
				['start','stop','restart'].forEach(function(act){
					var btn = $('<button class="button button-small sp-action">').text(act).attr('data-act', act).attr('data-id', c.Id);
					if (isProtectedName(name) && (act === 'stop' || act === 'restart')) { btn.prop('disabled', true).attr('title','Protected container'); }
					actions.append(btn).append(' ');
				});
				var logsBtn = $('<button class="button button-small sp-logs">Logs</button>').attr('data-id', c.Id);
				actions.append(logsBtn);
				row.append(actions);
				tbody.append(row);
			});
		}

		function loadContainers(){
			if (!hasDashboard) return;
			var envEl = $('#sp-env');
			if (!envEl.length || !envEl.val()) {
				$('#sp-table tbody').html('<tr><td colspan="6">No environment selected. Add one under Settings.</td></tr>');
				return;
			}
			$('#sp-table tbody').html('<tr><td colspan="6">Loading...</td></tr>');
			$.post(ajaxurl, { action: 'sp_list_containers', nonce: '<?php echo esc_js(wp_create_nonce('sp_nonce')); ?>', envId: envEl.val() }, function(r){
				if(!r.success){ alert(r.data && r.data.message ? r.data.message : 'Error'); return; }
				renderTable(r.data);
			});
		}

		$(document).on('click', '#sp-refresh', loadContainers);
		$(document).on('change', '#sp-env', loadContainers);
		$(document).on('click','.sp-action',function(){
			var action = $(this).data('act'); var id = $(this).data('id');
			$.post(ajaxurl, { action: 'sp_container_action', nonce: '<?php echo esc_js(wp_create_nonce('sp_nonce')); ?>', envId: $('#sp-env').val(), containerId:id, op:action }, function(r){
				if(!r.success){ alert(r.data && r.data.message ? r.data.message : 'Error'); return; }
				loadContainers();
			});
		});
		$(document).on('click','.sp-logs',function(){
			var containerId = $(this).data('id');
			$('#sp-logs-pre').text('Loading logs...');
			$('#sp-logs-modal').attr('role','dialog').attr('aria-modal','true').data('containerId', containerId).show();
			$.post(ajaxurl, { action:'sp_fetch_logs', nonce: '<?php echo esc_js(wp_create_nonce('sp_nonce')); ?>', envId: $('#sp-env').val(), containerId: containerId }, function(r){
				if(!r.success){ $('#sp-logs-pre').text('Failed: ' + (r.data && r.data.message ? r.data.message : 'Error')); return; }
				$('#sp-logs-pre').text(r.data.logs || '');
			});
		});
		$('#sp-logs-close').on('click', function(){
			$('#sp-logs-modal').hide().removeData('containerId');
			if (autoRefreshTimer) { clearInterval(autoRefreshTimer); autoRefreshTimer = null; }
		});
		$('#sp-logs-refresh').on('click', function(){
			var id = $('#sp-logs-modal').data('containerId');
			if(!id) return;
			$('#sp-logs-pre').text('Loading logs...');
			$.post(ajaxurl, { action:'sp_fetch_logs', nonce: '<?php echo esc_js(wp_create_nonce('sp_nonce')); ?>', envId: $('#sp-env').val(), containerId: id }, function(r){
				if(!r.success){ $('#sp-logs-pre').text('Failed: ' + (r.data && r.data.message ? r.data.message : 'Error')); return; }
				$('#sp-logs-pre').text(r.data.logs || '');
			});
		});

		$('#sp-autorefresh').on('change', function(){
			autoRefresh = $(this).is(':checked');
			if (autoRefreshTimer) { clearInterval(autoRefreshTimer); autoRefreshTimer = null; }
			if (autoRefresh) {
				autoRefreshTimer = setInterval(function(){ $('#sp-logs-refresh').trigger('click'); }, 5000);
			}
		});

		if (hasDashboard) { loadContainers(); }
	})(jQuery);
	</script>
	<?php
}


