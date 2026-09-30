<?php
/**
 * Newsletter subscribers post type.
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore\Newsletter;

/**
 * Private `pc_subscriber` post type: one post per email address.
 */
final class Subscribers {

	const POST_TYPE = 'pc_subscriber';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'column' ), 10, 2 );
	}

	/**
	 * Register the post type. Subscribers are only created by the form.
	 *
	 * @return void
	 */
	public static function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'       => array(
					'name'          => __( 'Newsletter Subscribers', 'profchoicecore' ),
					'singular_name' => __( 'Newsletter Subscriber', 'profchoicecore' ),
					'menu_name'     => __( 'Subscribers', 'profchoicecore' ),
					'all_items'     => __( 'Subscribers', 'profchoicecore' ),
					'search_items'  => __( 'Search Subscribers', 'profchoicecore' ),
					'not_found'     => __( 'No subscribers yet.', 'profchoicecore' ),
				),
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => true,
				'menu_icon'    => 'dashicons-email-alt',
				'supports'     => array( 'title' ),
				'map_meta_cap' => true,
				'capabilities' => array( 'create_posts' => 'do_not_allow' ),
				'rewrite'      => false,
				'query_var'    => false,
			)
		);
	}

	/**
	 * List table columns.
	 *
	 * @param array<string, string> $columns Default columns.
	 * @return array<string, string>
	 */
	public static function columns( $columns ) {
		return array(
			'cb'        => $columns['cb'] ?? '',
			'title'     => __( 'Email', 'profchoicecore' ),
			'pc_user'   => __( 'User', 'profchoicecore' ),
			'pc_source' => __( 'Source', 'profchoicecore' ),
			'date'      => $columns['date'] ?? __( 'Date', 'profchoicecore' ),
		);
	}

	/**
	 * Render a custom column.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Subscriber post ID.
	 * @return void
	 */
	public static function column( $column, $post_id ) {
		if ( 'pc_user' === $column ) {
			$user = get_userdata( (int) get_post_meta( $post_id, '_pc_user_id', true ) );
			if ( $user ) {
				printf( '<a href="%s">%s</a>', esc_url( get_edit_user_link( $user->ID ) ), esc_html( $user->user_login ) );
			}
		} elseif ( 'pc_source' === $column ) {
			$source = (int) get_post_meta( $post_id, '_pc_source', true );
			if ( $source && get_post( $source ) ) {
				printf( '<a href="%s">%s</a>', esc_url( get_permalink( $source ) ), esc_html( get_the_title( $source ) ) );
			}
		}
	}

	/**
	 * Find the subscriber post for an email.
	 *
	 * @param string $email Sanitized email.
	 * @return int Post ID or 0.
	 */
	public static function find( $email ) {
		$ids = get_posts(
			array(
				'post_type'        => self::POST_TYPE,
				'post_status'      => 'any',
				'numberposts'      => 1,
				'fields'           => 'ids',
				'meta_key'         => '_pc_email', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'       => $email, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'suppress_filters' => false,
			)
		);

		return $ids ? (int) $ids[0] : 0;
	}

	/**
	 * Create the subscriber post unless the email is already listed.
	 *
	 * @param string $email   Sanitized email.
	 * @param int    $user_id Subscriber's user ID.
	 * @param int    $source  Post ID the form was submitted from.
	 * @return int|\WP_Error Post ID.
	 */
	public static function upsert( $email, $user_id, $source ) {
		$existing = self::find( $email );
		if ( $existing ) {
			return $existing;
		}

		return wp_insert_post(
			array(
				'post_type'   => self::POST_TYPE,
				'post_status' => 'private',
				'post_title'  => $email,
				'meta_input'  => array(
					'_pc_email'   => $email,
					'_pc_user_id' => $user_id,
					'_pc_source'  => $source,
				),
			),
			true
		);
	}
}
