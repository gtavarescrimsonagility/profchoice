<?php
/**
 * Hero Slider block render.
 *
 * Edge cases handled server-side: slides without media are skipped; zero
 * usable slides renders nothing; a single image renders as a static banner.
 * The first slide's image is
 * eager-loaded with fetchpriority=high (it is usually the LCP element);
 * the rest are lazy. Videos are muted/inline, preload only metadata, and
 * start only after the view script has applied the user's motion preference.
 *
 * @package Commercebuild_Velocity
 *
 * @var array $attributes Block attributes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cbv_slides = array();

foreach ( (array) $attributes['slides'] as $cbv_slide ) {
	$cbv_slide = wp_parse_args(
		(array) $cbv_slide,
		array(
			'type'       => 'image',
			'mediaId'    => 0,
			'mediaUrl'   => '',
			'posterUrl'  => '',
			'heading'    => '',
			'text'       => '',
			'buttonText' => '',
			'buttonUrl'  => '',
			'overlay'    => 30,
		)
	);

	if ( ! $cbv_slide['mediaId'] && '' === $cbv_slide['mediaUrl'] ) {
		continue;
	}

	if ( 'image' === $cbv_slide['type'] && ! $cbv_slide['mediaId'] && function_exists( 'attachment_url_to_postid' ) ) {
		$cbv_slide['mediaId'] = attachment_url_to_postid( esc_url_raw( $cbv_slide['mediaUrl'] ) );
	}

	/**
	 * Filter the resolved media attachment id for a hero slide.
	 *
	 * This lets CDN or rewritten local URLs recover responsive attachment
	 * markup when WordPress cannot resolve the URL by itself.
	 *
	 * @since 0.11.7
	 *
	 * @param int   $media_id Resolved attachment id, or zero.
	 * @param array $cbv_slide Normalized slide data.
	 */
	$cbv_slide['mediaId'] = absint( apply_filters( 'cbv_hero_media_id', $cbv_slide['mediaId'], $cbv_slide ) );

	$cbv_slides[] = $cbv_slide;
}

/**
 * Filter the hero slides before rendering.
 *
 * @since 0.3.0
 *
 * @param array $cbv_slides Normalized slides.
 * @param array $attributes Block attributes.
 */
$cbv_slides = apply_filters( 'cbv_hero_slides', $cbv_slides, $attributes );

if ( empty( $cbv_slides ) ) {
	return;
}

$cbv_count      = count( $cbv_slides );
$cbv_is_slider  = $cbv_count > 1;
$cbv_autoplay   = $cbv_is_slider && ! empty( $attributes['autoplay'] );
$cbv_has_video  = ! empty(
	array_filter(
		$cbv_slides,
		static function ( $slide ) {
			return 'video' === $slide['type'];
		}
	)
);
$cbv_has_motion = $cbv_autoplay || $cbv_has_video;
$cbv_interval   = min( 30, max( 2, absint( $attributes['interval'] ) ) );
$cbv_min_height = min( 900, max( 200, absint( $attributes['minHeight'] ) ) );

