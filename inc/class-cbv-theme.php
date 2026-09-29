<?php
/**
 * Theme setup.
 *
 * @package Commercebuild_Velocity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core theme supports, menus, and image sizes.
 *
 * @since 0.1.0
 */
class CBV_Theme {

	/**
	 * Register hooks.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'after_setup_theme', array( $this, 'setup' ) );
		add_filter( 'excerpt_length', array( $this, 'excerpt_length' ) );
		add_filter( 'walker_nav_menu_start_el', array( $this, 'add_submenu_toggle' ), 10, 4 );
		add_action( 'wp_head', array( $this, 'resource_hints' ), 1 );
	}

	/**
	 * Theme supports and registrations.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function setup() {
		load_theme_textdomain( 'commercebuild-velocity', CBV_DIR . 'languages' );

		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'customize-selective-refresh-widgets' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'editor-styles' );
		add_theme_support( 'wp-block-styles' );
		add_theme_support( 'align-wide' );
		add_theme_support(
			'html5',
			array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
		);

		add_theme_support(
			'custom-logo',
			array(
				'height'               => 120,
				'width'                => 480,
				'flex-height'          => true,
				'flex-width'           => true,
				'header-text'          => array( 'cbv-header__site-name' ),
				'unlink-homepage-logo' => false,
			)
		);

		add_theme_support( 'woocommerce' );
		add_theme_support( 'wc-product-gallery-zoom' );
		add_theme_support( 'wc-product-gallery-slider' );
		add_theme_support( 'wc-product-gallery-lightbox' );

		register_nav_menus(
			array(
				'primary'        => __( 'Primary Menu', 'commercebuild-velocity' ),
				'utility'        => __( 'Utility Menu', 'commercebuild-velocity' ),
				'categories'     => __( 'Category Strip', 'commercebuild-velocity' ),
				'footer'         => __( 'Footer Menu', 'commercebuild-velocity' ),
				'footer_shop'    => __( 'Footer: Shop', 'commercebuild-velocity' ),
				'footer_more'    => __( 'Footer: More categories', 'commercebuild-velocity' ),
				'footer_account' => __( 'Footer: Account', 'commercebuild-velocity' ),
				'footer_company' => __( 'Footer: Company', 'commercebuild-velocity' ),
			)
		);

		add_image_size( 'cbv-tile', 480, 480, true );
		add_image_size( 'cbv-hero', 1920, 900, true );

		add_editor_style( array( 'assets/css/main.css', 'assets/css/editor.css' ) );
	}

	/**
	 * Shorter excerpts for cards.
	 *
	 * @since 0.1.0
	 *
	 * @return int
	 */
	public function excerpt_length() {
		return 20;
	}

	/**
	 * Add a dedicated disclosure control beside links that own submenus.
	 *
	 * Keeping the control separate preserves the link's native destination
	 * while giving keyboard and touch users an explicit submenu action.
	 *
	 * @since 0.11.7
	 *
	 * @param string   $item_output Menu item markup.
	 * @param WP_Post  $item        Menu item object.
	 * @param int      $depth       Menu depth.
	 * @param stdClass $args        Menu arguments.
	 * @return string
	 */
	public function add_submenu_toggle( $item_output, $item, $depth, $args ) {
		$menu_class = isset( $args->menu_class ) ? (string) $args->menu_class : '';

		if ( false === strpos( $menu_class, 'cbv-nav__list' ) || empty( $item->classes ) || ! in_array( 'menu-item-has-children', $item->classes, true ) ) {
			return $item_output;
		}

		$label = sprintf(
			/* translators: %s: menu item label. */
			__( 'Toggle submenu for %s', 'commercebuild-velocity' ),
			wp_strip_all_tags( $item->title )
		);

		$toggle = sprintf(
			'<button type="button" class="cbv-nav__toggle" aria-expanded="false"><span class="screen-reader-text">%s</span><span class="cbv-nav__toggle-icon" aria-hidden="true"></span></button>',
			esc_html( $label )
		);

		/**
		 * Filter a navigation submenu disclosure control.
		 *
		 * @since 0.11.7
		 *
		 * @param string   $toggle Toggle button markup.
		 * @param WP_Post  $item   Menu item object.
		 * @param int      $depth  Menu depth.
		 * @param stdClass $args   Menu arguments.
		 */
		$toggle = apply_filters( 'cbv_nav_submenu_toggle_html', $toggle, $item, $depth, $args );

		return $item_output . $toggle;
	}

	/**
	 * Preconnect hints for the object storage / CDN origins of the site.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function resource_hints() {
		/**
		 * Filter the origins the theme preconnects to.
		 *
		 * @since 0.1.0
		 *
		 * @param string[] $origins List of origin URLs.
		 */
		$origins = apply_filters( 'cbv_preconnect_origins', array() );

		foreach ( $origins as $origin ) {
			printf( '<link rel="preconnect" href="%s" crossorigin>' . "\n", esc_url( $origin ) );
		}
	}
}
