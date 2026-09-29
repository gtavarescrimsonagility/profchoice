<?php
/**
 * Commerce checkout form (classic shortcode).
 *
 * @package Commercebuild_Velocity
 * @version 9.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_before_checkout_form', $checkout );

if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'commercebuild-velocity' ) ) );
	return;
}
?>
<ol class="cbv-checkout-steps">
	<li class="is-current">
		<span class="cbv-checkout-steps__num">1</span>
		<span class="cbv-checkout-steps__label"><?php esc_html_e( 'Shipping', 'commercebuild-velocity' ); ?></span>
	</li>
	<li>
		<span class="cbv-checkout-steps__num">2</span>
		<span class="cbv-checkout-steps__label"><?php esc_html_e( 'Review & Payments', 'commercebuild-velocity' ); ?></span>
	</li>
</ol>
<form name="checkout" method="post" class="checkout woocommerce-checkout cbv-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data" aria-label="<?php esc_attr_e( 'Checkout', 'commercebuild-velocity' ); ?>">
	<?php if ( $checkout->get_checkout_fields() ) : ?>
		<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>
		<div class="cbv-checkout__main" id="customer_details">
			<?php do_action( 'woocommerce_checkout_shipping' ); ?>
			<?php do_action( 'woocommerce_checkout_billing' ); ?>
			<?php cbv_the_checkout_shipping_methods(); ?>
			<?php
			if ( function_exists( 'woocommerce_checkout_payment' ) ) {
				woocommerce_checkout_payment();
			}
			?>
		</div>
		<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>
	<?php endif; ?>
	<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>
	<div class="cbv-checkout__sidebar">
		<h3 id="order_review_heading"><?php esc_html_e( 'Order Summary', 'commercebuild-velocity' ); ?></h3>
		<?php if ( function_exists( 'WC' ) && WC()->cart ) : ?>
			<p class="cbv-checkout-count">
				<?php
				$cbv_count = WC()->cart->get_cart_contents_count();
				echo esc_html(
					sprintf(
						/* translators: %s: item count. */
						_n( '%s item in Cart', '%s items in Cart', $cbv_count, 'commercebuild-velocity' ),
						number_format_i18n( $cbv_count )
					)
				);
				?>
			</p>
		<?php endif; ?>
		<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>
		<div id="order_review" class="woocommerce-checkout-review-order cbv-dash-summary">
			<?php do_action( 'woocommerce_checkout_order_review' ); ?>
		</div>
		<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
	</div>
</form>
<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
