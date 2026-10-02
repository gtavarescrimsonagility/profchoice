<?php
/**
 * Wishlist settings: WooCommerce > Settings > Wishlist.
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore\Wishlist;

/**
 * Settings tab, getters and the wishlist page.
 */
final class Settings {

	const TAB = 'profchoice_wishlist';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_filter( 'woocommerce_settings_tabs_array', array( __CLASS__, 'add_tab' ), 60 );
		add_action( 'woocommerce_settings_' . self::TAB, array( __CLASS__, 'output' ) );
		add_action( 'woocommerce_update_options_' . self::TAB, array( __CLASS__, 'save' ) );
		add_action( 'admin_init', array( __CLASS__, 'ensure_page' ) );
	}

	/**
	 * Whether guests can keep a wishlist (saved in their browser).
	 *
	 * @return bool
	 */
	public static function guests_allowed() {
		/**
		 * Filters whether guests can keep a wishlist.
		 *
		 * @param bool $allowed From the Wishlist settings.
		 */
		return (bool) apply_filters( 'profchoicecore_wishlist_guests_allowed', 'no' !== get_option( 'profchoice_wishlist_guests', 'yes' ) );
	}

	/**
	 * Whether visitors can keep several lists.
	 *
	 * @return bool
	 */
	public static function multiple_lists() {
		return 'yes' === get_option( 'profchoice_wishlist_multiple', 'no' );
	}

	/**
	 * Whether an alert confirms adding or removing.
	 *
	 * @return bool
	 */
	public static function show_notice() {
		return 'yes' === get_option( 'profchoice_wishlist_notice', 'no' );
	}

	/**
	 * Days a guest list is kept after its last change.
	 *
	 * @return int
	 */
	public static function retention_days() {
		return max( 1, absint( get_option( 'profchoice_wishlist_retention', 30 ) ) );
	}

	/**
	 * The wishlist page ID.
	 *
	 * @return int
	 */
	public static function page_id() {
		/**
		 * Filters the wishlist page ID.
		 *
		 * @param int $page_id From the Wishlist settings.
		 */
		return absint( apply_filters( 'profchoicecore_wishlist_page_id', absint( get_option( 'profchoice_wishlist_page_id', 0 ) ) ) );
	}

	/**
	 * The wishlist URL: the page, or the My Account endpoint for users.
	 *
	 * @return string
	 */
	public static function page_url() {
		$page = self::page_id();
		if ( $page && 'publish' === get_post_status( $page ) ) {
			return (string) get_permalink( $page );
		}
		return function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( Account::ENDPOINT ) : home_url( '/' );
	}

	/**
	 * Create the Wishlist page (with the wishlist block) when none is set.
	 *
	 * @return void
	 */
	public static function ensure_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) || get_option( 'profchoice_wishlist_page_created' ) ) {
			return;
		}
		update_option( 'profchoice_wishlist_page_created', 1, false );
		$page = self::page_id();
		if ( $page && get_post( $page ) ) {
			return;
		}
		$id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => __( 'Wishlist', 'profchoicecore' ),
				'post_name'    => 'wishlist',
				'post_content' => '<!-- wp:profchoice/wishlist /-->',
			)
		);
		if ( $id && ! is_wp_error( $id ) ) {
			update_option( 'profchoice_wishlist_page_id', $id );
		}
	}

	/**
	 * Add the tab.
	 *
	 * @param array $tabs Settings tabs.
	 * @return array
	 */
	public static function add_tab( $tabs ) {
		$tabs[ self::TAB ] = __( 'Wishlist', 'profchoicecore' );
		return $tabs;
	}

	/**
	 * Settings fields.
	 *
	 * @return array
	 */
	private static function fields() {
		return array(
			array(
				'title' => __( 'Wishlist', 'profchoicecore' ),
				'type'  => 'title',
				'desc'  => __( 'Hearts on products save them to the visitor\'s default list; the visitor can move them to another list.', 'profchoicecore' ),
				'id'    => 'profchoice_wishlist_options',
			),
			array(
				'title'   => __( 'Guests', 'profchoicecore' ),
				'id'      => 'profchoice_wishlist_guests',
				'type'    => 'select',
				'default' => 'yes',
				'options' => array(
					'yes' => __( 'Allow guests (saved in their browser, kept after they log in)', 'profchoicecore' ),
					'no'  => __( 'Require login', 'profchoicecore' ),
				),
			),
			array(
				'title'    => __( 'Wishlist page', 'profchoicecore' ),
				'id'       => 'profchoice_wishlist_page_id',
				'type'     => 'single_select_page_with_search',
				'default'  => '',
				'class'    => 'wc-page-search',
				'desc_tip' => __( 'A page with the Wishlist block. Logged-in customers also find their lists in My Account > Wishlists.', 'profchoicecore' ),
				'args'     => array( 'exclude' => array() ),
			),
			array(
				'title'   => __( 'Multiple lists', 'profchoicecore' ),
				'desc'    => __( 'Visitors can create, rename and choose lists', 'profchoicecore' ),
				'id'      => 'profchoice_wishlist_multiple',
				'type'    => 'checkbox',
				'default' => 'no',
			),
			array(
				'title'   => __( 'Confirmation', 'profchoicecore' ),
				'desc'    => __( 'Show an alert after adding or removing', 'profchoicecore' ),
				'id'      => 'profchoice_wishlist_notice',
				'type'    => 'checkbox',
				'default' => 'no',
			),
			array(
				'title'             => __( 'Guest lists retention', 'profchoicecore' ),
				'desc'              => __( 'days after the last change', 'profchoicecore' ),
				'id'                => 'profchoice_wishlist_retention',
				'type'              => 'number',
				'default'           => '30',
				'custom_attributes' => array( 'min' => 1 ),
				'css'               => 'width: 6em;',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'profchoice_wishlist_options',
			),
		);
	}

	/**
	 * Output the tab.
	 *
	 * @return void
	 */
	public static function output() {
		woocommerce_admin_fields( self::fields() );
	}

	/**
	 * Save the fields.
	 *
	 * @return void
	 */
	public static function save() {
		woocommerce_update_options( self::fields() );
	}
}
