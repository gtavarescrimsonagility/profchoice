<?php
/**
 * Catalog page layout.
 *
 * Shop, product category, product tag, and search pages share one layout and
 * one set of column controls, because they are the same template with a
 * different query behind it.
 *
 * @package Commercebuild_Velocity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ordered catalog page rendering and column configuration.
 *
 * @since 0.9.0
 */
class CBV_Catalog_Layout {

	/**
	 * Theme mod storing the element layout.
	 *
	 * @var string
	 */
	const MOD = 'cbv_catalog_layout';

	/**
	 * The cfw_core pagination renderer detached from woocommerce_after_shop_loop.
	 *
	 * @var object|null
	 */
	private static $cfw_pagination_renderer = null;

	/**
	 * Register hooks.
	 *
	 * @since 0.9.0
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wp', array( $this, 'apply_layout' ) );
		add_action( 'customize_register', array( $this, 'add_controls' ) );
		add_filter( 'body_class', array( $this, 'body_class' ) );
		add_filter( 'loop_shop_per_page', array( $this, 'per_page_override' ), 30 );
		add_filter( 'cfw_dynamic_archive_enabled', array( $this, 'dynamic_archive_for_sessions_only' ) );
	}

	/**
	 * Restrict cfw_core's dynamic (skeleton-first) archive to logged-in sessions.
	 *
	 * Guests — which includes every crawler — get the fully server-rendered
	 * page 1, keeping archives indexable and full-page-cacheable. Logged-in
	 * shoppers, who can never be served from a page cache anyway, get the
	 * fast skeleton shell with AJAX hydration.
	 *
	 * @since 0.9.11
	 *
	 * @param bool $enabled Whether the dynamic archive runs for this request.
	 * @return bool
	 */
	public function dynamic_archive_for_sessions_only( $enabled ) {
		if ( ! is_user_logged_in() ) {
			$enabled = false;
		}

		/**
		 * Filter the theme's dynamic-archive gating decision.
		 *
		 * @since 0.9.11
		 *
		 * @param bool $enabled False for guests/crawlers, cfw's value otherwise.
		 */
		return (bool) apply_filters( 'cbv_dynamic_archive_enabled', $enabled );
	}

	/**
	 * Whether the current request is a catalog page this layout owns.
	 *
	 * @since 0.9.0
	 *
	 * @return bool
	 */
	public static function is_catalog() {
		if ( ! function_exists( 'is_shop' ) ) {
			return false;
		}

		$is_catalog = is_shop() || is_product_taxonomy() || ( is_search() && 'product' === get_query_var( 'post_type' ) );

		/**
		 * Filter whether the catalog layout applies to this request.
		 *
		 * @since 0.9.0
		 *
		 * @param bool $is_catalog True on shop, product taxonomy, and product search.
		 */
		return (bool) apply_filters( 'cbv_is_catalog_page', $is_catalog );
	}

