<?php
/**
 * Wishlist admin: a sortable "Wishlists" column in Products (how many lists
 * have each product; no list contents are shown).
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore\Wishlist;

/**
 * Admin screens.
 */
final class Admin {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_filter( 'manage_edit-product_columns', array( __CLASS__, 'add_column' ), 20 );
		add_action( 'manage_product_posts_custom_column', array( __CLASS__, 'column' ), 10, 2 );
		add_filter( 'manage_edit-product_sortable_columns', array( __CLASS__, 'sortable' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'sort' ) );
	}

	/**
	 * Add the column after the price.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public static function add_column( $columns ) {
		$out = array();
		foreach ( $columns as $key => $label ) {
			$out[ $key ] = $label;
			if ( 'price' === $key ) {
				$out['pc_wishlists'] = __( 'Wishlists', 'profchoicecore' );
			}
		}
		if ( ! isset( $out['pc_wishlists'] ) ) {
			$out['pc_wishlists'] = __( 'Wishlists', 'profchoicecore' );
		}
		return $out;
	}

	/**
	 * In how many lists the product is.
	 *
	 * @param string $column  Column.
	 * @param int    $post_id Product ID.
	 * @return void
	 */
	public static function column( $column, $post_id ) {
		if ( 'pc_wishlists' === $column ) {
			echo esc_html( number_format_i18n( (int) get_post_meta( $post_id, Lists::COUNT, true ) ) );
		}
	}

	/**
	 * Sortable column.
	 *
	 * @param array $columns Sortable columns.
	 * @return array
	 */
	public static function sortable( $columns ) {
		$columns['pc_wishlists'] = 'pc_wishlists';
		return $columns;
	}

	/**
	 * Sort products by wishlist count.
	 *
	 * @param \WP_Query $query Query.
	 * @return void
	 */
	public static function sort( $query ) {
		if ( is_admin() && $query->is_main_query() && 'pc_wishlists' === $query->get( 'orderby' ) ) {
			$query->set( 'meta_key', Lists::COUNT ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_key
			$query->set( 'orderby', 'meta_value_num' );
		}
	}
}
