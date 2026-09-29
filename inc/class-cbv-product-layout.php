<?php
/**
 * Product page layout.
 *
 * Replaces WooCommerce's fixed single-product hook order with an ordered,
 * toggleable element list stored in one theme mod, edited in the Customizer.
 *
 * Rendering cost is deliberately flat: the layout is a small array read once
 * per request, and every element reads from the already-loaded WC_Product
 * object, so page cost does not grow with catalog size.
 *
 * @package Commercebuild_Velocity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ordered product page rendering.
 *
 * @since 0.7.0
 */
class CBV_Product_Layout {

	/**
	 * Theme mod storing the layout.
	 *
	 * @var string
	 */
	const MOD = 'cbv_product_layout';

	/**
	 * Register hooks.
	 *
	 * @since 0.7.0
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wp', array( $this, 'apply_layout' ) );
		add_action( 'customize_register', array( $this, 'add_controls' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'output_column_vars' ), 21 );
	}

	/**
	 * Publish the configured column widths as CSS variables.
	 *
	 * @since 0.9.0
	 *
	 * @return void
	 */
	public function output_column_vars() {
		$gallery = min( 70, max( 30, (int) get_theme_mod( 'cbv_product_gallery_ratio', 48 ) ) );
		$catalog = CBV_Catalog_Layout::columns();

		$css = sprintf(
			':root{--cbv-product-gallery:%1$dfr;--cbv-product-summary:%2$dfr;--cbv-catalog-sidebar:%3$dpx;--cbv-catalog-cols:%4$d;}',
			$gallery,
			100 - $gallery,
			$catalog['width'],
			$catalog['products']
		);

		wp_add_inline_style( 'cbv-main', $css );
	}

