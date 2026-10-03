<?php
/**
 * Write a Review form: posted to wp-comments-post.php like WooCommerce's
 * own review form (rating, review, and name and email for guests).
 *
 * @package ProfChoiceCore
 *
 * @var WC_Product $pc_product Product.
 * @var string     $pc_uid     Unique ID prefix.
 * @var string     $pc_full    Filled star SVG.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pc_user = wp_get_current_user();
?>
<form class="pc-review-form" id="<?php echo esc_attr( $pc_uid . '-form' ); ?>" action="<?php echo esc_url( site_url( '/wp-comments-post.php' ) ); ?>" method="post" aria-label="<?php esc_attr_e( 'Write a review', 'profchoicecore' ); ?>" hidden data-wp-bind--hidden="!context.formOpen">
	<?php if ( wc_review_ratings_enabled() ) : ?>
		<fieldset class="pc-review-form__rating">
			<legend class="pc-review-form__label"><?php esc_html_e( 'Your rating', 'profchoicecore' ); ?></legend>
			<div class="pc-review-form__stars">
				<?php for ( $pc_i = 1; $pc_i <= 5; $pc_i++ ) : ?>
					<input class="pc-review-form__star-input" type="radio" name="rating" id="<?php echo esc_attr( $pc_uid . '-rating-' . $pc_i ); ?>" value="<?php echo esc_attr( (string) $pc_i ); ?>"<?php echo wc_review_ratings_required() ? ' required' : ''; ?>>
					<label class="pc-review-form__star" for="<?php echo esc_attr( $pc_uid . '-rating-' . $pc_i ); ?>" title="<?php echo esc_attr( sprintf( /* translators: %d: rating. */ __( '%d of 5', 'profchoicecore' ), $pc_i ) ); ?>"><?php echo $pc_full; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span class="pc-visually-hidden"><?php echo esc_html( sprintf( /* translators: %d: rating. */ _n( '%d star', '%d stars', $pc_i, 'profchoicecore' ), $pc_i ) ); ?></span></label>
				<?php endfor; ?>
			</div>
		</fieldset>
	<?php endif; ?>
	<div class="pc-review-form__field">
		<label class="pc-review-form__label" for="<?php echo esc_attr( $pc_uid . '-text' ); ?>"><?php esc_html_e( 'Your review', 'profchoicecore' ); ?></label>
		<textarea class="pc-review-form__input" id="<?php echo esc_attr( $pc_uid . '-text' ); ?>" name="comment" rows="5" required></textarea>
	</div>
	<?php if ( ! $pc_user->exists() ) : ?>
		<div class="pc-review-form__row">
			<div class="pc-review-form__field">
				<label class="pc-review-form__label" for="<?php echo esc_attr( $pc_uid . '-name' ); ?>"><?php esc_html_e( 'Name', 'profchoicecore' ); ?></label>
				<input class="pc-review-form__input" id="<?php echo esc_attr( $pc_uid . '-name' ); ?>" name="author" type="text" autocomplete="name"<?php echo get_option( 'require_name_email' ) ? ' required' : ''; ?>>
			</div>
			<div class="pc-review-form__field">
				<label class="pc-review-form__label" for="<?php echo esc_attr( $pc_uid . '-email' ); ?>"><?php esc_html_e( 'Email', 'profchoicecore' ); ?></label>
				<input class="pc-review-form__input" id="<?php echo esc_attr( $pc_uid . '-email' ); ?>" name="email" type="email" autocomplete="email"<?php echo get_option( 'require_name_email' ) ? ' required' : ''; ?>>
			</div>
		</div>
		<p class="pc-review-form__note"><?php esc_html_e( 'Your email address will not be published.', 'profchoicecore' ); ?></p>
	<?php endif; ?>
	<input type="hidden" name="comment_post_ID" value="<?php echo absint( $pc_product->get_id() ); ?>">
	<input type="hidden" name="comment_parent" value="0">
	<?php do_action( 'comment_form', $pc_product->get_id() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core hook (spam protection plugins). ?>
	<button class="pc-review-form__submit" type="submit"><?php esc_html_e( 'Submit Review', 'profchoicecore' ); ?></button>
</form>
