<?php
/**
 * Page template.
 *
 * @package Commercebuild_Velocity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<div class="cbv-container cbv-content">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article <?php post_class( 'cbv-entry' ); ?>>
			<?php
			$cbv_hide_title = is_front_page();
			if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() ) ) {
				$cbv_hide_title = true;
			}
			if ( function_exists( 'is_account_page' ) && is_account_page() && ! is_user_logged_in() ) {
				$cbv_hide_title = true;
			}
			if ( ! $cbv_hide_title ) :
				?>
				<h1 class="cbv-entry__title"><?php the_title(); ?></h1>
			<?php endif; ?>
			<div class="cbv-entry__content"><?php the_content(); ?></div>
		</article>
	<?php endwhile; ?>
</div>
<?php
get_footer();
