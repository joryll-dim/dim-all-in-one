<?php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', function () {
    add_menu_page(
        'DIM Plugin',
        'DIM Plugin',
        'manage_options',
        'dim-plugin',
        'dim_admin_page',
        'dashicons-admin-plugins'
    );
});

add_action('wp_ajax_dim_dismiss_test_module_notice', function () {

    update_user_meta(
        get_current_user_id(),
        'dim_test_module_notice_dismissed',
        1
    );

    wp_die();
});


function dim_admin_page() {
    $redirect_after_save = false;
    $kill_switch_active = defined('DIM_KILL_SWITCH') && DIM_KILL_SWITCH === true;

    if (isset($_POST['dim_save'])) {
        check_admin_referer('dim_save_modules');
        DIM_Module_Manager::save($_POST['modules'] ?? []);
        $redirect_after_save = true;
    }

    $modules = DIM_Module_Manager::get_all_modules();
    $enabled = DIM_Module_Manager::get_enabled_modules();

    // Redirect after save to refresh the page with updated modules
    if ($redirect_after_save) {
        echo '<div class="updated notice is-dismissible"><p>Modules updated successfully!</p></div>';
        echo '<script type="text/javascript">
            setTimeout(function() {
                window.location.href = window.location.href;
            }, 500);
        </script>';
    }
    ?>

    <div class="wrap">
        <h1>DIM Modules</h1>

        <?php if ($kill_switch_active): ?>
        <div class="notice notice-error" style="border-left-color: #dc3232; padding: 15px; margin: 20px 0;">
            <p style="margin: 0; font-size: 14px;">
                <span class="dashicons dashicons-warning" style="color: #dc3232; font-size: 20px; vertical-align: middle;"></span>
                <strong style="color: #dc3232;">EMERGENCY KILL SWITCH ACTIVE</strong> - All modules are currently disabled.
            </p>
            <p style="margin: 10px 0 0 0; font-size: 13px;">
                The constant <code>DIM_KILL_SWITCH</code> is defined in your <code>wp-config.php</code> file.
                To re-enable modules, remove or comment out this line and refresh the page.
            </p>
            <p style="margin: 10px 0 0 0; font-size: 12px; color: #666;">
                <a href="<?php echo esc_url(admin_url('admin.php?page=dim-plugin')); ?>" style="text-decoration: none;">
                    📖 View KILL-SWITCH.md for instructions
                </a>
            </p>
        </div>
        <?php endif; ?>

        <form method="post">
            <?php wp_nonce_field('dim_save_modules'); ?>

            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th>Plugin Name</th>
                        <th>Status</th>
                        <th>Size</th>
                    </tr>
                </thead>
                <tbody>

                <?php foreach ($modules as $key => $module):
                    $size = file_exists($module['file'])
                        ? round(filesize($module['file']) / 1024, 2) . ' KB'
                        : '-';
                ?>
                    <tr<?php echo $kill_switch_active ? ' style="opacity: 0.5;"' : ''; ?>>
                        <td><?php echo esc_html($module['label']); ?></td>
                        <td>
                            <label class="dim-switch">
                                <input type="checkbox" name="modules[<?php echo esc_attr($key); ?>]"
                                    <?php checked($enabled[$key] ?? false); ?>
                                    <?php disabled($kill_switch_active); ?>>
                                <span class="slider"></span>
                            </label>
                            <?php if ($kill_switch_active): ?>
                                <span style="font-size: 11px; color: #dc3232; margin-left: 10px;">Disabled by kill switch</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($size); ?></td>
                    </tr>
                <?php endforeach; ?>

                </tbody>
            </table>

            <p>
                <button class="button button-primary" name="dim_save" <?php disabled($kill_switch_active); ?>>
                    Save Changes
                </button>
                <?php if ($kill_switch_active): ?>
                    <span style="color: #dc3232; margin-left: 10px; font-size: 13px;">
                        Changes cannot be saved while kill switch is active
                    </span>
                <?php endif; ?>
            </p>
        </form>
    </div>

    <style>
    .dim-switch {
        position: relative;
        display: inline-block;
        width: 46px;
        height: 22px;
    }
    .dim-switch input { display:none; }
    .slider {
        position: absolute;
        cursor: pointer;
        background-color: #ccc;
        border-radius: 22px;
        inset: 0;
        transition: .3s;
    }
    .slider:before {
        content: "";
        position: absolute;
        height: 18px;
        width: 18px;
        left: 2px;
        bottom: 2px;
        background: #fff;
        border-radius: 50%;
        transition: .3s;
    }
    input:checked + .slider {
        background-color: #0073aa;
    }
    input:checked + .slider:before {
        transform: translateX(24px);
    }
    </style>

<?php
}
