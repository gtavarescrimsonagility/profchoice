<?php
/**
 * Catalog page element library.
 *
 * Powers shop, product category, product tag, and search result pages, which
 * share one layout because they are the same template with a different query.
 *
 * @package Commercebuild_Velocity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renderers and metadata for catalog page elements.
 *
 * @since 0.9.0
 */
class CBV_Catalog_Elements {

	/**
	 * Element definitions.
	 *
	 * Areas: above (full width), sidebar, toolbar, content, below.
	 *
	 * @since 0.9.0
	 *
	 * @return array
	 */
	public static function definitions() {
		$definitions = array(
			'breadcrumbs'       => array(
				'label' => __( 'Breadcrumbs', 'commercebuild-velocity' ),
				'area'  => 'above',
			),
			'page_title'        => array(
				'label' => __( 'Page title', 'commercebuild-velocity' ),
				'area'  => 'above',
			),
			'term_description'  => array(
				'label' => __( 'Category description', 'commercebuild-velocity' ),
				'area'  => 'above',
			),
			'subcategories'     => array(
				'label'       => __( 'Subcategory tiles', 'commercebuild-velocity' ),
				'area'        => 'above',
				'description' => __( 'Children of the current category, or top level on the shop page.', 'commercebuild-velocity' ),
			),
			'filters'           => array(
				'label'       => __( 'Faceted filters', 'commercebuild-velocity' ),
				'area'        => 'sidebar',
				'description' => __( 'Renders the commercebuild Elasticsearch filters shortcode.', 'commercebuild-velocity' ),
			),
			'sidebar_shortcode' => array(
				'label'       => __( 'Shortcode (sidebar)', 'commercebuild-velocity' ),
				'area'        => 'sidebar',
				'description' => __( 'Any shortcode, e.g. a promo or contact block.', 'commercebuild-velocity' ),
			),
			'sidebar_widgets'   => array(
				'label' => __( 'Shop sidebar widgets', 'commercebuild-velocity' ),
				'area'  => 'sidebar',
			),
			'result_count'      => array(
				'label' => __( 'Result count', 'commercebuild-velocity' ),
				'area'  => 'toolbar',
			),
			'ordering'          => array(
				'label' => __( 'Sort dropdown', 'commercebuild-velocity' ),
				'area'  => 'toolbar',
			),
			'per_page'          => array(
				'label' => __( 'Products per page', 'commercebuild-velocity' ),
				'area'  => 'toolbar',
			),
			'view_toggle'       => array(
				'label' => __( 'Grid / list toggle', 'commercebuild-velocity' ),
				'area'  => 'toolbar',
			),
			'products'          => array(
				'label'       => __( 'Product grid', 'commercebuild-velocity' ),
				'area'        => 'content',
				'description' => __( 'The catalog loop itself.', 'commercebuild-velocity' ),
			),
			'pagination'        => array(
				'label' => __( 'Pagination', 'commercebuild-velocity' ),
				'area'  => 'content',
			),
			'content_shortcode' => array(
				'label' => __( 'Shortcode (content)', 'commercebuild-velocity' ),
				'area'  => 'content',
			),
			'below_shortcode'   => array(
				'label' => __( 'Shortcode (below)', 'commercebuild-velocity' ),
				'area'  => 'below',
			),
			'seo_text'          => array(
				'label'       => __( 'Category text below products', 'commercebuild-velocity' ),
				'area'        => 'below',
				'description' => __( 'Uses the category description, for SEO copy under the grid.', 'commercebuild-velocity' ),
			),
		);

		/**
		 * Filter the catalog page element library.
		 *
		 * @since 0.9.0
		 *
		 * @param array $definitions Element definitions keyed by element id.
		 */
		return apply_filters( 'cbv_catalog_elements', $definitions );
	}

