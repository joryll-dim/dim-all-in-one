<?php
/**
 * Custom Post Type Registration for Google Reviews
 */

defined('ABSPATH') or die('No direct script access allowed');

class DFP_Reviews_CPT {

    /**
     * Register the Google Reviews custom post type
     */
    public static function register() {
        $labels = array(
            'name'                  => 'Google Reviews',
            'singular_name'         => 'Google Review',
            'menu_name'             => 'Google Reviews',
            'name_admin_bar'        => 'Google Review',
            'add_new'               => 'Add New',
            'add_new_item'          => 'Add New Review',
            'new_item'              => 'New Review',
            'edit_item'             => 'Edit Review',
            'view_item'             => 'View Review',
            'all_items'             => 'All Reviews',
            'search_items'          => 'Search Reviews',
            'parent_item_colon'     => 'Parent Reviews:',
            'not_found'             => 'No reviews found.',
            'not_found_in_trash'    => 'No reviews found in Trash.',
            'featured_image'        => 'Review Author Image',
            'set_featured_image'    => 'Set author image',
            'remove_featured_image' => 'Remove author image',
            'use_featured_image'    => 'Use as author image',
            'archives'              => 'Review Archives',
            'insert_into_item'      => 'Insert into review',
            'uploaded_to_this_item' => 'Uploaded to this review',
            'filter_items_list'     => 'Filter reviews list',
            'items_list_navigation' => 'Reviews list navigation',
            'items_list'            => 'Reviews list',
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'google-reviews'),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 20,
            'menu_icon'          => 'dashicons-star-filled',
            'supports'           => array('title', 'editor', 'thumbnail'),
            'show_in_rest'       => true,
        );

        register_post_type('google_reviews', $args);
    }

    /**
     * Register custom meta fields for Google Reviews
     */
    public static function register_meta_fields() {
        $meta_fields = array(
            'review_id'             => 'string',
            'review_rating'         => 'integer',
            'author_title'          => 'string',
            'author_image'          => 'string',
            'review_text'           => 'string',
            'review_link'           => 'string',
            'review_timestamp'      => 'integer',
            'review_datetime_utc'   => 'string',
            'source'                => 'integer',
            'clinic_id'             => 'string',
        );

        foreach ($meta_fields as $meta_key => $meta_type) {
            register_post_meta('google_reviews', $meta_key, array(
                'type'              => $meta_type,
                'description'       => ucwords(str_replace('_', ' ', $meta_key)),
                'single'            => true,
                'show_in_rest'      => true,
                'sanitize_callback' => array('DFP_Reviews_CPT', 'sanitize_meta_' . $meta_type),
            ));
        }
    }

    /**
     * Sanitize string meta
     */
    public static function sanitize_meta_string($value) {
        return sanitize_text_field($value);
    }

    /**
     * Sanitize integer meta
     */
    public static function sanitize_meta_integer($value) {
        return intval($value);
    }

    /**
     * Add custom columns to the admin list
     */
    public static function add_custom_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['rating'] = 'Rating';
        $new_columns['clinic'] = 'Clinic';
        $new_columns['date'] = $columns['date'];

        return $new_columns;
    }

    /**
     * Populate custom columns
     */
    public static function populate_custom_columns($column, $post_id) {
        switch ($column) {
            case 'rating':
                $rating = get_post_meta($post_id, 'review_rating', true);
                if ($rating) {
                    echo str_repeat('⭐', intval($rating));
                }
                break;
            case 'clinic':
                $clinic_id = get_post_meta($post_id, 'clinic_id', true);
                echo esc_html($clinic_id ?: 'N/A');
                break;
        }
    }

    /**
     * Make rating column sortable
     */
    public static function make_columns_sortable($columns) {
        $columns['rating'] = 'review_rating';
        $columns['clinic'] = 'clinic_id';
        return $columns;
    }
}
