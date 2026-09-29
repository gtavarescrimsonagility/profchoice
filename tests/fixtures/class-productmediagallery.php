<?php
/**
 * Fixture media API.
 *
 * @package Commercebuild_Velocity
 */

namespace Automattic\WooCommerce\Internal\ProductGallery;

/** Minimal ordered media rendering double. */
class ProductMediaGallery {
	/**
	 * Return scenario media.
	 *
	 * @param \WC_Product $product Product.
	 * @return array
	 */
	public static function get_product_media_gallery_items_for_display( $product ) {
		return $product instanceof \WC_Product ? array(
			array(
				'id'          => 42,
				'source_type' => $GLOBALS['scenario'],
				'media_type'  => $GLOBALS['scenario'],
			),
		) : array();
	}

	/**
	 * Render the fixture video.
	 *
	 * @param array $item Media item.
	 * @param bool  $main Main item.
	 * @return string
	 */
	public static function get_gallery_video_html( $item, $main ) {
		return $main ? '<video>video-' . $item['id'] . '</video>' : '';
	}
}
