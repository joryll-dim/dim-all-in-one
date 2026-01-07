<?php

/**
 * Cron job functionality for automatic Instagram sync
 */

defined('ABSPATH') or die('No direct script access allowed');

class DIM_Instagram_Cron {

    /**
     * Schedule cron event
     */
    public static function schedule() {
        $sync_enabled = get_option('dim_instagram_sync_enabled', '0');
        $frequency = get_option('dim_instagram_sync_frequency', 'daily');

        if ($sync_enabled === '1') {
            if (!wp_next_scheduled('dim_instagram_sync_event')) {
                wp_schedule_event(time(), $frequency, 'dim_instagram_sync_event');
            }
        } else {
            self::unschedule();
        }
    }

    /**
     * Unschedule cron event
     */
    public static function unschedule() {
        $timestamp = wp_next_scheduled('dim_instagram_sync_event');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'dim_instagram_sync_event');
        }
    }

    /**
     * Sync posts via cron
     */
    public function sync_posts() {
        require_once plugin_dir_path(__FILE__) . 'class-api.php';

        $result = DIM_Instagram_API::sync_posts();

        if (is_wp_error($result)) {
            error_log('Instagram Feed Sync Error: ' . $result->get_error_message());
        } else {
            error_log(sprintf(
                'Instagram Feed Sync Complete: %d imported, %d updated, %d skipped',
                $result['imported'],
                $result['updated'],
                $result['skipped']
            ));
        }
    }

    /**
     * Add custom cron schedules
     */
    public static function add_cron_schedules($schedules) {
        if (!isset($schedules['weekly'])) {
            $schedules['weekly'] = array(
                'interval' => 604800,
                'display'  => __('Once Weekly', 'dim-instagram')
            );
        }

        if (!isset($schedules['twicedaily'])) {
            $schedules['twicedaily'] = array(
                'interval' => 43200,
                'display'  => __('Twice Daily', 'dim-instagram')
            );
        }

        return $schedules;
    }
}

// Register custom cron schedules
add_filter('cron_schedules', array('DIM_Instagram_Cron', 'add_cron_schedules'));
