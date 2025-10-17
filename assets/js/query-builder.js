/**
 * Visual Query Builder
 * Drag-and-drop interface for building complex Graylog queries
 */

jQuery(document).ready(function($) {
    
    // Query builder state
    var fields = {};
    var operators = {};
    var queryStructure = {
        operator: 'AND',
        groups: []
    };
    var nextGroupId = 1;
    var nextConditionId = 1;
    
    // Initialize query builder
    function initQueryBuilder() {
        // Load fields and operators
        loadFields();
        loadOperators();
        
        // Create a hidden button that can be triggered programmatically
        // (needed for the Query Builder tab functionality)
        if ($('#open-query-builder').length === 0) {
            $('<button type="button" id="open-query-builder" style="display:none;"></button>').appendTo('body');
        }
        
        // Make showQueryBuilder globally accessible
        window.graylogShowQueryBuilder = showQueryBuilder;
    }
    
    // Load available fields
    function loadFields() {
        $.ajax({
            url: graylogSearch.ajaxUrl,
            type: 'POST',
            data: {
                action: 'graylog_get_fields',
                nonce: graylogSearch.nonce
            },
            success: function(response) {
                if (response.success) {
                    fields = response.data.fields;
                }
            }
        });
    }
    
    // Load operators
    function loadOperators() {
        $.ajax({
            url: graylogSearch.ajaxUrl,
            type: 'POST',
            data: {
                action: 'graylog_get_operators',
                nonce: graylogSearch.nonce
            },
            success: function(response) {
                if (response.success) {
                    operators = response.data.operators;
                }
            }
        });
    }
    
    // Open query builder modal
    $(document).on('click', '#open-query-builder', function() {
        showQueryBuilder();
    });
    
    // Show query builder modal
    function showQueryBuilder() {
        var html = '<div class="query-builder-overlay"></div>';
        html += '<div class="query-builder-modal">';
        html += '<div class="query-builder-header">';
        html += '<h2>🔧 Visual Query Builder</h2>';
        html += '<button class="query-builder-close">✕</button>';
        html += '</div>';
        html += '<div class="query-builder-body">';
        
        // Top-level operator
        html += '<div class="query-builder-top-operator">';
        html += '<label>Combine groups with:</label>';
        html += '<select id="query-top-operator">';
        html += '<option value="AND">AND (all must match)</option>';
        html += '<option value="OR">OR (any can match)</option>';
        html += '</select>';
        html += '</div>';
        
        // Groups container
        html += '<div id="query-groups-container"></div>';
        
        // Add group button
        html += '<div class="query-builder-actions">';
        html += '<button class="button" id="add-query-group"><span class="dashicons dashicons-plus-alt"></span> Add Group</button>';
        html += '</div>';
        
        // Query preview
        html += '<div class="query-preview-section">';
        html += '<h3>Query Preview (Lucene Syntax)</h3>';
        html += '<div id="query-preview" class="query-preview-box">No conditions added yet</div>';
        html += '<button class="button button-small" id="refresh-preview">Refresh Preview</button>';
        html += '</div>';
        
        // Bottom actions
        html += '<div class="query-builder-footer">';
        html += '<button class="button button-primary button-large" id="use-visual-query">Use This Query</button>';
        html += '<button class="button button-large" id="save-query-template">Save as Template</button>';
        html += '<button class="button button-large" id="load-query-template">Load Template</button>';
        html += '<button class="button button-secondary button-large query-builder-close">Cancel</button>';
        html += '</div>';
        
        html += '</div>';
        html += '</div>';
        
        $('body').append(html);
        
        // Initialize with one empty group
        if (queryStructure.groups.length === 0) {
            addQueryGroup();
        } else {
            // Render existing structure
            renderQueryStructure();
        }
        
        updateQueryPreview();
    }
    
    // Add query group
    function addQueryGroup() {
        var groupId = 'group-' + (nextGroupId++);
        
        var group = {
            id: groupId,
            operator: 'AND',
            conditions: []
        };
        
        queryStructure.groups.push(group);
        renderQueryGroup(group);
        updateQueryPreview();
    }
    
    // Render query group
    function renderQueryGroup(group) {
        var html = '<div class="query-group" data-group-id="' + group.id + '">';
        html += '<div class="query-group-header">';
        html += '<span class="query-group-title">Group ' + queryStructure.groups.length + '</span>';
        html += '<select class="query-group-operator" data-group-id="' + group.id + '">';
        html += '<option value="AND"' + (group.operator === 'AND' ? ' selected' : '') + '>AND</option>';
        html += '<option value="OR"' + (group.operator === 'OR' ? ' selected' : '') + '>OR</option>';
        html += '</select>';
        html += '<button class="button button-small remove-query-group" data-group-id="' + group.id + '">✕ Remove Group</button>';
        html += '</div>';
        html += '<div class="query-conditions" data-group-id="' + group.id + '">';
        
        if (group.conditions.length === 0) {
            html += '<p class="query-empty-state">No conditions in this group</p>';
        } else {
            group.conditions.forEach(function(condition) {
                html += renderCondition(group.id, condition);
            });
        }
        
        html += '</div>';
        html += '<button class="button button-small add-condition" data-group-id="' + group.id + '"><span class="dashicons dashicons-plus"></span> Add Condition</button>';
        html += '</div>';
        
        $('#query-groups-container').append(html);
    }
    
    // Render condition
    function renderCondition(groupId, condition) {
        var condId = condition.id || 'cond-' + (nextConditionId++);
        condition.id = condId;
        
        var html = '<div class="query-condition" data-condition-id="' + condId + '" data-group-id="' + groupId + '">';
        html += '<select class="condition-field" data-condition-id="' + condId + '">';
        html += '<option value="">Select field...</option>';
        $.each(fields, function(key, field) {
            html += '<option value="' + field.name + '"' + (condition.field === field.name ? ' selected' : '') + '>' + field.display_name + '</option>';
        });
        html += '</select>';
        
        html += '<select class="condition-operator" data-condition-id="' + condId + '">';
        html += '<option value="">Select operator...</option>';
        if (condition.field && fields[condition.field]) {
            var fieldType = fields[condition.field].type;
            $.each(operators, function(key, op) {
                if (op.types.indexOf(fieldType) !== -1 || op.types.indexOf('string') !== -1) {
                    html += '<option value="' + key + '"' + (condition.operator === key ? ' selected' : '') + '>' + op.label + '</option>';
                }
            });
        }
        html += '</select>';
        
        html += '<input type="text" class="condition-value" data-condition-id="' + condId + '" placeholder="Value" value="' + (condition.value || '') + '">';
        html += '<button class="button button-small remove-condition" data-condition-id="' + condId + '" data-group-id="' + groupId + '">✕</button>';
        html += '</div>';
        
        return html;
    }
    
    // Render entire query structure
    function renderQueryStructure() {
        $('#query-groups-container').empty();
        $('#query-top-operator').val(queryStructure.operator);
        
        queryStructure.groups.forEach(function(group) {
            renderQueryGroup(group);
        });
    }
    
    // Add query group handler
    $(document).on('click', '#add-query-group', function() {
        addQueryGroup();
    });
    
    // Remove query group
    $(document).on('click', '.remove-query-group', function() {
        var groupId = $(this).data('group-id');
        
        // Remove from structure
        queryStructure.groups = queryStructure.groups.filter(function(g) {
            return g.id !== groupId;
        });
        
        // Remove from DOM
        $('.query-group[data-group-id="' + groupId + '"]').fadeOut(function() {
            $(this).remove();
            updateQueryPreview();
        });
    });
    
    // Add condition
    $(document).on('click', '.add-condition', function() {
        var groupId = $(this).data('group-id');
        var group = queryStructure.groups.find(function(g) {
            return g.id === groupId;
        });
        
        if (group) {
            var condition = {
                id: 'cond-' + (nextConditionId++),
                field: '',
                operator: '',
                value: ''
            };
            
            group.conditions.push(condition);
            
            // Remove empty state
            $('.query-conditions[data-group-id="' + groupId + '"] .query-empty-state').remove();
            
            // Add condition to DOM
            var conditionHtml = renderCondition(groupId, condition);
            $('.query-conditions[data-group-id="' + groupId + '"]').append(conditionHtml);
            
            updateQueryPreview();
        }
    });
    
    // Remove condition
    $(document).on('click', '.remove-condition', function() {
        var conditionId = $(this).data('condition-id');
        var groupId = $(this).data('group-id');
        
        var group = queryStructure.groups.find(function(g) {
            return g.id === groupId;
        });
        
        if (group) {
            group.conditions = group.conditions.filter(function(c) {
                return c.id !== conditionId;
            });
            
            $('.query-condition[data-condition-id="' + conditionId + '"]').fadeOut(function() {
                $(this).remove();
                
                // Show empty state if no conditions left
                if (group.conditions.length === 0) {
                    $('.query-conditions[data-group-id="' + groupId + '"]').html('<p class="query-empty-state">No conditions in this group</p>');
                }
                
                updateQueryPreview();
            });
        }
    });
    
    // Update condition field
    $(document).on('change', '.condition-field', function() {
        var conditionId = $(this).data('condition-id');
        var fieldName = $(this).val();
        
        // Find and update condition
        queryStructure.groups.forEach(function(group) {
            group.conditions.forEach(function(cond) {
                if (cond.id === conditionId) {
                    cond.field = fieldName;
                    cond.operator = ''; // Reset operator when field changes
                    
                    // Update operator dropdown
                    var $operatorSelect = $('.condition-operator[data-condition-id="' + conditionId + '"]');
                    $operatorSelect.empty();
                    $operatorSelect.append('<option value="">Select operator...</option>');
                    
                    if (fieldName && fields[fieldName]) {
                        var fieldType = fields[fieldName].type;
                        $.each(operators, function(key, op) {
                            if (op.types.indexOf(fieldType) !== -1) {
                                $operatorSelect.append('<option value="' + key + '">' + op.label + '</option>');
                            }
                        });
                    }
                }
            });
        });
        
        updateQueryPreview();
    });
    
    // Update condition operator
    $(document).on('change', '.condition-operator', function() {
        var conditionId = $(this).data('condition-id');
        var operatorValue = $(this).val();
        
        // Find and update condition
        queryStructure.groups.forEach(function(group) {
            group.conditions.forEach(function(cond) {
                if (cond.id === conditionId) {
                    cond.operator = operatorValue;
                }
            });
        });
        
        updateQueryPreview();
    });
    
    // Update condition value
    $(document).on('input', '.condition-value', function() {
        var conditionId = $(this).data('condition-id');
        var value = $(this).val();
        
        // Find and update condition
        queryStructure.groups.forEach(function(group) {
            group.conditions.forEach(function(cond) {
                if (cond.id === conditionId) {
                    cond.value = value;
                }
            });
        });
        
        updateQueryPreview();
    });
    
    // Update group operator
    $(document).on('change', '.query-group-operator', function() {
        var groupId = $(this).data('group-id');
        var operator = $(this).val();
        
        var group = queryStructure.groups.find(function(g) {
            return g.id === groupId;
        });
        
        if (group) {
            group.operator = operator;
            updateQueryPreview();
        }
    });
    
    // Update top-level operator
    $(document).on('change', '#query-top-operator', function() {
        queryStructure.operator = $(this).val();
        updateQueryPreview();
    });
    
    // Update query preview
    function updateQueryPreview() {
        // Build query via AJAX
        $.ajax({
            url: graylogSearch.ajaxUrl,
            type: 'POST',
            data: {
                action: 'graylog_build_query',
                nonce: graylogSearch.nonce,
                query_structure: JSON.stringify(queryStructure)
            },
            success: function(response) {
                if (response.success && response.data.query) {
                    $('#query-preview').html('<code>' + escapeHtml(response.data.query) + '</code>');
                } else {
                    $('#query-preview').html('<em>No valid query yet</em>');
                }
            }
        });
    }
    
    // Refresh preview button
    $(document).on('click', '#refresh-preview', function() {
        updateQueryPreview();
    });
    
    // Use visual query
    $(document).on('click', '#use-visual-query', function() {
        $.ajax({
            url: graylogSearch.ajaxUrl,
            type: 'POST',
            data: {
                action: 'graylog_build_query',
                nonce: graylogSearch.nonce,
                query_structure: JSON.stringify(queryStructure)
            },
            success: function(response) {
                if (response.success && response.data.query) {
                    // Put query in search terms field
                    $('#search_terms').val(response.data.query);
                    
                    // Clear other fields (query is complete)
                    $('#search_fqdn').val('');
                    $('#filter_out').val('');
                    
                    // Enable regex mode
                    $('#regex-mode-toggle').prop('checked', true).trigger('change');
                    
                    // Close modal
                    $('.query-builder-modal, .query-builder-overlay').remove();
                    
                    showNotification('Visual query applied! Ready to search.', 'success');
                } else {
                    alert('Error building query. Please check your conditions.');
                }
            }
        });
    });
    
    // Save as template
    $(document).on('click', '#save-query-template', function() {
        var name = prompt('Enter a name for this query template:');
        if (!name) return;
        
        var description = prompt('Enter a description (optional):') || '';
        
        $.ajax({
            url: graylogSearch.ajaxUrl,
            type: 'POST',
            data: {
                action: 'graylog_save_query_template',
                nonce: graylogSearch.nonce,
                name: name,
                description: description,
                query_structure: JSON.stringify(queryStructure)
            },
            success: function(response) {
                if (response.success) {
                    showNotification('Query template saved!', 'success');
                } else {
                    alert('Error: ' + response.data.message);
                }
            }
        });
    });
    
    // Load template
    $(document).on('click', '#load-query-template', function() {
        $.ajax({
            url: graylogSearch.ajaxUrl,
            type: 'POST',
            data: {
                action: 'graylog_get_query_templates',
                nonce: graylogSearch.nonce
            },
            success: function(response) {
                if (response.success) {
                    showTemplateSelector(response.data.templates);
                }
            }
        });
    });
    
    // Show template selector
    function showTemplateSelector(templates) {
        if (Object.keys(templates).length === 0) {
            alert('No saved templates yet. Build a query and save it as a template!');
            return;
        }
        
        var html = '<div class="template-selector-overlay"></div>';
        html += '<div class="template-selector-modal">';
        html += '<h3>Load Query Template</h3>';
        html += '<div class="template-list">';
        
        $.each(templates, function(name, template) {
            html += '<div class="template-item">';
            html += '<div class="template-name">' + escapeHtml(name) + '</div>';
            if (template.description) {
                html += '<div class="template-desc">' + escapeHtml(template.description) + '</div>';
            }
            html += '<button class="button button-small load-template-btn" data-name="' + escapeHtml(name) + '">Load</button>';
            html += '<button class="button button-small button-link-delete delete-template-btn" data-name="' + escapeHtml(name) + '">Delete</button>';
            html += '</div>';
        });
        
        html += '</div>';
        html += '<button class="button close-template-selector">Cancel</button>';
        html += '</div>';
        
        $('body').append(html);
        
        // Store templates in data attribute for access
        $('.template-selector-modal').data('templates', templates);
    }
    
    // Load template button
    $(document).on('click', '.load-template-btn', function() {
        var name = $(this).data('name');
        var templates = $('.template-selector-modal').data('templates');
        
        if (templates[name]) {
            queryStructure = templates[name].structure;
            nextGroupId = queryStructure.groups.length + 1;
            
            // Re-render query builder
            renderQueryStructure();
            updateQueryPreview();
            
            $('.template-selector-modal, .template-selector-overlay').remove();
            showNotification('Template loaded!', 'success');
        }
    });
    
    // Delete template button
    $(document).on('click', '.delete-template-btn', function() {
        if (!confirm('Delete this template?')) return;
        
        var name = $(this).data('name');
        
        $.ajax({
            url: graylogSearch.ajaxUrl,
            type: 'POST',
            data: {
                action: 'graylog_delete_query_template',
                nonce: graylogSearch.nonce,
                name: name
            },
            success: function(response) {
                if (response.success) {
                    $('.template-selector-modal, .template-selector-overlay').remove();
                    showNotification('Template deleted!', 'success');
                }
            }
        });
    });
    
    // Close modals
    $(document).on('click', '.query-builder-close, .query-builder-overlay, .close-template-selector, .template-selector-overlay', function(e) {
        if (e.target === this) {
            $('.query-builder-modal, .query-builder-overlay, .template-selector-modal, .template-selector-overlay').remove();
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
    
    function showNotification(message, type) {
        var color = type === 'success' ? '#46b450' : (type === 'error' ? '#dc3232' : '#0073aa');
        var $notif = $('<div class="query-builder-notification">' + message + '</div>');
        $notif.css({
            'position': 'fixed',
            'top': '20px',
            'right': '20px',
            'background': color,
            'color': 'white',
            'padding': '15px 20px',
            'border-radius': '5px',
            'box-shadow': '0 2px 10px rgba(0,0,0,0.2)',
            'z-index': '100001',
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
    initQueryBuilder();
});

