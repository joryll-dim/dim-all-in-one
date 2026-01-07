<?php

/**
 * The core plugin class
 */

defined('ABSPATH') or die('No direct script access allowed');

class DIM_Instagram_Feed {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     */
    public function __construct() {
        $this->version = defined('DIM_INSTAGRAM_VERSION') ? DIM_INSTAGRAM_VERSION : '1.0.0';
        $this->plugin_name = 'instagram-feed';

        $this->load_dependencies();
        $this->register_cpt();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     */
    private function load_dependencies() {
        /**
         * The class responsible for orchestrating the actions and filters of the core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-loader.php';

        /**
         * The class responsible for defining the CPT
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-cpt.php';

        /**
         * The class responsible for API interactions
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-api.php';

        /**
         * The class responsible for cron jobs
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-cron.php';

        /**
         * The class responsible for shortcodes
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-shortcode.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-admin.php';

        /**
         * Create an instance of the loader which will be used to register the hooks with WordPress.
         */
        $this->loader = new DIM_Instagram_Loader();
    }

    /**
     * Register the Custom Post Type
     */
    private function register_cpt() {
        $this->loader->add_action('init', 'DIM_Instagram_CPT', 'register', 10);
        $this->loader->add_action('init', 'DIM_Instagram_CPT', 'register_meta_fields', 11);

        // Admin columns
        $this->loader->add_filter('manage_instagram_posts_posts_columns', 'DIM_Instagram_CPT', 'add_admin_columns');
        $this->loader->add_action('manage_instagram_posts_posts_custom_column', 'DIM_Instagram_CPT', 'display_admin_columns', 10, 2);
        $this->loader->add_filter('manage_edit-instagram_posts_sortable_columns', 'DIM_Instagram_CPT', 'make_columns_sortable');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     */
    private function define_admin_hooks() {
        $plugin_admin = new DIM_Instagram_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
        $this->loader->add_action('admin_init', $plugin_admin, 'settings_init');
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     */
    private function define_public_hooks() {
        // Initialize shortcodes
        new DIM_Instagram_Shortcode();

        // Initialize cron
        $cron = new DIM_Instagram_Cron();
        $this->loader->add_action('dim_instagram_sync_event', $cron, 'sync_posts');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * Retrieve the version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
}
