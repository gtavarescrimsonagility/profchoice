<?php
/**
 * Shared rendering helpers.
 *
 * One product card renderer used by every block, template, and loop, so the
 * card markup (and its per-context toggles) lives in exactly one place.
 *
 * @package Commercebuild_Velocity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether price HTML is a $0 / $0.00 amount.
 *
 * @since 0.12.28
 *
 * @param string $html Price HTML.
 * @return bool
 */
function cbv_is_zero_price_html( $html ) {
	$plain = strtolower( wp_strip_all_tags( html_entity_decode( (string) $html, ENT_QUOTES, 'UTF-8' ) ) );

	if ( false !== strpos( $plain, 'contact for pricing' ) ) {
		return false;
	}

	$num = preg_replace( '/[^0-9.]/', '', str_replace( ',', '', $plain ) );

	if ( '' === $num ) {
		return false;
	}

	return 0.0 === (float) $num;
}

/**
 * Print the site brand (custom logo or site name).
 *
 * @since 0.12.1
 *
 * @param string $context header or footer. Child themes can swap the mark.
 * @return void
 */
function cbv_the_brand( $context = 'header' ) {
	$context = ( 'footer' === $context ) ? 'footer' : 'header';

	if ( has_custom_logo() ) {
		if ( 'header' === $context ) {
			the_custom_logo();
			return;
		}

		echo apply_filters( 'cbv_header_brand_html', get_custom_logo(), $context ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Filtered HTML is escaped at the source.
		return;
	}

	$fallback = sprintf(
		'<a class="cbv-header__site-name" href="%s">%s</a>',
		esc_url( home_url( '/' ) ),
		esc_html( get_bloginfo( 'name' ) )
	);

	/**
	 * Filter header/footer brand HTML when no Customizer logo is set.
	 *
	 * @since 0.12.1
	 * @since 0.12.2 Second parameter $context (header or footer).
	 *
	 * @param string $fallback Site-name link markup.
	 * @param string $context  header or footer.
	 */
	echo apply_filters( 'cbv_header_brand_html', $fallback, $context ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Filtered HTML is escaped at the source.
}

/**
 * Name used in the commerce header welcome line.
 *
 * Prefers first name. Emails are omitted so the departments nav does not wrap.
 *
 * @since 0.12.2
 *
 * @param WP_User|null $user User. Defaults to the current user.
 * @return string Display name, or empty to hide the welcome line.
 */
function cbv_header_welcome_name( $user = null ) {
	if ( ! $user instanceof WP_User ) {
		$user = wp_get_current_user();
	}

	$name = trim( (string) $user->first_name );

	if ( '' === $name ) {
		$name = trim( (string) get_user_meta( $user->ID, 'billing_first_name', true ) );
	}

	if ( '' === $name ) {
		$name = trim( (string) $user->display_name );
	}

	if ( '' === $name || is_email( $name ) ) {
		$name = '';
	}

	/**
	 * Filter the header welcome name.
	 *
	 * Return an empty string to hide the welcome line.
	 *
	 * @since 0.12.2
	 *
	 * @param string  $name Welcome name.
	 * @param WP_User $user Current user.
	 */
	return (string) apply_filters( 'cbv_header_welcome_name', $name, $user );
}

/**
 * Header cart subtotal HTML, or empty when the total is $0.
 *
 * POA carts render as $0.00, which is misleading next to a non-zero count.
 *
 * @since 0.12.19
 *
 * @param WC_Cart|null $cart Cart.
 * @return string
 */
function cbv_header_cart_total_html( $cart = null ) {
	if ( ! $cart && function_exists( 'WC' ) && WC()->cart ) {
		$cart = WC()->cart;
	}

	if ( ! $cart || ! is_object( $cart ) ) {
		return '';
	}

	$amount = (float) $cart->get_displayed_subtotal();

	if ( $amount <= 0 ) {
		$html = '';
	} else {
		$html = $cart->get_cart_subtotal();
	}

	/**
	 * Filter the header cart total HTML.
	 *
	 * Return an empty string to hide the amount.
	 *
	 * @since 0.12.19
	 *
	 * @param string       $html Header total HTML.
	 * @param WC_Cart|null $cart Cart.
	 */
	return (string) apply_filters( 'cbv_header_cart_total_html', $html, $cart );
}

/**
 * Pack / UOM label for cart and dashboard rows.
 *
 * @since 0.12.13
 *
 * @param WC_Product $product Product.
 * @return string
 */
function cbv_product_pack( $product ) {
	$pack = '';

	if ( is_object( $product ) && method_exists( $product, 'get_attribute' ) ) {
		$pack = trim( (string) $product->get_attribute( 'pack' ) );
	}

	if ( '' === $pack && is_object( $product ) && method_exists( $product, 'get_meta' ) ) {
		$pack = trim( (string) $product->get_meta( 'uom' ) );
	}

	/**
	 * Filter the pack label shown in cart and dashboard rows.
	 *
	 * @since 0.12.13
	 *
	 * @param string     $pack    Pack label.
	 * @param WC_Product $product Product.
	 */
	return (string) apply_filters( 'cbv_product_pack', $pack, $product );
}

/**
 * Price suffix like "avg / case" when the product has a numeric price and UOM.
 *
 * @since 0.12.21
 *
 * @param WC_Product $product Product.
 * @return string
 */
function cbv_price_suffix( $product ) {
	if ( ! $product instanceof WC_Product ) {
		return '';
	}

	$price = $product->get_price();

	if ( '' === (string) $price || ! is_numeric( $price ) ) {
		return '';
	}

	$plain = strtolower( wp_strip_all_tags( (string) $product->get_price_html() ) );

	if ( false !== strpos( $plain, 'contact' ) || false !== strpos( $plain, 'application' ) ) {
		return '';
	}

	$uom = apply_filters( 'cbv_product_card_uom', (string) $product->get_meta( 'uom' ), $product );

	if ( '' === $uom ) {
		$uom = cbv_product_pack( $product );
	}

	if ( '' === $uom ) {
		return '';
	}

	$label = strtolower( $uom );
	$map   = array(
		'cs' => 'case',
		'ca' => 'case',
		'ea' => 'ea',
		'kg' => 'kg',
	);

	if ( isset( $map[ $label ] ) ) {
		$label = $map[ $label ];
	}

	$suffix = sprintf(
		/* translators: %s: unit of measure. */
		__( 'avg / %s', 'commercebuild-velocity' ),
		$label
	);

	/**
	 * Filter the price UOM suffix.
	 *
	 * Return an empty string to hide it.
	 *
	 * @since 0.12.21
	 *
	 * @param string     $suffix  Suffix text.
	 * @param WC_Product $product Product.
	 * @param string     $uom     Raw UOM.
	 */
	return (string) apply_filters( 'cbv_price_suffix', $suffix, $product, $uom );
}

/**
 * Print the trade-account CTA band.
 *
 * @since 0.12.21
 *
 * @param array|null $cta CTA data. Defaults to the marketing homepage CTA.
 * @return void
 */
function cbv_the_cta_band( $cta = null ) {
	if ( null === $cta ) {
		$home = class_exists( 'CBV_Chrome' ) ? CBV_Chrome::marketing_home() : array();
		$cta  = isset( $home['cta'] ) && is_array( $home['cta'] ) ? $home['cta'] : array();
	}

	/**
	 * Filter CTA band data before render.
	 *
	 * @since 0.12.21
	 *
	 * @param array $cta heading, text, checks, buttons.
	 */
	$cbv_cta = apply_filters( 'cbv_cta_band', $cta );

	include CBV_DIR . 'template-parts/cta-band.php';
}

/**
 * Print homepage category tiles (guest and dashboard).
 *
 * @since 0.12.3
 *
 * @param array $tiles Tiles with name/url and optional image, thumbnail_id, badge, subtitle.
 * @return void
 */
function cbv_the_home_category_tiles( $tiles ) {
	if ( empty( $tiles ) || ! is_array( $tiles ) ) {
		return;
	}

	/**
	 * Fires before the homepage category tile grid.
	 *
	 * @since 0.12.3
	 *
	 * @param array $tiles Tiles about to render.
	 */
	do_action( 'cbv_home_category_tiles_before', $tiles );

	echo '<div class="cbv-cat-photo-grid">';

	foreach ( $tiles as $tile ) {
		if ( empty( $tile['url'] ) || empty( $tile['name'] ) ) {
			continue;
		}

		ob_start();
		echo '<a class="cbv-cat-photo" href="' . esc_url( $tile['url'] ) . '">';
		echo '<span class="cbv-cat-photo__media">';
		if ( ! empty( $tile['badge'] ) ) {
			echo '<span class="cbv-cat-photo__badge">' . esc_html( $tile['badge'] ) . '</span>';
		}
		if ( ! empty( $tile['thumbnail_id'] ) ) {
			echo wp_get_attachment_image( (int) $tile['thumbnail_id'], 'cbv-tile', false, array( 'loading' => 'lazy' ) );
		} elseif ( ! empty( $tile['image'] ) ) {
			printf(
				'<img src="%s" alt="" loading="lazy" decoding="async" width="320" height="220">',
				esc_url( $tile['image'] )
			);
		}
		echo '</span>';
		echo '<span class="cbv-cat-photo__name">' . esc_html( $tile['name'] ) . '</span>';
		if ( ! empty( $tile['subtitle'] ) ) {
			echo '<span class="cbv-cat-photo__sub">' . esc_html( $tile['subtitle'] ) . '</span>';
		}
		echo '</a>';

		$html = ob_get_clean();

		/**
		 * Filter one homepage category tile's HTML.
		 *
		 * @since 0.12.3
		 *
		 * @param string $html Tile markup.
		 * @param array  $tile Tile data.
		 */
		echo apply_filters( 'cbv_home_category_tile_html', $html, $tile ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built with escaped values above.
	}

	echo '</div>';

	/**
	 * Fires after the homepage category tile grid.
	 *
	 * @since 0.12.3
	 *
	 * @param array $tiles Tiles that rendered.
	 */
	do_action( 'cbv_home_category_tiles_after', $tiles );
}

/**
 * Print dashboard promotional cards and their trailing extension point.
 *
 * @since 0.12.65
 *
 * @param array $dashboard Dashboard section data.
 * @return void
 */
function cbv_the_dashboard_promos( $dashboard ) {
	$cards = isset( $dashboard['promo_cards'] ) && is_array( $dashboard['promo_cards'] )
		? $dashboard['promo_cards']
		: array();

	if ( ! empty( $cards ) ) {
		echo '<section class="cbv-deals cbv-deals--compact">';
		echo '<div class="cbv-container cbv-deals__pills">';

		foreach ( $cards as $card ) {
			if ( empty( $card['url'] ) || empty( $card['title'] ) ) {
				continue;
			}

			$classes = 'cbv-deals__pill';

			if ( ! empty( $card['image'] ) ) {
				$classes .= ' cbv-deals__pill--photo';
			}

			echo '<a class="' . esc_attr( $classes ) . '" href="' . esc_url( $card['url'] ) . '">';

			if ( ! empty( $card['image'] ) ) {
				echo '<img src="' . esc_url( $card['image'] ) . '" alt="" loading="lazy" decoding="async">';
			}

			echo '<div>';

			if ( ! empty( $card['kicker'] ) ) {
				echo '<p class="cbv-deals__kicker">' . esc_html( $card['kicker'] ) . '</p>';
			}

			echo '<p class="cbv-deals__title">' . esc_html( $card['title'] ) . '</p>';

			if ( ! empty( $card['text'] ) ) {
				echo '<p class="cbv-deals__text">' . esc_html( $card['text'] ) . '</p>';
			}

			echo '</div>';

			if ( ! empty( $card['button'] ) ) {
				echo '<span class="cbv-button cbv-button--ghost">' . esc_html( $card['button'] ) . '</span>';
			}

			echo '</a>';
		}

		echo '</div>';
		echo '</section>';
	}

	/**
	 * Fires after dashboard promo cards at their configured position.
	 *
	 * @since 0.12.3
	 *
	 * @param array $dashboard Dashboard data.
	 */
	do_action( 'cbv_dashboard_after_promos', $dashboard );
}

/**
 * Print a dashboard cart quantity stepper that posts to WooCommerce cart.
 *
 * @since 0.12.3
 *
 * @param string $item_key Cart item key.
 * @param int    $qty      Current quantity.
 * @return void
 */
function cbv_the_dash_qty( $item_key, $qty ) {
	$item_key = (string) $item_key;
	$qty      = max( 0, (int) $qty );
	$cart_url = home_url( '/' );

	if ( function_exists( 'wc_get_cart_url' ) && ! is_front_page() ) {
		$cart_url = wc_get_cart_url();
	}

	echo '<div class="cbv-dash-qty">';
	cbv_the_dash_qty_button( $cart_url, $item_key, max( 0, $qty - 1 ), '-', __( 'Decrease quantity', 'commercebuild-velocity' ) );
	echo '<span class="cbv-dash-qty__value">' . esc_html( (string) $qty ) . '</span>';
	cbv_the_dash_qty_button( $cart_url, $item_key, $qty + 1, '+', __( 'Increase quantity', 'commercebuild-velocity' ) );
	echo '</div>';
}

/**
 * Print one dashboard qty submit button.
 *
 * @since 0.12.3
 *
 * @param string $cart_url Cart form action.
 * @param string $item_key Cart item key.
 * @param int    $next_qty Quantity to post.
 * @param string $label    Button face.
 * @param string $aria     Accessible name.
 * @return void
 */
function cbv_the_dash_qty_button( $cart_url, $item_key, $next_qty, $label, $aria ) {
	echo '<form class="cbv-dash-qty__form" method="post" action="' . esc_url( $cart_url ) . '">';
	if ( function_exists( 'wp_create_nonce' ) ) {
		echo '<input type="hidden" name="woocommerce-cart-nonce" value="'
			. esc_attr( wp_create_nonce( 'woocommerce-cart' ) )
			. '">';
	}
	echo '<input type="hidden" name="cart[' . esc_attr( $item_key ) . '][qty]" value="' . esc_attr( (string) (int) $next_qty ) . '">';
	echo '<button type="submit" name="update_cart" value="1" class="cbv-dash-qty__btn" aria-label="' . esc_attr( $aria ) . '">' . esc_html( $label ) . '</button>';
	echo '</form>';
}

/**
 * Render a single product card.
 *
 * @since 0.1.0
 *
 * @param int   $product_id Product ID.
 * @param array $args {
 *     Optional card toggles.
 *
 *     @type bool $show_price       Default true.
 *     @type bool $show_sku         Show the ERP original_sku (Woo SKU fallback). Default false.
 *     @type bool $show_uom         Show the unit of measure (uom meta). Default false.
 *     @type bool $show_add_to_cart Default true.
 *     @type bool $lazy             Lazy-load the image. Default true.
 * }
 * @return string Card HTML.
 */
function cbv_product_card( $product_id, array $args = array() ) {
	$product = wc_get_product( $product_id );

	if ( ! $product || ! $product->is_visible() ) {
		return '';
	}

	$args = wp_parse_args(
		$args,
		array(
			'show_price'        => true,
			'show_sku'          => false,
			'show_uom'          => false,
			'show_brand'        => true,
			'show_pack'         => true,
			'show_stock'        => true,
			'show_add_to_cart'  => true,
			'show_category'     => false,
			'show_price_suffix' => false,
			'lazy'              => true,
		)
	);

	/**
	 * Filter product card toggles (e.g. hide prices for guests).
	 *
	 * @since 0.1.0
	 *
	 * @param array      $args    Card args.
	 * @param WC_Product $product Product object.
	 */
	$args = apply_filters( 'cbv_product_card_args', $args, $product );

	$image = $product->get_image(
		'woocommerce_thumbnail',
		array( 'loading' => $args['lazy'] ? 'lazy' : 'eager' )
	);

	if ( ! $product->get_image_id() ) {
		/**
		 * Filter product-card image HTML when the product has no featured image.
		 *
		 * @since 0.12.30
		 *
		 * @param string     $html    Empty string to keep the Woo placeholder.
		 * @param WC_Product $product Product.
		 * @param string     $size    Image size slug.
		 */
		$fallback = apply_filters( 'cbv_product_fallback_image_html', '', $product, 'woocommerce_thumbnail' );

		if ( is_string( $fallback ) && '' !== $fallback ) {
			$image = $fallback;
		}
	}

	// ERP sites store the real SKU in original_sku; the Woo SKU can be a
	// UOM-variant suffix. Prefer the original, fall back to the Woo SKU.
	$sku = (string) $product->get_meta( 'original_sku' );

	if ( '' === $sku ) {
		$sku = (string) $product->get_sku();
	}

	/**
	 * Filter the SKU shown on product cards.
	 *
	 * @since 0.2.2
	 *
	 * @param string     $sku     Resolved SKU.
	 * @param WC_Product $product Product object.
	 */
	$sku = apply_filters( 'cbv_product_card_sku', $sku, $product );

	/**
	 * Filter the unit of measure shown on product cards.
	 *
	 * @since 0.2.2
	 *
	 * @param string     $uom     UOM string (uom product meta).
	 * @param WC_Product $product Product object.
	 */
	$uom = apply_filters( 'cbv_product_card_uom', (string) $product->get_meta( 'uom' ), $product );

	$brand = '';

	if ( $args['show_brand'] ) {
		$brand = (string) $product->get_attribute( 'brand' );

		if ( '' === $brand && taxonomy_exists( 'product_brand' ) ) {
			$brands = get_the_terms( $product->get_id(), 'product_brand' );

			if ( is_array( $brands ) && ! is_wp_error( $brands ) && ! empty( $brands ) ) {
				$brand = $brands[0]->name;
			}
		}
	}

	/**
	 * Filter the brand shown on product cards.
	 *
	 * @since 0.12.0
	 *
	 * @param string     $brand   Brand name.
	 * @param WC_Product $product Product object.
	 */
	$brand = apply_filters( 'cbv_product_card_brand', $brand, $product );

	if ( '' === $brand && ! empty( $args['show_category'] ) ) {
		$terms = get_the_terms( $product->get_id(), 'product_cat' );

		if ( is_array( $terms ) && ! is_wp_error( $terms ) && ! empty( $terms ) ) {
			$brand = $terms[0]->name;
		}
	}

	$pack = '';

	if ( $args['show_pack'] ) {
		$pack = (string) $product->get_attribute( 'pack' );

		if ( '' === $pack && '' !== $uom ) {
			$pack = $uom;
		}

		if ( '' === $pack && $product->has_weight() ) {
			$pack = wc_format_weight( $product->get_weight() );
		}
	}

	/**
	 * Filter the pack size shown on product cards.
	 *
	 * @since 0.12.0
	 *
	 * @param string     $pack    Pack string.
	 * @param WC_Product $product Product object.
	 */
	$pack = apply_filters( 'cbv_product_card_pack', $pack, $product );

	$show_sku         = $args['show_sku'] && '' !== $sku;
	$show_uom         = $args['show_uom'] && '' !== $uom;
	$show_brand       = ( $args['show_brand'] || ! empty( $args['show_category'] ) ) && '' !== $brand;
	$show_pack        = $args['show_pack'] && '' !== $pack;
	$show_stock       = ! empty( $args['show_stock'] );
	$show_add_to_cart = ! empty( $args['show_add_to_cart'] )
		&& (
			$product->is_type( array( 'external', 'grouped' ) )
			|| ( $product->is_purchasable() && $product->is_in_stock() )
		);

	/**
	 * Filter whether a product card renders its Add to cart control.
	 *
	 * @since 0.12.47
	 *
	 * @param bool       $show_add_to_cart Whether the control should render.
	 * @param WC_Product $product          Product object.
	 * @param array      $args             Card args.
	 */
	$show_add_to_cart = (bool) apply_filters( 'cbv_product_card_purchase_enabled', $show_add_to_cart, $product, $args );

	ob_start();
	?>
	<article class="cbv-card">
		<?php
		/**
		 * Fires at the top of a product card (badges, favorite buttons).
		 *
		 * @since 0.3.0
		 *
		 * @param WC_Product $product Product object.
		 * @param array      $args    Card args.
		 */
		do_action( 'cbv_product_card_top', $product, $args );
		?>
		<a class="cbv-card__media" href="<?php echo esc_url( $product->get_permalink() ); ?>">
			<?php echo wp_kses_post( $image ); ?>
			<?php
			$cbv_badges = array();

			if ( $product->is_on_sale() ) {
				$cbv_badges[] = array( 'sale', __( 'Special', 'commercebuild-velocity' ) );
			}

			if ( $product->is_featured() ) {
				$cbv_badges[] = array( 'featured', __( 'Special', 'commercebuild-velocity' ) );
			}

			$created = strtotime( $product->get_date_created() ? $product->get_date_created()->date( 'Y-m-d H:i:s' ) : '' );

			if ( $created && ( time() - $created ) < MONTH_IN_SECONDS ) {
				$cbv_badges[] = array( 'new', __( 'New', 'commercebuild-velocity' ) );
			}

			/**
			 * Filter product card badges.
			 *
			 * @since 0.12.0
			 *
			 * @param array      $cbv_badges List of array( slug, label ).
			 * @param WC_Product $product    Product object.
			 */
			$cbv_badges = apply_filters( 'cbv_product_card_badges', $cbv_badges, $product );

			$seen = array();

			foreach ( $cbv_badges as $cbv_badge ) {
				if ( empty( $cbv_badge[1] ) || isset( $seen[ $cbv_badge[1] ] ) ) {
					continue;
				}

				$seen[ $cbv_badge[1] ] = true;
				printf(
					'<span class="cbv-card__badge cbv-card__badge--%s">%s</span>',
					esc_attr( sanitize_html_class( $cbv_badge[0] ) ),
					esc_html( $cbv_badge[1] )
				);
			}
			?>
		</a>
		<?php
		/**
		 * Fires after the card media, before the card body.
		 *
		 * @since 0.3.0
		 *
		 * @param WC_Product $product Product object.
		 * @param array      $args    Card args.
		 */
		do_action( 'cbv_product_card_media_after', $product, $args );
		?>
		<div class="cbv-card__body">
			<?php if ( $show_brand ) : ?>
				<span class="cbv-card__brand"><?php echo esc_html( $brand ); ?></span>
			<?php endif; ?>
			<?php if ( $show_sku || $show_uom || $show_pack ) : ?>
				<span class="cbv-card__meta-row">
					<?php if ( $show_sku ) : ?>
						<span class="cbv-card__sku"><?php echo esc_html( $sku ); ?></span>
					<?php endif; ?>
					<?php if ( $show_pack ) : ?>
						<span class="cbv-card__pack"><?php echo esc_html( $pack ); ?></span>
					<?php endif; ?>
					<?php if ( $show_uom ) : ?>
						<span class="cbv-card__uom"><?php echo esc_html( $uom ); ?></span>
					<?php endif; ?>
				</span>
			<?php endif; ?>
			<?php
			/**
			 * Filter the product name shown on a card.
			 *
			 * @since 0.12.37
			 *
			 * @param string     $title   Product name.
			 * @param WC_Product $product Product object.
			 */
			$cbv_card_title = apply_filters( 'cbv_product_card_title', $product->get_name(), $product );
			?>
			<h3 class="cbv-card__title">
				<a href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php echo esc_html( $cbv_card_title ); ?></a>
			</h3>
			<?php
			/**
			 * Fires after the card title (ratings, brand names).
			 *
			 * @since 0.3.0
			 *
			 * @param WC_Product $product Product object.
			 * @param array      $args    Card args.
			 */
			do_action( 'cbv_product_card_title_after', $product, $args );
			?>
			<?php if ( $args['show_price'] || $show_stock ) : ?>
				<div class="cbv-card__price-row">
					<?php if ( $args['show_price'] ) : ?>
						<div class="cbv-card__price product__price">
							<?php
							$price_html = $product->get_price_html();

							if ( ! empty( $args['show_price_suffix'] ) ) {
								$suffix = cbv_price_suffix( $product );

								if ( '' !== $suffix ) {
									$price_html .= ' <span class="cbv-price-suffix">' . esc_html( $suffix ) . '</span>';
								}
							}

							echo wp_kses_post( $price_html );
							?>
						</div>
					<?php endif; ?>
					<?php if ( $show_stock ) : ?>
						<?php
						$stock_status = $product->get_stock_status();

						if ( 'onbackorder' === $stock_status ) {
							$stock_class = 'onbackorder';
							$stock_label = __( 'On backorder', 'commercebuild-velocity' );
						} elseif ( $product->is_in_stock() ) {
							$stock_class = 'instock';
							$stock_label = __( 'In Stock', 'commercebuild-velocity' );
						} else {
							$stock_class = 'outofstock';
							$stock_label = __( 'Out of Stock', 'commercebuild-velocity' );
						}
						?>
						<span class="cbv-stock cbv-stock--<?php echo esc_attr( $stock_class ); ?>"><?php echo esc_html( $stock_label ); ?></span>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<?php
			/**
			 * Fires after the card price (stock/warehouse badges, UOM selectors).
			 *
			 * @since 0.3.0
			 *
			 * @param WC_Product $product Product object.
			 * @param array      $args    Card args.
			 */
			do_action( 'cbv_product_card_price_after', $product, $args );
			?>
			<?php if ( $show_add_to_cart ) : ?>
				<div class="cbv-card__actions">
					<a class="cbv-button" href="<?php echo esc_url( $product->add_to_cart_url() ); ?>" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>">
						<?php echo esc_html( $product->add_to_cart_text() ); ?>
					</a>
				</div>
			<?php endif; ?>
			<?php
			/**
			 * Fires at the bottom of the card body.
			 *
			 * @since 0.3.0
			 *
			 * @param WC_Product $product Product object.
			 * @param array      $args    Card args.
			 */
			do_action( 'cbv_product_card_bottom', $product, $args );
			?>
		</div>
	</article>
	<?php

	/**
	 * Filter the finished product card HTML.
	 *
	 * @since 0.3.0
	 *
	 * @param string     $html    Card HTML.
	 * @param WC_Product $product Product object.
	 * @param array      $args    Card args.
	 */
	return apply_filters( 'cbv_product_card_html', ob_get_clean(), $product, $args );
}

/**
 * Render a grid of product cards from a list of IDs.
 *
 * Primes post, meta, and term caches for the whole set in three queries
 * before rendering, so cards never trigger per-product lookups.
 *
 * @since 0.1.0
 *
 * @param int[] $ids     Product IDs.
 * @param array $args    Card args (see cbv_product_card()).
 * @param int   $columns Grid columns. Default 4.
 * @return string Grid HTML.
 */
function cbv_product_grid( array $ids, array $args = array(), $columns = 4 ) {
	$ids = array_filter( array_map( 'absint', $ids ) );

	if ( empty( $ids ) || ! function_exists( 'wc_get_product' ) ) {
		return '';
	}

	_prime_post_caches( $ids, true, true );

	$columns = min( 12, max( 1, absint( $columns ) ) );

	/**
	 * Filter the product grid column count.
	 *
	 * @since 0.12.2
	 *
	 * @param int   $columns Column count after the 1-12 clamp.
	 * @param int[] $ids     Product IDs.
	 * @param array $args    Card args.
	 */
	$columns = (int) apply_filters( 'cbv_product_grid_columns', $columns, $ids, $args );
	$cards   = '';

	foreach ( $ids as $id ) {
		$cards .= cbv_product_card( $id, $args );
	}

	if ( '' === $cards ) {
		return '';
	}

	$html = sprintf(
		'<div class="cbv-grid" style="--cbv-grid-cols:%d">%s</div>',
		(int) $columns,
		$cards
	);

	/**
	 * Filter the finished product grid HTML.
	 *
	 * @since 0.3.0
	 *
	 * @param string $html    Grid HTML.
	 * @param int[]  $ids     Product IDs rendered.
	 * @param array  $args    Card args.
	 * @param int    $columns Column count.
	 */
	return apply_filters( 'cbv_product_grid_html', $html, $ids, $args, $columns );
}

/**
 * Render an inline SVG icon from the theme registry.
 *
 * @since 0.11.0
 *
 * @param string $name Icon slug (see CBV_Icons::registry()).
 * @param array  $args Optional. Size/class arguments passed to CBV_Icons::get().
 * @return string SVG markup, empty string for unknown icons.
 */
function cbv_icon( $name, array $args = array() ) {
	return CBV_Icons::get( $name, $args );
}

/**
 * Echo an inline SVG icon, escaped for output.
 *
 * @since 0.11.0
 *
 * @param string $name Icon slug.
 * @param array  $args Optional. Size/class arguments.
 * @return void
 */
function cbv_the_icon( $name, array $args = array() ) {
	echo wp_kses( cbv_icon( $name, $args ), CBV_Icons::kses_allowed() );
}

/**
 * Catalog hero data (shop, taxonomy, product search).
 *
 * @since 0.12.11
 *
 * @return array {
 *     @type bool   $enabled Whether to render the hero.
 *     @type string $title   Heading.
 *     @type string $kicker  Uppercase line under the title.
 *     @type string $image   Background image URL.
 *     @type string $alt     Image alt.
 * }
 */
function cbv_catalog_hero_data() {
	$title = '';

	if ( function_exists( 'woocommerce_page_title' ) ) {
		$title = woocommerce_page_title( false );
	}

	$image = '';

	if ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) {
		$term = get_queried_object();

		if ( $term && ! empty( $term->term_id ) && function_exists( 'get_term_meta' ) ) {
			$thumb_id = absint( get_term_meta( $term->term_id, 'thumbnail_id', true ) );

			if ( $thumb_id ) {
				$url = wp_get_attachment_image_url( $thumb_id, 'full' );

				if ( $url ) {
					$image = $url;
				}
			}
		}
	}

	$data = array(
		'enabled' => true,
		'title'   => $title,
		'kicker'  => '',
		'image'   => $image,
		'alt'     => $title,
	);

	/**
	 * Filter catalog hero content.
	 *
	 * @since 0.12.11
	 *
	 * @param array $data Hero data.
	 */
	return apply_filters( 'cbv_catalog_hero', $data );
}

/**
 * Print the full-width catalog hero.
 *
 * @since 0.12.11
 *
 * @return void
 */
function cbv_the_catalog_hero() {
	$data = cbv_catalog_hero_data();

	if ( empty( $data['enabled'] ) || '' === trim( (string) $data['title'] ) ) {
		return;
	}

	echo '<section class="cbv-catalog-hero">';

	if ( ! empty( $data['image'] ) ) {
		echo '<div class="cbv-catalog-hero__media"><img src="' . esc_url( $data['image'] ) . '" alt="' . esc_attr( $data['alt'] ) . '" decoding="async"></div>';
	}

	echo '<div class="cbv-container cbv-catalog-hero__inner">';
	echo '<h1 class="cbv-catalog-hero__title">' . esc_html( $data['title'] ) . '</h1>';

	if ( '' !== trim( (string) $data['kicker'] ) ) {
		echo '<p class="cbv-catalog-hero__kicker">' . esc_html( $data['kicker'] ) . '</p>';
	}

	echo '</div></section>';
}

/**
 * Whether classic checkout should show the billing fieldset.
 *
 * B2B account billing lives on the ERP customer. cfw_core marks those
 * fields readonly and does not offer a billing picker unless
 * `cfw_b2b_enable_billing_address_management` is on. Guests and B2C
 * still get a normal billing form.
 *
 * Hidden billing fields stay in the POST (Woo still needs them). Do not
 * remove the inputs.
 *
 * @since 0.12.44
 *
 * @return bool
 */
function cbv_checkout_show_billing_fields() {
	$show = true;

	if ( function_exists( 'cfw_is_b2b' ) && cfw_is_b2b() ) {
		$show = (bool) get_option( 'cfw_b2b_enable_billing_address_management' );
	}

	/**
	 * Filter whether the checkout billing fieldset is visible.
	 *
	 * @since 0.12.44
	 *
	 * @param bool $show True to show the billing heading and fields.
	 */
	return (bool) apply_filters( 'cbv_checkout_show_billing_fields', $show );
}

/**
 * Print checkout shipping methods as cards (V1 shipping step).
 *
 * @since 0.12.8
 *
 * @return void
 */
function cbv_the_checkout_shipping_methods() {
	echo '<section class="cbv-checkout-methods">';
	echo '<h3>' . esc_html__( 'Shipping Methods', 'commercebuild-velocity' ) . '</h3>';

	$packages = array();
	$chosen   = array();

	if ( function_exists( 'WC' ) && WC()->cart && WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) {
		$packages = WC()->shipping()->get_packages();
		$chosen   = ( WC()->session ) ? (array) WC()->session->get( 'chosen_shipping_methods' ) : array();
	}

	$has_rate = false;

	echo '<ul id="shipping_method" class="woocommerce-shipping-methods cbv-checkout-methods__list">';

	foreach ( $packages as $index => $package ) {
		$rates = isset( $package['rates'] ) ? $package['rates'] : array();

		foreach ( $rates as $method ) {
			$has_rate  = true;
			$selected  = isset( $chosen[ $index ] ) ? $chosen[ $index ] : '';
			$method_id = sanitize_title( $method->id );

			echo '<li>';
			printf(
				'<input type="radio" name="shipping_method[%1$d]" data-index="%1$d" id="shipping_method_%1$d_%2$s" value="%3$s" class="shipping_method" %4$s />',
				(int) $index,
				esc_attr( $method_id ),
				esc_attr( $method->id ),
				checked( $method->id, $selected, false )
			);
			printf(
				'<label for="shipping_method_%1$d_%2$s">%3$s</label>',
				(int) $index,
				esc_attr( $method_id ),
				wp_kses_post( wc_cart_totals_shipping_method_label( $method ) )
			);
			echo '</li>';
		}
	}

	echo '</ul>';

	if ( ! $has_rate ) {
		echo '<p class="cbv-checkout-methods__empty">' . esc_html__( 'Enter your address to view shipping options.', 'commercebuild-velocity' ) . '</p>';
	}

	/**
	 * Fires after checkout shipping method cards.
	 *
	 * @since 0.12.8
	 */
	do_action( 'cbv_checkout_shipping_methods_after' );

	echo '</section>';
}
