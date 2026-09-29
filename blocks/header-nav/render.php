<?php
/**
 * Menu block render.
 *
 * @package Commercebuild_Velocity
 *
 * @var array $attributes Block attributes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cbv_location = sanitize_key( $attributes['location'] );

if ( ! has_nav_menu( $cbv_location ) ) {
	return;
}

// The wrapper attributes (alignment, per-block custom CSS, generated
// classes) go on a neutral outer div — matching the editor canvas, where
// block styles land on a transparent wrapper around the rendered nav — so
// user CSS produces the same box model on both sides. The visual styling
// (green background) stays on the inner nav.
$cbv_classes = 'cbv-hblock cbv-hblock--nav';

if ( 'vertical' === $attributes['orientation'] ) {
	$cbv_classes .= ' cbv-hblock--nav-vertical';
}
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => $cbv_classes ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by WordPress core. ?>>
<nav class="cbv-nav" aria-label="<?php echo esc_attr( $cbv_location ); ?>">
	<?php
	/**
	 * Fires inside the menu block wrapper, before its content.
	 *
	 * @since 0.8.0
	 *
	 * @param array $attributes Block attributes.
	 */
	do_action( 'cbv_block_header_nav_before', $attributes );

	wp_nav_menu(
		array(
			'theme_location' => $cbv_location,
			'menu_class'     => 'cbv-nav__list',
			'container'      => false,
			'depth'          => min( 4, max( 1, absint( $attributes['depth'] ) ) ),
		)
	);

	/**
	 * Fires inside the menu block wrapper, after its content.
	 *
	 * @since 0.8.0
	 *
	 * @param array $attributes Block attributes.
	 */
	do_action( 'cbv_block_header_nav_after', $attributes );
	?>
</nav>
</div>
