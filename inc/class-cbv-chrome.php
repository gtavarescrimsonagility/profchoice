<?php
/**
 * Optional B2B storefront chrome: utility bar, category strip, ticker,
 * columned footer, and homepage section data.
 *
 * Classic sites keep the original header/footer. Commerce layout is opt-in
 * via the Header layout design setting (or the `cbv_design_value` filter).
 *
 * @package Commercebuild_Velocity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Commerce chrome helpers.
 *
 * @since 0.12.0
 */
class CBV_Chrome {

	/**
	 * Register hooks.
	 *
	 * @since 0.12.0
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'customize_register', array( $this, 'add_controls' ) );
		add_filter( 'body_class', array( $this, 'body_class' ) );
	}

	/**
	 * Whether the commerce (multi-row) chrome is active.
	 *
	 * @since 0.12.0
	 *
	 * @return bool
	 */
	public static function is_commerce() {
		return 'commerce' === CBV_Design::get( 'header_layout' );
	}

	/**
	 * Storefront contact + ticker settings.
	 *
	 * @since 0.12.0
	 *
	 * @return array slug => array( label, default, type ).
	 */
	public static function settings() {
		/**
		 * Filter storefront chrome settings.
		 *
		 * @since 0.12.0
		 *
		 * @param array $settings slug => array( label, default, type ).
		 */
		return apply_filters(
			'cbv_chrome_settings',
			array(
				'tagline'      => array( __( 'Utility tagline', 'commercebuild-velocity' ), '', 'text' ),
				'location'     => array( __( 'Location', 'commercebuild-velocity' ), '', 'text' ),
				'phone'        => array( __( 'Phone', 'commercebuild-velocity' ), '', 'text' ),
				'email'        => array( __( 'Sales email', 'commercebuild-velocity' ), '', 'email' ),
				'address'      => array( __( 'Street address', 'commercebuild-velocity' ), '', 'textarea' ),
				'footer_blurb' => array( __( 'Footer blurb', 'commercebuild-velocity' ), '', 'textarea' ),
				'ticker'       => array( __( 'Promo ticker (one item per line)', 'commercebuild-velocity' ), '', 'textarea' ),
			)
		);
	}

	/**
	 * A chrome setting value.
	 *
	 * @since 0.12.0
	 *
	 * @param string $slug Setting slug.
	 * @return string
	 */
	public static function get( $slug ) {
		$settings = self::settings();
		$default  = isset( $settings[ $slug ] ) ? $settings[ $slug ][1] : '';
		$value    = (string) get_theme_mod( 'cbv_chrome_' . $slug, $default );

		/**
		 * Filter a chrome setting.
		 *
		 * @since 0.12.0
		 *
		 * @param string $value Setting value.
		 * @param string $slug  Setting slug.
		 */
		return apply_filters( 'cbv_chrome_value', $value, $slug );
	}

	/**
	 * Ticker items, one per line.
	 *
	 * @since 0.12.0
	 *
	 * @return string[]
	 */
	public static function ticker_items() {
		$raw   = self::get( 'ticker' );
		$items = array_values(
			array_filter(
				array_map( 'trim', preg_split( '/\r\n|\r|\n/', $raw ) )
			)
		);

		/**
		 * Filter promo ticker items.
		 *
		 * @since 0.12.0
		 *
		 * @param string[] $items Ticker lines.
		 */
		return apply_filters( 'cbv_ticker_items', $items );
	}

