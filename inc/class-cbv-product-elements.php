<?php
/**
 * Product page element library.
 *
 * Each element is a small, self-contained renderer for one piece of the
 * single product page. Elements read only from the already-loaded WC_Product
 * object (no extra queries), feature-detect cfw_core integrations, and are
 * written to behave correctly for every product type: simple, variable,
 * grouped, external, and any custom type registered by a plugin.
 *
 * @package Commercebuild_Velocity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renderers and metadata for product page elements.
 *
 * @since 0.7.0
 */
class CBV_Product_Elements {

	/**
	 * Element definitions.
	 *
	 * Keys: label, area (above|summary|after), callback, description, options.
	 *
	 * @since 0.7.0
	 *
	 * @return array
	 */
	public static function definitions() {
		$definitions = array(
			'breadcrumbs'       => array(
				'label' => __( 'Breadcrumbs', 'commercebuild-velocity' ),
				'area'  => 'above',
			),
			'title'             => array(
				'label' => __( 'Product title', 'commercebuild-velocity' ),
				'area'  => 'summary',
			),
			'rating'            => array(
				'label' => __( 'Star rating', 'commercebuild-velocity' ),
				'area'  => 'summary',
			),
			'price'             => array(
				'label' => __( 'Price', 'commercebuild-velocity' ),
				'area'  => 'summary',
			),
			'poa'               => array(
				'label'       => __( 'Price on Application notice', 'commercebuild-velocity' ),
				'area'        => 'summary',
				'description' => __( 'Shown only when the product has no purchasable price.', 'commercebuild-velocity' ),
			),
			'sku'               => array(
				'label'       => __( 'SKU', 'commercebuild-velocity' ),
				'area'        => 'summary',
				'description' => __( 'Prefers the ERP original_sku.', 'commercebuild-velocity' ),
			),
			'uom'               => array(
				'label' => __( 'Unit of measure', 'commercebuild-velocity' ),
				'area'  => 'summary',
			),
			'stock_qty'         => array(
				'label' => __( 'Stock quantity', 'commercebuild-velocity' ),
				'area'  => 'summary',
			),
			'stock_status'      => array(
				'label' => __( 'Stock status', 'commercebuild-velocity' ),
				'area'  => 'summary',
			),
			'qty_in_cart'       => array(
				'label' => __( 'Quantity already in cart', 'commercebuild-velocity' ),
				'area'  => 'summary',
			),
			'short_description' => array(
				'label' => __( 'Short description', 'commercebuild-velocity' ),
				'area'  => 'summary',
			),
			'add_to_cart'       => array(
				'label'       => __( 'Add to cart form', 'commercebuild-velocity' ),
				'area'        => 'summary',
				'description' => __( 'Includes the quantity input and variation/grouped controls.', 'commercebuild-velocity' ),
			),
			'purchase_order'    => array(
				'label'       => __( 'Purchase order field', 'commercebuild-velocity' ),
				'area'        => 'summary',
				'description' => __( 'cfw_core collects the PO at checkout; shown here for reference only.', 'commercebuild-velocity' ),
			),
			'uom_selector'      => array(
				'label'       => __( 'UOM selector', 'commercebuild-velocity' ),
				'area'        => 'summary',
				'description' => __( 'Requires the cfw_core "hide UOM selector" option so this element controls its position.', 'commercebuild-velocity' ),
			),
			'quantity_breaks'   => array(
				'label'       => __( 'Quantity break pricing', 'commercebuild-velocity' ),
				'area'        => 'summary',
				'description' => __( 'Use once per page.', 'commercebuild-velocity' ),
			),
			'msrp'              => array(
				'label' => __( 'MSRP', 'commercebuild-velocity' ),
				'area'  => 'summary',
			),
			'attributes'        => array(
				'label' => __( 'Product attributes', 'commercebuild-velocity' ),
				'area'  => 'summary',
			),
			'meta_fields'       => array(
				'label'       => __( 'Product meta fields', 'commercebuild-velocity' ),
				'area'        => 'summary',
				'description' => __( 'Meta keys configured below.', 'commercebuild-velocity' ),
			),
			'categories'        => array(
				'label' => __( 'Categories and tags', 'commercebuild-velocity' ),
				'area'  => 'summary',
			),
			'prop65'            => array(
				'label' => __( 'Prop 65 warning', 'commercebuild-velocity' ),
				'area'  => 'summary',
			),
			'warehouse_stock'   => array(
				'label' => __( 'Warehouse availability', 'commercebuild-velocity' ),
				'area'  => 'summary',
			),
			'sharing'           => array(
				'label' => __( 'Sharing buttons', 'commercebuild-velocity' ),
				'area'  => 'summary',
			),
			'trade_notice'      => array(
				'label'       => __( 'Trade pricing notice', 'commercebuild-velocity' ),
				'area'        => 'summary',
				'description' => __( 'Italic line under the stars, e.g. trade pricing applied at checkout.', 'commercebuild-velocity' ),
			),
			'account_cta'       => array(
				'label'       => __( 'Account CTA box', 'commercebuild-velocity' ),
				'area'        => 'summary',
				'description' => __( 'Become-a-customer notice for guests. Hidden when the filter returns empty.', 'commercebuild-velocity' ),
			),
			'sku_row'           => array(
				'label'       => __( 'SKU and stock row', 'commercebuild-velocity' ),
				'area'        => 'summary',
				'description' => __( 'SKU and In Stock on one line.', 'commercebuild-velocity' ),
			),
			'pack_size'         => array(
				'label'       => __( 'Pack size selector', 'commercebuild-velocity' ),
				'area'        => 'summary',
				'description' => __( 'Selectable pack/UOM boxes. Child themes supply extra sizes via cbv_product_pack_sizes.', 'commercebuild-velocity' ),
			),
			'actions'           => array(
				'label'       => __( 'Email and compare', 'commercebuild-velocity' ),
				'area'        => 'summary',
				'description' => __( 'Secondary actions under add to cart.', 'commercebuild-velocity' ),
			),
			'shortcode'         => array(
				'label'       => __( 'Shortcode', 'commercebuild-velocity' ),
				'area'        => 'summary',
				'description' => __( 'Any shortcode, rendered in the summary column.', 'commercebuild-velocity' ),
			),
			'divider'           => array(
				'label' => __( 'Divider', 'commercebuild-velocity' ),
				'area'  => 'summary',
			),
			'tabs'              => array(
				'label'       => __( 'Product tabs', 'commercebuild-velocity' ),
				'area'        => 'after',
				'description' => __( 'Description, additional information, reviews, and cfw custom tabs.', 'commercebuild-velocity' ),
			),
			'description'       => array(
				'label'       => __( 'Full description', 'commercebuild-velocity' ),
				'area'        => 'after',
				'description' => __( 'Use when tabs are disabled.', 'commercebuild-velocity' ),
			),
			'upsells'           => array(
				'label' => __( 'Upsells', 'commercebuild-velocity' ),
				'area'  => 'after',
			),
			'related'           => array(
				'label' => __( 'Related products', 'commercebuild-velocity' ),
				'area'  => 'after',
			),
			'cross_sells'       => array(
				'label' => __( 'Cross-sells', 'commercebuild-velocity' ),
				'area'  => 'after',
			),
			'shortcode_below'   => array(
				'label'       => __( 'Shortcode (below)', 'commercebuild-velocity' ),
				'area'        => 'after',
				'description' => __( 'Any shortcode, rendered full width below the product.', 'commercebuild-velocity' ),
			),
			'cta_band'          => array(
				'label'       => __( 'Trade account CTA', 'commercebuild-velocity' ),
				'area'        => 'after',
				'description' => __( 'Reuses the homepage CTA band when that copy is set.', 'commercebuild-velocity' ),
			),
		);

		/**
		 * Filter the product page element library.
		 *
		 * @since 0.7.0
		 *
		 * @param array $definitions Element definitions keyed by element id.
		 */
		return apply_filters( 'cbv_product_elements', $definitions );
	}

