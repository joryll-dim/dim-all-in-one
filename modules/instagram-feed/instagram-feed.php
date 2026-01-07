<?php
/**
 * Module Name: Instagram Feed
 * Description: Display your Instagram feed on your website with automatic sync
 * Version: 1.0.0
 * Author: Dental Funnels The Platform
 */

defined('ABSPATH') or die('No direct script access allowed');

// Security: Check minimum requirements
if (!function_exists('add_action')) {
    exit('WordPress is required.');
}

// Define module constants
if (!defined('DIM_INSTAGRAM_VERSION')) {
    define('DIM_INSTAGRAM_VERSION', '1.0.0');
}
if (!defined('DIM_INSTAGRAM_PLUGIN_NAME')) {
    define('DIM_INSTAGRAM_PLUGIN_NAME', 'instagram-feed');
}
if (!defined('DIM_INSTAGRAM_PLUGIN_DIR')) {
    define('DIM_INSTAGRAM_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (!defined('DIM_INSTAGRAM_PLUGIN_URL')) {
    define('DIM_INSTAGRAM_PLUGIN_URL', plugin_dir_url(__FILE__));
}

// Include Composer autoloader if it exists (optional - no external dependencies required)
$autoload_path = plugin_dir_path(__FILE__) . 'vendor/autoload.php';
if (file_exists($autoload_path)) {
    require_once($autoload_path);
}

/**
 * One-time setup on first module enable
 * This runs on 'init' hook to ensure WordPress is fully loaded
 */
add_action('init', function() {
    if (get_option('dim_instagram_module_activated') !== 'yes') {
        require_once plugin_dir_path(__FILE__) . 'includes/class-activator.php';
        DIM_Instagram_Activator::activate();
        update_option('dim_instagram_module_activated', 'yes');
    }
}, 5); // Priority 5 to run before CPT registration at priority 10

/**
 * The core plugin class
 */
require plugin_dir_path(__FILE__) . 'includes/class-instagram-feed.php';

/**
 * Begin execution of the module
 */
function run_dim_instagram() {
    $plugin = new DIM_Instagram_Feed();
    $plugin->run();
}

run_dim_instagram();
