<?php
/**
 * Trade-account CTA band.
 *
 * @package Commercebuild_Velocity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $cbv_cta ) || ! is_array( $cbv_cta ) || empty( $cbv_cta['heading'] ) ) {
	return;
}
?>
<section class="cbv-cta-band">
	<div class="cbv-container cbv-cta-band__inner">
		<div>
			<h2><?php echo esc_html( $cbv_cta['heading'] ); ?></h2>
			<?php if ( ! empty( $cbv_cta['text'] ) ) : ?>
				<p><?php echo esc_html( $cbv_cta['text'] ); ?></p>
			<?php endif; ?>
			<?php if ( ! empty( $cbv_cta['checks'] ) ) : ?>
				<ul class="cbv-cta-band__checks">
					<?php foreach ( $cbv_cta['checks'] as $check ) : ?>
						<li><?php echo esc_html( $check ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php if ( ! empty( $cbv_cta['buttons'] ) ) : ?>
			<div class="cbv-cta-band__actions">
				<?php foreach ( $cbv_cta['buttons'] as $button ) : ?>
					<a class="cbv-button<?php echo empty( $button['primary'] ) ? ' cbv-button--ghost-dark' : ''; ?>" href="<?php echo esc_url( $button['url'] ); ?>"><?php echo esc_html( $button['label'] ); ?></a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