	/**
	 * Per-element option fields, used to build the Customizer UI and to
	 * validate saved values.
	 *
	 * @since 0.7.0
	 *
	 * @return array Element id => field key => field definition.
	 */
	public static function option_schema() {
		$grid_fields = array(
			'limit'   => array(
				'type'    => 'number',
				'label'   => __( 'Products', 'commercebuild-velocity' ),
				'default' => 4,
				'min'     => 1,
				'max'     => 12,
			),
			'columns' => array(
				'type'    => 'number',
				'label'   => __( 'Columns', 'commercebuild-velocity' ),
				'default' => 4,
				'min'     => 1,
				'max'     => 6,
			),
		);

		$schema = array(
			'poa'             => array(
				'text'  => array(
					'type'        => 'text',
					'label'       => __( 'Notice text', 'commercebuild-velocity' ),
					'placeholder' => __( 'Price on application', 'commercebuild-velocity' ),
					'default'     => '',
				),
				'style' => array(
					'type'    => 'select',
					'label'   => __( 'Style', 'commercebuild-velocity' ),
					'default' => 'notice',
					'choices' => array(
						'notice' => __( 'Notice (price-sized)', 'commercebuild-velocity' ),
						'plain'  => __( 'Plain (muted text)', 'commercebuild-velocity' ),
					),
				),
			),
			'add_to_cart'     => array(
				'hide_quantity' => array(
					'type'    => 'checkbox',
					'label'   => __( 'Hide the quantity input', 'commercebuild-velocity' ),
					'default' => false,
				),
			),
			'trade_notice'    => array(
				'text' => array(
					'type'        => 'text',
					'label'       => __( 'Notice text', 'commercebuild-velocity' ),
					'placeholder' => __( 'Trade pricing applied at checkout', 'commercebuild-velocity' ),
					'default'     => '',
				),
			),
			'account_cta'     => array(
				'text'   => array(
					'type'        => 'textarea',
					'label'       => __( 'Body text', 'commercebuild-velocity' ),
					'placeholder' => __( 'Registered trade accounts get exclusive pricing, saved order guides & dedicated rep support.', 'commercebuild-velocity' ),
					'default'     => '',
				),
				'button' => array(
					'type'        => 'text',
					'label'       => __( 'Button label', 'commercebuild-velocity' ),
					'placeholder' => __( 'Become a Customer +', 'commercebuild-velocity' ),
					'default'     => '',
				),
			),
			'attributes'      => array(
				'include' => array(
					'type'        => 'text',
					'label'       => __( 'Only these attributes (comma separated, blank for all)', 'commercebuild-velocity' ),
					'placeholder' => 'pa_color, pa_size',
					'default'     => '',
				),
			),
			'meta_fields'     => array(
				'fields' => array(
					'type'        => 'textarea',
					'label'       => __( 'Meta fields, one per line as meta_key|Label', 'commercebuild-velocity' ),
					'placeholder' => "_length|Length\n_material|Material",
					'default'     => '',
				),
			),
			'warehouse_stock' => array(
				'dropdown' => array(
					'type'    => 'checkbox',
					'label'   => __( 'Use the warehouse dropdown variant', 'commercebuild-velocity' ),
					'default' => false,
				),
			),
			'related'         => array(
				'source'        => array(
					'type'    => 'select',
					'label'   => __( 'Source', 'commercebuild-velocity' ),
					'default' => 'related',
					'choices' => array(
						'related'       => __( 'WooCommerce related products', 'commercebuild-velocity' ),
						'same_category' => __( 'Same category, newest first', 'commercebuild-velocity' ),
						'best_selling'  => __( 'Best selling', 'commercebuild-velocity' ),
						'on_sale'       => __( 'On sale', 'commercebuild-velocity' ),
					),
				),
				'limit'         => $grid_fields['limit'],
				'columns'       => $grid_fields['columns'],
				'heading'       => array(
					'type'        => 'text',
					'label'       => __( 'Heading', 'commercebuild-velocity' ),
					'placeholder' => __( 'You may also like', 'commercebuild-velocity' ),
					'default'     => '',
				),
				'footer_label'  => array(
					'type'        => 'text',
					'label'       => __( 'Footer button label (blank to hide)', 'commercebuild-velocity' ),
					'placeholder' => __( 'Browse catalogue', 'commercebuild-velocity' ),
					'default'     => '',
				),
			),
			'upsells'         => $grid_fields,
			'cross_sells'     => $grid_fields,
			'shortcode'       => array(
				'shortcode' => array(
					'type'        => 'textarea',
					'label'       => __( 'Shortcode', 'commercebuild-velocity' ),
					'placeholder' => '[my_shortcode]',
					'default'     => '',
				),
			),
			'shortcode_below' => array(
				'shortcode' => array(
					'type'        => 'textarea',
					'label'       => __( 'Shortcode', 'commercebuild-velocity' ),
					'placeholder' => '[my_shortcode]',
					'default'     => '',
				),
			),
		);

		/**
		 * Filter the product element option schema.
		 *
		 * @since 0.7.0
		 *
		 * @param array $schema Option fields keyed by element id.
		 */
		return apply_filters( 'cbv_product_element_options', $schema );
	}

	/**
	 * Render one element.
	 *
	 * @since 0.7.0
	 *
	 * @param string     $id      Element id.
	 * @param WC_Product $product Current product.
	 * @param array      $options Element options.
	 * @return void
	 */
	public static function render( $id, $product, array $options = array() ) {
		$method = 'render_' . str_replace( '-', '_', $id );

		/**
		 * Short-circuit an element's output.
		 *
		 * Returning a string replaces the built-in renderer entirely.
		 *
		 * @since 0.7.0
		 *
		 * @param null|string $output  Replacement output.
		 * @param string      $id      Element id.
		 * @param WC_Product  $product Product object.
		 * @param array       $options Element options.
		 */
		$output = apply_filters( 'cbv_product_element_output', null, $id, $product, $options );

		if ( null !== $output ) {
			echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Filtered output owns its escaping.
			return;
		}

		if ( ! method_exists( __CLASS__, $method ) ) {
			return;
		}

		/**
		 * Fires before a product element renders.
		 *
		 * @since 0.7.0
		 *
		 * @param string     $id      Element id.
		 * @param WC_Product $product Product object.
		 */
		do_action( 'cbv_product_element_before', $id, $product );

		self::$method( $product, $options );

		/**
		 * Fires after a product element renders.
		 *
		 * @since 0.7.0
		 *
		 * @param string     $id      Element id.
		 * @param WC_Product $product Product object.
		 */
		do_action( 'cbv_product_element_after', $id, $product );
	}

	/**
	 * Breadcrumbs.
	 *
	 * @since 0.7.0
	 *
	 * @return void
	 */
	private static function render_breadcrumbs() {
		if ( function_exists( 'woocommerce_breadcrumb' ) ) {
			woocommerce_breadcrumb();
		}
	}

	/**
	 * Product title.
	 *
	 * @since 0.7.0
	 *
	 * @param WC_Product $product Product.
	 * @return void
	 */
	private static function render_title( $product ) {
		/**
		 * Filter the product title on the single product page.
		 *
		 * @since 0.12.38
		 *
		 * @param string     $title   Product name.
		 * @param WC_Product $product Product object.
		 */
		$title = apply_filters( 'cbv_product_title', $product->get_name(), $product );

		printf( '<h1 class="product_title entry-title">%s</h1>', esc_html( $title ) );
	}

