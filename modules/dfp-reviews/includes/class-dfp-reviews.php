<?php

/**
 * The file that defines the core plugin class
 */

defined('ABSPATH') or die('No direct script access allowed');

class DFP_Reviews {

    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        if (defined('DFP_REVIEWS_VERSION')) {
            $this->version = DFP_REVIEWS_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'dfp-reviews';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-loader.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-api.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-cron.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-shortcode.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-admin.php';

        $this->loader = new DFP_Reviews_Loader();
    }

    private function set_locale() {
        // No translations needed
    }

    private function define_admin_hooks() {
        $plugin_admin = new DFP_Reviews_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
        $this->loader->add_action('admin_init', $plugin_admin, 'settings_init');
    }

    private function define_public_hooks() {
        // No public hooks needed
    }


    public function run() {
        $this->loader->run();

        // Initialize components
        new DFP_Reviews_Shortcode();
        new DFP_Reviews_Cron();
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_loader() {
        return $this->loader;
    }

    public function get_version() {
        return $this->version;
    }
}