	/**
	 * Customizer section for chrome copy.
	 *
	 * @since 0.12.0
	 *
	 * @param WP_Customize_Manager $wp_customize Customizer manager.
	 * @return void
	 */
	public function add_controls( $wp_customize ) {
		$wp_customize->add_section(
			'cbv_chrome',
			array(
				'title'       => __( 'Storefront chrome', 'commercebuild-velocity' ),
				'priority'    => 32,
				'description' => __( 'Utility bar, ticker, and footer contact used by the commerce header layout.', 'commercebuild-velocity' ),
			)
		);

		foreach ( self::settings() as $slug => $setting ) {
			$wp_customize->add_setting(
				'cbv_chrome_' . $slug,
				array(
					'default'           => $setting[1],
					'sanitize_callback' => ( 'email' === $setting[2] ) ? 'sanitize_email' : ( ( 'textarea' === $setting[2] ) ? 'sanitize_textarea_field' : 'sanitize_text_field' ),
				)
			);

			$wp_customize->add_control(
				'cbv_chrome_' . $slug,
				array(
					'label'   => $setting[0],
					'section' => 'cbv_chrome',
					'type'    => ( 'textarea' === $setting[2] ) ? 'textarea' : 'text',
				)
			);
		}
	}

	/**
	 * Body class when commerce chrome is on.
	 *
	 * @since 0.12.0
	 *
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public function body_class( $classes ) {
		if ( self::is_commerce() ) {
			$classes[] = 'cbv-layout-commerce';
		}

		if ( is_user_logged_in() ) {
			$classes[] = 'cbv-is-logged-in';
		} else {
			$classes[] = 'cbv-is-guest';
		}

		return $classes;
	}

	/**
	 * Utility bar (tagline, location, phone, email, utility menu).
	 *
	 * @since 0.12.0
	 *
	 * @return void
	 */
	public static function render_utility_bar() {
		$tagline  = self::get( 'tagline' );
		$location = self::get( 'location' );
		$phone    = self::get( 'phone' );
		$email    = self::get( 'email' );
		$has_menu = has_nav_menu( 'utility' );

		if ( '' === $tagline && '' === $location && '' === $phone && '' === $email && ! $has_menu ) {
			return;
		}

		echo '<div class="cbv-utility">';
		echo '<div class="cbv-container cbv-utility__inner">';
		echo '<div class="cbv-utility__meta">';

		if ( '' !== $tagline ) {
			echo '<span class="cbv-utility__tagline">' . esc_html( $tagline ) . '</span>';
		}

		if ( '' !== $location ) {
			echo '<span class="cbv-utility__item">' . esc_html( $location ) . '</span>';
		}

		if ( '' !== $phone ) {
			$tel = preg_replace( '/[^0-9+]/', '', $phone );
			printf(
				'<a class="cbv-utility__item" href="%s">%s</a>',
				esc_url( 'tel:' . $tel ),
				esc_html( $phone )
			);
		}

		if ( '' !== $email ) {
			printf(
				'<a class="cbv-utility__item" href="%s">%s</a>',
				esc_url( 'mailto:' . $email ),
				esc_html( $email )
			);
		}

		echo '</div>';

		if ( $has_menu ) {
			wp_nav_menu(
				array(
					'theme_location' => 'utility',
					'menu_class'     => 'cbv-utility__links',
					'container'      => false,
					'depth'          => 1,
					'fallback_cb'    => false,
				)
			);
		}

		echo '</div></div>';
	}

	/**
	 * Category strip under the header row.
	 *
	 * @since 0.12.0
	 *
	 * @return void
	 */
	public static function render_category_strip() {
		if ( ! has_nav_menu( 'categories' ) ) {
			return;
		}

		echo '<nav class="cbv-cats" aria-label="' . esc_attr__( 'Catalog', 'commercebuild-velocity' ) . '">';
		echo '<div class="cbv-container">';
		wp_nav_menu(
			array(
				'theme_location' => 'categories',
				'menu_class'     => 'cbv-cats__list',
				'container'      => false,
				'depth'          => 2,
				'fallback_cb'    => false,
			)
		);
		echo '</div></nav>';
	}

	/**
	 * Promo ticker.
	 *
	 * @since 0.12.0
	 *
	 * @return void
	 */
	public static function render_ticker() {
		$items = self::ticker_items();

		if ( empty( $items ) ) {
			return;
		}

		echo '<div class="cbv-ticker"><div class="cbv-container cbv-ticker__inner">';

		foreach ( $items as $item ) {
			echo '<span class="cbv-ticker__item">' . wp_kses_post( $item ) . '</span>';
		}

		echo '</div></div>';
	}

