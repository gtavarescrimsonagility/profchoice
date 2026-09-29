<?php
/**
 * Design presets: header icon style, button shape, nav style, card hover,
 * sticky header.
 *
 * Look-and-feel choices are Customizer options so per-customer taste never
 * requires CSS surgery. The rules for the ACTIVE preset are generated once
 * in PHP and injected into both the frontend and the block-editor canvas,
 * so the editor previews exactly what the frontend renders (AGENTS.md §3).
 * Body classes are still emitted for child themes to key off.
 *
 * @package Commercebuild_Velocity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Design option controls and output.
 *
 * @since 0.11.0
 */
class CBV_Design {

	/**
	 * Design settings registry.
	 *
	 * @since 0.11.0
	 *
	 * @return array slug => array( label, default, choices ).
	 */
	public static function settings() {
		/**
		 * Filter the design settings registry.
		 *
		 * Added entries get a Customizer select control and a
		 * `cbv-{slug-with-dashes}--{value}` body class automatically.
		 *
		 * @since 0.11.0
		 *
		 * @param array $settings slug => array( label, default, choices array ).
		 */
		return apply_filters(
			'cbv_design_settings',
			array(
				'heading_tone'      => array(
					__( 'Heading color', 'commercebuild-velocity' ),
					'neutral',
					array(
						'neutral' => __( 'Neutral (text color)', 'commercebuild-velocity' ),
						'brand'   => __( 'Brand (secondary color)', 'commercebuild-velocity' ),
						'accent'  => __( 'Accent color', 'commercebuild-velocity' ),
					),
				),
				'header_icon_style' => array(
					__( 'Header icons', 'commercebuild-velocity' ),
					'icons_labels',
					array(
						'icons_labels' => __( 'Icons with labels', 'commercebuild-velocity' ),
						'icons'        => __( 'Icons only', 'commercebuild-velocity' ),
						'labels'       => __( 'Labels only', 'commercebuild-velocity' ),
					),
				),
				'button_shape'      => array(
					__( 'Button shape', 'commercebuild-velocity' ),
					'rounded',
					array(
						'rounded' => __( 'Rounded', 'commercebuild-velocity' ),
						'pill'    => __( 'Pill', 'commercebuild-velocity' ),
						'square'  => __( 'Square', 'commercebuild-velocity' ),
					),
				),
				'nav_style'         => array(
					__( 'Navigation style', 'commercebuild-velocity' ),
					'filled',
					array(
						'filled'  => __( 'Filled bar', 'commercebuild-velocity' ),
						'minimal' => __( 'Minimal (underline on hover)', 'commercebuild-velocity' ),
					),
				),
				'card_hover'        => array(
					__( 'Product card hover', 'commercebuild-velocity' ),
					'lift',
					array(
						'lift' => __( 'Lift with shadow', 'commercebuild-velocity' ),
						'zoom' => __( 'Zoom image', 'commercebuild-velocity' ),
						'none' => __( 'None', 'commercebuild-velocity' ),
					),
				),
				'sticky_header'     => array(
					__( 'Sticky header', 'commercebuild-velocity' ),
					'off',
					array(
						'off' => __( 'Off', 'commercebuild-velocity' ),
						'on'  => __( 'On (header follows scroll)', 'commercebuild-velocity' ),
					),
				),
				'header_layout'     => array(
					__( 'Header layout', 'commercebuild-velocity' ),
					'classic',
					array(
						'classic'  => __( 'Classic (logo, search, account, cart, menu)', 'commercebuild-velocity' ),
						'commerce' => __( 'Commerce (utility bar, departments, category strip, ticker)', 'commercebuild-velocity' ),
					),
				),
			)
		);
	}

