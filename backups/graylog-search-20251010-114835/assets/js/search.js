jQuery(document).ready(function($) {
    // Handle search form submission - Admin interface
    $('#graylog-search-form').on('submit', function(e) {
        e.preventDefault();
        performSearch($(this), 'admin');
    });
    
    // Handle search form submission - Shortcode interface
    $('.graylog-search-shortcode').on('submit', '.graylog-search-form', function(e) {
        e.preventDefault();
        performSearch($(this), 'shortcode');
    });
    
    // Perform search function
    function performSearch($form, interfaceType) {
        var $container = interfaceType === 'admin' ? $('.graylog-search-wrap') : $form.closest('.graylog-search-shortcode');
        
        // Hide previous results/errors
        $container.find('.graylog-results-container, #search-results-container').hide();
        $container.find('.graylog-error-message, #search-error').hide();
        
        // Show loading
        $container.find('.graylog-loading, #search-loading').show();
        
        // Get form data based on interface type
        var formData = {
            action: 'graylog_search_logs',
            nonce: graylogSearch.nonce,
            fqdn: interfaceType === 'admin' ? $('#search_fqdn').val() : $form.find('.search-fqdn').val(),
            search_terms: interfaceType === 'admin' ? $('#search_terms').val() : $form.find('.search-terms').val(),
            filter_out: interfaceType === 'admin' ? $('#filter_out').val() : $form.find('.filter-out').val(),
            time_range: interfaceType === 'admin' ? $('#time_range').val() : $form.find('.time-range').val(),
            limit: interfaceType === 'admin' ? $('#result_limit').val() : $form.find('.result-limit').val()
        };
        
        // Make AJAX request
        $.ajax({
            url: graylogSearch.ajaxUrl,
            type: 'POST',
            data: formData,
            success: function(response) {
                console.log('AJAX response:', response);
                $container.find('.graylog-loading, #search-loading').hide();
                
                if (response.success) {
                    displayResults(response.data, $container, interfaceType);
                } else {
                    console.error('Search failed:', response);
                    showError(response.data.message || 'Search failed', $container, interfaceType);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', xhr, status, error);
                $container.find('.graylog-loading, #search-loading').hide();
                showError('Network error: ' + error, $container, interfaceType);
            }
        });
    }
    
    // Handle clear button - Admin
    $('#clear-search').on('click', function() {
        $('#graylog-search-form')[0].reset();
        $('#search-results-container').hide();
        $('#search-error').hide();
    });
    
    // Handle clear button - Shortcode
    $('.graylog-search-shortcode').on('click', '.clear-search', function() {
        var $form = $(this).closest('.graylog-search-shortcode');
        $form.find('.graylog-search-form')[0].reset();
        $form.find('.graylog-results-container').hide();
        $form.find('.graylog-error-message').hide();
    });
    
    // Display search results
    function displayResults(data, $container, interfaceType) {
        console.log('Displaying results:', data);
        var messages = data.messages || [];
        var totalResults = data.total_results || 0;
        
        console.log('Total results:', totalResults);
        console.log('Messages count:', messages.length);
        
        if (messages.length === 0) {
            showError('No results found', $container, interfaceType);
            return;
        }
        
        // Update result count
        var countText = '(' + totalResults + ' total)';
        if (interfaceType === 'admin') {
            $('#result-count').text(countText);
        } else {
            $container.find('.result-count').text(countText);
        }
        
        // Build table with compact 2-column layout
        var table = '<div class="graylog-results-table-wrapper">';
        table += '<table class="wp-list-table widefat fixed striped graylog-results-table">';
        table += '<thead><tr>';
        table += '<th class="column-info">Info</th>';
        table += '<th class="column-message">Message</th>';
        table += '</tr></thead>';
        table += '<tbody>';
        
        messages.forEach(function(item) {
            var message = item.message || {};
            
            // Extract fields
            var timestamp = message.timestamp || '';
            var source = message.source || message.host || 'N/A';
            var messageText = message.message || message.full_message || 'No message';
            
            // Parse level from message text or use default
            var level = 'INFO';
            if (message.level && message.level !== -1) {
                level = String(message.level).toUpperCase();
            } else {
                // Try to extract level from message text
                var msgLower = messageText.toLowerCase();
                if (msgLower.includes('error')) level = 'ERROR';
                else if (msgLower.includes('warn')) level = 'WARNING';
                else if (msgLower.includes('threat') || msgLower.includes('blocked')) level = 'ERROR';
                else if (msgLower.includes('connected') || msgLower.includes('disconnected')) level = 'INFO';
            }
            
            // Format timestamp - compact version (MM/DD HH:MM AM/PM)
            var timestampFormatted = '';
            if (timestamp) {
                var date = new Date(timestamp);
                var month = String(date.getMonth() + 1).padStart(2, '0');
                var day = String(date.getDate()).padStart(2, '0');
                var hours = date.getHours();
                var minutes = String(date.getMinutes()).padStart(2, '0');
                var ampm = hours >= 12 ? 'PM' : 'AM';
                hours = hours % 12;
                hours = hours ? hours : 12; // 0 should be 12
                timestampFormatted = month + '/' + day + ' ' + hours + ':' + minutes + ' ' + ampm;
            }
            
            // Determine level class
            var levelClass = 'level-' + level.toLowerCase();
            
            // Build combined info column
            var infoHtml = '<div class="log-info-stacked">';
            infoHtml += '<div class="log-timestamp">' + escapeHtml(timestampFormatted) + '</div>';
            infoHtml += '<div class="log-source">' + escapeHtml(source) + '</div>';
            infoHtml += '<div class="log-level-container"><span class="log-level log-level-compact ' + levelClass + '">' + escapeHtml(level) + '</span></div>';
            infoHtml += '</div>';
            
            table += '<tr>';
            table += '<td class="column-info">' + infoHtml + '</td>';
            table += '<td class="column-message"><div class="message-text">' + escapeHtml(messageText) + '</div></td>';
            table += '</tr>';
        });
        
        table += '</tbody></table>';
        table += '</div>';
        
        // Display results based on interface type
        if (interfaceType === 'admin') {
            $('#search-results').html(table);
            $('#search-results-container').show();
            
            // Scroll to results
            $('html, body').animate({
                scrollTop: $('#search-results-container').offset().top - 50
            }, 500);
        } else {
            $container.find('.graylog-results-content').html(table);
            $container.find('.graylog-results-container').show();
            
            // Scroll container to top
            $container.find('.graylog-results-scroll').scrollTop(0);
        }
    }
    
    // Show error message
    function showError(message, $container, interfaceType) {
        if (interfaceType === 'admin') {
            $('#error-message').text(message);
            $('#search-error').show();
        } else {
            $container.find('.graylog-error-message').text(message).show();
        }
    }
    
    // Escape HTML to prevent XSS
    function escapeHtml(text) {
        if (!text) return '';
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
    }
});