	/**
	 * Columned footer (commerce layout).
	 *
	 * @since 0.12.0
	 *
	 * @return void
	 */
	public static function render_footer() {
		$locations = array(
			'footer_shop'    => __( 'Shop', 'commercebuild-velocity' ),
			'footer_more'    => __( 'More categories', 'commercebuild-velocity' ),
			'footer_account' => __( 'Account', 'commercebuild-velocity' ),
			'footer_company' => __( 'Company & support', 'commercebuild-velocity' ),
		);

		echo '<div class="cbv-container cbv-footer__grid">';
		echo '<div class="cbv-footer__brand">';

		cbv_the_brand( 'footer' );

		$tagline = self::get( 'tagline' );
		$blurb   = self::get( 'footer_blurb' );
		$address = self::get( 'address' );
		$phone   = self::get( 'phone' );
		$email   = self::get( 'email' );

		if ( '' !== $tagline ) {
			echo '<p class="cbv-footer__tagline">' . esc_html( $tagline ) . '</p>';
		}

		if ( '' !== $blurb ) {
			echo '<p class="cbv-footer__blurb">' . esc_html( $blurb ) . '</p>';
		}

		if ( '' !== $address ) {
			echo '<p class="cbv-footer__address">' . nl2br( esc_html( $address ) ) . '</p>';
		}

		if ( '' !== $phone ) {
			echo '<p class="cbv-footer__contact"><span>' . esc_html__( 'Phone:', 'commercebuild-velocity' ) . '</span> ' . esc_html( $phone ) . '</p>';
		}

		if ( '' !== $email ) {
			echo '<p class="cbv-footer__contact"><span>' . esc_html__( 'Sales:', 'commercebuild-velocity' ) . '</span> ' . esc_html( $email ) . '</p>';
		}

		echo '</div>';

		foreach ( $locations as $location => $label ) {
			if ( ! has_nav_menu( $location ) ) {
				continue;
			}

			echo '<nav class="cbv-footer__col" aria-label="' . esc_attr( $label ) . '">';
			echo '<h2 class="cbv-footer__heading">' . esc_html( $label ) . '</h2>';
			wp_nav_menu(
				array(
					'theme_location' => $location,
					'menu_class'     => 'cbv-footer__col-list',
					'container'      => false,
					'depth'          => 1,
					'fallback_cb'    => false,
				)
			);
			echo '</nav>';
		}

		echo '</div>';
	}

	/**
	 * Marketing homepage section data. Child themes fill this in.
	 *
	 * @since 0.12.0
	 *
	 * @return array
	 */
	public static function marketing_home() {
		$shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
		$data = array(
			'eyebrow'         => '',
			'heading'         => get_bloginfo( 'name' ),
			'text'            => get_bloginfo( 'description' ),
			'primary_label'   => __( 'Shop now', 'commercebuild-velocity' ),
			'primary_url'     => $shop,
			'secondary_label' => __( 'Log in to your account', 'commercebuild-velocity' ),
			'secondary_url'   => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url(),
			'image_url'       => '',
			'image_alt'       => '',
			'stats'           => array(),
			'stat_cta_label'  => '',
			'stat_cta_url'    => '',
			'bestsellers'     => array(
				'heading' => __( 'Best Sellers', 'commercebuild-velocity' ),
				'link'    => $shop,
				'limit'   => 7,
			),
			'deals'           => array(
				'kicker'  => __( 'Your deals & savings', 'commercebuild-velocity' ),
				'heading' => __( "This Month's Specials", 'commercebuild-velocity' ),
				'text'    => '',
				'link'    => $shop,
				'tiles'   => array(),
				'pills'   => array(),
			),
			'categories'      => array(
				'kicker'  => __( 'Browse all categories', 'commercebuild-velocity' ),
				'heading' => __( 'Shop by Category', 'commercebuild-velocity' ),
				'text'    => '',
				'link'    => $shop,
				'limit'   => 16,
			),
			'story'           => array(
				'eyebrow' => '',
				'heading' => '',
				'text'    => '',
				'button'  => '',
				'url'     => '',
				'cards'   => array(),
			),
			'cta'             => array(
				'heading' => '',
				'text'    => '',
				'checks'  => array(),
				'buttons' => array(),
			),
			'facts'           => array(),
		);

		/**
		 * Filter marketing homepage data.
		 *
		 * @since 0.12.0
		 *
		 * @param array $data Homepage sections.
		 */
		return apply_filters( 'cbv_marketing_home', $data );
	}

