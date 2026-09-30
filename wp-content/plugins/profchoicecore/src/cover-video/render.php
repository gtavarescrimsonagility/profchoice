<?php
/**
 * Cover video: a background video that starts on its poster, an overlay, and
 * the inner content with a Play button bound to the store.
 *
 * @package ProfChoiceCore
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner blocks HTML.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pc_labels = array(
	'playLabel'  => __( 'Play video', 'profchoicecore' ),
	'pauseLabel' => __( 'Pause video', 'profchoicecore' ),
);

// Derived state for server-side directive processing (see view.ts).
wp_interactivity_state(
	'profchoice/cover-video',
	array(
		'playLabel' => static function () {
			$context = wp_interactivity_get_context( 'profchoice/cover-video' );
			return empty( $context['isPlaying'] ) ? $context['playLabel'] : $context['pauseLabel'];
		},
	)
);

// Bind the Play button: the first button or link after `.pc-cover-video__play`.
$pc_inner = new WP_HTML_Tag_Processor( $content );
$pc_found = false;
while ( $pc_inner->next_tag() ) {
	if ( $pc_inner->has_class( 'pc-cover-video__play' ) ) {
		$pc_found = true;
	}
	if ( $pc_found && in_array( $pc_inner->get_tag(), array( 'BUTTON', 'A' ), true ) ) {
		$pc_inner->set_attribute( 'data-wp-on--click', 'actions.toggle' );
		$pc_inner->set_attribute( 'data-wp-bind--aria-pressed', 'context.isPlaying' );
		$pc_inner->set_attribute( 'data-wp-bind--aria-label', 'state.playLabel' );
		break;
	}
}

$pc_video_url  = isset( $attributes['videoUrl'] ) ? $attributes['videoUrl'] : '';
$pc_poster_url = isset( $attributes['posterUrl'] ) ? $attributes['posterUrl'] : '';
$pc_position   = '';
if ( isset( $attributes['focalPoint']['x'], $attributes['focalPoint']['y'] ) ) {
	$pc_position = sprintf(
		'object-position:%s%% %s%%',
		round( (float) $attributes['focalPoint']['x'] * 100 ),
		round( (float) $attributes['focalPoint']['y'] * 100 )
	);
}

$pc_overlay = sprintf( 'opacity:%s', max( 0, min( 100, (int) $attributes['dimRatio'] ) ) / 100 );
if ( ! empty( $attributes['overlayColor'] ) && ! preg_match( '/[;{}<>"\'\\\\]|url\s*\(/i', $attributes['overlayColor'] ) ) {
	$pc_overlay .= ';background-color:' . $attributes['overlayColor'];
}

$pc_wrapper = get_block_wrapper_attributes( array( 'class' => 'pc-cover-video' ) );
?>
<div
	<?php echo $pc_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	data-wp-interactive="profchoice/cover-video"
	<?php echo wp_interactivity_data_wp_context( array_merge( array( 'isPlaying' => false ), $pc_labels ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	data-wp-class--is-playing="context.isPlaying"
	data-wp-init="callbacks.init"
>
	<?php if ( $pc_video_url ) : ?>
		<video
			class="pc-cover-video__video"
			src="<?php echo esc_url( $pc_video_url ); ?>"
			<?php if ( $pc_poster_url ) : ?>
				poster="<?php echo esc_url( $pc_poster_url ); ?>"
			<?php endif; ?>
			<?php if ( $pc_position ) : ?>
				style="<?php echo esc_attr( $pc_position ); ?>"
			<?php endif; ?>
			muted
			playsinline
			preload="metadata"
			<?php echo ! empty( $attributes['loop'] ) ? 'loop' : ''; ?>
			aria-hidden="true"
		></video>
	<?php endif; ?>
	<span class="pc-cover-video__overlay" aria-hidden="true" style="<?php echo esc_attr( $pc_overlay ); ?>"></span>
	<div class="pc-cover-video__inner">
		<?php echo $pc_inner->get_updated_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
</div>
