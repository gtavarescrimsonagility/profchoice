<?php
/**
 * Commerce cart table (classic shortcode).
 *
 * @package Commercebuild_Velocity
 * @version 11.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_before_cart' );

$button_class = wc_wp_theme_get_element_class_name( 'button' );
?>
<div class="cbv-dash-cart">
<form class="woocommerce-cart-form" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
	<?php do_action( 'woocommerce_before_cart_table' ); ?>
		<div class="cbv-dash-cart__table-wrap">
			<table class="shop_table shop_table_responsive cart woocommerce-cart-form__contents cbv-dash-cart__table" cellspacing="0">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Product', 'commercebuild-velocity' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Pack', 'commercebuild-velocity' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Price', 'commercebuild-velocity' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Qty', 'commercebuild-velocity' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Total', 'commercebuild-velocity' ); ?></th>
						<th scope="col"><span class="screen-reader-text"><?php esc_html_e( 'Remove', 'commercebuild-velocity' ); ?></span></th>
					</tr>
				</thead>
				<tbody>
					<?php do_action( 'woocommerce_before_cart_contents' ); ?>
					<?php
					foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
						$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
						$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );
						$visible    = apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key );

						if ( ! $_product instanceof WC_Product || ! $_product->exists() || $cart_item['quantity'] <= 0 || ! $visible ) {
							continue;
						}

						$product_name      = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );
						$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
						?>
						<tr class="woocommerce-cart-form__cart-item <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">
							<td
								class="product-name"
								role="rowheader"
								data-title="<?php esc_attr_e( 'Product', 'commercebuild-velocity' ); ?>"
							>
								<?php
								if ( ! $product_permalink ) {
									echo wp_kses_post( $product_name );
								} else {
									echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $_product->get_name() ), $cart_item, $cart_item_key ) );
								}
								do_action( 'woocommerce_after_cart_item_name', $cart_item, $cart_item_key );
								echo wp_kses_post( wc_get_formatted_cart_item_data( $cart_item ) );
								$cbv_is_backordered = 'onbackorder' === $_product->get_stock_status()
									|| $_product->is_on_backorder( $cart_item['quantity'] );
								if ( $_product->backorders_require_notification() && $_product->is_on_backorder( $cart_item['quantity'] ) ) {
									echo wp_kses_post( apply_filters( 'woocommerce_cart_item_backorder_notification', '<p class="backorder_notification">' . esc_html__( 'Available on backorder', 'commercebuild-velocity' ) . '</p>', $product_id ) );
								}
								if ( $cbv_is_backordered ) {
									echo '<span class="cbv-stock cbv-stock--onbackorder">';
									esc_html_e( 'On backorder', 'commercebuild-velocity' );
									echo '</span>';
								} elseif ( $_product->is_in_stock() ) {
									echo '<span class="cbv-stock cbv-stock--instock">' . esc_html__( 'In Stock', 'commercebuild-velocity' ) . '</span>';
								} else {
									echo '<span class="cbv-stock cbv-stock--outofstock">' . esc_html__( 'Out of Stock', 'commercebuild-velocity' ) . '</span>';
								}
								?>
							</td>
							<td
								class="product-pack"
								data-title="<?php esc_attr_e( 'Pack', 'commercebuild-velocity' ); ?>"
							>
								<?php echo esc_html( cbv_product_pack( $_product ) ); ?>
							</td>
							<td
								class="product-price"
								data-title="<?php esc_attr_e( 'Price', 'commercebuild-velocity' ); ?>"
							>
								<?php echo apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WC HTML. ?>
							</td>
							<td
								class="product-quantity"
								data-title="<?php esc_attr_e( 'Qty', 'commercebuild-velocity' ); ?>"
							>
								<?php
								if ( $_product->is_sold_individually() ) {
									$min_quantity = 1;
									$max_quantity = 1;
								} else {
									$min_quantity = 0;
									$max_quantity = $_product->get_max_purchase_quantity();
								}

								$product_quantity = woocommerce_quantity_input(
									array(
										'input_name'   => 'cart[' . $cart_item_key . '][qty]',
										'input_value'  => $cart_item['quantity'],
										'max_value'    => $max_quantity,
										'min_value'    => $min_quantity,
										'product_name' => $product_name,
									),
									$_product,
									false
								);
								?>
								<div class="cbv-dash-qty">
									<button type="button" class="cbv-dash-qty__btn" data-cbv-qty="-1" aria-label="<?php esc_attr_e( 'Decrease quantity', 'commercebuild-velocity' ); ?>">-</button>
									<?php echo apply_filters( 'woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WC HTML. ?>
									<button type="button" class="cbv-dash-qty__btn" data-cbv-qty="1" aria-label="<?php esc_attr_e( 'Increase quantity', 'commercebuild-velocity' ); ?>">+</button>
								</div>
							</td>
							<td
								class="product-subtotal"
								data-title="<?php esc_attr_e( 'Total', 'commercebuild-velocity' ); ?>"
							>
								<?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WC HTML. ?>
							</td>
							<td class="product-remove">
								<?php
								echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WC HTML.
									'woocommerce_cart_item_remove_link',
									sprintf(
										'<a role="button" href="%s" class="cbv-dash-cart__remove" aria-label="%s" data-product_id="%s" data-product_sku="%s">%s</a>',
										esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
										esc_attr( sprintf( /* translators: %s: product name. */ __( 'Remove %s from cart', 'commercebuild-velocity' ), wp_strip_all_tags( $product_name ) ) ),
										esc_attr( (string) $product_id ),
										esc_attr( $_product->get_sku() ),
										esc_html__( 'Remove', 'commercebuild-velocity' )
									),
									$cart_item_key
								);
								?>
							</td>
						</tr>
						<?php
					}
					?>
					<?php do_action( 'woocommerce_cart_contents' ); ?>
					<tr>
						<td colspan="6" class="actions">
							<?php if ( wc_coupons_enabled() ) : ?>
								<div class="coupon">
									<label for="coupon_code" class="screen-reader-text"><?php esc_html_e( 'Coupon', 'commercebuild-velocity' ); ?></label>
									<input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="<?php esc_attr_e( 'Promo code', 'commercebuild-velocity' ); ?>">
									<button type="submit" class="button<?php echo $button_class ? ' ' . esc_attr( $button_class ) : ''; ?>" name="apply_coupon" value="<?php esc_attr_e( 'Apply coupon', 'commercebuild-velocity' ); ?>"><?php esc_html_e( 'Apply', 'commercebuild-velocity' ); ?></button>
									<?php do_action( 'woocommerce_cart_coupon' ); ?>
								</div>
							<?php endif; ?>
							<button type="submit" class="button<?php echo $button_class ? ' ' . esc_attr( $button_class ) : ''; ?>" name="update_cart" value="<?php esc_attr_e( 'Update cart', 'commercebuild-velocity' ); ?>"><?php esc_html_e( 'Update cart', 'commercebuild-velocity' ); ?></button>
							<?php do_action( 'woocommerce_cart_actions' ); ?>
							<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
						</td>
					</tr>
					<?php do_action( 'woocommerce_after_cart_contents' ); ?>
				</tbody>
			</table>
		</div>
		<?php do_action( 'woocommerce_after_cart_table' ); ?>
	</form>
<?php do_action( 'woocommerce_before_cart_collaterals' ); ?>
	<div class="cart-collaterals">
		<?php
		/**
		 * Cart collaterals hook.
		 *
		 * @hooked woocommerce_cross_sell_display
		 * @hooked woocommerce_cart_totals - 10
		 */
		do_action( 'woocommerce_cart_collaterals' );
		?>
	</div>
</div>
<?php do_action( 'woocommerce_after_cart' ); ?>
