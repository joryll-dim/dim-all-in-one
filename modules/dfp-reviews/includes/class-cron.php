<?php

/**
 * Cron job management
 */

defined('ABSPATH') or die('No direct script access allowed');

class DFP_Reviews_Cron {

    public function __construct() {
        $this->init_hooks();
    }

    private function init_hooks() {
        // Register cron hooks for up to 10 clinics
        for ($source = 1; $source <= 10; $source++) {
            add_action("dfp_reviews_cron_hook_{$source}", array($this, 'execute_cron_job'));
        }
    }

    public function execute_cron_job($source) {
        require_once plugin_dir_path(__FILE__) . 'class-api.php';
        $result = DFP_Reviews_API::get_data_from_outscraper($source, true);

        // Store cron job result for admin review
        $cron_log = get_option('dfp_reviews_cron_log', array());
        $clinic_id = get_option("dfp_reviews_clinic_id_{$source}", "Clinic {$source}");

        $cron_log[$source] = array(
            'timestamp' => current_time('mysql'),
            'success' => $result,
            'clinic_id' => $clinic_id,
            'reviews' => $result ? get_option("dfp_reviews_total_reviews_{$source}", 0) : 0,
            'rating' => $result ? get_option("dfp_reviews_total_stars_{$source}", 0) : 0
        );
        update_option('dfp_reviews_cron_log', $cron_log);

        // Update total stats
        $total_stats = DFP_Reviews_API::calculate_total_reviews();
        update_option('dfp_reviews_total_stats', $total_stats);
    }

    public static function schedule_cron_jobs() {
        $clinic_count = max(1, get_option('dfp_reviews_clinic_count', 1));

        for ($source = 1; $source <= $clinic_count; $source++) {
            if (get_option("dfp_reviews_enable_source_{$source}", 1)) {
                $frequency = get_option("dfp_reviews_update_frequency_{$source}", "weekly");
                self::remove_cron_job($source);

                if ($frequency !== 'manual') {
                    if (!wp_next_scheduled("dfp_reviews_cron_hook_{$source}")) {
                        wp_schedule_event(time(), $frequency, "dfp_reviews_cron_hook_{$source}", array($source));
                    }
                }
            } else {
                self::remove_cron_job($source);
            }
        }
    }

    public static function remove_cron_job($source) {
        if (!isset($source) || !is_int($source)) {
            return;
        }

        $timestamp = wp_next_scheduled("dfp_reviews_cron_hook_{$source}");
        if ($timestamp) {
            wp_unschedule_event($timestamp, "dfp_reviews_cron_hook_{$source}", array($source));
        }
    }
}