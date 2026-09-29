<?php
/**
 * WooCommerce integration and archive performance layer.
 *
 * Archives stay server-rendered (crawlable, cacheable first page) but the
 * main product query is tuned: ElasticPress integration when available, and
 * the product_visibility anti-join is stripped when no products are hidden.
 * On large catalogs (5M+ term_relationships rows) that anti-join alone can
 * cost ~300ms per query while excluding nothing.
 *
 * @package Commercebuild_Velocity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce hooks.
 *
 * @since 0.1.0
 */
class CBV_WooCommerce {

	/**
	 * Query engine.
	 *
	 * @var CBV_Query_Engine
	 */
	private $engine;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param CBV_Query_Engine $engine Query engine.
	 */
	public function __construct( CBV_Query_Engine $engine ) {
		$this->engine = $engine;
	}

	/**
	 * Register hooks.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'woocommerce_product_query', array( $this, 'tune_product_query' ) );
		add_filter( 'woocommerce_product_query_tax_query', array( $this, 'strip_empty_visibility_antijoin' ) );
		add_filter( 'loop_shop_columns', array( $this, 'loop_columns' ) );
		add_filter( 'loop_shop_per_page', array( $this, 'per_page' ) );
		add_filter( 'woocommerce_enqueue_styles', array( $this, 'trim_wc_styles' ) );
		add_filter( 'woocommerce_product_related_products_heading', array( $this, 'related_heading' ) );
		add_filter( 'woocommerce_product_add_to_cart_text', array( 'CBV_Product_Elements', 'add_to_cart_text' ) );

		remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
		remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
		remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
		add_action( 'woocommerce_before_main_content', array( $this, 'open_wrapper' ) );
		add_action( 'woocommerce_after_main_content', array( $this, 'close_wrapper' ) );
		add_action( 'init', array( $this, 'ensure_classic_cart_checkout' ), 30 );
		add_filter( 'woocommerce_default_address_fields', array( $this, 'address_field_layout' ) );
		add_filter( 'woocommerce_enable_order_notes_field', array( $this, 'hide_order_notes' ) );
		add_filter( 'woocommerce_order_button_text', array( $this, 'order_button_text' ) );
		add_filter( 'option_woocommerce_checkout_company_field', array( $this, 'company_field_visibility' ) );
		add_filter( 'woocommerce_checkout_fields', array( $this, 'ensure_company_field' ) );
		add_action( 'woocommerce_before_customer_login_form', array( $this, 'restore_login_notices' ), 9 );
		add_action( 'init', array( $this, 'relocate_checkout_payment' ), 40 );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'checkout_methods_fragment' ) );
		add_filter( 'gettext', array( $this, 'free_shipping_label' ), 10, 3 );
		add_filter( 'woocommerce_ship_to_different_address_checked', array( $this, 'force_ship_to_different_address' ) );
	}

	/**
	 * Print account notices when another integration removes Woo's renderer.
	 *
	 * WooCommerce validates the account sign-in form on the server. Some B2B
	 * integrations remove the standard notice callback for logged-out account
	 * pages, which makes invalid sign-in attempts fail without feedback. Run
	 * immediately before Woo's normal priority-10 callback and only supply the
	 * missing renderer, so sites with the standard hook never receive duplicates.
	 *
	 * @since 0.12.56
	 *
	 * @return void
	 */
	public function restore_login_notices() {
		if (
			false !== has_action( 'woocommerce_before_customer_login_form', 'woocommerce_output_all_notices' )
			|| ! function_exists( 'woocommerce_output_all_notices' )
		) {
			return;
		}

		woocommerce_output_all_notices();
	}

	/**
	 * Always post shipping_* so cfw_core's ship-to picker and address code apply.
	 *
	 * Woo's default destination is billing. If the different-address box is
	 * off, checkout copies billing onto shipping and drops shipping_address_select
	 * / shipping_address_code.
	 *
	 * @since 0.12.43
	 *
	 * @param bool $checked WooCommerce default.
	 * @return bool
	 */
	public function force_ship_to_different_address( $checked ) {
		/**
		 * Filter whether classic checkout posts a distinct shipping address.
		 *
		 * @since 0.12.43
		 *
		 * @param bool $force   True to keep shipping_* on the order.
		 * @param bool $checked WooCommerce default.
		 */
		return (bool) apply_filters( 'cbv_force_ship_to_different_address', true, $checked );
	}

