<?php
/**
 * Product Grid block render.
 *
 * @package Commercebuild_Velocity
 *
 * @var array $attributes Block attributes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wc_get_product' ) ) {
	return;
}

$cbv_ids = cbv_engine()->get_product_ids(
	array(
		'limit'    => $attributes['limit'],
		'category' => wp_parse_id_list( $attributes['categoryIds'] ),
		'include'  => wp_parse_id_list( $attributes['includeIds'] ),
		'featured' => $attributes['featured'],
		'on_sale'  => $attributes['onSale'],
		'orderby'  => $attributes['orderby'],
	)
);

if ( empty( $cbv_ids ) ) {
	return;
}

$cbv_grid = cbv_product_grid(
	$cbv_ids,
	array(
		'show_price'       => $attributes['showPrice'],
		'show_sku'         => $attributes['showSku'],
		'show_uom'         => $attributes['showUom'],
		'show_add_to_cart' => $attributes['showAddToCart'],
	),
	$attributes['columns']
);

if ( '' === $cbv_grid ) {
	return;
}
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'cbv-block-product-grid' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by WordPress core. ?>>
	<?php
	/**
	 * Fires inside the product-grid block wrapper, before its content.
	 *
	 * @since 0.3.0
	 *
	 * @param array $attributes Block attributes.
	 */
	do_action( 'cbv_block_product_grid_before', $attributes );
	?>
	<?php if ( '' !== $attributes['heading'] ) : ?>
		<h2 class="cbv-block__heading"><?php echo esc_html( $attributes['heading'] ); ?></h2>
	<?php endif; ?>
	<?php echo $cbv_grid; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped during card rendering. ?>
	<?php
	/**
	 * Fires inside the product-grid block wrapper, after its content.
	 *
	 * @since 0.3.0
	 *
	 * @param array $attributes Block attributes.
	 */
	do_action( 'cbv_block_product_grid_after', $attributes );
	?>
</section>
