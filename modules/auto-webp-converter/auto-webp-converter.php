<?php
/**
 * Module Name: Auto WebP Converter
 * Description: Automatically converts new JPG/PNG uploads and existing library images to WebP.
 * Version: 1.1.1
 * Author: Dental Funnels The Platform
 */

// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define module constants
if ( ! defined( 'AWC_VERSION' ) ) {
	define( 'AWC_VERSION', '1.1.1' );
}
if ( ! defined( 'AWC_PLUGIN_DIR' ) ) {
	define( 'AWC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'AWC_PLUGIN_URL' ) ) {
	define( 'AWC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'AWC_WEBP_QUALITY' ) ) {
	define( 'AWC_WEBP_QUALITY', 82 );
}
if ( ! defined( 'AWC_DB_VERSION' ) ) {
	define( 'AWC_DB_VERSION', '1.0' );
}

// Load required classes
require_once AWC_PLUGIN_DIR . 'includes/class-awc-converter.php';
require_once AWC_PLUGIN_DIR . 'includes/class-awc-admin.php';

// Module initialization
function awc_module_init() {
	// Run activation setup if needed (for database tables)
	AWC_Converter::activate();

	// Initialize converter
	new AWC_Converter();

	// Initialize admin interface
	if ( is_admin() ) {
		new AWC_Admin();
	}
}
awc_module_init();

// Helper function to check if PNG is palette-indexed
function awc_is_problematic_palette_png_with_gd( string $image_path ): bool {
	if ( ! file_exists( $image_path ) || ! is_readable( $image_path ) ) return false;
	$image_type = @exif_imagetype( $image_path );
	if ( false === $image_type || image_type_to_mime_type( $image_type ) !== 'image/png' ) return false;
	if ( ! function_exists( 'imagecreatefrompng' ) || ! function_exists( 'imageistruecolor' ) ) return false;
	$img = @imagecreatefrompng( $image_path );
	if ( ! $img ) return false;
	$is_palette = ! imageistruecolor( $img );
	imagedestroy( $img );
	return $is_palette;
}