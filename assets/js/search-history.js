/**
 * Search History Management
 * View, filter, favorite, and re-run past searches
 */

jQuery(document).ready(function($) {
    
    var currentFilters = {
        date_from: '',
        date_to: '',
        favorites_only: false,
        search: '',
        offset: 0,
        limit: 50
    };
    
    // Initialize search history
    function initSearchHistory() {
        // Add search history button to main search page
        if ($('#search_fqdn').length > 0 && $('#view-search-history').length === 0) {
            var buttonHtml = '<button type="button" class="button button-large" id="view-search-history">';
            buttonHtml += '<span class="dashicons dashicons-backup"></span> Search History';
            buttonHtml += '</button>';
            
            $('#open-query-builder').after(buttonHtml);
        }
        
        // Load statistics if on history page
        if ($('#search-history-container').length > 0) {
            loadSearchHistory();
            loadStatistics();
        }
    }
    
    // Open search history modal
    $(document).on('click', '#view-search-history', function() {
        showSearchHistoryModal();
    });
    
    // Show search history modal
    function showSearchHistoryModal() {
        var html = '<div class="search-history-overlay"></div>';
        html += '<div class="search-history-modal">';
        html += '<div class="search-history-header">';
        html += '<h2>📚 Search History</h2>';
        html += '<button class="search-history-close">✕</button>';
        html += '</div>';
        html += '<div class="search-history-body">';
        
        // Statistics
        html += '<div class="search-statistics" id="search-statistics-container">';
        html += '<div class="stat-loading">Loading statistics...</div>';
        html += '</div>';
        
        // Filters
        html += '<div class="search-history-filters">';
        html += '<input type="date" id="filter-date-from" placeholder="From date">';
        html += '<input type="date" id="filter-date-to" placeholder="To date">';
        html += '<input type="text" id="filter-search" placeholder="Search in history...">';
        html += '<label><input type="checkbox" id="filter-favorites"> Favorites only</label>';
        html += '<button class="button" id="apply-history-filters">Apply Filters</button>';
        html += '<button class="button" id="clear-history-filters">Clear</button>';
        html += '</div>';
        
        // History list
        html += '<div class="search-history-list" id="search-history-list">';
        html += '<div class="history-loading">Loading history...</div>';
        html += '</div>';
        
        // Pagination
        html += '<div class="search-history-pagination" id="history-pagination"></div>';
        
        html += '</div>';
        html += '</div>';
        
        $('body').append(html);
        
        // Load data
        loadSearchHistory();
        loadStatistics();
    }
    
    // Load search history
    function loadSearchHistory() {
        $.ajax({
            url: graylogSearch.ajaxUrl,
            type: 'POST',
            data: {
                action: 'graylog_get_search_history',
                nonce: graylogSearch.nonce,
                date_from: currentFilters.date_from,
                date_to: currentFilters.date_to,
                favorites_only: currentFilters.favorites_only,
                search: currentFilters.search,
                offset: currentFilters.offset,
                limit: currentFilters.limit
            },
            success: function(response) {
                if (response.success) {
                    displaySearchHistory(response.data.history, response.data.total, response.data.has_more);
                } else {
                    showError('Failed to load search history');
                }
            },
            error: function() {
                showError('Error loading search history');
            }
        });
    }
    
    // Display search history
    function displaySearchHistory(history, total, hasMore) {
        var $container = $('#search-history-list');
        
        if (history.length === 0) {
            $container.html('<div class="history-empty">No searches found. Try adjusting your filters.</div>');
            $('#history-pagination').html('');
            return;
        }
        
        var html = '<div class="history-items">';
        
        history.forEach(function(item) {
            var params = item.search_params;
            var date = new Date(item.search_date);
            var dateStr = date.toLocaleString();
            
            html += '<div class="history-item" data-id="' + item.id + '">';
            html += '<div class="history-item-header">';
            html += '<button class="history-favorite ' + (item.is_favorite == 1 ? 'is-favorite' : '') + '" data-id="' + item.id + '" title="' + (item.is_favorite == 1 ? 'Remove from favorites' : 'Add to favorites') + '">';
            html += '<span class="dashicons dashicons-star-' + (item.is_favorite == 1 ? 'filled' : 'empty') + '"></span>';
            html += '</button>';
            html += '<div class="history-date">' + escapeHtml(dateStr) + '</div>';
            html += '<div class="history-actions">';
            html += '<button class="button button-small history-rerun" data-id="' + item.id + '" title="Re-run this search"><span class="dashicons dashicons-controls-play"></span> Re-run</button>';
            html += '<button class="button button-small history-delete" data-id="' + item.id + '" title="Delete"><span class="dashicons dashicons-trash"></span></button>';
            html += '</div>';
            html += '</div>';
            
            html += '<div class="history-item-content">';
            
            // Query string
            if (item.query_string) {
                html += '<div class="history-query"><strong>Query:</strong> <code>' + escapeHtml(item.query_string) + '</code></div>';
            }
            
            // Parameters
            html += '<div class="history-params">';
            if (params.fqdn) {
                html += '<span class="history-param"><strong>Hostname:</strong> ' + escapeHtml(params.fqdn) + '</span>';
            }
            if (params.search_terms) {
                html += '<span class="history-param"><strong>Terms:</strong> ' + escapeHtml(params.search_terms) + '</span>';
            }
            if (params.filter_out) {
                html += '<span class="history-param"><strong>Filter Out:</strong> ' + escapeHtml(params.filter_out) + '</span>';
            }
            if (params.time_range) {
                html += '<span class="history-param"><strong>Time Range:</strong> ' + formatTimeRange(params.time_range) + '</span>';
            }
            html += '</div>';
            
            // Stats
            html += '<div class="history-stats">';
            html += '<span class="history-stat"><strong>Results:</strong> ' + item.result_count + '</span>';
            if (item.execution_time > 0) {
                html += '<span class="history-stat"><strong>Time:</strong> ' + item.execution_time.toFixed(2) + 's</span>';
            }
            html += '</div>';
            
            // Notes
            if (item.notes) {
                html += '<div class="history-notes"><strong>Notes:</strong> ' + escapeHtml(item.notes) + '</div>';
            } else {
                html += '<div class="history-add-note"><button class="button-link add-note-btn" data-id="' + item.id + '">+ Add note</button></div>';
            }
            
            html += '</div>';
            html += '</div>';
        });
        
        html += '</div>';
        
        $container.html(html);
        
        // Pagination
        renderPagination(total, hasMore);
    }
    
    // Render pagination
    function renderPagination(total, hasMore) {
        var $container = $('#history-pagination');
        var currentPage = Math.floor(currentFilters.offset / currentFilters.limit) + 1;
        var totalPages = Math.ceil(total / currentFilters.limit);
        
        var html = '<div class="pagination-info">Showing ' + (currentFilters.offset + 1) + ' - ' + Math.min(currentFilters.offset + currentFilters.limit, total) + ' of ' + total + '</div>';
        html += '<div class="pagination-buttons">';
        
        if (currentFilters.offset > 0) {
            html += '<button class="button history-page-prev">« Previous</button>';
        }
        
        html += '<span class="pagination-current">Page ' + currentPage + ' of ' + totalPages + '</span>';
        
        if (hasMore) {
            html += '<button class="button history-page-next">Next »</button>';
        }
        
        html += '</div>';
        
        $container.html(html);
    }
    
    // Load statistics
    function loadStatistics() {
        $.ajax({
            url: graylogSearch.ajaxUrl,
            type: 'POST',
            data: {
                action: 'graylog_get_search_statistics',
                nonce: graylogSearch.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayStatistics(response.data.statistics);
                }
            }
        });
    }
    
    // Display statistics
    function displayStatistics(stats) {
        var html = '<div class="stat-grid">';
        html += '<div class="stat-card"><div class="stat-number">' + stats.total_searches + '</div><div class="stat-label">Total Searches</div></div>';
        html += '<div class="stat-card"><div class="stat-number">' + stats.searches_today + '</div><div class="stat-label">Today</div></div>';
        html += '<div class="stat-card"><div class="stat-number">' + stats.searches_week + '</div><div class="stat-label">This Week</div></div>';
        html += '<div class="stat-card"><div class="stat-number">' + stats.favorites_count + '</div><div class="stat-label">Favorites</div></div>';
        if (stats.avg_execution_time) {
            html += '<div class="stat-card"><div class="stat-number">' + parseFloat(stats.avg_execution_time).toFixed(2) + 's</div><div class="stat-label">Avg Time</div></div>';
        }
        html += '</div>';
        
        $('#search-statistics-container').html(html);
    }
    
    // Apply filters
    $(document).on('click', '#apply-history-filters', function() {
        currentFilters.date_from = $('#filter-date-from').val();
        currentFilters.date_to = $('#filter-date-to').val();
        currentFilters.favorites_only = $('#filter-favorites').is(':checked');
        currentFilters.search = $('#filter-search').val();
        currentFilters.offset = 0;
        
        loadSearchHistory();
    });
    
    // Clear filters
    $(document).on('click', '#clear-history-filters', function() {
        currentFilters = {
            date_from: '',
            date_to: '',
            favorites_only: false,
            search: '',
            offset: 0,
            limit: 50
        };
        
        $('#filter-date-from').val('');
        $('#filter-date-to').val('');
        $('#filter-favorites').prop('checked', false);
        $('#filter-search').val('');
        
        loadSearchHistory();
    });
    
    // Pagination handlers
    $(document).on('click', '.history-page-prev', function() {
        currentFilters.offset = Math.max(0, currentFilters.offset - currentFilters.limit);
        loadSearchHistory();
    });
    
    $(document).on('click', '.history-page-next', function() {
        currentFilters.offset += currentFilters.limit;
        loadSearchHistory();
    });
    
    // Toggle favorite
    $(document).on('click', '.history-favorite', function() {
        var $btn = $(this);
        var id = $btn.data('id');
        
        $.ajax({
            url: graylogSearch.ajaxUrl,
            type: 'POST',
            data: {
                action: 'graylog_toggle_favorite',
                nonce: graylogSearch.nonce,
                history_id: id
            },
            success: function(response) {
                if (response.success) {
                    var isFavorite = response.data.is_favorite;
                    $btn.toggleClass('is-favorite', isFavorite);
                    $btn.find('.dashicons').toggleClass('dashicons-star-filled', isFavorite).toggleClass('dashicons-star-empty', !isFavorite);
                    $btn.attr('title', isFavorite ? 'Remove from favorites' : 'Add to favorites');
                    
                    showNotification(response.data.message, 'success');
                }
            }
        });
    });
    
    // Re-run search
    $(document).on('click', '.history-rerun', function() {
        var $item = $(this).closest('.history-item');
        var id = $(this).data('id');
        
        // Get search params from the item
        var historyData = null;
        $('.search-history-list .history-item').each(function() {
            if ($(this).data('id') == id) {
                // Find this in the history data - we need to access the original data
                // For now, let's extract from the displayed params
                historyData = extractParamsFromItem($(this));
            }
        });
        
        if (historyData) {
            // Close modal
            $('.search-history-modal, .search-history-overlay').remove();
            
            // Fill search form
            $('#search_fqdn').val(historyData.fqdn || '');
            $('#search_terms').val(historyData.search_terms || '');
            $('#filter_out').val(historyData.filter_out || '');
            $('#time_range').val(historyData.time_range || 60);
            
            // Trigger search
            $('#search_button').click();
            
            showNotification('Search re-run!', 'success');
        }
    });
    
    // Extract params from displayed item
    function extractParamsFromItem($item) {
        var params = {};
        
        $item.find('.history-param').each(function() {
            var text = $(this).text();
            if (text.includes('Hostname:')) {
                params.fqdn = text.replace('Hostname:', '').trim();
            } else if (text.includes('Terms:')) {
                params.search_terms = text.replace('Terms:', '').trim();
            } else if (text.includes('Filter Out:')) {
                params.filter_out = text.replace('Filter Out:', '').trim();
            } else if (text.includes('Time Range:')) {
                var timeText = text.replace('Time Range:', '').trim();
                params.time_range = parseTimeRange(timeText);
            }
        });
        
        return params;
    }
    
    // Parse time range text back to minutes
    function parseTimeRange(text) {
        if (text.includes('hour')) {
            var hours = parseInt(text);
            return hours * 60;
        } else if (text.includes('day')) {
            var days = parseInt(text);
            return days * 24 * 60;
        } else if (text.includes('week')) {
            return 7 * 24 * 60;
        }
        return 60; // default
    }
    
    // Delete search
    $(document).on('click', '.history-delete', function() {
        if (!confirm('Delete this search from history?')) return;
        
        var id = $(this).data('id');
        
        $.ajax({
            url: graylogSearch.ajaxUrl,
            type: 'POST',
            data: {
                action: 'graylog_delete_search_history',
                nonce: graylogSearch.nonce,
                history_id: id
            },
            success: function(response) {
                if (response.success) {
                    showNotification('Search deleted', 'success');
                    loadSearchHistory();
                    loadStatistics();
                }
            }
        });
    });
    
    // Add note
    $(document).on('click', '.add-note-btn', function() {
        var id = $(this).data('id');
        var note = prompt('Add a note for this search:');
        
        if (note) {
            $.ajax({
                url: graylogSearch.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'graylog_add_search_note',
                    nonce: graylogSearch.nonce,
                    history_id: id,
                    note: note
                },
                success: function(response) {
                    if (response.success) {
                        showNotification('Note saved', 'success');
                        loadSearchHistory();
                    }
                }
            });
        }
    });
    
    // Close modal
    $(document).on('click', '.search-history-close, .search-history-overlay', function(e) {
        if (e.target === this) {
            $('.search-history-modal, .search-history-overlay').remove();
        }
    });
    
    // Helper functions
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
    
    function formatTimeRange(minutes) {
        if (minutes < 60) {
            return minutes + ' minutes';
        } else if (minutes < 1440) {
            return (minutes / 60) + ' hour(s)';
        } else if (minutes < 10080) {
            return (minutes / 1440) + ' day(s)';
        } else {
            return (minutes / 10080) + ' week(s)';
        }
    }
    
    function showError(message) {
        $('#search-history-list').html('<div class="history-error">' + message + '</div>');
    }
    
    function showNotification(message, type) {
        var color = type === 'success' ? '#46b450' : (type === 'error' ? '#dc3232' : '#0073aa');
        var $notif = $('<div class="history-notification">' + message + '</div>');
        $notif.css({
            'position': 'fixed',
            'top': '20px',
            'right': '20px',
            'background': color,
            'color': 'white',
            'padding': '15px 20px',
            'border-radius': '5px',
            'box-shadow': '0 2px 10px rgba(0,0,0,0.2)',
            'z-index': '100002',
            'animation': 'slideIn 0.3s'
        });
        
        $('body').append($notif);
        
        setTimeout(function() {
            $notif.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    // Initialize on page load
    initSearchHistory();
});

