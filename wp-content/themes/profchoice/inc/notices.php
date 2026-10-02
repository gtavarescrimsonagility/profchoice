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
 * @param string $part 'icon-success', 'icon-info', 'icon-danger' or 'close'.
 * @return string HTML.
 */
function profchoice_alert_part( $part ) {
	$icons = array(
		'icon-success' => 'check-circle',
		'icon-info'    => 'info-circle',
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
