<?php
/**
 * Product Search block render.
 *
 * @package Commercebuild_Velocity
 *
 * @var array $attributes Block attributes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'cbv-hblock cbv-hblock--search' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by WordPress core. ?>>
	<?php
	/**
	 * Fires inside the header-search block wrapper, before its content.
	 *
	 * @since 0.8.0
	 *
	 * @param array $attributes Block attributes.
	 */
	do_action( 'cbv_block_header_search_before', $attributes );
	?>
	<?php
	$cbv_field_icon  = ! isset( $attributes['showFieldIcon'] ) || ! empty( $attributes['showFieldIcon'] );
	$cbv_icon_button = isset( $attributes['buttonStyle'] ) && 'icon' === $attributes['buttonStyle'];
	$cbv_search_id   = wp_unique_id( 'cbv-hsearch-' );
	?>
	<form role="search" method="get" class="cbv-hblock__form<?php echo $cbv_field_icon ? ' cbv-hblock__form--icon' : ''; ?>" action="<?php echo esc_url( home_url( '/' ) ); ?>">
		<label class="screen-reader-text" for="<?php echo esc_attr( $cbv_search_id ); ?>"><?php esc_html_e( 'Search products', 'commercebuild-velocity' ); ?></label>
		<span class="cbv-hblock__field">
			<?php if ( $cbv_field_icon ) : ?>
				<span class="cbv-hblock__field-icon">
					<?php
					cbv_the_icon(
						'search',
						array(
							'size'         => 20,
							'stroke_width' => 1.5,
						)
					);
					?>
				</span>
			<?php endif; ?>
			<input
				type="search"
				id="<?php echo esc_attr( $cbv_search_id ); ?>"
				name="s"
				value="<?php echo esc_attr( get_search_query() ); ?>"
				placeholder="<?php echo esc_attr( $attributes['placeholder'] ); ?>"
			>
		</span>
		<input type="hidden" name="post_type" value="product">
		<?php if ( ! empty( $attributes['showButton'] ) ) : ?>
			<?php if ( $cbv_icon_button ) : ?>
				<button type="submit" class="cbv-hblock__submit cbv-hblock__submit--icon" aria-label="<?php echo esc_attr( $attributes['buttonText'] ); ?>">
					<?php cbv_the_icon( 'search', array( 'size' => 20 ) ); ?>
				</button>
			<?php else : ?>
				<button type="submit" class="cbv-hblock__submit"><?php echo esc_html( $attributes['buttonText'] ); ?></button>
			<?php endif; ?>
		<?php endif; ?>
	</form>
	<?php
	/**
	 * Fires inside the header-search block wrapper, after its content.
	 *
	 * @since 0.8.0
	 *
	 * @param array $attributes Block attributes.
	 */
	do_action( 'cbv_block_header_search_after', $attributes );
	?>
</div>
