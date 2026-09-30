<?php
/**
 * Professional's Choice child theme.
 *
 * @package profchoice
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style(
			'profchoice',
			get_stylesheet_uri(),
			array( 'cbv-main' ),
			wp_get_theme()->get( 'Version' )
		);
	}
);