	/**
	 * Price. WooCommerce's price HTML already covers ranges for variable and
	 * grouped products.
	 *
	 * @since 0.7.0
	 *
	 * @param WC_Product $product Product.
	 * @return void
	 */
	private static function render_price( $product ) {
		$html = $product->get_price_html();

		if ( '' === $html ) {
			return;
		}

		$plain = strtolower( wp_strip_all_tags( html_entity_decode( (string) $html, ENT_QUOTES, 'UTF-8' ) ) );

		if ( false !== strpos( $plain, 'contact for pricing' ) || false !== strpos( $plain, 'price on application' ) ) {
			printf( '<div class="cbv-pe cbv-pe--poa">%s</div>', wp_kses_post( $html ) );
			return;
		}

		$suffix = function_exists( 'cbv_price_suffix' ) ? cbv_price_suffix( $product ) : '';

		if ( '' !== $suffix ) {
			$html .= ' <span class="cbv-price-suffix">' . esc_html( $suffix ) . '</span>';
		}

		printf( '<div class="cbv-pe cbv-pe--price price">%s</div>', wp_kses_post( $html ) );
	}

	/**
	 * Price on Application notice, shown when no price is available.
	 *
	 * @since 0.7.0
	 *
	 * @param WC_Product $product Product.
	 * @param array      $options Options.
	 * @return void
	 */
	private static function render_poa( $product, array $options = array() ) {
		$storage = self::cfw_service( '\\Commercebuild\\WCCore\\PriceOnApplication\\PriceOnApplicationManager' );
		$is_poa  = null;
		$message = '';

		if ( $storage && method_exists( $storage, 'isProductPOA' ) ) {
			// cfw_core owns POA state (global guest/B2B/B2C toggles plus
			// per-product meta), so never infer it from an empty price.
			$is_poa = (bool) $storage->isProductPOA( $product->get_id() );

			if ( $is_poa && method_exists( $storage, 'getStorage' ) ) {
				$poa_storage = $storage->getStorage();

				if ( $poa_storage && method_exists( $poa_storage, 'getEffectivePOAMessage' ) ) {
					$message = (string) $poa_storage->getEffectivePOAMessage( $product->get_id() );
				}
			}
		}

		if ( null === $is_poa ) {
			// Plain WooCommerce fallback: no price to show.
			$is_poa = ( '' === (string) $product->get_price() && '' === $product->get_price_html() );
		}

		if ( ! $is_poa ) {
			return;
		}

		$text = isset( $options['text'] ) && '' !== $options['text'] ? $options['text'] : $message;

		if ( '' === $text ) {
			$text = __( 'Price on application', 'commercebuild-velocity' );
		}

		/**
		 * Filter the Price on Application text.
		 *
		 * @since 0.7.0
		 *
		 * @param string     $text    Notice text.
		 * @param WC_Product $product Product object.
		 */
		$text = apply_filters( 'cbv_poa_text', $text, $product );

		/**
		 * Filter the Price on Application display style.
		 *
		 * @since 0.11.0
		 *
		 * @param string     $style   'notice' or 'plain'.
		 * @param WC_Product $product Product object.
		 */
		$style = apply_filters( 'cbv_poa_style', isset( $options['style'] ) && '' !== $options['style'] ? $options['style'] : 'notice', $product );

		printf(
			'<div class="cbv-pe cbv-pe--poa%s">%s</div>',
			'plain' === $style ? ' cbv-pe--poa-plain' : '',
			esc_html( $text )
		);
	}

	/**
	 * SKU, preferring the ERP original_sku.
	 *
	 * @since 0.7.0
	 *
	 * @param WC_Product $product Product.
	 * @return void
	 */
	private static function render_sku( $product ) {
		$sku = (string) $product->get_meta( 'original_sku' );

		if ( '' === $sku ) {
			$sku = (string) $product->get_sku();
		}

		/** This filter is documented in inc/template-tags.php */
		$sku = apply_filters( 'cbv_product_card_sku', $sku, $product );

		if ( '' === $sku ) {
			return;
		}

		self::render_pair( __( 'SKU', 'commercebuild-velocity' ), $sku, 'sku' );
	}

	/**
	 * Unit of measure.
	 *
	 * @since 0.7.0
	 *
	 * @param WC_Product $product Product.
	 * @return void
	 */
	private static function render_uom( $product ) {
		/** This filter is documented in inc/template-tags.php */
		$uom = apply_filters( 'cbv_product_card_uom', (string) $product->get_meta( 'uom' ), $product );

		if ( '' === $uom ) {
			return;
		}

		self::render_pair( __( 'Unit of measure', 'commercebuild-velocity' ), $uom, 'uom' );
	}

	/**
	 * Stock quantity.
	 *
	 * Variable and grouped products hold stock on their children, so the
	 * parent only reports a number when it manages stock itself.
	 *
	 * @since 0.7.0
	 *
	 * @param WC_Product $product Product.
	 * @return void
	 */
	private static function render_stock_qty( $product ) {
		$qty       = null;
		$inventory = self::cfw_service( '\\Commercebuild\\WCCore\\cfwInventory' );

		if ( $inventory && method_exists( $inventory, 'get_adjusted_stock_quantity' ) ) {
			$adjusted = $inventory->get_adjusted_stock_quantity( $product );

			if ( is_numeric( $adjusted ) ) {
				$qty = (float) $adjusted;
			}
		}

		if ( null === $qty ) {
			if ( ! $product->managing_stock() ) {
				return;
			}

			$qty = $product->get_stock_quantity();
		}

		if ( null === $qty ) {
			return;
		}

		/*
		 * cfw_core reports a sentinel quantity for non-stock items (see
		 * cfwInventory::get_stock_quantity), which must never be printed as a
		 * real number.
		 */
		if ( $qty >= 999999 ) {
			return;
		}

		if ( $qty <= 0 ) {
			return;
		}

		/**
		 * Filter the displayed stock quantity.
		 *
		 * @since 0.7.0
		 *
		 * @param int|float  $qty     Stock quantity.
		 * @param WC_Product $product Product object.
		 */
		$qty = apply_filters( 'cbv_stock_quantity', $qty, $product );

		self::render_pair(
			__( 'In stock', 'commercebuild-velocity' ),
			number_format_i18n( (float) $qty ),
			'stock-qty'
		);
	}

	/**
	 * Stock status badge.
	 *
	 * @since 0.7.0
	 *
	 * @param WC_Product $product Product.
	 * @return void
	 */
	private static function render_stock_status( $product ) {
		$status = $product->get_stock_status();

		$labels = array(
			'instock'     => __( 'In stock', 'commercebuild-velocity' ),
			'outofstock'  => __( 'Out of stock', 'commercebuild-velocity' ),
			'onbackorder' => __( 'On backorder', 'commercebuild-velocity' ),
		);

		$label = isset( $labels[ $status ] ) ? $labels[ $status ] : $status;

		printf(
			'<div class="cbv-pe cbv-pe--stock-status cbv-stock cbv-stock--%1$s">%2$s</div>',
			esc_attr( $status ),
			esc_html( $label )
		);
	}

	/**
	 * Quantity of this product already in the cart.
	 *
	 * Sums every cart line for the product, including its variations, so a
	 * variable product reports the total across chosen variations.
	 *
	 * @since 0.7.0
	 *
	 * @param WC_Product $product Product.
	 * @return void
	 */
	private static function render_qty_in_cart( $product ) {
		if ( ! function_exists( 'WC' ) || is_null( WC()->cart ) ) {
			return;
		}

		$product_id = $product->get_id();
		$quantity   = 0;

		foreach ( WC()->cart->get_cart() as $item ) {
			if ( (int) $item['product_id'] === $product_id || (int) $item['variation_id'] === $product_id ) {
				$quantity += $item['quantity'];
			}
		}

		if ( $quantity <= 0 ) {
			return;
		}

		printf(
			'<div class="cbv-pe cbv-pe--qty-in-cart">%s</div>',
			esc_html(
				sprintf(
					/* translators: %s: quantity currently in the cart. */
					__( '%s already in your cart', 'commercebuild-velocity' ),
					number_format_i18n( $quantity )
				)
			)
		);
	}

