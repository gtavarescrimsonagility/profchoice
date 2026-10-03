<?php
/**
 * Product reviews: summary, the newest reviews (more load from the Store
 * API in the slider), pager and Write a Review form (posted to
 * wp-comments-post.php, as WooCommerce's own form).
 *
 * @package ProfChoiceCore
 *
 * @var array    $attributes Block attributes.
 * @var WP_Block $block      Block instance.
 */

use ProfChoiceCore\Reviews\Reviews;

if ( ! defined( 'ABSPATH' ) || ! function_exists( 'wc_get_product' ) ) {
	return;
}

$pc_product_id = ! empty( $attributes['productId'] ) ? absint( $attributes['productId'] ) : 0;
if ( ! $pc_product_id ) {
	$pc_product_id = isset( $block->context['postId'] ) ? absint( $block->context['postId'] ) : absint( get_the_ID() );
}
$pc_product = wc_get_product( $pc_product_id );
if ( ! $pc_product || ! wc_review_ratings_enabled() && ! $pc_product->get_reviews_allowed() ) {
	return;
}

$pc_per_page = max( 1, absint( isset( $attributes['perPage'] ) ? $attributes['perPage'] : 2 ) );
$pc_total    = (int) $pc_product->get_review_count();
$pc_average  = (float) $pc_product->get_average_rating();
$pc_items    = $pc_total ? Reviews::latest( $pc_product->get_id(), $pc_per_page ) : array();
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only flag set by wp-comments-post.php's redirect.
$pc_submitted = isset( $_GET['unapproved'] );
$pc_expanded  = empty( $attributes['collapsed'] ) || $pc_submitted;
$pc_can_write = $pc_product->get_reviews_allowed() && ( ! get_option( 'comment_registration' ) || is_user_logged_in() );
$pc_uid       = wp_unique_id( 'pc-reviews-' );

$pc_icon = static function ( $name, $class = 'pc-inline-icon' ) {
	return function_exists( 'wp_get_icon' ) ? trim( (string) wp_get_icon( 'profchoice/' . $name, array( 'size' => null, 'class' => $class ) ) ) : '';
};
$pc_full = $pc_icon( 'star-filled' );
$pc_half = $pc_icon( 'star-half' );

wp_interactivity_state(
	Reviews::STORE,
	array(
		'restUrl'   => rest_url( 'wc/store/v1/products/reviews' ),
		'ratingOf'  => __( 'Rated %d out of 5', 'profchoicecore' ),
		'starFull'  => static function () {
			$context = wp_interactivity_get_context( Reviews::STORE );
			return isset( $context['star'] ) && 'full' === $context['star'] ? 'contents' : 'none';
		},
		'starHalf'  => static function () {
			$context = wp_interactivity_get_context( Reviews::STORE );
			return isset( $context['star'] ) && 'half' === $context['star'] ? 'contents' : 'none';
		},
		'ratingLabel' => static function () {
			$context = wp_interactivity_get_context( Reviews::STORE );
			/* translators: %d: rating. */
			return isset( $context['review']['rating'] ) ? sprintf( __( 'Rated %d out of 5', 'profchoicecore' ), $context['review']['rating'] ) : '';
		},
		'from'      => static function () {
			$context = wp_interactivity_get_context( Reviews::STORE );
			return min( $context['start'] + 1, $context['total'] );
		},
		'toText'    => static function () {
			$context = wp_interactivity_get_context( Reviews::STORE );
			$to      = min( $context['start'] + $context['perView'], $context['total'] );
			return $to > $context['start'] + 1 ? '–' . $to : '';
		},
		'cannotPrev' => static function () {
			$context = wp_interactivity_get_context( Reviews::STORE );
			return $context['start'] <= 0;
		},
		'cannotNext' => static function () {
			$context = wp_interactivity_get_context( Reviews::STORE );
			return $context['start'] + $context['perView'] >= $context['total'];
		},
	)
);

$pc_wrapper = get_block_wrapper_attributes(
	array(
		'class'           => 'pc-reviews',
		'id'              => 'reviews',
		'aria-labelledby' => $pc_uid . '-title',
	)
);
$pc_summary = sprintf(
	/* translators: 1: average rating, 2: number of reviews. */
	_n( '%1$s out of 5 · %2$s review', '%1$s out of 5 · %2$s reviews', $pc_total, 'profchoicecore' ),
	number_format_i18n( $pc_average, 1 ),
	number_format_i18n( $pc_total )
);
?>
<section
	<?php echo $pc_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php echo $pc_expanded ? '' : 'hidden'; ?>
	data-wp-interactive="profchoice/reviews"
	<?php
	echo wp_interactivity_data_wp_context( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		array(
			'productId' => $pc_product->get_id(),
			'total'     => $pc_total,
			'start'     => 0,
			'perView'   => $pc_per_page,
			'loading'   => false,
			'expanded'  => $pc_expanded,
			'formOpen'  => false,
			'items'     => $pc_items,
		)
	);
	?>
	data-wp-bind--hidden="!context.expanded"
	data-wp-init="callbacks.init"
