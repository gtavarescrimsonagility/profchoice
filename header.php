<?php
/**
 * Site header.
 *
 * @package Commercebuild_Velocity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="cbv-skip-link" href="#cbv-main"><?php esc_html_e( 'Skip to content', 'commercebuild-velocity' ); ?></a>
<?php
/**
 * Fires before the site header (announcement bars, top strips).
 *
 * @since 0.3.0
 */
do_action( 'cbv_before_header' );
?>
<header class="cbv-header<?php echo CBV_Chrome::is_commerce() ? ' cbv-header--commerce' : ''; ?>">
	<?php if ( 'classic' === CBV_Block_Areas::header_mode() ) : ?>
		<?php if ( CBV_Chrome::is_commerce() ) : ?>
			<?php CBV_Chrome::render_utility_bar(); ?>
	<div class="cbv-container cbv-header__inner">
			<?php
			/**
			 * Fires at the start of the header row, before the brand.
			 *
			 * @since 0.3.0
			 */
			do_action( 'cbv_header_start' );
			?>
		<div class="cbv-header__brand">
			<?php cbv_the_brand(); ?>
		</div>
			<?php if ( has_nav_menu( 'primary' ) ) : ?>
		<nav class="cbv-header__depts" aria-label="<?php esc_attr_e( 'Departments', 'commercebuild-velocity' ); ?>">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'menu_class'     => 'cbv-nav__list cbv-header__dept-list',
						'container'      => false,
						'depth'          => 2,
						'fallback_cb'    => false,
					)
				);
				?>
		</nav>
			<?php endif; ?>
			<?php
			/**
			 * Filter whether the classic commerce header renders a product search.
			 *
			 * @since 0.12.66
			 *
			 * @param bool $enabled Whether the product search is enabled.
			 */
			$cbv_search_enabled = (bool) apply_filters( 'cbv_commerce_header_search_enabled', false );

			if ( $cbv_search_enabled ) {
				$cbv_search_attributes = array(
					'placeholder'   => __( 'Search products...', 'commercebuild-velocity' ),
					'buttonText'    => __( 'Search', 'commercebuild-velocity' ),
					'showButton'    => false,
					'buttonStyle'   => 'icon',
					'showFieldIcon' => true,
				);

				/**
				 * Filter the classic commerce header search block attributes.
				 *
				 * @since 0.12.66
				 *
				 * @param array $attributes Header search block attributes.
				 */
				$cbv_search_attributes = apply_filters(
					'cbv_commerce_header_search_attributes',
					$cbv_search_attributes
				);
				$cbv_search_markup     = render_block(
					array(
						'blockName'    => 'commercebuild/header-search',
						'attrs'        => $cbv_search_attributes,
						'innerBlocks'  => array(),
						'innerHTML'    => '',
						'innerContent' => array(),
					)
				);

				/**
				 * Fires before the classic commerce header product search.
				 *
				 * @since 0.12.66
				 *
				 * @param array $attributes Header search block attributes.
				 */
				do_action( 'cbv_commerce_header_search_before', $cbv_search_attributes );

				/**
				 * Filter the classic commerce header product search markup.
				 *
				 * @since 0.12.66
				 *
				 * @param string $markup     Rendered search block markup.
				 * @param array  $attributes Header search block attributes.
				 */
				$cbv_search_markup = apply_filters(
					'cbv_commerce_header_search_html',
					$cbv_search_markup,
					$cbv_search_attributes
				);

				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped block markup.
				echo $cbv_search_markup;

				/**
				 * Fires after the classic commerce header product search.
				 *
				 * @since 0.12.66
				 *
				 * @param array $attributes Header search block attributes.
				 */
				do_action( 'cbv_commerce_header_search_after', $cbv_search_attributes );
			}
			?>
		<nav class="cbv-header__actions" aria-label="<?php esc_attr_e( 'Account and cart', 'commercebuild-velocity' ); ?>">
			<?php
			$cbv_welcome_name = is_user_logged_in() ? cbv_header_welcome_name() : '';
			if ( '' !== $cbv_welcome_name ) :
				?>
			<span class="cbv-header__welcome">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: customer display name. */
						__( 'Welcome back, %s', 'commercebuild-velocity' ),
						$cbv_welcome_name
					)
				);
				?>
			</span>
			<?php endif; ?>
			<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
				<?php
				/** This filter is documented in blocks/header-account/render.php */
				$cbv_account_icon = apply_filters( 'cbv_header_account_icon', 'user', array() );
				/** This filter is documented in blocks/header-cart/render.php */
				$cbv_cart_icon = apply_filters( 'cbv_header_cart_icon', 'cart', array() );
				$cbv_cart      = function_exists( 'WC' ) ? WC()->cart : null;
				$cbv_count     = ( $cbv_cart && is_object( $cbv_cart ) ) ? (int) $cbv_cart->get_cart_contents_count() : 0;
				$cbv_total     = function_exists( 'cbv_header_cart_total_html' ) ? cbv_header_cart_total_html( $cbv_cart ) : ( ( $cbv_cart && is_object( $cbv_cart ) ) ? $cbv_cart->get_cart_subtotal() : '' );
				?>
				<a class="cbv-header__action cbv-hblock__link cbv-header__account" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">
					<?php if ( $cbv_account_icon && 'none' !== $cbv_account_icon ) : ?>
						<span class="cbv-hblock__icon"><?php cbv_the_icon( $cbv_account_icon ); ?></span>
					<?php endif; ?>
					<span class="cbv-hblock__label"><?php esc_html_e( 'My Account', 'commercebuild-velocity' ); ?></span>
				</a>
				<a class="cbv-header__action cbv-hblock__link cbv-header__cart" href="<?php echo esc_url( wc_get_cart_url() ); ?>">
					<?php if ( $cbv_cart_icon && 'none' !== $cbv_cart_icon ) : ?>
						<span class="cbv-hblock__icon">
							<?php cbv_the_icon( $cbv_cart_icon ); ?>
							<?php if ( $cbv_count > 0 ) : ?>
								<span class="cbv-header__cart-count cbv-header__cart-count--badge"><?php echo esc_html( (string) $cbv_count ); ?></span>
							<?php endif; ?>
						</span>
					<?php endif; ?>
					<span class="cbv-hblock__label">
						<?php esc_html_e( 'Cart', 'commercebuild-velocity' ); ?>
						<?php if ( $cbv_total ) : ?>
							<span class="cbv-header__cart-total"><?php echo wp_kses_post( $cbv_total ); ?></span>
						<?php endif; ?>
					</span>
				</a>
			<?php endif; ?>
			<?php
			/**
			 * Fires inside the header actions nav, after account/cart links
			 * (favorites, quick order, warehouse selector).
			 *
			 * @since 0.3.0
			 */
			do_action( 'cbv_header_actions' );
			?>
			<button type="button" class="cbv-header__menu-toggle" aria-expanded="false" aria-controls="cbv-mobile-nav">
				<span class="screen-reader-text"><?php esc_html_e( 'Open menu', 'commercebuild-velocity' ); ?></span>
				<?php cbv_the_icon( 'menu' ); ?>
			</button>
		</nav>
			<?php
			/**
			 * Fires at the end of the header row.
			 *
			 * @since 0.3.0
			 */
			do_action( 'cbv_header_end' );
			?>
	</div>
			<?php
			/**
			 * Fires before the primary navigation (mega menu slot).
			 *
			 * @since 0.3.0
			 */
			do_action( 'cbv_before_nav' );
			CBV_Chrome::render_category_strip();
			CBV_Chrome::render_ticker();
			/**
			 * Fires after the primary navigation.
			 *
			 * @since 0.3.0
			 */
			do_action( 'cbv_after_nav' );
			?>
	<div id="cbv-mobile-nav" class="cbv-mobile-nav" hidden>
		<div class="cbv-mobile-nav__search">
			<?php
			if ( function_exists( 'get_product_search_form' ) ) {
				get_product_search_form();
			} else {
				get_search_form();
			}
			?>
		</div>
			<?php if ( has_nav_menu( 'primary' ) ) : ?>
		<nav aria-label="<?php esc_attr_e( 'Departments', 'commercebuild-velocity' ); ?>">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'menu_class'     => 'cbv-nav__list',
						'container'      => false,
						'depth'          => 2,
						'fallback_cb'    => false,
					)
				);
				?>
		</nav>
			<?php endif; ?>
			<?php if ( has_nav_menu( 'categories' ) ) : ?>
		<nav aria-label="<?php esc_attr_e( 'Catalog', 'commercebuild-velocity' ); ?>">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'categories',
						'menu_class'     => 'cbv-mobile-nav__cats',
						'container'      => false,
						'depth'          => 1,
						'fallback_cb'    => false,
					)
				);
				?>
		</nav>
			<?php endif; ?>
	</div>
		<?php else : ?>
	<div class="cbv-container cbv-header__inner">
		<?php
		/**
		 * Fires at the start of the header row, before the brand.
		 *
		 * @since 0.3.0
		 */
		do_action( 'cbv_header_start' );
		?>
		<div class="cbv-header__brand">
			<?php cbv_the_brand(); ?>
		</div>
		<div class="cbv-header__search">
			<?php if ( function_exists( 'get_product_search_form' ) ) : ?>
				<?php get_product_search_form(); ?>
			<?php else : ?>
				<?php get_search_form(); ?>
			<?php endif; ?>
		</div>
		<nav class="cbv-header__actions" aria-label="<?php esc_attr_e( 'Account and cart', 'commercebuild-velocity' ); ?>">
			<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
				<?php
				/** This filter is documented in blocks/header-account/render.php */
				$cbv_account_icon = apply_filters( 'cbv_header_account_icon', 'user', array() );
				/** This filter is documented in blocks/header-cart/render.php */
				$cbv_cart_icon = apply_filters( 'cbv_header_cart_icon', 'cart', array() );
				?>
				<a class="cbv-header__action cbv-hblock__link" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">
					<?php if ( $cbv_account_icon && 'none' !== $cbv_account_icon ) : ?>
						<span class="cbv-hblock__icon"><?php cbv_the_icon( $cbv_account_icon ); ?></span>
					<?php endif; ?>
					<span class="cbv-hblock__label"><?php esc_html_e( 'Account', 'commercebuild-velocity' ); ?></span>
				</a>
				<a class="cbv-header__action cbv-hblock__link cbv-header__cart" href="<?php echo esc_url( wc_get_cart_url() ); ?>">
					<?php if ( $cbv_cart_icon && 'none' !== $cbv_cart_icon ) : ?>
						<span class="cbv-hblock__icon">
							<?php cbv_the_icon( $cbv_cart_icon ); ?>
							<?php if ( ! is_null( WC()->cart ) ) : ?>
								<span class="cbv-header__cart-count cbv-header__cart-count--badge"><?php echo esc_html( WC()->cart->get_cart_contents_count() ); ?></span>
							<?php endif; ?>
						</span>
					<?php endif; ?>
					<span class="cbv-hblock__label"><?php esc_html_e( 'Cart', 'commercebuild-velocity' ); ?></span>
				</a>
			<?php endif; ?>
			<?php
			/**
			 * Fires inside the header actions nav, after account/cart links
			 * (favorites, quick order, warehouse selector).
			 *
			 * @since 0.3.0
			 */
			do_action( 'cbv_header_actions' );
			?>
		</nav>
		<?php
		/**
		 * Fires at the end of the header row.
		 *
		 * @since 0.3.0
		 */
		do_action( 'cbv_header_end' );
		?>
	</div>
		<?php
		/**
		 * Fires before the primary navigation (mega menu slot).
		 *
		 * @since 0.3.0
		 */
		do_action( 'cbv_before_nav' );
		?>
		<?php if ( has_nav_menu( 'primary' ) ) : ?>
		<nav class="cbv-nav" aria-label="<?php esc_attr_e( 'Primary', 'commercebuild-velocity' ); ?>">
			<div class="cbv-container">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'menu_class'     => 'cbv-nav__list',
						'container'      => false,
						'depth'          => 2,
					)
				);
				?>
			</div>
		</nav>
	<?php endif; ?>
		<?php
		/**
		 * Fires after the primary navigation.
		 *
		 * @since 0.3.0
		 */
		do_action( 'cbv_after_nav' );
		?>
		<?php endif; ?>
	<?php endif; ?>
	<?php CBV_Block_Areas::render( 'header' ); ?>
</header>
<?php
/**
 * Fires after the site header, before main content.
 *
 * @since 0.3.0
 */
do_action( 'cbv_after_header' );
?>
<?php
/**
 * Fires immediately before the main content element.
 *
 * @since 0.8.0
 */
do_action( 'cbv_before_main' );
?>
<main id="cbv-main" class="cbv-main">
<?php
/**
 * Fires just inside the main content element.
 *
 * @since 0.8.0
 */
do_action( 'cbv_main_start' );
?>
