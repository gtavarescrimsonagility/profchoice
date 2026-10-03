<?php
/**
 * PDP main section: gallery, product info, "As ridden by" card and the
 * add to cart form (woocommerce/single-product/add-to-cart/variable.php).
 *
 * @package profchoice
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

$pdp_badges = profchoice_badges( $product->get_id() );

// Gallery thumbnails pick the color whose variations use that image.
$pdp_colors = array();
if ( $product->is_type( 'variable' ) ) {
	foreach ( $product->get_available_variations( 'objects' ) as $pdp_variation ) {
		$pdp_attributes = $pdp_variation->get_attributes();
		$pdp_image      = (int) $pdp_variation->get_image_id();
		if ( ! empty( $pdp_attributes['pa_color'] ) && $pdp_image && ! isset( $pdp_colors[ $pdp_image ] ) ) {
			$pdp_colors[ $pdp_image ] = $pdp_attributes['pa_color'];
		}
	}
}
$pdp_color  = $product->is_type( 'variable' ) ? $product->get_variation_default_attribute( 'pa_color' ) : '';
$pdp_thumbs = array();
foreach ( $product->get_gallery_image_ids() as $pdp_index => $pdp_image_id ) {
	$pdp_value    = isset( $pdp_colors[ $pdp_image_id ] ) ? $pdp_colors[ $pdp_image_id ] : '';
	$pdp_term     = $pdp_value ? get_term_by( 'slug', $pdp_value, 'pa_color' ) : false;
	$pdp_label    = $pdp_term ? $pdp_term->name : (string) get_post_meta( $pdp_image_id, '_wp_attachment_image_alt', true );
	$pdp_thumbs[] = array(
		'id'     => $pdp_image_id,
		'label'  => $pdp_label,
		'value'  => $pdp_value,
		'active' => $pdp_value ? $pdp_value === $pdp_color : 0 === $pdp_index,
	);
}

$pdp_stock   = $product->is_in_stock() ? __( 'In Stock', 'profchoice' ) : __( 'Out of Stock', 'profchoice' );
$pdp_rating  = (float) $product->get_average_rating();
$pdp_reviews = (int) $product->get_review_count();
?>
<section class="pdp-main">
	<div class="container wide">
		<div
			class="pdp-main__layout"
			data-wp-interactive="profchoice/pdp"
			<?php echo wp_interactivity_data_wp_context(
				array(
					'color'     => $pdp_color,
					'stock'     => $pdp_stock,
					'price'     => html_entity_decode( wp_strip_all_tags( $product->get_price_html() ), ENT_QUOTES, 'UTF-8' ),
					'priceHtml' => '',
				)
			); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			data-wp-init="callbacks.init"
		>
			<div class="pdp-main__media">
				<div class="pdp-main__stage">
					<?php echo profchoice_stage_gallery( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php if ( $pdp_badges ) : ?>
						<span class="pdp-main__badges">
							<?php foreach ( $pdp_badges as $pdp_slug => $pdp_badge ) : ?>
								<span class="pdp-main__badge pdp-main__badge--<?php echo esc_attr( $pdp_slug ); ?>"<?php profchoice_badge_style( $pdp_slug ); ?>><?php echo esc_html( $pdp_badge ); ?></span>
							<?php endforeach; ?>
						</span>
					<?php endif; ?>
					<?php echo profchoice_wishlist_button( $product->get_id(), 'pdp-main__fav pdp-main__fav--overlay', __( 'Add to favorites', 'profchoice' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<?php if ( $pdp_thumbs ) : ?>
					<div class="pdp-main__thumbs">
						<?php foreach ( $pdp_thumbs as $pdp_thumb ) : ?>
							<button
								class="pdp-main__thumb<?php echo $pdp_thumb['active'] ? ' active' : ''; ?>"
								type="button"
								aria-pressed="<?php echo $pdp_thumb['active'] ? 'true' : 'false'; ?>"
								aria-label="<?php echo esc_attr( $pdp_thumb['label'] ); ?>"
								<?php if ( $pdp_thumb['value'] ) : ?>
									<?php echo wp_interactivity_data_wp_context( array( 'value' => $pdp_thumb['value'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									data-wp-class--active="state.isThumbActive"
									data-wp-bind--aria-pressed="state.isThumbActive"
									data-wp-on--click="actions.pickColor"
								<?php endif; ?>
							>
								<?php
								echo wp_get_attachment_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									$pdp_thumb['id'],
									'profchoice-thumb-2x',
									false,
									array(
										'alt'     => '',
										'sizes'   => '(width <= 30rem) 4rem, 5rem',
										'loading' => false,
									)
								);
								?>
							</button>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="pdp-main__info">
				<h1 class="pdp-main__title"><?php echo esc_html( $product->get_name() ); ?></h1>

				<div class="pdp-main__meta">
					<?php if ( $pdp_reviews ) : ?>
						<div class="pdp-main__rating">
							<?php $pdp_filled = (int) round( $pdp_rating ); ?>
							<span class="pdp-main__stars" aria-hidden="true"><?php echo esc_html( str_repeat( '★', $pdp_filled ) ); ?><?php if ( $pdp_filled < 5 ) : ?><span class="pdp-main__stars-off"><?php echo esc_html( str_repeat( '★', 5 - $pdp_filled ) ); ?></span><?php endif; ?></span>
							<span class="pdp-main__score"><span class="pdp-visually-hidden"><?php esc_html_e( 'Rated', 'profchoice' ); ?> </span><?php echo esc_html( number_format_i18n( $pdp_rating, 1 ) ); ?></span>
							<span class="pdp-main__dot" aria-hidden="true">·</span>
							<a class="pdp-main__reviews" href="#reviews">
								<?php
								/* translators: %s: number of reviews. */
								echo esc_html( sprintf( _n( '%s review', '%s reviews', $pdp_reviews, 'profchoice' ), number_format_i18n( $pdp_reviews ) ) );
								?>
							</a>
						</div>
					<?php endif; ?>
					<?php if ( $product->get_sku() ) : ?>
						<p class="pdp-main__sku">
							<?php
							/* translators: %s: product SKU. */
							echo esc_html( sprintf( __( 'SKU: %s', 'profchoice' ), $product->get_sku() ) );
							?>
						</p>
					<?php endif; ?>
				</div>

				<div class="pdp-main__price-row">
					<p class="pdp-main__price" data-wp-watch="callbacks.price"><?php echo wp_kses_post( $product->get_price_html() ); ?></p>
					<p class="pdp-main__stock" data-wp-text="context.stock"><?php echo esc_html( $pdp_stock ); ?></p>
				</div>

				<?php if ( $product->get_short_description() ) : ?>
					<p class="pdp-main__description"><?php echo wp_kses( $product->get_short_description(), array( 'a' => array( 'href' => true ), 'strong' => array(), 'em' => array(), 'br' => array() ) ); ?></p>
				<?php endif; ?>

				<?php get_template_part( 'template-parts/pdp/rider' ); ?>

				<?php woocommerce_template_single_add_to_cart(); ?>
			</div>
		</div>
	</div>
</section>
