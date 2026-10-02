<?php
/**
 * Product page (PDP): assets and template helpers for the classic single
 * product template in woocommerce/ and template-parts/pdp/.
 *
 * @package profchoice
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Styles are built from src/scss (npm run build) into build/, which is not
 * committed: the release workflow builds it.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}
		$file = get_stylesheet_directory() . '/build/css/pdp.css';
		if ( ! file_exists( $file ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				trigger_error( 'profchoice: build/css/pdp.css is missing, run npm run build in the theme.', E_USER_WARNING ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
			}
			return;
		}
		$deps = array_filter(
			array( 'cbv-main', 'woocommerce-general' ),
			function ( $handle ) {
				return wp_style_is( $handle, 'registered' );
			}
		);
		wp_enqueue_style( 'profchoice-pdp', get_stylesheet_directory_uri() . '/build/css/pdp.css', $deps, (string) filemtime( $file ) );

		wp_enqueue_script_module(
			'profchoice-pdp',
			get_stylesheet_directory_uri() . '/assets/js/pdp.js',
			array( '@wordpress/interactivity' ),
			(string) filemtime( get_stylesheet_directory() . '/assets/js/pdp.js' )
		);
	},
	110
);

/**
 * An icon of the profchoicecore collection as inline SVG (currentColor).
 *
 * @param string $name Icon name in the profchoice collection.
 * @return string
 */
function profchoice_icon( $name ) {
	if ( ! function_exists( 'wp_get_icon' ) ) {
		return '';
	}
	return trim(
		wp_get_icon(
			'profchoice/' . $name,
			array(
				'size'  => null,
				'class' => 'pc-inline-icon',
			)
		)
	);
}

/**
 * Badge text of a product (profchoicecore "Product page" tab).
 *
 * @param int $product_id Product ID.
 * @return string
 */
function profchoice_badge( $product_id ) {
	return class_exists( 'ProfChoiceCore\ProductMeta' ) ? ProfChoiceCore\ProductMeta::badge( $product_id ) : '';
}

/**
 * Breadcrumb trail of a product: Home, Shop, the deepest category's
 * ancestors and the category, then the product.
 *
 * @param WC_Product $product Product.
 * @return array<int, array{label: string, url: string}>
 */
function profchoice_product_trail( $product ) {
	$trail = array(
		array(
			'label' => __( 'Home', 'profchoice' ),
			'url'   => home_url( '/' ),
		),
	);
	$shop  = wc_get_page_id( 'shop' );
	if ( $shop > 0 ) {
		$trail[] = array(
			'label' => get_the_title( $shop ),
			'url'   => get_permalink( $shop ),
		);
	}

	$terms = wc_get_product_terms( $product->get_id(), 'product_cat', array( 'orderby' => 'parent', 'order' => 'DESC' ) );
	if ( $terms ) {
		$term = $terms[0];
		foreach ( array_reverse( get_ancestors( $term->term_id, 'product_cat' ) ) as $ancestor ) {
			$ancestor = get_term( $ancestor, 'product_cat' );
			$trail[]  = array(
				'label' => $ancestor->name,
				'url'   => get_term_link( $ancestor ),
			);
		}
		$trail[] = array(
			'label' => $term->name,
			'url'   => get_term_link( $term ),
		);
	}

	$trail[] = array(
		'label' => $product->get_name(),
		'url'   => '',
	);
	return $trail;
}

/**
 * Color swatches of a product card: the colors of its pa_color terms.
 *
 * @param WC_Product $product Product.
 * @return string[] Hex colors.
 */
function profchoice_card_colors( $product ) {
	if ( ! class_exists( 'ProfChoiceCore\Swatches' ) || ! taxonomy_exists( 'pa_color' ) ) {
		return array();
	}
	// In the attribute's term order (Products > Attributes > Color).
	$colors = array();
	foreach ( wc_get_product_terms( $product->get_id(), 'pa_color', array( 'fields' => 'all' ) ) as $term ) {
		$visual = ProfChoiceCore\Swatches::term_visual( $term );
		if ( 'color' === $visual['type'] ) {
			$colors[] = $visual['value'];
		}
	}
	return $colors;
}
