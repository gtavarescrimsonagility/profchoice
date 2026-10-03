<?php
/**
 * WooCommerce notices as alerts (woocommerce/notices/*.php): styles and
 * the dismiss script, on every page (shop, cart, checkout, account).
 *
 * @package profchoice
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		$file = get_stylesheet_directory() . '/build/css/notices.css';
		if ( file_exists( $file ) ) {
			$deps = array_values(
				array_filter(
					array( 'cbv-main', 'woocommerce-general' ),
					function ( $handle ) {
						return wp_style_is( $handle, 'registered' );
					}
				)
			);
			wp_enqueue_style( 'profchoice-notices', get_stylesheet_directory_uri() . '/build/css/notices.css', $deps, (string) filemtime( $file ) );
			// Small enough to print inline instead of a render-blocking link.
			wp_style_add_data( 'profchoice-notices', 'path', $file );
		}
		wp_enqueue_script_module(
			'profchoice-notices',
			get_stylesheet_directory_uri() . '/assets/js/notices.js',
			array(),
			(string) filemtime( get_stylesheet_directory() . '/assets/js/notices.js' )
		);
	},
	105
);

/**
 * Alert pieces shared by the notice templates.
 *
 * @param string $part 'icon-success', 'icon-info', 'icon-warning', 'icon-danger' or 'close'.
 * @return string HTML.
 */
function profchoice_alert_part( $part ) {
	$icons = array(
		'icon-success' => 'check-circle',
		'icon-info'    => 'info-circle',
		'icon-warning' => 'alert-triangle',
		'icon-danger'  => 'alert-circle',
	);
	if ( isset( $icons[ $part ] ) ) {
		return profchoice_icon( $icons[ $part ], 'pc-alert__icon' );
	}
	$close = profchoice_icon( 'close', 'pc-alert__close-icon' );
	return sprintf(
		'<button class="pc-alert__close" type="button" aria-label="%s">%s</button>',
		esc_attr__( 'Dismiss', 'profchoice' ),
		$close ? $close : '<span aria-hidden="true">&times;</span>'
	);
}

/*
 * WooCommerce runs the printed notices through wp_kses: keep the alert's
 * icons (inline SVG) and dismiss button.
 */
add_filter(
	'woocommerce_kses_notice_allowed_tags',
	function ( $tags ) {
		$tags['svg']    = array(
			'class'       => true,
			'aria-hidden' => true,
			'focusable'   => true,
			'xmlns'       => true,
			'viewbox'     => true,
		);
		$tags['path']   = array( 'd' => true );
		$tags['button'] = array_merge(
			isset( $tags['button'] ) ? $tags['button'] : array(),
			array(
				'class'      => true,
				'type'       => true,
				'aria-label' => true,
			)
		);
		return $tags;
	}
);

/*
 * Preview every notice type: ?pc-notices=preview on a WooCommerce page, for
 * store managers only. Adds sample notices like the real ones (add to cart,
 * a form with errors, an info message) for design reviews.
 */
add_action(
	'template_redirect',
	function () {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only preview for store managers.
		$mode = isset( $_GET['pc-notices'] ) ? sanitize_key( wp_unslash( $_GET['pc-notices'] ) ) : '';
		if ( ! in_array( $mode, array( 'preview', 'preview-single' ), true ) || ! function_exists( 'wc_add_notice' ) || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		if ( 'preview-single' === $mode ) {
			wc_add_notice( __( 'Please enter a valid postcode to calculate shipping.', 'profchoice' ), 'error' );
			return;
		}
		$cart = wc_get_cart_url();
		wc_add_notice(
			sprintf(
				'<a href="%s" class="button wc-forward">%s</a> %s',
				esc_url( $cart ),
				esc_html__( 'View cart', 'woocommerce' ),
				esc_html__( '“Satin Dressage Pad with VenTECH™ Lining” has been added to your cart.', 'profchoice' )
			),
			'success'
		);
		wc_add_notice( __( 'Please choose product options by visiting the product page.', 'profchoice' ), 'notice' );
		wc_add_notice( __( 'Only 2 left in stock for this size. Order soon to get yours.', 'profchoice' ), 'notice', array( 'pc_variant' => 'warning' ) );
		wc_add_notice( __( 'Sorry, this product is unavailable. Please choose a different combination.', 'profchoice' ), 'error' );
		wc_add_notice( __( '<strong>Billing First name</strong> is a required field.', 'profchoice' ), 'error' );
		wc_add_notice( __( '<strong>Billing Email address</strong> is not a valid email address.', 'profchoice' ), 'error' );
	}
);
