/**
 * Admin JavaScript for Context-Aware Animation plugin
 * Handles accordion functionality for effect settings
 */
(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Handle effect radio button changes
        $('.caa-effect-radio').on('change', function() {
            var selectedEffect = $(this).val();
            
            // Hide all accordions
            $('.caa-effect-accordion').slideUp(200);
            
            // Show the accordion for the selected effect
            $('.caa-effect-accordion[data-effect="' + selectedEffect + '"]').slideDown(200);
        });
        
        // Initialize: show accordion for currently selected effect
        var selectedEffect = $('.caa-effect-radio:checked').val();
        if (selectedEffect) {
            $('.caa-effect-accordion[data-effect="' + selectedEffect + '"]').show();
        }
    });
})(jQuery);

