<?php

/**
 * API Integration with Apify
 */

defined('ABSPATH') or die('No direct script access allowed');

class DIM_Instagram_API {

    /**
     * Apify API base URL
     */
    private static $api_base = 'https://api.apify.com/v2';

    /**
     * Fetch Instagram posts from Apify
     *
     * @param string $username Instagram username
     * @param int $limit Number of posts to fetch
     * @return array|WP_Error Array of posts or WP_Error on failure
     */
    public static function fetch_posts($username, $limit = 12) {
        $api_key = get_option('dim_instagram_api_key');
        $actor_id = get_option('dim_instagram_actor_id');

        if (empty($api_key)) {
            return new WP_Error('no_api_key', 'Apify API key is not configured.');
        }

        if (empty($actor_id)) {
            return new WP_Error('no_actor_id', 'Apify Actor ID is not configured.');
        }

        if (empty($username)) {
            return new WP_Error('no_username', 'Instagram username is not configured.');
        }

        // Start actor run
        $run_url = self::$api_base . "/acts/{$actor_id}/runs?token={$api_key}";

        // Apify expects input to be wrapped in an 'input' field for the actor run
        $run_body = json_encode(array(
            'directUrls' => array("https://www.instagram.com/{$username}/"),
            'resultsLimit' => intval($limit),
            'resultsType' => 'posts',
        ));

        $run_response = wp_remote_post($run_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => $run_body,
            'timeout' => 30,
        ));

        if (is_wp_error($run_response)) {
            return $run_response;
        }

        $run_body_response = json_decode(wp_remote_retrieve_body($run_response), true);

        if (!isset($run_body_response['data']['id'])) {
            $error_message = 'Failed to start Apify actor run.';

            // Add more details if available
            if (isset($run_body_response['error'])) {
                $error_message .= ' Error: ' . $run_body_response['error']['message'];
            } elseif (is_array($run_body_response)) {
                $error_message .= ' Response: ' . print_r($run_body_response, true);
            } else {
                $response_code = wp_remote_retrieve_response_code($run_response);
                $error_message .= ' HTTP Code: ' . $response_code;
            }

            return new WP_Error('run_failed', $error_message);
        }

        $run_id = $run_body_response['data']['id'];

        // Wait for run to complete (check every 5 seconds, max 60 seconds)
        $max_attempts = 12;
        $attempt = 0;

        while ($attempt < $max_attempts) {
            sleep(5);
            $attempt++;

            $status_url = self::$api_base . "/actor-runs/{$run_id}?token={$api_key}";
            $status_response = wp_remote_get($status_url, array('timeout' => 15));

            if (is_wp_error($status_response)) {
                continue;
            }

            $status_body = json_decode(wp_remote_retrieve_body($status_response), true);

            if (isset($status_body['data']['status']) && $status_body['data']['status'] === 'SUCCEEDED') {
                // Get dataset items
                $dataset_id = $status_body['data']['defaultDatasetId'];
                return self::get_dataset_items($dataset_id, $api_key);
            }

            if (isset($status_body['data']['status']) && $status_body['data']['status'] === 'FAILED') {
                return new WP_Error('run_failed', 'Apify actor run failed.');
            }
        }

