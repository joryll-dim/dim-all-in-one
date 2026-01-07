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
                    <tr>
                        <td><?php echo esc_html($module['label']); ?></td>
                        <td>
                            <label class="dim-switch">
                                <input type="checkbox" name="modules[<?php echo esc_attr($key); ?>]"
                                    <?php checked($enabled[$key] ?? false); ?>>
                                <span class="slider"></span>
                            </label>
                        </td>
                        <td><?php echo esc_html($size); ?></td>
                    </tr>
                <?php endforeach; ?>

                </tbody>
            </table>

            <p>
                <button class="button button-primary" name="dim_save">Save Changes</button>
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
