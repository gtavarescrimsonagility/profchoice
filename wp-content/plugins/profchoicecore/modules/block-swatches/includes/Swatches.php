<?php
/**
 * Variation swatches.
 *
 * WooCommerce stores colors and images for `wc-visual` attributes (term meta
 * `color` / `image`) but only draws swatches in its block-based product page.
 * This adds the attribute types on any theme, and renders the
 * `profchoice/swatches` block (the source of truth) into the classic
 * variations form, next to the select WooCommerce's script keeps using.
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore;

/**
 * Attribute types, swatch data and the classic form integration.
 */
final class Swatches {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_filter( 'product_attributes_type_selector', array( __CLASS__, 'attribute_types' ) );
		add_filter( 'woocommerce_dropdown_variation_attribute_options_html', array( __CLASS__, 'dropdown_html' ), 20, 2 );
		add_shortcode( 'profchoice_swatches', array( __CLASS__, 'shortcode' ) );
	}

	/**
	 * Attribute types: core's "Color / image" on any theme (WooCommerce only
	 * offers it on block themes with an experimental flag), and "Button" for
	 * labels such as sizes. The slugs match the ones swatch plugins use, so
	 * stored attributes stay portable.
	 *
	 * @param array $types Type => label.
	 * @return array
	 */
	public static function attribute_types( $types ) {
		$types = (array) $types;
		if ( ! isset( $types['wc-visual'] ) ) {
			$types['wc-visual'] = __( 'Color / image', 'profchoicecore' );
		}
		$types['button'] = __( 'Button', 'profchoicecore' );
		return $types;
	}

	/**
	 * Type of a global attribute (`select`, `wc-visual`, `button`…).
	 *
	 * @param string $taxonomy Attribute taxonomy (`pa_color`).
	 * @return string Empty for custom (non-taxonomy) attributes.
	 */
	public static function attribute_type( $taxonomy ) {
		if ( ! function_exists( 'wc_attribute_taxonomy_id_by_name' ) || ! taxonomy_exists( $taxonomy ) ) {
			return '';
		}
		$attribute = wc_get_attribute( wc_attribute_taxonomy_id_by_name( $taxonomy ) );
		return $attribute ? (string) $attribute->type : '';
	}

	/**
	 * Whether the attribute renders as swatches: its type, set in Products >
	 * Attributes ("Select" keeps WooCommerce's dropdown).
	 *
	 * @param string $taxonomy Attribute taxonomy.
	 * @return bool
	 */
	public static function is_swatch_attribute( $taxonomy ) {
		return in_array( self::attribute_type( $taxonomy ), array( 'wc-visual', 'button' ), true );
	}

	/**
	 * A term's visual: WooCommerce's `image` (attachment ID) or `color` (hex)
	 * meta, or the keys swatch plugins use.
	 *
	 * @param \WP_Term $term Attribute term.
	 * @return array{type: string, value: string}
	 */
	public static function term_visual( $term ) {
		$image = absint( get_term_meta( $term->term_id, 'image', true ) );
		$image = $image ? $image : absint( get_term_meta( $term->term_id, 'product_attribute_image', true ) );
		$color = sanitize_hex_color( (string) get_term_meta( $term->term_id, 'color', true ) );
		$color = $color ? $color : sanitize_hex_color( (string) get_term_meta( $term->term_id, 'product_attribute_color', true ) );

		if ( $image ) {
			$url = wp_get_attachment_image_url( $image, 'thumbnail' );
			if ( $url ) {
				return array(
					'type'  => 'image',
					'value' => $url,
				);
			}
		}
		if ( $color ) {
			return array(
				'type'  => 'color',
				'value' => $color,
			);
		}
		return array(
			'type'  => 'none',
			'value' => '',
		);
	}

	/**
	 * The swatches of one attribute, in the order of its terms.
	 *
	 * @param \WC_Product $product  Variable product.
	 * @param string      $taxonomy Attribute taxonomy.
	 * @return array<int, array{value: string, label: string, visual: array, available: bool}>
	 */
	public static function items( $product, $taxonomy ) {
		$attributes = $product->get_variation_attributes();
		if ( ! isset( $attributes[ $taxonomy ] ) ) {
			return array();
		}
		$options  = array_map( 'strval', (array) $attributes[ $taxonomy ] );
		$in_stock = self::in_stock_values( $product, $taxonomy );
		$items    = array();

		foreach ( wc_get_product_terms( $product->get_id(), $taxonomy, array( 'fields' => 'all' ) ) as $term ) {
			if ( ! in_array( $term->slug, $options, true ) ) {
				continue;
			}
			$items[] = array(
				'value'     => $term->slug,
				/** This filter is documented in woocommerce/includes/wc-template-functions.php */
				'label'     => apply_filters( 'woocommerce_variation_option_name', $term->name, $term, $taxonomy, $product ),
				'visual'    => self::term_visual( $term ),
				'available' => null === $in_stock || in_array( $term->slug, $in_stock, true ),
			);
		}
		return $items;
	}

	/**
	 * Values of an attribute that some in-stock variation offers.
	 *
	 * @param \WC_Product $product  Variable product.
	 * @param string      $taxonomy Attribute taxonomy.
	 * @return string[]|null Null when any value can be in stock (a variation
	 *                       for "any" value is in stock).
	 */
	private static function in_stock_values( $product, $taxonomy ) {
		$values = array();
		foreach ( $product->get_children() as $child_id ) {
			$variation = wc_get_product( $child_id );
			if ( ! $variation || ! $variation->is_in_stock() || 'publish' !== $variation->get_status() ) {
				continue;
			}
			$value = (string) get_post_meta( $child_id, 'attribute_' . $taxonomy, true );
			if ( '' === $value ) {
				return null;
			}
			$values[] = $value;
		}
		return array_values( array_unique( $values ) );
	}

	/**
	 * Classic form: keep WooCommerce's select (hidden, its script still reads
	 * it) inside the swatches block.
	 *
	 * Themes pass block attributes through the `profchoice_swatches` argument of
	 * `wc_dropdown_variation_attribute_options()` (e.g. a className or a size
	 * guide URL).
	 *
	 * @param string $html Select markup.
	 * @param array  $args Dropdown arguments.
	 * @return string
	 */
	public static function dropdown_html( $html, $args ) {
		$product  = isset( $args['product'] ) ? $args['product'] : null;
		$taxonomy = isset( $args['attribute'] ) ? (string) $args['attribute'] : '';

		if ( ! $product instanceof \WC_Product || ! self::is_swatch_attribute( $taxonomy ) ) {
			return $html;
		}

		$extra      = isset( $args['profchoice_swatches'] ) && is_array( $args['profchoice_swatches'] ) ? $args['profchoice_swatches'] : array();
		$attributes = array_merge(
			array(
				'attribute' => $taxonomy,
				'productId' => $product->get_id(),
				'selected'  => isset( $args['selected'] ) ? (string) $args['selected'] : '',
				'name'      => ! empty( $args['name'] ) ? (string) $args['name'] : 'attribute_' . sanitize_title( $taxonomy ),
			),
			$extra
		);

		/**
		 * Filters the attributes of a swatches block in the classic form.
		 *
		 * @param array       $attributes Block attributes.
		 * @param array       $args       Dropdown arguments.
		 * @param \WC_Product $product    Product.
		 */
		$attributes = apply_filters( 'profchoicecore_swatches_block_attributes', $attributes, $args, $product );

		return self::render( $attributes, $html );
	}

	/**
	 * `[profchoice_swatches attribute="pa_color" product="123"]`: the same
	 * block, for builders and widgets. It changes the product's variations
	 * form when it sits inside one.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'attribute'      => '',
				'product'        => 0,
				'show_label'     => 'true',
				'size_guide_url' => '',
				'class'          => '',
			),
			$atts,
			'profchoice_swatches'
		);

		$attributes = array(
			'attribute' => sanitize_key( $atts['attribute'] ),
			'productId' => absint( $atts['product'] ),
			'showLabel' => 'false' !== $atts['show_label'],
		);
		if ( '' !== $atts['size_guide_url'] ) {
			$attributes['sizeGuideUrl'] = esc_url_raw( $atts['size_guide_url'] );
		}
		if ( '' !== $atts['class'] ) {
			$attributes['className'] = sanitize_html_class( $atts['class'] );
		}
		return self::render( $attributes );
	}

	/**
	 * Render the block; inner content is the fallback select, if any.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $inner      Inner HTML.
	 * @return string
	 */
	public static function render( array $attributes, $inner = '' ) {
		return render_block(
			array(
				'blockName'    => 'profchoice/swatches',
				'attrs'        => $attributes,
				'innerBlocks'  => array(),
				'innerHTML'    => $inner,
				'innerContent' => array( $inner ),
			)
		);
	}
}