	/**
	 * Woo prints "Free!" for a zero shipping total. Checkout V1 is "Free".
	 *
	 * @since 0.12.26
	 *
	 * @param string $translated Translated text.
	 * @param string $text       Original text.
	 * @param string $domain     Text domain.
	 * @return string
	 */
	public function free_shipping_label( $translated, $text, $domain ) {
		if ( 'woocommerce' === $domain && 'Free!' === $text ) {
			return __( 'Free', 'commercebuild-velocity' );
		}

		return $translated;
	}

	/**
	 * Open the theme content wrapper around WooCommerce templates.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function open_wrapper() {
		echo '<div class="cbv-container cbv-content">';
	}

	/**
	 * Close the theme content wrapper.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function close_wrapper() {
		echo '</div>';
	}

	/**
	 * Route the main archive query through ElasticPress when active.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_Query $query Main product query.
	 * @return void
	 */
	public function tune_product_query( $query ) {
		if ( $this->engine->elasticpress_active() ) {
			$query->set( 'ep_integrate', true );
		}
	}

	/**
	 * Drop the product_visibility NOT IN clause when nothing is hidden.
	 *
	 * WooCommerce always anti-joins term_relationships against the
	 * exclude-from-catalog / out-of-stock visibility terms. When those terms
	 * are empty the join filters nothing but still scans millions of rows.
	 * The term counts are cached for an hour.
	 *
	 * @since 0.1.0
	 *
	 * @param array $tax_query Tax query for the catalog.
	 * @return array
	 */
	public function strip_empty_visibility_antijoin( $tax_query ) {
		$cache = cbv_services()['cache'];

		// Route the key through CBV_Cache::key() so the namespace-salt fallback
		// can invalidate it on object caches that cannot flush a group. The
		// count is a global schema fact, identical for every visitor, so it is
		// unscoped (no per-visitor visibility identity).
		$hidden = $cache->remember(
			$cache->key( 'cbv_hidden_visibility_count', array(), false ),
			function () {
				$count = 0;

				foreach ( array( 'exclude-from-catalog', 'outofstock' ) as $slug ) {
					$term = get_term_by( 'slug', $slug, 'product_visibility' );

					if ( $term instanceof WP_Term ) {
						$count += (int) $term->count;
					}
				}

				return $count;
			},
			HOUR_IN_SECONDS
		);

		if ( $hidden > 0 ) {
			return $tax_query;
		}

		foreach ( $tax_query as $index => $clause ) {
			if (
				is_array( $clause )
				&& isset( $clause['taxonomy'], $clause['operator'] )
				&& 'product_visibility' === $clause['taxonomy']
				&& 'NOT IN' === $clause['operator']
			) {
				unset( $tax_query[ $index ] );
			}
		}

		return $tax_query;
	}

	/**
	 * Grid columns for the shop loop.
	 *
	 * @since 0.1.0
	 *
	 * @return int
	 */
	public function loop_columns() {
		/**
		 * Filter the shop loop column count.
		 *
		 * @since 0.3.0
		 *
		 * @param int $columns Columns.
		 */
		return (int) apply_filters( 'cbv_loop_columns', 4 );
	}

	/**
	 * Products per archive page.
	 *
	 * @since 0.1.0
	 *
	 * @return int
	 */
	public function per_page() {
		/**
		 * Filter the products shown per archive page.
		 *
		 * @since 0.3.0
		 *
		 * @param int $per_page Products per page.
		 */
		return (int) apply_filters( 'cbv_products_per_page', 24 );
	}

	/**
	 * Keep only the WooCommerce styles the theme does not replace.
	 *
	 * @since 0.1.0
	 *
	 * @param array $styles WooCommerce style handles.
	 * @return array
	 */
	public function trim_wc_styles( $styles ) {
		unset( $styles['woocommerce-layout'], $styles['woocommerce-smallscreen'] );

		return $styles;
	}

