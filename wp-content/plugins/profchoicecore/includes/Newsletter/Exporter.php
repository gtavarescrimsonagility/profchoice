<?php
/**
 * Newsletter subscribers CSV export.
 *
 * Download headers and cell escaping adapted from the axellcore plugin's
 * import/export tool; the file is streamed directly instead of batched.
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore\Newsletter;

use ProfChoiceCore\Csv;

/**
 * "Export CSV" button on the Subscribers screen and its download handler.
 */
final class Exporter {

	const ACTION     = 'profchoicecore_export_subscribers';
	const CAPABILITY = 'manage_options';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'manage_posts_extra_tablenav', array( __CLASS__, 'button' ) );
		add_action( 'admin_post_' . self::ACTION, array( __CLASS__, 'download' ) );
	}

	/**
	 * Print the export button above the subscribers list.
	 *
	 * @param string $which Tablenav position.
	 * @return void
	 */
	public static function button( $which ) {
		$screen = get_current_screen();
		if ( 'top' !== $which || ! $screen || Subscribers::POST_TYPE !== $screen->post_type || ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		$url = wp_nonce_url( admin_url( 'admin-post.php?action=' . self::ACTION ), self::ACTION );
		printf(
			'<div class="alignleft actions"><a class="button" href="%s">%s</a></div>',
			esc_url( $url ),
			esc_html__( 'Export CSV', 'profchoicecore' )
		);
	}

	/**
	 * Stream every subscriber as CSV.
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
		fwrite( $out, Csv::line( array( __( 'Email', 'profchoicecore' ), __( 'User', 'profchoicecore' ), __( 'Source', 'profchoicecore' ), __( 'Date', 'profchoicecore' ) ) ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite

		$page = 1;
		do {
			$ids = get_posts(
				array(
					'post_type'      => Subscribers::POST_TYPE,
					'post_status'    => 'any',
					'posts_per_page' => 500,
					'paged'          => $page,
					'orderby'        => 'date',
					'order'          => 'ASC',
					'fields'         => 'ids',
				)
			);

			foreach ( $ids as $id ) {
				$user   = get_userdata( (int) get_post_meta( $id, '_pc_user_id', true ) );
				$source = (int) get_post_meta( $id, '_pc_source', true );
				$row    = array(
					(string) get_post_meta( $id, '_pc_email', true ),
					$user ? $user->user_login : '',
					$source ? (string) get_permalink( $source ) : '',
					(string) get_post_field( 'post_date', $id ),
				);
				fwrite( $out, Csv::line( $row ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
			}

			++$page;
		} while ( count( $ids ) === 500 );

		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}
}
