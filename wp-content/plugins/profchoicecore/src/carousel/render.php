<?php
/**
 * Carousel: viewport with the slides, then the arrows/dots child blocks.
 *
 * Markup and accessibility adapted from rtCamp/rt-carousel (GPL-2.0-or-later).
 *
 * @package ProfChoiceCore
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner blocks HTML (unused: inner blocks are rendered one by one).
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pc_slides = array();
$pc_extras = array();
foreach ( $block->inner_blocks as $pc_inner ) {
	if ( 'profchoice/carousel-slide' === $pc_inner->name ) {
		$pc_slides[] = $pc_inner;
	} else {
		$pc_extras[] = $pc_inner;
	}
}

$pc_count      = count( $pc_slides );
$pc_loop       = ! empty( $attributes['loop'] );
$pc_transition = isset( $attributes['transition'] ) && 'fade' === $attributes['transition'] ? 'fade' : 'slide';
$pc_label      = ! empty( $attributes['ariaLabel'] ) ? $attributes['ariaLabel'] : __( 'Carousel', 'profchoicecore' );

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
	'snaps'               => array_map(
		static function ( $index ) {
			return array( 'index' => $index );
		},
		$pc_count ? range( 0, $pc_count - 1 ) : array()
	),
	'canScrollPrev'       => $pc_loop,
	'canScrollNext'       => $pc_count > 1,
	'announcement'        => '',
	'shouldAnnounce'      => false,
	/* translators: Keep the {{currentSlide}} and {{totalSlides}} placeholders. */
	'announcementPattern' => __( 'Slide {{currentSlide}} of {{totalSlides}}', 'profchoicecore' ),
	/* translators: %d: slide number. */
	'dotLabelPattern'     => __( 'Go to slide %d', 'profchoicecore' ),
);

/*
 * Derived state mirrors the getters in view.ts so server-side directive
 * processing renders the initial inert/is-active/aria attributes correctly.
 */
wp_interactivity_state(
	'profchoice/carousel',
	array(
		'canPrev'       => static function () {
			$context = wp_interactivity_get_context( 'profchoice/carousel' );
			return ! empty( $context['canScrollPrev'] );
		},
		'canNext'       => static function () {
			$context = wp_interactivity_get_context( 'profchoice/carousel' );
			return ! empty( $context['canScrollNext'] );
		},
		'isSlideActive' => static function () {
			$context = wp_interactivity_get_context( 'profchoice/carousel' );
			return isset( $context['index'] ) && $context['index'] === $context['selectedIndex'];
		},
		'isDotActive'   => static function () {
			$context = wp_interactivity_get_context( 'profchoice/carousel' );
			return isset( $context['snap']['index'] ) && $context['snap']['index'] === $context['selectedIndex'];
		},
		'dotLabel'      => static function () {
			$context = wp_interactivity_get_context( 'profchoice/carousel' );
			return sprintf( $context['dotLabelPattern'], ( $context['snap']['index'] ?? 0 ) + 1 );
		},
	)
);

// Slides: label them and bind their active state.
$pc_slides_html = '';
foreach ( $pc_slides as $pc_index => $pc_slide ) {
	$pc_tags = new WP_HTML_Tag_Processor( $pc_slide->render() );
	if ( $pc_tags->next_tag() ) {
		$pc_tags->set_attribute(
			'aria-label',
			/* translators: 1: slide number, 2: number of slides. */
			sprintf( __( '%1$d of %2$d', 'profchoicecore' ), $pc_index + 1, $pc_count )
		);
		$pc_tags->set_attribute( 'data-wp-context', wp_json_encode( array( 'index' => $pc_index ) ) );
		$pc_tags->set_attribute( 'data-wp-class--is-active', 'state.isSlideActive' );
		$pc_tags->set_attribute( 'data-wp-bind--inert', '!state.isSlideActive' );
		$pc_tags->set_attribute( 'data-wp-bind--aria-hidden', '!state.isSlideActive' );
	}
	$pc_slides_html .= $pc_tags->get_updated_html();
}

// Arrows and dots only make sense with two or more slides.
$pc_extras_html = '';
foreach ( $pc_extras as $pc_extra ) {
	if ( $pc_count < 2 && in_array( $pc_extra->name, array( 'profchoice/carousel-control-prev', 'profchoice/carousel-control-next', 'profchoice/carousel-dots' ), true ) ) {
		continue;
	}
	$pc_extras_html .= $pc_extra->render();
}

$pc_wrapper = get_block_wrapper_attributes(
	array(
		'class'                => 'pc-carousel',
		'role'                 => 'region',
		'aria-roledescription' => __( 'carousel', 'profchoicecore' ),
		'aria-label'           => $pc_label,
		'data-transition'      => $pc_transition,
		// Focusable from script (a click on a slide), for keyboard navigation.
		'tabindex'             => '-1',
	)
);
?>
<div
	<?php echo $pc_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	data-wp-interactive="profchoice/carousel"
	<?php echo wp_interactivity_data_wp_context( $pc_context ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	data-wp-init="callbacks.init"
	data-wp-on--keydown="actions.onKeydown"
	data-wp-on--pointerdown="actions.focusOnPointer"
>
	<div class="pc-carousel__viewport">
		<div class="pc-carousel__container">
			<?php echo $pc_slides_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	</div>
	<?php echo $pc_extras_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<div class="pc-carousel__status" role="status" aria-live="polite" aria-atomic="true" data-wp-text="context.announcement"></div>
</div>
