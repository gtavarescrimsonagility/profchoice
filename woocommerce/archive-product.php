<?php
/**
 * Catalog template for shop, product taxonomy, and product search pages.
 *
 * Renders the areas configured in Customizer > Category &amp; Search Pages.
 * The main query is untouched, so pagination, sorting, and any plugin that
 * hooks the WooCommerce loop keep working; only the arrangement is ours.
 *
 * @package Commercebuild_Velocity
 * @version 8.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header( 'shop' );

$cbv_columns = CBV_Catalog_Layout::columns();
$cbv_sidebar = ( 'none' !== $cbv_columns['sidebar'] ) && CBV_Catalog_Layout::area_has_content( 'sidebar' );

/**
 * Fires before the catalog wrapper.
 *
 * @since 0.9.0
 */
do_action( 'cbv_before_catalog' );

cbv_the_catalog_hero();
?>
<div class="cbv-container cbv-content cbv-catalog__wrap">
	<?php
	/** This action is documented in WooCommerce templates. */
	do_action( 'woocommerce_before_main_content' );

	/** This action is documented in WooCommerce templates. */
	do_action( 'woocommerce_shop_loop_header' );
	?>

	<header class="cbv-catalog__header">
		<?php CBV_Catalog_Layout::render_area( 'above' ); ?>
	</header>

	<?php $cbv_drawer = $cbv_sidebar && 'drawer' === CBV_Catalog_Layout::mobile_filters_mode(); ?>

	<div class="cbv-catalog__body cbv-catalog__body--<?php echo esc_attr( $cbv_sidebar ? $cbv_columns['sidebar'] : 'none' ); ?>">
		<?php if ( $cbv_drawer ) : ?>
			<?php
			/**
			 * Fires before the mobile filters toggle button.
			 *
			 * @since 0.9.7
			 */
			do_action( 'cbv_catalog_filters_toggle_before' );

			$cbv_filter_icon = wp_kses(
				cbv_icon(
					'filter',
					array(
						'size' => 20,
					)
				),
				CBV_Icons::kses_allowed()
			);
			$cbv_toggle_html = sprintf(
				'<button type="button" class="cbv-filters-toggle" aria-controls="cbv-catalog-sidebar" aria-expanded="false"><span class="cbv-filters-toggle__icon" aria-hidden="true">%1$s</span><span>%2$s</span></button>',
				$cbv_filter_icon,
				esc_html(
					/**
					 * Filter the mobile filters toggle button label.
					 *
					 * @since 0.9.7
					 *
					 * @param string $label Button label.
					 */
					apply_filters( 'cbv_catalog_filters_toggle_label', __( 'Filters', 'commercebuild-velocity' ) )
				)
			);

			/**
			 * Filter the full mobile filters toggle button markup.
			 *
			 * @since 0.9.7
			 *
			 * @param string $cbv_toggle_html Button HTML.
			 */
			echo apply_filters( 'cbv_catalog_filters_toggle_html', $cbv_toggle_html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built above from escaped parts; filterable by design.

			/**
			 * Fires after the mobile filters toggle button.
			 *
			 * @since 0.9.7
			 */
			do_action( 'cbv_catalog_filters_toggle_after' );
			?>
		<?php endif; ?>

		<?php if ( $cbv_sidebar ) : ?>
			<aside id="cbv-catalog-sidebar" class="cbv-catalog__sidebar<?php echo $cbv_drawer ? ' cbv-catalog__sidebar--drawer' : ''; ?>" aria-label="<?php esc_attr_e( 'Catalog filters', 'commercebuild-velocity' ); ?>">
				<?php if ( $cbv_drawer ) : ?>
					<button type="button" class="cbv-filters-close" aria-label="<?php esc_attr_e( 'Close filters', 'commercebuild-velocity' ); ?>"><?php cbv_the_icon( 'close' ); ?></button>
				<?php endif; ?>
				<?php CBV_Catalog_Layout::render_area( 'sidebar' ); ?>
			</aside>
			<?php if ( $cbv_drawer ) : ?>
				<div class="cbv-filters-overlay" hidden></div>
			<?php endif; ?>
		<?php endif; ?>

		<div class="cbv-catalog__main">
			<?php if ( CBV_Catalog_Layout::area_has_content( 'toolbar' ) ) : ?>
				<div class="cbv-catalog__toolbar">
					<?php CBV_Catalog_Layout::render_area( 'toolbar' ); ?>
				</div>
			<?php endif; ?>

			<?php
			/** This action is documented in WooCommerce templates. */
			do_action( 'woocommerce_before_shop_loop' );

			CBV_Catalog_Layout::render_area( 'content' );

			/** This action is documented in WooCommerce templates. */
			do_action( 'woocommerce_after_shop_loop' );
			?>
		</div>
	</div>

	<?php if ( CBV_Catalog_Layout::area_has_content( 'below' ) ) : ?>
		<div class="cbv-catalog__below">
			<?php CBV_Catalog_Layout::render_area( 'below' ); ?>
		</div>
	<?php endif; ?>

	<?php
	/** This action is documented in WooCommerce templates. */
	do_action( 'woocommerce_after_main_content' );
	?>
</div>
<?php
/**
 * Fires after the catalog wrapper.
 *
 * @since 0.9.0
 */
do_action( 'cbv_after_catalog' );

get_footer( 'shop' );
