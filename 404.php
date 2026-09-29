<?php
/**
 * 404 template.
 *
 * Also used by cfw_core, which calls get_template_part( 404 ) when it hides a
 * restricted or uncategorized product, so this file must exist for those
 * responses to render a real page instead of falling through to index.php.
 *
 * @package Commercebuild_Velocity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<div class="cbv-container cbv-content cbv-404">
	<h1 class="cbv-404__title"><?php esc_html_e( 'We can&rsquo;t find that page', 'commercebuild-velocity' ); ?></h1>
	<p class="cbv-404__text">
		<?php esc_html_e( 'The page you were looking for may have moved, or the product may no longer be available.', 'commercebuild-velocity' ); ?>
	</p>
	<div class="cbv-404__search">
		<?php if ( function_exists( 'get_product_search_form' ) ) : ?>
			<?php get_product_search_form(); ?>
		<?php else : ?>
			<?php get_search_form(); ?>
		<?php endif; ?>
	</div>
	<p class="cbv-404__actions">
		<a class="cbv-button" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back to home', 'commercebuild-velocity' ); ?></a>
		<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
			<a class="cbv-button cbv-button--ghost" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Browse products', 'commercebuild-velocity' ); ?></a>
		<?php endif; ?>
	</p>
	<?php
	/**
	 * Fires inside the 404 page, below the default actions.
	 *
	 * @since 0.5.1
	 */
	do_action( 'cbv_404_content' );
	?>
</div>
<?php
get_footer();
