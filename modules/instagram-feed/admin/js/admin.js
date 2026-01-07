(function($) {
    'use strict';

    /**
     * Admin JavaScript for Instagram Feed Module
     */

    $(document).ready(function() {
        // Handle sync enabled checkbox
        $('#dim_instagram_sync_enabled').on('change', function() {
            if ($(this).is(':checked')) {
                $('#dim_instagram_sync_frequency').prop('disabled', false);
            } else {
                $('#dim_instagram_sync_frequency').prop('disabled', true);
            }
        }).trigger('change');

        // Confirm manual sync
        $('button[name="dim_instagram_manual_sync"]').on('click', function(e) {
            if (!confirm('This will fetch posts from Instagram via Apify. Continue?')) {
                e.preventDefault();
                return false;
            }
        });
    });

})(jQuery);
