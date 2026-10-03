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
 * @param string $name  Icon name in the profchoice collection.
 * @param string $class Class of the svg.
 * @return string Empty when the icon (or the icons module) is missing.
 */
function profchoice_icon( $name, $class = 'pc-inline-icon' ) {
	if ( ! function_exists( 'wp_get_icon' ) ) {
		return '';
	}
	return trim(
		wp_get_icon(
			'profchoice/' . $name,
			array(
				'size'  => null,
				'class' => $class,
			)
		)
	);
}

/**
 * Badges of a product, slug => name (profchoicecore badge tags).
 *
 * @param int $product_id Product ID.
 * @return array<string, string>
 */
function profchoice_badges( $product_id ) {
	return class_exists( 'ProfChoiceCore\Badges' ) ? ProfChoiceCore\Badges::for_product( $product_id ) : array();
}

/**
 * Print a badge's style attribute: the tag's colors as CSS variables.
 *
 * @param string $slug Tag slug.
 * @return void
 */
function profchoice_badge_style( $slug ) {
	$style = class_exists( 'ProfChoiceCore\Badges' ) ? ProfChoiceCore\Badges::style( $slug ) : '';
	if ( '' !== $style ) {
		echo ' style="' . esc_attr( $style ) . '"';
	}
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

/**
 * The product stage as a core Gallery of Image blocks with "Expand on
 * click", so core's lightbox (full screen, previous and next) opens on it.
 * One image per color: the featured image, then each distinct variation
 * image, in the color terms' order. Only the current color's image shows
 * (profchoice/pdp state.isStageHidden), the first one by default.
 *
 * @param WC_Product $product Product.
 * @return string
 */
function profchoice_stage_gallery( $product ) {
	$featured = (int) $product->get_image_id();
	$items    = array();
	if ( $featured ) {
		$items[ $featured ] = array();
	}
	if ( $product->is_type( 'variable' ) && taxonomy_exists( 'pa_color' ) ) {
		$by_color = array();
		foreach ( $product->get_available_variations( 'objects' ) as $variation ) {
			$color = $variation->get_attribute( 'pa_color' ) ? $variation->get_attributes()['pa_color'] : '';
			$image = (int) $variation->get_image_id();
			if ( $color && $image && ! isset( $by_color[ $color ] ) ) {
				$by_color[ $color ] = $image;
			}
		}
		foreach ( wc_get_product_terms( $product->get_id(), 'pa_color', array( 'fields' => 'slugs' ) ) as $slug ) {
			if ( isset( $by_color[ $slug ] ) ) {
				$items[ $by_color[ $slug ] ][] = $slug;
			}
		}
	}
	if ( ! $items ) {
		return '';
	}

	$images = array();
	$first  = true;
	foreach ( $items as $id => $colors ) {
		$img      = wp_get_attachment_image(
			$id,
			'full',
			false,
			array(
				'class'         => 'pdp-main__image wp-image-' . $id,
				'fetchpriority' => $first ? 'high' : false,
				'loading'       => $first ? false : 'lazy',
			)
		);
		$images[] = array(
			'blockName'    => 'core/image',
			'attrs'        => array(
				'id'              => $id,
				'sizeSlug'        => 'full',
				'linkDestination' => 'none',
				'lightbox'        => array( 'enabled' => true ),
			),
			'innerBlocks'  => array(),
			'innerHTML'    => '<figure class="wp-block-image size-full">' . $img . '</figure>',
			'innerContent' => array( '<figure class="wp-block-image size-full">' . $img . '</figure>' ),
		);
		$first    = false;
	}
	$html = render_block(
		array(
			'blockName'    => 'core/gallery',
			'attrs'        => array(
				'linkTo'    => 'none',
				'imageCrop' => false,
				'className' => 'pdp-main__gallery',
			),
			'innerBlocks'  => $images,
			'innerHTML'    => '<figure class="wp-block-gallery pdp-main__gallery"></figure>',
			'innerContent' => array_merge( array( '<figure class="wp-block-gallery pdp-main__gallery">' ), array_fill( 0, count( $images ), null ), array( '</figure>' ) ),
		)
	);

	// Which colors each image shows; the first one is the default.
	$processor = new WP_HTML_Tag_Processor( $html );
	$index     = 0;
	$colors    = array_values( $items );
	while ( $processor->next_tag( array( 'tag_name' => 'figure', 'class_name' => 'wp-block-image' ) ) ) {
		$processor->set_attribute( 'data-pc-colors', implode( ' ', $colors[ $index ] ?? array() ) );
		$processor->set_attribute( 'data-pc-default', 0 === $index ? 'true' : 'false' );
		$processor->set_attribute( 'data-wp-bind--hidden', 'profchoice/pdp::state.isStageHidden' );
		if ( $index > 0 ) {
			$processor->set_attribute( 'hidden', true );
		}
		++$index;
	}
	return $processor->get_updated_html();
}
