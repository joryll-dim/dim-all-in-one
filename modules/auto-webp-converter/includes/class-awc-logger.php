<?php
/**
 * Auto WebP Converter - Logger Class
 * 
 * Handles error logging and debugging
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AWC_Logger {
	/**
	 * Log an error message with context
	 *
	 * @param string $message Error message
	 * @param array  $context Additional context
	 */
	public function error( $message, $context = [] ) {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		$log_entry = sprintf(
			'[AWC] %s | Context: %s',
			$message,
			! empty( $context ) ? json_encode( $context ) : 'none'
		);

		error_log( $log_entry );
	}

	/**
	 * Log a debug message with context
	 *
	 * @param string $message Debug message
	 * @param array  $context Additional context
	 */
	public function debug( $message, $context = [] ) {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		$log_entry = sprintf(
			'[AWC Debug] %s | Context: %s',
			$message,
			! empty( $context ) ? json_encode( $context ) : 'none'
		);

		error_log( $log_entry );
	}

	/**
	 * Log a warning message with context
	 *
	 * @param string $message Warning message
	 * @param array  $context Additional context
	 */
	public function warning( $message, $context = [] ) {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		$log_entry = sprintf(
			'[AWC Warning] %s | Context: %s',
			$message,
			! empty( $context ) ? json_encode( $context ) : 'none'
		);

		error_log( $log_entry );
	}
} 