<?php

/**
 * The settings-specific functionality of the plugin.
 *
 * @link       http://www.dentalfunnels.site
 * @since      1.0.0
 *
 * @package    DFP_Reviews
 * @subpackage DFP_Reviews/admin
 */

/**
 * The settings-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and all hooks for the settings functionality.
 *
 * @package    DFP_Reviews
 * @subpackage DFP_Reviews/admin
 * @author     Dental Funnels The Platform <info@dentalfunnels.site>
 */
class DFP_Reviews_Settings {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

    }

    /**
     * Register the settings menu.
     *
     * @since    1.0.0
     */
    public function add_settings_menu() {
        add_options_page(
            'DFP Reviews Settings',
            'DFP Reviews',
            'manage_options',
            'dfp-reviews-settings',
            array( $this, 'display_settings_page' )
        );
    }

    /**
     * Initialize all settings.
     *
     * @since    1.0.0
     */
    public function initialize_settings() {
        register_setting('dfp_reviews_options', 'dfp_reviews_api_key', 'sanitize_text_field');
        register_setting('dfp_reviews_options', 'dfp_reviews_clinic_count', array('type' => 'integer', 'sanitize_callback' => 'intval'));
        register_setting('dfp_reviews_options', 'dfp_reviews_reviews_limit', array('type' => 'integer', 'sanitize_callback' => array($this, 'sanitize_reviews_limit')));

        add_settings_section("dfp_reviews_settings", "", '__return_false', 'dfp-reviews-settings');
        add_settings_field("dfp_reviews_api_key", "Outscraper API Key", array( $this, 'api_key_callback' ), 'dfp-reviews-settings', "dfp_reviews_settings");
        add_settings_field("dfp_reviews_reviews_limit", "Reviews Limit", array( $this, 'reviews_limit_callback' ), 'dfp-reviews-settings', "dfp_reviews_settings");

        // Get current clinic count
        $clinic_count = max(1, get_option('dfp_reviews_clinic_count', 1));

        // If form is submitted, use the new count immediately with proper security
        if (isset($_POST['dfp_reviews_clinic_count']) && check_admin_referer('dfp_reviews_options-options')) {
            // Additional capability check
            if (!current_user_can('manage_options')) {
                add_settings_error('dfp_reviews_settings', 'capability_error', 'You do not have permission to modify clinic count.', 'error');
                return;
            }

            $new_clinic_count = intval(sanitize_text_field(wp_unslash($_POST['dfp_reviews_clinic_count'])));
            if ($new_clinic_count > $clinic_count) {
                $clinic_count = $new_clinic_count;
                update_option('dfp_reviews_clinic_count', $new_clinic_count); // Update immediately
            }
        }

        // Register settings for all clinics up to the current count plus one
        for ($source = 1; $source <= $clinic_count + 1; $source++) {
            register_setting('dfp_reviews_options', "dfp_reviews_id_place_{$source}", 'sanitize_text_field');
            register_setting('dfp_reviews_options', "dfp_reviews_update_frequency_{$source}", array( $this, 'sanitize_update_frequency' ));
            register_setting('dfp_reviews_options', "dfp_reviews_total_reviews_{$source}", 'intval');
            register_setting('dfp_reviews_options', "dfp_reviews_total_stars_{$source}", 'floatval');
            register_setting('dfp_reviews_options', "dfp_reviews_clinic_id_{$source}", 'sanitize_text_field');

            add_settings_section("dfp_reviews_source_{$source}", "", '__return_false', 'dfp-reviews-settings');

            add_settings_field("dfp_reviews_id_place_{$source}", "ID Place Google", array( $this, 'id_place_callback' ), 'dfp-reviews-settings', "dfp_reviews_source_{$source}", ['source' => $source]);
            add_settings_field("dfp_reviews_update_frequency_{$source}", "Update Frequency", array( $this, 'update_frequency_callback' ), 'dfp-reviews-settings', "dfp_reviews_source_{$source}", ['source' => $source]);
            add_settings_field("dfp_reviews_total_reviews_{$source}", "Total Reviews", array( $this, 'total_reviews_callback' ), 'dfp-reviews-settings', "dfp_reviews_source_{$source}", ['source' => $source]);
            add_settings_field("dfp_reviews_total_stars_{$source}", "Stars", array( $this, 'total_stars_callback' ), 'dfp-reviews-settings', "dfp_reviews_source_{$source}", ['source' => $source]);
            add_settings_field("dfp_reviews_clinic_id_{$source}", "Clinic Name", array( $this, 'clinic_id_callback' ), 'dfp-reviews-settings', "dfp_reviews_source_{$source}", ['source' => $source]);
        }
    }

    /**
     * Callback for API key field.
     *
     * @since    1.0.0
     */
    public function api_key_callback() {
        $api_key = esc_attr(get_option("dfp_reviews_api_key", ""));
        ?>
        <label for="dfp_reviews_api_key" class="screen-reader-text">
            <?php esc_html_e('Outscraper API Key', 'dfp-reviews'); ?>
        </label>
        <input
            type="text"
            name="dfp_reviews_api_key"
            id="dfp_reviews_api_key"
            value="<?php echo $api_key; ?>"
            class="regular-text"
            aria-required="true"
            aria-describedby="api-key-description"
        />
        <p class="description" id="api-key-description">
            <?php esc_html_e('Insert your Outscraper API Key to fetch Google reviews.', 'dfp-reviews'); ?>
        </p>
        <?php
    }

    /**
     * Callback for reviews limit field.
     *
     * @since    2.5.5
     */
    public function reviews_limit_callback() {
        $reviews_limit = intval(get_option("dfp_reviews_reviews_limit", 20));
        ?>
        <label for="dfp_reviews_reviews_limit" class="screen-reader-text">
            <?php esc_html_e('Reviews Limit', 'dfp-reviews'); ?>
        </label>
        <input
            type="number"
            name="dfp_reviews_reviews_limit"
            id="dfp_reviews_reviews_limit"
            value="<?php echo $reviews_limit; ?>"
            min="1"
            max="100"
            class="small-text"
            aria-required="true"
            aria-describedby="reviews-limit-description"
        />
        <p class="description" id="reviews-limit-description">
            <?php esc_html_e('Maximum number of reviews to fetch per API call (1-100). Default: 20', 'dfp-reviews'); ?>
        </p>
        <?php
    }

    /**
     * Callback for clinic ID field.
     *
     * @since    1.0.0
     * @param    array    $args    Field arguments including source.
     */
    public function clinic_id_callback($args) {
        $source = $args['source'];
        $clinic_id = esc_attr(get_option("dfp_reviews_clinic_id_{$source}", ""));
        ?>
        <label for="dfp_reviews_clinic_id_<?php echo $source; ?>" class="screen-reader-text">
            <?php printf(esc_html__('Clinic %d Name', 'dfp-reviews'), $source); ?>
        </label>
        <input
            type="text"
            name="dfp_reviews_clinic_id_<?php echo $source; ?>"
            id="dfp_reviews_clinic_id_<?php echo $source; ?>"
            value="<?php echo $clinic_id; ?>"
            class="regular-text"
            aria-describedby="clinic-id-description-<?php echo $source; ?>"
        />
        <p class="description" id="clinic-id-description-<?php echo $source; ?>">
            <?php printf(esc_html__('Enter a custom name for Clinic %d (e.g., "Brooklyn Office").', 'dfp-reviews'), $source); ?>
        </p>
        <?php
    }

    /**
     * Callback for ID place field.
     *
     * @since    1.0.0
     * @param    array    $args    Field arguments including source.
     */
    public function id_place_callback($args) {
        $source = intval($args['source']);
        $id_place = esc_attr(get_option("dfp_reviews_id_place_{$source}", ""));
        echo "<input type='text' name='dfp_reviews_id_place_" . esc_attr($source) . "' value='" . esc_attr($id_place) . "' class='regular-text'>";
        echo "<p class='description'>Enter the Google Business Profile ID for Clinic " . esc_html($source) . ".</p>";
    }

    /**
     * Callback for update frequency field.
     *
     * @since    1.0.0
     * @param    array    $args    Field arguments including source.
     */
    public function update_frequency_callback($args) {
        $source = intval($args['source']);
        $update_frequency = esc_attr(get_option("dfp_reviews_update_frequency_{$source}", "weekly"));
        $options = array(
            'manual' => 'Manual',
            'onceaday' => 'Once a Day',
            'everythreedays' => 'Once Every 3 Days',
            'weekly' => 'Once Weekly',
            'everyfifteendays' => 'Once Every 15 Days',
        );

        echo "<select name='dfp_reviews_update_frequency_" . esc_attr($source) . "'>";
        foreach ($options as $value => $label) {
            echo "<option value='" . esc_attr($value) . "'" . selected($value, $update_frequency, false) . ">" . esc_html($label) . "</option>";
        }
        echo "</select>";
    }

    /**
     * Callback for total reviews field.
     *
     * @since    1.0.0
     * @param    array    $args    Field arguments including source.
     */
    public function total_reviews_callback($args) {
        $source = intval($args['source']);
        $total_reviews = intval(get_option("dfp_reviews_total_reviews_{$source}", 0));
        echo "<input type='text' name='dfp_reviews_total_reviews_" . esc_attr($source) . "' value='" . esc_attr($total_reviews) . "' readonly />";
        echo "<p>Total reviews for Clinic " . esc_html($source) . " on Google.</p>";
    }

    /**
     * Callback for total stars field.
     *
     * @since    1.0.0
     * @param    array    $args    Field arguments including source.
     */
    public function total_stars_callback($args) {
        $source = intval($args['source']);
        $total_stars = floatval(get_option("dfp_reviews_total_stars_{$source}", 0));
        echo "<input type='number' step='0.1' name='dfp_reviews_total_stars_" . esc_attr($source) . "' value='" . esc_attr($total_stars) . "' readonly class='regular-text'>";
        echo "<p class='description'>Total of Stars for Clinic " . esc_html($source) . "</p>";
    }

    /**
     * Display shortcode information section.
     *
     * @since    1.0.0
     */
    public function display_shortcode_info() {
        ?>
        <section id="shortcode-info">
            <?php
            echo "<h3>Shortcode Information</h3>";
            echo "Shortcode: [dfp_reviews clinic='' type='']<br>";
            echo "Parameters:<br>";
            echo "<ul>";
            echo "<li><strong>clinic:</strong> The clinic number (1-n). Use '0' for all clinics.</li>";
            echo "<li><strong>type:</strong> Either 'reviews' for total reviews, 'stars' for average rating, 'total_reviews_all' for total reviews across all clinics, or 'average_stars_all' for average stars across all clinics.</li>";
            echo "</ul>";
            echo "Examples:<br>";
            echo "<ul>";
            echo "<li>[dfp_reviews clinic='1' type='reviews'] - Displays total reviews for Clinic 1</li>";
            echo "<li>[dfp_reviews clinic='2' type='stars'] - Displays average stars for Clinic 2</li>";
            echo "<li>[dfp_reviews clinic='0' type='total_reviews_all'] - Displays total reviews across all clinics</li>";
            echo "<li>[dfp_reviews clinic='0' type='average_stars_all'] - Displays average stars across all clinics</li>";
            echo "</ul>";
            ?>
        </section>
        <?php
    }

    /**
     * Display the settings page.
     *
     * @since    1.0.0
     */
    public function display_settings_page() {
        // Security check: verify user has proper capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $clinic_count = max(1, get_option('dfp_reviews_clinic_count', 1));

        // Handle clinic removal only on explicit GET request with proper security
        if (isset($_GET['action']) && sanitize_text_field(wp_unslash($_GET['action'])) === 'remove_clinic' &&
            isset($_GET['clinic_id']) && !isset($_POST['submit'])) {

            $clinic_id_raw = sanitize_text_field(wp_unslash($_GET['clinic_id']));
            $clinic_to_remove = intval($clinic_id_raw);

            // Verify nonce for clinic removal
            if (!check_admin_referer('remove_clinic_' . $clinic_to_remove)) {
                add_settings_error('dfp_reviews_settings', 'nonce_error', 'Security check failed for clinic removal.', 'error');
                return;
            }

            // Additional capability check for clinic removal
            if (!current_user_can('manage_options')) {
                add_settings_error('dfp_reviews_settings', 'capability_error', 'You do not have permission to remove clinics.', 'error');
                return;
            }
            if ($clinic_to_remove > 1 && $clinic_to_remove <= $clinic_count) {
                for ($i = $clinic_to_remove; $i < $clinic_count; $i++) {
                    $next = $i + 1;
                    update_option("dfp_reviews_id_place_{$i}", get_option("dfp_reviews_id_place_{$next}", ""));
                    update_option("dfp_reviews_update_frequency_{$i}", get_option("dfp_reviews_update_frequency_{$next}", "weekly"));
                    update_option("dfp_reviews_total_reviews_{$i}", get_option("dfp_reviews_total_reviews_{$next}", 0));
                    update_option("dfp_reviews_total_stars_{$i}", get_option("dfp_reviews_total_stars_{$next}", 0));
                    update_option("dfp_reviews_clinic_id_{$i}", get_option("dfp_reviews_clinic_id_{$next}", ""));
                }
                delete_option("dfp_reviews_id_place_{$clinic_count}");
                delete_option("dfp_reviews_update_frequency_{$clinic_count}");
                delete_option("dfp_reviews_total_reviews_{$clinic_count}");
                delete_option("dfp_reviews_total_stars_{$clinic_count}");
                delete_option("dfp_reviews_clinic_id_{$clinic_count}");
                $clinic_count--;
                update_option('dfp_reviews_clinic_count', $clinic_count);
                add_settings_error('dfp_reviews_settings', 'clinic_removed', "Clinic $clinic_to_remove has been successfully removed.", 'updated');
            }
        }

        // Handle manual data update with proper security checks
        if (isset($_POST['dfp_reviews_get_data']) && isset($_POST['source'])) {
            // Verify nonce for CSRF protection
            if (!isset($_POST['dfp_reviews_get_data_nonce']) ||
                !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['dfp_reviews_get_data_nonce'])), 'dfp_reviews_get_data_nonce')) {
                add_settings_error('dfp_reviews_settings', 'nonce_error', 'Security check failed. Please try again.', 'error');
                return;
            }

            // Check user capabilities
            if (!current_user_can('manage_options')) {
                add_settings_error('dfp_reviews_settings', 'capability_error', 'You do not have permission to perform this action.', 'error');
                return;
            }

            // Sanitize and validate input
            $source = intval(sanitize_text_field(wp_unslash($_POST['source'])));
            if ($source < 1 || $source > get_option('dfp_reviews_clinic_count', 1)) {
                add_settings_error('dfp_reviews_settings', 'invalid_source', 'Invalid clinic selected.', 'error');
                return;
            }

            $this->process_get_data_request($source);
        }

        $this->schedule_cron();

        // Display success message for settings save with sanitization
        if (isset($_GET['settings-updated']) && sanitize_text_field(wp_unslash($_GET['settings-updated'])) === 'true') {
            add_settings_error('dfp_reviews_settings', 'settings_saved', 'Settings saved successfully.', 'updated');
        }

        ?>
        <div class="wrap">
            <h1 id="dfp_title">DFP Reviews Settings</h1>
            <?php settings_errors(); ?>
            <form action="options.php" method="post" id="dfp-reviews-form">
                <?php settings_fields('dfp_reviews_options'); ?>
                <table class="form-table">
                    <?php do_settings_fields('dfp-reviews-settings', 'dfp_reviews_settings'); ?>
                </table>
                <div id="clinics-container">
                    <?php
                    for ($source = 1; $source <= $clinic_count; $source++) {
                        ?>
                        <div class="clinic-section" data-clinic-id="<?php echo esc_attr($source); ?>">
                            <table class="form-table">
                                <tr><th colspan="2">
                                    <h2>Clinic <?php echo esc_html($source); ?>
                                    <?php if ($source > 1) {
                                        $remove_url = wp_nonce_url(
                                            admin_url('options-general.php?page=dfp-reviews-settings&action=remove_clinic&clinic_id=' . $source),
                                            'remove_clinic_' . $source
                                        );
                                    ?>
                                        <a href="<?php echo esc_url($remove_url); ?>" class="button button-secondary remove-clinic" onclick="return confirm('Are you sure you want to remove Clinic <?php echo esc_js($source); ?>?');">Remove</a>
                                    <?php } ?>
                                    </h2>
                                </th></tr>
                                <?php do_settings_fields('dfp-reviews-settings', "dfp_reviews_source_{$source}"); ?>
                            </table>
                        </div>
                        <?php
                    }
                    ?>
                </div>
                <input type="hidden" name="dfp_reviews_clinic_count" id="clinic-count" value="<?php echo esc_attr($clinic_count); ?>">
                <div class="button-container">
                    <p><button type="button" class="button button-secondary" id="add-clinic">Add New Clinic</button></p>
                    <?php submit_button('Save Settings', 'primary', 'submit', false); ?>
                </div>
            </form>
            <form action="" method="post" class="button-container-form">
                <?php wp_nonce_field('dfp_reviews_get_data_nonce', 'dfp_reviews_get_data_nonce'); ?>
                <input type="hidden" name="dfp_reviews_get_data" value="1">
                <label for="update-clinic-select">
                    <?php esc_html_e('Select Clinic:', 'dfp-reviews'); ?>
                </label>
                <select name="source" id="update-clinic-select" aria-describedby="update-description">
                    <?php
                    for ($source = 1; $source <= $clinic_count; $source++) {
                        $clinic_name = get_option("dfp_reviews_clinic_id_{$source}", "Clinic {$source}");
                        echo "<option value='" . esc_attr($source) . "'>" . esc_html($clinic_name) . "</option>";
                    }
                    ?>
                </select>
                <?php submit_button('Update Data Now', 'secondary', 'dfp_reviews_get_data_btn', false); ?>
                <p class="description" id="update-description" style="margin: 0;">
                    <?php esc_html_e('Manually fetch the latest reviews for the selected clinic.', 'dfp-reviews'); ?>
                </p>
            </form>
            <?php
            $total_stats = $this->calculate_total_reviews();
            ?>
            <div class="dfp-reviews-total-stats">
                <h2>General Total of Reviews</h2>
                <table class="form-table">
                    <tr>
                        <th>Total Reviews (All Clinics)</th>
                        <td><?php echo esc_html($total_stats['total_reviews']); ?></td>
                    </tr>
                    <tr>
                        <th>Average Stars (All Clinics)</th>
                        <td><?php echo esc_html($total_stats['average_stars']); ?></td>
                    </tr>
                </table>
            </div>
            <?php $this->display_cron_log(); ?>
            <?php $this->display_shortcode_info(); ?>
        </div>
        <?php
    }

    /**
     * Process the get data request.
     *
     * @since    1.0.0
     * @param    int    $source    The clinic source number.
     */
    public function process_get_data_request($source) {
        $clinic_count = max(1, get_option('dfp_reviews_clinic_count', 1));
        if ($source < 1 || $source > $clinic_count) {
            add_settings_error('dfp_reviews_settings', 'invalid_source', 'Invalid clinic selected.', 'error');
            return;
        }

        // Use the API class for data fetching (includes all error handling)
        require_once plugin_dir_path(__FILE__) . '/../includes/class-api.php';
        $result = DFP_Reviews_API::get_data_from_outscraper($source);

        if ($result) {
            // Get the updated values to display in success message
            $total_reviews = get_option("dfp_reviews_total_reviews_{$source}", 0);
            $average_rating = get_option("dfp_reviews_total_stars_{$source}", 0);
            $clinic_id = get_option("dfp_reviews_clinic_id_{$source}", $source);

            add_settings_error('dfp_reviews_settings', 'update_success', "Data successfully updated for Clinic {$clinic_id}. Retrieved {$total_reviews} reviews with {$average_rating} average rating.", 'updated');

            // Update JetEngine option
            $this->update_jetengine_option($average_rating, $total_reviews, $source, $clinic_id);
        }
        // Error messages already added by API class if $result is false

        // Update total stats across all clinics
        $total_stats = $this->calculate_total_reviews();
        update_option('dfp_reviews_total_stats', $total_stats);
    }

    /**
     * Save and return clinic ID.
     *
     * @since    1.0.0
     * @param    int    $source    The clinic source number.
     * @return   string The clinic ID.
     */
    public function save_clinic_id($source) {
        $clinic_id = get_option("dfp_reviews_clinic_id_{$source}", "");
        return $clinic_id;
    }

    /**
     * Calculate total reviews across all clinics.
     *
     * @since    1.0.0
     * @return   array Array containing total reviews and average stars.
     */
    public function calculate_total_reviews() {
        $clinic_count = max(1, get_option('dfp_reviews_clinic_count', 1));
        $total_reviews = 0;
        $total_stars = 0;
        $enabled_sources = 0;

        for ($source = 1; $source <= $clinic_count; $source++) {
            if (get_option("dfp_reviews_enable_source_{$source}", 1)) {
                $total_reviews += intval(get_option("dfp_reviews_total_reviews_{$source}", 0));
                $total_stars += floatval(get_option("dfp_reviews_total_stars_{$source}", 0));
                $enabled_sources++;
            }
        }

        $average_stars = $enabled_sources > 0 ? round($total_stars / $enabled_sources, 1) : 0;

        return array(
            'total_reviews' => $total_reviews,
            'average_stars' => $average_stars
        );
    }

    /**
     * Create a new testimonial post.
     *
     * @since    1.0.0
     * @param    array    $testimonial_data    The testimonial data.
     * @return   int|false The post ID on success or false on failure.
     */
    public function create_new_testimonial($testimonial_data) {
        $clinic_count = max(1, get_option('dfp_reviews_clinic_count', 1));
        if ($testimonial_data['source'] < 1 || $testimonial_data['source'] > $clinic_count || !get_option("dfp_reviews_enable_source_{$testimonial_data['source']}", 1)) {
            return false;
        }

        $existing_testimonial = get_posts(array(
            'post_type' => 'testimonials',
            'meta_key' => 'review_id',
            'meta_value' => $testimonial_data['review_id'],
            'post_status' => 'any',
            'posts_per_page' => 1,
        ));

        if (!empty($existing_testimonial)) {
            return false;
        }

        $testimonial_post = array(
            'post_type' => 'testimonials',
            'post_title' => $testimonial_data['author_title'],
            'post_status' => 'publish',
            'post_date' => date('Y-m-d H:i:s', $testimonial_data['review_timestamp']),
            'meta_input' => array(
                'review_id' => $testimonial_data['review_id'],
                'review_rating' => $testimonial_data['review_rating'],
                'author_title' => $testimonial_data['author_title'],
                'author_image' => $testimonial_data['author_image'],
                'review_text' => $testimonial_data['review_text'],
                'review_link' => $testimonial_data['review_link'],
                'review_time' => $testimonial_data['review_datetime_utc'],
                'source' => $testimonial_data['source'],
                'clinic_id' => $testimonial_data['clinic_id']
            ),
        );

        return wp_insert_post($testimonial_post);
    }

    /**
     * Update JetEngine practice information option.
     *
     * @since    1.0.0
     * @param    float    $rating     The average reviews rating.
     * @param    int      $reviews    The total number of reviews.
     * @param    int      $source     The clinic source number.
     * @param    string   $clinic_id  The clinic ID.
     */
    public function update_jetengine_option($rating, $reviews, $source, $clinic_id) {
        $practice_information = get_option('practice-information', array());
        $total_stats = $this->calculate_total_reviews();

        $practice_information["google-rating-{$source}"] = $rating;
        $practice_information["google-total-reviews"] = $total_stats['total_reviews'];
        $practice_information["google-average-rating"] = $total_stats['average_stars'];

        update_option('practice-information', $practice_information);
    }

    /**
     * Schedule cron jobs for all clinics.
     *
     * @since    1.0.0
     */
    public function schedule_cron() {
        $clinic_count = max(1, get_option('dfp_reviews_clinic_count', 1));
        for ($source = 1; $source <= $clinic_count; $source++) {
            if (get_option("dfp_reviews_enable_source_{$source}", 1)) {
                $frequency = get_option("dfp_reviews_update_frequency_{$source}", "weekly");
                $this->remove_cron($source);
                if ($frequency !== 'manual') {
                    if (!wp_next_scheduled("dfp_reviews_cron_hook_{$source}")) {
                        wp_schedule_event(time(), $frequency, "dfp_reviews_cron_hook_{$source}", array($source));
                    }
                }
            } else {
                $this->remove_cron($source);
            }
        }
    }

    /**
     * Remove cron job for a specific source.
     *
     * @since    1.0.0
     * @param    int    $source    The clinic source number.
     */
    public function remove_cron($source) {
        if (!isset($source) || !is_int($source)) {
            return; // Prevent errors if source is not provided or invalid
        }
        $timestamp = wp_next_scheduled("dfp_reviews_cron_hook_{$source}");
        if ($timestamp) {
            wp_unschedule_event($timestamp, "dfp_reviews_cron_hook_{$source}", array($source));
        }
    }

    /**
     * Sanitize update frequency input.
     *
     * @since    1.0.0
     * @param    string    $input    The input to sanitize.
     * @return   string    The sanitized input.
     */
    public function sanitize_update_frequency($input) {
        $valid_options = array('manual', 'onceaday', 'everythreedays', 'weekly', 'everyfifteendays');
        return in_array($input, $valid_options) ? $input : 'weekly';
    }

    /**
     * Sanitize reviews limit input.
     *
     * @since    2.6.1
     * @param    mixed    $input    The input to sanitize.
     * @return   int      The sanitized input (1-100).
     */
    public function sanitize_reviews_limit($input) {
        $value = intval($input);
        // Enforce range of 1-100
        return max(1, min(100, $value));
    }

    /**
     * Display cron job execution log.
     *
     * @since    2.5.6
     */
    public function display_cron_log() {
        $cron_log = get_option('dfp_reviews_cron_log', array());

        if (empty($cron_log)) {
            return; // Don't display section if no cron jobs have run yet
        }
        ?>
        <div class="dfp-reviews-cron-log">
            <h2>Automated Update History</h2>
            <p>Last automated update status for each clinic:</p>
            <table class="widefat fixed" style="margin-top: 10px;">
                <thead>
                    <tr>
                        <th>Clinic</th>
                        <th>Last Update</th>
                        <th>Status</th>
                        <th>Reviews</th>
                        <th>Rating</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    ksort($cron_log); // Sort by clinic number
                    foreach ($cron_log as $source => $log_entry):
                        $status_class = $log_entry['success'] ? 'success' : 'error';
                        $status_text = $log_entry['success'] ? '✓ Success' : '✗ Failed';
                        $status_color = $log_entry['success'] ? '#46b450' : '#dc3232';
                    ?>
                    <tr>
                        <td><strong><?php echo esc_html($log_entry['clinic_id']); ?></strong></td>
                        <td><?php echo esc_html($log_entry['timestamp']); ?></td>
                        <td style="color: <?php echo esc_attr($status_color); ?>; font-weight: bold;">
                            <?php echo esc_html($status_text); ?>
                        </td>
                        <td><?php echo $log_entry['success'] ? esc_html($log_entry['reviews']) : 'N/A'; ?></td>
                        <td><?php echo $log_entry['success'] ? esc_html($log_entry['rating']) : 'N/A'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p style="font-style: italic; color: #666; margin-top: 10px;">
                Note: This shows the last automated update for each clinic. Manual updates via "Update Data Now" are not logged here.
                If you see failed updates, check your API key and Place ID settings, or review error logs.
            </p>
        </div>
        <?php
    }

}