	/**
	 * Related products heading.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function related_heading() {
		return __( 'You may also like', 'commercebuild-velocity' );
	}

	/**
	 * Replace WooCommerce Blocks cart/checkout pages with classic shortcodes.
	 *
	 * Theme cart/checkout templates only run for classic shortcodes. Original
	 * block markup is stored in post meta so it can be restored.
	 *
	 * @since 0.12.4
	 *
	 * @return void
	 */
	public function ensure_classic_cart_checkout() {
		if ( is_admin() || wp_doing_cron() || wp_doing_ajax() ) {
			return;
		}

		if ( get_option( 'cbv_classic_cart_checkout' ) ) {
			return;
		}

		if ( ! function_exists( 'wc_get_page_id' ) ) {
			return;
		}

		$cart      = $this->maybe_classic_page( 'cart', '[woocommerce_cart]', 'woocommerce/cart' );
		$checkout  = $this->maybe_classic_page( 'checkout', '[woocommerce_checkout]', 'woocommerce/checkout' );

		if ( $cart && $checkout ) {
			update_option( 'cbv_classic_cart_checkout', 1, true );
		}
	}

	/**
	 * Convert one Woo page from a Blocks template to a classic shortcode.
	 *
	 * @since 0.12.4
	 *
	 * @param string $page       Woo page slug (cart|checkout).
	 * @param string $shortcode  Replacement content.
	 * @param string $block_name Block name to detect.
	 * @return bool True when the page already is classic or was converted.
	 */
	private function maybe_classic_page( $page, $shortcode, $block_name ) {
		$id = (int) wc_get_page_id( $page );

		if ( $id <= 0 ) {
			return false;
		}

		$post = get_post( $id );

		if ( ! $post ) {
			return false;
		}

		$tag = trim( str_replace( array( '[', ']' ), '', $shortcode ) );

		if ( has_shortcode( $post->post_content, $tag ) ) {
			return true;
		}

		$is_block = function_exists( 'has_block' ) && has_block( $block_name, $post );
		$is_block = $is_block || false !== strpos( $post->post_content, 'wp:' . $block_name );

		if ( ! $is_block ) {
			return true;
		}

		if ( ! get_post_meta( $id, '_cbv_content_before_classic', true ) ) {
			update_post_meta( $id, '_cbv_content_before_classic', $post->post_content );
		}

		$result = wp_update_post(
			array(
				'ID'           => $id,
				'post_content' => $shortcode,
			),
			true
		);

		return ! is_wp_error( $result );
	}

	/**
	 * Pair checkout address fields like the V1 shipping form.
	 *
	 * @since 0.12.5
	 *
	 * @param array $fields Address fields.
	 * @return array
	 */
	public function address_field_layout( $fields ) {
		$layout = array(
			'first_name' => array( 10, array( 'form-row-first' ) ),
			'last_name'  => array( 20, array( 'form-row-last' ) ),
			'company'    => array( 30, array( 'form-row-wide' ) ),
			'address_1'  => array( 40, array( 'form-row-wide' ) ),
			'address_2'  => array( 50, array( 'form-row-wide' ) ),
			'city'       => array( 60, array( 'form-row-first' ) ),
			'state'      => array( 70, array( 'form-row-last' ) ),
			'postcode'   => array( 80, array( 'form-row-first' ) ),
			'country'    => array( 90, array( 'form-row-last' ) ),
		);

		foreach ( $layout as $key => $spec ) {
			if ( ! isset( $fields[ $key ] ) ) {
				continue;
			}

			$fields[ $key ]['priority'] = $spec[0];
			$fields[ $key ]['class']    = $spec[1];
		}

		if ( isset( $fields['company'] ) ) {
			$fields['company']['placeholder'] = __( 'Restaurant / business name', 'commercebuild-velocity' );
		}

		/**
		 * Filter commerce checkout address field layout.
		 *
		 * @since 0.12.5
		 *
		 * @param array $fields Address fields after pairing.
		 */
		return apply_filters( 'cbv_address_fields', $fields );
	}

