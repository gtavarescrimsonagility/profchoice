<?php
/**
 * Professional's Choice child theme.
 *
 * @package profchoice
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Brand palette (Figma COMB-527) as the parent's palette defaults. The parent
 * turns each entry into a Customizer control, a --cbv-{slug} variable and an
 * editor palette color (settings.color.palette), so defaults live in code and
 * reach every environment; the Customizer can still override them.
 */
add_filter(
	'cbv_palette',
	function ( $palette ) {
		$brand = array(
			'primary'          => '#c32627', // Red: buttons, links, eyebrows.
			'primary_contrast' => '#ffffff',
			'secondary'        => '#0b2e54', // Navy: certified and signup sections.
			'accent'           => '#e6dacb', // Sand.
			'background'       => '#ffffff',
			'header_bg'        => '#ffffff',
			'footer_bg'        => '#f4f1ea', // Cream.
			'surface'          => '#f4f1ea', // Cream: catalog and trending sections.
			'text'             => '#14161a', // Ink: headings.
			'text_muted'       => '#3a3d44', // Slate: body copy.
			'border'           => '#e5e2dc', // Stone: sponsor strip borders.
			'danger'           => '#c32627',
		);
		foreach ( $brand as $slug => $hex ) {
			if ( isset( $palette[ $slug ] ) ) {
				$palette[ $slug ][1] = $hex;
			}
		}
		return $palette;
	}
);

/*
 * In the editor canvas iframe (Style Book, site editor) the parent's
 * main.css loads after the injected palette variables, so its :root
 * defaults win. Append the variables right after main.css there too.
 */
add_action(
	'enqueue_block_editor_assets',
	function () {
		if ( class_exists( 'CBV_Customizer' ) && wp_style_is( 'cbv-main', 'enqueued' ) ) {
			wp_add_inline_style( 'cbv-main', ( new CBV_Customizer() )->css_vars() );
		}
	},
	20
);

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
