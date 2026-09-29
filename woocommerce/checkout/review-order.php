<?php
/**
 * Checkout order summary table (cost lines only; method radios live in the main column).
 *
 * @package Commercebuild_Velocity
 * @version 11.0.0
 */

defined( 'ABSPATH' ) || exit;
?>
<table class="shop_table woocommerce-checkout-review-order-table">
	<thead>
		<tr>
			<th class="product-name"><?php esc_html_e( 'Product', 'commercebuild-velocity' ); ?></th>
			<th class="product-total"><?php esc_html_e( 'Subtotal', 'commercebuild-velocity' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php
		do_action( 'woocommerce_review_order_before_cart_contents' );

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
			$visible  = apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key );

			if ( $_product instanceof WC_Product && $_product->exists() && $cart_item['quantity'] > 0 && $visible ) {
				?>
				<tr class="<?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">
					<td class="product-name" colspan="2">
						<div class="cbv-checkout-line">
							<?php
							echo wp_kses_post(
								$_product->get_image(
									'woocommerce_gallery_thumbnail',
									array(
										'class' => 'cbv-checkout-line__thumb',
									)
								)
							);
							?>
							<span class="cbv-checkout-line__text">
								<?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key ) ); ?>
								<?php echo apply_filters( 'woocommerce_checkout_cart_item_quantity', ' <strong class="product-quantity">' . sprintf( '&times;&nbsp;%s', $cart_item['quantity'] ) . '</strong>', $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WC HTML. ?>
								<?php echo wc_get_formatted_cart_item_data( $cart_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WC HTML. ?>
								<a class="cbv-checkout-line__details" href="<?php echo esc_url( $_product->get_permalink() ); ?>"><?php esc_html_e( 'View details', 'commercebuild-velocity' ); ?></a>
							</span>
							<span class="product-total cbv-checkout-line__total">
								<span class="screen-reader-text">
									<?php
									esc_html_e(
										'Subtotal:',
										'commercebuild-velocity'
									);
									?>
								</span>
								<?php
								$cbv_line_total = apply_filters(
									'woocommerce_cart_item_subtotal',
									WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ),
									$cart_item,
									$cart_item_key
								);
								if (
									function_exists( 'cbv_is_zero_price_html' )
									&& cbv_is_zero_price_html( $cbv_line_total )
								) {
									$cbv_line_total = apply_filters(
										'cbv_zero_cart_item_subtotal',
										$cbv_line_total,
										$cart_item,
										$cart_item_key
									);
								}
								// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WC HTML.
								echo $cbv_line_total;
								?>
							</span>
						</div>
					</td>
				</tr>
				<?php
			}
		}

		do_action( 'woocommerce_review_order_after_cart_contents' );
		?>
	</tbody>
	<tfoot>
		<?php if ( wc_coupons_enabled() ) : ?>
			<tr class="cbv-checkout-coupon-row">
				<td colspan="2">
					<div class="cbv-checkout-coupon">
						<label for="cbv-coupon-code" class="screen-reader-text"><?php esc_html_e( 'Promo code', 'commercebuild-velocity' ); ?></label>
						<input type="text" class="input-text" name="coupon_code" id="cbv-coupon-code" form="woocommerce-checkout-form-coupon" placeholder="<?php esc_attr_e( 'Promo code', 'commercebuild-velocity' ); ?>">
						<button type="submit" class="button" name="apply_coupon" value="1" form="woocommerce-checkout-form-coupon"><?php esc_html_e( 'Apply', 'commercebuild-velocity' ); ?></button>
					</div>
				</td>
			</tr>
		<?php endif; ?>
		<tr class="cart-subtotal">
			<th><?php esc_html_e( 'Subtotal', 'commercebuild-velocity' ); ?></th>
			<td><?php wc_cart_totals_subtotal_html(); ?></td>
		</tr>
		<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
			<tr class="cart-discount coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
				<th><?php wc_cart_totals_coupon_label( $coupon ); ?></th>
				<td><?php wc_cart_totals_coupon_html( $coupon ); ?></td>
			</tr>
		<?php endforeach; ?>
		<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>
			<?php do_action( 'woocommerce_review_order_before_shipping' ); ?>
			<tr class="woocommerce-shipping-totals shipping">
				<th><?php esc_html_e( 'Shipping', 'commercebuild-velocity' ); ?></th>
				<td><?php echo wp_kses_post( str_replace( 'Free!', esc_html__( 'Free', 'commercebuild-velocity' ), WC()->cart->get_cart_shipping_total() ) ); ?></td>
			</tr>
			<?php do_action( 'woocommerce_review_order_after_shipping' ); ?>
		<?php endif; ?>
		<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
			<tr class="fee">
				<th><?php echo esc_html( $fee->name ); ?></th>
				<td><?php wc_cart_totals_fee_html( $fee ); ?></td>
			</tr>
		<?php endforeach; ?>
		<?php if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) : ?>
			<?php if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) : ?>
				<?php foreach ( WC()->cart->get_tax_totals() as $code => $tax ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited ?>
					<tr class="tax-rate tax-rate-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
						<th><?php echo esc_html( $tax->label ); ?></th>
						<td><?php echo wp_kses_post( $tax->formatted_amount ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr class="tax-total">
					<th><?php echo esc_html( WC()->countries->tax_or_vat() ); ?></th>
					<td><?php wc_cart_totals_taxes_total_html(); ?></td>
				</tr>
			<?php endif; ?>
		<?php endif; ?>
		<?php do_action( 'woocommerce_review_order_before_order_total' ); ?>
		<tr class="order-total">
			<th><?php esc_html_e( 'Total', 'commercebuild-velocity' ); ?></th>
			<td><?php wc_cart_totals_order_total_html(); ?></td>
		</tr>
		<?php do_action( 'woocommerce_review_order_after_order_total' ); ?>
	</tfoot>
</table>
