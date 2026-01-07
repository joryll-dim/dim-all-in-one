<?php

/**
 * Custom Post Type Registration for Instagram Posts
 */

defined('ABSPATH') or die('No direct script access allowed');

class DIM_Instagram_CPT {

    /**
     * Register the instagram_posts custom post type
     */
    public static function register() {
        $labels = array(
            'name'               => _x('Instagram Posts', 'post type general name', 'dim-instagram'),
            'singular_name'      => _x('Instagram Post', 'post type singular name', 'dim-instagram'),
            'menu_name'          => _x('Instagram Posts', 'admin menu', 'dim-instagram'),
            'name_admin_bar'     => _x('Instagram Post', 'add new on admin bar', 'dim-instagram'),
            'add_new'            => _x('Add New', 'instagram post', 'dim-instagram'),
            'add_new_item'       => __('Add New Instagram Post', 'dim-instagram'),
            'new_item'           => __('New Instagram Post', 'dim-instagram'),
            'edit_item'          => __('Edit Instagram Post', 'dim-instagram'),
            'view_item'          => __('View Instagram Post', 'dim-instagram'),
            'all_items'          => __('All Instagram Posts', 'dim-instagram'),
            'search_items'       => __('Search Instagram Posts', 'dim-instagram'),
            'parent_item_colon'  => __('Parent Instagram Posts:', 'dim-instagram'),
            'not_found'          => __('No Instagram posts found.', 'dim-instagram'),
            'not_found_in_trash' => __('No Instagram posts found in Trash.', 'dim-instagram')
        );

        $args = array(
            'labels'             => $labels,
            'description'        => __('Instagram posts synced from your feed', 'dim-instagram'),
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'instagram-posts'),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'menu_icon'          => 'dashicons-instagram',
            'supports'           => array('title', 'editor', 'thumbnail'),
            'show_in_rest'       => true,
        );

        register_post_type('instagram_posts', $args);
    }

    /**
     * Register meta fields for Instagram posts
     */
    public static function register_meta_fields() {
        $meta_fields = array(
            'post_id'         => 'string',  // Instagram post ID
            'post_url'        => 'string',  // Link to Instagram post
            'caption'         => 'string',  // Post caption/text
            'image_url'       => 'string',  // Media URL
            'media_type'      => 'string',  // Image/Video/Carousel
            'likes_count'     => 'integer', // Number of likes
            'comments_count'  => 'integer', // Number of comments
            'posted_date'     => 'string',  // When posted on Instagram
            'username'        => 'string',  // Instagram username
            'hashtags'        => 'string',  // Extracted hashtags (comma-separated)
        );

        foreach ($meta_fields as $meta_key => $meta_type) {
            register_post_meta('instagram_posts', $meta_key, array(
                'type'         => $meta_type,
                'single'       => true,
                'show_in_rest' => true,
            ));
        }
    }

    /**
     * Add custom columns to admin list
     */
    public static function add_admin_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['thumbnail'] = __('Image', 'dim-instagram');
        $new_columns['media_type'] = __('Type', 'dim-instagram');
        $new_columns['likes'] = __('Likes', 'dim-instagram');
        $new_columns['comments'] = __('Comments', 'dim-instagram');
        $new_columns['username'] = __('Username', 'dim-instagram');
        $new_columns['posted_date'] = __('Posted', 'dim-instagram');
        $new_columns['date'] = $columns['date'];

        return $new_columns;
    }

    /**
     * Display custom column content
     */
    public static function display_admin_columns($column, $post_id) {
        switch ($column) {
            case 'thumbnail':
                $image_url = get_post_meta($post_id, 'image_url', true);
                if ($image_url) {
                    echo '<img src="' . esc_url($image_url) . '" width="50" height="50" style="object-fit: cover;">';
                } else {
                    echo '—';
                }
                break;

            case 'media_type':
                $media_type = get_post_meta($post_id, 'media_type', true);
                echo $media_type ? esc_html(ucfirst($media_type)) : '—';
                break;

            case 'likes':
                $likes = get_post_meta($post_id, 'likes_count', true);
                echo $likes ? esc_html(number_format($likes)) : '0';
                break;

            case 'comments':
                $comments = get_post_meta($post_id, 'comments_count', true);
                echo $comments ? esc_html(number_format($comments)) : '0';
                break;

            case 'username':
                $username = get_post_meta($post_id, 'username', true);
                echo $username ? esc_html('@' . $username) : '—';
                break;

            case 'posted_date':
                $posted_date = get_post_meta($post_id, 'posted_date', true);
                if ($posted_date) {
                    $timestamp = strtotime($posted_date);
                    echo esc_html(date_i18n('M j, Y', $timestamp));
                } else {
                    echo '—';
                }
                break;
        }
    }

    /**
     * Make custom columns sortable
     */
    public static function make_columns_sortable($columns) {
        $columns['likes'] = 'likes_count';
        $columns['comments'] = 'comments_count';
        $columns['posted_date'] = 'posted_date';
        return $columns;
    }
}
