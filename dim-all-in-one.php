<?php
/**
 * Plugin Name: DIM – All In One
 * Description: Modular All-in-One Plugin System
 * Version: 0.2.8
 * Author: Dental Funnels The Platform
 * Plugin URI: https://github.com/joryll-dim/dim-all-in-one
 */

if (!defined('ABSPATH')) exit;

define('DIM_PATH', plugin_dir_path(__FILE__));
define('DIM_URL', plugin_dir_url(__FILE__));
define('DIM_VERSION', '0.2.8');

require_once DIM_PATH . 'includes/module-manager.php';
require_once DIM_PATH . 'includes/module-loader.php';
require_once DIM_PATH . 'includes/admin-page.php';

// Update checker has been moved to a must-use plugin (mu-plugin/dim-update-checker.php)
// This ensures update checking works even when this plugin is deactivated
// See MU-PLUGIN-INSTALL.md for installation instructions
