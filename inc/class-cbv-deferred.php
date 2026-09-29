<?php
/**
 * Deferred product-page elements.
 *
 * Heavy below-the-fold elements (related, upsells, cross-sells) render as
 * skeleton placeholders on the initial response and hydrate through a REST
 * fragment endpoint when they approach the viewport. Purchase-critical
 * elements (price, stock, add-to-cart, variations) are never deferred.
 *
 * @package Commercebuild_Velocity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Skeleton-first rendering for heavy PDP elements.
 *
 * @since 0.10.0
 */
class CBV_Deferred {

	/**
	 * Elements that may never be deferred.
	 *
	 * @var string[]
	 */
	const NEVER_DEFER = array( 'price', 'stock_qty', 'add_to_cart', 'variations', 'poa', 'uom' );

	/**
	 * True while the REST endpoint renders fragments, so the deferral
	 * short-circuit stands down and the real renderer runs.
	 *
	 * @var bool
	 */
	private $rendering_fragment = false;

	/**
	 * Register hooks.
	 *
	 * @since 0.10.0
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'cbv_product_element_output', array( $this, 'maybe_defer' ), 5, 4 );
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ), 25 );
		add_action( 'customize_register', array( $this, 'add_controls' ), 20 );
	}

	/**
	 * The set of element ids deferred on this request.
	 *
	 * @since 0.10.0
	 *
	 * @return string[]
	 */
	public function deferred_elements() {
		$mode     = get_theme_mod( 'cbv_product_deferral', 'balanced' );
		$elements = 'off' === $mode ? array() : array( 'related', 'upsells', 'cross_sells' );

		/**
		 * Filter the element ids rendered as deferred skeletons.
		 *
		 * Purchase-critical elements in CBV_Deferred::NEVER_DEFER are always
		 * stripped from this list.
		 *
		 * @since 0.10.0
		 *
		 * @param string[] $elements Deferred element ids.
		 * @param string   $mode     Customizer mode: balanced|off.
		 */
		$elements = (array) apply_filters( 'cbv_deferred_elements', $elements, $mode );

		return array_values( array_diff( $elements, self::NEVER_DEFER ) );
	}

	/**
	 * Replace a deferred element's output with a skeleton placeholder.
	 *
	 * @since 0.10.0
	 *
	 * @param null|string $output  Short-circuit output from earlier filters.
	 * @param string      $id      Element id.
	 * @param WC_Product  $product Product object.
	 * @param array       $options Element options.
	 * @return null|string
	 */
	public function maybe_defer( $output, $id, $product, $options ) {
		if ( null !== $output || $this->rendering_fragment || is_admin() ) {
			return $output;
		}

		if ( ! function_exists( 'is_product' ) || ! is_product() || ! in_array( $id, $this->deferred_elements(), true ) ) {
			return $output;
		}

		// A crawler that does not run JavaScript would never hydrate the
		// skeleton, so serve it the real section to keep related/upsell/
		// cross-sell content indexable (the PDP counterpart to AGENTS.md rule 2).
		if ( $this->is_crawler() ) {
			return $output;
		}

		/**
		 * Filter whether a specific element defers on this request.
		 *
		 * @since 0.10.0
		 *
		 * @param bool       $defer   True to defer.
		 * @param string     $id      Element id.
		 * @param WC_Product $product Product object.
		 */
		if ( ! apply_filters( 'cbv_defer_element', true, $id, $product ) ) {
			return $output;
		}

		$min_height  = (int) apply_filters( 'cbv_deferred_min_height', 300, $id );
		$label       = $this->section_label( $id );
		$placeholder = sprintf(
			'<section class="cbv-deferred" data-cbv-element="%1$s" data-cbv-product="%2$d" data-cbv-min-height="%3$d" aria-label="%4$s" aria-busy="true" style="min-height:%3$dpx">%5$s%6$s</section>',
			esc_attr( $id ),
			(int) $product->get_id(),
			$min_height,
			esc_attr( $label ),
			$this->skeleton_html( $id ),
			$this->error_html( $id, $label )
		);

		/**
		 * Filter the skeleton placeholder markup for a deferred element.
		 *
		 * @since 0.10.0
		 *
		 * @param string     $placeholder Placeholder HTML.
		 * @param string     $id          Element id.
		 * @param WC_Product $product     Product object.
		 * @param array      $options     Element options.
		 */
		return apply_filters( 'cbv_deferred_placeholder_html', $placeholder, $id, $product, $options );
	}

