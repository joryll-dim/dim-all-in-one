<?php

/**
 * Provide a admin area view for the plugin settings
 */

defined('ABSPATH') or die('No direct script access allowed');

?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <form method="post" action="options.php">
        <?php
        settings_fields('dim_instagram_settings');
        do_settings_sections('dim-instagram-settings');
        submit_button('Save Settings');
        ?>
    </form>
</div>