	/**
	 * Register hooks.
	 *
	 * @since 0.11.0
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'customize_register', array( $this, 'add_controls' ) );
		add_filter( 'body_class', array( $this, 'body_classes' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'output_frontend_css' ), 21 );
		add_filter( 'block_editor_settings_all', array( $this, 'inject_canvas_css' ) );
		add_action( 'customize_save_after', array( $this, 'purge_page_cache' ) );
	}

	/**
	 * Current value of a design setting.
	 *
	 * @since 0.11.0
	 *
	 * @param string $slug Setting slug from the registry.
	 * @return string
	 */
	public static function get( $slug ) {
		$settings = self::settings();

		if ( ! isset( $settings[ $slug ] ) ) {
			return '';
		}

		$value = get_theme_mod( 'cbv_design_' . $slug, $settings[ $slug ][1] );

		if ( ! isset( $settings[ $slug ][2][ $value ] ) ) {
			$value = $settings[ $slug ][1];
		}

		/**
		 * Filter a resolved design setting value.
		 *
		 * @since 0.11.0
		 *
		 * @param string $value Setting value.
		 * @param string $slug  Setting slug.
		 */
		return apply_filters( 'cbv_design_value', $value, $slug );
	}

	/**
	 * Add the Design section and one select per setting.
	 *
	 * @since 0.11.0
	 *
	 * @param WP_Customize_Manager $wp_customize Customizer manager.
	 * @return void
	 */
	public function add_controls( $wp_customize ) {
		$wp_customize->add_section(
			'cbv_design',
			array(
				'title'    => __( 'Design Style', 'commercebuild-velocity' ),
				'priority' => 29,
			)
		);

		foreach ( self::settings() as $slug => $setting ) {
			$setting_id = 'cbv_design_' . $slug;

			$wp_customize->add_setting(
				$setting_id,
				array(
					'default'           => $setting[1],
					'sanitize_callback' => 'sanitize_key',
				)
			);

			$wp_customize->add_control(
				$setting_id,
				array(
					'label'   => $setting[0],
					'section' => 'cbv_design',
					'type'    => 'select',
					'choices' => $setting[2],
				)
			);
		}
	}

