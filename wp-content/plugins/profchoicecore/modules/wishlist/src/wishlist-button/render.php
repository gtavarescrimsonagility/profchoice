<?php
/**
 * Wishlist heart: outline, or filled when the product is in a list.
 *
 * @package ProfChoiceCore
 *
 * @var array    $attributes Block attributes.
 * @var WP_Block $block      Block instance.
 */

use ProfChoiceCore\Wishlist\Wishlist;

if ( ! defined( 'ABSPATH' ) || ! function_exists( 'wc_get_product' ) ) {
	return;
}

$pc_product_id = ! empty( $attributes['productId'] ) ? absint( $attributes['productId'] ) : 0;
if ( ! $pc_product_id ) {
	$pc_product_id = isset( $block->context['postId'] ) ? absint( $block->context['postId'] ) : absint( get_the_ID() );
}
$pc_product = wc_get_product( $pc_product_id );
if ( ! $pc_product ) {
	return;
}

Wishlist::prepare();
Wishlist::need_dialog();

$pc_wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'pc-wishlist-button',
		'type'  => 'button',
	)
);
?>
<button
	<?php echo $pc_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	data-wp-interactive="profchoice/wishlist"
	<?php
	echo wp_interactivity_data_wp_context( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		array(
			'productId'   => $pc_product->get_id(),
			'productName' => $pc_product->get_name(),
		)
	);
	?>
	data-wp-on--click="actions.toggle"
	data-wp-class--is-saved="state.isSaved"
	data-wp-bind--aria-pressed="state.isSaved"
	data-wp-bind--aria-label="state.buttonLabel"
>
	<?php
	echo str_replace( '<svg ', '<svg data-wp-style--display="state.outlineDisplay" ', Wishlist::heart( false, 'pc-wishlist-button__outline' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo str_replace( '<svg ', '<svg data-wp-style--display="state.filledDisplay" ', Wishlist::heart( true, 'pc-wishlist-button__filled' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	?>
</button>