	/**
	 * Short description.
	 *
	 * @since 0.7.0
	 *
	 * @param WC_Product $product Product.
	 * @return void
	 */
	private static function render_short_description( $product ) {
		$excerpt = $product->get_short_description();

		if ( '' === $excerpt ) {
			return;
		}

		printf(
			'<div class="cbv-pe cbv-pe--short-description woocommerce-product-details__short-description">%s</div>',
			wp_kses_post( wpautop( do_shortcode( $excerpt ) ) )
		);
	}

	/**
	 * Add to cart form.
	 *
	 * Delegates to WooCommerce's type-aware template so simple, variable,
	 * grouped, external, and custom product types all render their correct
	 * form (including the quantity input and variation selectors).
	 *
	 * @since 0.7.0
	 *
	 * @param WC_Product $product Product.
	 * @param array      $options Options.
	 * @return void
	 */
	private static function render_add_to_cart( $product, array $options = array() ) {
		if ( ! function_exists( 'woocommerce_template_single_add_to_cart' ) ) {
			return;
		}

		$hide_quantity = ! empty( $options['hide_quantity'] );
		$purchasable   = $product->is_purchasable() && $product->is_in_stock();

		/**
		 * Filter whether the single-product add to cart form is enabled.
		 *
		 * @since 0.12.38
		 *
		 * @param bool       $purchasable Whether the form should render.
		 * @param WC_Product $product     Product object.
		 */
		$purchasable = (bool) apply_filters( 'cbv_product_form_enabled', $purchasable, $product );

		if ( ! $purchasable ) {
			return;
		}

		echo '<div class="cbv-pe cbv-pe--add-to-cart">';

		add_action( 'woocommerce_before_add_to_cart_quantity', array( __CLASS__, 'qty_open' ), 1 );
		add_action( 'woocommerce_after_add_to_cart_quantity', array( __CLASS__, 'qty_close' ), 99 );

		if ( $hide_quantity ) {
			add_filter( 'woocommerce_is_sold_individually', '__return_true', 99 );
		}

		woocommerce_template_single_add_to_cart();

		if ( $hide_quantity ) {
			remove_filter( 'woocommerce_is_sold_individually', '__return_true', 99 );
		}

		remove_action( 'woocommerce_before_add_to_cart_quantity', array( __CLASS__, 'qty_open' ), 1 );
		remove_action( 'woocommerce_after_add_to_cart_quantity', array( __CLASS__, 'qty_close' ), 99 );

		echo '</div>';
	}

	/**
	 * Open the PDP quantity stepper.
	 *
	 * @since 0.12.21
	 *
	 * @return void
	 */
	public static function qty_open() {
		echo '<div class="cbv-qty">';
		echo '<button type="button" class="cbv-qty__btn" data-cbv-qty="-1" aria-label="' . esc_attr__( 'Decrease quantity', 'commercebuild-velocity' ) . '">-</button>';
	}

	/**
	 * Close the PDP quantity stepper.
	 *
	 * @since 0.12.21
	 *
	 * @return void
	 */
	public static function qty_close() {
		echo '<button type="button" class="cbv-qty__btn" data-cbv-qty="1" aria-label="' . esc_attr__( 'Increase quantity', 'commercebuild-velocity' ) . '">+</button>';
		echo '</div>';
	}

