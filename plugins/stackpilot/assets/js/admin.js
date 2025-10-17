jQuery(function($){
	var hasDashboard = $('#sp-table').length > 0;

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
		$.post(ajaxurl, { action: 'sp_list_containers', nonce: sp ? sp.nonce : '', envId: envEl.val() }, function(r){
			if(!r.success){ alert(r.data && r.data.message ? r.data.message : 'Error'); return; }
			renderTable(r.data);
		});
	}

	$(document).on('click', '#sp-refresh', loadContainers);
	$(document).on('change', '#sp-env', loadContainers);
	$(document).on('click','.sp-action',function(){
		var action = $(this).data('act'); var id = $(this).data('id');
		$.post(ajaxurl, { action: 'sp_container_action', nonce: sp ? sp.nonce : '', envId: $('#sp-env').val(), containerId:id, op:action }, function(r){
			if(!r.success){ alert(r.data && r.data.message ? r.data.message : 'Error'); return; }
			loadContainers();
		});
	});
	$(document).on('click','.sp-logs',function(){
		var containerId = $(this).data('id');
		$('#sp-logs-pre').text('Loading logs...');
		$('#sp-logs-modal').show();
		$.post(ajaxurl, { action:'sp_fetch_logs', nonce: sp ? sp.nonce : '', envId: $('#sp-env').val(), containerId: containerId }, function(r){
			if(!r.success){ $('#sp-logs-pre').text('Failed: ' + (r.data && r.data.message ? r.data.message : 'Error')); return; }
			$('#sp-logs-pre').text(r.data.logs || '');
		});
	});
	$('#sp-logs-close').on('click', function(){ $('#sp-logs-modal').hide(); });
	$('#sp-logs-refresh').on('click', function(){
		var id = $('#sp-logs-modal').data('containerId');
		if(!id) return;
		$('#sp-logs-pre').text('Loading logs...');
		$.post(ajaxurl, { action:'sp_fetch_logs', nonce: sp ? sp.nonce : '', envId: $('#sp-env').val(), containerId: id }, function(r){
			if(!r.success){ $('#sp-logs-pre').text('Failed: ' + (r.data && r.data.message ? r.data.message : 'Error')); return; }
			$('#sp-logs-pre').text(r.data.logs || '');
		});
	});

	if (hasDashboard) { loadContainers(); }
});


