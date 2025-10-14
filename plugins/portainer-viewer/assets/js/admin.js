jQuery(function($){
	var hasDashboard = $('#pv-table').length > 0;

	function renderTable(data){
		var tbody = $('#pv-table tbody');
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
				var btn = $('<button class="button button-small pv-action">').text(act).attr('data-act', act).attr('data-id', c.Id);
				actions.append(btn).append(' ');
			});
			var logsBtn = $('<button class="button button-small pv-logs">Logs</button>').attr('data-id', c.Id);
			actions.append(logsBtn);
			row.append(actions);
			tbody.append(row);
		});
	}

	function loadContainers(){
		if (!hasDashboard) return;
		var envEl = $('#pv-env');
		if (!envEl.length || !envEl.val()) {
			$('#pv-table tbody').html('<tr><td colspan="6">No environment selected. Add one under Settings.</td></tr>');
			return;
		}
		$('#pv-table tbody').html('<tr><td colspan="6">Loading...</td></tr>');
		$.post(pv.ajaxUrl, { action: 'pv_list_containers', nonce: pv.nonce, envId: envEl.val() }, function(r){
			if(!r.success){ alert(r.data && r.data.message ? r.data.message : 'Error'); return; }
			renderTable(r.data);
		});
	}

	$(document).on('click', '#pv-refresh', loadContainers);
	$(document).on('change', '#pv-env', loadContainers);
	$(document).on('click','.pv-action',function(){
		var action = $(this).data('act'); var id = $(this).data('id');
		$.post(pv.ajaxUrl, { action: 'pv_container_action', nonce: pv.nonce, envId: $('#pv-env').val(), containerId:id, op:action }, function(r){
			if(!r.success){ alert(r.data && r.data.message ? r.data.message : 'Error'); return; }
			loadContainers();
		});
	});
	$(document).on('click','.pv-logs',function(){
		var containerId = $(this).data('id');
		$('#pv-logs-pre').text('Loading logs...');
		$('#pv-logs-modal').data('containerId', containerId).show();
		$.post(pv.ajaxUrl, { action:'pv_fetch_logs', nonce: pv.nonce, envId: $('#pv-env').val(), containerId: containerId }, function(r){
			if(!r.success){ $('#pv-logs-pre').text('Failed: ' + (r.data && r.data.message ? r.data.message : 'Error')); return; }
			$('#pv-logs-pre').text(r.data.logs || '');
		});
	});
	$('#pv-logs-close').on('click', function(){ $('#pv-logs-modal').hide().removeData('containerId'); });
	$('#pv-logs-refresh').on('click', function(){
		var id = $('#pv-logs-modal').data('containerId');
		if(!id) return;
		$('#pv-logs-pre').text('Loading logs...');
		$.post(pv.ajaxUrl, { action:'pv_fetch_logs', nonce: pv.nonce, envId: $('#pv-env').val(), containerId: id }, function(r){
			if(!r.success){ $('#pv-logs-pre').text('Failed: ' + (r.data && r.data.message ? r.data.message : 'Error')); return; }
			$('#pv-logs-pre').text(r.data.logs || '');
		});
	});

	// Initial load only on dashboard page
	if (hasDashboard) {
		loadContainers();
	}
});


