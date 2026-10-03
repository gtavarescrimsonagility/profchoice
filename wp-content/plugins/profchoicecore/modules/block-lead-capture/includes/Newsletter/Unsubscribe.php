<?php
/**
 * Opt-out: a subscription starts right away and an email offers a link to
 * leave (someone typed another person's email, by mistake or not, or the
 * subscriber changed their mind).
 *
 * The link (wp-login.php?action=pc-unsubscribe&unsubscribe_key=…&login=…)
 * opens a page with an Unsubscribe button, so mail scanners that follow
 * links don't unsubscribe anyone. The email also carries List-Unsubscribe
 * headers for the mail apps' one-click unsubscribe (RFC 8058), which posts
 * to the same address.
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore\Newsletter;

use WP_User;

/**
 * Welcome email with the unsubscribe link, and the unsubscribe page.
 */
final class Unsubscribe {

	const ACTION   = 'pc-unsubscribe';
	const META_OFF = '_pc_newsletter_unsubscribed';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'login_form_' . self::ACTION, array( __CLASS__, 'handle' ) );
		add_action( 'profchoicecore_newsletter_subscribed', array( __CLASS__, 'resubscribed' ), 5, 2 );
	}

	/**
	 * The user's unsubscribe key: stable (every email's link keeps
	 * working) and tied to the email, so it stops working if it changes.
	 *
	 * @param WP_User $user User.
	 * @return string
	 */
	private static function key( WP_User $user ) {
		return substr( wp_hash( 'pc-unsubscribe|' . $user->ID . '|' . strtolower( $user->user_email ) ), 0, 32 );
	}

	/**
	 * The unsubscribe link.
	 *
	 * @param WP_User $user User.
	 * @return string
	 */
	public static function url( WP_User $user ) {
		return add_query_arg(
			array(
				'action'          => self::ACTION,
				// Not "key": wp-login.php turns any `key` into a password reset.
				'unsubscribe_key' => self::key( $user ),
				'login'           => rawurlencode( $user->user_login ),
			),
			wp_login_url()
		);
	}

	/**
	 * Emails the subscription notice with the unsubscribe link.
	 *
	 * @param WP_User $user Subscriber.
	 * @return bool Whether the email was sent.
	 */
	public static function send( WP_User $user ) {
		$url  = self::url( $user );
		$site = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		/* translators: %s: site title. */
		$subject = sprintf( __( '[%s] You are subscribed', 'profchoicecore' ), $site );
		$message = sprintf(
			/* translators: 1: site title, 2: unsubscribe link. */
			__( "This email address is now subscribed to the %1\$s newsletter.\n\nIf you did not subscribe, or you changed your mind, you can unsubscribe here:\n\n%2\$s", 'profchoicecore' ),
			$site,
			$url
		);

		/**
		 * Filters the subscription (opt-out) email.
		 *
		 * @param array   $email {to, subject, message, headers}.
		 * @param WP_User $user  Subscriber.
		 * @param string  $url   Unsubscribe link.
		 */
		$email = apply_filters(
			'profchoicecore_newsletter_unsubscribe_email',
			array(
				'to'      => $user->user_email,
				'subject' => $subject,
				'message' => $message,
				'headers' => array(
					'List-Unsubscribe: <' . esc_url_raw( $url ) . '>',
					'List-Unsubscribe-Post: List-Unsubscribe=One-Click',
				),
			),
			$user,
			$url
		);

		return wp_mail( $email['to'], $email['subject'], $email['message'], $email['headers'] );
	}

	/**
	 * Subscribing again clears an earlier unsubscribe.
	 *
	 * @param string $email   Subscriber email.
	 * @param int    $user_id User ID.
	 * @return void
	 */
	public static function resubscribed( $email, $user_id ) {
		delete_user_meta( $user_id, self::META_OFF );
	}

	/**
	 * Handles the link: GET shows the page with the button, POST (the
	 * button, or a mail app's one-click) unsubscribes.
	 *
	 * @return void
	 */
	public static function handle() {
		// phpcs:disable WordPress.Security.NonceVerification -- The key is the proof, like core's password reset link.
		$login = isset( $_GET['login'] ) ? sanitize_user( rawurldecode( wp_unslash( $_GET['login'] ) ) ) : '';
		$key   = isset( $_GET['unsubscribe_key'] ) ? sanitize_text_field( wp_unslash( $_GET['unsubscribe_key'] ) ) : '';
		$post  = isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'];
		// phpcs:enable

		$user  = '' !== $login ? get_user_by( 'login', $login ) : false;
		$valid = $user && '' !== $key && hash_equals( self::key( $user ), $key );

		if ( ! $valid ) {
			wp_die(
				esc_html__( 'This unsubscribe link is invalid.', 'profchoicecore' ),
				esc_html__( 'Unsubscribe', 'profchoicecore' ),
				array(
					'response'  => 403,
					'link_url'  => esc_url( home_url( '/' ) ),
					'link_text' => esc_html__( 'Go to the site', 'profchoicecore' ),
				)
			);
		}

		if ( $post ) {
			self::unsubscribe( $user );
			self::page( __( 'You are unsubscribed. You will not get our newsletter anymore.', 'profchoicecore' ) );
		}

		self::page(
			sprintf(
				/* translators: %s: email address. */
				__( 'Unsubscribe %s from the newsletter?', 'profchoicecore' ),
				$user->user_email
			),
			self::url( $user )
		);
	}

	/**
	 * Unsubscribes: a newsletter-only account (subscriber or unconfirmed)
	 * is deleted, so the email isn't kept; other accounts (customers) keep
	 * their account and are marked as unsubscribed.
	 *
	 * @param WP_User $user User.
	 * @return void
	 */
	private static function unsubscribe( WP_User $user ) {
		$email = $user->user_email;

		/**
		 * Fires before an email unsubscribes from the newsletter.
		 *
		 * @param string $email   Subscriber email.
		 * @param int    $user_id User ID (deleted next, for newsletter-only accounts).
		 */
		do_action( 'profchoicecore_newsletter_unsubscribed', $email, $user->ID );

		$roles = (array) $user->roles;
		if ( $roles && ! array_diff( $roles, array( Subscribers::ROLE, Confirmation::ROLE ) ) ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
			wp_delete_user( $user->ID );
			return;
		}
		update_user_meta( $user->ID, self::META_OFF, time() );
	}

	/**
	 * A page in the login screen's style, with an optional Unsubscribe
	 * button that posts to $action.
	 *
	 * @param string $message Message.
	 * @param string $action  Form action (shows the button when set).
	 * @return never
	 */
	private static function page( $message, $action = '' ) {
		login_header( __( 'Unsubscribe', 'profchoicecore' ) );
		?>
		<div class="message"><p><?php echo esc_html( $message ); ?></p></div>
		<?php if ( $action ) : ?>
			<form method="post" action="<?php echo esc_url( $action ); ?>">
				<p class="submit">
					<button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Unsubscribe', 'profchoicecore' ); ?></button>
				</p>
			</form>
		<?php endif; ?>
		<?php
		login_footer();
		exit;
	}
}