	/**
	 * Register the Product Page section and layout control.
	 *
	 * @since 0.7.0
	 *
	 * @param WP_Customize_Manager $wp_customize Customizer manager.
	 * @return void
	 */
	public function add_controls( $wp_customize ) {
		require_once CBV_DIR . 'inc/class-cbv-layout-control.php';

		if ( ! class_exists( 'CBV_Layout_Control' ) ) {
			return;
		}

		$wp_customize->add_section(
			'cbv_product_page',
			array(
				'title'       => __( 'Product Page', 'commercebuild-velocity' ),
				'priority'    => 29,
				'description' => __( 'Drag to reorder, untick to hide. Elements with a gear have their own settings.', 'commercebuild-velocity' ),
			)
		);

		$wp_customize->add_setting(
			'cbv_product_gallery_ratio',
			array(
				'default'           => 48,
				'sanitize_callback' => 'absint',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'cbv_product_gallery_ratio',
			array(
				'label'       => __( 'Gallery column width (%)', 'commercebuild-velocity' ),
				'description' => __( 'The summary column takes the rest.', 'commercebuild-velocity' ),
				'section'     => 'cbv_product_page',
				'type'        => 'number',
				'input_attrs' => array(
					'min'  => 30,
					'max'  => 70,
					'step' => 5,
				),
			)
		);

		$wp_customize->add_setting(
			self::MOD,
			array(
				'default'           => '',
				'sanitize_callback' => array( $this, 'sanitize_layout' ),
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			new CBV_Layout_Control(
				$wp_customize,
				self::MOD,
				array(
					'section' => 'cbv_product_page',
					'label'   => __( 'Product page elements', 'commercebuild-velocity' ),
					'areas'   => array(
						'above'   => __( 'Above the product', 'commercebuild-velocity' ),
						'summary' => __( 'Summary column', 'commercebuild-velocity' ),
						'after'   => __( 'Below the product', 'commercebuild-velocity' ),
					),
				)
			)
		);
	}

	/**
	 * Sanitize the serialized layout.
	 *
	 * Unknown element ids are dropped and every option is coerced to the type
	 * declared in the element schema, so a malformed payload can never reach
	 * a renderer.
	 *
	 * @since 0.7.0
	 *
	 * @param string $value Raw JSON from the control.
	 * @return string Sanitized JSON.
	 */
	public function sanitize_layout( $value ) {
		$decoded = json_decode( (string) $value, true );

		if ( ! is_array( $decoded ) ) {
			return '';
		}

		$definitions = CBV_Product_Elements::definitions();
		$schema      = CBV_Product_Elements::option_schema();
		$areas       = array_keys( self::default_layout() );
		$clean       = array();

		$used = array();

		foreach ( $areas as $area ) {
			if ( empty( $decoded[ $area ] ) || ! is_array( $decoded[ $area ] ) ) {
				continue;
			}

			$clean[ $area ] = array();

			foreach ( $decoded[ $area ] as $row ) {
				if ( empty( $row['id'] ) || ! isset( $definitions[ $row['id'] ] ) ) {
					continue;
				}

				$id = (string) $row['id'];

				// One instance per element, and only in the area it declares:
				// several elements (quantity breaks, the cart form) are
				// single-instance by nature.
				if ( isset( $used[ $id ] ) ) {
					continue;
				}

				$declared_area = isset( $definitions[ $id ]['area'] ) ? $definitions[ $id ]['area'] : 'summary';

				if ( $declared_area !== $area ) {
					continue;
				}

				$used[ $id ] = true;
				$options     = array();

				if ( isset( $schema[ $id ] ) && isset( $row['options'] ) && is_array( $row['options'] ) ) {
					foreach ( $schema[ $id ] as $key => $field ) {
						if ( ! array_key_exists( $key, $row['options'] ) ) {
							continue;
						}

						$raw = $row['options'][ $key ];

						switch ( $field['type'] ) {
							case 'checkbox':
								$options[ $key ] = (bool) $raw;
								break;
							case 'number':
								$min             = isset( $field['min'] ) ? (int) $field['min'] : 1;
								$max             = isset( $field['max'] ) ? (int) $field['max'] : 12;
								$options[ $key ] = min( $max, max( $min, absint( $raw ) ) );
								break;
							case 'select':
								$options[ $key ] = isset( $field['choices'][ $raw ] ) ? (string) $raw : (string) $field['default'];
								break;
							case 'textarea':
								$options[ $key ] = sanitize_textarea_field( (string) $raw );
								break;
							default:
								$options[ $key ] = sanitize_text_field( (string) $raw );
						}
					}
				}

				$clean[ $area ][] = array(
					'id'      => $id,
					'enabled' => ! empty( $row['enabled'] ),
					'options' => $options,
				);
			}
		}

		return (string) wp_json_encode( $clean );
	}

	/**
	 * Default layout: mirrors a conventional WooCommerce product page, with
	 * the commerce-critical extras enabled where the data exists.
	 *
	 * @since 0.7.0
	 *
	 * @return array
	 */
	public static function default_layout() {
		$layout = array(
			'above'   => array(
				array(
					'id'      => 'breadcrumbs',
					'enabled' => true,
				),
			),
			'summary' => array(
				array(
					'id'      => 'title',
					'enabled' => true,
				),
				array(
					'id'      => 'rating',
					'enabled' => true,
				),
				array(
					'id'      => 'trade_notice',
					'enabled' => true,
				),
				array(
					'id'      => 'price',
					'enabled' => true,
				),
				array(
					'id'      => 'poa',
					'enabled' => true,
				),
				array(
					'id'      => 'account_cta',
					'enabled' => true,
				),
				array(
					'id'      => 'sku_row',
					'enabled' => true,
				),
				array(
					'id'      => 'sku',
					'enabled' => false,
				),
				array(
					'id'      => 'uom',
					'enabled' => false,
				),
				array(
					'id'      => 'msrp',
					'enabled' => false,
				),
				array(
					'id'      => 'stock_status',
					'enabled' => false,
				),
				array(
					'id'      => 'stock_qty',
					'enabled' => false,
				),
				array(
					'id'      => 'warehouse_stock',
					'enabled' => false,
				),
				array(
					'id'      => 'short_description',
					'enabled' => false,
				),
				array(
					'id'      => 'qty_in_cart',
					'enabled' => false,
				),
				array(
					'id'      => 'uom_selector',
					'enabled' => false,
				),
				array(
					'id'      => 'quantity_breaks',
					'enabled' => false,
				),
				array(
					'id'      => 'pack_size',
					'enabled' => true,
				),
				array(
					'id'      => 'add_to_cart',
					'enabled' => true,
					'options' => array( 'hide_quantity' => false ),
				),
				array(
					'id'      => 'purchase_order',
					'enabled' => false,
				),
				array(
					'id'      => 'prop65',
					'enabled' => true,
				),
				array(
					'id'      => 'sharing',
					'enabled' => false,
				),
				array(
					'id'      => 'actions',
					'enabled' => true,
				),
				array(
					'id'      => 'shortcode',
					'enabled' => false,
					'options' => array( 'shortcode' => '' ),
				),
				array(
					'id'      => 'divider',
					'enabled' => false,
				),
				array(
					'id'      => 'attributes',
					'enabled' => false,
					'options' => array( 'include' => '' ),
				),
				array(
					'id'      => 'meta_fields',
					'enabled' => false,
					'options' => array( 'fields' => '' ),
				),
				array(
					'id'      => 'categories',
					'enabled' => false,
				),
			),
			'after'   => array(
				array(
					'id'      => 'tabs',
					'enabled' => true,
				),
				array(
					'id'      => 'description',
					'enabled' => false,
				),
				array(
					'id'      => 'upsells',
					'enabled' => false,
					'options' => array(
						'limit'   => 4,
						'columns' => 4,
					),
				),
				array(
					'id'      => 'related',
					'enabled' => true,
					'options' => array(
						'source'       => 'related',
						'limit'        => 4,
						'columns'      => 4,
						'footer_label' => '',
					),
				),
				array(
					'id'      => 'cross_sells',
					'enabled' => false,
					'options' => array(
						'limit'   => 4,
						'columns' => 4,
					),
				),
				array(
					'id'      => 'cta_band',
					'enabled' => true,
				),
				array(
					'id'      => 'shortcode_below',
					'enabled' => false,
					'options' => array( 'shortcode' => '' ),
				),
			),
		);

		/**
		 * Filter the default product page layout.
		 *
		 * @since 0.7.0
		 *
		 * @param array $layout Default layout by area.
		 */
		return apply_filters( 'cbv_default_product_layout', $layout );
	}

	/**
	 * Current layout, reconciled against the element library so elements
	 * added by a later theme version appear without resetting the customer's
	 * ordering.
	 *
	 * @since 0.7.0
	 *
	 * @return array
	 */
	public static function layout() {
		static $layout = null;

		if ( null !== $layout ) {
			return $layout;
		}

		$defaults = self::default_layout();
		$stored   = json_decode( (string) get_theme_mod( self::MOD, '' ), true );

		if ( ! is_array( $stored ) ) {
			$layout = $defaults;
		} else {
			$layout      = array();
			$definitions = CBV_Product_Elements::definitions();

			foreach ( $defaults as $area => $default_rows ) {
				$rows = isset( $stored[ $area ] ) && is_array( $stored[ $area ] ) ? $stored[ $area ] : array();
				$seen = array();
				$out  = array();

				foreach ( $rows as $row ) {
					if ( empty( $row['id'] ) || ! isset( $definitions[ $row['id'] ] ) ) {
						continue;
					}

					$out[] = array(
						'id'      => (string) $row['id'],
						'enabled' => ! empty( $row['enabled'] ),
						'options' => isset( $row['options'] ) && is_array( $row['options'] ) ? $row['options'] : array(),
					);

					$seen[ $row['id'] ] = true;
				}

				// Append any element the stored layout has never seen.
				foreach ( $default_rows as $default_row ) {
					if ( ! isset( $seen[ $default_row['id'] ] ) ) {
						$out[] = $default_row;
					}
				}

				$layout[ $area ] = $out;
			}
		}

		/**
		 * Filter the resolved product page layout.
		 *
		 * @since 0.7.0
		 *
		 * @param array $layout Layout by area.
		 */
		$layout = apply_filters( 'cbv_product_layout', $layout );

		return $layout;
	}

	/**
	 * Swap WooCommerce's default single-product output for the configured layout.
	 *
	 * @since 0.7.0
	 *
	 * @return void
	 */
	public function apply_layout() {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		// Summary column.
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50 );

		// Below the summary.
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );

		// Breadcrumbs are an element, so drop the themed default.
		remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );

