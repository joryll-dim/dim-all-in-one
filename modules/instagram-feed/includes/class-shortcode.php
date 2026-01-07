<?php

/**
 * Shortcode functionality for Instagram Feed
 */

defined('ABSPATH') or die('No direct script access allowed');

class DIM_Instagram_Shortcode {

    public function __construct() {
        // Main grid shortcode
        add_shortcode('instagram_feed', array($this, 'instagram_feed_shortcode'));

        // Individual meta field shortcodes
        add_shortcode('instagram_caption', array($this, 'instagram_caption_shortcode'));
        add_shortcode('instagram_image', array($this, 'instagram_image_shortcode'));
        add_shortcode('instagram_link', array($this, 'instagram_link_shortcode'));
        add_shortcode('instagram_likes', array($this, 'instagram_likes_shortcode'));
        add_shortcode('instagram_comments', array($this, 'instagram_comments_shortcode'));
        add_shortcode('instagram_date', array($this, 'instagram_date_shortcode'));
        add_shortcode('instagram_username', array($this, 'instagram_username_shortcode'));
        add_shortcode('instagram_hashtags', array($this, 'instagram_hashtags_shortcode'));
    }

    /**
     * Main Instagram feed grid shortcode
     * Usage: [instagram_feed limit="12" columns="3"]
     */
    public function instagram_feed_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => '12',
            'columns' => '3',
            'show_caption' => 'no',
        ), $atts);

        $query_args = array(
            'post_type' => 'instagram_posts',
            'posts_per_page' => intval($atts['limit']),
            'orderby' => 'date',
            'order' => 'DESC',
            'post_status' => 'publish',
        );

        $instagram_query = new WP_Query($query_args);

        if (!$instagram_query->have_posts()) {
            return '<p>No Instagram posts found.</p>';
        }

        $columns = intval($atts['columns']);
        $show_caption = ($atts['show_caption'] === 'yes');

        ob_start();
        ?>
        <div class="dim-instagram-feed" data-columns="<?php echo esc_attr($columns); ?>">
            <style>
                .dim-instagram-feed {
                    display: grid;
                    grid-template-columns: repeat(<?php echo esc_attr($columns); ?>, 1fr);
                    gap: 15px;
                    margin: 20px 0;
                }
                .dim-instagram-post {
                    position: relative;
                    overflow: hidden;
                    aspect-ratio: 1;
                    border-radius: 8px;
                }
                .dim-instagram-post img {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                    transition: transform 0.3s ease;
                }
                .dim-instagram-post:hover img {
                    transform: scale(1.05);
                }
                .dim-instagram-overlay {
                    position: absolute;
                    bottom: 0;
                    left: 0;
                    right: 0;
                    background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);
                    padding: 15px;
                    color: white;
                    font-size: 14px;
                }
                @media (max-width: 768px) {
                    .dim-instagram-feed {
                        grid-template-columns: repeat(2, 1fr);
                    }
                }
                @media (max-width: 480px) {
                    .dim-instagram-feed {
                        grid-template-columns: 1fr;
                    }
                }
            </style>
            <?php
            while ($instagram_query->have_posts()) {
                $instagram_query->the_post();
                $post_id = get_the_ID();
                $image_url = get_post_meta($post_id, 'image_url', true);
                $post_url = get_post_meta($post_id, 'post_url', true);
                $caption = get_post_meta($post_id, 'caption', true);
                $likes = get_post_meta($post_id, 'likes_count', true);
                $comments = get_post_meta($post_id, 'comments_count', true);
                ?>
                <div class="dim-instagram-post">
                    <a href="<?php echo esc_url($post_url); ?>" target="_blank" rel="noopener noreferrer">
                        <?php if ($image_url): ?>
                            <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr(wp_trim_words($caption, 10, '...')); ?>" loading="lazy" referrerpolicy="no-referrer" crossorigin="anonymous">
                        <?php endif; ?>
                        <?php if ($show_caption && !empty($caption)): ?>
                            <div class="dim-instagram-overlay">
                                <p><?php echo esc_html(wp_trim_words($caption, 15, '...')); ?></p>
                            </div>
                        <?php endif; ?>
                    </a>
                </div>
                <?php
            }
            wp_reset_postdata();
            ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get current Instagram post ID from loop or specified ID
     */
    private function get_post_id($atts) {
        if (isset($atts['id']) && !empty($atts['id'])) {
            return intval($atts['id']);
        }
        return get_the_ID();
    }

    /**
     * Display Instagram post caption
     * Usage: [instagram_caption] or [instagram_caption id="123"]
     */
    public function instagram_caption_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $post_id = $this->get_post_id($atts);

        if (get_post_type($post_id) !== 'instagram_posts') {
            return '';
        }

        $caption = get_post_meta($post_id, 'caption', true);
        return $caption ? wp_kses_post($caption) : '';
    }

    /**
     * Display Instagram post image
     * Usage: [instagram_image] or [instagram_image id="123" size="medium"]
     */
    public function instagram_image_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
            'class' => 'instagram-image',
        ), $atts);

        $post_id = $this->get_post_id($atts);

        if (get_post_type($post_id) !== 'instagram_posts') {
            return '';
        }

        $image_url = get_post_meta($post_id, 'image_url', true);
        if (!$image_url) {
            return '';
        }

        $caption = get_post_meta($post_id, 'caption', true);
        $alt_text = $caption ? wp_trim_words($caption, 10, '...') : 'Instagram post';

        return sprintf(
            '<img src="%s" alt="%s" class="%s" loading="lazy">',
            esc_url($image_url),
            esc_attr($alt_text),
            esc_attr($atts['class'])
        );
    }

    /**
     * Display Instagram post link
     * Usage: [instagram_link text="View on Instagram"] or [instagram_link id="123"]
     */
    public function instagram_link_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
            'text' => 'View on Instagram',
            'class' => 'instagram-link',
            'target' => '_blank',
        ), $atts);

        $post_id = $this->get_post_id($atts);

        if (get_post_type($post_id) !== 'instagram_posts') {
            return '';
        }

        $link = get_post_meta($post_id, 'post_url', true);
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
     * Display Instagram post likes count
     * Usage: [instagram_likes] or [instagram_likes id="123"]
     */
    public function instagram_likes_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $post_id = $this->get_post_id($atts);

        if (get_post_type($post_id) !== 'instagram_posts') {
            return '';
        }

        $likes = get_post_meta($post_id, 'likes_count', true);
        return $likes ? esc_html(number_format($likes)) : '0';
    }

    /**
     * Display Instagram post comments count
     * Usage: [instagram_comments] or [instagram_comments id="123"]
     */
    public function instagram_comments_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $post_id = $this->get_post_id($atts);

        if (get_post_type($post_id) !== 'instagram_posts') {
            return '';
        }

        $comments = get_post_meta($post_id, 'comments_count', true);
        return $comments ? esc_html(number_format($comments)) : '0';
    }

    /**
     * Display Instagram post date
     * Usage: [instagram_date] or [instagram_date id="123" format="F j, Y"]
     */
    public function instagram_date_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
            'format' => 'F j, Y',
        ), $atts);

        $post_id = $this->get_post_id($atts);

        if (get_post_type($post_id) !== 'instagram_posts') {
            return '';
        }

        $posted_date = get_post_meta($post_id, 'posted_date', true);
        if (!$posted_date) {
            return '';
        }

        $timestamp = strtotime($posted_date);
        return $timestamp ? esc_html(date_i18n($atts['format'], $timestamp)) : '';
    }

    /**
     * Display Instagram username
     * Usage: [instagram_username] or [instagram_username id="123"]
     */
    public function instagram_username_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $post_id = $this->get_post_id($atts);

        if (get_post_type($post_id) !== 'instagram_posts') {
            return '';
        }

        $username = get_post_meta($post_id, 'username', true);
        return $username ? esc_html('@' . $username) : '';
    }

    /**
     * Display Instagram hashtags
     * Usage: [instagram_hashtags] or [instagram_hashtags id="123"]
     */
    public function instagram_hashtags_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $post_id = $this->get_post_id($atts);

        if (get_post_type($post_id) !== 'instagram_posts') {
            return '';
        }

        $hashtags = get_post_meta($post_id, 'hashtags', true);
        return $hashtags ? esc_html($hashtags) : '';
    }
}
