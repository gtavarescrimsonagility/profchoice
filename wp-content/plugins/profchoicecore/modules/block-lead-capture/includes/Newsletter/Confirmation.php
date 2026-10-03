<?php
/**
 * How a subscription is confirmed (Settings > Subscribers): right away,
 * right away with an email to unsubscribe (opt-out, see Unsubscribe), or
 * by email confirmation (double opt-in).
 *
 * With double opt-in, a new subscription creates an
 * `unconfirmed` user and emails a confirmation link, shaped like core's
 * privacy request confirmation link (wp-login.php?action=…&confirm_key=…
 * &login=…). Following it turns
 * the user into a `subscriber`.
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore\Newsletter;

use WP_User;

/**
 * Settings screen, the Unconfirmed role, the email and the confirm link.
 */
final class Confirmation {

	const ROLE       = 'unconfirmed';
	const OPTION     = 'profchoicecore_newsletter_confirm';
	const MODE       = 'profchoicecore_newsletter_mode';
	const PAGE       = 'pc-subscribers-settings';
	const ACTION     = 'pc-confirm-subscription';
	const META_KEY   = '_pc_confirm_key';
	const META_TIME  = '_pc_confirm_time';
	const EXPIRATION = WEEK_IN_SECONDS;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'init', array( __CLASS__, 'add_role' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_setting' ) );
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'login_form_' . self::ACTION, array( __CLASS__, 'confirm' ) );
	}

	/**
	 * Subscription mode: `none` (subscribed, no email), `opt-out`
	 * (subscribed, with an email to unsubscribe) or `opt-in` (subscribed
	 * after confirming by email). Sites that only had the old confirmation
	 * checkbox keep its value.
	 *
	 * @return string
	 */
	public static function mode() {
		$mode = (string) get_option( self::MODE, '' );
		if ( ! in_array( $mode, array( 'none', 'opt-out', 'opt-in' ), true ) ) {
			$mode = get_option( self::OPTION, false ) ? 'opt-in' : 'none';
		}
		/**
		 * Filters the subscription mode.
		 *
		 * @param string $mode none, opt-out or opt-in.
		 */
		return (string) apply_filters( 'profchoicecore_newsletter_mode', $mode );
	}

	/**
	 * Whether new subscriptions need email confirmation (opt-in).
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		return 'opt-in' === self::mode();
	}

	/**
	 * Sanitize the mode.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_mode( $value ) {
		return in_array( $value, array( 'none', 'opt-out', 'opt-in' ), true ) ? $value : 'none';
	}

	/**
	 * Registers the Unconfirmed role (no capabilities) once.
	 *
	 * @return void
	 */
	public static function add_role() {
		if ( ! get_role( self::ROLE ) ) {
			add_role( self::ROLE, __( 'Unconfirmed', 'profchoicecore' ), array() );
		}
	}

	/**
	 * Registers the option.
	 *
	 * @return void
	 */
	public static function register_setting() {
		register_setting(
			self::PAGE,
			self::MODE,
			array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => array( __CLASS__, 'sanitize_mode' ),
			)
		);
		add_settings_section( 'pc-subscribers', '', '__return_false', self::PAGE );
		add_settings_field(
			self::MODE,
			__( 'New subscriptions', 'profchoicecore' ),
			array( __CLASS__, 'render_field' ),
			self::PAGE,
			'pc-subscribers'
		);
	}

	/**
	 * Adds Settings > Subscribers.
	 *
	 * @return void
	 */
	public static function add_menu() {
		add_options_page(
			__( 'Subscribers', 'profchoicecore' ),
			__( 'Subscribers', 'profchoicecore' ),
			'manage_options',
			self::PAGE,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Renders Settings > Subscribers.
	 *
	 * @return void
	 */
	public static function render_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Subscribers', 'profchoicecore' ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::PAGE );
				do_settings_sections( self::PAGE );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Renders the mode choices.
	 *
	 * @return void
	 */
	public static function render_field() {
		$modes = array(
			'none'    => array(
				__( 'Subscribe right away', 'profchoicecore' ),
				__( 'No email is sent.', 'profchoicecore' ),
			),
			'opt-out' => array(
				__( 'Subscribe right away and email a link to unsubscribe (opt-out)', 'profchoicecore' ),
				__( 'For an email typed by mistake, on purpose by someone else, or a change of mind. Unsubscribing removes the subscriber; customers keep their account.', 'profchoicecore' ),
			),
			'opt-in'  => array(
				__( 'Require email confirmation (opt-in)', 'profchoicecore' ),
				__( 'New subscribers get the Unconfirmed role and an email with a confirmation link. They become Subscribers once they confirm. The link expires after 7 days.', 'profchoicecore' ),
			),
		);
		echo '<fieldset><legend class="screen-reader-text">' . esc_html__( 'New subscriptions', 'profchoicecore' ) . '</legend>';
		foreach ( $modes as $value => $text ) {
			printf(
				'<p><label><input type="radio" name="%1$s" value="%2$s" %3$s /> %4$s</label></p><p class="description" style="margin: 0 0 0.75em 1.75em;">%5$s</p>',
				esc_attr( self::MODE ),
				esc_attr( $value ),
				checked( self::mode(), $value, false ),
				esc_html( $text[0] ),
				esc_html( $text[1] )
			);
		}
		echo '</fieldset>';
	}

	/**
	 * Emails a (new) confirmation link to an unconfirmed user.
	 *
	 * @param WP_User $user Unconfirmed user.
	 * @return bool Whether the email was sent.
	 */
	public static function send( WP_User $user ) {
		$key = wp_generate_password( 20, false );
		update_user_meta( $user->ID, self::META_KEY, wp_hash_password( $key ) );
		update_user_meta( $user->ID, self::META_TIME, time() );

		$url = add_query_arg(
			array(
				'action'      => self::ACTION,
				// Not "key": wp-login.php turns any `key` into a password reset.
				'confirm_key' => $key,
				'login'       => rawurlencode( $user->user_login ),
			),
			wp_login_url()
		);

		$site = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		/* translators: %s: site title. */
		$subject = sprintf( __( '[%s] Confirm your subscription', 'profchoicecore' ), $site );
		$message = sprintf(
			/* translators: 1: site title, 2: confirmation link. */
			__( "Please confirm your subscription to %1\$s by visiting the following address:\n\n%2\$s\n\nIf you did not subscribe, you can ignore this email.", 'profchoicecore' ),
			$site,
			$url
		);

		/**
		 * Filters the confirmation email.
		 *
		 * @param array   $email {to, subject, message, headers}.
		 * @param WP_User $user  Unconfirmed user.
		 * @param string  $url   Confirmation link.
		 */
		$email = apply_filters(
			'profchoicecore_newsletter_confirmation_email',
			array(
				'to'      => $user->user_email,
				'subject' => $subject,
				'message' => $message,
				'headers' => '',
			),
			$user,
			$url
		);

		return wp_mail( $email['to'], $email['subject'], $email['message'], $email['headers'] );
	}

	/**
	 * Handles wp-login.php?action=pc-confirm-subscription&confirm_key=…&login=….
	 *
	 * @return void
	 */
	public static function confirm() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- The key is the proof, like core's password reset link.
		$login = isset( $_GET['login'] ) ? sanitize_user( rawurldecode( wp_unslash( $_GET['login'] ) ) ) : '';
		$key   = isset( $_GET['confirm_key'] ) ? sanitize_text_field( wp_unslash( $_GET['confirm_key'] ) ) : '';
		// phpcs:enable

		$user  = '' !== $login ? get_user_by( 'login', $login ) : false;
		$hash  = $user ? (string) get_user_meta( $user->ID, self::META_KEY, true ) : '';
		$time  = $user ? (int) get_user_meta( $user->ID, self::META_TIME, true ) : 0;
		$valid = $user
			&& in_array( self::ROLE, (array) $user->roles, true )
			&& '' !== $key
			&& '' !== $hash
			&& $time + self::EXPIRATION > time()
			&& wp_check_password( $key, $hash, $user->ID );

		if ( ! $valid ) {
			wp_die(
				esc_html__( 'This confirmation link is invalid or has expired. Please subscribe again to get a new one.', 'profchoicecore' ),
				esc_html__( 'Subscription', 'profchoicecore' ),
				array(
					'response'  => 403,
					'link_url'  => esc_url( home_url( '/' ) ),
					'link_text' => esc_html__( 'Go to the site', 'profchoicecore' ),
				)
			);
		}

		$user->set_role( Subscribers::ROLE );
		delete_user_meta( $user->ID, self::META_KEY );
		delete_user_meta( $user->ID, self::META_TIME );

		/** This action is documented in includes/Newsletter/Subscribe.php */
		do_action( 'profchoicecore_newsletter_subscribed', $user->user_email, $user->ID );

		wp_safe_redirect( add_query_arg( 'pc-newsletter', 'confirmed', home_url( '/' ) ) . '#newsletter' );
		exit;
	}
}
