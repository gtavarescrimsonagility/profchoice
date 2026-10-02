<?php
/**
 * Wishlist REST API (profchoicecore/v1), for the blocks' store. Requests
 * act for the current visitor: the logged-in user (cookie + wp_rest nonce)
 * or the guest session cookie.
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore\Wishlist;

/**
 * Routes.
 */
final class Rest {

	const NAMESPACE_ = 'profchoicecore/v1';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	/**
	 * Register the routes.
	 *
	 * @return void
	 */
	public static function routes() {
		$id   = array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
		);
		$open = array( __CLASS__, 'can_use' );

		register_rest_route(
			self::NAMESPACE_,
			'/wishlist',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_state' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			self::NAMESPACE_,
			'/wishlist/toggle',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'toggle' ),
				'permission_callback' => $open,
				'args'                => array(
					'product_id'   => $id + array( 'required' => true ),
					'variation_id' => $id,
				),
			)
		);
		register_rest_route(
			self::NAMESPACE_,
			'/wishlists',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'create' ),
				'permission_callback' => $open,
				'args'                => array(
					'name' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
		register_rest_route(
			self::NAMESPACE_,
			'/wishlists/(?P<id>\d+)',
			array(
				array(
					'methods'             => 'PATCH',
					'callback'            => array( __CLASS__, 'update' ),
					'permission_callback' => array( __CLASS__, 'owns' ),
					'args'                => array(
						'name'    => array(
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'default' => array( 'type' => 'boolean' ),
					),
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( __CLASS__, 'delete' ),
					'permission_callback' => array( __CLASS__, 'owns' ),
				),
			)
		);
		register_rest_route(
			self::NAMESPACE_,
			'/wishlists/(?P<id>\d+)/items',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'add_item' ),
				'permission_callback' => array( __CLASS__, 'owns' ),
				'args'                => array(
					'product_id'   => $id + array( 'required' => true ),
					'variation_id' => $id,
				),
			)
		);
		register_rest_route(
			self::NAMESPACE_,
			'/wishlists/(?P<id>\d+)/items/(?P<product_id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( __CLASS__, 'remove_item' ),
				'permission_callback' => array( __CLASS__, 'owns' ),
			)
		);
		register_rest_route(
			self::NAMESPACE_,
			'/wishlists/(?P<id>\d+)/items/(?P<product_id>\d+)/move',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'move_item' ),
				'permission_callback' => array( __CLASS__, 'owns' ),
				'args'                => array(
					'to' => $id + array( 'required' => true ),
				),
			)
		);
	}

	/**
	 * Logged in, or a guest when guests are allowed.
	 *
	 * @return true|\WP_Error
	 */
	public static function can_use() {
		if ( is_user_logged_in() || Settings::guests_allowed() ) {
			return true;
		}
		return new \WP_Error( 'profchoicecore_wishlist_login', __( 'Log in to save items to your wishlist.', 'profchoicecore' ), array( 'status' => 401 ) );
	}

	/**
	 * The list in the route belongs to the visitor.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return true|\WP_Error
	 */
	public static function owns( $request ) {
		$allowed = self::can_use();
		if ( true !== $allowed ) {
			return $allowed;
		}
		if ( ! Lists::owns( (int) $request['id'], Lists::current_owner() ) ) {
			return new \WP_Error( 'profchoicecore_wishlist_forbidden', __( 'This list is not yours.', 'profchoicecore' ), array( 'status' => 403 ) );
		}
		$target = $request->get_param( 'to' );
		if ( $target && ! Lists::owns( (int) $target, Lists::current_owner() ) ) {
			return new \WP_Error( 'profchoicecore_wishlist_forbidden', __( 'This list is not yours.', 'profchoicecore' ), array( 'status' => 403 ) );
		}
		return true;
	}

	/**
	 * The visitor's wishlist state.
	 *
	 * @return \WP_REST_Response
	 */
	public static function get_state() {
		return rest_ensure_response( Wishlist::state() );
	}

	/**
	 * Save to the default list, or remove from every list.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function toggle( $request ) {
		$product = wc_get_product( (int) $request['product_id'] );
		if ( ! $product || ! $product->is_visible() ) {
			return new \WP_Error( 'profchoicecore_wishlist_product', __( 'Product not found.', 'profchoicecore' ), array( 'status' => 404 ) );
		}
		$owner = Lists::current_owner( true );
		$saved = Lists::toggle( $owner, $product->get_id(), (int) $request['variation_id'] );
		$list  = $saved ? Lists::default_list( $owner ) : null;
		return rest_ensure_response(
			array_merge(
				Wishlist::state( $owner ),
				array(
					'isSaved' => $saved,
					'listId'  => $list ? $list->ID : 0,
				)
			)
		);
	}

	/**
	 * Create a list.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function create( $request ) {
		if ( ! Settings::multiple_lists() ) {
			return new \WP_Error( 'profchoicecore_wishlist_single', __( 'Only one list is available.', 'profchoicecore' ), array( 'status' => 400 ) );
		}
		$owner = Lists::current_owner( true );
		$id    = Lists::create( $owner, (string) $request['name'] );
		return rest_ensure_response( array_merge( Wishlist::state( $owner ), array( 'listId' => $id ) ) );
	}

	/**
	 * Rename a list or make it the default.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public static function update( $request ) {
		$owner = Lists::current_owner();
		if ( null !== $request->get_param( 'name' ) ) {
			Lists::rename( (int) $request['id'], (string) $request['name'] );
		}
		if ( $request->get_param( 'default' ) ) {
			Lists::set_default( (int) $request['id'], $owner );
		}
		return rest_ensure_response( Wishlist::state( $owner ) );
	}

	/**
	 * Delete a list.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function delete( $request ) {
		$owner = Lists::current_owner();
		if ( ! Lists::delete( (int) $request['id'], $owner ) ) {
			return new \WP_Error( 'profchoicecore_wishlist_last', __( 'You need at least one list.', 'profchoicecore' ), array( 'status' => 400 ) );
		}
		return rest_ensure_response( Wishlist::state( $owner ) );
	}

	/**
	 * Add a product to a list.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public static function add_item( $request ) {
		Lists::add_item( (int) $request['id'], (int) $request['product_id'], (int) $request['variation_id'] );
		return rest_ensure_response( Wishlist::state() );
	}

	/**
	 * Remove a product from a list.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public static function remove_item( $request ) {
		Lists::remove_item( (int) $request['id'], (int) $request['product_id'] );
		return rest_ensure_response( Wishlist::state() );
	}

	/**
	 * Move a product to another list.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public static function move_item( $request ) {
		Lists::move_item( (int) $request['id'], (int) $request['to'], (int) $request['product_id'] );
		return rest_ensure_response( array_merge( Wishlist::state(), array( 'listId' => (int) $request['to'] ) ) );
	}
}
