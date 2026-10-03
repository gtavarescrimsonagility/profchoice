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
 * Subscribes an email: creates a `subscriber` user (which cannot log in,
 * see Subscribers), or, with email confirmation on, an `unconfirmed` user
 * plus a confirmation email (see Confirmation). In opt-out mode the
 * subscriber gets an email with a link to unsubscribe (see Unsubscribe).
 * No other email is sent.
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
			(string) $request->get_param( 'website' )
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response(
			array(
				'ok'      => true,
				'pending' => 'pending' === $result,
			),
			200
		);
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
		// Only used to return to the form's page when there is no referer.
		$source   = isset( $_POST['source'] ) ? absint( $_POST['source'] ) : 0;
		$anchor   = isset( $_POST['anchor'] ) ? sanitize_title( wp_unslash( $_POST['anchor'] ) ) : '';
		// phpcs:enable

		$result   = self::subscribe( $email, $website );
		$referer  = wp_get_referer();
		$redirect = $referer ? $referer : ( $source ? get_permalink( $source ) : home_url( '/' ) );
		$redirect = remove_query_arg( 'pc-newsletter', (string) $redirect );
		$status   = 'pending' === $result ? 'pending' : 'success';
		$redirect = add_query_arg( 'pc-newsletter', is_wp_error( $result ) ? 'error' : $status, $redirect );

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
	 * @param string $honeypot Honeypot field value; bots fill it in.
	 * @return true|string|WP_Error True when subscribed, 'pending' when
	 *                              waiting for the email confirmation.
	 */
	public static function subscribe( $email, $honeypot ) {
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

		$user = get_user_by( 'email', $email );

		// Double opt-in: an unconfirmed user and a confirmation email. An
		// unconfirmed email that subscribes again gets a new link.
		if ( Confirmation::is_enabled() && ( ! $user || in_array( Confirmation::ROLE, (array) $user->roles, true ) ) ) {
			$user_id = $user ? $user->ID : self::create_user( $email, Confirmation::ROLE );
			$user    = is_wp_error( $user_id ) ? false : get_userdata( $user_id );
			if ( ! $user ) {
				return new WP_Error( 'profchoicecore_subscribe_failed', __( 'Something went wrong. Please try again.', 'profchoicecore' ), array( 'status' => 500 ) );
			}
			Confirmation::send( $user );

			/**
			 * Fires after an email subscribes and is waiting for confirmation.
			 *
			 * @param string $email   Subscriber email.
			 * @param int    $user_id Unconfirmed user ID.
			 */
			do_action( 'profchoicecore_newsletter_pending', $email, $user->ID );

			return 'pending';
		}

		$user_id = $user ? $user->ID : self::create_user( $email, Subscribers::ROLE );
		if ( is_wp_error( $user_id ) ) {
			return new WP_Error( 'profchoicecore_subscribe_failed', __( 'Something went wrong. Please try again.', 'profchoicecore' ), array( 'status' => 500 ) );
		}

		/**
		 * Fires after an email subscribes to the newsletter (or subscribes again),
		 * or confirms its subscription when confirmation is required.
		 *
		 * @param string $email   Subscriber email.
		 * @param int    $user_id Subscriber user ID (an existing account is reused).
		 */
		do_action( 'profchoicecore_newsletter_subscribed', $email, $user_id );

		if ( 'opt-out' === Confirmation::mode() ) {
			$user = get_userdata( $user_id );
			if ( $user ) {
				Unsubscribe::send( $user );
			}
		}

		return true;
	}

	/**
	 * Create a `subscriber` (or `unconfirmed`) user, without any core
	 * notification email. Existing accounts are reused elsewhere and never
	 * have their role changed.
	 *
	 * @param string $email Sanitized email.
	 * @param string $role  Role.
	 * @return int|WP_Error User ID.
	 */
	private static function create_user( $email, $role ) {
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
				'role'         => $role,
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
