<?php
/**
 * Newsletter subscribers: the `subscriber` users created by the newsletter
 * form, listed on a Subscribers admin screen. They cannot log in.
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore\Newsletter;

use WP_Error;
use WP_User;

/**
 * Subscribers admin screen (a users list filtered to the subscriber role)
 * and the login lock for subscribers.
 */
final class Subscribers {

	const ROLE       = 'subscriber';
	const PAGE       = 'pc-subscribers';
	const CAPABILITY = 'list_users';

	/**
	 * Screen hook suffix, set when the menu is added.
	 *
	 * @var string
	 */
	private static $hook = '';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_filter( 'authenticate', array( __CLASS__, 'block_login' ), 100 );
		add_filter( 'allow_password_reset', array( __CLASS__, 'block_password_reset' ), 10, 2 );
	}

	/**
	 * Adds the Subscribers screen under Users.
	 *
	 * @return void
	 */
	public static function add_menu() {
		self::$hook = (string) add_users_page(
			__( 'Subscribers', 'profchoicecore' ),
			__( 'Subscribers', 'profchoicecore' ),
			self::CAPABILITY,
			self::PAGE,
			array( __CLASS__, 'render_page' )
		);
		add_action( 'load-' . self::$hook, array( __CLASS__, 'load_page' ) );
	}

	/**
	 * Adds the screen's per-page option.
	 *
	 * @return void
	 */
	public static function load_page() {
		add_screen_option(
			'per_page',
			array(
				'default' => 20,
				'option'  => 'pc_subscribers_per_page',
			)
		);
		add_filter(
			'set_screen_option_pc_subscribers_per_page',
			static function ( $keep, $option, $value ) {
				return (int) $value;
			},
			10,
			3
		);
	}

	/**
	 * Renders the Subscribers screen, like a core list screen: title, Export
	 * as the header action, search and the list table.
	 *
	 * @return void
	 */
	public static function render_page() {
		require_once __DIR__ . '/SubscribersTable.php';
		$table = new SubscribersTable();
		$table->prepare_items();
		$search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Subscribers', 'profchoicecore' ); ?></h1>
			<?php if ( current_user_can( Exporter::CAPABILITY ) ) : ?>
				<a href="<?php echo esc_url( Exporter::url() ); ?>" class="page-title-action"><?php esc_html_e( 'Export', 'profchoicecore' ); ?></a>
			<?php endif; ?>
			<?php
			if ( '' !== $search ) {
				printf(
					'<span class="subtitle">%s</span>',
					/* translators: %s: search query. */
					sprintf( esc_html__( 'Search results for: %s', 'profchoicecore' ), '<strong>' . esc_html( $search ) . '</strong>' )
				);
			}
			?>
			<hr class="wp-header-end">
			<form method="get">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::PAGE ); ?>" />
				<?php $table->search_box( __( 'Search Subscribers', 'profchoicecore' ), 'subscriber' ); ?>
				<?php $table->display(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Subscribers (confirmed or not) cannot log in.
	 *
	 * @param WP_User|WP_Error|null $user Authenticated user or error.
	 * @return WP_User|WP_Error|null
	 */
	public static function block_login( $user ) {
		if ( $user instanceof WP_User && self::is_locked( $user ) ) {
			return new WP_Error(
				'profchoicecore_subscriber_login',
				__( 'This account is a subscriber and cannot log in.', 'profchoicecore' )
			);
		}
		return $user;
	}

	/**
	 * Subscribers cannot reset a password (which would let them log in).
	 *
	 * @param bool $allow   Whether to allow the reset.
	 * @param int  $user_id User ID.
	 * @return bool
	 */
	public static function block_password_reset( $allow, $user_id ) {
		$user = get_userdata( $user_id );
		return $user && self::is_locked( $user ) ? false : $allow;
	}

	/**
	 * Whether a user is a (possibly unconfirmed) subscriber, who cannot log in.
	 *
	 * @param WP_User $user User.
	 * @return bool
	 */
	private static function is_locked( WP_User $user ) {
		return (bool) array_intersect( array( self::ROLE, Confirmation::ROLE ), (array) $user->roles );
	}
}
