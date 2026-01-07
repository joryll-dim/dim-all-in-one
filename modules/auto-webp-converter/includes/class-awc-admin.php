<?php
/**
 * Auto WebP Converter - Admin Interface Class
 * 
 * Handles admin UI and AJAX operations
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AWC_Admin {
	
	public function __construct() {
		// Admin menu
		add_action( 'admin_menu', [ $this, 'add_menu_pages' ] );
		
		// AJAX handlers
		add_action( 'wp_ajax_awc_get_image_library', [ $this, 'ajax_get_image_library' ] );
		add_action( 'wp_ajax_awc_bulk_convert_image', [ $this, 'ajax_bulk_convert_image' ] );
		add_action( 'wp_ajax_awc_delete_all_originals', [ $this, 'ajax_delete_all_originals' ] );
		add_action( 'wp_ajax_awc_update_uninstall_setting', [ $this, 'ajax_update_uninstall_setting' ] );
	}
	
	/**
	 * Add admin menu pages
	 */
	public function add_menu_pages() {
		add_media_page(
			'Bulk WebP Converter',
			'Bulk WebP Convert',
			'upload_files',
			'awc-bulk-converter',
			[ $this, 'render_bulk_converter_page' ]
		);
	}
	
	/**
	 * Render bulk converter page
	 */
	public function render_bulk_converter_page() {
		// Get stats for display
		$stats = AWC_Converter::get_conversion_stats();
		?>
		<div class="wrap">
			<h1>Bulk WebP Converter</h1>
			<p>This tool will scan your Media Library for all JPG and PNG images and convert them to WebP, replacing the originals.</p>
			<p><strong>Warning:</strong> This process is irreversible and modifies your files and database. It is highly recommended to <strong>back up your `uploads` directory and your database</strong> before proceeding.</p>
			
			<div id="awc-bulk-actions">
				<button id="awc-start-bulk-convert" class="button button-primary">Start Bulk Conversion</button>
			</div>
			
			<div id="awc-summary" style="margin-top: 20px; font-weight: bold;"></div>
			
			<h3>Progress Log</h3>
			<div id="awc-progress-log" style="height: 400px; overflow-y: scroll; background: #fff; border: 1px solid #ccc; padding: 10px; font-family: monospace; white-space: pre-wrap;">
				Welcome! Click the button above to begin.
			</div>
			
			<?php if ( $stats && $stats->file_count > 0 ) : ?>
			<div style="margin-top: 40px; padding: 20px; background: #f0f0f1; border: 1px solid #c3c4c7;">
				<h3 style="margin-top: 0;">Original Files Management</h3>
				
				<p>
					You have <strong><?php echo esc_html( $stats->file_count ); ?> original files</strong> 
					from <strong><?php echo esc_html( $stats->image_count ); ?> images</strong> 
					taking up <strong><?php echo esc_html( $this->format_bytes( $stats->total_size ) ); ?></strong> of storage space.
				</p>
				
				<?php if ( $stats->oldest_date && $stats->newest_date ) : ?>
				<p>
					<?php 
					$oldest = date( 'M j, Y', strtotime( $stats->oldest_date ) );
					$newest = date( 'M j, Y', strtotime( $stats->newest_date ) );
					
					if ( $oldest === $newest ) : ?>
						These files were kept from bulk conversions on <strong><?php echo esc_html( $oldest ); ?></strong>.
					<?php else : ?>
						These files were kept from bulk conversions between 
						<strong><?php echo esc_html( $oldest ); ?></strong> and 
						<strong><?php echo esc_html( $newest ); ?></strong>.
					<?php endif; ?>
				</p>
				<?php endif; ?>
				
				<p>
					<button id="awc-delete-all-originals" class="button button-secondary">Delete All Original Files</button>
					<span class="description" style="margin-left: 10px;">This will free up <?php echo esc_html( $this->format_bytes( $stats->total_size ) ); ?></span>
				</p>
				
				<hr style="margin: 20px 0;">
				
				<p>
					<label>
						<input type="checkbox" id="awc-keep-table-uninstall" name="awc_keep_table_on_uninstall" value="1" 
							   <?php checked( get_option( 'awc_keep_table_on_uninstall' ), 1 ); ?> />
						Keep conversion history table when uninstalling plugin
					</label>
					<br>
					<span class="description">Original image files are always kept when uninstalling, regardless of this setting.</span>
				</p>
			</div>
			<?php endif; ?>
		</div>
		
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				const startButton = $('#awc-start-bulk-convert');
				const logBox = $('#awc-progress-log');
				const summaryBox = $('#awc-summary');
				let image_ids = [];
				let currentIndex = 0;
				let successCount = 0;
				let failureCount = 0;

				function logMessage(message) {
					logBox.append(message + "\n");
					logBox.scrollTop(logBox[0].scrollHeight);
				}

				// Bulk conversion
				startButton.on('click', function() {
					if ( ! confirm('Are you sure you want to start the bulk conversion? This cannot be undone.') ) return;

					startButton.prop('disabled', true).text('Processing...');
					logBox.html('');
					summaryBox.html('');
					logMessage('Starting... Fetching list of images from the Media Library.');

					$.post(ajaxurl, { 
						action: 'awc_get_image_library', 
						nonce: '<?php echo wp_create_nonce("awc_bulk_nonce"); ?>' 
					})
					.done(function(response) {
						if (response.success) {
							image_ids = response.data;
							if (image_ids.length > 0) {
								logMessage(`Found ${image_ids.length} images to process.`);
								currentIndex = 0; 
								successCount = 0; 
								failureCount = 0;
								processNextImage();
							} else {
								logMessage('No JPG or PNG images found to convert.');
								startButton.prop('disabled', false).text('Start Bulk Conversion');
							}
						} else {
							logMessage('Error: Could not retrieve image list. ' + (response.data.message || 'Unknown error.'));
							startButton.prop('disabled', false).text('Start Bulk Conversion');
						}
					})
					.fail(function() {
						logMessage('Error: Failed to communicate with the server.');
						startButton.prop('disabled', false).text('Start Bulk Conversion');
					});
				});

				function processNextImage() {
					if (currentIndex >= image_ids.length) {
						logMessage("\n--- Bulk Conversion Complete! ---");
						logMessage(`Finished! Successfully converted: ${successCount}. Failed or skipped: ${failureCount}.`);
						summaryBox.html(`Finished! Successfully converted: ${successCount}. Failed or skipped: ${failureCount}.`);
						startButton.prop('disabled', false).text('Start Bulk Conversion');
						
						// Reload page to show updated stats
						setTimeout(function() {
							location.reload();
						}, 2000);
						return;
					}

					const attachment_id = image_ids[currentIndex];
					logMessage(`[${currentIndex + 1}/${image_ids.length}] Processing Attachment ID: ${attachment_id}...`);

					$.post(ajaxurl, { 
						action: 'awc_bulk_convert_image', 
						nonce: '<?php echo wp_create_nonce("awc_bulk_nonce"); ?>', 
						attachment_id: attachment_id 
					})
					.done(function(response) {
						if (response.success) {
							logMessage(` -> SUCCESS: ${response.data.message}`);
							successCount++;
						} else {
							logMessage(` -> FAILED: ${response.data.message}`);
							failureCount++;
						}
					})
					.fail(function() {
						logMessage(` -> FAILED: Server communication error for Attachment ID ${attachment_id}.`);
						failureCount++;
					})
					.always(function() {
						currentIndex++;
						setTimeout(processNextImage, 50);
					});
				}
				
				// Delete all originals
				$('#awc-delete-all-originals').on('click', function() {
					if (!confirm('Are you sure you want to delete all original files? This cannot be undone.')) {
						return;
					}
					
					const button = $(this);
					button.prop('disabled', true).text('Deleting...');
					
					$.post(ajaxurl, {
						action: 'awc_delete_all_originals',
						nonce: '<?php echo wp_create_nonce("awc_admin_nonce"); ?>'
					})
					.done(function(response) {
						if (response.success) {
							alert(`Successfully deleted ${response.data.deleted} files and freed ${response.data.freed_space}.`);
							location.reload();
						} else {
							alert('Error: ' + response.data.message);
							button.prop('disabled', false).text('Delete All Original Files');
						}
					})
					.fail(function() {
						alert('Failed to communicate with server.');
						button.prop('disabled', false).text('Delete All Original Files');
					});
				});
				
				// Update uninstall setting
				$('#awc-keep-table-uninstall').on('change', function() {
					const keepTable = $(this).is(':checked') ? 1 : 0;
					
					$.post(ajaxurl, {
						action: 'awc_update_uninstall_setting',
						nonce: '<?php echo wp_create_nonce("awc_admin_nonce"); ?>',
						keep_table: keepTable
					});
				});
			});
		</script>
		<?php
	}
	
	/**
	 * AJAX: Get image library
	 */
	public function ajax_get_image_library() {
		check_ajax_referer( 'awc_bulk_nonce', 'nonce' );
		
		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( [ 'message' => 'Permission denied.' ] );
		}

		$query = new WP_Query( [
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'post_mime_type' => [ 'image/jpeg', 'image/png' ],
			'posts_per_page' => -1,
			'fields'         => 'ids',
		] );
		
		wp_send_json_success( $query->posts );
	}
	
	/**
	 * AJAX: Bulk convert single image
	 */
	public function ajax_bulk_convert_image() {
		check_ajax_referer( 'awc_bulk_nonce', 'nonce' );
		
		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( [ 'message' => 'Permission denied.' ] );
		}

		$attachment_id = isset( $_POST['attachment_id'] ) ? intval( $_POST['attachment_id'] ) : 0;
		if ( $attachment_id <= 0 ) {
			wp_send_json_error( [ 'message' => 'Invalid Attachment ID.' ] );
		}

		$converter = new AWC_Converter();
		$result = $converter->run_conversion_for_attachment( $attachment_id, true ); // true = bulk conversion

		if ( $result ) {
			wp_send_json_success( [ 'message' => 'File converted to WebP.' ] );
		} else {
			wp_send_json_error( [ 'message' => 'Conversion failed or was not necessary.' ] );
		}
	}
	
	/**
	 * AJAX: Delete all original files
	 */
	public function ajax_delete_all_originals() {
		check_ajax_referer( 'awc_admin_nonce', 'nonce' );
		
		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( [ 'message' => 'Permission denied.' ] );
		}
		
		$result = AWC_Converter::delete_all_originals();
		
		wp_send_json_success( [
			'deleted' => $result['deleted'],
			'freed_space' => $this->format_bytes( $result['freed_space'] )
		] );
	}
	
	/**
	 * AJAX: Update uninstall setting
	 */
	public function ajax_update_uninstall_setting() {
		check_ajax_referer( 'awc_admin_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Permission denied.' ] );
		}
		
		$keep_table = isset( $_POST['keep_table'] ) ? intval( $_POST['keep_table'] ) : 0;
		update_option( 'awc_keep_table_on_uninstall', $keep_table );
		
		wp_send_json_success();
	}
	
	/**
	 * Format bytes to human readable
	 */
	private function format_bytes( $bytes, $precision = 2 ) {
		$units = [ 'B', 'KB', 'MB', 'GB', 'TB' ];
		
		$bytes = max( $bytes, 0 );
		$pow = floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) );
		$pow = min( $pow, count( $units ) - 1 );
		
		$bytes /= pow( 1024, $pow );
		
		return round( $bytes, $precision ) . ' ' . $units[$pow];
	}
}