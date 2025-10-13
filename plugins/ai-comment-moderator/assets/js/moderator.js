jQuery(document).ready(function($) {
    'use strict';
    
    // Global variables
    var currentBatchId = null;
    var batchProcessing = false;
    
    // Initialize the plugin
    init();
    
    function init() {
        // Settings page functionality
        initSettingsPage();
        
        // Batch processing functionality
        initBatchProcessing();
        
        // Prompt management functionality
        initPromptManagement();
        
        // Dashboard functionality
        initDashboard();
        
        // Comment processing functionality
        initCommentProcessing();
    }
    
    /**
     * Settings Page Functionality
     */
    function initSettingsPage() {
        // Test Ollama connection
        $('#test-connection').on('click', function(e) {
            e.preventDefault();
            testOllamaConnection();
        });
        
        // Load models when connection is successful
        $('#load-models').on('click', function(e) {
            e.preventDefault();
            loadOllamaModels();
        });
        
        // Auto-load models when URL changes
        $('#ollama_url').on('blur', function() {
            if ($(this).val()) {
                loadOllamaModels();
            }
        });
    }
    
    function testOllamaConnection() {
        var $button = $('#test-connection');
        var $status = $('#connection-status');
        var ollamaUrl = $('#ollama_url').val();
        
        if (!ollamaUrl) {
            showMessage($status, 'Please enter an Ollama URL first.', 'error');
            return;
        }
        
        $button.prop('disabled', true).text('Testing...');
        
        $.post(aiCommentModerator.ajaxUrl, {
            action: 'ai_moderator_test_connection',
            nonce: aiCommentModerator.nonce,
            ollama_url: ollamaUrl
        }, function(response) {
            if (response.success) {
                showMessage($status, '✓ Connection successful! Found ' + response.data.models_count + ' models.', 'success');
                loadOllamaModels();
            } else {
                showMessage($status, '✗ Connection failed: ' + response.data, 'error');
            }
        }).fail(function() {
            showMessage($status, '✗ Connection failed: Network error', 'error');
        }).always(function() {
            $button.prop('disabled', false).text('Test Connection');
        });
    }
    
    function loadOllamaModels() {
        var $select = $('#ollama_model');
        var $button = $('#load-models');
        var ollamaUrl = $('#ollama_url').val();
        
        if (!ollamaUrl) {
            return;
        }
        
        $button.prop('disabled', true).text('Loading...');
        
        $.post(aiCommentModerator.ajaxUrl, {
            action: 'ai_moderator_get_models',
            nonce: aiCommentModerator.nonce,
            ollama_url: ollamaUrl
        }, function(response) {
            if (response.success) {
                $select.empty().append('<option value="">Select a model...</option>');
                
                $.each(response.data.models, function(index, model) {
                    $select.append('<option value="' + model + '">' + model + '</option>');
                });
                
                showMessage($('#connection-status'), '✓ Loaded ' + response.data.models.length + ' models', 'success');
            } else {
                showMessage($('#connection-status'), '✗ Failed to load models: ' + response.data, 'error');
            }
        }).fail(function() {
            showMessage($('#connection-status'), '✗ Failed to load models: Network error', 'error');
        }).always(function() {
            $button.prop('disabled', false).text('Load Models');
        });
    }
    
    /**
     * Batch Processing Functionality
     */
    function initBatchProcessing() {
        $('#batch-process-form').on('submit', function(e) {
            e.preventDefault();
            startBatchProcessing();
        });
        
        // Toggle status filter visibility based on comment source
        $('#comment_source').on('change', function() {
            var source = $(this).val();
            if (source === 'local') {
                $('#local-status-filter').show();
            } else {
                $('#local-status-filter').hide();
            }
        });
    }
    
    function startBatchProcessing() {
        var promptId = $('#prompt_id').val();
        var batchCount = $('#batch_count').val();
        var commentStatus = $('#comment_status').val();
        var includeReviewed = $('#include_reviewed').is(':checked') ? '1' : '0';
        var commentSource = $('#comment_source').val();
        
        // Parse remote site ID if selecting specific remote site
        var remoteSiteId = 0;
        var actualSource = 'local';
        if (commentSource.startsWith('remote_')) {
            actualSource = 'remote';
            if (commentSource !== 'remote_all') {
                remoteSiteId = parseInt(commentSource.replace('remote_', ''));
            }
        }
        
        if (!promptId || !batchCount) {
            alert('Please select a prompt and specify the number of comments to process.');
            return;
        }
        
        var $button = $('#start-batch');
        $button.prop('disabled', true).text('Starting...');
        
        // Show progress section
        $('#batch-progress').show();
        $('#batch-results').hide();
        
        $.post(aiCommentModerator.ajaxUrl, {
            action: 'ai_moderator_start_batch',
            nonce: aiCommentModerator.nonce,
            prompt_id: promptId,
            batch_count: batchCount,
            comment_status: commentStatus,
            include_reviewed: includeReviewed,
            comment_source: actualSource,
            remote_site_id: remoteSiteId
        }, function(response) {
            if (response.success) {
                currentBatchId = response.data.batch_id;
                batchProcessing = true;
                
                updateProgress(0, response.data.total_comments, 'Starting batch processing...');
                processBatchChunk(0, response.data.total_comments, response.data.chunk_size);
            } else {
                alert('Failed to start batch processing: ' + response.data);
                resetBatchUI();
            }
        }).fail(function() {
            alert('Network error while starting batch processing');
            resetBatchUI();
        });
    }
    
    function processBatchChunk(offset, total, chunkSize) {
        if (!batchProcessing || !currentBatchId) {
            return;
        }
        
        updateProgress(offset, total, 'Processing comments ' + (offset + 1) + '-' + Math.min(offset + chunkSize, total) + ' of ' + total + '...');
        
        $.post(aiCommentModerator.ajaxUrl, {
            action: 'ai_moderator_process_batch_chunk',
            nonce: aiCommentModerator.nonce,
            batch_id: currentBatchId,
            chunk_offset: offset
        }, function(response) {
            if (response.success) {
                var processed = offset + response.data.chunk_results.processed;
                updateProgress(processed, total, 'Processed ' + processed + ' of ' + total + ' comments');
                
                // Log chunk results
                logBatchResults(response.data.chunk_results);
                
                if (response.data.completed) {
                    completeBatchProcessing();
                } else {
                    // Process next chunk after a short delay
                    setTimeout(function() {
                        processBatchChunk(response.data.next_offset, total, chunkSize);
                    }, 500);
                }
            } else {
                alert('Error processing batch chunk: ' + response.data);
                resetBatchUI();
            }
        }).fail(function() {
            alert('Network error during batch processing');
            resetBatchUI();
        });
    }
    
    function completeBatchProcessing() {
        batchProcessing = false;
        updateProgress(100, 100, 'Batch processing completed!');
        
        // Show results section
        $('#batch-results').show();
        
        // Get final batch status
        $.post(aiCommentModerator.ajaxUrl, {
            action: 'ai_moderator_get_batch_status',
            nonce: aiCommentModerator.nonce,
            batch_id: currentBatchId
        }, function(response) {
            if (response.success) {
                displayBatchResults(response.data);
            }
        });
        
        resetBatchUI();
    }
    
    function updateProgress(current, total, message) {
        var percentage = total > 0 ? Math.round((current / total) * 100) : 0;
        $('#progress-fill').css('width', percentage + '%');
        $('#progress-text').text(message + ' (' + percentage + '%)');
    }
    
    function logBatchResults(results) {
        var $log = $('#progress-log');
        
        $.each(results.details, function(index, detail) {
            var result = detail.result;
            var logClass = result.success ? 'log-success' : 'log-error';
            var message = '';
            
            if (result.success) {
                // Success - show decision with details
                var authorInfo = detail.author ? ' by <strong>' + escapeHtml(detail.author) + '</strong>' : '';
                var siteInfo = detail.site ? ' [' + escapeHtml(detail.site) + ']' : '';
                var snippet = detail.snippet ? '<div style="margin-left: 20px; color: #666; font-size: 0.9em;">"' + escapeHtml(detail.snippet) + '"</div>' : '';
                
                message = 'Comment #' + detail.comment_id + authorInfo + siteInfo + ': ' +
                         '<span style="color: #2271b1; font-weight: bold;">' + result.decision + '</span> → ' +
                         '<span style="color: #135e96;">' + result.action + '</span>' +
                         snippet;
            } else {
                // Error - show error message
                message = 'Comment #' + detail.comment_id + ': <span style="color: #d63638;">Error</span> - ' + escapeHtml(result.error);
            }
            
            $log.append('<div class="log-entry ' + logClass + '" style="margin-bottom: 10px; padding: 8px; border-left: 3px solid ' + (result.success ? '#46b450' : '#d63638') + ';">' + message + '</div>');
        });
        
        // Auto-scroll to bottom
        $log.scrollTop($log[0].scrollHeight);
    }
    
    function displayBatchResults(batchData) {
        var html = '<div class="results-summary">';
        html += '<h3>Processing Summary</h3>';
        html += '<p><strong>Total Comments:</strong> ' + batchData.total_count + '</p>';
        html += '<p><strong>Successfully Processed:</strong> ' + batchData.success_count + '</p>';
        html += '<p><strong>Errors:</strong> ' + batchData.error_count + '</p>';
        html += '<p><strong>Started:</strong> ' + batchData.started_at + '</p>';
        html += '<p><strong>Completed:</strong> ' + batchData.completed_at + '</p>';
        html += '</div>';
        
        $('#results-summary').html(html);
    }
    
    function resetBatchUI() {
        $('#start-batch').prop('disabled', false).text('Start Processing');
        currentBatchId = null;
        batchProcessing = false;
    }
    
    /**
     * Prompt Management Functionality
     */
    function initPromptManagement() {
        // Test prompt functionality
        $('#test-prompt').on('click', function(e) {
            e.preventDefault();
            testPrompt();
        });
        
        // Preview prompt functionality
        $('#preview-prompt').on('click', function(e) {
            e.preventDefault();
            previewPrompt();
        });
        
        // Template loading
        $('#prompt-template').on('change', function() {
            loadPromptTemplate();
        });
    }
    
    function testPrompt() {
        var promptText = $('#prompt-text').val();
        var testCommentId = $('#test-comment-id').val();
        
        if (!promptText) {
            alert('Please enter a prompt to test.');
            return;
        }
        
        var $button = $('#test-prompt');
        $button.prop('disabled', true).text('Testing...');
        
        $.post(aiCommentModerator.ajaxUrl, {
            action: 'ai_moderator_test_prompt',
            nonce: aiCommentModerator.nonce,
            prompt_text: promptText,
            test_comment_id: testCommentId
        }, function(response) {
            if (response.success) {
                displayPromptTestResults(response.data);
            } else {
                alert('Prompt test failed: ' + response.data);
            }
        }).fail(function() {
            alert('Network error during prompt test');
        }).always(function() {
            $button.prop('disabled', false).text('Test Prompt');
        });
    }
    
    function previewPrompt() {
        var promptText = $('#prompt-text').val();
        var commentId = $('#preview-comment-id').val();
        
        if (!promptText) {
            alert('Please enter a prompt to preview.');
            return;
        }
        
        $.post(aiCommentModerator.ajaxUrl, {
            action: 'ai_moderator_preview_prompt',
            nonce: aiCommentModerator.nonce,
            prompt_text: promptText,
            comment_id: commentId
        }, function(response) {
            if (response.success) {
                $('#prompt-preview').html('<pre>' + response.data.processed_prompt + '</pre>').show();
            } else {
                alert('Preview failed: ' + response.data);
            }
        }).fail(function() {
            alert('Network error during preview');
        });
    }
    
    function displayPromptTestResults(data) {
        var html = '<div class="test-results">';
        html += '<h4>Test Results</h4>';
        html += '<p><strong>Processed Prompt:</strong></p>';
        html += '<pre>' + data.processed_prompt + '</pre>';
        html += '<p><strong>AI Response:</strong></p>';
        html += '<pre>' + data.ai_response + '</pre>';
        html += '<p><strong>Processing Time:</strong> ' + data.processing_time.toFixed(2) + ' seconds</p>';
        html += '<p><strong>Model Used:</strong> ' + data.model_used + '</p>';
        html += '</div>';
        
        $('#test-results').html(html).show();
    }
    
    /**
     * Dashboard Functionality
     */
    function initDashboard() {
        // Refresh dashboard data
        $('#refresh-dashboard').on('click', function(e) {
            e.preventDefault();
            loadDashboardData();
        });
        
        // Auto-refresh every 30 seconds if on dashboard page
        if ($('.ai-moderator-stats').length > 0) {
            setInterval(loadDashboardData, 30000);
        }
    }
    
    function loadDashboardData() {
        $.post(aiCommentModerator.ajaxUrl, {
            action: 'ai_moderator_get_dashboard_data',
            nonce: aiCommentModerator.nonce
        }, function(response) {
            if (response.success) {
                updateDashboardStats(response.data);
            }
        });
    }
    
    function updateDashboardStats(data) {
        $('.stat-card').each(function() {
            var $card = $(this);
            var $number = $card.find('.stat-number');
            var title = $card.find('h3').text().toLowerCase();
            
            if (title.includes('total')) {
                $number.text(numberFormat(data.total_comments));
            } else if (title.includes('reviewed')) {
                $number.text(numberFormat(data.reviewed_count));
            } else if (title.includes('pending')) {
                $number.text(numberFormat(data.pending_count));
            } else if (title.includes('progress')) {
                var percentage = data.total_comments > 0 ? 
                    Math.round((data.reviewed_count / data.total_comments) * 100) : 0;
                $number.text(percentage + '%');
            }
        });
    }
    
    /**
     * Comment Processing Functionality
     */
    function initCommentProcessing() {
        // Individual comment processing from comment edit screen
        window.processComment = function(commentId) {
            var promptId = $('#ai-prompt-select').val();
            if (!promptId) {
                alert('Please select a prompt first.');
                return;
            }
            
            var $button = $(event.target);
            $button.prop('disabled', true).text('Processing...');
            
            $.post(ajaxurl, {
                action: 'ai_moderator_process_comment',
                nonce: aiCommentModerator.nonce,
                comment_id: commentId,
                prompt_id: promptId
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                    $button.prop('disabled', false).text('Process Now');
                }
            }).fail(function() {
                alert('Network error during processing');
                $button.prop('disabled', false).text('Process Now');
            });
        };
    }
    
    /**
     * Utility Functions
     */
    function showMessage($container, message, type) {
        var className = type === 'success' ? 'connection-success' : 'connection-error';
        $container.html('<div class="connection-status ' + className + '">' + message + '</div>');
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            $container.fadeOut();
        }, 5000);
    }
    
    function numberFormat(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
    
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
    
    // Loading state management
    function setLoading($element, loading) {
        if (loading) {
            $element.addClass('loading');
        } else {
            $element.removeClass('loading');
        }
    }
    
    // Error handling
    $(document).ajaxError(function(event, jqXHR, ajaxSettings, thrownError) {
        if (jqXHR.status === 403) {
            alert('Session expired. Please refresh the page and try again.');
        } else if (jqXHR.status === 500) {
            console.error('Server error:', thrownError);
        }
    });
    
    // Confirmation dialogs for destructive actions
    $('.delete-prompt').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this prompt? This action cannot be undone.')) {
            e.preventDefault();
        }
    });
    
    // Form validation
    $('form').on('submit', function(e) {
        var $form = $(this);
        var valid = true;
        
        $form.find('[required]').each(function() {
            if (!$(this).val()) {
                $(this).addClass('error');
                valid = false;
            } else {
                $(this).removeClass('error');
            }
        });
        
        if (!valid) {
            e.preventDefault();
            alert('Please fill in all required fields.');
        }
    });
    
    // Auto-save functionality for prompt editing
    var autoSaveTimer;
    $('#prompt-text').on('input', function() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(function() {
            // Auto-save draft (could be implemented)
            console.log('Auto-saving prompt draft...');
        }, 2000);
    });
    
    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl+S to save forms
        if (e.ctrlKey && e.which === 83) {
            e.preventDefault();
            $('form').first().submit();
        }
        
        // Escape to close modals/dialogs
        if (e.which === 27) {
            $('.modal, .dialog').hide();
        }
    });
    
    // Tooltips
    $('[data-tooltip]').hover(
        function() {
            var tooltip = $(this).data('tooltip');
            $(this).append('<div class="tooltip-popup">' + tooltip + '</div>');
        },
        function() {
            $('.tooltip-popup').remove();
        }
    );
});