	/**
	 * Per-element option fields.
	 *
	 * @since 0.9.0
	 *
	 * @return array
	 */
	public static function option_schema() {
		$shortcode_field = array(
			'shortcode' => array(
				'type'        => 'textarea',
				'label'       => __( 'Shortcode', 'commercebuild-velocity' ),
				'placeholder' => '[my_shortcode]',
				'default'     => '',
			),
		);

		$schema = array(
			'filters'           => array(
				'shortcode' => array(
					'type'        => 'text',
					'label'       => __( 'Filter shortcode', 'commercebuild-velocity' ),
					'placeholder' => '[cfw_es_filters]',
					'default'     => '',
				),
				'heading'   => array(
					'type'        => 'text',
					'label'       => __( 'Heading', 'commercebuild-velocity' ),
					'placeholder' => __( 'Filter', 'commercebuild-velocity' ),
					'default'     => '',
				),
			),
			'sidebar_shortcode' => $shortcode_field,
			'content_shortcode' => $shortcode_field,
			'below_shortcode'   => $shortcode_field,
			'subcategories'     => array(
				'columns' => array(
					'type'    => 'number',
					'label'   => __( 'Columns', 'commercebuild-velocity' ),
					'default' => 4,
					'min'     => 1,
					'max'     => 6,
				),
				'limit'   => array(
					'type'    => 'number',
					'label'   => __( 'Maximum tiles', 'commercebuild-velocity' ),
					'default' => 12,
					'min'     => 1,
					'max'     => 48,
				),
			),
		);

		/**
		 * Filter the catalog element option schema.
		 *
		 * @since 0.9.0
		 *
		 * @param array $schema Option fields keyed by element id.
		 */
		return apply_filters( 'cbv_catalog_element_options', $schema );
	}

	/**
	 * Render one element.
	 *
	 * @since 0.9.0
	 *
	 * @param string $id      Element id.
	 * @param array  $options Element options.
	 * @return void
	 */
	public static function render( $id, array $options = array() ) {
		$method = 'render_' . str_replace( '-', '_', $id );

		/**
		 * Short-circuit a catalog element's output.
		 *
		 * @since 0.9.0
		 *
		 * @param null|string $output  Replacement output.
		 * @param string      $id      Element id.
		 * @param array       $options Element options.
		 */
		$output = apply_filters( 'cbv_catalog_element_output', null, $id, $options );

		if ( null !== $output ) {
			echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Filtered output owns its escaping.
			return;
		}

		if ( ! method_exists( __CLASS__, $method ) ) {
			return;
		}

		/**
		 * Fires before a catalog element renders.
		 *
		 * @since 0.9.0
		 *
		 * @param string $id      Element id.
		 * @param array  $options Element options.
		 */
		do_action( 'cbv_catalog_element_before', $id, $options );

		self::$method( $options );

		/**
		 * Fires after a catalog element renders.
		 *
		 * @since 0.9.0
		 *
		 * @param string $id      Element id.
		 * @param array  $options Element options.
		 */
		do_action( 'cbv_catalog_element_after', $id, $options );
	}

	/**
	 * Breadcrumbs.
	 *
	 * @since 0.9.0
	 *
	 * @return void
	 */
	private static function render_breadcrumbs() {
		if ( function_exists( 'woocommerce_breadcrumb' ) ) {
			woocommerce_breadcrumb();
		}
	}

	/**
	 * Page title.
	 *
	 * @since 0.9.0
	 *
	 * @return void
	 */
	private static function render_page_title() {
		$hero = function_exists( 'cbv_catalog_hero_data' ) ? cbv_catalog_hero_data() : array();

		if ( ! empty( $hero['enabled'] ) ) {
			return;
		}

		if ( function_exists( 'woocommerce_page_title' ) ) {
			echo '<h1 class="cbv-catalog__title woocommerce-products-header__title page-title">';
			woocommerce_page_title();
			echo '</h1>';
		}
	}

	/**
	 * Category or shop description.
	 *
	 * @since 0.9.0
	 *
	 * @return void
	 */
	private static function render_term_description() {
		if ( function_exists( 'woocommerce_taxonomy_archive_description' ) ) {
			woocommerce_taxonomy_archive_description();
		}

		if ( function_exists( 'woocommerce_product_archive_description' ) ) {
			woocommerce_product_archive_description();
		}
	}

