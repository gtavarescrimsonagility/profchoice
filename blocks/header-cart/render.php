<?php
/**
 * Cart Link block render.
 *
 * @package Commercebuild_Velocity
 *
 * @var array $attributes Block attributes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wc_get_cart_url' ) ) {
	return;
}

$cbv_cart  = WC()->cart;
$cbv_count = $cbv_cart ? $cbv_cart->get_cart_contents_count() : 0;
$cbv_total = $cbv_cart ? $cbv_cart->get_cart_subtotal() : '';
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'cbv-hblock cbv-hblock--cart' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by WordPress core. ?>>
	<?php
	/**
	 * Fires inside the header-cart block wrapper, before its content.
	 *
	 * @since 0.8.0
	 *
	 * @param array $attributes Block attributes.
	 */
	do_action( 'cbv_block_header_cart_before', $attributes );
	?>
	<?php
	/**
	 * Filter the cart block's icon slug.
	 *
	 * @since 0.11.0
	 *
	 * @param string $icon       Icon slug ('' or 'none' renders no icon).
	 * @param array  $attributes Block attributes.
	 */
	$cbv_icon       = apply_filters( 'cbv_header_cart_icon', isset( $attributes['icon'] ) ? $attributes['icon'] : 'cart', $attributes );
	$cbv_show_label = ! isset( $attributes['showLabel'] ) || ! empty( $attributes['showLabel'] );
	$cbv_icon_svg   = ( $cbv_icon && 'none' !== $cbv_icon ) ? cbv_icon( $cbv_icon ) : '';
	?>
	<a class="cbv-hblock__link" href="<?php echo esc_url( wc_get_cart_url() ); ?>">
		<?php if ( '' !== $cbv_icon_svg ) : ?>
			<span class="cbv-hblock__icon">
				<?php echo wp_kses( $cbv_icon_svg, CBV_Icons::kses_allowed() ); ?>
				<?php if ( ! empty( $attributes['showCount'] ) ) : ?>
					<span class="cbv-header__cart-count cbv-header__cart-count--badge"><?php echo esc_html( $cbv_count ); ?></span>
				<?php endif; ?>
			</span>
		<?php endif; ?>
		<span class="cbv-hblock__label<?php echo $cbv_show_label ? '' : ' screen-reader-text'; ?>"><?php echo esc_html( $attributes['label'] ); ?></span>
		<?php if ( '' === $cbv_icon_svg && ! empty( $attributes['showCount'] ) ) : ?>
			<span class="cbv-header__cart-count"><?php echo esc_html( $cbv_count ); ?></span>
		<?php endif; ?>
		<?php if ( ! empty( $attributes['showTotal'] ) && '' !== $cbv_total ) : ?>
			<span class="cbv-hblock__total"><?php echo wp_kses_post( $cbv_total ); ?></span>
		<?php endif; ?>
	</a>
	<?php
	/**
	 * Fires inside the header-cart block wrapper, after its content.
	 *
	 * @since 0.8.0
	 *
	 * @param array $attributes Block attributes.
	 */
	do_action( 'cbv_block_header_cart_after', $attributes );
	?>
</div>
