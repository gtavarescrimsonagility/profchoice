<?php
/**
 * Related Products block render.
 *
 * @package Commercebuild_Velocity
 *
 * @var array    $attributes Block attributes.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wc_get_product' ) ) {
	return;
}

$cbv_product_id = isset( $block->context['postId'] ) ? absint( $block->context['postId'] ) : get_the_ID();

if ( ! $cbv_product_id || 'product' !== get_post_type( $cbv_product_id ) ) {
	return;
}

$cbv_ids = cbv_engine()->get_related_ids( $cbv_product_id, $attributes['limit'] );

if ( empty( $cbv_ids ) ) {
	return;
}

$cbv_grid = cbv_product_grid(
	$cbv_ids,
	array(
		'show_price'       => $attributes['showPrice'],
		'show_add_to_cart' => $attributes['showAddToCart'],
	),
	$attributes['columns']
);

if ( '' === $cbv_grid ) {
	return;
}
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'cbv-block-related-products' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by WordPress core. ?>>
	<?php
	/**
	 * Fires inside the related-products block wrapper, before its content.
	 *
	 * @since 0.3.0
	 *
	 * @param array $attributes Block attributes.
	 */
	do_action( 'cbv_block_related_products_before', $attributes );
	?>
	<?php if ( '' !== $attributes['heading'] ) : ?>
		<h2 class="cbv-block__heading"><?php echo esc_html( $attributes['heading'] ); ?></h2>
	<?php endif; ?>
	<?php echo $cbv_grid; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped during card rendering. ?>
	<?php
	/**
	 * Fires inside the related-products block wrapper, after its content.
	 *
	 * @since 0.3.0
	 *
	 * @param array $attributes Block attributes.
	 */
	do_action( 'cbv_block_related_products_after', $attributes );
	?>
</section>
