<?php

/**
 * Fired during plugin deactivation
 */

defined('ABSPATH') or die('No direct script access allowed');

class DFP_Reviews_Deactivator {

    public static function deactivate() {
        self::clear_scheduled_cron_jobs();
    }

    private static function clear_scheduled_cron_jobs() {
        $clinic_count = max(1, get_option('dfp_reviews_clinic_count', 1));

        for ($source = 1; $source <= $clinic_count; $source++) {
            $timestamp = wp_next_scheduled("dfp_reviews_cron_hook_{$source}");
            if ($timestamp) {
                wp_unschedule_event($timestamp, "dfp_reviews_cron_hook_{$source}", array($source));
            }
        }
    }
}