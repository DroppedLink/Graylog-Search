/**
 * Regex Helper - Pattern Library, Tester, and Syntax Helper
 */

jQuery(document).ready(function($) {
    
    // Initialize regex mode toggle
    var regexMode = false;
    var regexPatterns = {};
    var customPatterns = {};
    
    // Add regex toggle button to search form
    if ($('#search_terms').length > 0) {
        var regexToggleHtml = '<div class="regex-controls" style="margin-top: 10px;">';
        regexToggleHtml += '<label style="display: inline-flex; align-items: center; gap: 5px;">';
        regexToggleHtml += '<input type="checkbox" id="regex-mode-toggle"> ';
        regexToggleHtml += '<span>Regex Mode</span>';
        regexToggleHtml += '</label>';
        regexToggleHtml += '<button type="button" class="button button-small" id="regex-help-btn" style="margin-left: 10px;">📚 Pattern Library</button>';
        regexToggleHtml += '<button type="button" class="button button-small" id="regex-test-btn" style="margin-left: 5px;">🧪 Test Regex</button>';
        regexToggleHtml += '<button type="button" class="button button-small" id="regex-syntax-btn" style="margin-left: 5px;">❓ Syntax Help</button>';
        regexToggleHtml += '</div>';
        
        $('#search_terms').closest('td').append(regexToggleHtml);
    }
    
    // Toggle regex mode
    $(document).on('change', '#regex-mode-toggle', function() {
        regexMode = $(this).is(':checked');
        
        if (regexMode) {
            $('#search_terms').attr('placeholder', 'Enter regex pattern (e.g., \\berror\\b|\\bfatal\\b)');
            showNotification('Regex mode enabled. Your search terms will be treated as regular expressions.', 'info');
        } else {
            $('#search_terms').attr('placeholder', 'error, warning, specific text\nMultiple terms: one per line or comma-separated');
        }
    });
    
    // Show pattern library
    $(document).on('click', '#regex-help-btn', function() {
        loadRegexPatterns();
    });
    
    // Show regex tester
    $(document).on('click', '#regex-test-btn', function() {
        showRegexTester();
    });
    
    // Show syntax help
    $(document).on('click', '#regex-syntax-btn', function() {
        showRegexSyntaxHelp();
    });
    
    // Load regex patterns
    function loadRegexPatterns() {
        $.ajax({
            url: graylogSearch.ajaxUrl,
            type: 'POST',
            data: {
                action: 'graylog_get_regex_patterns',
                nonce: graylogSearch.nonce
            },
            success: function(response) {
                if (response.success) {
                    regexPatterns = response.data.patterns;
                    loadCustomPatterns();
                } else {
                    alert('Error loading patterns: ' + response.data.message);
                }
            }
        });
    }
    
    // Load custom patterns
    function loadCustomPatterns() {
        $.ajax({
            url: graylogSearch.ajaxUrl,
            type: 'POST',
            data: {
                action: 'graylog_get_custom_regex_patterns',
                nonce: graylogSearch.nonce
            },
            success: function(response) {
                if (response.success) {
                    customPatterns = response.data.patterns;
                    showPatternLibrary();
                }
            }
        });
    }
    
    // Show pattern library modal
    function showPatternLibrary() {
        var html = '<div class="regex-modal-overlay"></div>';
        html += '<div class="regex-modal">';
        html += '<div class="regex-modal-header">';
        html += '<h2>📚 Regex Pattern Library</h2>';
        html += '<button class="regex-modal-close">✕</button>';
        html += '</div>';
        html += '<div class="regex-modal-body">';
        
        // Tabs
        html += '<div class="regex-tabs">';
        html += '<button class="regex-tab active" data-tab="common">Common Patterns</button>';
        html += '<button class="regex-tab" data-tab="custom">My Patterns</button>';
        html += '</div>';
        
        // Common patterns tab
        html += '<div class="regex-tab-content" id="regex-tab-common">';
        html += '<div class="regex-pattern-grid">';
        $.each(regexPatterns, function(name, data) {
            html += '<div class="regex-pattern-card">';
            html += '<div class="regex-pattern-name">' + escapeHtml(name) + '</div>';
            html += '<div class="regex-pattern-desc">' + escapeHtml(data.description) + '</div>';
            html += '<div class="regex-pattern-code"><code>' + escapeHtml(data.pattern) + '</code></div>';
            html += '<div class="regex-pattern-example"><em>Example: ' + escapeHtml(data.example) + '</em></div>';
            html += '<div class="regex-pattern-actions">';
            html += '<button class="button button-small use-pattern" data-pattern="' + escapeHtml(data.pattern) + '">Use Pattern</button>';
            html += '<button class="button button-small copy-pattern" data-pattern="' + escapeHtml(data.pattern) + '">Copy</button>';
            html += '</div>';
            html += '</div>';
        });
        html += '</div>';
        html += '</div>';
        
        // Custom patterns tab
        html += '<div class="regex-tab-content" id="regex-tab-custom" style="display: none;">';
        
        if (Object.keys(customPatterns).length === 0) {
            html += '<p style="text-align: center; color: #666; padding: 40px;">No custom patterns yet. Save your frequently used patterns!</p>';
        } else {
            html += '<div class="regex-pattern-grid">';
            $.each(customPatterns, function(name, data) {
                html += '<div class="regex-pattern-card">';
                html += '<div class="regex-pattern-name">' + escapeHtml(name) + '</div>';
                html += '<div class="regex-pattern-desc">' + escapeHtml(data.description) + '</div>';
                html += '<div class="regex-pattern-code"><code>' + escapeHtml(data.pattern) + '</code></div>';
                html += '<div class="regex-pattern-actions">';
                html += '<button class="button button-small use-pattern" data-pattern="' + escapeHtml(data.pattern) + '">Use Pattern</button>';
                html += '<button class="button button-small copy-pattern" data-pattern="' + escapeHtml(data.pattern) + '">Copy</button>';
                html += '<button class="button button-small button-link-delete delete-custom-pattern" data-name="' + escapeHtml(name) + '">Delete</button>';
                html += '</div>';
                html += '</div>';
            });
            html += '</div>';
        }
        
        // Add custom pattern form
        html += '<div class="regex-add-custom">';
        html += '<h3>Save Custom Pattern</h3>';
        html += '<input type="text" id="custom-pattern-name" placeholder="Pattern name" style="width: 100%; margin-bottom: 10px;">';
        html += '<textarea id="custom-pattern-regex" placeholder="Regex pattern" rows="3" style="width: 100%; margin-bottom: 10px;"></textarea>';
        html += '<input type="text" id="custom-pattern-desc" placeholder="Description (optional)" style="width: 100%; margin-bottom: 10px;">';
        html += '<button class="button button-primary" id="save-custom-pattern">Save Pattern</button>';
        html += '</div>';
        
        html += '</div>';
        
        html += '</div>';
        html += '</div>';
        
        $('body').append(html);
    }
    
    // Show regex tester modal
    function showRegexTester() {
        var currentPattern = $('#search_terms').val();
        
        var html = '<div class="regex-modal-overlay"></div>';
        html += '<div class="regex-modal regex-tester-modal">';
        html += '<div class="regex-modal-header">';
        html += '<h2>🧪 Regex Tester</h2>';
        html += '<button class="regex-modal-close">✕</button>';
        html += '</div>';
        html += '<div class="regex-modal-body">';
        
        html += '<div class="regex-tester-section">';
        html += '<label><strong>Regex Pattern:</strong></label>';
        html += '<textarea id="regex-tester-pattern" rows="2" style="width: 100%; margin-bottom: 10px; font-family: monospace;">' + escapeHtml(currentPattern) + '</textarea>';
        html += '<div id="regex-validation-result"></div>';
        html += '</div>';
        
        html += '<div class="regex-tester-section">';
        html += '<label><strong>Test Text:</strong></label>';
        html += '<textarea id="regex-tester-text" rows="8" style="width: 100%; margin-bottom: 10px;" placeholder="Paste sample text here to test your regex pattern..."></textarea>';
        html += '</div>';
        
        html += '<div class="regex-tester-actions">';
        html += '<button class="button button-primary" id="test-regex-btn">Test Pattern</button>';
        html += '<button class="button" id="use-tested-pattern">Use This Pattern</button>';
        html += '</div>';
        
        html += '<div class="regex-tester-results" id="regex-test-results" style="display: none;"></div>';
        
        html += '</div>';
        html += '</div>';
        
        $('body').append(html);
        
        // Auto-validate on pattern change
        $('#regex-tester-pattern').on('input', function() {
            validateRegexPattern($(this).val());
        });
        
        // Initial validation
        if (currentPattern) {
            validateRegexPattern(currentPattern);
        }
    }
    
    // Validate regex pattern
    function validateRegexPattern(pattern) {
        if (!pattern) {
            $('#regex-validation-result').html('');
            return;
        }
        
        $.ajax({
            url: graylogSearch.ajaxUrl,
            type: 'POST',
            data: {
                action: 'graylog_validate_regex',
                nonce: graylogSearch.nonce,
                pattern: pattern
            },
            success: function(response) {
                if (response.success) {
                    $('#regex-validation-result').html('<span style="color: green;">✓ Valid regex pattern</span>');
                } else {
                    $('#regex-validation-result').html('<span style="color: red;">✗ Invalid: ' + escapeHtml(response.data.error || response.data.message) + '</span>');
                }
            }
        });
    }
    
    // Test regex pattern
    $(document).on('click', '#test-regex-btn', function() {
        var pattern = $('#regex-tester-pattern').val();
        var testText = $('#regex-tester-text').val();
        
        if (!pattern || !testText) {
            alert('Please provide both pattern and test text');
            return;
        }
        
        $.ajax({
            url: graylogSearch.ajaxUrl,
            type: 'POST',
            data: {
                action: 'graylog_test_regex',
                nonce: graylogSearch.nonce,
                pattern: pattern,
                test_text: testText
            },
            success: function(response) {
                if (response.success) {
                    displayTestResults(response.data);
                } else {
                    $('#regex-test-results').html('<div class="regex-error">Error: ' + escapeHtml(response.data.error || response.data.message) + '</div>').show();
                }
            }
        });
    });
    
    // Display test results
    function displayTestResults(data) {
        var html = '<div class="regex-test-success">';
        html += '<h3>✓ Test Results</h3>';
        html += '<p><strong>Matches found:</strong> ' + data.match_count + '</p>';
        
        if (data.match_count > 0) {
            html += '<table class="regex-matches-table">';
            html += '<thead><tr><th>Match</th><th>Position</th></tr></thead>';
            html += '<tbody>';
            $.each(data.matches, function(i, match) {
                html += '<tr>';
                html += '<td><code>' + escapeHtml(match.text) + '</code></td>';
                html += '<td>' + match.position + '</td>';
                html += '</tr>';
            });
            html += '</tbody></table>';
        }
        
        html += '</div>';
        
        $('#regex-test-results').html(html).show();
    }
    
    // Show regex syntax help
    function showRegexSyntaxHelp() {
        var html = '<div class="regex-modal-overlay"></div>';
        html += '<div class="regex-modal regex-syntax-modal">';
        html += '<div class="regex-modal-header">';
        html += '<h2>❓ Regex Syntax Cheat Sheet</h2>';
        html += '<button class="regex-modal-close">✕</button>';
        html += '</div>';
        html += '<div class="regex-modal-body">';
        
        html += '<div class="regex-syntax-grid">';
        
        // Basic syntax
        html += '<div class="regex-syntax-section">';
        html += '<h3>Basic Patterns</h3>';
        html += '<table class="regex-syntax-table">';
        html += '<tr><td><code>.</code></td><td>Any character</td></tr>';
        html += '<tr><td><code>\\d</code></td><td>Digit (0-9)</td></tr>';
        html += '<tr><td><code>\\w</code></td><td>Word character (a-Z, 0-9, _)</td></tr>';
        html += '<tr><td><code>\\s</code></td><td>Whitespace</td></tr>';
        html += '<tr><td><code>\\b</code></td><td>Word boundary</td></tr>';
        html += '<tr><td><code>^</code></td><td>Start of line</td></tr>';
        html += '<tr><td><code>$</code></td><td>End of line</td></tr>';
        html += '</table>';
        html += '</div>';
        
        // Quantifiers
        html += '<div class="regex-syntax-section">';
        html += '<h3>Quantifiers</h3>';
        html += '<table class="regex-syntax-table">';
        html += '<tr><td><code>*</code></td><td>0 or more</td></tr>';
        html += '<tr><td><code>+</code></td><td>1 or more</td></tr>';
        html += '<tr><td><code>?</code></td><td>0 or 1</td></tr>';
        html += '<tr><td><code>{3}</code></td><td>Exactly 3</td></tr>';
        html += '<tr><td><code>{3,}</code></td><td>3 or more</td></tr>';
        html += '<tr><td><code>{3,5}</code></td><td>3 to 5</td></tr>';
        html += '</table>';
        html += '</div>';
        
        // Groups
        html += '<div class="regex-syntax-section">';
        html += '<h3>Groups & Alternation</h3>';
        html += '<table class="regex-syntax-table">';
        html += '<tr><td><code>()</code></td><td>Capture group</td></tr>';
        html += '<tr><td><code>(?:)</code></td><td>Non-capture group</td></tr>';
        html += '<tr><td><code>|</code></td><td>OR (alternation)</td></tr>';
        html += '<tr><td><code>[]</code></td><td>Character class</td></tr>';
        html += '<tr><td><code>[^]</code></td><td>Negated class</td></tr>';
        html += '</table>';
        html += '</div>';
        
        // Examples
        html += '<div class="regex-syntax-section regex-syntax-examples">';
        html += '<h3>Common Examples</h3>';
        html += '<ul>';
        html += '<li><code>\\berror\\b</code> - Whole word "error"</li>';
        html += '<li><code>error|fail</code> - "error" OR "fail"</li>';
        html += '<li><code>^ERROR</code> - Lines starting with "ERROR"</li>';
        html += '<li><code>\\d{3}-\\d{4}</code> - Phone format 555-1234</li>';
        html += '<li><code>[A-Z]{3,}</code> - 3+ uppercase letters</li>';
        html += '<li><code>\\w+@\\w+\\.\\w+</code> - Simple email</li>';
        html += '</ul>';
        html += '</div>';
        
        html += '</div>';
        html += '</div>';
        html += '</div>';
        
        $('body').append(html);
    }
    
    // Modal event handlers
    $(document).on('click', '.regex-modal-close, .regex-modal-overlay', function() {
        $('.regex-modal, .regex-modal-overlay').remove();
    });
    
    $(document).on('click', '.regex-tab', function() {
        var tab = $(this).data('tab');
        $('.regex-tab').removeClass('active');
        $(this).addClass('active');
        $('.regex-tab-content').hide();
        $('#regex-tab-' + tab).show();
    });
    
    $(document).on('click', '.use-pattern', function() {
        var pattern = $(this).data('pattern');
        $('#search_terms').val(pattern);
        $('#regex-mode-toggle').prop('checked', true).trigger('change');
        $('.regex-modal, .regex-modal-overlay').remove();
        showNotification('Pattern applied! Regex mode enabled.', 'success');
    });
    
    $(document).on('click', '.copy-pattern', function() {
        var pattern = $(this).data('pattern');
        copyToClipboard(pattern);
        showNotification('Pattern copied to clipboard!', 'success');
    });
    
    $(document).on('click', '#use-tested-pattern', function() {
        var pattern = $('#regex-tester-pattern').val();
        $('#search_terms').val(pattern);
        $('#regex-mode-toggle').prop('checked', true).trigger('change');
        $('.regex-modal, .regex-modal-overlay').remove();
        showNotification('Pattern applied! Regex mode enabled.', 'success');
    });
    
    $(document).on('click', '#save-custom-pattern', function() {
        var name = $('#custom-pattern-name').val();
        var pattern = $('#custom-pattern-regex').val();
        var description = $('#custom-pattern-desc').val();
        
        if (!name || !pattern) {
            alert('Name and pattern are required');
            return;
        }
        
        $.ajax({
            url: graylogSearch.ajaxUrl,
            type: 'POST',
            data: {
                action: 'graylog_save_regex_pattern',
                nonce: graylogSearch.nonce,
                name: name,
                pattern: pattern,
                description: description
            },
            success: function(response) {
                if (response.success) {
                    showNotification('Custom pattern saved!', 'success');
                    $('.regex-modal, .regex-modal-overlay').remove();
                } else {
                    alert('Error: ' + response.data.message);
                }
            }
        });
    });
    
    $(document).on('click', '.delete-custom-pattern', function() {
        if (!confirm('Delete this pattern?')) return;
        
        var name = $(this).data('name');
        
        $.ajax({
            url: graylogSearch.ajaxUrl,
            type: 'POST',
            data: {
                action: 'graylog_delete_regex_pattern',
                nonce: graylogSearch.nonce,
                name: name
            },
            success: function(response) {
                if (response.success) {
                    showNotification('Pattern deleted', 'success');
                    $('.regex-modal, .regex-modal-overlay').remove();
                } else {
                    alert('Error: ' + response.data.message);
                }
            }
        });
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
    
    function copyToClipboard(text) {
        var $temp = $('<textarea>');
        $('body').append($temp);
        $temp.val(text).select();
        document.execCommand('copy');
        $temp.remove();
    }
    
    function showNotification(message, type) {
        var color = type === 'success' ? '#46b450' : (type === 'error' ? '#dc3232' : '#0073aa');
        var $notif = $('<div class="regex-notification">' + message + '</div>');
        $notif.css({
            'position': 'fixed',
            'top': '20px',
            'right': '20px',
            'background': color,
            'color': 'white',
            'padding': '15px 20px',
            'border-radius': '5px',
            'box-shadow': '0 2px 10px rgba(0,0,0,0.2)',
            'z-index': '100000',
            'animation': 'slideIn 0.3s'
        });
        
        $('body').append($notif);
        
        setTimeout(function() {
            $notif.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    // Integrate regex mode with search
    var originalPerformSearch = window.performSearch;
    if (typeof originalPerformSearch === 'function') {
        window.performSearch = function($form, interfaceType, retryCount) {
            // If regex mode is enabled, add regex flag to search
            if (regexMode && $('#search_terms').val()) {
                // Store regex mode in form data (will be handled by backend)
                $form.data('regex-mode', true);
            }
            
            return originalPerformSearch.call(this, $form, interfaceType, retryCount);
        };
    }
});

