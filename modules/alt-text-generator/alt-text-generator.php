<?php
/**
 * Module Name: Alt Text Generator
 * Description: Scans images and copies the title to alt text if alt text is missing
 * Version: 1.1
 * Author: Dental Funnels The Platform
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Copy_Title_To_Alt {

    public function __init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_copy_title_batch', array($this, 'process_batch'));
        add_action('wp_ajax_get_image_count', array($this, 'get_image_count'));
    }

    // Add menu item to WordPress admin
    public function add_admin_menu() {
        add_management_page(
            'Copy Title to Alt Text',
            'Copy Title to Alt',
            'manage_options',
            'copy-title-to-alt',
            array($this, 'admin_page')
        );
    }

    // Admin page HTML
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Copy Title to Alt Text</h1>
            <p>This tool will scan all images in your media library and copy the title to the alt text field if the alt text is empty.</p>

            <div id="progress-container" style="display:none; margin: 20px 0;">
                <div style="background: #f0f0f1; border: 1px solid #c3c4c7; border-radius: 4px; padding: 3px;">
                    <div id="progress-bar" style="background: #2271b1; height: 24px; border-radius: 2px; width: 0%; transition: width 0.3s;"></div>
                </div>
                <p id="progress-text" style="margin-top: 10px;">Preparing...</p>
            </div>

            <p>
                <button id="start-batch" class="button button-primary">Scan and Update Images</button>
                <button id="cancel-batch" class="button" style="display:none;">Cancel</button>
            </p>

            <div id="results" style="margin-top: 20px;"></div>
        </div>

        <style>
            .success-message {
                background: #d5f4e6;
                border-left: 4px solid #00a32a;
                padding: 12px;
                margin: 20px 0;
            }
            .error-message {
                background: #f9e2e2;
                border-left: 4px solid #d63638;
                padding: 12px;
                margin: 20px 0;
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            let cancelled = false;
            let totalImages = 0;
            let processedImages = 0;
            let updatedImages = 0;

            $('#start-batch').on('click', function() {
                cancelled = false;
                processedImages = 0;
                updatedImages = 0;

                $('#start-batch').prop('disabled', true).hide();
                $('#cancel-batch').show();
                $('#progress-container').show();
                $('#results').html('');

                // Get total count first
                $.post(ajaxurl, {
                    action: 'get_image_count',
                    nonce: '<?php echo wp_create_nonce('copy_title_batch_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        totalImages = response.data.count;
                        $('#progress-text').text('Found ' + totalImages + ' images. Starting...');
                        processBatch(0);
                    }
                });
            });

            $('#cancel-batch').on('click', function() {
                cancelled = true;
                $('#cancel-batch').prop('disabled', true).text('Cancelling...');
            });

            function processBatch(offset) {
                if (cancelled) {
                    showResults('Cancelled', 'error-message');
                    resetButtons();
                    return;
                }

                $.post(ajaxurl, {
                    action: 'copy_title_batch',
                    nonce: '<?php echo wp_create_nonce('copy_title_batch_nonce'); ?>',
                    offset: offset
                }, function(response) {
                    if (response.success) {
                        processedImages += response.data.processed;
                        updatedImages += response.data.updated;

                        let percentage = totalImages > 0 ? (processedImages / totalImages * 100) : 0;
                        $('#progress-bar').css('width', percentage + '%');
                        $('#progress-text').text('Processed ' + processedImages + ' of ' + totalImages + ' images (' + updatedImages + ' updated)');

                        if (response.data.has_more) {
                            processBatch(offset + response.data.processed);
                        } else {
                            showResults('Complete! Updated ' + updatedImages + ' images out of ' + totalImages + ' total.', 'success-message');
                            resetButtons();
                        }
                    } else {
                        showResults('Error: ' + response.data, 'error-message');
                        resetButtons();
                    }
                }).fail(function() {
                    showResults('Error: Request failed', 'error-message');
                    resetButtons();
                });
            }

            function showResults(message, className) {
                $('#results').html('<div class="' + className + '">' + message + '</div>');
            }

            function resetButtons() {
                $('#start-batch').prop('disabled', false).show();
                $('#cancel-batch').hide().prop('disabled', false).text('Cancel');
            }
        });
        </script>
        <?php
    }

    // Get total image count
    public function get_image_count() {
        check_ajax_referer('copy_title_batch_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $count = wp_count_posts('attachment');
        $total = 0;

        foreach ($count as $status => $num) {
            $total += $num;
        }

        wp_send_json_success(array('count' => $total));
    }

    // Check if title is just a filename with extension
    private function is_filename_title($title) {
        // Common image extensions
        $extensions = array('.jpg', '.jpeg', '.png', '.gif', '.webp', '.bmp', '.svg', '.ico');

        $title_lower = strtolower($title);

        foreach ($extensions as $ext) {
            if (substr($title_lower, -strlen($ext)) === $ext) {
                return true;
            }
        }

        return false;
    }

    // Process batch of images
    public function process_batch() {
        check_ajax_referer('copy_title_batch_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $batch_size = 50; // Process 50 images at a time

        $args = array(
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'posts_per_page' => $batch_size,
            'offset' => $offset,
            'post_status' => 'any',
            'orderby' => 'ID',
            'order' => 'ASC'
        );

        $images = get_posts($args);
        $updated_count = 0;

        foreach ($images as $image) {
            $alt_text = get_post_meta($image->ID, '_wp_attachment_image_alt', true);

            // Only update if alt text is empty, title exists, and title is not a filename
            if (empty($alt_text) && !empty($image->post_title) && !$this->is_filename_title($image->post_title)) {
                update_post_meta($image->ID, '_wp_attachment_image_alt', $image->post_title);
                $updated_count++;
            }
        }

        $has_more = count($images) === $batch_size;

        wp_send_json_success(array(
            'processed' => count($images),
            'updated' => $updated_count,
            'has_more' => $has_more
        ));
    }
}

// Initialize the module
$copy_title_to_alt = new Copy_Title_To_Alt();
$copy_title_to_alt->__init();
