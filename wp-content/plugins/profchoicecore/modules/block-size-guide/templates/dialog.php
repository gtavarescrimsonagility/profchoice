<?php
/**
 * Size guide modal: the page's title and content.
 *
 * @package ProfChoiceCore
 *
 * @var WP_Post $pc_page Size guide page.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pc_id    = 'pc-size-guide-' . $pc_page->ID;
$pc_close = function_exists( 'wp_get_icon' ) ? trim( wp_get_icon( 'profchoice/close', array( 'size' => null, 'class' => 'pc-size-guide__close-icon' ) ) ) : '';
?>
<dialog class="pc-size-guide" id="<?php echo esc_attr( $pc_id ); ?>" aria-labelledby="<?php echo esc_attr( $pc_id . '-title' ); ?>" data-wp-interactive="profchoice/size-guide" data-wp-on--click="actions.backdrop" data-wp-on--close="actions.closed">
	<div class="pc-size-guide__head">
		<div>
			<?php if ( 0 !== strcasecmp( trim( $pc_page->post_title ), __( 'Size guide', 'profchoicecore' ) ) ) : ?>
				<p class="pc-size-guide__eyebrow"><?php esc_html_e( 'Size guide', 'profchoicecore' ); ?></p>
			<?php endif; ?>
			<h2 class="pc-size-guide__title" id="<?php echo esc_attr( $pc_id . '-title' ); ?>"><?php echo esc_html( $pc_page->post_title ); ?></h2>
		</div>
		<button class="pc-size-guide__close" type="button" aria-label="<?php esc_attr_e( 'Close', 'profchoicecore' ); ?>" data-wp-on--click="actions.close">
			<?php echo $pc_close ? $pc_close : '<span aria-hidden="true">&times;</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</button>
	</div>
	<div class="pc-size-guide__body">
		<?php echo apply_filters( 'the_content', $pc_page->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core filter. ?>
	</div>
</dialog>
