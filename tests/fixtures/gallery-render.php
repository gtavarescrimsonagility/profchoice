<?php
/**
 * Isolated gallery rendering harness. Never load in WordPress.
 *
 * @package Commercebuild_Velocity
 */

define( 'ABSPATH', __DIR__ );
require __DIR__ . '/class-producttype.php';
require __DIR__ . '/class-wc-product.php';
if ( 'legacy' !== $argv[2] ) {
	require __DIR__ . '/class-productmediagallery.php';
}
$scenario = $argv[2];
$product  = 'invalid' === $scenario ? null : new WC_Product();

/**
 * Dispatch fixture filters.
 *
 * @param string $hook Hook name.
 * @param mixed  $value Value.
 * @param mixed  ...$args Arguments.
 * @return mixed
 */
function apply_filters( $hook, $value, ...$args ) {
	if ( 'cbv_product_fallback_image_html' === $hook ) {
		return '<img src="custom-fallback.png">';
	}
	if ( 'cbv_product_fallback_thumb_count' === $hook ) {
		return 2;
	}
	if ( 'woocommerce_single_product_image_thumbnail_html' === $hook ) {
		return $value . '<span>image-filter-' . $args[0] . '</span>';
	}
	if ( 'woocommerce_single_product_video_thumbnail_html' === $hook ) {
		return $value . '<span>video-filter-' . $args[0] . '</span>';
	}
	return $value;
}

/**
 * Render fixture actions.
 *
 * @param string $hook Hook name.
 */
function do_action( $hook ) {
	if ( 'cbv_product_gallery_before' === $hook ) {
		echo '<span>badge-hook</span>';
	}
	if ( 'woocommerce_product_thumbnails' === $hook ) {
		echo '<div>thumbnail-slide</div>';
	}
}

/**
 * Render a fixture image.
 *
 * @param int  $id Image ID.
 * @param bool $main Main image.
 * @return string
 */
function wc_get_gallery_image_html( $id, $main ) {
	return $main ? '<div>image-' . $id . '</div>' : '';
}

/**
 * Normalize an integer.
 *
 * @param mixed $value Input.
 * @return int
 */
function absint( $value ) {
	return abs( (int) $value );
}

/**
 * Escape a fixture attribute.
 *
 * @param string $value Input.
 * @return string
 */
function esc_attr( $value ) {
	return htmlspecialchars( $value, ENT_QUOTES );
}

/**
 * Escape a fixture URL.
 *
 * @param string $value Input.
 * @return string
 */
function esc_url( $value ) {
	return esc_attr( $value );
}

/**
 * Pass through fixture class names.
 *
 * @param string $value Input.
 * @return string
 */
function sanitize_html_class( $value ) {
	return $value;
}

require $argv[1];
