<?php
/**
 * Auto WebP Converter - Uninstall Handler
 * 
 * This file is executed when the plugin is deleted through WordPress admin
 */

// Exit if uninstall not called from WordPress
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Check if user wants to keep the conversion history table
$keep_table = get_option( 'awc_keep_table_on_uninstall' );

if ( ! $keep_table ) {
	// User wants to delete the table
	$table_name = $wpdb->prefix . 'awc_conversions';
	$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
}

// Always delete plugin options
delete_option( 'awc_db_version' );
delete_option( 'awc_keep_table_on_uninstall' );

// Note: Original image files are intentionally preserved
// Users can manually delete them if needed