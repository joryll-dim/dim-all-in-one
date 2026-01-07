<?php
/**
 * Module Name: DIM Google Reviews
 * Description: Display Google Business reviews and ratings on your website with seamless integration to the Outscraper API.
 * Version: 2.6.1
 * Author: Dental Funnels The Platform
 */

defined('ABSPATH') or die('No direct script access allowed');

// Security: Check minimum requirements
if (!function_exists('add_action')) {
    exit('WordPress is required.');
}

// Define module constants
if (!defined('DFP_REVIEWS_VERSION')) {
    define('DFP_REVIEWS_VERSION', '2.6.1');
}
if (!defined('DFP_REVIEWS_PLUGIN_NAME')) {
    define('DFP_REVIEWS_PLUGIN_NAME', 'dfp-reviews');
}
if (!defined('DFP_REVIEWS_PLUGIN_DIR')) {
    define('DFP_REVIEWS_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (!defined('DFP_REVIEWS_PLUGIN_URL')) {
    define('DFP_REVIEWS_PLUGIN_URL', plugin_dir_url(__FILE__));
}

// Include Composer autoloader for dependencies
$autoload_path = plugin_dir_path(__FILE__) . 'vendor/autoload.php';
if (file_exists($autoload_path)) {
    require_once($autoload_path);
} else {
    // Show admin notice if dependencies are missing
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p><strong>DFP Reviews Module:</strong> Missing dependencies. Please run <code>composer install</code> in the module directory.</p></div>';
    });
    return;
}

/**
 * One-time setup on first module enable
 * This runs on 'init' hook to ensure WordPress is fully loaded
 */
add_action('init', function() {
    if (get_option('dfp_reviews_module_activated') !== 'yes') {
        require_once plugin_dir_path(__FILE__) . 'includes/class-activator.php';
        DFP_Reviews_Activator::activate();
        update_option('dfp_reviews_module_activated', 'yes');
    }
}, 5); // Priority 5 to run before CPT registration at priority 10

/**
 * The core plugin class
 */
require plugin_dir_path(__FILE__) . 'includes/class-dfp-reviews.php';

/**
 * Begin execution of the module
 */
function run_dfp_reviews() {
    $plugin = new DFP_Reviews();
    $plugin->run();
}

run_dfp_reviews();