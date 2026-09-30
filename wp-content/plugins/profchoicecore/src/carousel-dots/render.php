<?php
/**
 * Carousel dots: one button per slide, rendered from the carousel context.
 *
 * @package ProfChoiceCore
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Unused.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pc_style = static function ( $state ) use ( $attributes ) {
	$defaults = WP_Block_Type_Registry::get_instance()->get_registered( 'profchoice/carousel-dots' )->attributes[ $state ]['default'];
	return wp_parse_args( isset( $attributes[ $state ] ) ? (array) $attributes[ $state ] : array(), $defaults );
};
$pc_inactive = $pc_style( 'inactive' );
$pc_active   = $pc_style( 'active' );

// Keep in sync with vars.ts.
$pc_vars = array(
	'--pc-carousel-dot-color'         => $pc_inactive['color'],
	'--pc-carousel-dot-border'        => $pc_inactive['borderWidth'] . ' solid ' . $pc_inactive['borderColor'],
	'--pc-carousel-dot-width'         => $pc_inactive['width'],
	'--pc-carousel-dot-height'        => $pc_inactive['height'],
	'--pc-carousel-dot-radius'        => $pc_inactive['radius'],
	'--pc-carousel-dot-active-color'  => $pc_active['color'],
	'--pc-carousel-dot-active-border' => $pc_active['borderWidth'] . ' solid ' . $pc_active['borderColor'],
	'--pc-carousel-dot-active-width'  => $pc_active['width'],
	'--pc-carousel-dot-active-height' => $pc_active['height'],
	'--pc-carousel-dot-active-radius' => $pc_active['radius'],
);
$pc_css  = '';
foreach ( $pc_vars as $pc_name => $pc_value ) {
	// Values end up in a style attribute: reject anything that could break out.
	if ( '' !== trim( $pc_value ) && ! preg_match( '/[;{}<>"\'\\\\]|url\s*\(|expression/i', $pc_value ) ) {
		$pc_css .= $pc_name . ':' . $pc_value . ';';
	}
}

$pc_wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'pc-carousel__dots' . ( ! empty( $attributes['overlay'] ) ? ' is-overlay' : '' ),
		'style' => $pc_css,
	)
);
?>
<div <?php echo $pc_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<template data-wp-each--snap="context.snaps">
		<button
			type="button"
			class="pc-carousel__dot"
			data-wp-on--click="actions.goTo"
			data-wp-class--is-active="state.isDotActive"
			data-wp-bind--aria-current="state.isDotActive"
			data-wp-bind--aria-label="state.dotLabel"
		></button>
	</template>
</div>