	/**
	 * Map each design setting to a body class.
	 *
	 * @since 0.11.0
	 *
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public function body_classes( $classes ) {
		foreach ( array_keys( self::settings() ) as $slug ) {
			$classes[] = 'cbv-' . str_replace( '_', '-', $slug ) . '--' . self::get( $slug );
		}

		/**
		 * Filter the design-derived body classes.
		 *
		 * @since 0.11.0
		 *
		 * @param string[] $classes Full body class list.
		 */
		return apply_filters( 'cbv_design_body_classes', $classes );
	}

	/**
	 * CSS for the active presets, scoped for frontend or editor canvas.
	 *
	 * One generator feeds both surfaces so they cannot drift. Only the
	 * selected preset's rules ship; the alternatives cost nothing.
	 *
	 * @since 0.11.0
	 *
	 * @param string $scope Selector prefix: 'body' on the frontend,
	 *                      '.editor-styles-wrapper' in the canvas.
	 * @return string
	 */
	public function preset_css( $scope ) {
		$s   = $scope;
		$css = '';

		$radii = array(
			'rounded' => 'var(--cbv-radius)',
			'pill'    => '999px',
			'square'  => '3px',
		);
		$shape = self::get( 'button_shape' );

		/**
		 * Filter the button border radius derived from the shape setting.
		 *
		 * @since 0.11.0
		 *
		 * @param string $radius CSS radius value.
		 * @param string $shape  Selected shape slug.
		 */
		$radius = apply_filters( 'cbv_button_radius', isset( $radii[ $shape ] ) ? $radii[ $shape ] : $radii['rounded'], $shape );

		$heading_vars = array(
			'neutral' => 'var(--cbv-text)',
			'brand'   => 'var(--cbv-secondary)',
			'accent'  => 'var(--cbv-accent)',
		);
		$tone         = self::get( 'heading_tone' );
		$heading      = isset( $heading_vars[ $tone ] ) ? $heading_vars[ $tone ] : $heading_vars['neutral'];

		$var_scope = 'body' === $s ? ':root,body' : $s;
		$css      .= $var_scope . '{--cbv-radius-button:' . $radius . ';--cbv-heading:' . $heading . ';}';

		$icon_style = self::get( 'header_icon_style' );

		if ( 'icons' === $icon_style ) {
			// Only hide the label when the link actually has an icon, so an
			// icon of "none" never yields a visually empty link. Without
			// :has() support the label just stays visible - safe fallback.
			$css .= $s . ' .cbv-hblock__link:has(.cbv-icon) .cbv-hblock__label{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;}';
		} elseif ( 'labels' === $icon_style ) {
			$css .= $s . ' .cbv-hblock__icon .cbv-icon{display:none;}';
			$css .= $s . ' .cbv-hblock__icon:not(:has(.cbv-header__cart-count--badge)){display:none;}';
			$css .= $s . ' .cbv-header__cart-count--badge{position:static;border:0;background:var(--cbv-primary);color:var(--cbv-primary-contrast);}';
			// With icons gone the label is the only representation left;
			// restore it even when a block hid it via showLabel.
			$css .= $s . ' .cbv-hblock__link .cbv-hblock__label.screen-reader-text{position:static;width:auto;height:auto;overflow:visible;clip:auto;white-space:normal;margin:0;}';
		}

		if ( 'minimal' === self::get( 'nav_style' ) ) {
			// The body background (not the header background) so the text
			// color pairing always holds, whatever the header color is.
			$css .= $s . ' .cbv-nav{background:var(--cbv-background);border-bottom:1px solid var(--cbv-border);}';
			$css .= $s . ' .cbv-nav__list > li > a{position:relative;color:var(--cbv-text);font-weight:600;}';
			$css .= $s . ' .cbv-nav__list > li > a::after{content:"";position:absolute;left:16px;right:16px;bottom:6px;height:2px;background:var(--cbv-primary);transform:scaleX(0);transform-origin:left;transition:transform 0.18s ease;}';
			$css .= $s . ' .cbv-nav__list > li > a:hover{background:transparent;color:var(--cbv-primary);}';
			$css .= $s . ' .cbv-nav__list > li > a:hover::after,' . $s . ' .cbv-nav__list > li > a:focus-visible::after{transform:scaleX(1);}';
			$css .= $s . ' .cbv-nav__list > li > .cbv-nav__toggle{color:var(--cbv-text);}';
			$css .= $s . ' .cbv-nav__list > li > .cbv-nav__toggle:hover{background:var(--cbv-surface);}';
		}

		$hover = self::get( 'card_hover' );

		if ( 'lift' === $hover ) {
			$css .= $s . ' .cbv-card:hover{box-shadow:0 8px 24px rgba(0,0,0,0.08);transform:translateY(-2px);}';
		} elseif ( 'zoom' === $hover ) {
			$css .= $s . ' .cbv-card:hover{border-color:color-mix(in srgb, var(--cbv-primary) 40%, var(--cbv-border));}';
			$css .= $s . ' .cbv-card:hover .cbv-card__media img{transform:scale(1.05);}';
		}

		$css .= '@media (prefers-reduced-motion:reduce){' . $s . ' .cbv-card:hover,' . $s . ' .cbv-card:hover .cbv-card__media img{transform:none;}}';

		// Sticky is viewport behavior, meaningless inside the canvas.
		if ( 'body' === $s && 'on' === self::get( 'sticky_header' ) ) {
			$css .= 'body .cbv-header{position:sticky;top:0;z-index:90;box-shadow:0 1px 10px rgba(0,0,0,0.06);}';
		}

		/**
		 * Filter the generated preset CSS.
		 *
		 * @since 0.11.0
		 *
		 * @param string $css   Generated rules.
		 * @param string $scope Selector prefix in use.
		 */
		return apply_filters( 'cbv_design_preset_css', $css, $scope );
	}

	/**
	 * Inline the active preset rules after the main stylesheet.
	 *
	 * @since 0.11.0
	 *
	 * @return void
	 */
	public function output_frontend_css() {
		wp_add_inline_style( 'cbv-main', $this->preset_css( 'body' ) );
	}

	/**
	 * Push the same preset rules into the block editor canvas.
	 *
	 * @since 0.11.0
	 *
	 * @param array $settings Block editor settings.
	 * @return array
	 */
	public function inject_canvas_css( $settings ) {
		$settings['styles'][] = array(
			'css'            => $this->preset_css( '.editor-styles-wrapper' ),
			'__unstableType' => 'theme',
		);

		return $settings;
	}

	/**
	 * Ask the page cache to drop everything after a Customizer save, so
	 * guests don't keep the previous design out of cache.
	 *
	 * Breeze listens for this action; on stacks without it the action is a
	 * no-op.
	 *
	 * @since 0.11.0
	 *
	 * @return void
	 */
	public function purge_page_cache() {
		do_action( 'breeze_clear_all_cache' );
	}
}