>
	<div class="pc-reviews__inner">
		<div class="pc-reviews__head">
			<div class="pc-reviews__heading">
				<h2 class="pc-reviews__title" id="<?php echo esc_attr( $pc_uid . '-title' ); ?>"><?php esc_html_e( 'Customer Reviews', 'profchoicecore' ); ?></h2>
				<div class="pc-reviews__summary">
					<?php if ( $pc_total ) : ?>
						<span class="pc-reviews__stars pc-reviews__stars--summary" role="img" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: average rating. */ __( 'Rated %s out of 5', 'profchoicecore' ), number_format_i18n( $pc_average, 1 ) ) ); ?>"><?php echo implode( '', array_map( static function ( $kind ) use ( $pc_full, $pc_half ) { return 'half' === $kind ? $pc_half : $pc_full; }, Reviews::stars( $pc_average ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<p class="pc-reviews__count"><?php echo esc_html( $pc_summary ); ?></p>
					<?php else : ?>
						<p class="pc-reviews__count"><?php esc_html_e( 'No reviews yet. Be the first to review this product.', 'profchoicecore' ); ?></p>
					<?php endif; ?>
				</div>
			</div>
			<?php if ( $pc_can_write ) : ?>
				<button class="pc-reviews__write" type="button" aria-expanded="false" aria-controls="<?php echo esc_attr( $pc_uid . '-form' ); ?>" data-wp-bind--aria-expanded="context.formOpen" data-wp-on--click="actions.toggleForm"><?php esc_html_e( 'Write a Review', 'profchoicecore' ); ?></button>
			<?php endif; ?>
		</div>

		<?php if ( $pc_submitted ) : ?>
			<div class="woocommerce-message pc-alert pc-alert--success pc-reviews__notice" role="status">
				<?php echo $pc_icon( 'check-circle', 'pc-alert__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<div class="pc-alert__body"><?php esc_html_e( 'Thank you for your review. It will appear once it is approved.', 'profchoicecore' ); ?></div>
			</div>
		<?php endif; ?>

		<?php if ( $pc_can_write ) : ?>
			<?php require dirname( __DIR__, 2 ) . '/templates/form.php'; ?>
		<?php endif; ?>

		<?php if ( $pc_total ) : ?>
			<ul class="pc-reviews__list" data-wp-bind--aria-busy="context.loading" data-wp-on--scroll="actions.scrolled">
				<template data-wp-each--review="context.items" data-wp-each-key="context.review.id">
					<li class="pc-review">
						<span class="pc-reviews__stars" role="img" data-wp-bind--aria-label="state.ratingLabel">
							<template data-wp-each--star="context.review.stars"><span class="pc-reviews__star"><span data-wp-style--display="state.starFull"><?php echo $pc_full; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><span data-wp-style--display="state.starHalf"><?php echo $pc_half; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></span></template>
						</span>
						<div class="pc-review__meta">
							<p class="pc-review__author" data-wp-text="context.review.author"></p>
							<span class="pc-review__badge" data-wp-bind--hidden="!context.review.verified"><?php esc_html_e( 'Verified Buyer', 'profchoicecore' ); ?></span>
							<time class="pc-review__date" data-wp-bind--datetime="context.review.datetime" data-wp-text="context.review.date"></time>
						</div>
						<p class="pc-review__text" data-wp-text="context.review.text"></p>
					</li>
				</template>
				<li class="pc-review pc-review--skeleton" aria-hidden="true" hidden data-wp-bind--hidden="!context.loading"><span class="pc-skeleton pc-skeleton--stars"></span><span class="pc-skeleton__meta"><span class="pc-skeleton pc-skeleton--name"></span><span class="pc-skeleton pc-skeleton--date"></span></span><span class="pc-skeleton pc-skeleton--line"></span><span class="pc-skeleton pc-skeleton--line pc-skeleton--short"></span></li>
				<li class="pc-review pc-review--skeleton" aria-hidden="true" hidden data-wp-bind--hidden="!context.loading"><span class="pc-skeleton pc-skeleton--stars"></span><span class="pc-skeleton__meta"><span class="pc-skeleton pc-skeleton--name"></span><span class="pc-skeleton pc-skeleton--date"></span></span><span class="pc-skeleton pc-skeleton--line"></span><span class="pc-skeleton pc-skeleton--line pc-skeleton--short"></span></li>
			</ul>
			<div class="pc-reviews__pager">
				<p class="pc-reviews__position" aria-live="polite"><span data-wp-text="state.from"></span><span class="pc-reviews__to" data-wp-text="state.toText"></span> <?php esc_html_e( 'of', 'profchoicecore' ); ?> <span data-wp-text="context.total"></span></p>
				<div class="pc-reviews__arrows">
					<button class="pc-reviews__arrow pc-reviews__arrow--prev" type="button" aria-label="<?php esc_attr_e( 'Previous reviews', 'profchoicecore' ); ?>" data-wp-bind--disabled="state.cannotPrev" data-wp-on--click="actions.prev"><?php echo $pc_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
					<button class="pc-reviews__arrow" type="button" aria-label="<?php esc_attr_e( 'Next reviews', 'profchoicecore' ); ?>" data-wp-bind--disabled="state.cannotNext" data-wp-on--click="actions.next"><?php echo $pc_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
				</div>
			</div>
		<?php endif; ?>
	</div>
</section>
