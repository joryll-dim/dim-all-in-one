<?php
/**
 * Module Name: Reading Time
 * Description: Adds a [reading_time] shortcode that displays estimated reading time for posts
 * Version: 1.0.0
 * Author: DIM
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simple Reading Time Shortcode [reading_time]
 *
 * @return int Reading time in minutes
 */
function dfp_reading_time_shortcode() {
	// Get current post content
	$content = get_post_field( 'post_content', get_the_ID() );

	// Strip tags and get word count
	$word_count = str_word_count( strip_tags( strip_shortcodes( $content ) ) );

	// Calculate reading time (200 words per minute)
	$reading_time = ceil( $word_count / 200 );

	// Return just the number
	return $reading_time;
}

// Register the shortcode
add_shortcode( 'reading_time', 'dfp_reading_time_shortcode' );