	/**
	 * Logged-in dashboard homepage data.
	 *
	 * @since 0.12.0
	 *
	 * @return array
	 */
	public static function dashboard_home() {
		$name = function_exists( 'cbv_header_welcome_name' ) ? cbv_header_welcome_name() : '';
		$data = array(
			'heading'     => $name ? sprintf( /* translators: %s: customer name. */ __( 'Welcome back, %s', 'commercebuild-velocity' ), $name ) : __( 'Welcome back', 'commercebuild-velocity' ),
			'subheading'  => '',
			'actions'     => array(),
			'search'      => true,
			'show_cart'   => true,
			'promo_cards' => array(),
			'promo_order' => 'before_panels',
			'orders'      => array(),
			'guide'       => array(
				'heading' => __( 'My Order Guide', 'commercebuild-velocity' ),
				'link'    => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/' ),
				'items'   => array(),
			),
			'categories'  => array(
				'kicker'  => '',
				'heading' => __( 'Shop by Category', 'commercebuild-velocity' ),
				'text'    => '',
				'link'    => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ),
			),
		);

		if ( function_exists( 'wc_get_orders' ) && is_user_logged_in() ) {
			$orders = wc_get_orders(
				array(
					'customer_id' => get_current_user_id(),
					'limit'       => 3,
					'orderby'     => 'date',
					'order'       => 'DESC',
					'return'      => 'objects',
				)
			);

			/**
			 * Filter the dashboard recent-order list.
			 *
			 * @since 0.12.3
			 *
			 * @param array $orders Recent WC_Order objects.
			 */
			$orders = apply_filters( 'cbv_dashboard_orders', $orders );

			if ( ! empty( $orders ) && is_a( $orders[0], 'WC_Order' ) ) {
				$last               = $orders[0];
				$data['subheading'] = sprintf(
					/* translators: 1: order date, 2: order total, 3: status. */
					__( 'Last order: %1$s · %2$s · %3$s', 'commercebuild-velocity' ),
					( $last->get_date_created() ) ? wc_format_datetime( $last->get_date_created() ) : '',
					wp_strip_all_tags( $last->get_formatted_order_total() ),
					wc_get_order_status_name( $last->get_status() )
				);

				foreach ( $orders as $order ) {
					if ( ! is_a( $order, 'WC_Order' ) ) {
						continue;
					}

					$reorder = '';
					if ( function_exists( 'wp_nonce_url' ) && function_exists( 'wc_get_cart_url' ) ) {
						$reorder = wp_nonce_url(
							add_query_arg( 'order_again', $order->get_id(), wc_get_cart_url() ),
							'woocommerce-order_again'
						);
					}

					$data['orders'][] = array(
						'number'      => $order->get_order_number(),
						'status'      => wc_get_order_status_name( $order->get_status() ),
						'status_slug' => $order->get_status(),
						'date'        => $order->get_date_created() ? wc_format_datetime( $order->get_date_created() ) : '',
						'total'       => $order->get_formatted_order_total(),
						'url'         => $order->get_view_order_url(),
						'reorder_url' => $reorder,
					);
				}
			}
		}

		/**
		 * Filter logged-in dashboard homepage data.
		 *
		 * @since 0.12.0
		 *
		 * @param array $data Dashboard sections.
		 */
		return apply_filters( 'cbv_dashboard_home', $data );
	}
}
