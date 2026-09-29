<?php
/**
 * Guest marketing homepage.
 *
 * @package Commercebuild_Velocity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cbv_home = CBV_Chrome::marketing_home();
$cbv_shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
?>
<div class="cbv-home cbv-home--marketing">
	<section class="cbv-split-hero">
		<div class="cbv-container cbv-split-hero__inner">
			<div class="cbv-split-hero__copy">
				<?php if ( ! empty( $cbv_home['eyebrow'] ) ) : ?>
					<p class="cbv-split-hero__eyebrow"><?php echo esc_html( $cbv_home['eyebrow'] ); ?></p>
				<?php endif; ?>
				<h1 class="cbv-split-hero__heading"><?php echo wp_kses_post( $cbv_home['heading'] ); ?></h1>
				<?php if ( ! empty( $cbv_home['text'] ) ) : ?>
					<p class="cbv-split-hero__text"><?php echo esc_html( $cbv_home['text'] ); ?></p>
				<?php endif; ?>
				<div class="cbv-split-hero__actions">
					<?php if ( ! empty( $cbv_home['primary_label'] ) ) : ?>
						<a class="cbv-button" href="<?php echo esc_url( $cbv_home['primary_url'] ); ?>"><?php echo esc_html( $cbv_home['primary_label'] ); ?></a>
					<?php endif; ?>
					<?php if ( ! empty( $cbv_home['secondary_label'] ) ) : ?>
						<a class="cbv-button cbv-button--ghost" href="<?php echo esc_url( $cbv_home['secondary_url'] ); ?>"><?php echo esc_html( $cbv_home['secondary_label'] ); ?></a>
					<?php endif; ?>
				</div>
			</div>
			<div class="cbv-split-hero__media">
				<?php if ( ! empty( $cbv_home['image_url'] ) ) : ?>
					<img src="<?php echo esc_url( $cbv_home['image_url'] ); ?>" alt="<?php echo esc_attr( $cbv_home['image_alt'] ); ?>" fetchpriority="high" decoding="async">
				<?php endif; ?>
				<?php if ( ! empty( $cbv_home['stats'] ) ) : ?>
					<div class="cbv-split-hero__stats">
						<?php foreach ( $cbv_home['stats'] as $cbv_stat ) : ?>
							<div class="cbv-split-hero__stat">
								<strong><?php echo esc_html( $cbv_stat['value'] ); ?></strong>
								<span><?php echo esc_html( $cbv_stat['label'] ); ?></span>
							</div>
						<?php endforeach; ?>
						<?php if ( ! empty( $cbv_home['stat_cta_label'] ) ) : ?>
							<a class="cbv-button" href="<?php echo esc_url( $cbv_home['stat_cta_url'] ); ?>"><?php echo esc_html( $cbv_home['stat_cta_label'] ); ?></a>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<?php
	$cbv_best = ! empty( $cbv_home['bestsellers']['limit'] ) ? absint( $cbv_home['bestsellers']['limit'] ) : 7;
	$cbv_ids  = array();

	if ( function_exists( 'cbv_engine' ) ) {
		$cbv_ids = cbv_engine()->get_hot_deal_ids( 'best_selling', $cbv_best, array() );

		if ( empty( $cbv_ids ) ) {
			$cbv_ids = cbv_engine()->get_hot_deal_ids( 'newest', max( $cbv_best, 24 ), array() );
		}

		/**
		 * Filter homepage Best Seller product IDs before the grid.
		 *
		 * @since 0.12.32
		 *
		 * @param int[] $cbv_ids  Candidate IDs.
		 * @param int   $cbv_best Number of cards to show.
		 */
		$cbv_ids = apply_filters( 'cbv_bestseller_ids', $cbv_ids, $cbv_best );
		$cbv_ids = array_slice( array_map( 'absint', $cbv_ids ), 0, $cbv_best );
	}
	?>
	<?php if ( ! empty( $cbv_ids ) ) : ?>
		<section class="cbv-home-section">
			<div class="cbv-container">
				<div class="cbv-home-section__head">
					<h2 class="cbv-block__heading"><?php echo esc_html( $cbv_home['bestsellers']['heading'] ); ?></h2>
					<?php if ( ! empty( $cbv_home['bestsellers']['link'] ) ) : ?>
						<a class="cbv-home-section__link" href="<?php echo esc_url( $cbv_home['bestsellers']['link'] ); ?>"><?php esc_html_e( 'Browse Full Catalogue', 'commercebuild-velocity' ); ?></a>
					<?php endif; ?>
				</div>
				<?php echo cbv_product_grid( $cbv_ids, array(), min( 7, count( $cbv_ids ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped during card rendering. ?>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( ! empty( $cbv_home['deals']['tiles'] ) ) : ?>
		<section class="cbv-deals">
			<div class="cbv-container">
				<div class="cbv-home-section__head cbv-home-section__head--light">
					<div>
						<?php if ( ! empty( $cbv_home['deals']['kicker'] ) ) : ?>
							<p class="cbv-kicker"><?php echo esc_html( $cbv_home['deals']['kicker'] ); ?></p>
						<?php endif; ?>
						<h2 class="cbv-block__heading"><?php echo esc_html( $cbv_home['deals']['heading'] ); ?></h2>
						<?php if ( ! empty( $cbv_home['deals']['text'] ) ) : ?>
							<p class="cbv-home-section__text"><?php echo esc_html( $cbv_home['deals']['text'] ); ?></p>
						<?php endif; ?>
					</div>
					<?php if ( ! empty( $cbv_home['deals']['link'] ) ) : ?>
						<a class="cbv-home-section__link" href="<?php echo esc_url( $cbv_home['deals']['link'] ); ?>"><?php esc_html_e( 'View All Promotions', 'commercebuild-velocity' ); ?></a>
					<?php endif; ?>
				</div>
				<div class="cbv-deals__mosaic">
					<?php foreach ( $cbv_home['deals']['tiles'] as $index => $tile ) : ?>
						<a class="cbv-deals__tile<?php echo 0 === $index ? ' cbv-deals__tile--feature' : ''; ?>" href="<?php echo esc_url( $tile['url'] ); ?>">
							<?php if ( ! empty( $tile['image'] ) ) : ?>
								<img src="<?php echo esc_url( $tile['image'] ); ?>" alt="" loading="lazy" decoding="async">
							<?php endif; ?>
							<span class="cbv-deals__body">
								<?php if ( ! empty( $tile['kicker'] ) ) : ?>
									<span class="cbv-deals__kicker"><?php echo esc_html( $tile['kicker'] ); ?></span>
								<?php endif; ?>
								<span class="cbv-deals__title"><?php echo esc_html( $tile['title'] ); ?></span>
								<?php if ( ! empty( $tile['text'] ) ) : ?>
									<span class="cbv-deals__text"><?php echo esc_html( $tile['text'] ); ?></span>
								<?php endif; ?>
							</span>
						</a>
					<?php endforeach; ?>
				</div>
				<?php if ( ! empty( $cbv_home['deals']['pills'] ) ) : ?>
					<div class="cbv-deals__pills">
						<?php foreach ( $cbv_home['deals']['pills'] as $pill ) : ?>
							<div class="cbv-deals__pill">
								<div>
									<?php if ( ! empty( $pill['kicker'] ) ) : ?>
										<p class="cbv-deals__kicker"><?php echo esc_html( $pill['kicker'] ); ?></p>
									<?php endif; ?>
									<p class="cbv-deals__title"><?php echo esc_html( $pill['title'] ); ?></p>
									<?php if ( ! empty( $pill['text'] ) ) : ?>
										<p class="cbv-deals__text"><?php echo esc_html( $pill['text'] ); ?></p>
									<?php endif; ?>
								</div>
								<?php if ( ! empty( $pill['button'] ) ) : ?>
									<a class="cbv-button cbv-button--ghost" href="<?php echo esc_url( $pill['url'] ); ?>"><?php echo esc_html( $pill['button'] ); ?></a>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</section>
	<?php endif; ?>

	<section class="cbv-home-section">
		<div class="cbv-container">
			<div class="cbv-home-section__head">
				<div>
					<?php if ( ! empty( $cbv_home['categories']['kicker'] ) ) : ?>
						<p class="cbv-kicker"><?php echo esc_html( $cbv_home['categories']['kicker'] ); ?></p>
					<?php endif; ?>
					<h2 class="cbv-block__heading"><?php echo esc_html( $cbv_home['categories']['heading'] ); ?></h2>
					<?php if ( ! empty( $cbv_home['categories']['text'] ) ) : ?>
						<p class="cbv-home-section__text"><?php echo esc_html( $cbv_home['categories']['text'] ); ?></p>
					<?php endif; ?>
				</div>
				<?php if ( ! empty( $cbv_home['categories']['link'] ) ) : ?>
					<a class="cbv-home-section__link" href="<?php echo esc_url( $cbv_home['categories']['link'] ); ?>"><?php esc_html_e( 'View Full Catalogue', 'commercebuild-velocity' ); ?></a>
				<?php endif; ?>
			</div>
			<?php
			if ( function_exists( 'cbv_engine' ) && taxonomy_exists( 'product_cat' ) ) {
				$cbv_tiles = cbv_engine()->get_category_tiles(
					array(
						'limit' => absint( $cbv_home['categories']['limit'] ),
					)
				);

				/**
				 * Filter homepage category tiles.
				 *
				 * @since 0.12.0
				 *
				 * @param array $cbv_tiles Tiles.
				 */
				$cbv_tiles = apply_filters( 'cbv_home_category_tiles', $cbv_tiles );

				cbv_the_home_category_tiles( $cbv_tiles );
			}
			?>
		</div>
	</section>

	<?php if ( ! empty( $cbv_home['story']['heading'] ) ) : ?>
		<section class="cbv-story">
			<div class="cbv-container cbv-story__inner">
				<div class="cbv-story__copy">
					<?php if ( ! empty( $cbv_home['story']['eyebrow'] ) ) : ?>
						<p class="cbv-split-hero__eyebrow"><?php echo esc_html( $cbv_home['story']['eyebrow'] ); ?></p>
					<?php endif; ?>
					<h2 class="cbv-split-hero__heading"><?php echo esc_html( $cbv_home['story']['heading'] ); ?></h2>
					<p><?php echo esc_html( $cbv_home['story']['text'] ); ?></p>
					<?php if ( ! empty( $cbv_home['story']['button'] ) ) : ?>
						<a class="cbv-button cbv-button--ghost" href="<?php echo esc_url( $cbv_home['story']['url'] ); ?>"><?php echo esc_html( $cbv_home['story']['button'] ); ?></a>
					<?php endif; ?>
				</div>
				<?php if ( ! empty( $cbv_home['story']['cards'] ) ) : ?>
					<div class="cbv-story__cards">
						<?php foreach ( $cbv_home['story']['cards'] as $card ) : ?>
							<div class="cbv-story__card">
								<?php if ( ! empty( $card['icon'] ) ) : ?>
									<span class="cbv-story__icon"><?php cbv_the_icon( $card['icon'], array( 'size' => 18 ) ); ?></span>
								<?php endif; ?>
								<strong><?php echo esc_html( $card['title'] ); ?></strong>
								<p><?php echo esc_html( $card['text'] ); ?></p>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</section>
	<?php endif; ?>

	<?php
	if ( function_exists( 'cbv_the_cta_band' ) ) {
		cbv_the_cta_band( isset( $cbv_home['cta'] ) ? $cbv_home['cta'] : array() );
	}
	?>

	<?php if ( ! empty( $cbv_home['facts'] ) ) : ?>
		<section class="cbv-facts">
			<div class="cbv-container cbv-facts__inner">
				<?php foreach ( $cbv_home['facts'] as $fact ) : ?>
					<div class="cbv-facts__item">
						<?php if ( ! empty( $fact['icon'] ) ) : ?>
							<span class="cbv-facts__icon"><?php cbv_the_icon( $fact['icon'], array( 'size' => 20 ) ); ?></span>
						<?php endif; ?>
						<strong><?php echo esc_html( $fact['title'] ); ?></strong>
						<span><?php echo esc_html( $fact['text'] ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>
</div>
