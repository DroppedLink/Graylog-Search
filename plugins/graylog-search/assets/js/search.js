jQuery(document).ready(function($) {
    // Active client-side filters
    var activeFilters = [];
    var keepOnlyFilters = [];
    var activeHighlights = [];
    
    // DNS lookup cache
    var dnsCache = {};
    
    // Auto-refresh variables
    var autoRefreshInterval = null;
    var lastSearchData = null;
    
    // Timezone variables
    var userTimezone = 'UTC';
    var showOriginalTimes = false;
    var originalResultsData = null;
    
    // Parse variables
    var parseEnabled = false;
    var parseFormats = {json: true, kv: true, cef: true, leef: true};
    
    // Load saved timezone preference on page load
    loadTimezonePreference();
    
    // Load saved searches, recent searches, and quick filters
    loadSavedSearches();
    loadRecentSearches();
    loadQuickFilters();
    
    // Debounce timer for search-as-you-type
    var debounceTimer = null;
    
    // Handle search form submission - Admin interface
    $('#graylog-search-form').on('submit', function(e) {
        e.preventDefault();
        clearTimeout(debounceTimer);
        performSearch($(this), 'admin');
    });
    
    // Handle search form submission - Shortcode interface
    $('.graylog-search-shortcode').on('submit', '.graylog-search-form', function(e) {
        e.preventDefault();
        clearTimeout(debounceTimer);
        performSearch($(this), 'shortcode');
    });
    
    // Optional: Auto-search on input change (debounced)
    $('#search_terms, #search_fqdn, .search-terms, .search-fqdn').on('input', function() {
        // Only if enabled (not enabled by default to avoid unwanted API calls)
        if (window.graylogAutoSearchEnabled) {
            clearTimeout(debounceTimer);
            var $form = $(this).closest('form');
            var interfaceType = $form.attr('id') === 'graylog-search-form' ? 'admin' : 'shortcode';
            
            debounceTimer = setTimeout(function() {
                performSearch($form, interfaceType);
            }, 300);
        }
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
            var timestampClass = '';
            var timestampTitle = '';
            
            if (timestamp) {
                // Try to convert to user's timezone
                var converted = convertTimestamp(timestamp, userTimezone);
                
                if (converted) {
                    // Use converted timestamp
                    timestampFormatted = converted;
                    timestampClass = 'timestamp-converted';
                    
                    // Create original timestamp for title
                    var date = new Date(timestamp);
                    var month = String(date.getUTCMonth() + 1).padStart(2, '0');
                    var day = String(date.getUTCDate()).padStart(2, '0');
                    var hours = date.getUTCHours();
                    var minutes = String(date.getUTCMinutes()).padStart(2, '0');
                    var ampm = hours >= 12 ? 'PM' : 'AM';
                    hours = hours % 12;
                    hours = hours ? hours : 12;
                    var originalTime = month + '/' + day + ' ' + hours + ':' + minutes + ' ' + ampm + ' UTC';
                    timestampTitle = 'Original (UTC): ' + originalTime;
                } else {
                    // Use original timestamp
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
            }
            
            // Determine level class
            var levelClass = 'level-' + level.toLowerCase();
            
            // Build combined info column
            var infoHtml = '<div class="log-info-stacked">';
            var timestampHtml = '<div class="log-timestamp ' + timestampClass + '"';
            if (timestampTitle) {
                timestampHtml += ' title="' + escapeHtml(timestampTitle) + '"';
            }
            timestampHtml += '>' + escapeHtml(timestampFormatted) + '</div>';
            infoHtml += timestampHtml;
            infoHtml += '<div class="log-source">' + escapeHtml(source) + '</div>';
            infoHtml += '<div class="log-level-container"><span class="log-level log-level-compact ' + levelClass + '">' + escapeHtml(level) + '</span></div>';
            infoHtml += '</div>';
            
            // Enrich message text with clickable IP addresses AND convert embedded timestamps
            var enrichedMessage = enrichMessageText(messageText, userTimezone);
            
            // Parse message if enabled
            var parsedFields = parseMessageText(messageText);
            
            // Store row data for exports
            var rowData = {
                timestamp: timestamp,
                source: source,
                level: level,
                message: messageText,
                parsedFields: parsedFields
            };
            var rowDataJson = escapeHtml(JSON.stringify(rowData));
            
            // Build row with action menu
            table += '<tr data-row-data=\'' + rowDataJson + '\'>';
            table += '<td class="column-info">' + infoHtml + '</td>';
            table += '<td class="column-message">';
            table += '<div class="message-text">' + enrichedMessage + '</div>';
            
            // Add row actions menu
            table += '<div class="row-actions-menu">';
            table += '<button class="row-actions-btn" title="Actions">⋮</button>';
            table += '<div class="row-actions-dropdown" style="display: none;">';
            table += '<button class="row-action-include" data-text="' + escapeHtml(source) + '">+ Include Source</button>';
            table += '<button class="row-action-exclude" data-text="' + escapeHtml(source) + '">− Exclude Source</button>';
            table += '<button class="row-action-copy">📋 Copy Row</button>';
            table += '<button class="row-action-details">📄 Details</button>';
            table += '</div>';
            table += '</div>';
            table += '</td>';
            table += '</tr>';
            
            // Add details row (hidden by default)
            table += '<tr class="details-row">';
            table += '<td colspan="2">';
            table += '<div class="details-content">';
            
            // Show parsed fields if available
            if (parsedFields) {
                table += '<div class="details-section">';
                table += '<h4>Parsed Fields:</h4>';
                table += '<div class="details-fields">';
                for (var key in parsedFields) {
                    if (parsedFields.hasOwnProperty(key)) {
                        table += '<div class="details-field-name">' + escapeHtml(key) + ':</div>';
                        table += '<div class="details-field-value parsed-field">' + escapeHtml(parsedFields[key]) + '</div>';
                    }
                }
                table += '</div>';
                table += '</div>';
            }
            
            // Show raw message
            table += '<div class="details-section">';
            table += '<h4>Raw Message:</h4>';
            table += '<div>' + escapeHtml(messageText) + '</div>';
            table += '</div>';
            
            table += '</div>';
            table += '</td>';
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
        
        // Store original data for timezone conversions
        originalResultsData = data;
        
        // Apply any existing filters
        if (activeFilters.length > 0) {
            applyFilters();
        }
        
        // Auto-highlight search terms
        autoHighlightSearchTerms($container, interfaceType);
        
        // Update result count and IP resolve button
        updateResultCount();
    }
    
    // Auto-highlight search terms in results
    function autoHighlightSearchTerms($container, interfaceType) {
        var searchTermsValue;
        
        // Get search terms from the appropriate form
        if (interfaceType === 'admin') {
            searchTermsValue = $('#search_terms').val();
        } else {
            searchTermsValue = $container.find('.search-terms').val();
        }
        
        if (!searchTermsValue || searchTermsValue.trim() === '') {
            return; // No search terms to highlight
        }
        
        // Parse search terms (split by commas and newlines, don't split on spaces for phrases)
        var terms = parseMultiValueInput(searchTermsValue);
        
        // Highlight each term
        terms.forEach(function(term) {
            if (term && term.trim().length > 0) {
                highlightText(term.trim());
            }
        });
    }
    
    // Parse multi-value input (commas and newlines, NOT spaces)
    function parseMultiValueInput(input) {
        var values = [];
        
        // Split by newlines
        var lines = input.split(/\r\n|\r|\n/);
        
        lines.forEach(function(line) {
            line = line.trim();
            if (line === '') {
                return;
            }
            
            // Split by comma
            if (line.indexOf(',') !== -1) {
                var parts = line.split(',');
                parts.forEach(function(part) {
                    part = part.trim();
                    if (part !== '') {
                        values.push(part);
                    }
                });
            } else {
                // Single value (or phrase with spaces)
                values.push(line);
            }
        });
        
        return values;
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
    
    // Show filter popup with multiple options
    function showFilterPopup(text, x, y) {
        var truncatedText = text.length > 30 ? text.substring(0, 30) + '...' : text;
        
        var popup = $('<div class="filter-popup"></div>');
        popup.html(`
            <div class="filter-popup-actions">
                <button class="filter-action-btn filter-out-btn" data-action="filter-out">
                    <span class="dashicons dashicons-dismiss"></span> Filter Out
                </button>
                <button class="filter-action-btn keep-only-btn" data-action="keep-only">
                    <span class="dashicons dashicons-visibility"></span> Keep Only
                </button>
                <button class="filter-action-btn highlight-btn" data-action="highlight">
                    <span class="dashicons dashicons-art"></span> Highlight
                </button>
                <button class="filter-action-btn copy-btn" data-action="copy">
                    <span class="dashicons dashicons-clipboard"></span> Copy
                </button>
            </div>
            <div class="filter-popup-text">"${escapeHtml(truncatedText)}"</div>
        `);
        popup.css({
            position: 'absolute',
            left: x + 10 + 'px',
            top: y - 80 + 'px'
        });
        
        $('body').append(popup);
        
        // Handle action button clicks
        popup.find('.filter-action-btn').on('click', function(e) {
            e.stopPropagation();
            var action = $(this).data('action');
            
            switch(action) {
                case 'filter-out':
                    addFilter(text);
                    break;
                case 'keep-only':
                    addKeepOnlyFilter(text);
                    break;
                case 'highlight':
                    highlightText(text);
                    break;
                case 'copy':
                    copyToClipboard(text);
                    break;
            }
            
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
    
    // Clear all keep-only filters
    function clearAllKeepOnlyFilters() {
        keepOnlyFilters = [];
        updateFilterDisplay();
        applyKeepOnlyFilters();
    }
    
    // Clear all highlights
    function clearAllHighlights() {
        activeHighlights.forEach(function(highlight) {
            $('.graylog-results-table .highlight-mark[data-highlight="' + escapeHtml(highlight) + '"]').each(function() {
                var $this = $(this);
                $this.replaceWith($this.html());
            });
        });
        activeHighlights = [];
        updateFilterDisplay();
    }
    
    // Keep only rows containing text
    function addKeepOnlyFilter(text) {
        if (keepOnlyFilters.indexOf(text) === -1) {
            keepOnlyFilters.push(text);
            updateFilterDisplay();
            applyKeepOnlyFilters();
        }
    }
    
    // Apply keep-only filters
    function applyKeepOnlyFilters() {
        if (keepOnlyFilters.length === 0) {
            // If no keep-only filters, show all rows (unless filtered out)
            $('.graylog-results-table tbody tr').each(function() {
                $(this).removeClass('keep-only-hidden');
            });
        } else {
            // Hide rows that don't match ANY keep-only filter
            $('.graylog-results-table tbody tr').each(function() {
                var $row = $(this);
                var rowText = $row.text().toLowerCase();
                var matches = false;
                
                for (var i = 0; i < keepOnlyFilters.length; i++) {
                    if (rowText.indexOf(keepOnlyFilters[i].toLowerCase()) !== -1) {
                        matches = true;
                        break;
                    }
                }
                
                if (!matches) {
                    $row.addClass('keep-only-hidden');
                } else {
                    $row.removeClass('keep-only-hidden');
                }
            });
        }
        
        updateResultCount();
    }
    
    // Remove keep-only filter
    function removeKeepOnlyFilter(filterText) {
        var index = keepOnlyFilters.indexOf(filterText);
        if (index > -1) {
            keepOnlyFilters.splice(index, 1);
            updateFilterDisplay();
            applyKeepOnlyFilters();
        }
    }
    
    // Highlight all occurrences of text
    function highlightText(text) {
        if (activeHighlights.indexOf(text) === -1) {
            activeHighlights.push(text);
            updateFilterDisplay();
        }
        
        // Add new highlights
        $('.graylog-results-table tbody tr').each(function() {
            var $row = $(this);
            $row.find('td').each(function() {
                var $td = $(this);
                var html = $td.html();
                var escapedText = escapeRegExp(text);
                var regex = new RegExp('(' + escapedText + ')', 'gi');
                var newHtml = html.replace(regex, '<mark class="highlight-mark" data-highlight="' + escapeHtml(text) + '">$1</mark>');
                $td.html(newHtml);
            });
        });
    }
    
    // Remove highlight
    function removeHighlight(text) {
        var index = activeHighlights.indexOf(text);
        if (index > -1) {
            activeHighlights.splice(index, 1);
            updateFilterDisplay();
            
            // Remove highlight marks
            $('.graylog-results-table .highlight-mark[data-highlight="' + escapeHtml(text) + '"]').each(function() {
                var $this = $(this);
                $this.replaceWith($this.html());
            });
        }
    }
    
    // Copy text to clipboard
    function copyToClipboard(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function() {
                showNotification('Copied to clipboard: "' + text + '"', 'success');
            }).catch(function(err) {
                showNotification('Failed to copy to clipboard', 'error');
            });
        } else {
            // Fallback for older browsers
            var textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-9999px';
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand('copy');
                showNotification('Copied to clipboard: "' + text + '"', 'success');
            } catch (err) {
                showNotification('Failed to copy to clipboard', 'error');
            }
            document.body.removeChild(textArea);
        }
    }
    
    // Escape special regex characters
    function escapeRegExp(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }
    
    // Update filter display
    function updateFilterDisplay() {
        var $container = $('.active-filters-container');
        
        if (activeFilters.length === 0 && keepOnlyFilters.length === 0 && activeHighlights.length === 0) {
            $container.hide();
            return;
        }
        
        var html = '<div class="active-filters">';
        
        // Filter Out section
        if (activeFilters.length > 0) {
            html += '<div class="filter-section">';
            html += '<span class="filters-label"><span class="dashicons dashicons-dismiss"></span> Filtering out:</span> ';
            
            activeFilters.forEach(function(filter) {
                var truncated = filter.length > 30 ? filter.substring(0, 30) + '...' : filter;
                html += '<span class="filter-badge filter-out-badge" data-filter="' + escapeHtml(filter) + '" data-type="filterout">';
                html += escapeHtml(truncated);
                html += ' <button class="remove-filter" title="Remove filter">×</button>';
                html += '</span> ';
            });
            html += '</div>';
        }
        
        // Keep Only section
        if (keepOnlyFilters.length > 0) {
            html += '<div class="filter-section">';
            html += '<span class="filters-label"><span class="dashicons dashicons-visibility"></span> Keeping only:</span> ';
            
            keepOnlyFilters.forEach(function(filter) {
                var truncated = filter.length > 30 ? filter.substring(0, 30) + '...' : filter;
                html += '<span class="filter-badge keep-only-badge" data-filter="' + escapeHtml(filter) + '" data-type="keeponly">';
                html += escapeHtml(truncated);
                html += ' <button class="remove-filter" title="Remove filter">×</button>';
                html += '</span> ';
            });
            html += '</div>';
        }
        
        // Highlights section
        if (activeHighlights.length > 0) {
            html += '<div class="filter-section">';
            html += '<span class="filters-label"><span class="dashicons dashicons-art"></span> Highlighted:</span> ';
            
            activeHighlights.forEach(function(highlight) {
                var truncated = highlight.length > 30 ? highlight.substring(0, 30) + '...' : highlight;
                html += '<span class="filter-badge highlight-badge" data-filter="' + escapeHtml(highlight) + '" data-type="highlight">';
                html += escapeHtml(truncated);
                html += ' <button class="remove-filter" title="Remove highlight">×</button>';
                html += '</span> ';
            });
            html += '</div>';
        }
        
        html += '<button class="clear-all-filters">Clear All</button>';
        html += '</div>';
        
        $container.html(html).show();
    }
    
    // Handle remove filter click
    $(document).on('click', '.remove-filter', function(e) {
        e.stopPropagation();
        var $badge = $(this).closest('.filter-badge');
        var filterText = $badge.data('filter');
        var filterType = $badge.data('type');
        
        if (filterType === 'filterout') {
            removeFilter(filterText);
        } else if (filterType === 'keeponly') {
            removeKeepOnlyFilter(filterText);
        } else if (filterType === 'highlight') {
            removeHighlight(filterText);
        }
    });
    
    // Handle clear all filters click
    $(document).on('click', '.clear-all-filters', function() {
        clearAllFilters();
        clearAllKeepOnlyFilters();
        clearAllHighlights();
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
    
    // Combined function to enrich message text with IP addresses and timestamp conversion
    function enrichMessageText(text, targetTimezone) {
        if (!text) return '';
        
        // First, escape HTML
        var escaped = escapeHtml(text);
        
        // Apply timezone conversion to embedded timestamps if needed
        if (!showOriginalTimes && targetTimezone !== 'UTC') {
            escaped = applyTimezoneConversionToText(escaped, targetTimezone);
        }
        
        // Then apply IP enrichment
        var ipv4Pattern = /\b(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\b/g;
        var enriched = escaped.replace(ipv4Pattern, function(ip) {
            var displayText = dnsCache[ip] || ip;
            var resolvedClass = dnsCache[ip] ? 'ip-resolved' : '';
            return '<span class="ip-address ' + resolvedClass + '" data-ip="' + ip + '" title="Click to resolve DNS">' + displayText + '</span>';
        });
        
        return enriched;
    }
    
    // Legacy function for backward compatibility
    function enrichIPAddresses(text) {
        return enrichMessageText(text, userTimezone);
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
    
    // ==================== TIMEZONE FUNCTIONS ====================
    
    // Load user's saved timezone preference
    function loadTimezonePreference() {
        $.ajax({
            url: graylogSearch.ajaxUrl,
            type: 'POST',
            data: {
                action: 'graylog_get_timezone',
                nonce: graylogSearch.nonce
            },
            success: function(response) {
                if (response.success && response.data.timezone) {
                    userTimezone = response.data.timezone;
                    $('#timezone-selector').val(userTimezone);
                }
            }
        });
    }
    
    // Handle timezone selector change
    $(document).on('change', '#timezone-selector', function() {
        var newTimezone = $(this).val();
        
        // Save timezone preference
        $.ajax({
            url: graylogSearch.ajaxUrl,
            type: 'POST',
            data: {
                action: 'graylog_save_timezone',
                nonce: graylogSearch.nonce,
                timezone: newTimezone
            },
            success: function(response) {
                if (response.success) {
                    userTimezone = newTimezone;
                    
                    // Re-render results if available
                    if (originalResultsData) {
                        var interfaceType = $('.graylog-search-wrap').length > 0 ? 'admin' : 'shortcode';
                        var $container = interfaceType === 'admin' ? $('.graylog-search-wrap') : $('.graylog-search-shortcode');
                        displayResults(originalResultsData, $container, interfaceType);
                    }
                    
                    showNotification('Timezone updated to ' + getTimezoneLabel(newTimezone), 'success');
                }
            }
        });
    });
    
    // Handle timezone toggle button
    $(document).on('click', '#timezone-toggle-btn', function() {
        showOriginalTimes = !showOriginalTimes;
        
        var $btn = $(this);
        if (showOriginalTimes) {
            $btn.addClass('active').text('Show Converted');
        } else {
            $btn.removeClass('active').text('Show Original');
        }
        
        // Re-render results if available
        if (originalResultsData) {
            var interfaceType = $('.graylog-search-wrap').length > 0 ? 'admin' : 'shortcode';
            var $container = interfaceType === 'admin' ? $('.graylog-search-wrap') : $('.graylog-search-shortcode');
            displayResults(originalResultsData, $container, interfaceType);
        }
    });
    
    // Get timezone label (short version)
    function getTimezoneLabel(tz) {
        var labels = {
            'UTC': 'UTC/GMT',
            'America/New_York': 'EST/EDT',
            'America/Chicago': 'CST/CDT',
            'America/Denver': 'MST/MDT',
            'America/Phoenix': 'MST',
            'America/Los_Angeles': 'PST/PDT',
            'America/Anchorage': 'AKST/AKDT',
            'Pacific/Honolulu': 'HST',
            'Asia/Kolkata': 'IST'
        };
        return labels[tz] || tz;
    }
    
    // Convert timestamp to target timezone
    function convertTimestamp(timestamp, targetTimezone) {
        if (!timestamp || showOriginalTimes || targetTimezone === 'UTC') {
            return null; // Return null to use original
        }
        
        try {
            var date = new Date(timestamp);
            
            // Format with Intl API
            var formatter = new Intl.DateTimeFormat('en-US', {
                timeZone: targetTimezone,
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            });
            
            var formatted = formatter.format(date);
            return formatted;
        } catch(e) {
            console.error('Timezone conversion error:', e);
            return null;
        }
    }
    
    // Apply timezone conversion to already-escaped text
    function applyTimezoneConversionToText(text, targetTimezone) {
        if (!text || targetTimezone === 'UTC') {
            return text;
        }
        
        // Define timestamp patterns (order matters - more specific first)
        var patterns = [
            // ISO8601 with timezone: 2025-10-10T14:30:00-05:00 or 2025-10-10T14:30:00Z
            {
                regex: /\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:[+-]\d{2}:\d{2}|Z)/g,
                parse: function(match) { return new Date(match); }
            },
            // ISO8601 basic: 2025-10-10T14:30:00
            {
                regex: /\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/g,
                parse: function(match) { return new Date(match + 'Z'); }
            },
            // Custom with timezone: 2025-10-10 14:30:00 UTC/EST/etc
            {
                regex: /\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}\s+[A-Z]{3,4}/g,
                parse: function(match) { return new Date(match); }
            },
            // MySQL/standard: 2025-10-10 14:30:00
            {
                regex: /\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}/g,
                parse: function(match) { return new Date(match + ' UTC'); }
            },
            // RFC2822: Wed, 10 Oct 2025 14:30:00 GMT
            {
                regex: /[A-Za-z]{3},\s+\d{1,2}\s+[A-Za-z]{3}\s+\d{4}\s+\d{2}:\d{2}:\d{2}\s+[A-Z]{3}/g,
                parse: function(match) { return new Date(match); }
            },
            // Apache/Nginx: 10/Oct/2025:14:30:00 +0000
            {
                regex: /\d{2}\/[A-Za-z]{3}\/\d{4}:\d{2}:\d{2}:\d{2}\s+[+-]\d{4}/g,
                parse: function(match) {
                    // Convert to standard format
                    var parts = match.match(/(\d{2})\/([A-Za-z]{3})\/(\d{4}):(\d{2}):(\d{2}):(\d{2})\s+([+-]\d{4})/);
                    if (parts) {
                        var monthMap = {Jan:0,Feb:1,Mar:2,Apr:3,May:4,Jun:5,Jul:6,Aug:7,Sep:8,Oct:9,Nov:10,Dec:11};
                        var date = new Date(Date.UTC(parts[3], monthMap[parts[2]], parts[1], parts[4], parts[5], parts[6]));
                        return date;
                    }
                    return null;
                }
            },
            // Windows Event Log: 10/10/2025 2:30:00 PM
            {
                regex: /\d{1,2}\/\d{1,2}\/\d{4}\s+\d{1,2}:\d{2}:\d{2}\s+(?:AM|PM)/gi,
                parse: function(match) { return new Date(match); }
            },
            // Unix timestamp (10 digits)
            {
                regex: /\b\d{10}\b/g,
                parse: function(match) {
                    var timestamp = parseInt(match) * 1000;
                    return new Date(timestamp);
                }
            }
        ];
        
        // Apply each pattern
        patterns.forEach(function(pattern) {
            text = text.replace(pattern.regex, function(match) {
                try {
                    var date = pattern.parse(match);
                    if (date && !isNaN(date.getTime())) {
                        var formatter = new Intl.DateTimeFormat('en-US', {
                            timeZone: targetTimezone,
                            year: 'numeric',
                            month: '2-digit',
                            day: '2-digit',
                            hour: '2-digit',
                            minute: '2-digit',
                            second: '2-digit',
                            hour12: false
                        });
                        var converted = formatter.format(date);
                        return '<span class="timestamp-converted" title="Original: ' + match + '">' + converted + '</span>';
                    }
                } catch(e) {
                    console.error('Error converting timestamp:', match, e);
                }
                return match;
            });
        });
        
        return text;
    }
    
    // Detect and convert timestamps in message text (kept for reference, use applyTimezoneConversionToText instead)
    function detectAndConvertTimestampsInMessage(messageText, targetTimezone) {
        var escaped = escapeHtml(messageText);
        return applyTimezoneConversionToText(escaped, targetTimezone);
    }
    
    // ==================== EXPORT FUNCTIONS ====================
    
    // Toggle export menu
    $(document).on('click', '.export-btn', function(e) {
        e.stopPropagation();
        var $menu = $(this).siblings('.export-menu');
        $('.export-menu').not($menu).hide();
        $menu.toggle();
    });
    
    // Close export menu when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.export-controls').length) {
            $('.export-menu').hide();
        }
    });
    
    // Get visible rows data for export
    function getVisibleRowsData() {
        var rows = [];
        $('.graylog-results-table tbody tr:visible').not('.details-row').each(function() {
            var $row = $(this);
            var rowData = $row.data('row-data');
            if (rowData) {
                rows.push(rowData);
            }
        });
        return rows;
    }
    
    // Export as CSV
    $(document).on('click', '.export-csv', function() {
        var rows = getVisibleRowsData();
        if (rows.length === 0) {
            showNotification('No visible rows to export', 'warning');
            return;
        }
        
        var csv = 'Timestamp,Source,Level,Message\n';
        rows.forEach(function(row) {
            var timestamp = (row.timestamp || '').replace(/"/g, '""');
            var source = (row.source || '').replace(/"/g, '""');
            var level = (row.level || '').replace(/"/g, '""');
            var message = (row.message || '').replace(/"/g, '""').replace(/\n/g, ' ');
            csv += '"' + timestamp + '","' + source + '","' + level + '","' + message + '"\n';
        });
        
        downloadFile(csv, 'graylog-export.csv', 'text/csv');
        $('.export-menu').hide();
        showNotification('Exported ' + rows.length + ' rows as CSV', 'success');
    });
    
    // Export as JSON
    $(document).on('click', '.export-json', function() {
        var rows = getVisibleRowsData();
        if (rows.length === 0) {
            showNotification('No visible rows to export', 'warning');
            return;
        }
        
        var json = JSON.stringify(rows, null, 2);
        downloadFile(json, 'graylog-export.json', 'application/json');
        $('.export-menu').hide();
        showNotification('Exported ' + rows.length + ' rows as JSON', 'success');
    });
    
    // Export as TXT
    $(document).on('click', '.export-txt', function() {
        var rows = getVisibleRowsData();
        if (rows.length === 0) {
            showNotification('No visible rows to export', 'warning');
            return;
        }
        
        var txt = '';
        rows.forEach(function(row) {
            txt += row.timestamp + ' | ' + row.source + ' | ' + row.level + ' | ' + row.message + '\n\n';
        });
        
        downloadFile(txt, 'graylog-export.txt', 'text/plain');
        $('.export-menu').hide();
        showNotification('Exported ' + rows.length + ' rows as Text', 'success');
    });
    
    // Copy to clipboard
    $(document).on('click', '.export-copy', function() {
        var rows = getVisibleRowsData();
        if (rows.length === 0) {
            showNotification('No visible rows to copy', 'warning');
            return;
        }
        
        var txt = '';
        rows.forEach(function(row) {
            txt += row.timestamp + ' | ' + row.source + ' | ' + row.level + ' | ' + row.message + '\n';
        });
        
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(txt).then(function() {
                showNotification('Copied ' + rows.length + ' rows to clipboard', 'success');
            }).catch(function() {
                showNotification('Failed to copy to clipboard', 'error');
            });
        } else {
            // Fallback for older browsers
            var $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(txt).select();
            try {
                document.execCommand('copy');
                showNotification('Copied ' + rows.length + ' rows to clipboard', 'success');
            } catch(e) {
                showNotification('Failed to copy to clipboard', 'error');
            }
            $temp.remove();
        }
        $('.export-menu').hide();
    });
    
    // Download file helper
    function downloadFile(content, filename, mimeType) {
        var blob = new Blob([content], {type: mimeType});
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
    
    // ==================== PARSE FUNCTIONS ====================
    
    // Toggle parse options
    $(document).on('change', '#parse-toggle', function() {
        parseEnabled = $(this).is(':checked');
        $('.parse-format-options').toggle(parseEnabled);
        
        // Re-render results if available
        if (originalResultsData) {
            var interfaceType = $('.graylog-search-wrap').length > 0 ? 'admin' : 'shortcode';
            var $container = interfaceType === 'admin' ? $('.graylog-search-wrap') : $('.graylog-search-shortcode');
            displayResults(originalResultsData, $container, interfaceType);
        }
    });
    
    // Update parse formats
    $(document).on('change', '.parse-format', function() {
        var format = $(this).val();
        parseFormats[format] = $(this).is(':checked');
        
        // Re-render if parse is enabled and results available
        if (parseEnabled && originalResultsData) {
            var interfaceType = $('.graylog-search-wrap').length > 0 ? 'admin' : 'shortcode';
            var $container = interfaceType === 'admin' ? $('.graylog-search-wrap') : $('.graylog-search-shortcode');
            displayResults(originalResultsData, $container, interfaceType);
        }
    });
    
    // Parse message text
    function parseMessageText(messageText) {
        if (!parseEnabled || !messageText) {
            return null;
        }
        
        var parsed = {};
        
        // Try JSON first
        if (parseFormats.json) {
            try {
                var jsonMatch = messageText.match(/\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\}/);
                if (jsonMatch) {
                    parsed = Object.assign(parsed, JSON.parse(jsonMatch[0]));
                }
            } catch(e) {}
        }
        
        // Try key=value pairs
        if (parseFormats.kv) {
            var kvRegex = /(\w+)=(?:"([^"]*)"|'([^']*)'|([^\s]+))/g;
            var match;
            while ((match = kvRegex.exec(messageText)) !== null) {
                var key = match[1];
                var value = match[2] || match[3] || match[4];
                parsed[key] = value;
            }
        }
        
        // Try CEF format
        if (parseFormats.cef && messageText.match(/^CEF:/)) {
            var cefParts = messageText.split('|');
            if (cefParts.length >= 7) {
                parsed['cef_version'] = cefParts[0].replace('CEF:', '');
                parsed['device_vendor'] = cefParts[1];
                parsed['device_product'] = cefParts[2];
                parsed['device_version'] = cefParts[3];
                parsed['signature_id'] = cefParts[4];
                parsed['name'] = cefParts[5];
                parsed['severity'] = cefParts[6];
                
                // Parse extensions (key=value pairs after pipe 7)
                if (cefParts.length > 7) {
                    var extensions = cefParts.slice(7).join('|');
                    var extRegex = /(\w+)=([^\s]+(?:\s+[^\w=]+)*)/g;
                    while ((match = extRegex.exec(extensions)) !== null) {
                        parsed[match[1]] = match[2].trim();
                    }
                }
            }
        }
        
        // Try LEEF format
        if (parseFormats.leef && messageText.match(/^LEEF:/)) {
            var leefParts = messageText.split('|');
            if (leefParts.length >= 5) {
                parsed['leef_version'] = leefParts[0].replace('LEEF:', '');
                parsed['vendor'] = leefParts[1];
                parsed['product'] = leefParts[2];
                parsed['version'] = leefParts[3];
                parsed['event_id'] = leefParts[4];
                
                // Parse attributes
                if (leefParts.length > 5) {
                    var attrs = leefParts.slice(5).join('|');
                    var delimiter = '\t'; // LEEF typically uses tab
                    attrs.split(delimiter).forEach(function(pair) {
                        var kv = pair.split('=');
                        if (kv.length === 2) {
                            parsed[kv[0].trim()] = kv[1].trim();
                        }
                    });
                }
            }
        }
        
        return Object.keys(parsed).length > 0 ? parsed : null;
    }
    
    // ==================== ROW ACTIONS ====================
    
    // Toggle row actions menu
    $(document).on('click', '.row-actions-btn', function(e) {
        e.stopPropagation();
        var $dropdown = $(this).siblings('.row-actions-dropdown');
        $('.row-actions-dropdown').not($dropdown).hide();
        $dropdown.toggle();
    });
    
    // Close row actions when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.row-actions-menu').length) {
            $('.row-actions-dropdown').hide();
        }
    });
    
    // Filter include (add as active filter)
    $(document).on('click', '.row-action-include', function() {
        var text = $(this).data('text');
        if (text) {
            addFilter(text);
            $(this).closest('.row-actions-dropdown').hide();
            showNotification('Added include filter: ' + text, 'success');
        }
    });
    
    // Filter exclude (add as NOT filter)
    $(document).on('click', '.row-action-exclude', function() {
        var text = $(this).data('text');
        if (text) {
            addFilter(text);
            $(this).closest('.row-actions-dropdown').hide();
            showNotification('Added exclude filter: ' + text, 'success');
        }
    });
    
    // Copy row
    $(document).on('click', '.row-action-copy', function() {
        var rowData = $(this).closest('tr').data('row-data');
        if (!rowData) return;
        
        var text = rowData.timestamp + ' | ' + rowData.source + ' | ' + rowData.level + ' | ' + rowData.message;
        
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function() {
                showNotification('Row copied to clipboard', 'success');
            });
        } else {
            var $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(text).select();
            try {
                document.execCommand('copy');
                showNotification('Row copied to clipboard', 'success');
            } catch(e) {}
            $temp.remove();
        }
        $(this).closest('.row-actions-dropdown').hide();
    });
    
    // Expand details
    $(document).on('click', '.row-action-details', function() {
        var $row = $(this).closest('tr');
        var $detailsRow = $row.next('.details-row');
        
        if ($detailsRow.length && $detailsRow.hasClass('visible')) {
            // Close details
            $detailsRow.removeClass('visible');
        } else {
            // Close any other open details
            $('.details-row').removeClass('visible');
            
            // Open this one
            if ($detailsRow.length) {
                $detailsRow.addClass('visible');
            }
        }
        
        $(this).closest('.row-actions-dropdown').hide();
    });
    
    // ========================================
    // Saved Searches, Recent Searches, Quick Filters
    // ========================================
    
    // Load saved searches
    function loadSavedSearches() {
        $.ajax({
            url: graylogSearch.ajaxUrl,
            type: 'POST',
            data: {
                action: 'graylog_get_saved_searches',
                nonce: graylogSearch.nonce
            },
            success: function(response) {
                if (response.success && response.data.searches) {
                    displaySavedSearches(response.data.searches);
                }
            }
        });
    }
    
    // Display saved searches
    function displaySavedSearches(searches) {
        var $list = $('#saved-searches-list');
        
        if (Object.keys(searches).length === 0) {
            $list.html('<p style="color: #666; font-size: 12px; margin: 0;">No saved searches yet</p>');
            return;
        }
        
        var html = '<div style="display: flex; flex-direction: column; gap: 5px;">';
        $.each(searches, function(name, data) {
            html += '<div class="saved-search-item" style="display: flex; justify-content: space-between; align-items: center; padding: 5px; background: white; border-radius: 3px; border: 1px solid #ddd;">';
            html += '<button class="button button-small load-saved-search" data-name="' + escapeHtml(name) + '" style="flex: 1; text-align: left; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">';
            html += '📁 ' + escapeHtml(name);
            html += '</button>';
            html += '<button class="button button-small button-link-delete delete-saved-search" data-name="' + escapeHtml(name) + '" style="color: #b32d2e; margin-left: 5px;" title="Delete">✕</button>';
            html += '</div>';
        });
        html += '</div>';
        
        $list.html(html);
    }
    
    // Load recent searches
    function loadRecentSearches() {
        $.ajax({
            url: graylogSearch.ajaxUrl,
            type: 'POST',
            data: {
                action: 'graylog_get_recent_searches',
                nonce: graylogSearch.nonce
            },
            success: function(response) {
                if (response.success && response.data.searches) {
                    displayRecentSearches(response.data.searches);
                }
            }
        });
    }
    
    // Display recent searches
    function displayRecentSearches(searches) {
        var $list = $('#recent-searches-list');
        
        if (searches.length === 0) {
            $list.html('<p style="color: #666; font-size: 12px; margin: 0;">No recent searches</p>');
            return;
        }
        
        var html = '<div style="display: flex; flex-direction: column; gap: 3px;">';
        $.each(searches.slice(0, 5), function(index, data) {
            var label = [];
            if (data.fqdn) label.push('Host: ' + data.fqdn.substring(0, 15) + (data.fqdn.length > 15 ? '...' : ''));
            if (data.search_terms) label.push('Terms: ' + data.search_terms.substring(0, 15) + (data.search_terms.length > 15 ? '...' : ''));
            if (label.length === 0) label.push('All logs');
            
            html += '<button class="button button-small load-recent-search" data-index="' + index + '" style="text-align: left; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 11px;">';
            html += '🕒 ' + label.join(', ');
            html += '</button>';
        });
        html += '</div>';
        
        $list.html(html);
    }
    
    // Load quick filters
    function loadQuickFilters() {
        $.ajax({
            url: graylogSearch.ajaxUrl,
            type: 'POST',
            data: {
                action: 'graylog_get_quick_filters',
                nonce: graylogSearch.nonce
            },
            success: function(response) {
                if (response.success && response.data.filters) {
                    // Quick filters are already displayed in HTML, just store the data
                    window.graylogQuickFilters = response.data.filters;
                }
            }
        });
    }
    
    // Save current search
    $(document).on('click', '#save-current-search-btn', function() {
        var searchName = prompt('Enter a name for this search:');
        if (!searchName) return;
        
        var formData = {
            action: 'graylog_save_search',
            nonce: graylogSearch.nonce,
            name: searchName,
            fqdn: $('#search_fqdn').val(),
            search_terms: $('#search_terms').val(),
            filter_out: $('#filter_out').val(),
            time_range: $('#time_range').val()
        };
        
        $.ajax({
            url: graylogSearch.ajaxUrl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert('Search saved successfully!');
                    displaySavedSearches(response.data.searches);
                } else {
                    alert('Error saving search: ' + response.data.message);
                }
            }
        });
    });
    
    // Load saved search
    $(document).on('click', '.load-saved-search', function() {
        var searchName = $(this).data('name');
        
        $.ajax({
            url: graylogSearch.ajaxUrl,
            type: 'POST',
            data: {
                action: 'graylog_get_saved_searches',
                nonce: graylogSearch.nonce
            },
            success: function(response) {
                if (response.success && response.data.searches[searchName]) {
                    var data = response.data.searches[searchName];
                    $('#search_fqdn').val(data.fqdn || '');
                    $('#search_terms').val(data.search_terms || '');
                    $('#filter_out').val(data.filter_out || '');
                    $('#time_range').val(data.time_range || 86400);
                    
                    // Automatically trigger search
                    $('#graylog-search-form').trigger('submit');
                }
            }
        });
    });
    
    // Delete saved search
    $(document).on('click', '.delete-saved-search', function(e) {
        e.stopPropagation();
        
        if (!confirm('Delete this saved search?')) return;
        
        var searchName = $(this).data('name');
        
        $.ajax({
            url: graylogSearch.ajaxUrl,
            type: 'POST',
            data: {
                action: 'graylog_delete_saved_search',
                nonce: graylogSearch.nonce,
                name: searchName
            },
            success: function(response) {
                if (response.success) {
                    displaySavedSearches(response.data.searches);
                } else {
                    alert('Error deleting search: ' + response.data.message);
                }
            }
        });
    });
    
    // Load recent search
    $(document).on('click', '.load-recent-search', function() {
        $.ajax({
            url: graylogSearch.ajaxUrl,
            type: 'POST',
            data: {
                action: 'graylog_get_recent_searches',
                nonce: graylogSearch.nonce
            },
            success: function(response) {
                if (response.success && response.data.searches) {
                    var index = parseInt($(this).data('index'));
                    var data = response.data.searches[index];
                    
                    if (data) {
                        $('#search_fqdn').val(data.fqdn || '');
                        $('#search_terms').val(data.search_terms || '');
                        $('#filter_out').val(data.filter_out || '');
                        $('#time_range').val(data.time_range || 86400);
                        
                        // Automatically trigger search
                        $('#graylog-search-form').trigger('submit');
                    }
                }
            }
        });
    });
    
    // Apply quick filter
    $(document).on('click', '.quick-filter-btn', function() {
        var filterName = $(this).data('name');
        
        if (!window.graylogQuickFilters) return;
        
        var filter = window.graylogQuickFilters.find(function(f) {
            return f.name === filterName;
        });
        
        if (filter) {
            $('#search_fqdn').val(filter.data.fqdn || '');
            $('#search_terms').val(filter.data.search_terms || '');
            $('#filter_out').val(filter.data.filter_out || '');
            $('#time_range').val(filter.data.time_range || 86400);
            
            // Automatically trigger search
            $('#graylog-search-form').trigger('submit');
        }
    });
    
    // Update recent searches after a search completes
    var originalPerformSearch = performSearch;
    performSearch = function($form, interfaceType) {
        var promise = originalPerformSearch.call(this, $form, interfaceType);
        
        // Reload recent searches after a successful search
        setTimeout(function() {
            loadRecentSearches();
        }, 1000);
        
        return promise;
    };
    
    // Helper function to escape HTML
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    // ========================================
    // Dark Mode Toggle
    // ========================================
    
    // Initialize dark mode
    initDarkMode();
    
    function initDarkMode() {
        // Check if user has a saved preference
        var darkMode = localStorage.getItem('graylog-dark-mode');
        
        // If no preference, check system preference
        if (darkMode === null) {
            darkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'enabled' : 'disabled';
        }
        
        // Apply dark mode if enabled
        if (darkMode === 'enabled') {
            $('body').addClass('graylog-dark-mode');
        }
        
        // Add toggle button
        var toggleHtml = '<div class="graylog-dark-mode-toggle" title="Toggle dark mode">' +
                         '<span class="dark-mode-icon">' + (darkMode === 'enabled' ? '☀️' : '🌙') + '</span>' +
                         '</div>';
        
        // Only add if not already present
        if ($('.graylog-dark-mode-toggle').length === 0) {
            $('body').append(toggleHtml);
        }
    }
    
    // Handle dark mode toggle click
    $(document).on('click', '.graylog-dark-mode-toggle', function() {
        var $body = $('body');
        var $icon = $(this).find('.dark-mode-icon');
        
        if ($body.hasClass('graylog-dark-mode')) {
            // Disable dark mode
            $body.removeClass('graylog-dark-mode');
            localStorage.setItem('graylog-dark-mode', 'disabled');
            $icon.text('🌙');
            
            // Save to server
            saveDarkModePreference(false);
        } else {
            // Enable dark mode
            $body.addClass('graylog-dark-mode');
            localStorage.setItem('graylog-dark-mode', 'enabled');
            $icon.text('☀️');
            
            // Save to server
            saveDarkModePreference(true);
        }
    });
    
    // Save dark mode preference to server
    function saveDarkModePreference(enabled) {
        $.ajax({
            url: graylogSearch.ajaxUrl,
            type: 'POST',
            data: {
                action: 'graylog_save_dark_mode',
                nonce: graylogSearch.nonce,
                enabled: enabled ? '1' : '0'
            }
        });
    }
});

