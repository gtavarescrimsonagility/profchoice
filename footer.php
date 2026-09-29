<?php
/**
 * Site footer.
 *
 * @package Commercebuild_Velocity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php
/**
 * Fires just inside the end of the main content element.
 *
 * @since 0.8.0
 */
do_action( 'cbv_main_end' );
?>
</main>
<?php
/**
 * Fires immediately after the main content element.
 *
 * @since 0.8.0
 */
do_action( 'cbv_after_main' );


/**
 * Fires after main content, before the site footer.
 *
 * @since 0.3.0
 */
do_action( 'cbv_before_footer' );
?>
<footer class="cbv-footer<?php echo CBV_Chrome::is_commerce() ? ' cbv-footer--commerce' : ''; ?>">
	<?php CBV_Block_Areas::render( 'footer' ); ?>
	<?php
	/**
	 * Fires at the start of the footer row.
	 *
	 * @since 0.3.0
	 */
	do_action( 'cbv_footer_start' );
	?>
	<?php if ( CBV_Chrome::is_commerce() ) : ?>
		<?php CBV_Chrome::render_footer(); ?>
	<div class="cbv-container cbv-footer__legal">
		<p class="cbv-footer__copyright">
			&copy; <?php echo esc_html( gmdate( 'Y' ) . ' ' . get_bloginfo( 'name' ) ); ?>
			<?php esc_html_e( 'All Rights Reserved.', 'commercebuild-velocity' ); ?>
		</p>
	</div>
	<?php else : ?>
	<div class="cbv-container cbv-footer__inner">
		<?php if ( has_nav_menu( 'footer' ) ) : ?>
			<nav class="cbv-footer__nav" aria-label="<?php esc_attr_e( 'Footer', 'commercebuild-velocity' ); ?>">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'footer',
						'menu_class'     => 'cbv-footer__list',
						'container'      => false,
						'depth'          => 1,
					)
				);
				?>
			</nav>
		<?php endif; ?>
		<p class="cbv-footer__copyright">
			&copy; <?php echo esc_html( gmdate( 'Y' ) . ' ' . get_bloginfo( 'name' ) ); ?>
		</p>
	</div>
	<?php endif; ?>
	<?php
	/**
	 * Fires at the end of the footer row.
	 *
	 * @since 0.3.0
	 */
	do_action( 'cbv_footer_end' );
	?>
</footer>
<?php
/**
 * Fires after the site footer, before wp_footer().
 *
 * @since 0.3.0
 */
do_action( 'cbv_after_footer' );
?>
<?php wp_footer(); ?>
</body>
</html>
