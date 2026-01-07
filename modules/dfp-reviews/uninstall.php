<?php

/**
 * Fired when the plugin is uninstalled
 */

// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete all plugin options
$clinic_count = get_option('dfp_reviews_clinic_count', 1);
for ($i = 1; $i <= $clinic_count; $i++) {
    delete_option("dfp_reviews_id_place_{$i}");
    delete_option("dfp_reviews_update_frequency_{$i}");
    delete_option("dfp_reviews_total_reviews_{$i}");
    delete_option("dfp_reviews_total_stars_{$i}");
    delete_option("dfp_reviews_clinic_id_{$i}");
    delete_option("dfp_reviews_enable_source_{$i}");
}

delete_option('dfp_reviews_clinic_count');
delete_option('dfp_reviews_api_key');
delete_option('dfp_reviews_reviews_limit');
delete_option('dfp_reviews_cron_log');
delete_option('dfp_reviews_total_stats');

// Remove all testimonial posts created by this plugin
$testimonials = get_posts(array(
    'post_type' => 'testimonials',
    'meta_query' => array(
        array(
            'key' => 'review_id',
            'compare' => 'EXISTS'
        )
    ),
    'posts_per_page' => -1
));

foreach ($testimonials as $testimonial) {
    wp_delete_post($testimonial->ID, true);
}

// Clear any remaining scheduled events
for ($source = 1; $source <= 10; $source++) {
    $timestamp = wp_next_scheduled("dfp_reviews_cron_hook_{$source}");
    if ($timestamp) {
        wp_unschedule_event($timestamp, "dfp_reviews_cron_hook_{$source}", array($source));
    }
}