<?php
/**
 * Hot Deals block render.
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

$cbv_ids = cbv_engine()->get_hot_deal_ids(
	$attributes['source'],
	$attributes['limit'],
	wp_parse_id_list( $attributes['includeIds'] )
);

if ( empty( $cbv_ids ) ) {
	return;
}

$cbv_grid = cbv_product_grid(
	$cbv_ids,
	array(
		'show_price'       => $attributes['showPrice'],
		'show_sku'         => $attributes['showSku'],
		'show_add_to_cart' => $attributes['showAddToCart'],
	),
	$attributes['columns']
);

if ( '' === $cbv_grid ) {
	return;
}
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'cbv-block-hot-deals' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by WordPress core. ?>>
	<?php
	/**
	 * Fires inside the hot-deals block wrapper, before its content.
	 *
	 * @since 0.3.0
	 *
	 * @param array $attributes Block attributes.
	 */
	do_action( 'cbv_block_hot_deals_before', $attributes );
	?>
	<?php if ( '' !== $attributes['heading'] ) : ?>
		<h2 class="cbv-block__heading cbv-block__heading--deals"><?php echo esc_html( $attributes['heading'] ); ?></h2>
	<?php endif; ?>
	<?php echo $cbv_grid; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped during card rendering. ?>
	<?php
	/**
	 * Fires inside the hot-deals block wrapper, after its content.
	 *
	 * @since 0.3.0
	 *
	 * @param array $attributes Block attributes.
	 */
	do_action( 'cbv_block_hot_deals_after', $attributes );
	?>
</section>
