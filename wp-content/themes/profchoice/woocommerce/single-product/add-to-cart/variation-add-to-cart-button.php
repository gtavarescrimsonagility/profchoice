<?php
/**
 * Purchase row of a variable product: quantity stepper, add to cart with the
 * price, and favorite.
 *
 * @package profchoice
 * @version 10.5.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

$pdp_price = html_entity_decode( wp_strip_all_tags( $product->get_price_html() ), ENT_QUOTES, 'UTF-8' );
?>
<div class="woocommerce-variation-add-to-cart variations_button pdp-main__purchase">
	<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>
	<?php do_action( 'woocommerce_before_add_to_cart_quantity' ); ?>
	<div class="pdp-qty">
		<button class="pdp-qty__step" type="button" aria-label="<?php esc_attr_e( 'Decrease quantity', 'profchoice' ); ?>" data-wp-on--click="actions.decrease">−</button>
		<input
			class="pdp-qty__value"
			type="number"
			name="quantity"
			value="<?php echo esc_attr( isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : $product->get_min_purchase_quantity() ); // phpcs:ignore WordPress.Security.NonceVerification.Missing ?>"
			min="<?php echo esc_attr( $product->get_min_purchase_quantity() ); ?>"
			<?php if ( $product->get_max_purchase_quantity() > 0 ) : ?>
				max="<?php echo esc_attr( $product->get_max_purchase_quantity() ); ?>"
			<?php endif; ?>
			step="1"
			inputmode="numeric"
			aria-label="<?php esc_attr_e( 'Quantity', 'profchoice' ); ?>"
		>
		<button class="pdp-qty__step" type="button" aria-label="<?php esc_attr_e( 'Increase quantity', 'profchoice' ); ?>" data-wp-on--click="actions.increase">+</button>
	</div>
	<?php do_action( 'woocommerce_after_add_to_cart_quantity' ); ?>
	<button type="submit" class="single_add_to_cart_button pdp-main__cart" data-wp-text="state.cartLabel">
		<?php
		/* translators: %s: price. */
		echo esc_html( sprintf( __( 'Add to Cart — %s', 'profchoice' ), $pdp_price ) );
		?>
	</button>
	<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>
	<button class="pdp-main__fav pdp-main__fav--box" type="button" aria-label="<?php esc_attr_e( 'Add to favorites', 'profchoice' ); ?>">
		<?php echo profchoice_icon( 'heart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</button>
	<input type="hidden" name="add-to-cart" value="<?php echo absint( $product->get_id() ); ?>" />
	<input type="hidden" name="product_id" value="<?php echo absint( $product->get_id() ); ?>" />
	<input type="hidden" name="variation_id" class="variation_id" value="0" />
</div>
