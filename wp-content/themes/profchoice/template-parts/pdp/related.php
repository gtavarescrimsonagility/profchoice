<?php
/**
 * PDP "May We Suggest": the product's upsells in their order, or related
 * products, as cards.
 *
 * @package profchoice
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

$pdp_ids = $product->get_upsell_ids();
if ( ! $pdp_ids ) {
	$pdp_ids = wc_get_related_products( $product->get_id(), 4 );
}
$pdp_products = array_filter( array_map( 'wc_get_product', array_slice( $pdp_ids, 0, 4 ) ) );
$pdp_products = array_filter(
	$pdp_products,
	function ( $item ) {
		return $item->is_visible();
	}
);
if ( ! $pdp_products ) {
	return;
}

$pdp_terms   = wc_get_product_terms( $product->get_id(), 'product_cat', array( 'orderby' => 'parent', 'order' => 'DESC' ) );
$pdp_all_url = $pdp_terms ? get_term_link( $pdp_terms[0] ) : wc_get_page_permalink( 'shop' );
?>
<section class="pdp-related" aria-labelledby="pdp-related-title">
	<div class="container wide">
		<div class="pdp-related__head">
			<h2 class="pdp-related__title" id="pdp-related-title"><?php esc_html_e( 'May We Suggest', 'profchoice' ); ?></h2>
			<a class="pdp-related__all" href="<?php echo esc_url( $pdp_all_url ); ?>"><?php esc_html_e( 'View All', 'profchoice' ); ?></a>
		</div>

		<ul class="pdp-related__list">
			<?php
			foreach ( $pdp_products as $pdp_card ) :
				$pdp_name   = $pdp_card->get_name();
				$pdp_link   = $pdp_card->get_permalink();
				$pdp_badge  = profchoice_badge( $pdp_card->get_id() );
				$pdp_colors = profchoice_card_colors( $pdp_card );
				?>
				<li class="pdp-card">
					<div class="pdp-card__media">
						<a class="pdp-card__image-link" href="<?php echo esc_url( $pdp_link ); ?>">
							<?php
							echo wp_get_attachment_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								$pdp_card->get_image_id(),
								'full',
								false,
								array(
									'class'    => 'pdp-card__image',
									'alt'      => $pdp_name,
									'loading'  => 'lazy',
									'decoding' => 'async',
								)
							);
							?>
						</a>
						<?php if ( $pdp_badge ) : ?>
							<span class="pdp-card__badge<?php echo 'sale' === sanitize_title( $pdp_badge ) ? ' pdp-card__badge--sale' : ''; ?>"><?php echo esc_html( $pdp_badge ); ?></span>
						<?php endif; ?>
						<?php echo profchoice_wishlist_button( $pdp_card->get_id(), 'pdp-card__fav', sprintf( /* translators: %s: product name. */ __( 'Add %s to favorites', 'profchoice' ), $pdp_name ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php if ( $pdp_card->is_purchasable() && $pdp_card->is_in_stock() && $pdp_card->is_type( 'simple' ) ) : ?>
							<button class="pdp-card__quick add_to_cart_button ajax_add_to_cart" type="button" data-product_id="<?php echo absint( $pdp_card->get_id() ); ?>" data-product_sku="<?php echo esc_attr( $pdp_card->get_sku() ); ?>" data-quantity="1"><?php esc_html_e( '+ Quick Add', 'profchoice' ); ?><span class="pdp-visually-hidden"> <?php echo esc_html( $pdp_name ); ?></span></button>
						<?php else : ?>
							<a class="pdp-card__quick" href="<?php echo esc_url( $pdp_link ); ?>"><?php esc_html_e( '+ Quick Add', 'profchoice' ); ?><span class="pdp-visually-hidden"> <?php echo esc_html( $pdp_name ); ?></span></a>
						<?php endif; ?>
					</div>
					<h3 class="pdp-card__name"><a href="<?php echo esc_url( $pdp_link ); ?>"><?php echo esc_html( $pdp_name ); ?></a></h3>
					<div class="pdp-card__foot">
						<p class="pdp-card__price"><?php echo wp_kses_post( $pdp_card->get_price_html() ); ?></p>
						<?php if ( $pdp_colors ) : ?>
							<p class="pdp-card__swatches">
								<span class="pdp-visually-hidden">
									<?php
									/* translators: %d: number of colors. */
									echo esc_html( sprintf( _n( '%d color', '%d colors', count( $pdp_colors ), 'profchoice' ), count( $pdp_colors ) ) );
									?>
								</span>
								<?php foreach ( $pdp_colors as $pdp_color ) : ?><span style="<?php echo esc_attr( '--swatch: ' . $pdp_color ); ?>"></span><?php endforeach; ?>
							</p>
						<?php endif; ?>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
