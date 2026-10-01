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
 * Brand palette (from the Figma design) as the parent's palette defaults. The parent
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

/*
 * Let Global Styles (theme.json + Site Editor edits) win over the parent's
 * main.css, like in a block theme: print them after cbv-main. Otherwise
 * main.css redefines body color/background/font with the same specificity
 * and every Styles panel change is saved but invisible.
 *
 * Since WP 6.9 classic themes print global styles in the footer and hoist
 * them into the <head> at the position of 'wp-global-styles-placeholder',
 * so that handle (or 'global-styles' when hoisting is off) gets the dependency.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		$styles = wp_styles();
		foreach ( array( 'wp-global-styles-placeholder', 'global-styles' ) as $handle ) {
			if ( isset( $styles->registered[ $handle ], $styles->registered['cbv-main'] ) ) {
				$styles->registered[ $handle ]->deps[] = 'cbv-main';
			}
		}
	},
	20
);

/*
 * The parent paints `.editor-styles-wrapper { background-color; color }` in
 * every editor canvas so Block Areas match the site chrome. Outside Block
 * Areas it overrides Global Styles in the editor, so keep it only there.
 */
add_filter(
	'block_editor_settings_all',
	function ( $settings, $context ) {
		$post = isset( $context->post ) ? $context->post : null;
		if ( ( $post instanceof WP_Post && 'cbv_area' === $post->post_type ) || empty( $settings['styles'] ) ) {
			return $settings;
		}
		$settings['styles'] = array_values(
			array_filter(
				$settings['styles'],
				function ( $style ) {
					return ! isset( $style['css'] ) || 0 !== strpos( $style['css'], '.editor-styles-wrapper{background-color:' );
				}
			)
		);
		return $settings;
	},
	30,
	2
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
		// Small enough for core to print inline instead of a render-blocking link.
		wp_style_add_data( 'profchoice', 'path', get_stylesheet_directory() . '/style.css' );
	},
	100
);

/*
 * Meta description: the page's excerpt, or the site tagline (Settings >
 * General) on the front page and pages without one. Nothing when both are
 * empty, or when an SEO plugin prints its own.
 */
add_action(
	'wp_head',
	function () {
		if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) ) {
			return;
		}
		$description = is_singular() && has_excerpt() ? get_the_excerpt() : '';
		if ( '' === $description ) {
			$description = get_bloginfo( 'description' );
		}
		$description = trim( wp_strip_all_tags( $description ) );
		if ( '' !== $description ) {
			printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $description ) );
		}
	},
	1
);

/*
 * Preload the theme's web fonts so the swap-driven reflow (FOUT) in Playfair
 * Display and Montserrat headings happens before first paint instead of
 * after, which removes the font-swap contribution to Cumulative Layout Shift.
 */
add_filter(
	'wp_preload_resources',
	function ( $resources ) {
		$font_path = get_stylesheet_directory_uri() . '/assets/fonts/';
		$fonts     = array(
			'montserrat.woff2',
			'playfair-display.woff2',
			'playfair-display-italic.woff2',
		);
		foreach ( $fonts as $font ) {
			$resources[] = array(
				'href'        => $font_path . $font,
				'as'          => 'font',
				'type'        => 'font/woff2',
				'crossorigin' => 'anonymous',
			);
		}
		return $resources;
	}
);
