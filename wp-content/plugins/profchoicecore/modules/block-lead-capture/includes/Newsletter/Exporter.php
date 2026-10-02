<?php
/**
 * Newsletter subscribers CSV export: the subscribers' emails.
 *
 * Download headers and cell escaping adapted from the axellcore plugin's
 * import/export tool; the file is streamed directly instead of batched.
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore\Newsletter;

use ProfChoiceCore\Csv;
use WP_User_Query;

/**
 * Export handler for the Subscribers screen's Export button.
 */
final class Exporter {

	const ACTION     = 'profchoicecore_export_subscribers';
	const CAPABILITY = 'list_users';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'admin_post_' . self::ACTION, array( __CLASS__, 'download' ) );
	}

	/**
	 * The export URL (nonced).
	 *
	 * @return string
	 */
	public static function url() {
		return wp_nonce_url( admin_url( 'admin-post.php?action=' . self::ACTION ), self::ACTION );
	}

	/**
	 * Stream the subscribers' emails as CSV, one per line under an "Email"
	 * header, oldest first.
	 *
	 * @return void
	 */
	public static function download() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You are not allowed to export subscribers.', 'profchoicecore' ), 403 );
		}
		check_admin_referer( self::ACTION );

		$filename = sanitize_file_name( 'subscribers-' . gmdate( 'Y-m-d' ) . '.csv' );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

		$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		fwrite( $out, Csv::BOM ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
		fwrite( $out, Csv::line( array( __( 'Email', 'profchoicecore' ) ) ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite

		$page = 1;
		do {
			$emails = ( new WP_User_Query(
				array(
					'role'        => Subscribers::ROLE,
					'number'      => 500,
					'paged'       => $page,
					'orderby'     => 'registered',
					'order'       => 'ASC',
					'fields'      => 'user_email',
					'count_total' => false,
				)
			) )->get_results();

			foreach ( $emails as $email ) {
				fwrite( $out, Csv::line( array( (string) $email ) ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
			}

			++$page;
		} while ( count( $emails ) === 500 );

		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}
}
