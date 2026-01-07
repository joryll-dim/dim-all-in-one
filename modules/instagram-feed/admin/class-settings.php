<?php

/**
 * Settings page functionality
 */

defined('ABSPATH') or die('No direct script access allowed');

class DIM_Instagram_Settings {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function initialize_settings() {
        // Register settings with sanitization callbacks
        register_setting('dim_instagram_settings', 'dim_instagram_api_key');
        register_setting('dim_instagram_settings', 'dim_instagram_actor_id');
        register_setting('dim_instagram_settings', 'dim_instagram_username');
        register_setting('dim_instagram_settings', 'dim_instagram_sync_enabled', array($this, 'sanitize_sync_enabled'));
        register_setting('dim_instagram_settings', 'dim_instagram_sync_frequency', array($this, 'sanitize_sync_frequency'));
        register_setting('dim_instagram_settings', 'dim_instagram_posts_limit');

        // API Settings Section
        add_settings_section(
            'dim_instagram_api_section',
            'Apify API Configuration',
            array($this, 'api_section_callback'),
            'dim-instagram-settings'
        );

        add_settings_field(
            'dim_instagram_api_key',
            'Apify API Key',
            array($this, 'api_key_callback'),
            'dim-instagram-settings',
            'dim_instagram_api_section'
        );

        add_settings_field(
            'dim_instagram_actor_id',
            'Apify Actor ID',
            array($this, 'actor_id_callback'),
            'dim-instagram-settings',
            'dim_instagram_api_section'
        );

        add_settings_field(
            'dim_instagram_username',
            'Instagram Username',
            array($this, 'username_callback'),
            'dim-instagram-settings',
            'dim_instagram_api_section'
        );

        // Sync Settings Section
        add_settings_section(
            'dim_instagram_sync_section',
            'Automatic Sync Settings',
            array($this, 'sync_section_callback'),
            'dim-instagram-settings'
        );

        add_settings_field(
            'dim_instagram_sync_enabled',
            'Enable Auto-Sync',
            array($this, 'sync_enabled_callback'),
            'dim-instagram-settings',
            'dim_instagram_sync_section'
        );

        add_settings_field(
            'dim_instagram_sync_frequency',
            'Sync Frequency',
            array($this, 'sync_frequency_callback'),
            'dim-instagram-settings',
            'dim_instagram_sync_section'
        );

        add_settings_field(
            'dim_instagram_posts_limit',
            'Posts Limit',
            array($this, 'posts_limit_callback'),
            'dim-instagram-settings',
            'dim_instagram_sync_section'
        );

        // Manual Sync Section
        add_settings_section(
            'dim_instagram_manual_section',
            'Manual Sync',
            array($this, 'manual_section_callback'),
            'dim-instagram-settings'
        );

        // Shortcode Info Section
        add_settings_section(
            'dim_instagram_shortcode_section',
            'Shortcode Information',
            array($this, 'shortcode_section_callback'),
            'dim-instagram-settings'
        );
    }

    // Section Callbacks
    public function api_section_callback() {
        echo '<p>Configure your Apify API credentials to fetch Instagram posts. <a href="https://apify.com/" target="_blank">Get your API key from Apify</a>.</p>';
    }

    public function sync_section_callback() {
        $last_sync = get_option('dim_instagram_last_sync');
        if ($last_sync) {
            echo '<p>Configure automatic syncing of your Instagram feed. Last sync: <strong>' . esc_html($last_sync) . '</strong></p>';
        } else {
            echo '<p>Configure automatic syncing of your Instagram feed. No sync has been performed yet.</p>';
        }
    }

    public function manual_section_callback() {
        echo '<p>Manually trigger a sync of your Instagram posts.</p>';
        $this->display_manual_sync_button();
    }

    public function shortcode_section_callback() {
        $this->display_shortcode_info();
    }

    // Field Callbacks
    public function api_key_callback() {
        $value = get_option('dim_instagram_api_key', '');
        echo '<input type="password" id="dim_instagram_api_key" name="dim_instagram_api_key" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">Your Apify API token</p>';
    }

    public function actor_id_callback() {
        $value = get_option('dim_instagram_actor_id', '');
        echo '<input type="text" id="dim_instagram_actor_id" name="dim_instagram_actor_id" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">The Apify Actor ID for Instagram scraping (e.g., apify/instagram-scraper)</p>';
    }

    public function username_callback() {
        $value = get_option('dim_instagram_username', '');
        echo '<input type="text" id="dim_instagram_username" name="dim_instagram_username" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">Your Instagram username (without @)</p>';
    }

    public function sync_enabled_callback() {
        $value = get_option('dim_instagram_sync_enabled', '0');
        echo '<label><input type="checkbox" id="dim_instagram_sync_enabled" name="dim_instagram_sync_enabled" value="1" ' . checked(1, $value, false) . ' /> Enable automatic sync</label>';
        echo '<p class="description">When enabled, Instagram posts will be synced automatically based on the frequency setting.</p>';
    }

