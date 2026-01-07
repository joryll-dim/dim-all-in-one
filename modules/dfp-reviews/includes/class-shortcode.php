<?php

/**
 * Shortcode functionality
 */

defined('ABSPATH') or die('No direct script access allowed');

class DFP_Reviews_Shortcode {

    public function __construct() {
        add_shortcode('dfp_reviews', array($this, 'handle_shortcode'));
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
}