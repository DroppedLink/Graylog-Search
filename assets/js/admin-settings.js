/**
 * Admin Settings JavaScript
 *
 * @package GraylogSearch
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		// Test Graylog Connection
		$('#test-graylog-connection').on('click', function() {
			var $button = $(this);
			var $spinner = $('#connection-test-spinner');
			var $result = $('#connection-test-result');
			
			// Get current form values (not saved yet)
			var apiUrl = $('#graylog_api_url').val();
			var apiToken = $('#graylog_api_token').val();
			var disableSSL = $('#disable_ssl_verify').is(':checked');
			
			if (!apiUrl || !apiToken) {
				$result.html('<div class="notice notice-error inline"><p><strong>' + graylogSearch.strings.error + ':</strong> ' + graylogSearch.strings.enterApiUrlToken + '</p></div>').show();
				return;
			}
			
			$button.prop('disabled', true);
			$spinner.css('visibility', 'visible');
			$result.html('').hide();
			
			$.ajax({
				url: graylogSearch.ajaxUrl,
				type: 'POST',
				data: {
					action: 'graylog_test_connection',
					nonce: graylogSearch.settingsNonce,
					api_url: apiUrl,
					api_token: apiToken,
					disable_ssl: disableSSL ? '1' : '0'
				},
				timeout: 30000,
				success: function(response) {
					if (response.success) {
						var html = '<div class="notice notice-success inline" style="padding: 15px;"><p><strong>✅ ' + graylogSearch.strings.connectionSuccess + '</strong></p>';
						html += '<ul style="margin: 10px 0 0 20px;">';
						
						if (response.data.graylog_version) {
							html += '<li><strong>' + graylogSearch.strings.graylogVersion + ':</strong> ' + response.data.graylog_version + '</li>';
						}
						if (response.data.hostname) {
							html += '<li><strong>' + graylogSearch.strings.serverHostname + ':</strong> ' + response.data.hostname + '</li>';
						}
						if (response.data.message_count !== undefined) {
							html += '<li><strong>' + graylogSearch.strings.testSearch + ':</strong> ' + graylogSearch.strings.found + ' ' + response.data.message_count + ' ' + graylogSearch.strings.messages + '</li>';
						}
						if (response.data.response_time) {
							html += '<li><strong>' + graylogSearch.strings.responseTime + ':</strong> ' + response.data.response_time + 'ms</li>';
						}
						
						html += '</ul></div>';
						$result.html(html).show();
					} else {
						var html = '<div class="notice notice-error inline" style="padding: 15px;"><p><strong>❌ ' + graylogSearch.strings.connectionFailed + '</strong></p>';
						html += '<p><strong>' + graylogSearch.strings.error + ':</strong> ' + (response.data.message || graylogSearch.strings.unknownError) + '</p>';
						
						if (response.data.details) {
							html += '<p style="margin-top: 10px;"><strong>' + graylogSearch.strings.details + ':</strong></p>';
							html += '<pre style="background: #f0f0f1; padding: 10px; border-radius: 3px; font-size: 12px; overflow-x: auto;">' + response.data.details + '</pre>';
						}
						
						if (response.data.suggestions) {
							html += '<p style="margin-top: 10px;"><strong>' + graylogSearch.strings.suggestions + ':</strong></p><ul style="margin-left: 20px;">';
							response.data.suggestions.forEach(function(suggestion) {
								html += '<li>' + suggestion + '</li>';
							});
							html += '</ul>';
						}
						
						html += '</div>';
						$result.html(html).show();
					}
				},
				error: function(jqXHR, textStatus) {
					var html = '<div class="notice notice-error inline"><p><strong>❌ ' + graylogSearch.strings.requestFailed + '</strong></p>';
					html += '<p>' + graylogSearch.strings.status + ': ' + textStatus + '</p>';
					if (textStatus === 'timeout') {
						html += '<p>' + graylogSearch.strings.timeoutMessage + '</p>';
					}
					html += '</div>';
					$result.html(html).show();
				},
				complete: function() {
					$button.prop('disabled', false);
					$spinner.css('visibility', 'hidden');
				}
			});
		});
		
		// Check for Updates
		$('#check-for-updates').on('click', function() {
			var $button = $(this);
			var $status = $('#update-check-status');
			
			$button.prop('disabled', true);
			$status.html('<span style="color: #2271b1;">' + graylogSearch.strings.checking + '</span>');
			
			$.ajax({
				url: graylogSearch.ajaxUrl,
				type: 'POST',
				data: {
					action: 'graylog_check_updates',
					nonce: graylogSearch.nonce
				},
				success: function(response) {
					if (response.success) {
						$status.html('<span style="color: #00a32a;">✓ ' + graylogSearch.strings.checkComplete + '</span>');
						setTimeout(function() {
							location.reload();
						}, 1000);
					} else {
						$status.html('<span style="color: #d63638;">✗ ' + graylogSearch.strings.checkFailed + '</span>');
					}
				},
				error: function() {
					$status.html('<span style="color: #d63638;">✗ ' + graylogSearch.strings.errorCheckingUpdates + '</span>');
				},
				complete: function() {
					setTimeout(function() {
						$button.prop('disabled', false);
						$status.html('');
					}, 3000);
				}
			});
		});
	});

	/**
	 * Copy shortcode to clipboard
	 */
	window.copyShortcode = function(text) {
		navigator.clipboard.writeText(text).then(function() {
			// Show success message
			var msg = document.createElement('div');
			msg.className = 'notice notice-success is-dismissible';
			msg.style.position = 'fixed';
			msg.style.top = '32px';
			msg.style.right = '20px';
			msg.style.zIndex = '999999';
			msg.innerHTML = '<p><strong>' + graylogSearch.strings.copied + '</strong> ' + graylogSearch.strings.shortcodeCopied + '</p>';
			document.body.appendChild(msg);
			setTimeout(function() {
				msg.remove();
			}, 3000);
		}, function(err) {
			alert(graylogSearch.strings.failedToCopy + ': ' + err);
		});
	};

})(jQuery);
