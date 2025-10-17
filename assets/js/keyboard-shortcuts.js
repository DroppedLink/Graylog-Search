/**
 * Keyboard Shortcuts for Graylog Search
 * - Ctrl+Enter: Submit search
 * - Esc: Clear search and close popups
 * - /: Focus search box
 */

jQuery(document).ready(function($) {
    
    // Handle keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ignore if user is typing in an input/textarea (except for specific shortcuts)
        var isInputField = $(e.target).is('input, textarea, select');
        
        // / to focus search box (unless already in an input)
        if (e.key === '/' && !isInputField) {
            e.preventDefault();
            $('#search_terms, .search-terms').first().focus();
            return;
        }
        
        // Esc to clear/close
        if (e.key === 'Escape') {
            e.preventDefault();
            
            // Close any popups
            $('.filter-popup').remove();
            $('.row-actions-dropdown').hide();
            
            // Clear selection
            if (window.getSelection) {
                window.getSelection().removeAllRanges();
            }
            
            // If in search field, clear it
            if ($(e.target).is('#search_terms, .search-terms, #search_fqdn, .search-fqdn, #filter_out, .filter-out')) {
                $(e.target).val('').focus();
            }
            return;
        }
        
        // Ctrl+Enter (or Cmd+Enter on Mac) to submit search
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            
            // Find and trigger the search form
            var $form = $('#graylog-search-form').first();
            if ($form.length === 0) {
                $form = $('.graylog-search-form').first();
            }
            
            if ($form.length > 0) {
                $form.trigger('submit');
            }
            return;
        }
    });
    
    // Show keyboard shortcuts help on ? key
    $(document).on('keydown', function(e) {
        if (e.key === '?' && e.shiftKey && !$(e.target).is('input, textarea, select')) {
            e.preventDefault();
            showKeyboardShortcutsHelp();
        }
    });
    
    // Show keyboard shortcuts modal
    function showKeyboardShortcutsHelp() {
        var isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
        var modKey = isMac ? '⌘' : 'Ctrl';
        
        var helpHtml = `
            <div class="keyboard-shortcuts-modal" style="
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: white;
                padding: 30px;
                border-radius: 8px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.3);
                z-index: 100000;
                max-width: 500px;
                width: 90%;
            ">
                <h2 style="margin-top: 0;">Keyboard Shortcuts</h2>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 8px; font-weight: bold;">${modKey} + Enter</td>
                        <td style="padding: 8px;">Submit search</td>
                    </tr>
                    <tr style="background: #f5f5f5;">
                        <td style="padding: 8px; font-weight: bold;">Esc</td>
                        <td style="padding: 8px;">Clear field / Close popups</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; font-weight: bold;">/</td>
                        <td style="padding: 8px;">Focus search terms field</td>
                    </tr>
                    <tr style="background: #f5f5f5;">
                        <td style="padding: 8px; font-weight: bold;">?</td>
                        <td style="padding: 8px;">Show this help</td>
                    </tr>
                </table>
                <button class="button button-primary" style="margin-top: 20px; width: 100%;" onclick="jQuery('.keyboard-shortcuts-modal, .keyboard-shortcuts-overlay').remove()">
                    Got it!
                </button>
            </div>
            <div class="keyboard-shortcuts-overlay" style="
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 99999;
            " onclick="jQuery('.keyboard-shortcuts-modal, .keyboard-shortcuts-overlay').remove()"></div>
        `;
        
        $('body').append(helpHtml);
    }
    
    // Add keyboard shortcuts indicator to search form
    if ($('#graylog-search-form, .graylog-search-form').length > 0) {
        var $searchButton = $('#graylog-search-form .search-button, .graylog-search-form .search-button').first();
        if ($searchButton.length > 0) {
            var isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
            var modKey = isMac ? '⌘' : 'Ctrl';
            $searchButton.attr('title', 'Search (' + modKey + '+Enter)');
        }
        
        // Add help icon
        var helpIcon = '<span class="keyboard-shortcuts-help" style="cursor: pointer; color: #666; font-size: 16px; margin-left: 10px;" title="Keyboard shortcuts (?)">⌨️</span>';
        $searchButton.after(helpIcon);
        
        $(document).on('click', '.keyboard-shortcuts-help', function(e) {
            e.preventDefault();
            showKeyboardShortcutsHelp();
        });
    }
});

