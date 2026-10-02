<?php
/**
 * Size guides: the page each attribute points to, the modal printed once
 * per page, and the shortcode, action and swatches integration that render
 * the profchoice/size-guide block.
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore\SizeGuide;

/**
 * Size guide pages and rendering.
 */
final class SizeGuide {

	const OPTION = 'profchoice_size_guides';
	const STORE  = 'profchoice/size-guide';
	const MODULE = 'profchoice-size-guide-store';

	/**
	 * Pages whose dialog the footer prints.
	 *
	 * @var int[]
	 */
	private static $dialogs = array();

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'init', array( __CLASS__, 'register_store' ), 5 );
		add_action( 'wp_footer', array( __CLASS__, 'print_dialogs' ), 5 );
		add_shortcode( 'profchoice_size_guide', array( __CLASS__, 'shortcode' ) );
		add_action( 'profchoicecore_size_guide', array( __CLASS__, 'action' ), 10, 2 );
		add_filter( 'profchoicecore_swatches_guide', array( __CLASS__, 'swatches_guide' ), 10, 2 );
	}

	/**
	 * The store script module (`viewScriptModule` of the block).
	 *
	 * @return void
	 */
	public static function register_store() {
		$build = dirname( __DIR__ ) . '/build/store/';
		if ( ! file_exists( $build . 'index.asset.php' ) ) {
			return;
		}
		$asset = require $build . 'index.asset.php';
		wp_register_script_module(
			self::MODULE,
			plugins_url( 'build/store/index.js', dirname( __DIR__ ) . '/module.php' ),
			$asset['dependencies'],
			$asset['version']
		);
	}

	/**
	 * Attribute ID => page ID.
	 *
	 * @return array<int, int>
	 */
	public static function pages() {
		$pages = get_option( self::OPTION, array() );
		return is_array( $pages ) ? array_map( 'absint', $pages ) : array();
	}

	/**
	 * The published size guide page of an attribute taxonomy, or 0.
	 *
	 * @param string $taxonomy Attribute taxonomy (`pa_size`).
	 * @return int
	 */
	public static function page_for( $taxonomy ) {
		$page = 0;
		if ( function_exists( 'wc_attribute_taxonomy_id_by_name' ) && 0 === strpos( (string) $taxonomy, 'pa_' ) ) {
			$pages = self::pages();
			$id    = wc_attribute_taxonomy_id_by_name( $taxonomy );
			$page  = isset( $pages[ $id ] ) ? $pages[ $id ] : 0;
		}
		/**
		 * Filters the size guide page of an attribute.
		 *
		 * @param int    $page     Page ID (0 for none).
		 * @param string $taxonomy Attribute taxonomy.
		 */
		$page = absint( apply_filters( 'profchoicecore_size_guide_page', $page, (string) $taxonomy ) );
		return $page && 'publish' === get_post_status( $page ) ? $page : 0;
	}

	/**
	 * Print the page's dialog in the footer (once per page).
	 *
	 * @param int $page_id Page ID.
	 * @return void
	 */
	public static function need_dialog( $page_id ) {
		self::$dialogs[ $page_id ] = $page_id;
		wp_enqueue_script_module( self::MODULE );
	}

	/**
	 * The dialogs of the size guides linked on this page.
	 *
	 * @return void
	 */
	public static function print_dialogs() {
		foreach ( self::$dialogs as $pc_page_id ) {
			$pc_page = get_post( $pc_page_id );
			if ( ! $pc_page ) {
				continue;
			}
			ob_start();
			require dirname( __DIR__ ) . '/templates/dialog.php';
			echo wp_interactivity_process_directives( (string) ob_get_clean() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Render the block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public static function render( array $attributes ) {
		return render_block(
			array(
				'blockName'    => 'profchoice/size-guide',
				'attrs'        => $attributes,
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array(),
			)
		);
	}

	/**
	 * `[profchoice_size_guide attribute="pa_size" page="12" text="Size Guide" class=""]`.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function shortcode( $atts ) {
		$atts       = shortcode_atts(
			array(
				'attribute' => '',
				'page'      => 0,
				'text'      => '',
				'class'     => '',
			),
			$atts,
			'profchoice_size_guide'
		);
		$attributes = array( 'attribute' => sanitize_key( $atts['attribute'] ) );
		if ( $atts['page'] ) {
			$attributes['pageId'] = absint( $atts['page'] );
		}
		if ( '' !== $atts['text'] ) {
			$attributes['text'] = sanitize_text_field( $atts['text'] );
		}
		if ( '' !== $atts['class'] ) {
			$attributes['className'] = sanitize_text_field( $atts['class'] );
		}
		return self::render( $attributes );
	}

	/**
	 * `do_action( 'profchoicecore_size_guide', 'pa_size', array( 'className' => '…' ) )`.
	 *
	 * @param string $taxonomy   Attribute taxonomy.
	 * @param array  $attributes Extra block attributes.
	 * @return void
	 */
	public static function action( $taxonomy, $attributes = array() ) {
		echo self::render( array_merge( (array) $attributes, array( 'attribute' => (string) $taxonomy ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * The guide link in the swatches' head (block-swatches module), with the
	 * swatches' own link class so themes style it the same way.
	 *
	 * @param string $html     Guide markup so far.
	 * @param string $taxonomy Attribute taxonomy.
	 * @return string
	 */
	public static function swatches_guide( $html, $taxonomy ) {
		if ( '' !== $html || ! self::page_for( $taxonomy ) ) {
			return $html;
		}
		return self::render(
			array(
				'attribute' => (string) $taxonomy,
				'className' => 'pc-swatches__guide',
			)
		);
	}
}
