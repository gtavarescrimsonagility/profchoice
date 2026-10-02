<?php
/**
 * My Account > Wishlists: the customer's lists (the wishlist block).
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore\Wishlist;

/**
 * My Account endpoint and menu item.
 */
final class Account {

	const ENDPOINT = 'wishlists';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_filter( 'woocommerce_get_query_vars', array( __CLASS__, 'query_vars' ) );
		add_filter( 'woocommerce_account_menu_items', array( __CLASS__, 'menu_item' ) );
		add_filter( 'woocommerce_endpoint_' . self::ENDPOINT . '_title', array( __CLASS__, 'title' ) );
		add_action( 'woocommerce_account_' . self::ENDPOINT . '_endpoint', array( __CLASS__, 'content' ) );
		add_action( 'init', array( __CLASS__, 'flush_once' ), 20 );
		add_filter( 'woocommerce_login_redirect', array( __CLASS__, 'login_redirect' ) );
	}

	/**
	 * Register the endpoint with WooCommerce's query vars.
	 *
	 * @param array $vars Query vars.
	 * @return array
	 */
	public static function query_vars( $vars ) {
		$vars[ self::ENDPOINT ] = self::ENDPOINT;
		return $vars;
	}

	/**
	 * "Wishlists" before "Log out".
	 *
	 * @param array $items Menu items.
	 * @return array
	 */
	public static function menu_item( $items ) {
		$logout = isset( $items['customer-logout'] ) ? array( 'customer-logout' => $items['customer-logout'] ) : array();
		unset( $items['customer-logout'] );
		$items[ self::ENDPOINT ] = Settings::multiple_lists() ? __( 'Wishlists', 'profchoicecore' ) : __( 'Wishlist', 'profchoicecore' );
		return $items + $logout;
	}

	/**
	 * Endpoint title.
	 *
	 * @return string
	 */
	public static function title() {
		return Settings::multiple_lists() ? __( 'Wishlists', 'profchoicecore' ) : __( 'Wishlist', 'profchoicecore' );
	}

	/**
	 * Endpoint content: the wishlist block.
	 *
	 * @return void
	 */
	public static function content() {
		echo Wishlist::render_page(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Flush rewrite rules once so the endpoint resolves.
	 *
	 * @return void
	 */
	public static function flush_once() {
		if ( get_option( 'profchoice_wishlist_endpoint' ) !== self::ENDPOINT ) {
			flush_rewrite_rules( false );
			update_option( 'profchoice_wishlist_endpoint', self::ENDPOINT );
		}
	}

	/**
	 * Back to the page where a guest clicked a heart (login required mode).
	 *
	 * @param string $redirect Default redirect.
	 * @return string
	 */
	public static function login_redirect( $redirect ) {
		$to = isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return $to ? wp_validate_redirect( $to, $redirect ) : $redirect;
	}
}