$cbv_wrapper = get_block_wrapper_attributes(
	array(
		'class'                => 'cbv-hero' . ( $cbv_is_slider ? ' cbv-hero--slider' : '' ),
		'style'                => '--cbv-hero-min-h:' . $cbv_min_height . 'px',
		'data-autoplay'        => $cbv_autoplay ? '1' : '0',
		'data-interval'        => (string) $cbv_interval,
		'role'                 => 'region',
		'aria-roledescription' => 'carousel',
		'aria-label'           => __( 'Featured content', 'commercebuild-velocity' ),
	)
);
?>
<section <?php echo $cbv_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by WordPress core. ?>>
	<?php
	/**
	 * Fires inside the hero-slider block wrapper, before its content.
	 *
	 * @since 0.3.0
	 *
	 * @param array $attributes Block attributes.
	 */
	do_action( 'cbv_block_hero_slider_before', $attributes );
	?>
	<div class="cbv-hero__track">
		<?php foreach ( $cbv_slides as $cbv_index => $cbv_slide ) : ?>
			<div class="cbv-hero__slide<?php echo 0 === $cbv_index ? ' is-active' : ''; ?>"<?php echo $cbv_is_slider ? ' aria-hidden="' . ( 0 === $cbv_index ? 'false' : 'true' ) . '"' . ( 0 === $cbv_index ? '' : ' inert' ) : ''; ?>>
				<?php if ( 'video' === $cbv_slide['type'] ) : ?>
					<video
						class="cbv-hero__media"
						src="<?php echo esc_url( $cbv_slide['mediaUrl'] ); ?>"
						<?php echo $cbv_slide['posterUrl'] ? 'poster="' . esc_url( $cbv_slide['posterUrl'] ) . '"' : ''; ?>
						muted
						loop
						playsinline
						preload="metadata"
					></video>
				<?php else : ?>
					<?php
					$cbv_img_attrs = array(
						'class'    => 'cbv-hero__media',
						'loading'  => 0 === $cbv_index ? 'eager' : 'lazy',
						'decoding' => 'async',
					);

					if ( 0 === $cbv_index ) {
						$cbv_img_attrs['fetchpriority'] = 'high';
					}

					if ( $cbv_slide['mediaId'] ) {
						echo wp_get_attachment_image( absint( $cbv_slide['mediaId'] ), 'cbv-hero', false, $cbv_img_attrs );
					} else {
						printf(
							'<img class="cbv-hero__media" src="%s" alt="" loading="%s" decoding="async"%s>',
							esc_url( $cbv_slide['mediaUrl'] ),
							0 === $cbv_index ? 'eager' : 'lazy',
							0 === $cbv_index ? ' fetchpriority="high"' : ''
						);
					}
					?>
				<?php endif; ?>
				<div class="cbv-hero__overlay" style="opacity:<?php echo esc_attr( min( 80, max( 0, absint( $cbv_slide['overlay'] ) ) ) / 100 ); ?>"></div>
				<?php if ( '' !== $cbv_slide['heading'] || '' !== $cbv_slide['text'] || '' !== $cbv_slide['buttonText'] ) : ?>
					<div class="cbv-hero__content">
						<?php if ( '' !== $cbv_slide['heading'] ) : ?>
							<h2 class="cbv-hero__heading"><?php echo esc_html( $cbv_slide['heading'] ); ?></h2>
						<?php endif; ?>
						<?php if ( '' !== $cbv_slide['text'] ) : ?>
							<p class="cbv-hero__text"><?php echo esc_html( $cbv_slide['text'] ); ?></p>
						<?php endif; ?>
						<?php if ( '' !== $cbv_slide['buttonText'] && '' !== $cbv_slide['buttonUrl'] ) : ?>
							<a class="cbv-button cbv-hero__button" href="<?php echo esc_url( $cbv_slide['buttonUrl'] ); ?>"><?php echo esc_html( $cbv_slide['buttonText'] ); ?></a>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
	<?php if ( $cbv_has_motion ) : ?>
		<?php
		$cbv_pause_icon = wp_kses(
			cbv_icon(
				'pause',
				array(
					'size'         => 18,
					'class'        => 'cbv-hero__motion-glyph cbv-hero__motion-glyph--pause',
					'stroke_width' => 2,
				)
			),
			CBV_Icons::kses_allowed()
		);
		$cbv_play_icon  = wp_kses(
			cbv_icon(
				'play',
				array(
					'size'         => 18,
					'class'        => 'cbv-hero__motion-glyph cbv-hero__motion-glyph--play',
					'stroke_width' => 2,
				)
			),
			CBV_Icons::kses_allowed()
		);

		$cbv_motion_control_html = sprintf(
			'<button class="cbv-hero__motion-toggle" type="button" aria-pressed="false" data-pause-label="%1$s" data-play-label="%2$s"><span class="cbv-hero__motion-icon" aria-hidden="true">%3$s%4$s</span><span class="cbv-hero__motion-label">%1$s</span></button>',
			esc_attr__( 'Pause motion', 'commercebuild-velocity' ),
			esc_attr__( 'Play motion', 'commercebuild-velocity' ),
			$cbv_pause_icon,
			$cbv_play_icon
		);

		/**
		 * Filter the persistent hero motion control markup.
		 *
		 * @since 0.11.7
		 *
		 * @param string $cbv_motion_control_html Motion control HTML.
		 * @param array  $attributes              Block attributes.
		 * @param array  $cbv_slides              Normalized slides.
		 */
		echo apply_filters( 'cbv_hero_motion_control_html', $cbv_motion_control_html, $attributes, $cbv_slides ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from escaped values; filterable by design.
		?>
	<?php endif; ?>
	<?php if ( $cbv_is_slider && ! empty( $attributes['showArrows'] ) ) : ?>
		<button class="cbv-hero__arrow cbv-hero__arrow--prev" type="button" aria-label="<?php esc_attr_e( 'Previous slide', 'commercebuild-velocity' ); ?>"><?php cbv_the_icon( 'chevron_left', array( 'size' => 28 ) ); ?></button>
		<button class="cbv-hero__arrow cbv-hero__arrow--next" type="button" aria-label="<?php esc_attr_e( 'Next slide', 'commercebuild-velocity' ); ?>"><?php cbv_the_icon( 'chevron_right', array( 'size' => 28 ) ); ?></button>
	<?php endif; ?>
	<?php if ( $cbv_is_slider && ! empty( $attributes['showDots'] ) ) : ?>
		<div class="cbv-hero__dots" role="group" aria-label="<?php esc_attr_e( 'Choose a featured slide', 'commercebuild-velocity' ); ?>">
			<?php for ( $cbv_i = 0; $cbv_i < $cbv_count; $cbv_i++ ) : ?>
				<button
					class="cbv-hero__dot<?php echo 0 === $cbv_i ? ' is-active' : ''; ?>"
					type="button"
					data-slide="<?php echo esc_attr( $cbv_i ); ?>"
					aria-current="<?php echo 0 === $cbv_i ? 'true' : 'false'; ?>"
					aria-label="<?php echo esc_attr( sprintf( /* translators: %d: slide number. */ __( 'Go to slide %d', 'commercebuild-velocity' ), $cbv_i + 1 ) ); ?>"
				></button>
			<?php endfor; ?>
		</div>
	<?php endif; ?>
	<?php
	/**
	 * Fires inside the hero-slider block wrapper, after its content.
	 *
	 * @since 0.3.0
	 *
	 * @param array $attributes Block attributes.
	 */
	do_action( 'cbv_block_hero_slider_after', $attributes );
	?>
</section>
