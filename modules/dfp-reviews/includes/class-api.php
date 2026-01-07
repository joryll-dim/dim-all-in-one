<?php

/**
 * Outscraper API integration
 */

defined('ABSPATH') or die('No direct script access allowed');

class DFP_Reviews_API {

    public static function get_data_from_outscraper($source, $is_cron = false) {
        // Security: Validate user capabilities before API calls (skip for cron context)
        if (!$is_cron && !current_user_can('manage_options')) {
            error_log('DFP Reviews: Unauthorized API call attempt');
            return false;
        }

        // Input validation and sanitization
        $source = intval($source);
        $clinic_count = max(1, get_option('dfp_reviews_clinic_count', 1));
        if ($source < 1 || $source > $clinic_count) {
            error_log('DFP Reviews: Invalid source parameter: ' . $source);
            return false;
        }

        // Sanitize retrieved data
        $clinic_id = sanitize_text_field(get_option("dfp_reviews_clinic_id_{$source}", ""));
        $api_key = sanitize_text_field(get_option("dfp_reviews_api_key", ""));
        $id_place = sanitize_text_field(get_option("dfp_reviews_id_place_{$source}", ""));
        $reviews_limit = max(1, min(100, intval(get_option("dfp_reviews_reviews_limit", 20))));

        // Enhanced validation
        if (empty($api_key) || strlen($api_key) < 10) {
            error_log('DFP Reviews: Invalid or empty API key');
            add_settings_error('dfp_reviews_settings', 'api_key_error', "Valid Outscraper API Key is required", 'error');
            return false;
        }

        if (empty($id_place) || strlen($id_place) < 10) {
            error_log('DFP Reviews: Invalid or empty Google Place ID for source ' . $source);
            add_settings_error('dfp_reviews_settings', 'id_place_error', "Valid Google Business Profile ID is required for Clinic {$source}", 'error');
            return false;
        }


        try {
            $client = new OutscraperClient($api_key);
            $results = $client->google_maps_reviews(
                query: [$id_place],
                language: 'en',
                region: null,
                limit: 1,
                reviews_limit: $reviews_limit,
                cutoff_rating: 5,
                sort: 'highest_rating'
            );

            // Validate response structure
            if (!isset($results) || !is_array($results)) {
                add_settings_error('dfp_reviews_settings', 'api_response_error', "Invalid API response structure", 'error');
                return false;
            }

            foreach ($results as $query_places) {
                // Validate required fields exist
                if (!isset($query_places['reviews']) || !isset($query_places['rating'])) {
                    add_settings_error('dfp_reviews_settings', 'api_data_error', "Missing required data in API response", 'error');
                    continue;
                }

                $total_reviews = intval($query_places['reviews']);
                $average_rating = floatval($query_places['rating']);

                update_option("dfp_reviews_total_reviews_{$source}", $total_reviews);
                update_option("dfp_reviews_total_stars_{$source}", $average_rating);

                // Validate reviews_data exists and is array
                if (!isset($query_places['reviews_data']) || !is_array($query_places['reviews_data'])) {
                    add_settings_error('dfp_reviews_settings', 'api_reviews_error', "No review data found in API response", 'warning');
                    continue;
                }

                foreach ($query_places['reviews_data'] as $review) {
                    // Validate required review fields
                    if (!isset($review['review_id']) || !isset($review['author_title'])) {
                        continue; // Skip malformed reviews
                    }

                    $testimonial_data = array(
                        'review_id' => sanitize_text_field($review['review_id'] ?? ''),
                        'review_rating' => intval($review['review_rating'] ?? 5),
                        'author_title' => sanitize_text_field($review['author_title'] ?? ''),
                        'author_image' => esc_url_raw($review['author_image'] ?? ''),
                        'review_text' => sanitize_textarea_field($review['review_text'] ?? ''),
                        'review_link' => esc_url_raw($review['review_link'] ?? ''),
                        'review_timestamp' => intval($review['review_timestamp'] ?? 0),
                        'review_datetime_utc' => sanitize_text_field($review['review_datetime_utc'] ?? ''),
                        'source' => $source,
                        'clinic_id' => $clinic_id
                    );

                    self::create_testimonial($testimonial_data);
                }
            }

            self::update_practice_information($average_rating, $total_reviews, $source, $clinic_id);

            // Log successful API call
            error_log("DFP Reviews: Successful API call for source {$source}. Retrieved {$total_reviews} reviews with {$average_rating} average rating");

            return true;

        } catch (OutscraperException $e) {
            // Handle specific Outscraper API errors
            error_log('Outscraper API Error: ' . $e->getMessage());
            add_settings_error('dfp_reviews_settings', 'outscraper_api_error', "Outscraper API Error: " . $e->getMessage(), 'error');
            return false;
        } catch (Exception $e) {
            // Handle general errors (network issues, invalid JSON, etc.)
            error_log('General API Error: ' . $e->getMessage());

            // Provide more specific error messages based on error content
            $error_message = $e->getMessage();
            if (strpos($error_message, 'cURL') !== false) {
                add_settings_error('dfp_reviews_settings', 'network_error', "Network connection error. Please check your internet connection.", 'error');
            } elseif (strpos($error_message, 'API') !== false || strpos($error_message, 'key') !== false) {
                add_settings_error('dfp_reviews_settings', 'api_key_error', "API authentication failed. Please verify your API key.", 'error');
            } else {
                add_settings_error('dfp_reviews_settings', 'general_api_error', "API call failed: " . $error_message, 'error');
            }
            return false;
        }
    }

    private static function create_testimonial($testimonial_data) {
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

        $result = wp_insert_post($testimonial_post);

        // Check for errors
        if (is_wp_error($result)) {
            error_log('DFP Reviews: Failed to create testimonial post: ' . $result->get_error_message());
            return false;
        }

        return $result;
    }

    private static function update_practice_information($rating, $reviews, $source, $clinic_id) {
        $practice_information = get_option('practice-information', array());
        $total_stats = self::calculate_total_reviews();

        $practice_information["google-rating-{$source}"] = $rating;
        $practice_information["google-total-reviews"] = $total_stats['total_reviews'];
        $practice_information["google-average-rating"] = $total_stats['average_stars'];

        update_option('practice-information', $practice_information);
    }

    public static function calculate_total_reviews() {
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
}