<?php

/**
 * Shortcode functionality
 */

defined('ABSPATH') or die('No direct script access allowed');

class DFP_Reviews_Shortcode {

    public function __construct() {
        add_shortcode('dfp_reviews', array($this, 'handle_shortcode'));

        // Register meta field shortcodes
        add_shortcode('review_rating', array($this, 'review_rating_shortcode'));
        add_shortcode('review_author', array($this, 'review_author_shortcode'));
        add_shortcode('review_text', array($this, 'review_text_shortcode'));
        add_shortcode('review_image', array($this, 'review_image_shortcode'));
        add_shortcode('review_link', array($this, 'review_link_shortcode'));
        add_shortcode('review_date', array($this, 'review_date_shortcode'));
        add_shortcode('review_stars', array($this, 'review_stars_shortcode'));
    }

    public function handle_shortcode($atts) {
        $atts = shortcode_atts(
            array(
                'clinic' => '1',
                'type' => 'reviews',
            ),
            $atts,
            'dfp_reviews'
        );

        $source = intval($atts['clinic']);
        $type = sanitize_text_field($atts['type']);
        $clinic_count = max(1, get_option('dfp_reviews_clinic_count', 1));

        if ($type === 'reviews' && $source <= $clinic_count) {
            return esc_html(strval(intval(get_option("dfp_reviews_total_reviews_{$source}", 0))));
        } elseif ($type === 'stars' && $source <= $clinic_count) {
            return esc_html(number_format(floatval(get_option("dfp_reviews_total_stars_{$source}", 0)), 1));
        } elseif ($type === 'total_reviews_all') {
            require_once plugin_dir_path(__FILE__) . 'class-api.php';
            $total_stats = DFP_Reviews_API::calculate_total_reviews();
            return esc_html(strval($total_stats['total_reviews']));
        } elseif ($type === 'average_stars_all') {
            require_once plugin_dir_path(__FILE__) . 'class-api.php';
            $total_stats = DFP_Reviews_API::calculate_total_reviews();
            return esc_html(number_format($total_stats['average_stars'], 1));
        }

        return '';
    }

    /**
     * Get current review post ID from loop or specified ID
     */
    private function get_review_id($atts) {
        if (isset($atts['id']) && !empty($atts['id'])) {
            return intval($atts['id']);
        }
        return get_the_ID();
    }

    /**
     * Display review rating (numeric)
     * Usage: [review_rating] or [review_rating id="123"]
     */
    public function review_rating_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $post_id = $this->get_review_id($atts);

        if (get_post_type($post_id) !== 'google_reviews') {
            return '';
        }

        $rating = get_post_meta($post_id, 'review_rating', true);
        return $rating ? esc_html($rating) : '';
    }

    /**
     * Display review author name
     * Usage: [review_author] or [review_author id="123"]
     */
    public function review_author_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $post_id = $this->get_review_id($atts);

        if (get_post_type($post_id) !== 'google_reviews') {
            return '';
        }

        $author = get_post_meta($post_id, 'author_title', true);
        return $author ? esc_html($author) : '';
    }

    /**
     * Display review text
     * Usage: [review_text] or [review_text id="123"]
     */
    public function review_text_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $post_id = $this->get_review_id($atts);

        if (get_post_type($post_id) !== 'google_reviews') {
            return '';
        }

        $text = get_post_meta($post_id, 'review_text', true);
        return $text ? wp_kses_post($text) : '';
    }

    /**
     * Display review author image
     * Usage: [review_image] or [review_image id="123" size="thumbnail"]
     */
    public function review_image_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
            'size' => 'thumbnail',
            'class' => 'review-author-image'
        ), $atts);

        $post_id = $this->get_review_id($atts);

        if (get_post_type($post_id) !== 'google_reviews') {
            return '';
        }

        $image_url = get_post_meta($post_id, 'author_image', true);
        if (!$image_url) {
            return '';
        }

        $author = get_post_meta($post_id, 'author_title', true);
        $alt_text = $author ? sprintf('Profile picture of %s', $author) : 'Reviewer profile picture';

        return sprintf(
            '<img src="%s" alt="%s" class="%s" loading="lazy">',
            esc_url($image_url),
            esc_attr($alt_text),
            esc_attr($atts['class'])
        );
    }

    /**
     * Display review link to Google
     * Usage: [review_link text="Read on Google"] or [review_link id="123"]
     */
    public function review_link_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
            'text' => 'Read on Google',
            'class' => 'review-link',
            'target' => '_blank'
        ), $atts);

        $post_id = $this->get_review_id($atts);

        if (get_post_type($post_id) !== 'google_reviews') {
            return '';
        }

        $link = get_post_meta($post_id, 'review_link', true);
        if (!$link) {
            return '';
        }

        $rel = ($atts['target'] === '_blank') ? 'noopener noreferrer' : '';

        return sprintf(
            '<a href="%s" class="%s" target="%s" rel="%s">%s</a>',
            esc_url($link),
            esc_attr($atts['class']),
            esc_attr($atts['target']),
            esc_attr($rel),
            esc_html($atts['text'])
        );
    }

    /**
     * Display review date
     * Usage: [review_date] or [review_date id="123" format="F j, Y"]
     */
    public function review_date_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
            'format' => 'F j, Y'
        ), $atts);

        $post_id = $this->get_review_id($atts);

        if (get_post_type($post_id) !== 'google_reviews') {
            return '';
        }

        $datetime = get_post_meta($post_id, 'review_datetime_utc', true);
        if (!$datetime) {
            return '';
        }

        $timestamp = strtotime($datetime);
        return $timestamp ? esc_html(date($atts['format'], $timestamp)) : '';
    }

    /**
     * Display review rating as stars
     * Usage: [review_stars] or [review_stars id="123" style="unicode"]
     */
    public function review_stars_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
            'style' => 'unicode' // 'unicode' or 'html'
        ), $atts);

        $post_id = $this->get_review_id($atts);

        if (get_post_type($post_id) !== 'google_reviews') {
            return '';
        }

        $rating = intval(get_post_meta($post_id, 'review_rating', true));
        if (!$rating || $rating < 1 || $rating > 5) {
            return '';
        }

        if ($atts['style'] === 'html') {
            // HTML/CSS stars (requires custom CSS)
            $output = '<div class="review-stars" role="img" aria-label="' . $rating . ' out of 5 stars">';
            for ($i = 1; $i <= 5; $i++) {
                $class = ($i <= $rating) ? 'star filled' : 'star empty';
                $output .= '<span class="' . $class . '">★</span>';
            }
            $output .= '</div>';
            return $output;
        } else {
            // Unicode stars (default)
            return str_repeat('⭐', $rating);
        }
    }
}