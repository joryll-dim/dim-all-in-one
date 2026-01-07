<?php
/**
 * Auto WebP Converter - Core Converter Class
 * 
 * Handles image conversion and database operations
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AWC_Converter {
	private readonly array $allowed_types;
	private readonly int $quality;
	private bool $is_converting = false;
	private static $table_name;

	public function __construct() {
		global $wpdb;
		self::$table_name = $wpdb->prefix . 'awc_conversions';
		
		$this->allowed_types = [ 'image/jpeg', 'image/png' ];
		$this->quality       = AWC_WEBP_QUALITY;

		// Prioritize Imagick over GD
		add_filter( 'wp_image_editors', [ $this, 'prioritize_imagick' ] );
		
		// Hook into the metadata generation process for immediate conversion
		add_filter( 'wp_generate_attachment_metadata', [ $this, 'convert_on_metadata_generation' ], 10, 2 );
	}
	
	/**
	 * Plugin activation
	 */
	public static function activate() {
		// Check for WebP support
		self::check_webp_support();
		
		// Create database tables
		self::create_tables();
		
		// Set default options
		add_option( 'awc_keep_table_on_uninstall', 0 );
	}
	
	/**
	 * Check if server supports WebP
	 */
	private static function check_webp_support() {
		if ( ! class_exists( 'WP_Image_Editor' ) ) require_once ABSPATH . WPINC . '/class-wp-image-editor.php';
		if ( ! class_exists( 'WP_Image_Editor_GD' ) ) require_once ABSPATH . WPINC . '/class-wp-image-editor-gd.php';
		if ( ! class_exists( 'WP_Image_Editor_Imagick' ) ) require_once ABSPATH . WPINC . '/class-wp-image-editor-imagick.php';

		$has_webp_support = false;
		$editors = apply_filters( 'wp_image_editors', [ 'WP_Image_Editor_Imagick', 'WP_Image_Editor_GD' ] );

		foreach ( $editors as $editor_class ) {
			if ( class_exists( $editor_class ) && call_user_func( [ $editor_class, 'test' ] ) ) {
				if ( call_user_func( [ $editor_class, 'supports_mime_type' ], 'image/webp' ) ) {
					$has_webp_support = true;
					break;
				}
			}
		}

		if ( ! $has_webp_support ) {
			deactivate_plugins( plugin_basename( dirname( __DIR__ ) . '/auto-webp-converter.php' ) );
			wp_die(
				'<strong>Auto WebP Converter:</strong> No suitable image editor with WebP support found.',
				'Plugin Activation Error',
				[ 'back_link' => true ]
			);
		}
	}
	
	/**
	 * Create database tables
	 */
	private static function create_tables() {
		global $wpdb;
		
		$charset_collate = $wpdb->get_charset_collate();
		$table_name = $wpdb->prefix . 'awc_conversions';
		
		$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			attachment_id bigint(20) NOT NULL,
			file_path varchar(500) NOT NULL,
			file_size bigint(20) NOT NULL,
			conversion_date datetime DEFAULT CURRENT_TIMESTAMP,
			is_bulk tinyint(1) DEFAULT 0,
			PRIMARY KEY (id),
			KEY attachment_id (attachment_id),
			KEY is_bulk (is_bulk)
		) $charset_collate;";
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
		
		update_option( 'awc_db_version', AWC_DB_VERSION );
	}
	
	/**
	 * Prioritize Imagick over GD
	 */
	public function prioritize_imagick( $editors ) {
		return [ 'WP_Image_Editor_Imagick', 'WP_Image_Editor_GD' ];
	}
	
	/**
	 * Convert images during metadata generation
	 */
	public function convert_on_metadata_generation( $metadata, $attachment_id ) {
		if ( $this->is_converting ) {
			return $metadata;
		}
		
		$mime_type = get_post_mime_type( $attachment_id );
		if ( ! in_array( $mime_type, $this->allowed_types, true ) ) {
			return $metadata;
		}
		
		$this->is_converting = true;
		$conversion_result = $this->run_conversion_for_attachment( $attachment_id, false );
		$this->is_converting = false;
		
		if ( $conversion_result ) {
			$new_metadata = wp_get_attachment_metadata( $attachment_id );
			return $new_metadata ?: $metadata;
		}
		
		return $metadata;
	}
	
	/**
	 * Main conversion engine
	 */
	public function run_conversion_for_attachment( int $attachment_id, bool $is_bulk = false ): bool {
		if ( get_post_mime_type( $attachment_id ) === 'image/webp' ) {
			return false;
		}

		if ( ! in_array( get_post_mime_type( $attachment_id ), $this->allowed_types, true ) ) {
			return false;
		}
		
		$original_metadata = wp_get_attachment_metadata( $attachment_id );
		if ( empty( $original_metadata ) ) {
			return false;
		}
		
		$main_file_path = get_attached_file( $attachment_id, true );
		if ( ! $main_file_path || ! file_exists( $main_file_path ) ) {
			return false;
		}
		
		$base_dir = dirname( $main_file_path );
		$files_to_process = [];
		
		// Track original extension
		$original_extension = pathinfo( $main_file_path, PATHINFO_EXTENSION );
		
		// Check for scaled version
		$true_original_filename = $original_metadata['original_image'] ?? null;
		
		// 1. Convert main file
		$main_conv = $this->convert_single_file( $main_file_path );
		if ( ! $main_conv ) {
			return false;
		}
		$files_to_process[] = [
			'original' => $main_file_path,
			'size' => filesize( $main_file_path )
		];
		
		// 2. Convert thumbnails
		$new_sizes = [];
		if ( ! empty( $original_metadata['sizes'] ) ) {
			foreach ( $original_metadata['sizes'] as $size_name => $size_info ) {
				$thumb_path = $base_dir . '/' . $size_info['file'];
				if ( file_exists( $thumb_path ) ) {
					$thumb_conv = $this->convert_single_file( $thumb_path );
					if ( $thumb_conv ) {
						$files_to_process[] = [
							'original' => $thumb_path,
							'size' => filesize( $thumb_path )
						];
						$size_info['file'] = basename( $thumb_conv['path'] );
						$size_info['mime-type'] = 'image/webp';
						$size_info['filesize'] = filesize( $thumb_conv['path'] );
					}
				}
				$new_sizes[ $size_name ] = $size_info;
			}
		}
		
		// 3. Convert true original if exists
		if ( $true_original_filename ) {
			$true_original_path = $base_dir . '/' . $true_original_filename;
			if ( file_exists( $true_original_path ) ) {
				$original_conv = $this->convert_single_file( $true_original_path );
				if ( $original_conv ) {
					$files_to_process[] = [
						'original' => $true_original_path,
						'size' => filesize( $true_original_path )
					];
					$original_metadata['original_image'] = basename( $original_conv['path'] );
				}
			}
		}

		// 4. Update metadata
		$new_metadata = $original_metadata;
		$new_metadata['file'] = _wp_relative_upload_path( $main_conv['path'] );
		$new_metadata['filesize'] = filesize( $main_conv['path'] );
		$new_metadata['sizes'] = $new_sizes;

		// 5. Update database
		update_attached_file( $attachment_id, $main_conv['path'] );
		wp_update_attachment_metadata( $attachment_id, $new_metadata );
		wp_update_post( [ 'ID' => $attachment_id, 'post_mime_type' => 'image/webp' ] );
		
		// 6. Handle original files
		if ( $is_bulk ) {
			// Track files for bulk conversion
			foreach ( $files_to_process as $file_info ) {
				$this->track_conversion( $attachment_id, $file_info['original'], $file_info['size'], true );
			}
		} else {
			// Delete files for new uploads
			foreach ( $files_to_process as $file_info ) {
				if ( file_exists( $file_info['original'] ) ) {
					@unlink( $file_info['original'] );
				}
			}
		}

		return true;
	}
	
	/**
	 * Convert single file to WebP
	 */
	private function convert_single_file( string $source_path ) {
		if ( ! file_exists( $source_path ) ) return false;
		
		$mime_type = wp_check_filetype( $source_path )['type'];
		if ( $mime_type === 'image/webp' ) {
			return false;
		}
		
		$editor = wp_get_image_editor( $source_path );
		if ( is_wp_error( $editor ) ) return false;
		
		// Handle palette PNGs if using GD
		if ( $editor instanceof WP_Image_Editor_GD && $mime_type === 'image/png' && awc_is_problematic_palette_png_with_gd( $source_path ) ) {
			$this->convert_palette_png_to_truecolor( $source_path );
			$editor = wp_get_image_editor( $source_path );
			if ( is_wp_error( $editor ) ) return false;
		}
		
		// Generate unique WebP filename using WordPress API
		$webp_path = substr( $source_path, 0, strrpos( $source_path, '.' ) ) . '.webp';
		$webp_filename = wp_unique_filename( dirname( $source_path ), basename( $webp_path ) );
		$webp_path = dirname( $source_path ) . '/' . $webp_filename;
		$editor->set_quality( $this->quality );
		$result = $editor->save( $webp_path, 'image/webp' );

		if ( is_wp_error( $result ) ) return false;
		@chmod( $result['path'], 0644 );
		return $result;
	}
	
	/**
	 * Convert palette PNG to true color
	 */
	private function convert_palette_png_to_truecolor( string $png_path ): bool {
		if ( ! function_exists( 'imagecreatefrompng' ) || ! function_exists( 'imagepalettetotruecolor' ) ) {
			return false;
		}
		
		$image = @imagecreatefrompng( $png_path );
		if ( ! $image ) {
			return false;
		}
		
		if ( ! imageistruecolor( $image ) ) {
			imagepalettetotruecolor( $image );
			$saved = @imagepng( $image, $png_path, 9 );
			imagedestroy( $image );
			return $saved;
		}
		
		imagedestroy( $image );
		return true;
	}
	
	/**
	 * Track file conversion in database
	 */
	private function track_conversion( $attachment_id, $file_path, $file_size, $is_bulk ) {
		global $wpdb;
		
		$wpdb->insert(
			self::$table_name,
			[
				'attachment_id' => $attachment_id,
				'file_path' => $file_path,
				'file_size' => $file_size,
				'conversion_date' => current_time( 'mysql' ),
				'is_bulk' => $is_bulk ? 1 : 0
			],
			[ '%d', '%s', '%d', '%s', '%d' ]
		);
	}
	
	/**
	 * Get conversion statistics
	 */
	public static function get_conversion_stats() {
		global $wpdb;
		
		$stats = $wpdb->get_row( "
			SELECT 
				COUNT(*) as file_count,
				COUNT(DISTINCT attachment_id) as image_count,
				SUM(file_size) as total_size,
				MIN(conversion_date) as oldest_date,
				MAX(conversion_date) as newest_date
			FROM " . self::$table_name . "
			WHERE is_bulk = 1
		" );
		
		return $stats;
	}
	
	/**
	 * Delete all original files
	 */
	public static function delete_all_originals() {
		global $wpdb;
		
		$files = $wpdb->get_results( "
			SELECT id, file_path, file_size 
			FROM " . self::$table_name . "
			WHERE is_bulk = 1
		" );
		
		$deleted_count = 0;
		$freed_space = 0;
		
		foreach ( $files as $file ) {
			if ( file_exists( $file->file_path ) ) {
				if ( @unlink( $file->file_path ) ) {
					$deleted_count++;
					$freed_space += $file->file_size;
					$wpdb->delete( self::$table_name, [ 'id' => $file->id ], [ '%d' ] );
				}
			} else {
				// Remove from database if file doesn't exist
				$wpdb->delete( self::$table_name, [ 'id' => $file->id ], [ '%d' ] );
			}
		}
		
		return [
			'deleted' => $deleted_count,
			'freed_space' => $freed_space
		];
	}
}