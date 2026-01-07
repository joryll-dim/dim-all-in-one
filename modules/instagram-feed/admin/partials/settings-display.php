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

        // Output only the settings sections (not manual sync)
        global $wp_settings_sections, $wp_settings_fields;

        if (isset($wp_settings_sections['dim-instagram-settings'])) {
            foreach ((array) $wp_settings_sections['dim-instagram-settings'] as $section) {
                // Skip the manual sync and shortcode sections (they appear at the bottom)
                if ($section['id'] === 'dim_instagram_manual_section' || $section['id'] === 'dim_instagram_shortcode_section') {
                    continue;
                }

                if ($section['title']) {
                    echo "<h2>{$section['title']}</h2>\n";
                }

                if ($section['callback']) {
                    call_user_func($section['callback'], $section);
                }

                if (isset($wp_settings_fields['dim-instagram-settings'][$section['id']])) {
                    echo '<table class="form-table" role="presentation">';
                    do_settings_fields('dim-instagram-settings', $section['id']);
                    echo '</table>';
                }
            }
        }

        submit_button('Save Settings');
        ?>
    </form>

    <?php
    // Output manual sync and shortcode sections separately (outside the settings form)
    if (isset($wp_settings_sections['dim-instagram-settings'])) {
        foreach ((array) $wp_settings_sections['dim-instagram-settings'] as $section) {
            if ($section['id'] === 'dim_instagram_manual_section' || $section['id'] === 'dim_instagram_shortcode_section') {
                if ($section['title']) {
                    echo "<h2>{$section['title']}</h2>\n";
                }

                if ($section['callback']) {
                    call_user_func($section['callback'], $section);
                }
            }
        }
    }
    ?>
</div>
