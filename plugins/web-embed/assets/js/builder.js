/**
 * Web Embed Builder JavaScript
 * 
 * Handles the interactive shortcode builder interface.
 *
 * @package WebEmbed
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    /**
     * Initialize builder when DOM is ready
     */
    $(document).ready(function() {
        initBuilder();
    });
    
    /**
     * Initialize the builder
     */
    function initBuilder() {
        const $form = $('#web-embed-builder-form');
        const $shortcodeOutput = $('#shortcode-output');
        const $copyButton = $('#copy-shortcode');
        const $copyFeedback = $('.copy-feedback');
        const $previewContainer = $('#preview-container');
        const $outputSection = $('.web-embed-shortcode-output');
        
        // Handle form submission
        $form.on('submit', function(e) {
            e.preventDefault();
            generatePreview();
        });
        
        // Handle copy button
        $copyButton.on('click', function() {
            copyShortcode();
        });
        
        /**
         * Generate preview via AJAX
         */
        function generatePreview() {
            // Show loading state
            $previewContainer.addClass('loading').html('');
            $outputSection.show();
            
            // Collect form data
            const formData = {
                action: 'web_embed_preview',
                nonce: webEmbedBuilder.nonce,
                url: $('#builder_url').val(),
                width: $('#builder_width').val(),
                height: $('#builder_height').val(),
                responsive: $('#builder_responsive').is(':checked') ? 'true' : 'false',
                border: $('#builder_border').val(),
                border_radius: $('#builder_border_radius').val(),
                title: $('#builder_title').val(),
                loading: $('#builder_loading').is(':checked') ? 'lazy' : 'eager',
                class: $('#builder_class').val()
            };
            
            // Make AJAX request
            $.ajax({
                url: webEmbedBuilder.ajaxUrl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        // Update shortcode display
                        $shortcodeOutput.text(response.data.shortcode);
                        $copyButton.prop('disabled', false);
                        
                        // Update preview
                        $previewContainer.removeClass('loading').html(response.data.html);
                        
                        // Add warning for sites that may block embedding
                        const url = $('#builder_url').val();
                        let hostname = '';
                        try {
                            hostname = new URL(url).hostname;
                        } catch(e) {
                            // Invalid URL, ignore
                        }
                        
                        const blockedSites = [
                            'google.com', 'facebook.com', 'twitter.com', 
                            'instagram.com', 'youtube.com', 'github.com', 
                            'linkedin.com'
                        ];
                        
                        const isLikelyBlocked = blockedSites.some(function(site) {
                            return hostname.includes(site) && !hostname.includes('maps.google.com') && !hostname.includes('youtube.com/embed');
                        });
                        
                        if (isLikelyBlocked) {
                            const warningHtml = 
                                '<div class="notice notice-warning" style="margin-top: 15px; padding: 10px;">' +
                                '<p><strong>⚠️ ' + escapeHtml(webEmbedBuilder.strings.error) + ': Embedding Likely Blocked</strong></p>' +
                                '<p>This site (' + escapeHtml(hostname) + ') typically prevents embedding for security reasons (X-Frame-Options header). ' +
                                'The shortcode will work on your site, but the content may show a fallback message instead.</p>' +
                                '<p><strong>Tip:</strong> Try embedding:</p>' +
                                '<ul>' +
                                '<li>Google Maps (maps.google.com/embed)</li>' +
                                '<li>YouTube videos (youtube.com/embed)</li>' +
                                '<li>Your own internal tools/dashboards</li>' +
                                '</ul>' +
                                '</div>';
                            $previewContainer.prepend(warningHtml);
                        }
                        
                        // Scroll to results
                        $('html, body').animate({
                            scrollTop: $outputSection.offset().top - 50
                        }, 500);
                    } else {
                        showError(response.data.message || 'Unknown error');
                    }
                },
                error: function(xhr, status, error) {
                    showError('AJAX error: ' + error);
                }
            });
        }
        
        /**
         * Copy shortcode to clipboard
         */
        function copyShortcode() {
            const shortcode = $shortcodeOutput.text();
            
            // Try modern clipboard API first
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(shortcode).then(
                    function() {
                        showCopyFeedback(true);
                    },
                    function() {
                        // Fallback to older method
                        fallbackCopy(shortcode);
                    }
                );
            } else {
                // Use fallback method
                fallbackCopy(shortcode);
            }
        }
        
        /**
         * Fallback copy method for older browsers
         */
        function fallbackCopy(text) {
            const $temp = $('<textarea>')
                .val(text)
                .appendTo('body')
                .select();
            
            try {
                const successful = document.execCommand('copy');
                showCopyFeedback(successful);
            } catch (err) {
                showCopyFeedback(false);
            }
            
            $temp.remove();
        }
        
        /**
         * Show copy feedback
         */
        function showCopyFeedback(success) {
            if (success) {
                $copyFeedback.text(webEmbedBuilder.strings.copied).fadeIn();
                setTimeout(function() {
                    $copyFeedback.fadeOut();
                }, 2000);
            } else {
                alert(webEmbedBuilder.strings.copyFailed);
            }
        }
        
        /**
         * Show error message
         */
        function showError(message) {
            $previewContainer.removeClass('loading').html(
                '<div class="notice notice-error"><p><strong>' + 
                escapeHtml(webEmbedBuilder.strings.error) + ':</strong> ' + 
                escapeHtml(message) + 
                '</p></div>'
            );
        }
        
        /**
         * Escape HTML for safe display
         */
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    }
    
})(jQuery);

