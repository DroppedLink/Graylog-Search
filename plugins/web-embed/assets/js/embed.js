/**
 * Web Embed Plugin JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Admin clear cache button handler
        $('#web-embed-clear-cache').on('click', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $result = $('#web-embed-cache-result');
            
            // Disable button
            $button.prop('disabled', true).text('Clearing...');
            $result.removeClass('success error').text('');
            
            // Make AJAX request
            $.ajax({
                url: webEmbed.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'web_embed_clear_cache',
                    nonce: webEmbed.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $result.addClass('success').text(response.data.message);
                    } else {
                        $result.addClass('error').text('Error: ' + response.data.message);
                    }
                },
                error: function() {
                    $result.addClass('error').text('Error: Failed to clear cache');
                },
                complete: function() {
                    // Re-enable button
                    $button.prop('disabled', false).text('Clear All Cache');
                    
                    // Clear result message after 5 seconds
                    setTimeout(function() {
                        $result.fadeOut(function() {
                            $(this).text('').removeClass('success error').show();
                        });
                    }, 5000);
                }
            });
        });
        
        // Frontend embed load detection
        $('.web-embed-container object').each(function() {
            var $container = $(this).closest('.web-embed-container');
            var $fallback = $(this).find('.web-embed-fallback');
            
            // Add loading class
            $container.addClass('web-embed-loading-state');
            
            // Detect if object loads successfully
            $(this).on('load', function() {
                $container.removeClass('web-embed-loading-state');
            });
            
            // Timeout to show fallback if content doesn't load
            setTimeout(function() {
                if ($container.hasClass('web-embed-loading-state')) {
                    $container.removeClass('web-embed-loading-state');
                    // The fallback will be visible if object fails to load
                }
            }, 10000); // 10 second timeout
        });
        
        // Handle keyboard navigation for embedded content
        $('.web-embed-container').each(function() {
            var $container = $(this);
            var $object = $container.find('object');
            
            // Make container keyboard accessible if it contains interactive content
            if ($object.length > 0) {
                $container.attr('tabindex', '0');
                
                $container.on('keydown', function(e) {
                    // Enter key focuses on embedded content
                    if (e.keyCode === 13) {
                        $object.focus();
                    }
                });
            }
        });
        
        // Optional: Fullscreen toggle functionality
        if (typeof screenfull !== 'undefined') {
            $('.web-embed-container').each(function() {
                var $container = $(this);
                
                // Add fullscreen button
                var $fsButton = $('<button class="web-embed-fullscreen-btn" title="Toggle Fullscreen">⛶</button>');
                $container.append($fsButton);
                
                $fsButton.on('click', function(e) {
                    e.preventDefault();
                    if (screenfull.isEnabled) {
                        screenfull.toggle($container[0]);
                    }
                });
            });
        }
        
        // Track embed performance (optional analytics)
        if (window.performance && window.performance.mark) {
            $('.web-embed-container object').each(function(index) {
                var embedId = $(this).attr('id') || 'embed-' + index;
                
                $(this).on('load', function() {
                    performance.mark(embedId + '-loaded');
                    
                    // You could send this data to analytics
                    console.log('Embed loaded: ' + embedId);
                });
            });
        }
        
        // Responsive resize handler
        var resizeTimer;
        $(window).on('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                // Recalculate responsive embed dimensions if needed
                $('.web-embed-responsive-wrapper').each(function() {
                    var $wrapper = $(this);
                    var $object = $wrapper.find('object');
                    
                    // Force redraw for better cross-browser compatibility
                    if ($object.length > 0) {
                        $object.hide().show(0);
                    }
                });
            }, 250);
        });
    });
    
})(jQuery);