	/**
	 * Subcategory tiles for the current category, through the cached engine.
	 *
	 * @since 0.9.0
	 *
	 * @param array $options Options.
	 * @return void
	 */
	private static function render_subcategories( array $options = array() ) {
		$parent = 0;

		if ( is_product_category() ) {
			$term = get_queried_object();

			if ( $term instanceof WP_Term ) {
				$parent = (int) $term->term_id;
			}
		}

		$tiles = cbv_engine()->get_category_tiles(
			array(
				'parent' => $parent,
				'limit'  => isset( $options['limit'] ) ? absint( $options['limit'] ) : 12,
			)
		);

		if ( empty( $tiles ) ) {
			return;
		}

		$columns = isset( $options['columns'] ) ? min( 6, max( 1, absint( $options['columns'] ) ) ) : 4;

		printf( '<div class="cbv-grid cbv-grid--tiles" style="--cbv-grid-cols:%d">', (int) $columns );

		foreach ( $tiles as $tile ) {
			printf(
				'<a class="cbv-tile" href="%1$s"><span class="cbv-tile__media">%2$s</span><span class="cbv-tile__name">%3$s</span></a>',
				esc_url( $tile['url'] ),
				$tile['thumbnail_id'] ? wp_get_attachment_image( $tile['thumbnail_id'], 'cbv-tile', false, array( 'loading' => 'lazy' ) ) : '', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core image markup.
				esc_html( $tile['name'] )
			);
		}

		echo '</div>';
	}

	/**
	 * Faceted filters.
	 *
	 * Defaults to the commercebuild Elasticsearch filters shortcode when present, and
	 * accepts any other filter shortcode a site prefers.
	 *
	 * @since 0.9.0
	 *
	 * @param array $options Options.
	 * @return void
	 */
	private static function render_filters( array $options = array() ) {
		$shortcode = isset( $options['shortcode'] ) ? trim( (string) $options['shortcode'] ) : '';

		if ( '' === $shortcode ) {
			foreach ( array( 'cfw_es_filters', 'cfw_filters', 'cfw_product_filters' ) as $candidate ) {
				if ( shortcode_exists( $candidate ) ) {
					// auto mode generates category/brand/attribute filters;
					// lazy_load defers rendering (and contextual count
					// computation) to AJAX so it never blocks TTFB.
					$shortcode = 'cfw_es_filters' === $candidate ? '[cfw_es_filters auto="yes" lazy_load="yes"]' : '[' . $candidate . ']';
					break;
				}
			}
		}

		/**
		 * Filter the catalog filter shortcode.
		 *
		 * @since 0.9.0
		 *
		 * @param string $shortcode Shortcode string.
		 */
		$shortcode = apply_filters( 'cbv_catalog_filters_shortcode', $shortcode );

		if ( '' === $shortcode ) {
			return;
		}

		$output = do_shortcode( $shortcode );

		if ( '' === trim( (string) $output ) ) {
			return;
		}

		echo '<div class="cbv-catalog__filters">';

		if ( ! empty( $options['heading'] ) ) {
			printf( '<h2 class="cbv-catalog__filters-title">%s</h2>', esc_html( $options['heading'] ) );
		}

		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shortcode output.
		echo '</div>';
	}

	/**
	 * Registered shop sidebar widgets.
	 *
	 * @since 0.9.0
	 *
	 * @return void
	 */
	private static function render_sidebar_widgets() {
		if ( is_active_sidebar( 'shop-sidebar' ) ) {
			dynamic_sidebar( 'shop-sidebar' );
		}
	}

	/**
	 * Result count.
	 *
	 * @since 0.9.0
	 *
	 * @return void
	 */
	private static function render_result_count() {
		if ( ! function_exists( 'woocommerce_result_count' ) ) {
			return;
		}

		ob_start();
		woocommerce_result_count();
		$html = trim( (string) ob_get_clean() );

		if ( '' !== $html ) {
			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce template output.
			return;
		}

		// WooCommerce renders nothing while the loop is empty — which is every
		// initial render under cfw_core's dynamic archive. Emit the async
		// placeholder cfw's hydration JS fills with the real count ("0
		// results" on genuinely empty archives). role="status" mirrors
		// WooCommerce's own result-count template semantics.
		echo '<p class="woocommerce-result-count" role="status" aria-live="polite" data-cfw-async-placeholder="result-count"></p>';
	}

