<?php
/**
 * First-run setup.
 *
 * Everything the theme needs in the database is created here so a fresh
 * install is usable immediately after activation: the Header/Footer block
 * areas, and a tabbed My Account page built from the Account Tabs block
 * (populated with whichever cfwPortal shortcodes the site actually has).
 *
 * The routine is idempotent, versioned, and never overwrites customer
 * content: it only replaces page content that is still WooCommerce's
 * untouched default, and stashes the original in post meta first.
 *
 * @package Commercebuild_Velocity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Theme activation bootstrap.
 *
 * @since 0.5.0
 */
class CBV_Setup {

	/**
	 * Option storing the setup version that has been applied.
	 *
	 * @var string
	 */
	const VERSION_OPTION = 'cbv_setup_version';

	/**
	 * Flag option set on activation.
	 *
	 * @var string
	 */
	const FLAG_OPTION = 'cbv_needs_setup';

	/**
	 * Current setup version. Bump to re-run setup after adding steps.
	 *
	 * @var int
	 */
	const SETUP_VERSION = 2;

	/**
	 * Post meta holding content replaced by setup, so it can be restored.
	 *
	 * @var string
	 */
	const BACKUP_META = '_cbv_content_before_setup';

	/**
	 * Register hooks.
	 *
	 * @since 0.5.0
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'after_switch_theme', array( $this, 'flag_for_setup' ) );

		// Runs late so WooCommerce/cfw_core shortcodes are registered and can
		// be feature-detected before the account tabs are built.
		add_action( 'admin_init', array( $this, 'maybe_run' ), 20 );
	}

	/**
	 * Mark the site as needing setup when the theme is activated.
	 *
	 * @since 0.5.0
	 *
	 * @return void
	 */
	public function flag_for_setup() {
		update_option( self::FLAG_OPTION, 1, true );
	}

	/**
	 * Run setup when flagged or when the setup version has advanced.
	 *
	 * @since 0.5.0
	 *
	 * @return void
	 */
	public function maybe_run() {
		$applied = (int) get_option( self::VERSION_OPTION, 0 );

		if ( ! get_option( self::FLAG_OPTION ) && $applied >= self::SETUP_VERSION ) {
			return;
		}

		$complete = $this->run();

		if ( ! $complete ) {
			// A dependency (usually WooCommerce) was not active yet. Leave the
			// flag set so setup retries once it is, instead of silently
			// skipping the account page forever.
			return;
		}

		delete_option( self::FLAG_OPTION );
		update_option( self::VERSION_OPTION, self::SETUP_VERSION, true );
	}

	/**
	 * Perform every setup step.
	 *
	 * @since 0.5.0
	 *
	 * @return bool True when every step could run.
	 */
	public function run() {
		$services = cbv_services();

		if ( isset( $services['areas'] ) ) {
			$services['areas']->ensure_areas();
		}

		$this->seed_account_page();
		$this->seed_front_page();
		$this->seed_primary_menu();

		/**
		 * Fires after theme setup has run, for site-specific bootstrapping.
		 *
		 * @since 0.5.0
		 */
		do_action( 'cbv_after_setup_run' );

		// WooCommerce-dependent steps cannot complete without it.
		return function_exists( 'wc_get_page_id' );
	}

