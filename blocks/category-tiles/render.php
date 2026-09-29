<?php
/**
 * Category Tiles block render.
 *
 * @package Commercebuild_Velocity
 *
 * @var array $attributes Block attributes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! taxonomy_exists( 'product_cat' ) ) {
	return;
}

$cbv_tiles = cbv_engine()->get_category_tiles(
	array(
		'parent'  => $attributes['parent'],
		'limit'   => $attributes['limit'],
		'include' => wp_parse_id_list( $attributes['includeIds'] ),
	)
);

if ( empty( $cbv_tiles ) ) {
	return;
}

$cbv_columns = min( 6, max( 1, absint( $attributes['columns'] ) ) );
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'cbv-block-category-tiles' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by WordPress core. ?>>
	<?php
	/**
	 * Fires inside the category-tiles block wrapper, before its content.
	 *
	 * @since 0.3.0
	 *
	 * @param array $attributes Block attributes.
	 */
	do_action( 'cbv_block_category_tiles_before', $attributes );
	?>
	<?php if ( '' !== $attributes['heading'] ) : ?>
		<h2 class="cbv-block__heading"><?php echo esc_html( $attributes['heading'] ); ?></h2>
	<?php endif; ?>
	<div class="cbv-grid cbv-grid--tiles" style="--cbv-grid-cols:<?php echo esc_attr( $cbv_columns ); ?>">
		<?php foreach ( $cbv_tiles as $cbv_tile ) : ?>
			<a class="cbv-tile" href="<?php echo esc_url( $cbv_tile['url'] ); ?>">
				<span class="cbv-tile__media">
					<?php
					if ( $cbv_tile['thumbnail_id'] ) {
						echo wp_get_attachment_image( $cbv_tile['thumbnail_id'], 'cbv-tile', false, array( 'loading' => 'lazy' ) );
					}
					?>
				</span>
				<span class="cbv-tile__name"><?php echo esc_html( $cbv_tile['name'] ); ?></span>
				<?php if ( $attributes['showCount'] ) : ?>
					<span class="cbv-tile__count">
						<?php
						/* translators: %s: number of products. */
						echo esc_html( sprintf( _n( '%s product', '%s products', $cbv_tile['count'], 'commercebuild-velocity' ), number_format_i18n( $cbv_tile['count'] ) ) );
						?>
					</span>
				<?php endif; ?>
			</a>
		<?php endforeach; ?>
	</div>
	<?php
	/**
	 * Fires inside the category-tiles block wrapper, after its content.
	 *
	 * @since 0.3.0
	 *
	 * @param array $attributes Block attributes.
	 */
	do_action( 'cbv_block_category_tiles_after', $attributes );
	?>
</section>
