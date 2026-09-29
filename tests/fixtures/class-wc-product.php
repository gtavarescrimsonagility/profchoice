<?php
/**
 * Fixture product.
 *
 * @package Commercebuild_Velocity
 */

/** Minimal product rendering double. */
class WC_Product {
	/**
	 * Return the featured image.
	 *
	 * @return int
	 */
	public function get_image_id() {
		return 'video' === $GLOBALS['scenario'] ? 0 : 10;
	}

	/**
	 * Match the fixture product type.
	 *
	 * @param string $type Requested type.
	 * @return bool
	 */
	public function is_type( $type ) {
		return 'simple' === $type;
	}
}
