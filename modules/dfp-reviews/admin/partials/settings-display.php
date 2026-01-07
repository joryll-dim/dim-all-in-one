<?php

/**
 * Provide a admin area view for the plugin settings
 *
 * @since      1.0.0
 */

defined('ABSPATH') or die('No direct script access allowed');

// Use the settings instance passed from the admin class
if (isset($settings_instance) && $settings_instance !== null) {
    $settings_instance->display_settings_page();
} else {
    // Fallback if settings instance is not available
    echo '<div class="notice notice-error"><p>Settings could not be loaded. Please refresh the page.</p></div>';
}