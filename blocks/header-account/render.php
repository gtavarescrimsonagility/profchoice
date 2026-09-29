<?php
/**
 * Account Link block render.
 *
 * @package Commercebuild_Velocity
 *
 * @var array $attributes Block attributes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wc_get_page_permalink' ) ) {
	return;
}

$cbv_label = $attributes['label'];

if ( ! empty( $attributes['showGreeting'] ) && is_user_logged_in() ) {
	$cbv_user = wp_get_current_user();
	/* translators: %s: customer first name or display name. */
	$cbv_label = sprintf( __( 'Hi, %s', 'commercebuild-velocity' ), $cbv_user->first_name ? $cbv_user->first_name : $cbv_user->display_name );
}
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'cbv-hblock cbv-hblock--account' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by WordPress core. ?>>
	<?php
	/**
	 * Fires inside the header-account block wrapper, before its content.
	 *
	 * @since 0.8.0
	 *
	 * @param array $attributes Block attributes.
	 */
	do_action( 'cbv_block_header_account_before', $attributes );
	?>
	<?php
	/**
	 * Filter the account block's icon slug.
	 *
	 * @since 0.11.0
	 *
	 * @param string $icon       Icon slug ('' or 'none' renders no icon).
	 * @param array  $attributes Block attributes.
	 */
	$cbv_icon       = apply_filters( 'cbv_header_account_icon', isset( $attributes['icon'] ) ? $attributes['icon'] : 'user', $attributes );
	$cbv_show_label = ! isset( $attributes['showLabel'] ) || ! empty( $attributes['showLabel'] );
	$cbv_icon_svg   = ( $cbv_icon && 'none' !== $cbv_icon ) ? cbv_icon( $cbv_icon ) : '';
	?>
	<a class="cbv-hblock__link" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">
		<?php if ( '' !== $cbv_icon_svg ) : ?>
			<span class="cbv-hblock__icon"><?php echo wp_kses( $cbv_icon_svg, CBV_Icons::kses_allowed() ); ?></span>
		<?php endif; ?>
		<span class="cbv-hblock__label<?php echo $cbv_show_label ? '' : ' screen-reader-text'; ?>"><?php echo esc_html( $cbv_label ); ?></span>
	</a>
	<?php
	/**
	 * Fires inside the header-account block wrapper, after its content.
	 *
	 * @since 0.8.0
	 *
	 * @param array $attributes Block attributes.
	 */
	do_action( 'cbv_block_header_account_after', $attributes );
	?>
</div>
