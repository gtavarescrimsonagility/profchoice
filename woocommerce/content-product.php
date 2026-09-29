<?php
/**
 * Product loop item.
 *
 * Every archive/loop product renders through the shared card renderer so
 * markup and per-context toggles live in one place.
 *
 * @package Commercebuild_Velocity
 * @version 9.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

if ( ! $product instanceof WC_Product || ! $product->is_visible() ) {
	return;
}
?>
<li <?php wc_product_class( 'cbv-loop-item', $product ); ?>>
	<?php echo cbv_product_card( $product->get_id() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped during card rendering. ?>
</li>
