<?php
/**
 * Carousel arrows: binds the two inner Button blocks to the carousel store.
 *
 * @package ProfChoiceCore
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner blocks HTML (core/buttons with two core/button).
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pc_buttons    = new WP_HTML_Tag_Processor( $content );
$pc_direction  = '';
$pc_directions = array(
	'prev' => array( 'actions.prev', '!state.canPrev' ),
	'next' => array( 'actions.next', '!state.canNext' ),
);
while ( $pc_buttons->next_tag() ) {
	if ( $pc_buttons->has_class( 'pc-carousel__arrow--prev' ) ) {
		$pc_direction = 'prev';
	} elseif ( $pc_buttons->has_class( 'pc-carousel__arrow--next' ) ) {
		$pc_direction = 'next';
	}

	if ( $pc_direction && in_array( $pc_buttons->get_tag(), array( 'BUTTON', 'A' ), true ) ) {
		$pc_buttons->set_attribute( 'data-wp-on--click', $pc_directions[ $pc_direction ][0] );
		$pc_buttons->set_attribute( 'data-wp-bind--disabled', $pc_directions[ $pc_direction ][1] );
		$pc_direction = '';
	}
}

$pc_wrapper = get_block_wrapper_attributes(
	array( 'class' => 'pc-carousel__controls' . ( ! empty( $attributes['overlay'] ) ? ' is-overlay' : '' ) )
);
?>
<div <?php echo $pc_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php echo $pc_buttons->get_updated_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