	/**
	 * Keep order notes out of the checkout address column.
	 *
	 * @since 0.12.5
	 *
	 * @param bool $enabled Whether notes are enabled.
	 * @return bool
	 */
	public function hide_order_notes( $enabled ) {
		/**
		 * Filter whether checkout order notes render.
		 *
		 * @since 0.12.5
		 *
		 * @param bool $show    False to hide.
		 * @param bool $enabled WooCommerce default.
		 */
		return (bool) apply_filters( 'cbv_checkout_order_notes', false, $enabled );
	}

	/**
	 * Label the checkout submit as the second stepper action.
	 *
	 * @since 0.12.6
	 *
	 * @param string $text WooCommerce button text.
	 * @return string
	 */
	public function order_button_text( $text ) {
		unset( $text );

		/**
		 * Filter the classic checkout submit label.
		 *
		 * @since 0.12.6
		 *
		 * @param string $label Button text.
		 */
		return (string) apply_filters( 'cbv_order_button_text', __( 'Proceed to Payment', 'commercebuild-velocity' ) );
	}

	/**
	 * Keep Company on the shipping form even when Woo hides it.
	 *
	 * @since 0.12.7
	 *
	 * @param string $value WooCommerce company-field setting.
	 * @return string
	 */
	public function company_field_visibility( $value ) {
		if ( 'required' === $value ) {
			return $value;
		}

		/**
		 * Filter checkout company-field visibility (hidden/optional/required).
		 *
		 * @since 0.12.7
		 *
		 * @param string $visibility Field visibility.
		 * @param string $value      WooCommerce setting.
		 */
		return (string) apply_filters( 'cbv_checkout_company_field', 'optional', $value );
	}

	/**
	 * Restore a missing Company checkout field and keep it full-width.
	 *
	 * @since 0.12.7
	 *
	 * @param array $fields Checkout fields.
	 * @return array
	 */
	public function ensure_company_field( $fields ) {
		if ( ! isset( $fields['billing'] ) || ! is_array( $fields['billing'] ) ) {
			return $fields;
		}

		if ( empty( $fields['billing']['billing_company'] ) ) {
			$fields['billing']['billing_company'] = array(
				'label'        => __( 'Company', 'commercebuild-velocity' ),
				'placeholder'  => __( 'Restaurant / business name', 'commercebuild-velocity' ),
				'required'     => false,
				'class'        => array( 'form-row-wide' ),
				'autocomplete' => 'organization',
				'priority'     => 30,
			);
		} else {
			$fields['billing']['billing_company']['label']       = __( 'Company', 'commercebuild-velocity' );
			$fields['billing']['billing_company']['class']       = array( 'form-row-wide' );
			$fields['billing']['billing_company']['priority']    = 30;
			$fields['billing']['billing_company']['placeholder'] = __( 'Restaurant / business name', 'commercebuild-velocity' );
		}

		/**
		 * Filter checkout fields after Company is ensured.
		 *
		 * @since 0.12.7
		 *
		 * @param array $fields Checkout fields.
		 */
		return apply_filters( 'cbv_checkout_fields', $fields );
	}

	/**
	 * Keep payment / place-order in the shipping column, not the summary.
	 *
	 * @since 0.12.8
	 *
	 * @return void
	 */
	public function relocate_checkout_payment() {
		remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
	}

	/**
	 * Refresh shipping method cards when checkout recalculates.
	 *
	 * @since 0.12.8
	 *
	 * @param array $fragments Order-review fragments.
	 * @return array
	 */
	public function checkout_methods_fragment( $fragments ) {
		if ( ! function_exists( 'cbv_the_checkout_shipping_methods' ) ) {
			return $fragments;
		}

		ob_start();
		cbv_the_checkout_shipping_methods();
		$fragments['.cbv-checkout-methods'] = ob_get_clean();

		/**
		 * Filter checkout fragments after shipping cards are added.
		 *
		 * @since 0.12.8
		 *
		 * @param array $fragments Order-review fragments.
		 */
		return apply_filters( 'cbv_checkout_fragments', $fragments );
	}
}
