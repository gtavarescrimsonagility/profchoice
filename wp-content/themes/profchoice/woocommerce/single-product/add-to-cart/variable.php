<?php
/**
 * Variable product add to cart: one profchoice/swatches block per attribute
 * (through wc_dropdown_variation_attribute_options(), which keeps the
 * select WooCommerce's script reads), then the purchase row.
 *
 * @package profchoice
 * @version 11.1.0
 *
 * @var array      $available_variations Variations, or false when loaded by AJAX.
 * @var array      $attributes           Attribute name => options.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

$pdp_variations_json = wp_json_encode( $available_variations );
$pdp_variations_attr = function_exists( 'wc_esc_json' ) ? wc_esc_json( $pdp_variations_json ) : _wp_specialchars( $pdp_variations_json, ENT_QUOTES, 'UTF-8', true );

/** This action is documented in woocommerce/templates/single-product/add-to-cart/variable.php */
do_action( 'woocommerce_before_add_to_cart_form' );
?>
<form class="variations_form pdp-main__form" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype="multipart/form-data" data-product_id="<?php echo absint( $product->get_id() ); ?>" data-product_variations="<?php echo $pdp_variations_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
	<?php do_action( 'woocommerce_before_variations_form' ); ?>

	<?php if ( empty( $available_variations ) && false !== $available_variations ) : ?>
		<p class="stock out-of-stock"><?php echo esc_html( apply_filters( 'woocommerce_out_of_stock_message', __( 'This product is currently out of stock and unavailable.', 'woocommerce' ) ) ); ?></p>
	<?php else : ?>
		<div class="variations">
			<?php
			foreach ( $attributes as $pdp_attribute => $pdp_options ) {
				wc_dropdown_variation_attribute_options(
					array(
						'options'             => $pdp_options,
						'attribute'           => $pdp_attribute,
						'product'             => $product,
						'profchoice_swatches' => array(
							'className'    => 'pdp-option',
							'sizeGuideUrl' => 'pa_size' === $pdp_attribute ? '#size-guide' : '',
						),
					)
				);
			}
			?>
		</div>
		<div class="reset_variations_alert pdp-visually-hidden" role="alert" aria-live="polite" aria-relevant="all"></div>
		<?php do_action( 'woocommerce_after_variations_table' ); ?>

		<div class="single_variation_wrap">
			<?php
			/** This action is documented in woocommerce/templates/single-product/add-to-cart/variable.php */
			do_action( 'woocommerce_before_single_variation' );
			/** This action is documented in woocommerce/templates/single-product/add-to-cart/variable.php */
			do_action( 'woocommerce_single_variation' );
			/** This action is documented in woocommerce/templates/single-product/add-to-cart/variable.php */
			do_action( 'woocommerce_after_single_variation' );
			?>
		</div>
	<?php endif; ?>

	<?php do_action( 'woocommerce_after_variations_form' ); ?>
</form>
<?php
do_action( 'woocommerce_after_add_to_cart_form' );