		add_filter( 'woocommerce_breadcrumb_defaults', array( 'CBV_Product_Elements', 'breadcrumb_defaults' ) );
		add_filter( 'woocommerce_product_tabs', array( 'CBV_Product_Elements', 'filter_tabs' ) );
		add_action( 'cbv_product_gallery_before', array( 'CBV_Product_Elements', 'output_gallery_badge' ) );
		add_filter( 'woocommerce_product_single_add_to_cart_text', array( 'CBV_Product_Elements', 'add_to_cart_text' ) );

		$this->release_plugin_owned_output();

		add_action( 'woocommerce_before_main_content', array( $this, 'render_above' ), 20 );
		add_action( 'woocommerce_after_single_product_summary', array( $this, 'render_after' ), 10 );

		$layout = self::layout();
		$rows   = isset( $layout['summary'] ) ? array_values( $layout['summary'] ) : array();

		if ( empty( $rows ) ) {
			return;
		}

		foreach ( self::summary_priorities( $rows ) as $index => $priority ) {
			$row = $rows[ $index ];

			if ( empty( $row['enabled'] ) ) {
				continue;
			}

			add_action(
				'woocommerce_single_product_summary',
				function () use ( $row ) {
					$this->render_element( $row );
				},
				$priority
			);
		}
	}

	/**
	 * Assign a hook priority to each summary row.
	 *
	 * Elements that replace a stock WooCommerce callback keep that callback's
	 * canonical priority, so plugins targeting the well-known landmarks (a
	 * badge at 15, a notice at 25) still interleave exactly where their
	 * authors intended. Everything else is placed in the gaps between its
	 * anchored neighbours, preserving the merchant's chosen order.
	 *
	 * @since 0.8.0
	 *
	 * @param array $rows Ordered summary rows.
	 * @return array Row index => priority.
	 */
	public static function summary_priorities( array $rows ) {
		$anchors = array(
			'title'             => 5,
			'rating'            => 10,
			'price'             => 10,
			'short_description' => 20,
			'add_to_cart'       => 30,
			'categories'        => 40,
			'sharing'           => 50,
		);

		/**
		 * Filter the canonical priorities used to anchor summary elements.
		 *
		 * @since 0.8.0
		 *
		 * @param array $anchors Element id => WooCommerce priority.
		 */
		$anchors = apply_filters( 'cbv_summary_priority_anchors', $anchors );

		$count      = count( $rows );
		$priorities = array();
		$anchor_at  = array();

		foreach ( $rows as $index => $row ) {
			if ( isset( $anchors[ $row['id'] ] ) ) {
				$anchor_at[ $index ] = (int) $anchors[ $row['id'] ];
			}
		}

		// Unanchored elements interpolate between the surrounding anchors.
		$previous_index    = -1;
		$previous_priority = 1;

		foreach ( $rows as $index => $row ) {
			if ( isset( $anchor_at[ $index ] ) ) {
				$priorities[ $index ] = $anchor_at[ $index ];
				$previous_index       = $index;
				$previous_priority    = $anchor_at[ $index ];
				continue;
			}

			$next_index    = null;
			$next_priority = 60;

			foreach ( $anchor_at as $candidate_index => $candidate_priority ) {
				if ( $candidate_index > $index ) {
					$next_index    = $candidate_index;
					$next_priority = $candidate_priority;
					break;
				}
			}

			$gap  = max( 1, $next_priority - $previous_priority );
			$span = ( null === $next_index ) ? ( $count - $previous_index ) : ( $next_index - $previous_index );
			$step = $gap / max( 1, $span );

			$priorities[ $index ] = (int) max(
				1,
				min( $next_priority, round( $previous_priority + ( ( $index - $previous_index ) * $step ) ) )
			);
		}

		return $priorities;
	}

	/**
	 * Detach plugin callbacks whose output this layout now owns.
	 *
	 * The cfw_core plugin renders the POA message and Prop 65 warning on its
	 * own summary
	 * priorities. Once those exist as layout elements the plugin copies must
	 * go, or the page shows each notice twice and the element's position and
	 * enable toggle do nothing. Only the presentational callbacks are removed:
	 * cfw_core's behavioural hooks (hiding add-to-cart for POA, price
	 * filtering, variation data) stay attached.
	 *
	 * @since 0.8.0
	 *
	 * @return void
	 */
	private function release_plugin_owned_output() {
		$layout = self::layout();
		$owned  = array();

		foreach ( $layout as $rows ) {
			foreach ( $rows as $row ) {
				$owned[ $row['id'] ] = true;
			}
		}

		$map = array(
			'poa'    => array( 'renderPOAMessage', 'PriceOnApplication' ),
			'prop65' => array( 'renderWarning', 'Prop65' ),
		);

		foreach ( $map as $element => $target ) {
			if ( isset( $owned[ $element ] ) ) {
				self::remove_method_callbacks( 'woocommerce_single_product_summary', $target[0], $target[1] );
			}
		}
	}

	/**
	 * Remove every callback on a hook that points at the given object method.
	 *
	 * @since 0.8.0
	 *
	 * @param string $hook      Hook name.
	 * @param string $method    Method name to detach.
	 * @param string $namespace Substring the owning class must contain, so an
	 *                          unrelated plugin using the same method name is
	 *                          never detached.
	 * @return void
	 */
	private static function remove_method_callbacks( $hook, $method, $namespace ) {
		global $wp_filter;

		if ( empty( $wp_filter[ $hook ] ) || ! $wp_filter[ $hook ] instanceof WP_Hook ) {
			return;
		}

		foreach ( $wp_filter[ $hook ]->callbacks as $priority => $callbacks ) {
			foreach ( $callbacks as $callback ) {
				if (
					is_array( $callback['function'] )
					&& is_object( $callback['function'][0] )
					&& $method === $callback['function'][1]
					&& false !== strpos( get_class( $callback['function'][0] ), $namespace )
				) {
					remove_action( $hook, $callback['function'], $priority );
				}
			}
		}
	}

	/**
	 * Render a single configured element.
	 *
	 * @since 0.8.0
	 *
	 * @param array $row Layout row.
	 * @return void
	 */
	public function render_element( array $row ) {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			$product = wc_get_product( get_the_ID() ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restores the expected global for element rendering.
		}

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		CBV_Product_Elements::render(
			$row['id'],
			$product,
			isset( $row['options'] ) ? (array) $row['options'] : array()
		);
	}

	/**
	 * Render the area above the product.
	 *
	 * @since 0.7.0
	 *
	 * @return void
	 */
	public function render_above() {
		$this->render_area( 'above' );
	}

	/**
	 * Render the area below the summary.
	 *
	 * @since 0.7.0
	 *
	 * @return void
	 */
	public function render_after() {
		$this->render_area( 'after' );
	}

	/**
	 * Render one configured area.
	 *
	 * @since 0.7.0
	 *
	 * @param string $area Area key.
	 * @return void
	 */
	private function render_area( $area ) {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			$product = wc_get_product( get_the_ID() ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restores the expected global for element rendering.
		}

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$layout = self::layout();

		if ( empty( $layout[ $area ] ) ) {
			return;
		}

		/**
		 * Fires before a product layout area renders.
		 *
		 * @since 0.7.0
		 *
		 * @param string     $area    Area key.
		 * @param WC_Product $product Product object.
		 */
		do_action( 'cbv_product_area_before', $area, $product );

		foreach ( $layout[ $area ] as $row ) {
			if ( empty( $row['enabled'] ) ) {
				continue;
			}

			CBV_Product_Elements::render(
				$row['id'],
				$product,
				isset( $row['options'] ) ? (array) $row['options'] : array()
			);
		}

		/**
		 * Fires after a product layout area renders.
		 *
		 * @since 0.7.0
		 *
		 * @param string     $area    Area key.
		 * @param WC_Product $product Product object.
		 */
		do_action( 'cbv_product_area_after', $area, $product );
	}
}
