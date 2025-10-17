/**
 * Web Embed Frontend JavaScript
 * 
 * Handles frontend embed enhancements and responsive behavior.
 *
 * @package WebEmbed
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    /**
     * Initialize embeds when DOM is ready
     */
    $(document).ready(function() {
        initEmbeds();
    });
    
    /**
     * Initialize all embeds on the page
     */
    function initEmbeds() {
        $('.web-embed-container').each(function() {
            const $container = $(this);
            enhanceEmbed($container);
        });
    }
    
    /**
     * Enhance individual embed
     */
    function enhanceEmbed($container) {
        // Add loading class
        $container.addClass('web-embed-loading');
        
        // Track fallback clicks for analytics (future feature)
        $container.find('.web-embed-fallback-button').on('click', function() {
            // Could send analytics event here in future
            console.log('Fallback button clicked');
        });
        
        // Remove loading class after a short delay
        setTimeout(function() {
            $container.removeClass('web-embed-loading');
        }, 500);
    }
    
    /**
     * Responsive embed height adjustment (if needed)
     * Currently handled by CSS, but can be enhanced here
     */
    function adjustResponsiveEmbeds() {
        $('.web-embed-responsive').each(function() {
            const $responsive = $(this);
            // Future: Could add dynamic aspect ratio adjustment
        });
    }
    
    // Adjust responsive embeds on window resize
    let resizeTimer;
    $(window).on('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            adjustResponsiveEmbeds();
        }, 250);
    });
    
})(jQuery);

