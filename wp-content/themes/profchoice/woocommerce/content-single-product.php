<?php
/**
 * Product page content: breadcrumb, gallery and info, related products.
 *
 * @package profchoice
 * @version 3.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

/** This action is documented in woocommerce/templates/content-single-product.php */
do_action( 'woocommerce_before_single_product' );

if ( post_password_required() ) {
	echo get_the_password_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}
?>
<article id="product-<?php the_ID(); ?>" <?php wc_product_class( 'pdp', $product ); ?>>
	<?php
	get_template_part( 'template-parts/pdp/breadcrumb' );
	get_template_part( 'template-parts/pdp/main' );
	get_template_part( 'template-parts/pdp/related' );

	// Product structured data, which WooCommerce prints from the summary hook.
	if ( isset( WC()->structured_data ) ) {
		WC()->structured_data->generate_product_data();
	}
	?>
</article>
<?php
/** This action is documented in woocommerce/templates/content-single-product.php */
do_action( 'woocommerce_after_single_product' );
