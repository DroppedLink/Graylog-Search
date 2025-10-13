jQuery(document).ready(function($) {
    // Active client-side filters
    var activeFilters = [];
    
    // DNS lookup cache
    var dnsCache = {};
    
    // Auto-refresh variables
    var autoRefreshInterval = null;
    var lastSearchData = null;
    
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
        
        // Store for auto-refresh
        lastSearchData = {
            form: $form,
            interfaceType: interfaceType,
            formData: formData
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
            
            // Enrich message text with clickable IP addresses
            var enrichedMessage = enrichIPAddresses(messageText);
            
            table += '<tr>';
            table += '<td class="column-info">' + infoHtml + '</td>';
            table += '<td class="column-message"><div class="message-text">' + enrichedMessage + '</div></td>';
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
        
        // Apply any existing filters
        if (activeFilters.length > 0) {
            applyFilters();
        }
        
        // Update result count and IP resolve button
        updateResultCount();
    }
    
    // Text selection for interactive filtering
    $(document).on('mouseup', '.graylog-results-table', function(e) {
        var selectedText = window.getSelection().toString().trim();
        
        // Remove any existing popup
        $('.filter-popup').remove();
        
        if (selectedText.length > 2 && selectedText.length < 100) {
            showFilterPopup(selectedText, e.pageX, e.pageY);
        }
    });
    
    // Show filter popup
    function showFilterPopup(text, x, y) {
        var truncatedText = text.length > 30 ? text.substring(0, 30) + '...' : text;
        
        var popup = $('<div class="filter-popup"></div>');
        popup.html('<button class="filter-out-btn"><span class="dashicons dashicons-dismiss"></span> Filter out "' + escapeHtml(truncatedText) + '"</button>');
        popup.css({
            position: 'absolute',
            left: x + 10 + 'px',
            top: y - 40 + 'px'
        });
        
        $('body').append(popup);
        
        // Handle filter button click
        popup.find('.filter-out-btn').on('click', function() {
            addFilter(text);
            popup.remove();
            window.getSelection().removeAllRanges();
        });
        
        // Remove popup on any click outside
        setTimeout(function() {
            $(document).one('click', function() {
                popup.remove();
            });
        }, 100);
    }
    
    // Add filter
    function addFilter(filterText) {
        if (activeFilters.indexOf(filterText) === -1) {
            activeFilters.push(filterText);
            updateFilterDisplay();
            applyFilters();
        }
    }
    
    // Remove filter
    function removeFilter(filterText) {
        var index = activeFilters.indexOf(filterText);
        if (index > -1) {
            activeFilters.splice(index, 1);
            updateFilterDisplay();
            applyFilters();
        }
    }
    
    // Clear all filters
    function clearAllFilters() {
        activeFilters = [];
        updateFilterDisplay();
        applyFilters();
    }
    
    // Update filter display
    function updateFilterDisplay() {
        var $container = $('.active-filters-container');
        
        if (activeFilters.length === 0) {
            $container.hide();
            return;
        }
        
        var html = '<div class="active-filters">';
        html += '<span class="filters-label">Filtering out:</span> ';
        
        activeFilters.forEach(function(filter) {
            var truncated = filter.length > 30 ? filter.substring(0, 30) + '...' : filter;
            html += '<span class="filter-badge" data-filter="' + escapeHtml(filter) + '">';
            html += escapeHtml(truncated);
            html += ' <button class="remove-filter" title="Remove filter">×</button>';
            html += '</span> ';
        });
        
        html += '<button class="clear-all-filters">Clear All</button>';
        html += '</div>';
        
        $container.html(html).show();
    }
    
    // Handle remove filter click
    $(document).on('click', '.remove-filter', function(e) {
        e.stopPropagation();
        var filterText = $(this).closest('.filter-badge').data('filter');
        removeFilter(filterText);
    });
    
    // Handle clear all filters click
    $(document).on('click', '.clear-all-filters', function() {
        clearAllFilters();
    });
    
    // Apply filters to rows
    function applyFilters() {
        var $rows = $('.graylog-results-table tbody tr');
        
        if (activeFilters.length === 0) {
            $rows.removeClass('filtered-out').show();
            updateResultCount();
            return;
        }
        
        $rows.each(function() {
            var $row = $(this);
            var messageText = $row.find('.message-text').text().toLowerCase();
            var shouldHide = false;
            
            activeFilters.forEach(function(filter) {
                if (messageText.includes(filter.toLowerCase())) {
                    shouldHide = true;
                }
            });
            
            if (shouldHide) {
                if (!$row.hasClass('filtered-out')) {
                    $row.addClass('filtered-out').fadeOut(200);
                }
            } else {
                if ($row.hasClass('filtered-out')) {
                    $row.removeClass('filtered-out').fadeIn(200);
                }
            }
        });
        
        // Update count after animation
        setTimeout(function() {
            updateResultCount();
        }, 250);
    }
    
    // Update result count
    function updateResultCount() {
        var $rows = $('.graylog-results-table tbody tr');
        var total = $rows.length;
        var visible = $rows.filter(':visible').length;
        var filtered = total - visible;
        
        if (filtered > 0) {
            var countText = '(' + visible + ' shown, ' + filtered + ' filtered, ' + total + ' total)';
        } else {
            var countText = '(' + total + ' total)';
        }
        
        $('#result-count, .result-count').text(countText);
        
        // Update IP resolve button visibility and count
        updateResolveAllButton();
    }
    
    // Update Resolve All IPs button
    function updateResolveAllButton() {
        var $unresolvedIPs = $('.ip-address').not('.ip-resolved, .ip-resolving, .ip-unresolvable, .ip-error');
        var count = $unresolvedIPs.length;
        
        if (count > 0) {
            $('.resolve-all-ips-btn').show().find('.ip-count').text(count);
        } else {
            $('.resolve-all-ips-btn').hide();
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
    
    // Enrich message text with clickable IP addresses
    function enrichIPAddresses(text) {
        if (!text) return '';
        
        // IPv4 regex pattern
        var ipv4Pattern = /\b(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\b/g;
        
        // Escape HTML first
        var escaped = escapeHtml(text);
        
        // Find and wrap IP addresses
        var enriched = escaped.replace(ipv4Pattern, function(ip) {
            // Check if we have a cached hostname for this IP
            var displayText = dnsCache[ip] || ip;
            var resolvedClass = dnsCache[ip] ? 'ip-resolved' : '';
            return '<span class="ip-address ' + resolvedClass + '" data-ip="' + ip + '" title="Click to resolve DNS">' + displayText + '</span>';
        });
        
        return enriched;
    }
    
    // Handle IP address click for DNS lookup
    $(document).on('click', '.ip-address', function(e) {
        e.stopPropagation();
        var $ip = $(this);
        var ipAddress = $ip.data('ip');
        
        // If already resolved, show info
        if (dnsCache[ipAddress]) {
            return; // Already resolved
        }
        
        // Show loading state
        $ip.addClass('ip-resolving').attr('title', 'Resolving...');
        
        // Perform DNS lookup via AJAX
        $.ajax({
            url: graylogSearch.ajaxUrl,
            type: 'POST',
            data: {
                action: 'graylog_dns_lookup',
                nonce: graylogSearch.nonce,
                ip: ipAddress
            },
            success: function(response) {
                if (response.success && response.data.hostname) {
                    var hostname = response.data.hostname;
                    
                    // Cache the result
                    dnsCache[ipAddress] = hostname;
                    
                    // Update all instances of this IP on the page
                    $('.ip-address[data-ip="' + ipAddress + '"]').each(function() {
                        $(this)
                            .text(hostname)
                            .removeClass('ip-resolving')
                            .addClass('ip-resolved')
                            .attr('title', 'Resolved: ' + hostname + ' (was ' + ipAddress + ')');
                    });
            } else {
                // Resolution failed, mark as unresolvable
                dnsCache[ipAddress] = ipAddress + ' (no DNS)';
                $('.ip-address[data-ip="' + ipAddress + '"]').each(function() {
                    $(this)
                        .removeClass('ip-resolving')
                        .addClass('ip-unresolvable')
                        .attr('title', 'DNS lookup failed - no hostname found');
                });
                
                // Show notification
                showNotification('DNS lookup failed for ' + ipAddress, 'error');
            }
            },
            error: function() {
                $('.ip-address[data-ip="' + ipAddress + '"]').each(function() {
                    $(this)
                        .removeClass('ip-resolving')
                        .addClass('ip-error')
                        .attr('title', 'DNS lookup error');
                });
                
                showNotification('DNS lookup error for ' + ipAddress, 'error');
            }
        });
    });
    
    // Show notification
    function showNotification(message, type) {
        type = type || 'info';
        
        var $notification = $('<div class="graylog-notification graylog-notification-' + type + '"></div>');
        $notification.html('<span class="notification-icon"></span> ' + escapeHtml(message));
        
        $('body').append($notification);
        
        // Trigger animation
        setTimeout(function() {
            $notification.addClass('show');
        }, 10);
        
        // Auto-hide after 4 seconds
        setTimeout(function() {
            $notification.removeClass('show');
            setTimeout(function() {
                $notification.remove();
            }, 300);
        }, 4000);
    }
    
    // Handle auto-refresh toggle
    $(document).on('change', '#auto-refresh-toggle', function() {
        if ($(this).is(':checked')) {
            var interval = parseInt($('#auto-refresh-interval').val()) * 1000;
            startAutoRefresh(interval);
        } else {
            stopAutoRefresh();
        }
    });
    
    // Handle auto-refresh interval change
    $(document).on('change', '#auto-refresh-interval', function() {
        if ($('#auto-refresh-toggle').is(':checked')) {
            stopAutoRefresh();
            var interval = parseInt($(this).val()) * 1000;
            startAutoRefresh(interval);
        }
    });
    
    // Start auto-refresh
    function startAutoRefresh(interval) {
        stopAutoRefresh(); // Clear any existing interval
        
        if (!lastSearchData) {
            return;
        }
        
        showNotification('Auto-refresh enabled (every ' + (interval/1000) + 's)', 'success');
        
        autoRefreshInterval = setInterval(function() {
            console.log('Auto-refreshing search...');
            
            // Re-run the last search
            if (lastSearchData) {
                var $form = lastSearchData.form;
                var interfaceType = lastSearchData.interfaceType;
                var $container = interfaceType === 'admin' ? $('.graylog-search-wrap') : $form.closest('.graylog-search-shortcode');
                
                // Make AJAX request (simplified version without showing loading)
                $.ajax({
                    url: graylogSearch.ajaxUrl,
                    type: 'POST',
                    data: lastSearchData.formData,
                    success: function(response) {
                        if (response.success) {
                            displayResults(response.data, $container, interfaceType);
                        }
                    }
                });
            }
        }, interval);
    }
    
    // Stop auto-refresh
    function stopAutoRefresh() {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
            autoRefreshInterval = null;
            showNotification('Auto-refresh disabled', 'info');
        }
    }
    
    // Handle Resolve All IPs button click
    $(document).on('click', '.resolve-all-ips-btn', function() {
        var $btn = $(this);
        
        // Get all unresolved IPs
        var $unresolvedIPs = $('.ip-address').not('.ip-resolved, .ip-resolving, .ip-unresolvable, .ip-error');
        
        if ($unresolvedIPs.length === 0) {
            showNotification('No IPs to resolve', 'info');
            return;
        }
        
        // Disable button
        $btn.prop('disabled', true).addClass('resolving');
        
        // Get unique IPs
        var uniqueIPs = [];
        $unresolvedIPs.each(function() {
            var ip = $(this).data('ip');
            if (uniqueIPs.indexOf(ip) === -1 && !dnsCache[ip]) {
                uniqueIPs.push(ip);
            }
        });
        
        var totalIPs = uniqueIPs.length;
        var resolvedCount = 0;
        var failedCount = 0;
        
        showNotification('Resolving ' + totalIPs + ' IP addresses...', 'info');
        
        // Mark all as resolving
        uniqueIPs.forEach(function(ip) {
            $('.ip-address[data-ip="' + ip + '"]').addClass('ip-resolving').attr('title', 'Resolving...');
        });
        
        // Resolve each IP with delay to avoid overwhelming the server
        var delay = 0;
        uniqueIPs.forEach(function(ip, index) {
            setTimeout(function() {
                resolveIP(ip, function(success) {
                    if (success) {
                        resolvedCount++;
                    } else {
                        failedCount++;
                    }
                    
                    // Update button text with progress
                    var remaining = totalIPs - resolvedCount - failedCount;
                    if (remaining > 0) {
                        $btn.find('.ip-count').text(remaining);
                    }
                    
                    // Check if all done
                    if (resolvedCount + failedCount === totalIPs) {
                        $btn.prop('disabled', false).removeClass('resolving');
                        updateResolveAllButton();
                        
                        var message = 'DNS Resolution Complete: ';
                        if (resolvedCount > 0) {
                            message += resolvedCount + ' resolved';
                        }
                        if (failedCount > 0) {
                            message += (resolvedCount > 0 ? ', ' : '') + failedCount + ' failed';
                        }
                        showNotification(message, resolvedCount > 0 ? 'success' : 'warning');
                    }
                });
            }, delay);
            delay += 300; // 300ms delay between requests
        });
    });
    
    // Resolve a single IP (helper function)
    function resolveIP(ipAddress, callback) {
        $.ajax({
            url: graylogSearch.ajaxUrl,
            type: 'POST',
            data: {
                action: 'graylog_dns_lookup',
                nonce: graylogSearch.nonce,
                ip: ipAddress
            },
            success: function(response) {
                if (response.success && response.data.hostname) {
                    var hostname = response.data.hostname;
                    dnsCache[ipAddress] = hostname;
                    
                    $('.ip-address[data-ip="' + ipAddress + '"]').each(function() {
                        $(this)
                            .text(hostname)
                            .removeClass('ip-resolving')
                            .addClass('ip-resolved')
                            .attr('title', 'Resolved: ' + hostname + ' (was ' + ipAddress + ')');
                    });
                    
                    callback(true);
                } else {
                    dnsCache[ipAddress] = ipAddress + ' (no DNS)';
                    $('.ip-address[data-ip="' + ipAddress + '"]').each(function() {
                        $(this)
                            .removeClass('ip-resolving')
                            .addClass('ip-unresolvable')
                            .attr('title', 'DNS lookup failed - no hostname found');
                    });
                    
                    callback(false);
                }
            },
            error: function() {
                $('.ip-address[data-ip="' + ipAddress + '"]').each(function() {
                    $(this)
                        .removeClass('ip-resolving')
                        .addClass('ip-error')
                        .attr('title', 'DNS lookup error');
                });
                
                callback(false);
            }
        });
    }
});