	/**
	 * Human-readable label for a deferred product section.
	 *
	 * @since 0.11.7
	 *
	 * @param string $id Element id.
	 * @return string
	 */
	private function section_label( $id ) {
		$labels = array(
			'related'     => __( 'related products', 'commercebuild-velocity' ),
			'upsells'     => __( 'recommended products', 'commercebuild-velocity' ),
			'cross_sells' => __( 'products you may also like', 'commercebuild-velocity' ),
		);
		$label  = isset( $labels[ $id ] ) ? $labels[ $id ] : __( 'product recommendations', 'commercebuild-velocity' );

		/**
		 * Filter the accessible label for a deferred product section.
		 *
		 * @since 0.11.7
		 *
		 * @param string $label Section label.
		 * @param string $id    Element id.
		 */
		return (string) apply_filters( 'cbv_deferred_section_label', $label, $id );
	}

	/**
	 * Visible recovery state for a deferred product section.
	 *
	 * @since 0.11.7
	 *
	 * @param string $id    Element id.
	 * @param string $label Human-readable section label.
	 * @return string
	 */
	private function error_html( $id, $label ) {
		$error_html = sprintf(
			'<div class="cbv-deferred__error" hidden role="status" aria-live="polite"><p>%1$s</p><button type="button" class="cbv-button cbv-deferred__retry" data-default-label="%2$s" data-busy-label="%3$s">%2$s</button></div>',
			esc_html( sprintf( /* translators: %s: product section label. */ __( 'We could not load %s. Check your connection and try again.', 'commercebuild-velocity' ), $label ) ),
			esc_html__( 'Try again', 'commercebuild-velocity' ),
			esc_html__( 'Trying again…', 'commercebuild-velocity' )
		);

		/**
		 * Filter the visible recovery markup for a deferred section.
		 *
		 * @since 0.11.7
		 *
		 * @param string $error_html Recovery-state HTML.
		 * @param string $id         Element id.
		 * @param string $label      Human-readable section label.
		 */
		return (string) apply_filters( 'cbv_deferred_error_html', $error_html, $id, $label );
	}

	/**
	 * Whether the current request is from a crawler that should receive the
	 * fully rendered sections instead of hydrating skeletons.
	 *
	 * Deferral trades a little initial server work for a faster first paint,
	 * but a crawler that does not execute JavaScript would otherwise never see
	 * the deferred sections. Serving it the real markup keeps them indexable.
	 *
	 * @since 0.11.5
	 *
	 * @return bool
	 */
	private function is_crawler() {
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

		$is_crawler = '' !== $ua && (bool) preg_match(
			'/bot|crawl|spider|slurp|mediapartners|facebookexternalhit|embedly|quora link preview|pinterest|redditbot|applebot|whatsapp|flipboard|tumblr|bitlybot|skypeuripreview|nuzzel|discordbot|qwantify|lighthouse|pingdom|gtmetrix/i',
			$ua
		);

		/**
		 * Filter whether the current request is treated as a crawler for
		 * deferred PDP sections. Return true to force full server rendering,
		 * for example for a known no-JavaScript audience.
		 *
		 * @since 0.11.5
		 *
		 * @param bool   $is_crawler Whether the user agent looks like a crawler.
		 * @param string $ua         The request user agent.
		 */
		return (bool) apply_filters( 'cbv_is_crawler', $is_crawler, $ua );
	}

	/**
	 * Skeleton card row markup.
	 *
	 * @since 0.10.0
	 *
	 * @param string $id Element id.
	 * @return string
	 */
	private function skeleton_html( $id ) {
		$cards = '';

		for ( $i = 0; $i < 4; $i++ ) {
			$cards .= '<div class="cbv-skeleton-card" aria-hidden="true"><div class="cbv-skeleton-card__thumb"></div><div class="cbv-skeleton-card__line"></div><div class="cbv-skeleton-card__line cbv-skeleton-card__line--short"></div></div>';
		}

		return '<div class="cbv-skeleton-row" data-cbv-skeleton="' . esc_attr( $id ) . '">' . $cards . '</div>';
	}