        return new WP_Error('timeout', 'Apify actor run timed out.');
    }

    /**
     * Get dataset items from Apify
     *
     * @param string $dataset_id Dataset ID
     * @param string $api_key API key
     * @return array|WP_Error Array of items or WP_Error on failure
     */
    private static function get_dataset_items($dataset_id, $api_key) {
        $dataset_url = self::$api_base . "/datasets/{$dataset_id}/items?token={$api_key}";

        $dataset_response = wp_remote_get($dataset_url, array('timeout' => 30));

        if (is_wp_error($dataset_response)) {
            return $dataset_response;
        }

        $items = json_decode(wp_remote_retrieve_body($dataset_response), true);

        if (!is_array($items)) {
            return new WP_Error('invalid_response', 'Invalid response from Apify dataset.');
        }

        return $items;
    }

    /**
     * Import posts into WordPress
     *
     * @param array $posts Array of Instagram posts from Apify
     * @return array Results with counts
     */
    public static function import_posts($posts) {
        $imported = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($posts as $post_data) {
            // Check if required fields exist
            if (empty($post_data['id']) || empty($post_data['url'])) {
                $skipped++;
                continue;
            }

            $post_id = $post_data['id'];

            // Check if post already exists
            $existing_posts = get_posts(array(
                'post_type' => 'instagram_posts',
                'meta_key' => 'post_id',
                'meta_value' => $post_id,
                'posts_per_page' => 1,
                'post_status' => 'any',
            ));

            $caption = isset($post_data['caption']) ? $post_data['caption'] : '';

            // Try multiple possible field names for image URL
            $image_url = '';
            if (!empty($post_data['displayUrl'])) {
                $image_url = $post_data['displayUrl'];
            } elseif (!empty($post_data['thumbnailUrl'])) {
                $image_url = $post_data['thumbnailUrl'];
            } elseif (!empty($post_data['imgUrl'])) {
                $image_url = $post_data['imgUrl'];
            } elseif (!empty($post_data['imageUrl'])) {
                $image_url = $post_data['imageUrl'];
            } elseif (!empty($post_data['videoUrl'])) {
                $image_url = $post_data['videoUrl'];
            }

            $media_type = isset($post_data['type']) ? $post_data['type'] : 'Image';
            $likes_count = isset($post_data['likesCount']) ? intval($post_data['likesCount']) : 0;
            $comments_count = isset($post_data['commentsCount']) ? intval($post_data['commentsCount']) : 0;
            $posted_date = isset($post_data['timestamp']) ? date('Y-m-d H:i:s', strtotime($post_data['timestamp'])) : current_time('mysql');
            $username = isset($post_data['ownerUsername']) ? $post_data['ownerUsername'] : get_option('dim_instagram_username', '');
            $hashtags = isset($post_data['hashtags']) ? implode(', ', $post_data['hashtags']) : '';

            // Prepare post title (first 50 chars of caption or "Instagram Post")
            $post_title = !empty($caption) ? wp_trim_words($caption, 10, '...') : 'Instagram Post';

            // Download image to WordPress media library
            $local_image_id = 0;
            $local_image_url = $image_url; // Fallback to original URL

            if (!empty($image_url)) {
                // Check if we already downloaded this image
                if (!empty($existing_posts)) {
                    $existing_image_id = get_post_thumbnail_id($existing_posts[0]->ID);
                    if ($existing_image_id) {
                        $local_image_id = $existing_image_id;
                        $local_image_url = wp_get_attachment_url($local_image_id);
                    }
                }

                // Download image if we don't have it yet
                if (empty($local_image_id)) {
                    $local_image_id = self::download_image($image_url, $post_title);
                    if ($local_image_id) {
                        $local_image_url = wp_get_attachment_url($local_image_id);
                    }
                }
            }

            $post_args = array(
                'post_type' => 'instagram_posts',
                'post_title' => sanitize_text_field($post_title),
                'post_content' => wp_kses_post($caption),
                'post_status' => 'publish',
                'meta_input' => array(
                    'post_id' => sanitize_text_field($post_id),
                    'post_url' => esc_url_raw($post_data['url']),
                    'caption' => sanitize_textarea_field($caption),
                    'image_url' => esc_url_raw($local_image_url),
                    'media_type' => sanitize_text_field($media_type),
                    'likes_count' => $likes_count,
                    'comments_count' => $comments_count,
                    'posted_date' => $posted_date,
                    'username' => sanitize_text_field($username),
                    'hashtags' => sanitize_text_field($hashtags),
                ),
            );

            if (!empty($existing_posts)) {
                // Update existing post
                $post_args['ID'] = $existing_posts[0]->ID;
                $wp_post_id = wp_update_post($post_args);
                $updated++;
            } else {
                // Create new post
                $wp_post_id = wp_insert_post($post_args);
                $imported++;
            }

            // Set featured image if we downloaded one
            if ($local_image_id && $wp_post_id) {
                set_post_thumbnail($wp_post_id, $local_image_id);
            }
        }

        // Update last sync time
        update_option('dim_instagram_last_sync', current_time('mysql'));

        return array(
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'total' => count($posts),
        );
    }

    /**
     * Download image from URL to WordPress media library
     *
     * @param string $image_url The image URL to download
     * @param string $title Post title for the attachment
     * @return int|false Attachment ID on success, false on failure
     */
    private static function download_image($image_url, $title) {
        if (empty($image_url)) {
            return false;
        }

        // Include required WordPress files
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // Download image using WordPress HTTP API with no-referrer header
        $temp_file = download_url($image_url);

        if (is_wp_error($temp_file)) {
            error_log('Instagram Feed: Failed to download image: ' . $temp_file->get_error_message());
            return false;
        }

        // Get the file extension
        $extension = pathinfo(parse_url($image_url, PHP_URL_PATH), PATHINFO_EXTENSION);
        if (empty($extension)) {
            $extension = 'jpg';
        }

        // Prepare file array
        $file_array = array(
            'name' => sanitize_file_name($title . '.' . $extension),
            'tmp_name' => $temp_file,
        );

        // Upload to media library
        $attachment_id = media_handle_sideload($file_array, 0, $title);

        // Clean up temp file
        if (file_exists($temp_file)) {
            @unlink($temp_file);
        }

        if (is_wp_error($attachment_id)) {
            error_log('Instagram Feed: Failed to sideload image: ' . $attachment_id->get_error_message());
            return false;
        }

        return $attachment_id;
    }

    /**
     * Sync posts (fetch and import)
     *
     * @return array|WP_Error Results or WP_Error on failure
     */
    public static function sync_posts() {
        $username = get_option('dim_instagram_username');
        $limit = intval(get_option('dim_instagram_posts_limit', 12));

        $posts = self::fetch_posts($username, $limit);

        if (is_wp_error($posts)) {
            return $posts;
        }

        return self::import_posts($posts);
    }
}
