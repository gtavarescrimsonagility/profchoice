<?php
/**
 * Single post template.
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
			<h1 class="cbv-entry__title"><?php the_title(); ?></h1>
			<div class="cbv-entry__meta"><?php echo esc_html( get_the_date() ); ?></div>
			<div class="cbv-entry__content"><?php the_content(); ?></div>
		</article>
	<?php endwhile; ?>
</div>
<?php
get_footer();
