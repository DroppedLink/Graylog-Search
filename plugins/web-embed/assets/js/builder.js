/**
 * Web Embed Builder JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        var $form = $('#web-embed-builder-form');
        var $previewButton = $('#web-embed-generate-preview');
        var $copyButton = $('#web-embed-copy-shortcode');
        var $clearButton = $('#web-embed-clear-form');
        var $shortcodeDisplay = $('#web-embed-shortcode-display');
        var $previewContainer = $('#web-embed-preview-container');
        var $previewLoading = $('#web-embed-preview-loading');
        var $previewError = $('#web-embed-preview-error');
        var $copyFeedback = $('#web-embed-copy-feedback');
        
        // Generate preview and shortcode
        $previewButton.on('click', function(e) {
            e.preventDefault();
            
            // Validate URL
            var url = $('#builder_url').val().trim();
            if (!url) {
                alert('Please enter a URL to embed');
                $('#builder_url').focus();
                return;
            }
            
            // Disable button
            $previewButton.prop('disabled', true).text('Generating...');
            $previewLoading.show();
            $previewError.hide();
            $copyFeedback.text('');
            
            // Gather form data
            var formData = {
                action: 'web_embed_preview',
                nonce: webEmbedBuilder.nonce,
                url: url,
                width: $('#builder_width').val(),
                height: $('#builder_height').val(),
                responsive: $('#builder_responsive').val(),
                border: $('#builder_border').val(),
                border_radius: $('#builder_border_radius').val(),
                custom_class: $('#builder_class').val(),
                title: $('#builder_title').val(),
                loading: $('#builder_loading').val(),
                fallback: $('#builder_fallback').val()
            };
            
            // Make AJAX request
            $.ajax({
                url: webEmbedBuilder.ajaxUrl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        // Update shortcode display
                        $shortcodeDisplay.text(response.data.shortcode);
                        $copyButton.prop('disabled', false);
                        
                        // Update preview
                        $previewContainer.html(response.data.html);
                        
                        // Add warning for sites that may block embedding
                        var url = $('#builder_url').val();
                        var hostname = '';
                        try {
                            hostname = new URL(url).hostname;
                        } catch(e) {}
                        
                        var blockedSites = ['google.com', 'facebook.com', 'twitter.com', 'instagram.com', 'youtube.com', 'github.com', 'linkedin.com'];
                        var isLikelyBlocked = blockedSites.some(function(site) {
                            return hostname.indexOf(site) !== -1;
                        });
                        
                        if (isLikelyBlocked) {
                            var warningHtml = '<div class="notice notice-warning" style="margin-top: 15px; padding: 10px;">' +
                                '<p><strong>⚠️ Embedding Likely Blocked</strong></p>' +
                                '<p>This site (' + hostname + ') typically prevents embedding for security reasons (X-Frame-Options header). ' +
                                'The shortcode will work on your site, but the content may show a fallback message instead.</p>' +
                                '<p><strong>Tip:</strong> Try embedding:\n' +
                                '• Google Maps (maps.google.com/embed)\n' +
                                '• YouTube videos (youtube.com/embed)\n' +
                                '• Your own internal tools/dashboards</p>' +
                                '</div>';
                            $previewContainer.prepend(warningHtml);
                        }
                        
                        // Scroll to results
                        $('html, body').animate({
                            scrollTop: $('.web-embed-shortcode-output').offset().top - 50
                        }, 500);
                    } else {
                        showError('Error: ' + (response.data.message || 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    showError('AJAX Error: ' + error);
                },
                complete: function() {
                    $previewButton.prop('disabled', false).text('Generate Preview & Shortcode');
                    $previewLoading.hide();
                }
            });
        });
        
        // Copy shortcode to clipboard
        $copyButton.on('click', function(e) {
            e.preventDefault();
            
            var shortcode = $shortcodeDisplay.text();
            
            // Modern clipboard API
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(shortcode).then(function() {
                    showCopySuccess();
                }).catch(function(err) {
                    fallbackCopy(shortcode);
                });
            } else {
                fallbackCopy(shortcode);
            }
        });
        
        // Fallback copy method for older browsers
        function fallbackCopy(text) {
            var $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(text).select();
            
            try {
                document.execCommand('copy');
                showCopySuccess();
            } catch (err) {
                showCopyError();
            }
            
            $temp.remove();
        }
        
        // Show copy success message
        function showCopySuccess() {
            $copyFeedback.removeClass('error').addClass('success')
                .text('✓ Shortcode copied to clipboard!')
                .fadeIn();
            
            setTimeout(function() {
                $copyFeedback.fadeOut();
            }, 3000);
        }
        
        // Show copy error message
        function showCopyError() {
            $copyFeedback.removeClass('success').addClass('error')
                .text('✗ Failed to copy. Please select and copy manually.')
                .fadeIn();
            
            // Select the text for manual copying
            var range = document.createRange();
            range.selectNode($shortcodeDisplay[0]);
            window.getSelection().removeAllRanges();
            window.getSelection().addRange(range);
        }
        
        // Show preview error
        function showError(message) {
            $previewError.html('<p>' + message + '</p>').show();
            $previewContainer.html('<p class="preview-placeholder">Preview unavailable due to error</p>');
        }
        
        // Clear form
        $clearButton.on('click', function(e) {
            e.preventDefault();
            
            if (confirm('Clear all fields and reset to defaults?')) {
                $('#builder_url').val('');
                $('#builder_border').val('none');
                $('#builder_border_radius').val('0');
                $('#builder_title').val('Embedded Content');
                $('#builder_loading').val('lazy');
                $('#builder_fallback').val('');
                
                // Reset preview
                $shortcodeDisplay.text('Fill in the form and click "Generate Preview & Shortcode"');
                $previewContainer.html('<p class="preview-placeholder">Your embed preview will appear here</p>');
                $copyButton.prop('disabled', true);
                $copyFeedback.text('');
                $previewError.hide();
                
                $('#builder_url').focus();
            }
        });
        
        // Enable Enter key to generate preview
        $form.on('keypress', function(e) {
            if (e.which === 13 && !$(e.target).is('textarea')) {
                e.preventDefault();
                $previewButton.click();
            }
        });
        
        // Auto-update border preview when typing
        var borderInputs = $('#builder_border, #builder_border_radius');
        var borderTimeout;
        
        borderInputs.on('input', function() {
            clearTimeout(borderTimeout);
            
            // Show live preview hint
            var $input = $(this);
            var value = $input.val();
            
            if (value && value !== 'none' && value !== '0') {
                // Optional: Could add live CSS preview here
            }
        });
        
        // Preset buttons (optional feature)
        if ($('.web-embed-preset').length) {
            $('.web-embed-preset').on('click', function(e) {
                e.preventDefault();
                var preset = $(this).data('preset');
                applyPreset(preset);
            });
        }
        
        // Apply preset configurations
        function applyPreset(preset) {
            switch(preset) {
                case 'responsive':
                    $('#builder_width').val('100%');
                    $('#builder_height').val('600px');
                    $('#builder_responsive').val('true');
                    break;
                case 'fixed':
                    $('#builder_width').val('800px');
                    $('#builder_height').val('600px');
                    $('#builder_responsive').val('false');
                    break;
                case 'bordered':
                    $('#builder_border').val('2px solid #cccccc');
                    $('#builder_border_radius').val('8px');
                    break;
            }
        }
        
        // URL validation on blur
        $('#builder_url').on('blur', function() {
            var url = $(this).val().trim();
            if (url && !isValidUrl(url)) {
                $(this).addClass('invalid');
                alert('Please enter a valid URL starting with http:// or https://');
            } else {
                $(this).removeClass('invalid');
            }
        });
        
        // Simple URL validation
        function isValidUrl(string) {
            try {
                new URL(string);
                return true;
            } catch (_) {
                return false;
            }
        }
    });
    
})(jQuery);