	/**
	 * Catalog ordering dropdown.
	 *
	 * @since 0.9.0
	 *
	 * @return void
	 */
	private static function render_ordering() {
		if ( ! function_exists( 'woocommerce_catalog_ordering' ) ) {
			return;
		}

		ob_start();
		woocommerce_catalog_ordering();
		$html = trim( (string) ob_get_clean() );

		if ( '' !== $html ) {
			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce template output.
			return;
		}

		// WooCommerce refuses to render the form while the loop is empty —
		// which is every initial render under cfw_core's dynamic archive.
		// Render the same orderby template directly so shoppers can sort;
		// cfw's loader binds its ordering handler to the standard form.
		$orderby_options = apply_filters(
			'woocommerce_catalog_orderby',
			array(
				'menu_order' => __( 'Default sorting', 'commercebuild-velocity' ),
				'popularity' => __( 'Sort by popularity', 'commercebuild-velocity' ),
				'rating'     => __( 'Sort by average rating', 'commercebuild-velocity' ),
				'date'       => __( 'Sort by latest', 'commercebuild-velocity' ),
				'price'      => __( 'Sort by price: low to high', 'commercebuild-velocity' ),
				'price-desc' => __( 'Sort by price: high to low', 'commercebuild-velocity' ),
			)
		);

		$default_orderby = get_option( 'woocommerce_default_catalog_orderby', 'menu_order' );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display preference.
		$current_orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : $default_orderby;

		wc_get_template(
			'loop/orderby.php',
			array(
				'catalog_orderby_options' => $orderby_options,
				'orderby'                 => $current_orderby,
				'show_default_orderby'    => 'menu_order' === $default_orderby,
			)
		);
	}

	/**
	 * Products-per-page selector.
	 *
	 * @since 0.9.0
	 *
	 * @return void
	 */
	private static function render_per_page() {
		$choices = array( 12, 24, 48, 96 );

		/**
		 * Filter the products-per-page choices.
		 *
		 * @since 0.9.0
		 *
		 * @param int[] $choices Per-page options.
		 */
		$choices = array_map( 'absint', (array) apply_filters( 'cbv_per_page_choices', $choices ) );

		// Reflect the EFFECTIVE per-page (?per_page override or the
		// Customizer value), and list it as a choice when it isn't one of
		// the presets, so the selector never shows a value the page isn't
		// using.
		$columns = CBV_Catalog_Layout::columns();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display preference.
		$current = isset( $_GET['per_page'] ) ? absint( $_GET['per_page'] ) : (int) $columns['per_page'];

		if ( $current > 0 && ! in_array( $current, $choices, true ) ) {
			$choices[] = $current;
			sort( $choices );
		}

		echo '<form class="cbv-catalog__per-page" method="get">';

		// Preserve the rest of the query string, including array-valued
		// params (cfw filters use filter_pa_*[] syntax).
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display preference.
		foreach ( $_GET as $key => $value ) {
			if ( 'per_page' === $key ) {
				continue;
			}

			$key = sanitize_key( $key );

			if ( is_array( $value ) ) {
				foreach ( $value as $item ) {
					if ( is_scalar( $item ) ) {
						printf(
							'<input type="hidden" name="%s[]" value="%s">',
							esc_attr( $key ),
							esc_attr( sanitize_text_field( wp_unslash( (string) $item ) ) )
						);
					}
				}
				continue;
			}

			printf(
				'<input type="hidden" name="%s" value="%s">',
				esc_attr( $key ),
				esc_attr( sanitize_text_field( wp_unslash( $value ) ) )
			);
		}

		echo '<label>';
		esc_html_e( 'Show', 'commercebuild-velocity' );
		echo ' <select name="per_page" onchange="this.form.submit()">';

		foreach ( $choices as $choice ) {
			printf(
				'<option value="%1$d" %2$s>%1$d</option>',
				(int) $choice,
				selected( $current, $choice, false )
			);
		}

		echo '</select></label></form>';
	}

	/**
	 * Grid / list view toggle.
	 *
	 * @since 0.9.0
	 *
	 * @return void
	 */
	private static function render_view_toggle() {
		$current = isset( $_COOKIE['cbv_view'] ) && 'list' === $_COOKIE['cbv_view'] ? 'list' : 'grid';
		/**
		 * Filter the accessible label for the catalog view controls.
		 *
		 * @since 0.11.7
		 *
		 * @param string $label Control-group label.
		 */
		$label = apply_filters( 'cbv_catalog_view_toggle_label', __( 'Product view', 'commercebuild-velocity' ) );
		printf(
			'<div class="cbv-catalog__view" data-current="%s" role="group" aria-label="%s">
				<button type="button" class="cbv-view-btn%s" data-view="grid" aria-label="%s" aria-pressed="%s">%s</button>
				<button type="button" class="cbv-view-btn%s" data-view="list" aria-label="%s" aria-pressed="%s">%s</button>
			</div>',
			esc_attr( $current ),
			esc_attr( $label ),
			'grid' === $current ? ' is-active' : '',
			esc_attr__( 'Grid view', 'commercebuild-velocity' ),
			'grid' === $current ? 'true' : 'false',
			wp_kses( cbv_icon( 'grid' ), CBV_Icons::kses_allowed() ),
			'list' === $current ? ' is-active' : '',
			esc_attr__( 'List view', 'commercebuild-velocity' ),
			'list' === $current ? 'true' : 'false',
			wp_kses( cbv_icon( 'list' ), CBV_Icons::kses_allowed() )
		);
	}

