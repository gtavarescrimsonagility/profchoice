<?php
/**
 * "As ridden by" card (profchoicecore "As ridden by" tab).
 *
 * @package profchoice
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

$pdp_rider = class_exists( 'ProfChoiceCore\ProductMeta' ) ? ProfChoiceCore\ProductMeta::rider( $product->get_id() ) : null;
if ( ! $pdp_rider ) {
	return;
}
$pdp_image = $pdp_rider['image'] ? wp_get_attachment_image_src( $pdp_rider['image'], 'full' ) : false;
?>
<aside class="pdp-rider">
	<?php if ( $pdp_image ) : ?>
		<div class="pdp-rider__media">
			<img class="pdp-rider__image" src="<?php echo esc_url( $pdp_image[0] ); ?>" width="<?php echo esc_attr( $pdp_image[1] ); ?>" height="<?php echo esc_attr( $pdp_image[2] ); ?>" alt="<?php echo esc_attr( $pdp_rider['alt'] ); ?>" decoding="async">
		</div>
	<?php endif; ?>
	<div class="pdp-rider__body">
		<div class="pdp-rider__heading">
			<?php if ( $pdp_rider['eyebrow'] ) : ?>
				<p class="pdp-rider__eyebrow"><?php echo esc_html( $pdp_rider['eyebrow'] ); ?></p>
			<?php endif; ?>
			<p class="pdp-rider__name"><?php echo esc_html( $pdp_rider['name'] ); ?></p>
		</div>
		<?php if ( $pdp_rider['link_text'] && $pdp_rider['link_url'] ) : ?>
			<a class="pdp-rider__link" href="<?php echo esc_url( $pdp_rider['link_url'] ); ?>">
				<?php echo esc_html( $pdp_rider['link_text'] ); ?>
				<?php echo profchoice_icon( 'arrow-long-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</a>
		<?php endif; ?>
	</div>
</aside>
