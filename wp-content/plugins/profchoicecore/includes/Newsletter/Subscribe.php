<?php
/**
 * Newsletter subscription: REST endpoint and no-JS fallback.
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore\Newsletter;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Subscribes an email: a `subscriber` user plus a `pc_subscriber` post.
 *
 * No nonce by design: the form lives on cached public pages. Abuse is
 * limited by a honeypot field and a per-IP rate limit, and nothing here
 * sends email or trusts a client-supplied recipient.
 */
final class Subscribe {

	const REST_NAMESPACE = 'profchoicecore/v1';
	const ACTION         = 'profchoicecore_subscribe';
	const RATE_LIMIT     = 5;
	const RATE_WINDOW    = 600;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_route' ) );
		add_action( 'admin_post_nopriv_' . self::ACTION, array( __CLASS__, 'handle_post' ) );
		add_action( 'admin_post_' . self::ACTION, array( __CLASS__, 'handle_post' ) );
	}

	/**
	 * Register POST profchoicecore/v1/subscribe.
	 *
	 * @return void
	 */
	public static function register_route() {
		register_rest_route(
			self::REST_NAMESPACE,
			'/subscribe',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'email'   => array(
						'type'     => 'string',
						'required' => true,
					),
					'website' => array(
						'type'    => 'string',
						'default' => '',
					),
					'source'  => array(
						'type'    => 'integer',
						'default' => 0,
					),
				),
			)
		);
	}

	/**
	 * REST callback.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest( WP_REST_Request $request ) {
		$result = self::subscribe(
			(string) $request->get_param( 'email' ),
			(int) $request->get_param( 'source' ),
			(string) $request->get_param( 'website' )
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( array( 'ok' => true ), 200 );
	}

	/**
	 * No-JS fallback: the form posts to admin-post.php and gets redirected back.
	 *
	 * @return void
	 */
	public static function handle_post() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Public form on cached pages, see class docblock.
		$email    = isset( $_POST['email'] ) ? sanitize_text_field( wp_unslash( $_POST['email'] ) ) : '';
		$website  = isset( $_POST['website'] ) ? sanitize_text_field( wp_unslash( $_POST['website'] ) ) : '';
		$source   = isset( $_POST['source'] ) ? absint( $_POST['source'] ) : 0;
		$anchor   = isset( $_POST['anchor'] ) ? sanitize_title( wp_unslash( $_POST['anchor'] ) ) : '';
		// phpcs:enable

		$result   = self::subscribe( $email, $source, $website );
		$referer  = wp_get_referer();
		$redirect = $referer ? $referer : ( $source ? get_permalink( $source ) : home_url( '/' ) );
		$redirect = remove_query_arg( 'pc-newsletter', (string) $redirect );
		$redirect = add_query_arg( 'pc-newsletter', is_wp_error( $result ) ? 'error' : 'success', $redirect );

		if ( $anchor ) {
			$redirect = strtok( $redirect, '#' ) . '#' . $anchor;
		}

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Subscribe an email address.
	 *
	 * The response is the same for new and existing subscribers so the form
	 * cannot be used to find out who is already registered.
	 *
	 * @param string $email    Raw email.
	 * @param int    $source   Post ID the form was submitted from.
	 * @param string $honeypot Honeypot field value; bots fill it in.
	 * @return true|WP_Error
	 */
	public static function subscribe( $email, $source, $honeypot ) {
		// Every attempt counts, including bots caught by the honeypot.
		if ( self::is_rate_limited() ) {
			return new WP_Error(
				'profchoicecore_rate_limited',
				__( 'Too many attempts. Please try again in a few minutes.', 'profchoicecore' ),
				array( 'status' => 429 )
			);
		}

		if ( '' !== trim( $honeypot ) ) {
			return true;
		}

		$email = strtolower( sanitize_email( $email ) );
		if ( ! is_email( $email ) ) {
			return new WP_Error(
				'profchoicecore_invalid_email',
				__( 'Please enter a valid email address.', 'profchoicecore' ),
				array( 'status' => 400 )
			);
		}

		$source  = get_post( $source ) ? $source : 0;
		$user    = get_user_by( 'email', $email );
		$user_id = $user ? $user->ID : self::create_user( $email );
		if ( is_wp_error( $user_id ) ) {
			return new WP_Error( 'profchoicecore_subscribe_failed', __( 'Something went wrong. Please try again.', 'profchoicecore' ), array( 'status' => 500 ) );
		}

		$post_id = Subscribers::upsert( $email, $user_id, $source );
		if ( is_wp_error( $post_id ) ) {
			return new WP_Error( 'profchoicecore_subscribe_failed', __( 'Something went wrong. Please try again.', 'profchoicecore' ), array( 'status' => 500 ) );
		}

		/**
		 * Fires after an email subscribes to the newsletter (or subscribes again).
		 *
		 * @param string $email   Subscriber email.
		 * @param int    $user_id Subscriber user ID.
		 * @param int    $post_id `pc_subscriber` post ID.
		 * @param int    $source  Post ID the form was submitted from.
		 */
		do_action( 'profchoicecore_newsletter_subscribed', $email, $user_id, $post_id, $source );

		return true;
	}

	/**
	 * Create a `subscriber` user. Existing accounts are reused elsewhere and
	 * never have their role changed.
	 *
	 * @param string $email Sanitized email.
	 * @return int|WP_Error User ID.
	 */
	private static function create_user( $email ) {
		$base  = sanitize_user( (string) strstr( $email, '@', true ), true );
		$base  = '' !== $base ? $base : 'subscriber';
		$login = $base;
		$i     = 1;
		while ( username_exists( $login ) ) {
			$login = $base . ++$i;
		}

		return wp_insert_user(
			array(
				'user_login'   => $login,
				'user_email'   => $email,
				'user_pass'    => wp_generate_password( 24 ),
				'display_name' => $login,
				'role'         => 'subscriber',
			)
		);
	}

	/**
	 * Count this request against the client's IP and report whether it is over
	 * the limit.
	 *
	 * @return bool
	 */
	private static function is_rate_limited() {
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$key = 'pc_newsletter_' . md5( $ip );

		$count = (int) get_transient( $key );
		if ( $count >= self::RATE_LIMIT ) {
			return true;
		}

		set_transient( $key, $count + 1, self::RATE_WINDOW );

		return false;
	}
}
