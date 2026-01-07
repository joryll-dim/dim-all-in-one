<?php
/**
 * DIM Plugin Update Checker (Must-Use Plugin)
 *
 * This must-use plugin ensures update checking works even when
 * the main DIM plugin is deactivated due to errors.
 *
 * Installation: Copy this file to wp-content/mu-plugins/
 */

// Only run if the main plugin file exists
$dim_plugin_file = WP_PLUGIN_DIR . '/dim-all-in-one/dim-all-in-one.php';

if (!file_exists($dim_plugin_file)) {
    return; // Main plugin not installed
}

// Load Plugin Update Checker library from main plugin
$puc_path = WP_PLUGIN_DIR . '/dim-all-in-one/lib/plugin-update-checker.php';

if (!file_exists($puc_path)) {
    return; // Library not found
}

require_once $puc_path;

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

// Initialize update checker
$dimUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/joryll-dim/dim-all-in-one',
    $dim_plugin_file,
    'dim-all-in-one'
);

// Set the branch that contains the stable release
$dimUpdateChecker->setBranch('main');

// Add notice if main plugin is deactivated but updates are available
add_action('admin_notices', function() use ($dim_plugin_file) {
    // Check if main plugin is inactive
    if (!is_plugin_active('dim-all-in-one/dim-all-in-one.php')) {
        // Check if there's an update available
        $update_info = get_site_transient('update_plugins');

        if (isset($update_info->response['dim-all-in-one/dim-all-in-one.php'])) {
            $update = $update_info->response['dim-all-in-one/dim-all-in-one.php'];
            ?>
            <div class="notice notice-warning">
                <p>
                    <strong>DIM Plugin Update Available:</strong>
                    Version <?php echo esc_html($update->new_version); ?> is available.
                    The plugin is currently deactivated, but you can still update it from the
                    <a href="<?php echo admin_url('plugins.php'); ?>">Plugins page</a>.
                </p>
            </div>
            <?php
        }
    }
});