	/**
	 * Default layout.
	 *
	 * @since 0.9.0
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
				array(
					'id'      => 'page_title',
					'enabled' => true,
				),
				array(
					'id'      => 'term_description',
					'enabled' => true,
				),
				array(
					'id'      => 'subcategories',
					'enabled' => true,
					'options' => array(
						'columns' => 4,
						'limit'   => 12,
					),
				),
			),
			'sidebar' => array(
				array(
					'id'      => 'filters',
					'enabled' => true,
					'options' => array(
						'shortcode' => '',
						'heading'   => '',
					),
				),
				array(
					'id'      => 'sidebar_widgets',
					'enabled' => true,
				),
				array(
					'id'      => 'sidebar_shortcode',
					'enabled' => false,
					'options' => array( 'shortcode' => '' ),
				),
			),
			'toolbar' => array(
				array(
					'id'      => 'result_count',
					'enabled' => true,
				),
				array(
					'id'      => 'view_toggle',
					'enabled' => true,
				),
				array(
					'id'      => 'per_page',
					'enabled' => true,
				),
				array(
					'id'      => 'ordering',
					'enabled' => true,
				),
			),
			'content' => array(
				array(
					'id'      => 'products',
					'enabled' => true,
				),
				array(
					'id'      => 'pagination',
					'enabled' => true,
				),
				array(
					'id'      => 'content_shortcode',
					'enabled' => false,
					'options' => array( 'shortcode' => '' ),
				),
			),
			'below'   => array(
				array(
					'id'      => 'seo_text',
					'enabled' => false,
				),
				array(
					'id'      => 'below_shortcode',
					'enabled' => false,
					'options' => array( 'shortcode' => '' ),
				),
			),
		);

		/**
		 * Filter the default catalog layout.
		 *
		 * @since 0.9.0
		 *
		 * @param array $layout Default layout by area.
		 */
		return apply_filters( 'cbv_default_catalog_layout', $layout );
	}

	/**
	 * Current layout, reconciled against the element library.
	 *
	 * @since 0.9.0
	 *
	 * @return array
	 */
	public static function layout() {
		static $layout = null;

		if ( null !== $layout ) {
			return $layout;
		}

		$defaults    = self::default_layout();
		$definitions = CBV_Catalog_Elements::definitions();
		$stored      = json_decode( (string) get_theme_mod( self::MOD, '' ), true );

		if ( ! is_array( $stored ) ) {
			$layout = $defaults;
		} else {
			$layout = array();

			foreach ( $defaults as $area => $default_rows ) {
				$rows = isset( $stored[ $area ] ) && is_array( $stored[ $area ] ) ? $stored[ $area ] : array();
				$seen = array();
				$out  = array();

				foreach ( $rows as $row ) {
					if ( empty( $row['id'] ) || ! isset( $definitions[ $row['id'] ] ) || isset( $seen[ $row['id'] ] ) ) {
						continue;
					}

					$out[] = array(
						'id'      => (string) $row['id'],
						'enabled' => ! empty( $row['enabled'] ),
						'options' => isset( $row['options'] ) && is_array( $row['options'] ) ? $row['options'] : array(),
					);

					$seen[ $row['id'] ] = true;
				}

				foreach ( $default_rows as $default_row ) {
					if ( ! isset( $seen[ $default_row['id'] ] ) ) {
						$out[] = $default_row;
					}
				}

				$layout[ $area ] = $out;
			}
		}

		/**
		 * Filter the resolved catalog layout.
		 *
		 * @since 0.9.0
		 *
		 * @param array $layout Layout by area.
		 */
		$layout = apply_filters( 'cbv_catalog_layout', $layout );

		return $layout;
	}

	/**
	 * Column configuration for catalog pages.
	 *
	 * @since 0.9.0
	 *
	 * @return array
	 */
	public static function columns() {
		$columns = array(
			'sidebar'  => get_theme_mod( 'cbv_catalog_sidebar', 'left' ),
			'width'    => (int) get_theme_mod( 'cbv_catalog_sidebar_width', 260 ),
			'products' => (int) get_theme_mod( 'cbv_catalog_columns', 4 ),
			'per_page' => (int) get_theme_mod( 'cbv_catalog_per_page', 24 ),
		);

		if ( ! in_array( $columns['sidebar'], array( 'left', 'right', 'none' ), true ) ) {
			$columns['sidebar'] = 'left';
		}

		$columns['width']    = min( 480, max( 180, $columns['width'] ) );
		$columns['products'] = min( 6, max( 1, $columns['products'] ) );
		$columns['per_page'] = min( 96, max( 1, $columns['per_page'] ) );

		/**
		 * Filter the catalog column configuration.
		 *
		 * @since 0.9.0
		 *
		 * @param array $columns Sidebar position/width and product columns.
		 */
		return apply_filters( 'cbv_catalog_columns', $columns );
	}

	/**
	 * Products per page, honouring the shopper's own selection.
	 *
	 * @since 0.9.0
	 *
	 * @param int $per_page Incoming value.
	 * @return int
	 */
	public function per_page_override( $per_page ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Required WordPress filter signature.
		$chosen = isset( $_GET['per_page'] ) ? absint( $_GET['per_page'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display preference.

		if ( $chosen > 0 ) {
			return min( 96, $chosen );
		}

		$columns = self::columns();

		return $columns['per_page'];
	}

	/**
	 * How the sidebar filters present on small screens: drawer|inline.
	 *
	 * @since 0.9.7
	 *
	 * @return string
	 */
	public static function mobile_filters_mode() {
		$mode = get_theme_mod( 'cbv_catalog_mobile_filters', 'drawer' );

		if ( ! in_array( $mode, array( 'drawer', 'inline' ), true ) ) {
			$mode = 'drawer';
		}

		/**
		 * Filter the mobile presentation of the catalog sidebar.
		 *
		 * @since 0.9.7
		 *
		 * @param string $mode Either 'drawer' (toggle button opens an
		 *                     off-canvas panel) or 'inline' (sidebar stacks
		 *                     with the content).
		 */
		return (string) apply_filters( 'cbv_catalog_mobile_filters_mode', $mode );
	}

	/**
	 * Body classes describing the catalog layout.
	 *
	 * @since 0.9.0
	 *
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public function body_class( $classes ) {
		if ( ! self::is_catalog() ) {
			return $classes;
		}

		$columns   = self::columns();
		$classes[] = 'cbv-catalog';
		$classes[] = 'cbv-catalog--sidebar-' . $columns['sidebar'];
		$classes[] = 'cbv-catalog--mobile-filters-' . self::mobile_filters_mode();

		return $classes;
	}

	/**
	 * Replace WooCommerce's archive output with the configured layout.
	 *
	 * @since 0.9.0
	 *
	 * @return void
	 */
	public function apply_layout() {
		if ( ! self::is_catalog() ) {
			return;
		}

		remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
		remove_action( 'woocommerce_shop_loop_header', 'woocommerce_product_taxonomy_archive_header', 10 );
		remove_action( 'woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10 );
		remove_action( 'woocommerce_archive_description', 'woocommerce_product_archive_description', 10 );
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_output_all_notices', 10 );
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
		remove_action( 'woocommerce_after_shop_loop', 'woocommerce_pagination', 10 );

		self::$cfw_pagination_renderer = self::detach_cfw_server_pagination();

		add_filter( 'loop_shop_columns', array( $this, 'loop_columns' ), 30 );
	}

	/**
	 * Detach cfw_core's server pagination from woocommerce_after_shop_loop.
	 *
	 * The cfw_core plugin renders filter-aware pagination on woocommerce_after_shop_loop,
	 * which this template still fires; combined with the layout's own
	 * pagination element the archive gets two pagers. The layout owns element
	 * placement, so the hook is removed here and the pagination element
	 * delegates to the detached renderer (keeping filter_* and search params
	 * on the links) via cfw_pagination_renderer().
	 *
	 * Only a callback whose owner is cfw_core's ElasticPress Filters class is
	 * removed; same-named methods on other classes are left alone.
	 *
	 * @since 0.9.5
	 *
	 * @return object|null The detached Filters instance, or null when absent.
	 */
	private static function detach_cfw_server_pagination() {
		global $wp_filter;

		if ( ! class_exists( '\Commercebuild\WCCore\ElasticPress\Filters' ) || empty( $wp_filter['woocommerce_after_shop_loop'] ) ) {
			return null;
		}

		foreach ( $wp_filter['woocommerce_after_shop_loop']->callbacks as $priority => $callbacks ) {
			foreach ( $callbacks as $callback ) {
				$function = isset( $callback['function'] ) ? $callback['function'] : null;

				if ( is_array( $function )
					&& isset( $function[0], $function[1] )
					&& 'render_server_pagination' === $function[1]
					&& $function[0] instanceof \Commercebuild\WCCore\ElasticPress\Filters ) {
					remove_action( 'woocommerce_after_shop_loop', $function, $priority );

					return $function[0];
				}
			}
		}

		return null;
	}

	/**
	 * The cfw_core pagination renderer, when one was detached.
	 *
	 * @since 0.9.5
	 *
	 * @return object|null
	 */
	public static function cfw_pagination_renderer() {
		return self::$cfw_pagination_renderer;
	}

	/**
	 * Grid columns for the catalog loop.
	 *
	 * @since 0.9.0
	 *
	 * @return int
	 */
	public function loop_columns() {
		$columns = self::columns();

		return $columns['products'];
	}

	/**
	 * Render one configured area.
	 *
	 * @since 0.9.0
	 *
	 * @param string $area Area key.
	 * @return void
	 */
	public static function render_area( $area ) {
		$layout = self::layout();

		if ( empty( $layout[ $area ] ) ) {
			return;
		}

		/**
		 * Fires before a catalog layout area renders.
		 *
		 * @since 0.9.0
		 *
		 * @param string $area Area key.
		 */
		do_action( 'cbv_catalog_area_before', $area );

		foreach ( $layout[ $area ] as $row ) {
			if ( empty( $row['enabled'] ) ) {
				continue;
			}

			CBV_Catalog_Elements::render(
				$row['id'],
				isset( $row['options'] ) ? (array) $row['options'] : array()
			);
		}

		/**
		 * Fires after a catalog layout area renders.
		 *
		 * @since 0.9.0
		 *
		 * @param string $area Area key.
		 */
		do_action( 'cbv_catalog_area_after', $area );
	}

	/**
	 * Whether an area has at least one enabled element.
	 *
	 * @since 0.9.0
	 *
	 * @param string $area Area key.
	 * @return bool
	 */
	public static function area_has_content( $area ) {
		$layout = self::layout();

		if ( empty( $layout[ $area ] ) ) {
			return false;
		}

		foreach ( $layout[ $area ] as $row ) {
			if ( ! empty( $row['enabled'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Register the Catalog Pages section, column controls, and layout control.
	 *
	 * @since 0.9.0
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
			'cbv_catalog_page',
			array(
				'title'       => __( 'Category &amp; Search Pages', 'commercebuild-velocity' ),
				'priority'    => 28,
				'description' => __( 'Shop, category, tag, and search results share this layout.', 'commercebuild-velocity' ),
			)
		);

		$controls = array(
			'cbv_catalog_sidebar'        => array(
				'default' => 'left',
				'label'   => __( 'Sidebar position', 'commercebuild-velocity' ),
				'type'    => 'select',
				'choices' => array(
					'left'  => __( 'Left', 'commercebuild-velocity' ),
					'right' => __( 'Right', 'commercebuild-velocity' ),
					'none'  => __( 'No sidebar (full width)', 'commercebuild-velocity' ),
				),
			),
			'cbv_catalog_sidebar_width'  => array(
				'default' => 260,
				'label'   => __( 'Sidebar width (px)', 'commercebuild-velocity' ),
				'type'    => 'number',
				'input'   => array(
					'min'  => 180,
					'max'  => 480,
					'step' => 10,
				),
			),
			'cbv_catalog_columns'        => array(
				'default' => 4,
				'label'   => __( 'Product columns', 'commercebuild-velocity' ),
				'type'    => 'number',
				'input'   => array(
					'min'  => 1,
					'max'  => 6,
					'step' => 1,
				),
			),
			'cbv_catalog_per_page'       => array(
				'default' => 24,
				'label'   => __( 'Products per page', 'commercebuild-velocity' ),
				'type'    => 'number',
				'input'   => array(
					'min'  => 1,
					'max'  => 96,
					'step' => 1,
				),
			),
			'cbv_catalog_mobile_filters' => array(
				'default' => 'drawer',
				'label'   => __( 'Mobile filters display', 'commercebuild-velocity' ),
				'type'    => 'select',
				'choices' => array(
					'drawer' => __( 'Filters button opens a drawer', 'commercebuild-velocity' ),
					'inline' => __( 'Filters stack with the content', 'commercebuild-velocity' ),
				),
			),
		);

		foreach ( $controls as $id => $control ) {
			$wp_customize->add_setting(
				$id,
				array(
					'default'           => $control['default'],
					'sanitize_callback' => 'select' === $control['type'] ? 'sanitize_key' : 'absint',
					'transport'         => 'refresh',
				)
			);

			$args = array(
				'label'   => $control['label'],
				'section' => 'cbv_catalog_page',
				'type'    => $control['type'],
			);

			if ( isset( $control['choices'] ) ) {
				$args['choices'] = $control['choices'];
			}

			if ( isset( $control['input'] ) ) {
				$args['input_attrs'] = $control['input'];
			}

			$wp_customize->add_control( $id, $args );
		}

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
					'section' => 'cbv_catalog_page',
					'label'   => __( 'Catalog page elements', 'commercebuild-velocity' ),
					'library' => 'catalog',
					'areas'   => array(
						'above'   => __( 'Above the catalog', 'commercebuild-velocity' ),
						'sidebar' => __( 'Sidebar column', 'commercebuild-velocity' ),
						'toolbar' => __( 'Toolbar', 'commercebuild-velocity' ),
						'content' => __( 'Content column', 'commercebuild-velocity' ),
						'below'   => __( 'Below the catalog', 'commercebuild-velocity' ),
					),
				)
			)
		);
	}

	/**
	 * Sanitize the serialized catalog layout.
	 *
	 * @since 0.9.0
	 *
	 * @param string $value Raw JSON from the control.
	 * @return string
	 */
	public function sanitize_layout( $value ) {
		$decoded = json_decode( (string) $value, true );

		if ( ! is_array( $decoded ) ) {
			return '';
		}

		$definitions = CBV_Catalog_Elements::definitions();
		$schema      = CBV_Catalog_Elements::option_schema();
		$areas       = array_keys( self::default_layout() );
		$clean       = array();
		$used        = array();

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

				if ( isset( $used[ $id ] ) ) {
					continue;
				}

				$declared_area = isset( $definitions[ $id ]['area'] ) ? $definitions[ $id ]['area'] : 'content';

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
								$max             = isset( $field['max'] ) ? (int) $field['max'] : 48;
								$options[ $key ] = min( $max, max( $min, absint( $raw ) ) );
								break;
							case 'textarea':
								// Shortcodes are stored raw and only ever run
								// through do_shortcode(), never echoed unescaped.
								$options[ $key ] = wp_kses_post( (string) $raw );
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
}
