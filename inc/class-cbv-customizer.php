<?php
/**
 * Customizer color palette.
 *
 * Every theme color is a CSS custom property sourced from one Customizer
 * section, so a site re-skins without touching code. The same variables are
 * injected into the block editor so Gutenberg previews match the frontend.
 *
 * @package Commercebuild_Velocity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Color palette controls and CSS variable output.
 *
 * @since 0.1.0
 */
class CBV_Customizer {

	/**
	 * Palette definition: slug => array( label, default ).
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public static function palette() {
		/**
		 * Filter the theme color palette definition.
		 *
		 * Added colors get a Customizer control, a --cbv-{slug} CSS variable
		 * (underscores become dashes), and a Gutenberg palette entry.
		 *
		 * @since 0.3.0
		 *
		 * @param array $palette slug => array( label, default hex ).
		 */
		return apply_filters(
			'cbv_palette',
			array(
				'primary'          => array( __( 'Primary', 'commercebuild-velocity' ), '#2563eb' ),
				'primary_contrast' => array( __( 'Primary Contrast', 'commercebuild-velocity' ), '#ffffff' ),
				'secondary'        => array( __( 'Secondary', 'commercebuild-velocity' ), '#0f172a' ),
				'accent'           => array( __( 'Accent', 'commercebuild-velocity' ), '#f59e0b' ),
				'background'       => array( __( 'Body Background', 'commercebuild-velocity' ), '#ffffff' ),
				'header_bg'        => array( __( 'Header Background', 'commercebuild-velocity' ), '#ffffff' ),
				'footer_bg'        => array( __( 'Footer Background', 'commercebuild-velocity' ), '#0f172a' ),
				'surface'          => array( __( 'Surface', 'commercebuild-velocity' ), '#f6f7f9' ),
				'text'             => array( __( 'Text', 'commercebuild-velocity' ), '#111827' ),
				'text_muted'       => array( __( 'Muted Text', 'commercebuild-velocity' ), '#6b7280' ),
				'border'           => array( __( 'Borders', 'commercebuild-velocity' ), '#e5e7eb' ),
				'success'          => array( __( 'Success', 'commercebuild-velocity' ), '#16a34a' ),
				'danger'           => array( __( 'Danger / Sale', 'commercebuild-velocity' ), '#dc2626' ),
			)
		);
	}

	/**
	 * Register hooks.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'customize_register', array( $this, 'add_controls' ) );
		add_action( 'customize_preview_init', array( $this, 'preview_script' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'output_frontend_vars' ), 20 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'output_editor_vars' ) );
		add_filter( 'block_editor_settings_all', array( $this, 'inject_canvas_vars' ) );
		add_filter( 'wp_theme_json_data_theme', array( $this, 'sync_editor_palette' ) );
	}

	/**
	 * Add the palette section and one control per color.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_Customize_Manager $wp_customize Customizer manager.
	 * @return void
	 */
	public function add_controls( $wp_customize ) {
		$wp_customize->add_section(
			'cbv_colors',
			array(
				'title'    => __( 'Color Palette', 'commercebuild-velocity' ),
				'priority' => 30,
			)
		);

		foreach ( self::palette() as $slug => $color ) {
			$setting_id = 'cbv_color_' . $slug;

			// The contrast mode select sits directly above the manual
			// contrast picker it governs.
			if ( 'primary_contrast' === $slug ) {
				$wp_customize->add_setting(
					'cbv_primary_contrast_mode',
					array(
						'default'           => 'auto',
						'sanitize_callback' => 'sanitize_key',
						'transport'         => 'postMessage',
					)
				);

				$wp_customize->add_control(
					'cbv_primary_contrast_mode',
					array(
						'label'       => __( 'Primary Contrast Text', 'commercebuild-velocity' ),
						'description' => __( 'Auto picks dark or white text for the best readability on your primary color.', 'commercebuild-velocity' ),
						'section'     => 'cbv_colors',
						'type'        => 'select',
						'choices'     => array(
							'auto'   => __( 'Auto (best contrast)', 'commercebuild-velocity' ),
							'custom' => __( 'Use the color below', 'commercebuild-velocity' ),
						),
					)
				);
			}

			$wp_customize->add_setting(
				$setting_id,
				array(
					'default'           => $color[1],
					'sanitize_callback' => 'sanitize_hex_color',
					'transport'         => 'postMessage',
				)
			);

			$wp_customize->add_control(
				new WP_Customize_Color_Control(
					$wp_customize,
					$setting_id,
					array(
						'label'   => $color[0],
						'section' => 'cbv_colors',
					)
				)
			);
		}
	}

	/**
	 * Live-preview script for postMessage transport.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function preview_script() {
		wp_enqueue_script(
			'cbv-customizer-preview',
			CBV_URI . 'assets/js/customizer-preview.js',
			array( 'customize-preview' ),
			CBV_VERSION,
			true
		);

		wp_localize_script(
			'cbv-customizer-preview',
			'cbvPalette',
			array( 'slugs' => array_keys( self::palette() ) )
		);
	}

	/**
	 * Compiled CSS custom properties from current settings.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function css_vars() {
		$vars    = '';
		$palette = self::palette();

		foreach ( $palette as $slug => $color ) {
			$value = get_theme_mod( 'cbv_color_' . $slug, $color[1] );

			// In "auto" contrast mode (the default) primary-surface text
			// color is computed from the actual primary via WCAG contrast
			// ratios, so a light primary (lime, yellow) gets readable dark
			// text instead of the stock white. Picking "Use the color
			// below" restores the manual picker.
			if ( 'primary_contrast' === $slug && 'custom' !== get_theme_mod( 'cbv_primary_contrast_mode', 'auto' ) ) {
				$value = self::contrast_color( get_theme_mod( 'cbv_color_primary', $palette['primary'][1] ) );
			}

			$vars .= '--cbv-' . str_replace( '_', '-', $slug ) . ':' . sanitize_hex_color( $value ) . ';';
		}

		$contrast_sources = array(
			'secondary_contrast' => 'secondary',
			'footer_contrast'    => 'footer_bg',
			'success_contrast'   => 'success',
			'danger_contrast'    => 'danger',
			'accent_contrast'    => 'accent',
		);
		$contrast_tokens  = array();

		foreach ( $contrast_sources as $token => $source ) {
			if ( ! isset( $palette[ $source ] ) ) {
				continue;
			}

			$contrast_tokens[ $token ] = self::contrast_color( get_theme_mod( 'cbv_color_' . $source, $palette[ $source ][1] ) );
		}

		/**
		 * Filter semantic contrast tokens derived from palette backgrounds.
		 *
		 * @since 0.11.7
		 *
		 * @param array $contrast_tokens  Contrast token slug => hex color.
		 * @param array $contrast_sources Contrast token slug => palette source.
		 * @param array $palette          Current palette definition.
		 */
		$contrast_tokens = (array) apply_filters( 'cbv_contrast_tokens', $contrast_tokens, $contrast_sources, $palette );

		foreach ( $contrast_tokens as $slug => $value ) {
			$value = sanitize_hex_color( $value );

			if ( $value ) {
				$vars .= '--cbv-' . str_replace( '_', '-', sanitize_key( $slug ) ) . ':' . $value . ';';
			}
		}

		return ':root,body{' . $vars . '}';
	}

	/**
	 * Dark or white text, whichever has the higher WCAG contrast ratio
	 * against the given background color.
	 *
	 * @since 0.11.0
	 *
	 * @param string $hex Background hex color.
	 * @return string `#111827`, `#000000`, or `#ffffff`.
	 */
	public static function contrast_color( $hex ) {
		$luminance = self::relative_luminance( $hex );

		if ( null === $luminance ) {
			return '#ffffff';
		}

		$dark_luminance = self::relative_luminance( '#111827' );

		// WCAG contrast ratio: (L_lighter + 0.05) / (L_darker + 0.05).
		$ratio_white = 1.05 / ( $luminance + 0.05 );
		$ratio_dark  = ( max( $luminance, $dark_luminance ) + 0.05 ) / ( min( $luminance, $dark_luminance ) + 0.05 );

		$contrast = $ratio_dark >= $ratio_white ? '#111827' : '#ffffff';

		// Near-black preserves the theme's softer visual tone. At the narrow
		// range where neither near-black nor white reaches AA, use true black.
		if ( max( $ratio_dark, $ratio_white ) < 4.5 ) {
			$contrast = '#000000';
		}

		/**
		 * Filter the computed contrast color.
		 *
		 * @since 0.11.0
		 *
		 * @param string $contrast    Chosen contrast hex.
		 * @param string $hex         Background hex.
		 * @param float  $ratio_dark  Contrast ratio of dark text on it.
		 * @param float  $ratio_white Contrast ratio of white text on it.
		 */
		return apply_filters( 'cbv_contrast_color', $contrast, $hex, $ratio_dark, $ratio_white );
	}

	/**
	 * WCAG relative luminance of a hex color.
	 *
	 * @since 0.11.0
	 *
	 * @param string $hex Hex color.
	 * @return float|null 0 (black) to 1 (white), null when unparseable.
	 */
	private static function relative_luminance( $hex ) {
		$hex = ltrim( (string) sanitize_hex_color( $hex ), '#' );

		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		if ( 6 !== strlen( $hex ) ) {
			return null;
		}

		$channels = array();

		foreach ( array( 0, 2, 4 ) as $offset ) {
			$channel    = hexdec( substr( $hex, $offset, 2 ) ) / 255;
			$channels[] = $channel <= 0.04045 ? $channel / 12.92 : pow( ( $channel + 0.055 ) / 1.055, 2.4 );
		}

		return ( 0.2126 * $channels[0] ) + ( 0.7152 * $channels[1] ) + ( 0.0722 * $channels[2] );
	}

	/**
	 * Push the palette variables into the block editor canvas iframe so
	 * server-rendered block previews match the frontend.
	 *
	 * @since 0.1.0
	 *
	 * @param array $settings Block editor settings.
	 * @return array
	 */
	public function inject_canvas_vars( $settings ) {
		$settings['styles'][] = array(
			'css'            => $this->css_vars(),
			'__unstableType' => 'theme',
		);

		return $settings;
	}

	/**
	 * Inline the variables after the main stylesheet.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function output_frontend_vars() {
		wp_add_inline_style( 'cbv-main', $this->css_vars() );
	}

	/**
	 * Make the same variables available inside the block editor.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function output_editor_vars() {
		wp_register_style( 'cbv-editor-vars', false, array(), CBV_VERSION );
		wp_enqueue_style( 'cbv-editor-vars' );
		wp_add_inline_style( 'cbv-editor-vars', $this->css_vars() );
	}

	/**
	 * Expose the palette to Gutenberg as named colors that track the
	 * Customizer via CSS variables.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_Theme_JSON_Data $theme_json Theme JSON data object.
	 * @return WP_Theme_JSON_Data
	 */
	public function sync_editor_palette( $theme_json ) {
		$palette = array();

		foreach ( self::palette() as $slug => $color ) {
			$palette[] = array(
				'slug'  => 'cbv-' . str_replace( '_', '-', $slug ),
				'name'  => $color[0],
				'color' => 'var(--cbv-' . str_replace( '_', '-', $slug ) . ')',
			);
		}

		return $theme_json->update_with(
			array(
				'version'  => 2,
				'settings' => array(
					'color' => array(
						'palette' => $palette,
					),
				),
			)
		);
	}
}
