/**
 * Custom Food and Drink Menu Popup Enhancements
 * This file adds enhanced functionality to the ordering popup
 * Safe from plugin updates
 */

jQuery(document).ready(function($) {
    
    // Add loading state to submit button
    function addLoadingState() {
        $('#fdm-ordering-popup-submit button').on('click', function() {
            $(this).addClass('loading');
        });
    }
    
    // Remove loading state when popup closes
    function removeLoadingState() {
        $('.fdm-ordering-popup-close, .fdm-ordering-popup-background').on('click', function() {
            $('#fdm-ordering-popup-submit button').removeClass('loading');
        });
    }
    
    // Add placeholder text to textarea
    function addPlaceholderText() {
        $('#fdm-ordering-popup-note textarea').attr('placeholder', 'Any special instructions or notes for your order...');
    }
    
    // Enhance popup opening animation
    function enhancePopupAnimation() {
        // Override the original popup display function
        var originalDisplay = window.fdm_display_order_details_popup;
        if (typeof originalDisplay === 'function') {
            window.fdm_display_order_details_popup = function(post_id) {
                // Call the original function
                originalDisplay(post_id);
                
                // Add our enhancements
                setTimeout(function() {
                    addPlaceholderText();
                    addLoadingState();
                    removeLoadingState();
                }, 100);
            };
        }
    }
    
    // Smooth scroll for mobile popup
    function improveMobileExperience() {
        if ($(window).width() <= 768) {
            $('.fdm-ordering-popup-inside').on('touchstart', function(e) {
                e.stopPropagation();
            });
        }
    }
    
    // Add keyboard navigation
    function addKeyboardNavigation() {
        $(document).on('keydown', function(e) {
            if ($('.fdm-ordering-popup').is(':visible') && !$('.fdm-ordering-popup').hasClass('fdm-hidden')) {
                // Escape key closes popup
                if (e.keyCode === 27) {
                    $('.fdm-ordering-popup-close').click();
                }
                // Enter key submits (if not in textarea)
                if (e.keyCode === 13 && !$(e.target).is('textarea')) {
                    e.preventDefault();
                    $('#fdm-ordering-popup-submit button').click();
                }
            }
        });
    }
    
    // Improve form validation visual feedback
    function enhanceFormValidation() {
        $('#fdm-ordering-popup-options input, #fdm-ordering-popup-options select').on('change', function() {
            $(this).removeClass('error');
            $(this).closest('.fdm-ordering-popup-option').removeClass('error');
        });
    }
    
    // Add focus management for accessibility
    function improveFocusManagement() {
        // When popup opens, focus on first input
        $(document).on('DOMNodeInserted', '.fdm-ordering-popup', function() {
            if (!$(this).hasClass('fdm-hidden')) {
                setTimeout(function() {
                    $('.fdm-ordering-popup-inside input:first, .fdm-ordering-popup-inside select:first').focus();
                }, 300);
            }
        });
        
        // Trap focus within popup
        $('.fdm-ordering-popup-inside').on('keydown', function(e) {
            if (e.keyCode === 9) { // Tab key
                var focusableElements = $(this).find('input, select, textarea, button').filter(':visible');
                var firstElement = focusableElements.first();
                var lastElement = focusableElements.last();
                
                if (e.shiftKey) {
                    if ($(e.target).is(firstElement)) {
                        e.preventDefault();
                        lastElement.focus();
                    }
                } else {
                    if ($(e.target).is(lastElement)) {
                        e.preventDefault();
                        firstElement.focus();
                    }
                }
            }
        });
    }
    
    // Initialize all enhancements
    function initializeEnhancements() {
        enhancePopupAnimation();
        improveMobileExperience();
        addKeyboardNavigation();
        enhanceFormValidation();
        improveFocusManagement();
        
        // Re-initialize when popup is shown
        $(document).on('click', '.fdm-options-add-to-cart-button', function() {
            setTimeout(function() {
                addPlaceholderText();
                addLoadingState();
                removeLoadingState();
            }, 200);
        });
    }
    
    // Run initialization
    initializeEnhancements();
    
    // Debug logging (remove in production)
    if (window.console && window.console.log) {
        console.log('FDM Popup Enhancements loaded successfully');
    }
});

// CSS-in-JS for dynamic styles (fallback)
if (typeof jQuery !== 'undefined') {
    jQuery(document).ready(function($) {
        // Add dynamic styles that can't be handled in CSS
        $('<style type="text/css">')
            .html(`
                .fdm-ordering-popup-option.error input,
                .fdm-ordering-popup-option.error select {
                    border-color: #e74c3c !important;
                    box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1) !important;
                }
                
                .fdm-ordering-popup-option.error {
                    border-color: #e74c3c !important;
                    background-color: #fdf2f2 !important;
                }
                
                @media (prefers-reduced-motion: reduce) {
                    .fdm-ordering-popup-inside {
                        animation: none !important;
                    }
                    
                    * {
                        transition: none !important;
                    }
                }
            `)
            .appendTo('head');
    });
}
