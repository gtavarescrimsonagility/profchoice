<?php
/**
 * Block registration.
 *
 * All theme blocks are dynamic (server-rendered) so markup, caching, and
 * ElasticPress routing live in PHP. The editor side is a single dependency-
 * free script using ServerSideRender, so the theme needs no build step.
 *
 * @package Commercebuild_Velocity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the theme's Gutenberg blocks.
 *
 * @since 0.1.0
 */
class CBV_Blocks {

	/**
	 * Query engine.
	 *
	 * @var CBV_Query_Engine
	 */
	private $engine;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param CBV_Query_Engine $engine Query engine.
	 */
	public function __construct( CBV_Query_Engine $engine ) {
		$this->engine = $engine;
	}

	/**
	 * Register hooks.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_filter( 'block_categories_all', array( $this, 'block_category' ) );
	}

	/**
	 * Register the shared editor script and each block from metadata.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register_blocks() {
		wp_register_script(
			'cbv-blocks-editor',
			CBV_URI . 'assets/js/blocks-editor.js',
			array(
				'wp-blocks',
				'wp-element',
				'wp-block-editor',
				'wp-components',
				'wp-server-side-render',
				'wp-i18n',
			),
			CBV_VERSION,
			true
		);

		$view_scripts = array(
			'cbv-account-tabs-view' => 'blocks/account-tabs/view.js',
			'cbv-hero-slider-view'  => 'blocks/hero-slider/view.js',
		);

		foreach ( $view_scripts as $handle => $path ) {
			wp_register_script(
				$handle,
				CBV_URI . $path,
				array(),
				CBV_VERSION,
				array(
					'strategy'  => 'defer',
					'in_footer' => true,
				)
			);
		}

		/*
		 * The editor template must mirror what setup would create: a plain
		 * WooCommerce site has none of the cfw portal shortcodes, and
		 * inserting them anyway would print literal shortcode text.
		 */
		$portal_tabs = array();

		foreach ( self::portal_tab_map() as $shortcode => $tab ) {
			if ( shortcode_exists( $shortcode ) ) {
				$portal_tabs[] = array(
					'title'     => $tab['title'],
					'shortcode' => '[' . $shortcode . ']',
					'active'    => $tab['active'],
				);
			}
		}

		wp_localize_script(
			'cbv-blocks-editor',
			'cbvAccountTabs',
			array( 'tabs' => $portal_tabs )
		);

		$blocks = array(
			'product-grid',
			'related-products',
			'category-tiles',
			'hero-slider',
			'hot-deals',
			'account-tabs',
			'account-tab',
			'header-search',
			'header-cart',
			'header-account',
			'header-nav',
		);

		foreach ( $blocks as $block ) {
			$args = array();

			/*
			 * account-tabs assembles its children itself so it can wrap each
			 * one in a tab panel. Without this, WP_Block::render() renders the
			 * inner blocks into $content BEFORE invoking the callback
			 * (wp-includes/class-wp-block.php), and the callback's own
			 * $inner->render() runs every cfwPortal / WooCommerce account
			 * shortcode a second time - doubling the order and invoice
			 * queries on My Account.
			 */
			if ( 'account-tabs' === $block ) {
				$args['skip_inner_blocks'] = true;
			}

			register_block_type( CBV_DIR . 'blocks/' . $block, $args );
		}
	}

	/**
	 * Portal tabs offered in the editor, in display order.
	 *
	 * @since 0.8.0
	 *
	 * @return array Shortcode => array( title, active ).
	 */
	public static function portal_tab_map() {
		return array(
			'cfw_portal_summary'                 => array(
				'title'  => __( 'Summary', 'commercebuild-velocity' ),
				'active' => true,
			),
			'cfw_portal_orders_table'            => array(
				'title'  => __( 'Orders', 'commercebuild-velocity' ),
				'active' => true,
			),
			'cfw_portal_invoices_table'          => array(
				'title'  => __( 'Invoices', 'commercebuild-velocity' ),
				'active' => true,
			),
			'cfw_portal_payments_table'          => array(
				'title'  => __( 'Payments', 'commercebuild-velocity' ),
				'active' => true,
			),
			'cfw_manage_addresses'               => array(
				'title'  => __( 'Addresses', 'commercebuild-velocity' ),
				'active' => true,
			),
			'cfw_portal_transactions_table'      => array(
				'title'  => __( 'Transactions', 'commercebuild-velocity' ),
				'active' => false,
			),
			'cfw_portal_sales_credit_memo_table' => array(
				'title'  => __( 'Credit Memos', 'commercebuild-velocity' ),
				'active' => false,
			),
			'cfw_portal_documents_table'         => array(
				'title'  => __( 'Documents', 'commercebuild-velocity' ),
				'active' => false,
			),
			'cfw_portal_quotes_table'            => array(
				'title'  => __( 'Quotes', 'commercebuild-velocity' ),
				'active' => false,
			),
			'cfw_saved_carts'                    => array(
				'title'  => __( 'Saved Carts', 'commercebuild-velocity' ),
				'active' => false,
			),
			'cfw_order_import'                   => array(
				'title'  => __( 'Order Import', 'commercebuild-velocity' ),
				'active' => false,
			),
		);
	}

	/**
	 * Add a "commercebuild" block category.
	 *
	 * @since 0.1.0
	 *
	 * @param array $categories Block categories.
	 * @return array
	 */
	public function block_category( $categories ) {
		array_unshift(
			$categories,
			array(
				'slug'  => 'commercebuild',
				'title' => __( 'commercebuild', 'commercebuild-velocity' ),
			)
		);

		return $categories;
	}
}
