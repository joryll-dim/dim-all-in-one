<?php

/**
 * Fired during plugin activation
 */

defined('ABSPATH') or die('No direct script access allowed');

class DFP_Reviews_Activator {

    public static function activate() {
        // Register custom post type
        require_once DFP_REVIEWS_PLUGIN_DIR . 'includes/class-cpt.php';
        DFP_Reviews_CPT::register();
        DFP_Reviews_CPT::register_meta_fields();

        // Flush rewrite rules to ensure CPT permalinks work
        flush_rewrite_rules();

        // Initialize default options
        if (!get_option('dfp_reviews_clinic_count')) {
            update_option('dfp_reviews_clinic_count', 1);
        }

        // Schedule cron jobs
        self::schedule_cron_jobs();
    }

    private static function schedule_cron_jobs() {
        $clinic_count = max(1, get_option('dfp_reviews_clinic_count', 1));

        for ($source = 1; $source <= $clinic_count; $source++) {
            $frequency = get_option("dfp_reviews_update_frequency_{$source}", "weekly");

            if ($frequency !== 'manual') {
                if (!wp_next_scheduled("dfp_reviews_cron_hook_{$source}")) {
                    wp_schedule_event(time(), $frequency, "dfp_reviews_cron_hook_{$source}", array($source));
                }
            }
        }
    }
}