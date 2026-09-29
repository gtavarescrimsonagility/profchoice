<?php
/**
 * Logged-in dashboard homepage.
 *
 * @package Commercebuild_Velocity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cbv_dash                = CBV_Chrome::dashboard_home();
$cbv_cart                = function_exists( 'WC' ) ? WC()->cart : null;
$cbv_promos_after_panels = isset( $cbv_dash['promo_order'] )
	&& 'after_panels' === $cbv_dash['promo_order'];
?>
<div class="cbv-home cbv-home--dashboard">
	<section class="cbv-dash-welcome">
		<div class="cbv-container cbv-dash-welcome__inner">
			<div>
				<h1><?php echo esc_html( $cbv_dash['heading'] ); ?></h1>
				<?php if ( ! empty( $cbv_dash['subheading'] ) ) : ?>
					<p><?php echo esc_html( $cbv_dash['subheading'] ); ?></p>
				<?php endif; ?>
			</div>
			<?php if ( ! empty( $cbv_dash['actions'] ) ) : ?>
				<div class="cbv-dash-welcome__actions">
					<?php foreach ( $cbv_dash['actions'] as $action ) : ?>
						<a class="cbv-button cbv-button--ghost" href="<?php echo esc_url( $action['url'] ); ?>">
							<?php
							if ( ! empty( $action['icon'] ) ) {
								cbv_the_icon( $action['icon'] );
							}
							echo esc_html( $action['label'] );
							?>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<section class="cbv-home-section">
		<div class="cbv-container">
			<?php if ( ! empty( $cbv_dash['search'] ) ) : ?>
				<div class="cbv-dash-search">
					<?php
					if ( function_exists( 'get_product_search_form' ) ) {
						get_product_search_form();
					} else {
						get_search_form();
					}
					?>
				</div>
			<?php endif; ?>

			<?php
			if ( is_search() && function_exists( 'woocommerce_product_loop' ) ) {
				echo '<div class="cbv-dash-results">';
				woocommerce_product_loop();
				echo '</div>';
			}
			?>

			<?php if ( $cbv_cart && ! $cbv_cart->is_empty() ) : ?>
				<div class="cbv-dash-cart">
					<div class="cbv-dash-cart__table-wrap">
						<table class="cbv-dash-cart__table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Product', 'commercebuild-velocity' ); ?></th>
									<th><?php esc_html_e( 'Pack', 'commercebuild-velocity' ); ?></th>
									<th><?php esc_html_e( 'Price', 'commercebuild-velocity' ); ?></th>
									<th><?php esc_html_e( 'Qty', 'commercebuild-velocity' ); ?></th>
									<th><?php esc_html_e( 'Total', 'commercebuild-velocity' ); ?></th>
									<th><span class="screen-reader-text"><?php esc_html_e( 'Remove', 'commercebuild-velocity' ); ?></span></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $cbv_cart->get_cart() as $cbv_item_key => $cbv_item ) : ?>
									<?php
									$cbv_product = $cbv_item['data'];
									if ( ! $cbv_product ) {
										continue;
									}
									?>
									<tr>
										<td data-title="<?php esc_attr_e( 'Product', 'commercebuild-velocity' ); ?>">
											<?php
											$cbv_is_backordered = 'onbackorder' === $cbv_product->get_stock_status()
												|| $cbv_product->is_on_backorder( (int) $cbv_item['quantity'] );
											?>
											<a href="<?php echo esc_url( $cbv_product->get_permalink() ); ?>"><?php echo esc_html( apply_filters( 'cbv_product_title', $cbv_product->get_name(), $cbv_product ) ); ?></a>
											<?php if ( $cbv_is_backordered ) : ?>
												<span class="cbv-stock cbv-stock--onbackorder">
													<?php esc_html_e( 'On backorder', 'commercebuild-velocity' ); ?>
												</span>
											<?php elseif ( $cbv_product->is_in_stock() ) : ?>
												<span class="cbv-stock cbv-stock--instock"><?php esc_html_e( 'In Stock', 'commercebuild-velocity' ); ?></span>
											<?php else : ?>
												<span class="cbv-stock cbv-stock--outofstock"><?php esc_html_e( 'Out of Stock', 'commercebuild-velocity' ); ?></span>
											<?php endif; ?>
										</td>
										<td data-title="<?php esc_attr_e( 'Pack', 'commercebuild-velocity' ); ?>">
											<?php echo esc_html( cbv_product_pack( $cbv_product ) ); ?>
										</td>
										<td data-title="<?php esc_attr_e( 'Price', 'commercebuild-velocity' ); ?>">
											<?php
											echo wp_kses_post(
												apply_filters(
													'woocommerce_cart_item_price',
													$cbv_cart->get_product_price( $cbv_product ),
													$cbv_item,
													$cbv_item_key
												)
											);
											?>
										</td>
										<td data-title="<?php esc_attr_e( 'Qty', 'commercebuild-velocity' ); ?>">
											<?php cbv_the_dash_qty( $cbv_item_key, (int) $cbv_item['quantity'] ); ?>
										</td>
										<td data-title="<?php esc_attr_e( 'Total', 'commercebuild-velocity' ); ?>">
											<?php
											echo wp_kses_post(
												apply_filters(
													'woocommerce_cart_item_subtotal',
													$cbv_cart->get_product_subtotal(
														$cbv_product,
														$cbv_item['quantity']
													),
													$cbv_item,
													$cbv_item_key
												)
											);
											?>
										</td>
										<td data-title="<?php esc_attr_e( 'Remove', 'commercebuild-velocity' ); ?>">
											<a class="cbv-dash-cart__remove" href="<?php echo esc_url( wc_get_cart_remove_url( $cbv_item_key ) ); ?>"><?php esc_html_e( 'Remove', 'commercebuild-velocity' ); ?></a>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<aside class="cbv-dash-summary">
						<h2><?php esc_html_e( 'Order Summary', 'commercebuild-velocity' ); ?></h2>
						<p class="cbv-dash-summary__line">
							<span><?php esc_html_e( 'Subtotal', 'commercebuild-velocity' ); ?></span>
							<span><?php echo wp_kses_post( $cbv_cart->get_cart_subtotal() ); ?></span>
						</p>
						<?php foreach ( $cbv_cart->get_coupons() as $cbv_code => $cbv_coupon ) : ?>
							<?php
							$cbv_coupon_class = 'cbv-dash-summary__line cbv-dash-summary__line--discount coupon-'
								. sanitize_title( $cbv_code );
							?>
							<p class="<?php echo esc_attr( $cbv_coupon_class ); ?>">
								<span><?php wc_cart_totals_coupon_label( $cbv_coupon ); ?></span>
								<span><?php wc_cart_totals_coupon_html( $cbv_coupon ); ?></span>
							</p>
						<?php endforeach; ?>
						<?php if ( $cbv_cart->needs_shipping() && $cbv_cart->show_shipping() ) : ?>
							<p class="cbv-dash-summary__line cbv-dash-summary__line--shipping">
								<span><?php esc_html_e( 'Shipping', 'commercebuild-velocity' ); ?></span>
								<span><?php echo wp_kses_post( $cbv_cart->get_cart_shipping_total() ); ?></span>
							</p>
						<?php endif; ?>
						<?php foreach ( $cbv_cart->get_fees() as $cbv_fee ) : ?>
							<p class="cbv-dash-summary__line cbv-dash-summary__line--fee">
								<span><?php echo esc_html( $cbv_fee->name ); ?></span>
								<span><?php wc_cart_totals_fee_html( $cbv_fee ); ?></span>
							</p>
						<?php endforeach; ?>
						<?php if ( wc_tax_enabled() && ! $cbv_cart->display_prices_including_tax() ) : ?>
							<?php if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) : ?>
								<?php foreach ( $cbv_cart->get_tax_totals() as $cbv_tax_code => $cbv_tax ) : ?>
									<?php
									$cbv_tax_class = 'cbv-dash-summary__line cbv-dash-summary__line--tax tax-rate-'
										. sanitize_title( $cbv_tax_code );
									?>
									<p class="<?php echo esc_attr( $cbv_tax_class ); ?>">
										<span><?php echo esc_html( $cbv_tax->label ); ?></span>
										<span><?php echo wp_kses_post( $cbv_tax->formatted_amount ); ?></span>
									</p>
								<?php endforeach; ?>
							<?php else : ?>
								<p class="cbv-dash-summary__line cbv-dash-summary__line--tax">
									<span><?php echo esc_html( WC()->countries->tax_or_vat() ); ?></span>
									<span><?php wc_cart_totals_taxes_total_html(); ?></span>
								</p>
							<?php endif; ?>
						<?php endif; ?>
						<p class="cbv-dash-summary__line cbv-dash-summary__total">
							<span><?php esc_html_e( 'Total', 'commercebuild-velocity' ); ?></span>
							<span>
								<?php
								echo wp_kses_post(
									apply_filters( 'woocommerce_cart_totals_order_total_html', $cbv_cart->get_total() )
								);
								?>
							</span>
						</p>
						<a class="cbv-button" href="<?php echo esc_url( wc_get_checkout_url() ); ?>"><?php esc_html_e( 'Proceed to Checkout', 'commercebuild-velocity' ); ?></a>
					</aside>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<?php if ( ! $cbv_promos_after_panels ) : ?>
		<?php cbv_the_dashboard_promos( $cbv_dash ); ?>
	<?php endif; ?>

	<?php if ( ! empty( $cbv_dash['orders'] ) || ! empty( $cbv_dash['guide']['heading'] ) ) : ?>
		<section class="cbv-home-section cbv-dash-panels-section">
			<div class="cbv-container cbv-dash-panels">
				<div class="cbv-dash-panel">
					<div class="cbv-home-section__head">
						<h2 class="cbv-block__heading"><?php esc_html_e( 'Recent Orders', 'commercebuild-velocity' ); ?></h2>
						<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
							<a class="cbv-home-section__link" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"><?php esc_html_e( 'Full Order History', 'commercebuild-velocity' ); ?></a>
						<?php endif; ?>
					</div>
					<?php if ( empty( $cbv_dash['orders'] ) ) : ?>
						<p class="cbv-dash-panel__empty"><?php esc_html_e( 'No recent orders yet.', 'commercebuild-velocity' ); ?></p>
					<?php else : ?>
						<ul class="cbv-dash-orders">
							<?php foreach ( $cbv_dash['orders'] as $cbv_order ) : ?>
								<li class="cbv-dash-orders__row">
									<div>
										<strong><?php echo esc_html( sprintf( /* translators: %s: order number. */ __( 'Order #%s', 'commercebuild-velocity' ), $cbv_order['number'] ) ); ?></strong>
										<span class="cbv-dash-orders__status cbv-dash-orders__status--<?php echo esc_attr( sanitize_html_class( $cbv_order['status_slug'] ) ); ?>"><?php echo esc_html( $cbv_order['status'] ); ?></span>
										<span class="cbv-dash-orders__meta"><?php echo esc_html( $cbv_order['date'] ); ?></span>
									</div>
									<div class="cbv-dash-orders__actions">
										<span class="cbv-dash-orders__total"><?php echo wp_kses_post( $cbv_order['total'] ); ?></span>
										<?php if ( ! empty( $cbv_order['url'] ) ) : ?>
											<a href="<?php echo esc_url( $cbv_order['url'] ); ?>"><?php esc_html_e( 'View Order', 'commercebuild-velocity' ); ?></a>
										<?php endif; ?>
										<?php if ( ! empty( $cbv_order['reorder_url'] ) ) : ?>
											<a href="<?php echo esc_url( $cbv_order['reorder_url'] ); ?>"><?php esc_html_e( 'Reorder', 'commercebuild-velocity' ); ?></a>
										<?php endif; ?>
									</div>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
				<div class="cbv-dash-panel">
					<div class="cbv-home-section__head">
						<h2 class="cbv-block__heading"><?php echo esc_html( $cbv_dash['guide']['heading'] ); ?></h2>
						<?php if ( ! empty( $cbv_dash['guide']['link'] ) ) : ?>
							<a class="cbv-home-section__link" href="<?php echo esc_url( $cbv_dash['guide']['link'] ); ?>"><?php esc_html_e( 'Manage Guides', 'commercebuild-velocity' ); ?></a>
						<?php endif; ?>
					</div>
					<?php if ( empty( $cbv_dash['guide']['items'] ) ) : ?>
						<p class="cbv-dash-panel__empty"><?php esc_html_e( 'No saved order guide yet.', 'commercebuild-velocity' ); ?></p>
					<?php else : ?>
						<ul class="cbv-dash-guide">
							<?php foreach ( $cbv_dash['guide']['items'] as $cbv_guide_item ) : ?>
								<li class="cbv-dash-guide__row">
									<span><?php echo esc_html( $cbv_guide_item['name'] ); ?></span>
									<?php if ( ! empty( $cbv_guide_item['pack'] ) ) : ?>
										<span class="cbv-dash-guide__pack"><?php echo esc_html( $cbv_guide_item['pack'] ); ?></span>
									<?php endif; ?>
									<?php if ( ! empty( $cbv_guide_item['price'] ) ) : ?>
										<span class="cbv-dash-guide__price"><?php echo wp_kses_post( $cbv_guide_item['price'] ); ?></span>
									<?php endif; ?>
									<?php if ( ! empty( $cbv_guide_item['url'] ) ) : ?>
										<a href="<?php echo esc_url( $cbv_guide_item['url'] ); ?>"><?php esc_html_e( 'Add', 'commercebuild-velocity' ); ?></a>
									<?php endif; ?>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $cbv_promos_after_panels ) : ?>
		<?php cbv_the_dashboard_promos( $cbv_dash ); ?>
	<?php endif; ?>

	<section class="cbv-home-section">
		<div class="cbv-container">
			<div class="cbv-home-section__head">
				<div>
					<?php if ( ! empty( $cbv_dash['categories']['kicker'] ) ) : ?>
						<p class="cbv-kicker"><?php echo esc_html( $cbv_dash['categories']['kicker'] ); ?></p>
					<?php endif; ?>
					<h2 class="cbv-block__heading"><?php echo esc_html( ! empty( $cbv_dash['categories']['heading'] ) ? $cbv_dash['categories']['heading'] : __( 'Shop by Category', 'commercebuild-velocity' ) ); ?></h2>
					<?php if ( ! empty( $cbv_dash['categories']['text'] ) ) : ?>
						<p class="cbv-home-section__text"><?php echo esc_html( $cbv_dash['categories']['text'] ); ?></p>
					<?php endif; ?>
				</div>
				<?php if ( ! empty( $cbv_dash['categories']['link'] ) ) : ?>
					<a class="cbv-home-section__link" href="<?php echo esc_url( $cbv_dash['categories']['link'] ); ?>"><?php esc_html_e( 'View Full Catalogue', 'commercebuild-velocity' ); ?></a>
				<?php endif; ?>
			</div>
			<?php
			if ( function_exists( 'cbv_engine' ) && taxonomy_exists( 'product_cat' ) ) {
				$cbv_tiles = apply_filters( 'cbv_home_category_tiles', cbv_engine()->get_category_tiles( array( 'limit' => 16 ) ) );
				cbv_the_home_category_tiles( $cbv_tiles );
			}
			?>
		</div>
	</section>
</div>