	/**
	 * The catalog loop.
	 *
	 * Uses WooCommerce's own loop so the main query, pagination, and any
	 * plugin loop hooks continue to work unchanged.
	 *
	 * @since 0.9.0
	 *
	 * @return void
	 */
	private static function render_products() {
		if ( ! function_exists( 'woocommerce_product_loop_start' ) ) {
			return;
		}

		if ( ! have_posts() ) {
			// Fire the canonical action instead of including the template
			// directly: WooCommerce renders its no-products notice from this
			// hook (priority 10), and cfw_core's dynamic archive replaces it
			// with the skeleton placeholder loop (priority 5) when enabled.
			/** This action is documented in WooCommerce templates. */
			do_action( 'woocommerce_no_products_found' );
			return;
		}

		woocommerce_product_loop_start();

		while ( have_posts() ) {
			the_post();

			/** This action is documented in WooCommerce. */
			do_action( 'woocommerce_shop_loop' );

			wc_get_template_part( 'content', 'product' );
		}

		woocommerce_product_loop_end();
	}

	/**
	 * Pagination.
	 *
	 * Prefers cfw_core's filter-aware pagination (detached from
	 * woocommerce_after_shop_loop by CBV_Catalog_Layout so it renders once,
	 * here, in the position the layout dictates); falls back to WooCommerce
	 * pagination when cfw_core is absent or renders nothing (e.g. the
	 * cfw_disable_server_pagination opt-out).
	 *
	 * @since 0.9.0
	 *
	 * @return void
	 */
	private static function render_pagination() {
		/**
		 * Filter the object used to render catalog pagination.
		 *
		 * Must expose a render_server_pagination() method. Return null to
		 * force the WooCommerce pagination fallback.
		 *
		 * @since 0.9.5
		 *
		 * @param object|null $renderer Detached cfw_core Filters instance, or null.
		 */
		$renderer = apply_filters( 'cbv_catalog_pagination_renderer', CBV_Catalog_Layout::cfw_pagination_renderer() );

		if ( $renderer && is_callable( array( $renderer, 'render_server_pagination' ) ) ) {
			ob_start();
			$renderer->render_server_pagination();
			$html = trim( (string) ob_get_clean() );

			if ( '' !== $html ) {
				echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by the cfw_core renderer.
				return;
			}
		}

		if ( function_exists( 'woocommerce_pagination' ) ) {
			woocommerce_pagination();
		}
	}

	/**
	 * SEO copy below the grid.
	 *
	 * @since 0.9.0
	 *
	 * @return void
	 */
	private static function render_seo_text() {
		$term = get_queried_object();

		if ( ! $term instanceof WP_Term || '' === trim( (string) $term->description ) ) {
			return;
		}

		printf(
			'<div class="cbv-catalog__seo-text">%s</div>',
			wp_kses_post( wpautop( do_shortcode( $term->description ) ) )
		);
	}

	/**
	 * Arbitrary shortcode elements.
	 *
	 * @since 0.9.0
	 *
	 * @param array $options Options.
	 * @return void
	 */
	private static function render_sidebar_shortcode( array $options = array() ) {
		self::render_shortcode_option( $options, 'sidebar' );
	}

	/**
	 * Content-area shortcode.
	 *
	 * @since 0.9.0
	 *
	 * @param array $options Options.
	 * @return void
	 */
	private static function render_content_shortcode( array $options = array() ) {
		self::render_shortcode_option( $options, 'content' );
	}

	/**
	 * Below-content shortcode.
	 *
	 * @since 0.9.0
	 *
	 * @param array $options Options.
	 * @return void
	 */
	private static function render_below_shortcode( array $options = array() ) {
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
			'<div class="cbv-catalog__shortcode cbv-catalog__shortcode--%s">%s</div>',
			esc_attr( $modifier ),
			$output // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shortcode output.
		);
	}
}
