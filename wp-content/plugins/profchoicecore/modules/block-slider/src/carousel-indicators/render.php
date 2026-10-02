<?php
/**
 * Carousel indicators: one button per slide, rendered from the carousel context.
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

// Empty active values inherit the default ones (see style.scss).
$pc_states = ProfChoiceCore\StateStyle::props(
	'carousel-indicator',
	array(
		'default' => $attributes['default'] ?? array(),
		'active'  => $attributes['active'] ?? array(),
	)
);

$pc_wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'pc-carousel-indicators' . ( ! empty( $attributes['overlay'] ) ? ' is-overlay' : '' ),
	)
);
ob_start();
?>
<div <?php echo $pc_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<template data-wp-each--snap="context.snaps">
		<button
			type="button"
			class="pc-carousel-indicator"
			data-wp-on--click="actions.goTo"
			data-wp-class--is-active="state.isDotActive"
			data-wp-bind--aria-current="state.isDotActive"
			data-wp-bind--aria-label="state.dotLabel"
		></button>
	</template>
</div>
<?php
echo ProfChoiceCore\StateStyle::apply( (string) ob_get_clean(), $pc_states['style'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
