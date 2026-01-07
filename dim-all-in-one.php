<?php
/**
 * Plugin Name: DIM – All In One
 * Description: Modular All-in-One Plugin System
 * Version: 0.2.2
 * Author: Dental Funnels The Platform
 * Plugin URI: https://github.com/joryll-dim/dim-all-in-one
 */

if (!defined('ABSPATH')) exit;

define('DIM_PATH', plugin_dir_path(__FILE__));
define('DIM_URL', plugin_dir_url(__FILE__));
define('DIM_VERSION', '0.2.2');

require_once DIM_PATH . 'includes/module-manager.php';
require_once DIM_PATH . 'includes/module-loader.php';
require_once DIM_PATH . 'includes/admin-page.php';

// Initialize Plugin Update Checker
require DIM_PATH . 'lib/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$dimUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/joryll-dim/dim-all-in-one',
    __FILE__,
    'dim-all-in-one'
);

// Optional: Set the branch that contains the stable release
$dimUpdateChecker->setBranch('main');
