<?php

/**
 * The admin-specific functionality of the plugin
 */

defined('ABSPATH') or die('No direct script access allowed');

class DFP_Reviews_Admin {

    private $plugin_name;
    private $version;
    private $settings;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->settings = null;
    }

    public function enqueue_styles($hook) {
        if ($hook != 'settings_page_dfp-reviews-settings') {
            return;
        }

        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'css/admin.css',
            array(),
            $this->version,
            'all'
        );
    }

    public function enqueue_scripts($hook) {
        if ($hook != 'settings_page_dfp-reviews-settings') {
            return;
        }

        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'js/admin.js',
            array('jquery'),
            $this->version,
            false
        );
    }

    public function add_plugin_admin_menu() {
        add_options_page(
            'DFP Reviews Settings',
            'DFP Reviews',
            'manage_options',
            'dfp-reviews-settings',
            array($this, 'display_plugin_setup_page')
        );
    }

    public function display_plugin_setup_page() {
        // Security check: verify user has proper capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // Pass settings instance to the partial
        $settings_instance = $this->settings;
        include_once('partials/settings-display.php');
    }

    public function settings_init() {
        if ($this->settings === null) {
            if (!class_exists('DFP_Reviews_Settings')) {
                require_once plugin_dir_path(__FILE__) . 'class-settings.php';
            }
            $this->settings = new DFP_Reviews_Settings($this->plugin_name, $this->version);
        }
        $this->settings->initialize_settings();
    }

    public function get_settings() {
        return $this->settings;
    }
}