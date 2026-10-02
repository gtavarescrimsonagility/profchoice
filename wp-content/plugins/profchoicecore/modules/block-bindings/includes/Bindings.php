<?php
/**
 * Block bindings to WooCommerce data (src/bindings for the editor side), so
 * designed blocks keep their markup and styles while their names, links and
 * images come from the store:
 *
 * - profchoice/product-category: a product category by slug (name, url).
 * - profchoice/product: a product, by ID or by its position among the
 *   featured products (name, url, image_id, image_url, image_alt).
 * - profchoice/shop: the shop page (url).
 *
 * A binding that finds nothing returns null, so the block keeps its saved
 * value.
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the binding sources, their editor script and its REST data.
 */
final class Bindings {

	const HANDLE = 'profchoicecore-bindings';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'init', array( __CLASS__, 'register_sources' ) );
		add_action( 'init', array( __CLASS__, 'register_assets' ), 20 );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_editor' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_route' ) );
	}

	/**
	 * Whether WooCommerce is loaded.
	 *
	 * @return bool
	 */
	private static function has_store() {
		return function_exists( 'wc_get_product' );
	}

	/**
	 * Registers the binding sources.
	 *
	 * @return void
	 */
	public static function register_sources() {
		if ( ! function_exists( 'register_block_bindings_source' ) ) {
			return;
		}
		register_block_bindings_source(
			'profchoice/product-category',
			array(
				'label'              => __( 'Product category', 'profchoicecore' ),
				'get_value_callback' => array( __CLASS__, 'category_value' ),
			)
		);
		register_block_bindings_source(
			'profchoice/product',
			array(
				'label'              => __( 'Product', 'profchoicecore' ),
				'get_value_callback' => array( __CLASS__, 'product_value' ),
			)
		);
		register_block_bindings_source(
			'profchoice/shop',
			array(
				'label'              => __( 'Shop', 'profchoicecore' ),
				'get_value_callback' => array( __CLASS__, 'shop_value' ),
			)
		);
	}

	/**
	 * A product category's name or link.
	 *
	 * @param array     $args      Binding args: slug, key (name|url).
	 * @param \WP_Block $block     Block instance.
	 * @param string    $attribute Bound attribute.
	 * @return string|null
	 */
	public static function category_value( array $args, $block, $attribute ) {
		$data = self::category( (string) ( $args['slug'] ?? '' ) );
		$key  = (string) ( $args['key'] ?? '' );
		if ( ! $data || ! isset( $data[ $key ] ) ) {
			return null;
		}
		return 'name' === $key ? self::keep_breaks( $data['name'], $block, $attribute ) : $data[ $key ];
	}

	/**
	 * A product's name, link or image.
	 *
	 * @param array     $args      Binding args: id or featured (1-based), key.
	 * @param \WP_Block $block     Block instance.
	 * @param string    $attribute Bound attribute.
	 * @return string|int|null
	 */
	public static function product_value( array $args, $block, $attribute ) {
		$data = self::product( $args );
		$key  = (string) ( $args['key'] ?? '' );
		if ( ! $data || ! isset( $data[ $key ] ) ) {
			return null;
		}
		return 'name' === $key ? self::keep_breaks( $data['name'], $block, $attribute ) : $data[ $key ];
	}

	/**
	 * The shop page link.
	 *
	 * @param array $args Binding args: key (url).
	 * @return string|null
	 */
	public static function shop_value( array $args ) {
		return 'url' === ( $args['key'] ?? '' ) ? self::shop_url() : null;
	}

	/**
	 * Keeps the line breaks of the saved text when it is the same name, so a
	 * designed break ("Ventech Sport<br>Boots") survives; another name
	 * replaces it.
	 *
	 * @param string    $name      Name from the store.
	 * @param \WP_Block $block     Block instance.
	 * @param string    $attribute Bound attribute.
	 * @return string
	 */
	private static function keep_breaks( $name, $block, $attribute ) {
		$saved = (string) ( $block->attributes[ $attribute ] ?? '' );
		// Rich text comes from the saved HTML, not the comment: the inside
		// of the block's element.
		if ( '' === $saved && preg_match( '/^\s*<[^>]+>(.*)<\/[a-z0-9]+>\s*$/is', (string) ( $block->parsed_block['innerHTML'] ?? '' ), $match ) ) {
			$saved = $match[1];
		}
		$plain = static function ( $text ) {
			return strtolower( trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( preg_replace( '/<br\s*\/?>/i', ' ', html_entity_decode( $text ) ) ) ) ) );
		};
		return '' !== $saved && $plain( $saved ) === $plain( $name ) ? $saved : esc_html( $name );
	}

	/**
	 * Category data by slug.
	 *
	 * @param string $slug Term slug.
	 * @return array|null
	 */
	private static function category( $slug ) {
		if ( ! self::has_store() || '' === $slug ) {
			return null;
		}
		$term = get_term_by( 'slug', $slug, 'product_cat' );
		if ( ! $term instanceof \WP_Term ) {
			return null;
		}
		$url = get_term_link( $term );
		return array(
			'name' => $term->name,
			'url'  => is_wp_error( $url ) ? '' : $url,
		);
	}

	/**
	 * Featured product IDs, in menu order (then newest first).
	 *
	 * @return int[]
	 */
	private static function featured_ids() {
		static $ids = null;
		if ( null === $ids ) {
			$ids = self::has_store() ? array_map(
				'intval',
				wc_get_products(
					array(
						'featured' => true,
						'status'   => 'publish',
						'limit'    => 12,
						'orderby'  => array(
							'menu_order' => 'ASC',
							'date'       => 'DESC',
						),
						'return'   => 'ids',
					)
				)
			) : array();
		}
		return $ids;
	}

	/**
	 * Product data by ID or featured position.
	 *
	 * @param array $args Binding args.
	 * @return array|null
	 */
	private static function product( array $args ) {
		if ( ! self::has_store() ) {
			return null;
		}
		if ( ! empty( $args['id'] ) ) {
			$id = absint( $args['id'] );
		} else {
			$position = max( 1, absint( $args['featured'] ?? 1 ) );
			$id       = self::featured_ids()[ $position - 1 ] ?? 0;
		}
		$product = $id ? wc_get_product( $id ) : null;
		if ( ! $product || ! $product->is_visible() ) {
			return null;
		}
		return self::product_data( $product );
	}

	/**
	 * Name, link and image of a product.
	 *
	 * @param \WC_Product $product Product.
	 * @return array
	 */
	private static function product_data( $product ) {
		$image_id = (int) $product->get_image_id();
		$data     = array(
			'id'   => $product->get_id(),
			'name' => $product->get_name(),
			'url'  => $product->get_permalink(),
		);
		if ( $image_id ) {
			$data['image_id']  = $image_id;
			$data['image_url'] = (string) wp_get_attachment_image_url( $image_id, 'full' );
			$data['image_alt'] = (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true );
		}
		return $data;
	}

	/**
	 * The shop page link.
	 *
	 * @return string|null
	 */
	private static function shop_url() {
		if ( ! self::has_store() ) {
			return null;
		}
		$url = get_permalink( wc_get_page_id( 'shop' ) );
		return $url ? $url : null;
	}

	/**
	 * Registers the editor script.
	 *
	 * @return void
	 */
	public static function register_assets() {
		$build = dirname( __DIR__ ) . '/build/bindings/';
		if ( ! file_exists( $build . 'index.asset.php' ) ) {
			return;
		}
		$asset = require $build . 'index.asset.php';
		wp_register_script( self::HANDLE, plugins_url( 'build/bindings/index.js', dirname( __DIR__ ) . '/module.php' ), $asset['dependencies'], $asset['version'], true );
		wp_set_script_translations( self::HANDLE, 'profchoicecore' );
	}

	/**
	 * Editor (admin document).
	 *
	 * @return void
	 */
	public static function enqueue_editor() {
		wp_enqueue_script( self::HANDLE );
	}

	/**
	 * GET /profchoicecore/v1/bindings: the values the editor shows for the
	 * bound blocks (categories by slug, featured products, shop link).
	 *
	 * @return void
	 */
	public static function register_route() {
		register_rest_route(
			'profchoicecore/v1',
			'/bindings',
			array(
				'methods'             => 'GET',
				'permission_callback' => static function () {
					return current_user_can( 'edit_posts' );
				},
				'callback'            => static function () {
					$categories = array();
					if ( self::has_store() ) {
						foreach ( get_terms(
							array(
								'taxonomy'   => 'product_cat',
								'hide_empty' => false,
							)
						) as $term ) {
							$categories[ $term->slug ] = self::category( $term->slug );
						}
					}
					$featured = array();
					foreach ( self::featured_ids() as $id ) {
						$product = wc_get_product( $id );
						if ( $product && $product->is_visible() ) {
							$featured[] = self::product_data( $product );
						}
					}
					return array(
						'categories' => (object) $categories,
						'featured'   => $featured,
						'shop'       => self::shop_url(),
					);
				},
			)
		);
	}
}
