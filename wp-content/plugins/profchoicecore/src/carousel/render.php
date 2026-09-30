<?php
/**
 * Carousel: viewport, arrows, dots and a live region around the slides.
 *
 * Markup and accessibility adapted from rtCamp/rt-carousel (GPL-2.0-or-later).
 *
 * @package ProfChoiceCore
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner blocks (slides) HTML.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pc_count      = count( $block->inner_blocks );
$pc_loop       = ! empty( $attributes['loop'] );
$pc_transition = isset( $attributes['transition'] ) && 'slide' === $attributes['transition'] ? 'slide' : 'fade';
$pc_label      = ! empty( $attributes['ariaLabel'] ) ? $attributes['ariaLabel'] : __( 'Carousel', 'profchoicecore' );
$pc_controls   = $pc_count > 1;

$pc_context = array(
	'transition'          => $pc_transition,
	'loop'                => $pc_loop,
	'autoplay'            => ! empty( $attributes['autoplay'] ) ? array(
		'delay'             => max( 2000, (int) ( $attributes['autoplayDelay'] ?? 6000 ) ),
		'stopOnInteraction' => true,
		'stopOnMouseEnter'  => true,
		'stopOnFocusIn'     => true,
	) : false,
	'selectedIndex'       => 0,
	'slideCount'          => $pc_count,
	'canScrollPrev'       => $pc_loop,
	'canScrollNext'       => $pc_count > 1,
	'announcement'        => '',
	'shouldAnnounce'      => false,
	/* translators: 1: current slide number, 2: number of slides. Keep the {{placeholders}}. */
	'announcementPattern' => __( 'Slide {{currentSlide}} of {{totalSlides}}', 'profchoicecore' ),
);

// Label each slide and bind its active state.
$pc_slides = new WP_HTML_Tag_Processor( $content );
$pc_index  = 0;
while ( $pc_slides->next_tag( array( 'class_name' => 'wp-block-profchoice-carousel-slide' ) ) ) {
	$pc_slides->set_attribute(
		'aria-label',
		/* translators: 1: slide number, 2: number of slides. */
		sprintf( __( '%1$d of %2$d', 'profchoicecore' ), $pc_index + 1, $pc_count )
	);
	$pc_slides->set_attribute( 'data-wp-context', wp_json_encode( array( 'index' => $pc_index ) ) );
	$pc_slides->set_attribute( 'data-wp-class--is-active', 'state.isSlideActive' );
	$pc_slides->set_attribute( 'data-wp-bind--inert', '!state.isSlideActive' );
	$pc_slides->set_attribute( 'data-wp-bind--aria-hidden', '!state.isSlideActive' );
	if ( 0 === $pc_index ) {
		$pc_slides->add_class( 'is-active' );
	} else {
		$pc_slides->set_attribute( 'inert', true );
		$pc_slides->set_attribute( 'aria-hidden', 'true' );
	}
	++$pc_index;
}

$pc_wrapper = get_block_wrapper_attributes(
	array(
		'class'                => 'pc-carousel',
		'role'                 => 'region',
		'aria-roledescription' => __( 'carousel', 'profchoicecore' ),
		'aria-label'           => $pc_label,
		'data-transition'      => $pc_transition,
	)
);

$pc_arrow = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="17" height="17" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12.576 4.847a.95.95 0 0 0 0 1.339l4.867 4.867H4.27a.95.95 0 0 0-.949.945.95.95 0 0 0 .949.949h13.173l-4.867 4.867a.95.95 0 0 0 0 1.34.95.95 0 0 0 1.342 0l6.484-6.485a.95.95 0 0 0 0-1.338l-6.484-6.484a.95.95 0 0 0-1.342 0"/></svg>';
?>
<div
	<?php echo $pc_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	data-wp-interactive="profchoice/carousel"
	<?php echo wp_interactivity_data_wp_context( $pc_context ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	data-wp-init="callbacks.init"
	data-wp-on--keydown="actions.onKeydown"
>
	<div class="pc-carousel__viewport">
		<div class="pc-carousel__container">
			<?php echo $pc_slides->get_updated_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	</div>
	<?php if ( $pc_controls && ! empty( $attributes['showArrows'] ) ) : ?>
		<div class="pc-carousel__arrows">
			<button type="button" class="pc-carousel__arrow pc-carousel__arrow--prev" aria-label="<?php esc_attr_e( 'Previous slide', 'profchoicecore' ); ?>" data-wp-on--click="actions.prev" data-wp-bind--disabled="!state.canPrev"<?php echo $pc_loop ? '' : ' disabled'; ?>>
				<?php echo $pc_arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
			<button type="button" class="pc-carousel__arrow pc-carousel__arrow--next" aria-label="<?php esc_attr_e( 'Next slide', 'profchoicecore' ); ?>" data-wp-on--click="actions.next" data-wp-bind--disabled="!state.canNext">
				<?php echo $pc_arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
		</div>
	<?php endif; ?>
	<?php if ( $pc_controls && ! empty( $attributes['showDots'] ) ) : ?>
		<div class="pc-carousel__dots">
			<?php for ( $pc_i = 0; $pc_i < $pc_count; $pc_i++ ) : ?>
				<button
					type="button"
					class="pc-carousel__dot<?php echo 0 === $pc_i ? ' is-active' : ''; ?>"
					<?php /* translators: %d: slide number. */ ?>
					aria-label="<?php echo esc_attr( sprintf( __( 'Go to slide %d', 'profchoicecore' ), $pc_i + 1 ) ); ?>"
					<?php echo 0 === $pc_i ? 'aria-current="true"' : ''; ?>
					data-wp-context='<?php echo wp_json_encode( array( 'snap' => $pc_i ) ); ?>'
					data-wp-on--click="actions.goTo"
					data-wp-class--is-active="state.isDotActive"
					data-wp-bind--aria-current="state.isDotActive"
				></button>
			<?php endfor; ?>
		</div>
	<?php endif; ?>
	<div class="pc-carousel__status" role="status" aria-live="polite" aria-atomic="true" data-wp-text="context.announcement"></div>
</div>