	/**
	 * Build a storefront home page and set it as the front page.
	 *
	 * Skipped entirely when the site already uses a static front page, so an
	 * existing home page is never replaced.
	 *
	 * @since 0.6.0
	 *
	 * @return void
	 */
	private function seed_front_page() {
		$existing = (int) get_option( 'page_on_front' );

		if ( 'page' === get_option( 'show_on_front' ) && $existing > 0 && get_post( $existing ) ) {
			return;
		}

		if ( get_option( 'cbv_front_page_id' ) ) {
			return;
		}

		// A site deliberately showing the blog on its homepage must keep it,
		// so only claim the front page when the site is still essentially new.
		if ( ! $this->site_looks_fresh() ) {
			return;
		}

		$page_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => __( 'Home', 'commercebuild-velocity' ),
				'post_content' => self::default_home_blocks(),
			)
		);

		if ( ! $page_id || is_wp_error( $page_id ) ) {
			return;
		}

		update_option( 'cbv_front_page_id', $page_id, true );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $page_id );
	}

	/**
	 * Whether the site still looks like a fresh install.
	 *
	 * Counts published, non-WooCommerce content: a couple of stock items (the
	 * sample post and privacy page) still count as fresh, anything more means
	 * the site has real content and its homepage is not ours to take.
	 *
	 * @since 0.8.0
	 *
	 * @return bool
	 */
	private function site_looks_fresh() {
		$posts = (int) wp_count_posts( 'post' )->publish;
		$pages = (int) wp_count_posts( 'page' )->publish;

		// WooCommerce publishes shop, cart, checkout, and my account pages.
		$woo_pages = 0;

		if ( function_exists( 'wc_get_page_id' ) ) {
			foreach ( array( 'shop', 'cart', 'checkout', 'myaccount', 'terms' ) as $page ) {
				if ( (int) wc_get_page_id( $page ) > 0 ) {
					++$woo_pages;
				}
			}
		}

		// An established site almost always has a nav menu, extra pages, or
		// more than the sample post; any of those means the homepage is not
		// ours to claim.
		$has_menus = ! empty( wp_get_nav_menus() );

		$fresh = ( $posts <= 1 ) && ( max( 0, $pages - $woo_pages ) <= 1 ) && ! $has_menus;

		/**
		 * Filter whether setup may claim the site's front page.
		 *
		 * @since 0.8.0
		 *
		 * @param bool $fresh True when the site looks newly installed.
		 */
		return (bool) apply_filters( 'cbv_site_looks_fresh', $fresh );
	}

	/**
	 * Default storefront home page layout.
	 *
	 * The hero renders nothing until slides are added, so a brand-new site
	 * shows a clean catalog page rather than an empty banner.
	 *
	 * @since 0.6.0
	 *
	 * @return string Serialized block markup.
	 */
	public static function default_home_blocks() {
		$blocks = array(
			'<!-- wp:commercebuild/hero-slider {"align":"full"} /-->',
			'<!-- wp:commercebuild/category-tiles {"heading":"' . esc_attr__( 'Shop by Category', 'commercebuild-velocity' ) . '","limit":6,"columns":3} /-->',
			'<!-- wp:commercebuild/hot-deals {"heading":"' . esc_attr__( 'Hot Deals', 'commercebuild-velocity' ) . '","source":"on_sale","limit":4} /-->',
			'<!-- wp:commercebuild/product-grid {"heading":"' . esc_attr__( 'New Arrivals', 'commercebuild-velocity' ) . '","limit":8} /-->',
			'<!-- wp:commercebuild/hot-deals {"heading":"' . esc_attr__( 'Best Sellers', 'commercebuild-velocity' ) . '","source":"best_selling","limit":4} /-->',
		);

		/**
		 * Filter the home page layout created during setup.
		 *
		 * @since 0.6.0
		 *
		 * @param string[] $blocks Block markup fragments.
		 */
		$blocks = apply_filters( 'cbv_default_home_blocks', $blocks );

		return implode( "\n\n", $blocks );
	}

	/**
	 * Option storing the id of the primary menu setup created, so later runs
	 * can top it up without touching a menu the site owner built.
	 *
	 * @var string
	 */
	const PRIMARY_MENU_OPTION = 'cbv_primary_menu_id';

	/**
	 * Create and assign a primary menu when the site has none, and top up the
	 * WooCommerce items on a later run.
	 *
	 * When the theme is activated before WooCommerce, the first pass creates a
	 * Home-only menu (Shop and My Account permalinks do not exist yet). Setup
	 * keeps retrying until WooCommerce is active; on a later pass the menu it
	 * created is already assigned, so rather than skip, it tops up the items
	 * that could not be added the first time.
	 *
	 * Only a menu setup created itself (tracked by PRIMARY_MENU_OPTION) is ever
	 * modified. A menu the site owner built is never adopted or changed, even if
	 * it shares the default name: there is no reliable way to prove provenance
	 * of a pre-existing menu, so setup does not try.
	 *
	 * @since 0.6.0
	 *
	 * @return void
	 */
	private function seed_primary_menu() {
		$owned_id = (int) get_option( self::PRIMARY_MENU_OPTION );

		if ( has_nav_menu( 'primary' ) ) {
			$locations = (array) get_theme_mod( 'nav_menu_locations', array() );
			$assigned  = isset( $locations['primary'] ) ? (int) $locations['primary'] : 0;

			// Top up only the menu setup created and that is still the one
			// assigned; anything else is the owner's and must be left alone.
			if ( $owned_id && $owned_id === $assigned ) {
				$this->ensure_primary_menu_items( $owned_id );
			}

			return;
		}

		$menu_name = __( 'Main Menu', 'commercebuild-velocity' );
		$existing  = wp_get_nav_menu_object( $menu_name );

		if ( $existing ) {
			// A menu named like our default already exists but is unassigned.
			// Use it for the location, but never claim ownership (no marker), so
			// a later run leaves the owner's menu untouched.
			$menu_id = (int) $existing->term_id;
		} else {
			$created = wp_create_nav_menu( $menu_name );

			// wp_create_nav_menu() returns a WP_Error on failure; test it before
			// casting, since (int) WP_Error is 1 and would target an unrelated
			// menu.
			if ( is_wp_error( $created ) ) {
				return;
			}

			$menu_id = (int) $created;

			if ( ! $menu_id ) {
				return;
			}

			$this->ensure_primary_menu_items( $menu_id );
			update_option( self::PRIMARY_MENU_OPTION, $menu_id, true );
		}

		$locations            = (array) get_theme_mod( 'nav_menu_locations', array() );
		$locations['primary'] = $menu_id;

		set_theme_mod( 'nav_menu_locations', $locations );
	}

	/**
	 * Add the standard Home / Shop / My Account items to a setup-created menu,
	 * skipping any already present so the step is safe to run more than once.
	 *
	 * @since 0.11.5
	 *
	 * @param int $menu_id Menu term id.
	 * @return void
	 */
	private function ensure_primary_menu_items( $menu_id ) {
		$items = array(
			array( __( 'Home', 'commercebuild-velocity' ), home_url( '/' ) ),
		);

		if ( function_exists( 'wc_get_page_permalink' ) ) {
			$items[] = array( __( 'Shop', 'commercebuild-velocity' ), wc_get_page_permalink( 'shop' ) );
			$items[] = array( __( 'My Account', 'commercebuild-velocity' ), wc_get_page_permalink( 'myaccount' ) );
		}

		$existing_items = wp_get_nav_menu_items( $menu_id );
		$existing       = array();
		$position       = 0;

		if ( is_array( $existing_items ) ) {
			foreach ( $existing_items as $item ) {
				$existing[ untrailingslashit( $item->url ) ] = true;
				// Append after the highest existing order; a plain count would
				// collide when orders have gaps or duplicates.
				$position = max( $position, (int) $item->menu_order );
			}
		}

		foreach ( $items as $item ) {
			$url = (string) $item[1];

			// A missing WooCommerce page yields an empty permalink; skip it
			// rather than create a broken link. Because the step is additive and
			// dedupes by URL, a later run adds it once the page exists.
			if ( '' === $url || isset( $existing[ untrailingslashit( $url ) ] ) ) {
				continue;
			}

			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'    => $item[0],
					'menu-item-url'      => $url,
					'menu-item-status'   => 'publish',
					'menu-item-position' => ++$position,
				)
			);

			$existing[ untrailingslashit( $url ) ] = true;
		}
	}

	/**
	 * Replace the default WooCommerce My Account page content with the
	 * tabbed Account Tabs layout.
	 *
	 * Only touches the page when its content is still exactly WooCommerce's
	 * default shortcode, so a customized account page is never clobbered.
	 *
	 * @since 0.5.0
	 *
	 * @return void
	 */
	private function seed_account_page() {
		if ( ! function_exists( 'wc_get_page_id' ) ) {
			return;
		}

		$page_id = (int) wc_get_page_id( 'myaccount' );

		if ( $page_id <= 0 ) {
			return;
		}

		$page = get_post( $page_id );

		if ( ! $page ) {
			return;
		}

		$content = trim( (string) $page->post_content );

		// Already converted, or customized by the site: leave it alone.
		if ( false !== strpos( $content, 'commercebuild/account-tabs' ) ) {
			return;
		}

		// WooCommerce ships the account page as a bare shortcode on older
		// installs and as a wrapped shortcode block on newer ones; both count
		// as untouched.
		$normalized = trim( preg_replace( '/<!--\s*\/?wp:shortcode\s*-->/', '', $content ) );

		if ( '[woocommerce_my_account]' !== $normalized ) {
			return;
		}

		update_post_meta( $page_id, self::BACKUP_META, $page->post_content );

		wp_update_post(
			array(
				'ID'           => $page_id,
				'post_content' => self::default_account_tabs(),
			)
		);
	}

	/**
	 * Default tab set for the account page.
	 *
	 * Portal tabs are only included when their shortcode actually exists, so
	 * a plain WooCommerce site never renders literal shortcode text.
	 *
	 * @since 0.5.0
	 *
	 * @return string Serialized block markup.
	 */
	public static function default_account_tabs() {
		$candidates = array(
			array( 'Summary', 'cfw_portal_summary', true ),
			array( 'Orders', 'cfw_portal_orders_table', true ),
			array( 'Invoices', 'cfw_portal_invoices_table', true ),
			array( 'Payments', 'cfw_portal_payments_table', true ),
			array( 'Addresses', 'cfw_manage_addresses', true ),
			array( 'Transactions', 'cfw_portal_transactions_table', false ),
			array( 'Credit Memos', 'cfw_portal_sales_credit_memo_table', false ),
			array( 'Documents', 'cfw_portal_documents_table', false ),
			array( 'Quotes', 'cfw_portal_quotes_table', false ),
			array( 'Saved Carts', 'cfw_saved_carts', false ),
			array( 'Order Import', 'cfw_order_import', false ),
		);

		$tabs = array();

		foreach ( $candidates as $candidate ) {
			list( $title, $shortcode, $active ) = $candidate;

			if ( ! shortcode_exists( $shortcode ) ) {
				continue;
			}

			$tabs[] = self::tab_markup( $title, '[' . $shortcode . ']', $active );
		}

		// Always present: the standard WooCommerce account screens.
		$tabs[] = self::tab_markup( __( 'Account', 'commercebuild-velocity' ), '[woocommerce_my_account]', true );

		$markup = '<!-- wp:commercebuild/account-tabs -->' . implode( '', $tabs ) . '<!-- /wp:commercebuild/account-tabs -->';

		/**
		 * Filter the account page tab markup created during setup.
		 *
		 * @since 0.5.0
		 *
		 * @param string $markup Serialized block markup.
		 * @param array  $tabs   Individual tab markup fragments.
		 */
		return apply_filters( 'cbv_default_account_tabs', $markup, $tabs );
	}

	/**
	 * Build one account tab containing a shortcode block.
	 *
	 * @since 0.5.0
	 *
	 * @param string $title     Tab title.
	 * @param string $shortcode Shortcode including brackets.
	 * @param bool   $active    Whether the tab is active.
	 * @return string
	 */
	private static function tab_markup( $title, $shortcode, $active ) {
		$attrs = array( 'title' => $title );

		if ( ! $active ) {
			$attrs['active'] = false;
		}

		return '<!-- wp:commercebuild/account-tab ' . wp_json_encode( $attrs ) . ' -->'
			. '<!-- wp:shortcode -->' . $shortcode . '<!-- /wp:shortcode -->'
			. '<!-- /wp:commercebuild/account-tab -->';
	}
}