	/**
	 * REST route: batch product element fragments.
	 *
	 * @since 0.10.0
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'cbv/v1',
			'/product-fragments',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => array( $this, 'fragments_response' ),
				'args'                => array(
					'product_id' => array(
						'type'     => 'integer',
						'required' => true,
					),
					'elements'   => array(
						'type'     => 'array',
						'required' => true,
						'items'    => array( 'type' => 'string' ),
					),
				),
			)
		);
	}

	/**
	 * Render the requested fragments for one product.
	 *
	 * Visibility is revalidated for the current user through the query
	 * engine, so a product this user must not see returns 404 regardless of
	 * what the request asks for.
	 *
	 * @since 0.10.0
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function fragments_response( $request ) {
		$product_id = absint( $request['product_id'] );
		$product    = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;

		if ( ! $product || 'publish' !== $product->get_status() || ! $product->is_visible() ) {
			return new WP_Error( 'cbv_not_found', __( 'Not available.', 'commercebuild-velocity' ), array( 'status' => 404 ) );
		}

		if ( function_exists( 'cbv_engine' ) && empty( cbv_engine()->filter_visible_ids( array( $product_id ) ) ) ) {
			return new WP_Error( 'cbv_not_found', __( 'Not available.', 'commercebuild-velocity' ), array( 'status' => 404 ) );
		}

		$allowed   = $this->deferred_elements();
		$requested = array_intersect( array_map( 'sanitize_key', (array) $request['elements'] ), $allowed );
		$layout    = CBV_Product_Layout::layout();
		$options   = array();

		foreach ( $layout as $rows ) {
			foreach ( $rows as $row ) {
				if ( ! empty( $row['enabled'] ) && isset( $row['id'] ) ) {
					$options[ $row['id'] ] = isset( $row['options'] ) ? (array) $row['options'] : array();
				}
			}
		}

		// Element context: templates rely on the loop globals.
		global $post;
		$post = get_post( $product_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restored below.
		setup_postdata( $post );
		$GLOBALS['product'] = $product;

		$fragments                = array();
		$this->rendering_fragment = true;

		foreach ( $requested as $element_id ) {
			if ( ! array_key_exists( $element_id, $options ) ) {
				continue;
			}

			ob_start();
			CBV_Product_Elements::render( $element_id, $product, $options[ $element_id ] );
			$html = (string) ob_get_clean();

			/**
			 * Filter a rendered fragment before it is returned.
			 *
			 * @since 0.10.0
			 *
			 * @param string     $html       Fragment HTML (may be empty).
			 * @param string     $element_id Element id.
			 * @param WC_Product $product    Product object.
			 */
			$fragments[ $element_id ] = apply_filters( 'cbv_fragment_html', $html, $element_id, $product );
		}

		$this->rendering_fragment = false;
		wp_reset_postdata();

		return rest_ensure_response( array( 'fragments' => $fragments ) );
	}

	/**
	 * Enqueue the hydration script on product pages with deferred elements.
	 *
	 * @since 0.10.0
	 *
	 * @return void
	 */
	public function enqueue() {
		if ( ! function_exists( 'is_product' ) || ! is_product() || empty( $this->deferred_elements() ) ) {
			return;
		}

		wp_enqueue_script(
			'cbv-deferred',
			CBV_URI . 'assets/js/deferred.js',
			array(),
			CBV_VERSION,
			true
		);

		wp_localize_script(
			'cbv-deferred',
			'cbvDeferred',
			array(
				'endpoint' => esc_url_raw( rest_url( 'cbv/v1/product-fragments' ) ),

				/*
				 * The fragment endpoint is public, so the nonce authorizes
				 * nothing for a logged-out visitor - but a stale one is fatal:
				 * WordPress rejects an *invalid* REST nonce outright, so a
				 * page-cached PDP outliving its nonce tick would 403 and leave
				 * every deferred section stuck as a skeleton. Logged-in
				 * responses are never page-cached, so only they carry it.
				 */
				'nonce'    => is_user_logged_in() ? wp_create_nonce( 'wp_rest' ) : '',
				/**
				 * Filter the IntersectionObserver root margin for hydration.
				 *
				 * @since 0.10.0
				 *
				 * @param string $margin CSS margin string.
				 */
				'margin'   => apply_filters( 'cbv_deferred_observer_margin', '400px 0px' ),

				/**
				 * Filter how many times a failed fragment request is retried
				 * after the initial request before the sections are marked
				 * failed. 0 disables retries.
				 *
				 * @since 0.11.5
				 *
				 * @param int $retries Retry count after the first attempt.
				 */
				'retries'  => max( 0, (int) apply_filters( 'cbv_deferred_retries', 3 ) ),
			)
		);
	}

	/**
	 * Customizer control: deferral mode.
	 *
	 * @since 0.10.0
	 *
	 * @param WP_Customize_Manager $wp_customize Customizer manager.
	 * @return void
	 */
	public function add_controls( $wp_customize ) {
		$wp_customize->add_setting(
			'cbv_product_deferral',
			array(
				'default'           => 'balanced',
				'sanitize_callback' => 'sanitize_key',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'cbv_product_deferral',
			array(
				'label'       => __( 'Deferred loading of heavy sections', 'commercebuild-velocity' ),
				'description' => __( 'Related, upsell, and cross-sell sections load as they scroll into view, making the page itself load faster.', 'commercebuild-velocity' ),
				'section'     => 'cbv_product_page',
				'type'        => 'select',
				'choices'     => array(
					'balanced' => __( 'On (recommended)', 'commercebuild-velocity' ),
					'off'      => __( 'Off (render everything up front)', 'commercebuild-velocity' ),
				),
			)
		);
	}
}