    public function sync_frequency_callback() {
        $value = get_option('dim_instagram_sync_frequency', 'daily');
        $frequencies = array(
            'hourly' => 'Hourly',
            'twicedaily' => 'Twice Daily',
            'daily' => 'Daily',
            'weekly' => 'Weekly',
        );
        echo '<select id="dim_instagram_sync_frequency" name="dim_instagram_sync_frequency">';
        foreach ($frequencies as $key => $label) {
            echo '<option value="' . esc_attr($key) . '" ' . selected($value, $key, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">How often to automatically sync Instagram posts.</p>';
    }

    public function posts_limit_callback() {
        $value = get_option('dim_instagram_posts_limit', '12');
        echo '<input type="number" id="dim_instagram_posts_limit" name="dim_instagram_posts_limit" value="' . esc_attr($value) . '" min="1" max="50" class="small-text" />';
        echo '<p class="description">Maximum number of posts to fetch (1-50)</p>';
    }

    // Manual sync button and handler
    public function display_manual_sync_button() {
        if (isset($_POST['dim_instagram_manual_sync']) && check_admin_referer('dim_instagram_manual_sync_action', 'dim_instagram_manual_sync_nonce')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-api.php';

            $result = DIM_Instagram_API::sync_posts();

            if (is_wp_error($result)) {
                echo '<div class="notice notice-error"><p><strong>Sync Failed:</strong> ' . esc_html($result->get_error_message()) . '</p></div>';
            } else {
                echo '<div class="notice notice-success"><p><strong>Sync Complete!</strong> Imported: ' . intval($result['imported']) . ', Updated: ' . intval($result['updated']) . ', Skipped: ' . intval($result['skipped']) . ', Total: ' . intval($result['total']) . '</p></div>';
            }
        }
        ?>
        <form method="post" action="">
            <?php wp_nonce_field('dim_instagram_manual_sync_action', 'dim_instagram_manual_sync_nonce'); ?>
            <button type="submit" name="dim_instagram_manual_sync" class="button button-primary">Sync Now</button>
        </form>
        <?php
    }

    // Sanitization callbacks to reschedule cron when settings change
    public function sanitize_sync_enabled($value) {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-cron.php';
        DIM_Instagram_Cron::schedule();
        return $value;
    }

    public function sanitize_sync_frequency($value) {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-cron.php';
        DIM_Instagram_Cron::schedule();
        return $value;
    }

    // Shortcode information display
    public function display_shortcode_info() {
        ?>
        <section id="shortcode-info">
            <h4>Instagram Feed Grid</h4>
            <p><strong>Shortcode:</strong> <code>[instagram_feed limit="" columns="" show_caption=""]</code></p>

            <table class="widefat" style="margin-top: 15px;">
                <thead>
                    <tr>
                        <th>Parameter</th>
                        <th>Description</th>
                        <th>Default</th>
                        <th>Example</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>limit</code></td>
                        <td>Number of posts to display</td>
                        <td>12</td>
                        <td><code>[instagram_feed limit="9"]</code></td>
                    </tr>
                    <tr>
                        <td><code>columns</code></td>
                        <td>Number of columns in grid</td>
                        <td>3</td>
                        <td><code>[instagram_feed columns="4"]</code></td>
                    </tr>
                    <tr>
                        <td><code>show_caption</code></td>
                        <td>Show caption overlay (yes/no)</td>
                        <td>no</td>
                        <td><code>[instagram_feed show_caption="yes"]</code></td>
                    </tr>
                </tbody>
            </table>

            <hr style="margin: 30px 0;">

            <h4>Instagram Post Meta Fields</h4>
            <p>Use these shortcodes to display individual post meta fields.</p>

            <table class="widefat" style="margin-top: 15px;">
                <thead>
                    <tr>
                        <th>Shortcode</th>
                        <th>Description</th>
                        <th>Example Usage</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>[instagram_caption]</code></td>
                        <td>Displays the post caption</td>
                        <td><code>[instagram_caption]</code> or <code>[instagram_caption id="123"]</code></td>
                    </tr>
                    <tr>
                        <td><code>[instagram_image]</code></td>
                        <td>Displays the post image</td>
                        <td><code>[instagram_image]</code> or <code>[instagram_image class="custom-class"]</code></td>
                    </tr>
                    <tr>
                        <td><code>[instagram_link]</code></td>
                        <td>Link to Instagram post</td>
                        <td><code>[instagram_link text="View on Instagram"]</code></td>
                    </tr>
                    <tr>
                        <td><code>[instagram_likes]</code></td>
                        <td>Number of likes</td>
                        <td><code>[instagram_likes]</code> or <code>[instagram_likes id="123"]</code></td>
                    </tr>
                    <tr>
                        <td><code>[instagram_comments]</code></td>
                        <td>Number of comments</td>
                        <td><code>[instagram_comments]</code> or <code>[instagram_comments id="123"]</code></td>
                    </tr>
                    <tr>
                        <td><code>[instagram_date]</code></td>
                        <td>Post date</td>
                        <td><code>[instagram_date format="F j, Y"]</code></td>
                    </tr>
                    <tr>
                        <td><code>[instagram_username]</code></td>
                        <td>Instagram username</td>
                        <td><code>[instagram_username]</code> or <code>[instagram_username id="123"]</code></td>
                    </tr>
                    <tr>
                        <td><code>[instagram_hashtags]</code></td>
                        <td>Post hashtags</td>
                        <td><code>[instagram_hashtags]</code> or <code>[instagram_hashtags id="123"]</code></td>
                    </tr>
                </tbody>
            </table>

            <p style="margin-top: 20px;"><strong>Note:</strong> Meta field shortcodes can be used:</p>
            <ul>
                <li>Inside a WordPress loop of Instagram Posts (no ID needed)</li>
                <li>Anywhere with a specific post ID: <code>[instagram_caption id="123"]</code></li>
                <li>In JetEngine Listing items (will automatically use the current post)</li>
            </ul>
        </section>
        <?php
    }
}