	/**
	 * Purchase order field (cfw_core).
	 *
	 * @since 0.7.0
	 *
	 * @return void
	 */
	private static function render_purchase_order() {
		if ( ! shortcode_exists( 'purchase_order_field' ) ) {
			return;
		}

		printf(
			'<div class="cbv-pe cbv-pe--purchase-order">%s</div>',
			do_shortcode( '[purchase_order_field]' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shortcode output.
		);
	}

	/**
	 * Product attributes.
	 *
	 * @since 0.7.0
	 *
	 * @param WC_Product $product Product.
	 * @param array      $options Options: `include` (comma separated taxonomy/attribute names).
	 * @return void
	 */
	private static function render_attributes( $product, array $options = array() ) {
		$attributes = $product->get_attributes();

		if ( empty( $attributes ) ) {
			return;
		}

		$include = array();

		if ( ! empty( $options['include'] ) ) {
			$include = array_filter( array_map( 'trim', explode( ',', $options['include'] ) ) );
		}

		$rows = array();

		foreach ( $attributes as $attribute ) {
			if ( ! $attribute instanceof WC_Product_Attribute ) {
				continue;
			}

			// Variation-selection attributes belong to the add-to-cart form.
			if ( $attribute->get_variation() ) {
				continue;
			}

			$name = $attribute->get_name();

			if ( ! empty( $include ) && ! in_array( $name, $include, true ) && ! in_array( wc_attribute_label( $name, $product ), $include, true ) ) {
				continue;
			}

			if ( $attribute->is_taxonomy() ) {
				$terms = wc_get_product_terms( $product->get_id(), $name, array( 'fields' => 'names' ) );
				$value = implode( ', ', $terms );
			} else {
				$value = implode( ', ', $attribute->get_options() );
			}

			if ( '' === $value ) {
				continue;
			}

			$rows[] = array( wc_attribute_label( $name, $product ), $value );
		}

		if ( empty( $rows ) ) {
			return;
		}

		echo '<div class="cbv-pe cbv-pe--attributes"><dl class="cbv-speclist">';

		foreach ( $rows as $row ) {
			printf(
				'<dt>%s</dt><dd>%s</dd>',
				esc_html( $row[0] ),
				esc_html( $row[1] )
			);
		}

		echo '</dl></div>';
	}

	/**
	 * Arbitrary product meta fields.
	 *
	 * @since 0.7.0
	 *
	 * @param WC_Product $product Product.
	 * @param array      $options Options: `fields` as "meta_key|Label" per line or comma separated.
	 * @return void
	 */
	private static function render_meta_fields( $product, array $options = array() ) {
		if ( empty( $options['fields'] ) ) {
			return;
		}

		$definitions = preg_split( '/[\r\n,]+/', (string) $options['fields'] );
		$rows        = array();

		foreach ( $definitions as $definition ) {
			$definition = trim( $definition );

			if ( '' === $definition ) {
				continue;
			}

			$parts = array_map( 'trim', explode( '|', $definition ) );
			$key   = $parts[0];
			$label = isset( $parts[1] ) && '' !== $parts[1] ? $parts[1] : ucwords( str_replace( array( '_', '-' ), ' ', $key ) );

			// Product meta is already loaded with the product; no extra query.
			$value = $product->get_meta( $key );

			if ( is_array( $value ) ) {
				$value = implode( ', ', array_filter( array_map( 'strval', $value ) ) );
			}

			$value = (string) $value;

			if ( '' === $value ) {
				continue;
			}

			$rows[] = array( $label, $value );
		}

		if ( empty( $rows ) ) {
			return;
		}

		echo '<div class="cbv-pe cbv-pe--meta"><dl class="cbv-speclist">';

		foreach ( $rows as $row ) {
			printf( '<dt>%s</dt><dd>%s</dd>', esc_html( $row[0] ), esc_html( $row[1] ) );
		}

		echo '</dl></div>';
	}

	/**
	 * Categories and tags.
	 *
	 * @since 0.7.0
	 *
	 * @param WC_Product $product Product.
	 * @return void
	 */
	private static function render_categories( $product ) {
		$id = $product->get_id();

		$categories = wc_get_product_category_list( $id, ', ', '<span class="posted_in">' . __( 'Category:', 'commercebuild-velocity' ) . ' ', '</span>' );
		$tags       = wc_get_product_tag_list( $id, ', ', '<span class="tagged_as">' . __( 'Tags:', 'commercebuild-velocity' ) . ' ', '</span>' );

		if ( '' === $categories && '' === $tags ) {
			return;
		}

		printf(
			'<div class="cbv-pe cbv-pe--categories product_meta">%s%s</div>',
			wp_kses_post( $categories ),
			wp_kses_post( $tags )
		);
	}

	/**
	 * Prop 65 warning (cfw_core), when available.
	 *
	 * @since 0.7.0
	 *
	 * @param WC_Product $product Product.
	 * @return void
	 */
	private static function render_prop65( $product ) {
		$output  = '';
		$manager = self::cfw_service( '\\Commercebuild\\WCCore\\Prop65\\Prop65Manager' );

		if ( $manager && method_exists( $manager, 'getRenderer' ) ) {
			$renderer = $manager->getRenderer();

			if ( $renderer && method_exists( $renderer, 'getWarningHtml' ) ) {
				// Handles visibility (B2B/B2C/guest) and variable products.
				$output = (string) $renderer->getWarningHtml( $product->get_id() );
			}
		}

		if ( '' === $output && shortcode_exists( 'cfw_prop65' ) ) {
			$output = do_shortcode( '[cfw_prop65]' );
		}

		/**
		 * Filter the Prop 65 warning output.
		 *
		 * @since 0.7.0
		 *
		 * @param string     $output  Warning HTML.
		 * @param WC_Product $product Product object.
		 */
		$output = apply_filters( 'cbv_prop65_output', $output, $product );

		if ( '' === trim( (string) $output ) ) {
			return;
		}

		printf(
			'<div class="cbv-pe cbv-pe--prop65">%s</div>',
			wp_kses_post( $output )
		);
	}

	/**
	 * Warehouse availability (cfw_core), when available.
	 *
	 * @since 0.7.0
	 *
	 * @param WC_Product $product Product.
	 * @param array      $options Options: `dropdown` to use the dropdown variant.
	 * @return void
	 */
	private static function render_warehouse_stock( $product, array $options = array() ) {
		$shortcode = ! empty( $options['dropdown'] )
			? 'cfw_additional_warehouse_data_dropdown'
			: 'cfw_additional_warehouse_data';

		if ( ! shortcode_exists( $shortcode ) ) {
			return;
		}

		$output = do_shortcode( '[' . $shortcode . ']' );

		if ( '' === trim( (string) $output ) ) {
			return;
		}

		printf( '<div class="cbv-pe cbv-pe--warehouse">%s</div>', wp_kses_post( $output ) );
	}

	/**
	 * Star rating, delegating to WooCommerce so review settings are respected.
	 *
	 * @since 0.8.0
	 *
	 * @return void
	 */
	private static function render_rating() {
		if ( ! function_exists( 'wc_review_ratings_enabled' ) || ! wc_review_ratings_enabled() ) {
			return;
		}

		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$count   = (int) $product->get_review_count();
		$average = (float) $product->get_average_rating();
		$percent = min( 100, max( 0, ( $average / 5 ) * 100 ) );
		$reviews = get_permalink( $product->get_id() ) . '#reviews';
		$write   = get_permalink( $product->get_id() ) . '#review_form';

		echo '<div class="cbv-pe cbv-pe--rating woocommerce-product-rating">';

		if ( $count > 0 ) {
			printf(
				'<div class="cbv-stars" role="img" aria-label="%s"><span class="cbv-stars__fill" style="width:%s%%"></span></div>',
				esc_attr(
					sprintf(
						/* translators: %s: average rating. */
						__( 'Rated %s out of 5', 'commercebuild-velocity' ),
						number_format_i18n( $average, 1 )
					)
				),
				esc_attr( (string) round( $percent ) )
			);
			printf(
				'<a class="cbv-rating__count" href="%1$s">%2$s</a>',
				esc_url( $reviews ),
				esc_html(
					sprintf(
						/* translators: %s: review count. */
						_n( '%s review', '%s reviews', $count, 'commercebuild-velocity' ),
						number_format_i18n( $count )
					)
				)
			);
		}

		printf(
			'<a class="cbv-rating__add" href="%s">%s</a>',
			esc_url( $write ),
			esc_html__( 'Add Your Review', 'commercebuild-velocity' )
		);
		echo '</div>';
	}

	/**
	 * Sharing output, delegating to WooCommerce's template hook.
	 *
	 * @since 0.8.0
	 *
	 * @return void
	 */
	private static function render_sharing() {
		if ( function_exists( 'woocommerce_template_single_sharing' ) ) {
			woocommerce_template_single_sharing();
		}
	}

	/**
	 * UOM selector (cfw_core).
	 *
	 * The plugin auto-renders this before the add-to-cart form unless the
	 * `cfw_config_hide_uom_selector` option is on, in which case the shortcode
	 * takes over and this element controls its position.
	 *
	 * @since 0.7.0
	 *
	 * @return void
	 */
	private static function render_uom_selector() {
		if ( ! shortcode_exists( 'cfw_uom_selector' ) ) {
			return;
		}

		$output = do_shortcode( '[cfw_uom_selector]' );

		if ( '' === trim( (string) $output ) ) {
			return;
		}

		printf( '<div class="cbv-pe cbv-pe--uom-selector">%s</div>', wp_kses_post( $output ) );
	}

	/**
	 * Quantity break pricing table (cfw_core).
	 *
	 * The shortcode renders a single-instance placeholder that loads over
	 * AJAX, so it must not be added to a layout twice.
	 *
	 * @since 0.7.0
	 *
	 * @return void
	 */
	private static function render_quantity_breaks() {
		if ( ! shortcode_exists( 'cfw_display_quantity_breaks' ) ) {
			return;
		}

		$output = do_shortcode( '[cfw_display_quantity_breaks]' );

		if ( '' === trim( (string) $output ) ) {
			return;
		}

		printf( '<div class="cbv-pe cbv-pe--quantity-breaks">%s</div>', wp_kses_post( $output ) );
	}

	/**
	 * MSRP (cfw_core).
	 *
	 * @since 0.7.0
	 *
	 * @return void
	 */
	private static function render_msrp() {
		if ( ! shortcode_exists( 'cfw_product_msrp' ) ) {
			return;
		}

		$output = do_shortcode( '[cfw_product_msrp]' );

		if ( '' === trim( (string) $output ) ) {
			return;
		}

		printf( '<div class="cbv-pe cbv-pe--msrp">%s</div>', wp_kses_post( $output ) );
	}

	/**
	 * Resolve a cfw_core service from the DI container.
	 *
	 * @since 0.7.0
	 *
	 * @param string $class Fully qualified class name.
	 * @return object|null
	 */
	private static function cfw_service( $class ) {
		static $resolved = array();

		if ( array_key_exists( $class, $resolved ) ) {
			return $resolved[ $class ];
		}

		$resolved[ $class ] = null;

		if ( ! class_exists( '\\Commercebuild\\WCCore\\cfwCoreDIContainer' ) || ! class_exists( $class ) ) {
			return null;
		}

		try {
			$container = \Commercebuild\WCCore\cfwCoreDIContainer::getContainer();

			if ( $container && method_exists( $container, 'get' ) ) {
				$resolved[ $class ] = $container->get( $class );
			}
		} catch ( \Throwable $e ) {
			$resolved[ $class ] = null;
		}

		return $resolved[ $class ];
	}

	/**
	 * Arbitrary shortcode in the summary column.
	 *
	 * @since 0.9.0
	 *
	 * @param WC_Product $product Product.
	 * @param array      $options Options.
	 * @return void
	 */
	private static function render_shortcode( $product, array $options = array() ) {
		self::render_shortcode_option( $options, 'summary' );
	}

	/**
	 * Arbitrary shortcode below the product.
	 *
	 * @since 0.9.0
	 *
	 * @param WC_Product $product Product.
	 * @param array      $options Options.
	 * @return void
	 */
	private static function render_shortcode_below( $product, array $options = array() ) {
		self::render_shortcode_option( $options, 'below' );
	}

	/**
	 * Shared shortcode renderer.
	 *
	 * @since 0.9.0
	 *
	 * @param array  $options  Options holding the shortcode string.
	 * @param string $modifier CSS modifier.
	 * @return void
	 */
	private static function render_shortcode_option( array $options, $modifier ) {
		$shortcode = isset( $options['shortcode'] ) ? trim( (string) $options['shortcode'] ) : '';

		if ( '' === $shortcode ) {
			return;
		}

		$output = do_shortcode( $shortcode );

		if ( '' === trim( (string) $output ) ) {
			return;
		}

		printf(
			'<div class="cbv-pe cbv-pe--shortcode cbv-pe--shortcode-%s">%s</div>',
			esc_attr( $modifier ),
			$output // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shortcode output.
		);
	}

	/**
	 * Divider.
	 *
	 * @since 0.7.0
	 *
	 * @return void
	 */
	private static function render_divider() {
		echo '<hr class="cbv-pe cbv-pe--divider">';
	}

	/**
	 * Product tabs (includes cfw custom tabs registered on woocommerce_product_tabs).
	 *
	 * @since 0.7.0
	 *
	 * @return void
	 */
	private static function render_tabs() {
		if ( function_exists( 'woocommerce_output_product_data_tabs' ) ) {
			woocommerce_output_product_data_tabs();
		}
	}

	/**
	 * Full description, for layouts that disable tabs.
	 *
	 * @since 0.7.0
	 *
	 * @param WC_Product $product Product.
	 * @return void
	 */
	private static function render_description( $product ) {
		$description = $product->get_description();

		if ( '' === $description ) {
			return;
		}

		printf(
			'<div class="cbv-pe cbv-pe--description">%s</div>',
			wp_kses_post( wpautop( do_shortcode( $description ) ) )
		);
	}

	/**
	 * Upsells.
	 *
	 * @since 0.7.0
	 *
	 * @param WC_Product $product Product.
	 * @param array      $options Options: `limit`, `columns`.
	 * @return void
	 */
	private static function render_upsells( $product, array $options = array() ) {
		$ids = cbv_engine()->filter_visible_ids( array_map( 'absint', $product->get_upsell_ids() ) );

		if ( empty( $ids ) ) {
			return;
		}

		self::render_grid(
			__( 'You may also like', 'commercebuild-velocity' ),
			array_slice( $ids, 0, self::limit( $options, 4 ) ),
			self::columns( $options, 4 ),
			'upsells',
			$options
		);
	}

	/**
	 * Cross-sells.
	 *
	 * @since 0.7.0
	 *
	 * @param WC_Product $product Product.
	 * @param array      $options Options: `limit`, `columns`.
	 * @return void
	 */
	private static function render_cross_sells( $product, array $options = array() ) {
		$ids = cbv_engine()->filter_visible_ids( array_map( 'absint', $product->get_cross_sell_ids() ) );

		if ( empty( $ids ) ) {
			return;
		}

		self::render_grid(
			__( 'Frequently bought together', 'commercebuild-velocity' ),
			array_slice( $ids, 0, self::limit( $options, 4 ) ),
			self::columns( $options, 4 ),
			'cross-sells',
			$options
		);
	}

	/**
	 * Related products, sourced through the cached query engine.
	 *
	 * @since 0.7.0
	 *
	 * @param WC_Product $product Product.
	 * @param array      $options Options: `source`, `limit`, `columns`.
	 * @return void
	 */
	private static function render_related( $product, array $options = array() ) {
		$limit  = self::limit( $options, 4 );
		$source = isset( $options['source'] ) ? $options['source'] : 'related';
		$engine = cbv_engine();

		switch ( $source ) {
			case 'same_category':
				$categories = $product->get_category_ids();

				$ids = $engine->get_product_ids(
					array(
						'limit'    => $limit,
						'category' => $categories,
						'exclude'  => array( $product->get_id() ),
						'orderby'  => 'date',
					)
				);
				break;
			case 'best_selling':
				$ids = array_values(
					array_diff(
						$engine->get_hot_deal_ids( 'best_selling', $limit + 1 ),
						array( $product->get_id() )
					)
				);
				break;
			case 'on_sale':
				$ids = array_values(
					array_diff(
						$engine->get_hot_deal_ids( 'on_sale', $limit + 1 ),
						array( $product->get_id() )
					)
				);
				break;
			default:
				$ids = $engine->get_related_ids( $product->get_id(), $limit );
		}

		$ids = array_slice( $ids, 0, $limit );

		if ( empty( $ids ) ) {
			return;
		}

		$heading = isset( $options['heading'] ) && '' !== trim( (string) $options['heading'] )
			? (string) $options['heading']
			: __( 'You may also like', 'commercebuild-velocity' );

		/**
		 * Filter the related-products heading.
		 *
		 * @since 0.12.21
		 *
		 * @param string     $heading Heading.
		 * @param WC_Product $product Product.
		 * @param array      $options Element options.
		 */
		$heading = apply_filters( 'cbv_related_heading', $heading, $product, $options );

		self::render_grid(
			$heading,
			$ids,
			self::columns( $options, 4 ),
			'related',
			$options
		);
	}

	/**
	 * Italic trade-pricing notice.
	 *
	 * @since 0.12.21
	 *
	 * @param WC_Product $product Product.
	 * @param array      $options Options.
	 * @return void
	 */
	private static function render_trade_notice( $product, array $options = array() ) {
		$text = isset( $options['text'] ) ? trim( (string) $options['text'] ) : '';

		if ( '' === $text ) {
			$text = __( 'Trade pricing applied at checkout', 'commercebuild-velocity' );
		}

		/**
		 * Filter the trade pricing notice.
		 *
		 * Return an empty string to hide it.
		 *
		 * @since 0.12.21
		 *
		 * @param string     $text    Notice text.
		 * @param WC_Product $product Product.
		 */
		$text = apply_filters( 'cbv_trade_notice', $text, $product );

		if ( '' === $text ) {
			return;
		}

		printf( '<p class="cbv-pe cbv-pe--trade-notice">%s</p>', esc_html( $text ) );
	}

	/**
	 * Become-a-customer box.
	 *
	 * @since 0.12.21
	 *
	 * @param WC_Product $product Product.
	 * @param array      $options Options.
	 * @return void
	 */
	private static function render_account_cta( $product, array $options = array() ) {
		if ( is_user_logged_in() ) {
			return;
		}

		$text = isset( $options['text'] ) ? trim( (string) $options['text'] ) : '';
		$button = isset( $options['button'] ) ? trim( (string) $options['button'] ) : '';

		if ( '' === $text ) {
			$text = __( 'Registered trade accounts get exclusive pricing, saved order guides & dedicated rep support.', 'commercebuild-velocity' );
		}

		if ( '' === $button ) {
			$button = __( 'Become a Customer +', 'commercebuild-velocity' );
		}

		$url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url();

		/**
		 * Filter the product-page account CTA.
		 *
		 * Return an empty text value to hide the box.
		 *
		 * @since 0.12.21
		 *
		 * @param array      $cta     text, button, url.
		 * @param WC_Product $product Product.
		 */
		$cta = apply_filters(
			'cbv_product_account_cta',
			array(
				'text'   => $text,
				'button' => $button,
				'url'    => $url,
			),
			$product
		);

		if ( empty( $cta['text'] ) ) {
			return;
		}

		echo '<div class="cbv-pe cbv-pe--account-cta">';
		echo '<p>' . esc_html( $cta['text'] ) . '</p>';

		if ( ! empty( $cta['button'] ) && ! empty( $cta['url'] ) ) {
			printf(
				'<a href="%s">%s</a>',
				esc_url( $cta['url'] ),
				esc_html( $cta['button'] )
			);
		}

		echo '</div>';
	}

	/**
	 * SKU and stock on one row.
	 *
	 * @since 0.12.21
	 *
	 * @param WC_Product $product Product.
	 * @return void
	 */
	private static function render_sku_row( $product ) {
		$sku = (string) $product->get_meta( 'original_sku' );

		if ( '' === $sku ) {
			$sku = (string) $product->get_sku();
		}

		/** This filter is documented in inc/template-tags.php */
		$sku = apply_filters( 'cbv_product_card_sku', $sku, $product );

		$status = $product->get_stock_status();

		// Honor an explicit on-backorder state. A product can be purchasable
		// while backordered because Woo treats backorders as in stock.
		if ( 'outofstock' === $status && $product->is_in_stock() ) {
			$status = 'instock';
		}

		$labels = array(
			'instock'     => __( 'In Stock', 'commercebuild-velocity' ),
			'outofstock'  => __( 'Out of Stock', 'commercebuild-velocity' ),
			'onbackorder' => __( 'On backorder', 'commercebuild-velocity' ),
		);
		$label = isset( $labels[ $status ] ) ? $labels[ $status ] : $status;

		if ( '' === $sku && '' === $label ) {
			return;
		}

		echo '<div class="cbv-pe cbv-pe--sku-row">';

		if ( '' !== $sku ) {
			printf(
				'<span class="cbv-sku-row__sku">%s %s</span>',
				esc_html__( 'SKU', 'commercebuild-velocity' ),
				esc_html( $sku )
			);
		}

		printf(
			'<span class="cbv-stock cbv-stock--%1$s">%2$s</span>',
			esc_attr( $status ),
			esc_html( $label )
		);

		echo '</div>';
	}

	/**
	 * Pack size boxes.
	 *
	 * @since 0.12.21
	 *
	 * @param WC_Product $product Product.
	 * @return void
	 */
	private static function render_pack_size( $product ) {
		$sizes = array();
		$uom   = apply_filters( 'cbv_product_card_uom', (string) $product->get_meta( 'uom' ), $product );

		if ( '' !== $uom ) {
			$sizes[] = array(
				'label'    => $uom,
				'selected' => true,
				'url'      => '',
			);
		}

		/**
		 * Filter pack-size options on the product page.
		 *
		 * Each item: label, selected (bool), url (optional).
		 *
		 * @since 0.12.21
		 *
		 * @param array      $sizes   Options.
		 * @param WC_Product $product Product.
		 */
		$sizes = apply_filters( 'cbv_product_pack_sizes', $sizes, $product );

		if ( empty( $sizes ) || ! is_array( $sizes ) ) {
			return;
		}

		echo '<div class="cbv-pe cbv-pe--pack-size">';
		echo '<span class="cbv-pack-size__label">' . esc_html__( 'Pack Size', 'commercebuild-velocity' ) . '</span>';
		echo '<div class="cbv-pack-size__options">';

		foreach ( $sizes as $size ) {
			if ( empty( $size['label'] ) ) {
				continue;
			}

			$selected = ! empty( $size['selected'] ) ? ' is-selected' : '';
			$url      = isset( $size['url'] ) ? (string) $size['url'] : '';

			if ( '' !== $url ) {
				printf(
					'<a class="cbv-pack-size__option%s" href="%s">%s</a>',
					esc_attr( $selected ),
					esc_url( $url ),
					esc_html( $size['label'] )
				);
			} else {
				printf(
					'<span class="cbv-pack-size__option%s">%s</span>',
					esc_attr( $selected ),
					esc_html( $size['label'] )
				);
			}
		}

		echo '</div></div>';
	}

	/**
	 * Email and compare actions.
	 *
	 * @since 0.12.21
	 *
	 * @param WC_Product $product Product.
	 * @return void
	 */
	private static function render_actions( $product ) {
		$subject = rawurlencode( $product->get_name() );
		$body    = rawurlencode( $product->get_name() . "\n" . $product->get_permalink() );
		$email   = 'mailto:?subject=' . $subject . '&body=' . $body;

		/**
		 * Filter the product email share URL.
		 *
		 * @since 0.12.21
		 *
		 * @param string     $email   mailto URL.
		 * @param WC_Product $product Product.
		 */
		$email = apply_filters( 'cbv_product_email_url', $email, $product );

		/**
		 * Filter the product compare URL.
		 *
		 * Return empty to keep a button without a destination.
		 *
		 * @since 0.12.21
		 *
		 * @param string     $url     Compare URL.
		 * @param WC_Product $product Product.
		 */
		$compare = apply_filters( 'cbv_product_compare_url', '', $product );

		echo '<div class="cbv-pe cbv-pe--actions">';
		printf(
			'<a class="cbv-button cbv-button--ghost-dark" href="%s">%s %s</a>',
			esc_url( $email ),
			wp_kses( cbv_icon( 'mail', array( 'size' => 18, 'stroke_width' => 1.75 ) ), CBV_Icons::kses_allowed() ),
			esc_html__( 'Email', 'commercebuild-velocity' )
		);

		$compare_icon = wp_kses( cbv_icon( 'compare', array( 'size' => 18, 'stroke_width' => 1.75 ) ), CBV_Icons::kses_allowed() );

		if ( '' !== $compare ) {
			printf(
				'<a class="cbv-button cbv-button--ghost-dark" href="%s">%s %s</a>',
				esc_url( $compare ),
				$compare_icon, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses'd SVG.
				esc_html__( 'Compare', 'commercebuild-velocity' )
			);
		} else {
			printf(
				'<button type="button" class="cbv-button cbv-button--ghost-dark" data-cbv-compare="%s">%s %s</button>',
				esc_attr( (string) $product->get_id() ),
				$compare_icon, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses'd SVG.
				esc_html__( 'Compare', 'commercebuild-velocity' )
			);
		}

		echo '</div>';
	}

	/**
	 * Homepage CTA band, reused on the product page.
	 *
	 * @since 0.12.21
	 *
	 * @return void
	 */
	private static function render_cta_band() {
		if ( ! function_exists( 'cbv_the_cta_band' ) ) {
			return;
		}

		echo '<div class="cbv-pe cbv-pe--cta-band">';
		cbv_the_cta_band();
		echo '</div>';
	}

	/**
	 * Breadcrumb defaults for the product page.
	 *
	 * @since 0.12.21
	 *
	 * @param array $defaults WooCommerce breadcrumb defaults.
	 * @return array
	 */
	public static function breadcrumb_defaults( $defaults ) {
		$defaults['delimiter']   = '<span class="cbv-breadcrumb__sep" aria-hidden="true"></span>';
		$defaults['wrap_before'] = '<nav class="woocommerce-breadcrumb cbv-breadcrumb">';
		$defaults['wrap_after']  = '</nav>';

		return $defaults;
	}

	/**
	 * Relabel and keep product tabs visible.
	 *
	 * @since 0.12.21
	 *
	 * @param array $tabs WooCommerce tabs.
	 * @return array
	 */
	public static function filter_tabs( $tabs ) {
		if ( ! is_array( $tabs ) ) {
			$tabs = array();
		}

		if ( ! isset( $tabs['description'] ) ) {
			$tabs['description'] = array(
				'title'    => __( 'Details', 'commercebuild-velocity' ),
				'priority' => 10,
				'callback' => array( __CLASS__, 'render_description_tab' ),
			);
		} else {
			$tabs['description']['title']    = __( 'Details', 'commercebuild-velocity' );
			$tabs['description']['callback'] = array( __CLASS__, 'render_description_tab' );
		}

		$more_title = '<span class="cbv-tab-full">' . esc_html__( 'More Information', 'commercebuild-velocity' ) . '</span><span class="cbv-tab-short">' . esc_html__( 'More Info', 'commercebuild-velocity' ) . '</span>';

		if ( ! isset( $tabs['additional_information'] ) ) {
			$tabs['additional_information'] = array(
				'title'    => $more_title,
				'priority' => 20,
				'callback' => 'woocommerce_product_additional_information_tab',
			);
		} else {
			$tabs['additional_information']['title'] = $more_title;
		}

		global $product;

		$count = ( $product instanceof WC_Product ) ? (int) $product->get_review_count() : 0;
		$reviews_title = sprintf(
			/* translators: %s: review count. */
			__( 'Reviews (%s)', 'commercebuild-velocity' ),
			number_format_i18n( $count )
		);

		if ( isset( $tabs['reviews'] ) ) {
			$tabs['reviews']['title'] = $reviews_title;
		} else {
			$tabs['reviews'] = array(
				'title'    => $reviews_title,
				'priority' => 30,
				'callback' => 'comments_template',
			);
		}

		/**
		 * Filter product tabs after Velocity relabels them.
		 *
		 * @since 0.12.21
		 *
		 * @param array $tabs WooCommerce tabs.
		 */
		return apply_filters( 'cbv_product_tabs', $tabs );
	}

	/**
	 * Details tab body. Uses the product description only, not the_content,
	 * so empty Sage copy cannot pick up price HTML from content filters.
	 *
	 * @since 0.12.32
	 *
	 * @return void
	 */
	public static function render_description_tab() {
		global $product;

		$heading = apply_filters( 'woocommerce_product_description_heading', __( 'Description', 'woocommerce' ) );

		if ( $heading ) {
			echo '<h2>' . esc_html( $heading ) . '</h2>';
		}

		$raw = '';

		if ( $product instanceof WC_Product ) {
			$raw = (string) get_post_field( 'post_content', $product->get_id() );
		}

		$plain = strtolower( trim( wp_strip_all_tags( $raw ) ) );

		if ( '' === $plain || false !== strpos( $plain, 'contact for pricing' ) ) {
			return;
		}

		echo wp_kses_post( wpautop( do_shortcode( $raw ) ) );
	}

	/**
	 * Ordered-before badge on the gallery.
	 *
	 * @since 0.12.21
	 *
	 * @param WC_Product $product Product.
	 * @return void
	 */
	public static function output_gallery_badge( $product ) {
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$label = '';

		if ( is_user_logged_in() && function_exists( 'wc_customer_bought_product' ) ) {
			$user = wp_get_current_user();

			if ( wc_customer_bought_product( $user->user_email, $user->ID, $product->get_id() ) ) {
				$label = __( 'Ordered before', 'commercebuild-velocity' );
			}
		}

		/**
		 * Filter the product gallery badge label.
		 *
		 * Return an empty string to hide it.
		 *
		 * @since 0.12.21
		 *
		 * @param string     $label   Badge text.
		 * @param WC_Product $product Product.
		 */
		$label = apply_filters( 'cbv_product_gallery_badge', $label, $product );

		if ( '' === $label ) {
			return;
		}

		printf( '<span class="cbv-gallery-badge">%s</span>', esc_html( $label ) );
	}

	/**
	 * Add to cart button label on the product page and loop cards.
	 *
	 * @since 0.12.21
	 * @since 0.12.35 Also used for loop/related cards (not Read more).
	 *
	 * @param string $text Default label.
	 * @return string
	 */
	public static function add_to_cart_text( $text ) {
		unset( $text );

		return __( 'Add to cart', 'commercebuild-velocity' );
	}

	/**
	 * Shared label/value row.
	 *
	 * @since 0.7.0
	 *
	 * @param string $label    Label.
	 * @param string $value    Value.
	 * @param string $modifier CSS modifier.
	 * @return void
	 */
	private static function render_pair( $label, $value, $modifier ) {
		printf(
			'<div class="cbv-pe cbv-pe--%1$s"><span class="cbv-pe__label">%2$s</span> <span class="cbv-pe__value">%3$s</span></div>',
			esc_attr( $modifier ),
			esc_html( $label ),
			esc_html( $value )
		);
	}

	/**
	 * Shared product grid section.
	 *
	 * @since 0.7.0
	 *
	 * @param string $heading  Section heading.
	 * @param int[]  $ids      Product IDs.
	 * @param int    $columns  Columns.
	 * @param string $modifier CSS modifier.
	 * @return void
	 */
	private static function render_grid( $heading, array $ids, $columns, $modifier, array $options = array() ) {
		$card_args = array(
			'show_sku'          => true,
			'show_pack'         => false,
			'show_uom'          => false,
			'show_category'     => true,
			'show_price_suffix' => true,
		);

		/**
		 * Filter card args for a product-page grid (related, upsells, cross-sells).
		 *
		 * @since 0.12.21
		 *
		 * @param array  $card_args Card args.
		 * @param string $modifier  Grid slug.
		 * @param int[]  $ids       Product IDs.
		 */
		$card_args = apply_filters( 'cbv_product_element_grid_card_args', $card_args, $modifier, $ids );

		$grid = cbv_product_grid( $ids, $card_args, $columns );

		if ( '' === $grid ) {
			return;
		}

		$footer_label = isset( $options['footer_label'] ) ? (string) $options['footer_label'] : '';

		/**
		 * Filter the catalogue button label under a product grid.
		 *
		 * Return an empty string to hide the button.
		 *
		 * @since 0.12.21
		 *
		 * @param string $footer_label Button label.
		 * @param string $modifier     Grid slug.
		 * @param array  $options      Element options.
		 */
		$footer_label = apply_filters( 'cbv_product_grid_footer_label', $footer_label, $modifier, $options );

		$footer = '';

		if ( '' !== trim( $footer_label ) ) {
			$url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );

			/**
			 * Filter the catalogue button URL under a product grid.
			 *
			 * @since 0.12.21
			 *
			 * @param string $url      URL.
			 * @param string $modifier Grid slug.
			 * @param array  $options  Element options.
			 */
			$url = apply_filters( 'cbv_product_grid_footer_url', $url, $modifier, $options );

			$footer = sprintf(
				'<div class="cbv-related-footer"><a class="cbv-button" href="%s">%s</a></div>',
				esc_url( $url ),
				esc_html( $footer_label )
			);
		}

		printf(
			'<section class="cbv-pe cbv-pe--%1$s"><h2 class="cbv-block__heading">%2$s</h2>%3$s%4$s</section>',
			esc_attr( $modifier ),
			esc_html( $heading ),
			$grid, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped during card rendering.
			$footer // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built with escaped values above.
		);
	}

	/**
	 * Clamp a limit option.
	 *
	 * @since 0.7.0
	 *
	 * @param array $options Options.
	 * @param int   $default Default value.
	 * @return int
	 */
	private static function limit( array $options, $default ) {
		$limit = isset( $options['limit'] ) ? absint( $options['limit'] ) : $default;

		return min( 12, max( 1, $limit ) );
	}

	/**
	 * Clamp a columns option.
	 *
	 * @since 0.7.0
	 *
	 * @param array $options Options.
	 * @param int   $default Default value.
	 * @return int
	 */
	private static function columns( array $options, $default ) {
		$columns = isset( $options['columns'] ) ? absint( $options['columns'] ) : $default;

		return min( 6, max( 1, $columns ) );
	}
}
