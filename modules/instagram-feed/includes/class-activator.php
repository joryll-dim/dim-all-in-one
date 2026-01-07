<?php

/**
 * Fired during module activation
 */

defined('ABSPATH') or die('No direct script access allowed');

class DIM_Instagram_Activator {

    /**
     * Short Description.
     *
     * Long Description.
     */
    public static function activate() {
        // Register CPT first so we can flush rewrite rules
        require_once plugin_dir_path(__FILE__) . 'class-cpt.php';
        DIM_Instagram_CPT::register();
        DIM_Instagram_CPT::register_meta_fields();

        // Flush rewrite rules to register the new CPT permalink structure
        flush_rewrite_rules();

        // Set default options
        if (get_option('dim_instagram_api_key') === false) {
            update_option('dim_instagram_api_key', '');
        }
        if (get_option('dim_instagram_actor_id') === false) {
            update_option('dim_instagram_actor_id', '');
        }
        if (get_option('dim_instagram_username') === false) {
            update_option('dim_instagram_username', '');
        }
        if (get_option('dim_instagram_sync_enabled') === false) {
            update_option('dim_instagram_sync_enabled', '0');
        }
        if (get_option('dim_instagram_sync_frequency') === false) {
            update_option('dim_instagram_sync_frequency', 'daily');
        }
        if (get_option('dim_instagram_posts_limit') === false) {
            update_option('dim_instagram_posts_limit', '12');
        }
        if (get_option('dim_instagram_last_sync') === false) {
            update_option('dim_instagram_last_sync', '');
        }

        // Schedule cron if sync is enabled
        require_once plugin_dir_path(__FILE__) . 'class-cron.php';
        DIM_Instagram_Cron::schedule();
    }
}
