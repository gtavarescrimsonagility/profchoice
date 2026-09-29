<?php
/**
 * Gutenberg-editable header and footer areas.
 *
 * Registers a private "Block Area" post type with one document each for the
 * header and footer. Customers edit them with the full block editor
 * (Appearance > Block Areas); the theme renders them inside the structural
 * header/footer, which stay as lean PHP for performance.
 *
 * @package Commercebuild_Velocity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Block area registration and rendering.
 *
 * @since 0.2.0
 */
class CBV_Block_Areas {

	/**
	 * Area slug => title.
	 *
	 * @var array
	 */
	const AREAS = array(
		'header' => 'Header Area',
		'footer' => 'Footer Area',
	);

	/**
	 * Register hooks.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'admin_init', array( $this, 'ensure_areas' ) );
		add_action( 'customize_register', array( $this, 'add_controls' ) );

		// Seeding on admin_init alone would miss the transition: the mod is
		// saved after that hook has run, leaving a blank header until the next
		// admin request.
		add_action( 'customize_save_after', array( $this, 'ensure_areas' ) );

		// Runs after the palette variables are injected so these rules can
		// reference them.
		add_filter( 'block_editor_settings_all', array( $this, 'canvas_styles' ), 20, 2 );
	}

	/**
	 * Give the editor canvas the same background the area has on the site.
	 *
	 * The header and footer backgrounds live on their structural wrappers,
	 * which do not exist inside the editor, so without this the Header Area
	 * document is edited on plain white while the site renders it on the
	 * palette colour.
	 *
	 * @since 0.9.2
	 *
	 * @param array                   $settings Block editor settings.
	 * @param WP_Block_Editor_Context $context  Editor context.
	 * @return array
	 */
	public function canvas_styles( $settings, $context ) {
		$background = 'var(--cbv-background)';
		$hex        = get_theme_mod( 'cbv_color_background', '#ffffff' );

		$post = isset( $context->post ) ? $context->post : null;

		if ( $post instanceof WP_Post && 'cbv_area' === $post->post_type ) {
			if ( (int) get_option( 'cbv_area_header' ) === $post->ID ) {
				$background = 'var(--cbv-header-bg)';
				$hex        = get_theme_mod( 'cbv_color_header_bg', '#ffffff' );
			} elseif ( (int) get_option( 'cbv_area_footer' ) === $post->ID ) {
				$background = 'var(--cbv-footer-bg)';
				$hex        = get_theme_mod( 'cbv_color_footer_bg', '#0f172a' );
			}
		}

		$color = CBV_Customizer::contrast_color( $hex );

		/**
		 * Filter the block-area editor canvas background and text colors.
		 *
		 * @since 0.11.7
		 *
		 * @param array                   $colors  Background and text colors.
		 * @param WP_Block_Editor_Context $context Editor context.
		 * @param string                  $hex     Current background hex.
		 */
		$colors     = (array) apply_filters(
			'cbv_block_area_canvas_colors',
			array(
				'background' => $background,
				'color'      => $color,
			),
			$context,
			$hex
		);
		$background = isset( $colors['background'] ) ? $colors['background'] : $background;
		$color      = isset( $colors['color'] ) ? sanitize_hex_color( $colors['color'] ) : $color;

		if ( ! $color ) {
			$color = CBV_Customizer::contrast_color( $hex );
		}

		$settings['styles'][] = array(
			'css'            => sprintf(
				'.editor-styles-wrapper{background-color:%1$s;color:%2$s;}
				.editor-styles-wrapper .editor-post-title__input,
				.editor-styles-wrapper .wp-block-post-title{color:%2$s;}',
				$background,
				$color
			),
			'__unstableType' => 'theme',
		);

		return $settings;
	}

	/**
	 * Current header rendering mode.
	 *
	 * Classic renders the theme's PHP header chrome (logo, search,
	 * account/cart, nav); blocks composes the entire header from the Header
	 * Area document instead.
	 *
	 * @since 0.4.0
	 *
	 * @return string classic|blocks
	 */
	public static function header_mode() {
		$mode = get_theme_mod( 'cbv_header_mode', 'classic' );

		/**
		 * Filter the header rendering mode.
		 *
		 * @since 0.4.0
		 *
		 * @param string $mode classic|blocks.
		 */
		$mode = apply_filters( 'cbv_header_mode', $mode );

		return ( 'blocks' === $mode ) ? 'blocks' : 'classic';
	}

	/**
	 * Customizer control for the header mode.
	 *
	 * @since 0.4.0
	 *
	 * @param WP_Customize_Manager $wp_customize Customizer manager.
	 * @return void
	 */
	public function add_controls( $wp_customize ) {
		$wp_customize->add_section(
			'cbv_header',
			array(
				'title'       => __( 'Header Layout', 'commercebuild-velocity' ),
				'priority'    => 28,
				'description' => __( 'Choose the built-in header, or build your own in Appearance &rsaquo; Block Areas &rsaquo; Header Area.', 'commercebuild-velocity' ),
			)
		);

		$wp_customize->add_setting(
			'cbv_header_mode',
			array(
				'default'           => 'classic',
				'sanitize_callback' => array( $this, 'sanitize_mode' ),
			)
		);

		$wp_customize->add_control(
			'cbv_header_mode',
			array(
				'label'   => __( 'Header layout', 'commercebuild-velocity' ),
				'section' => 'cbv_header',
				'type'    => 'radio',
				'choices' => array(
					'classic' => __( 'Built-in header (logo, search, account, cart, menu)', 'commercebuild-velocity' ),
					'blocks'  => __( 'Build my own with blocks', 'commercebuild-velocity' ),
				),
			)
		);
	}

	/**
	 * Sanitize the header mode setting.
	 *
	 * @since 0.4.0
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	public function sanitize_mode( $value ) {
		return ( 'blocks' === $value ) ? 'blocks' : 'classic';
	}

	/**
	 * Block markup that reproduces the built-in header, used to seed the
	 * Header Area document the first time block mode is enabled so customers
	 * start from the current design instead of a blank canvas.
	 *
	 * @since 0.4.0
	 *
	 * @return string
	 */
	public static function default_header_blocks() {
		$markup = '<!-- wp:group {"className":"cbv-header__inner","layout":{"type":"flex","flexWrap":"wrap","verticalAlignment":"center","justifyContent":"space-between"}} -->'
			. '<div class="wp-block-group cbv-header__inner">'
			. '<!-- wp:site-logo {"width":240} /-->'
			. '<!-- wp:commercebuild/header-search /-->'
			. '<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"}} --><div class="wp-block-group">'
			. '<!-- wp:commercebuild/header-account /-->'
			. '<!-- wp:commercebuild/header-cart /-->'
			. '</div><!-- /wp:group -->'
			. '</div><!-- /wp:group -->'
			. '<!-- wp:commercebuild/header-nav {"location":"primary"} /-->';

		/**
		 * Filter the seed markup for a block-built header.
		 *
		 * @since 0.4.0
		 *
		 * @param string $markup Serialized block markup.
		 */
		return apply_filters( 'cbv_default_header_blocks', $markup );
	}

	/**
	 * Register the block area post type.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function register_post_type() {
		register_post_type(
			'cbv_area',
			array(
				'labels'          => array(
					'name'          => __( 'Block Areas', 'commercebuild-velocity' ),
					'singular_name' => __( 'Block Area', 'commercebuild-velocity' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'themes.php',
				'show_in_rest'    => true,
				'supports'        => array( 'title', 'editor' ),
				'capability_type' => 'page',
				'map_meta_cap'    => true,
			)
		);
	}

	/**
	 * Create the header/footer area documents once.
	 *
	 * @since 0.2.0
	 *
	 * @return void
	 */
	public function ensure_areas() {
		foreach ( self::AREAS as $slug => $title ) {
			$option  = 'cbv_area_' . $slug;
			$post_id = (int) get_option( $option );

			if ( $post_id && 'cbv_area' === get_post_type( $post_id ) && 'trash' !== get_post_status( $post_id ) ) {
				continue;
			}

			$post_id = wp_insert_post(
				array(
					'post_type'   => 'cbv_area',
					'post_status' => 'publish',
					'post_title'  => $title,
				)
			);

			if ( $post_id && ! is_wp_error( $post_id ) ) {
				update_option( $option, $post_id, true );
			}
		}

		$this->seed_header_blocks();
	}

	/**
	 * Seed the Header Area document with the built-in header layout the first
	 * time block mode is enabled, so customers start from the current design.
	 *
	 * @since 0.4.0
	 *
	 * @return void
	 */
	private function seed_header_blocks() {
		if ( 'blocks' !== self::header_mode() ) {
			return;
		}

		$post_id = (int) get_option( 'cbv_area_header' );

		if ( ! $post_id ) {
			return;
		}

		$post = get_post( $post_id );

		if ( ! $post || '' !== trim( (string) $post->post_content ) ) {
			return;
		}

		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => self::default_header_blocks(),
			)
		);

		// Marks the content as theme-seeded chrome so classic mode knows not
		// to render it alongside the PHP header.
		update_post_meta( $post_id, '_cbv_seeded_header', 1 );
	}

	/**
	 * Render a block area.
	 *
	 * @since 0.2.0
	 *
	 * @param string $slug Area slug (header|footer).
	 * @return void
	 */
	public static function render( $slug ) {
		/**
		 * Filter the post ID rendered for a block area (per-page overrides).
		 *
		 * @since 0.3.0
		 *
		 * @param int    $post_id Area post ID.
		 * @param string $slug    Area slug.
		 */
		$post_id = (int) apply_filters( 'cbv_block_area_post_id', (int) get_option( 'cbv_area_' . sanitize_key( $slug ) ), $slug );

		if ( ! $post_id ) {
			return;
		}

		$post = get_post( $post_id );

		if ( ! $post || 'publish' !== $post->post_status || '' === trim( $post->post_content ) ) {
			return;
		}

		$slug = sanitize_key( $slug );

		if ( 'header' === $slug && 'classic' === self::header_mode() && get_post_meta( $post_id, '_cbv_seeded_header', true ) ) {
			// Seeded chrome duplicates the classic PHP header, so it renders
			// only while block mode is active.
			return;
		}

		// A block-built header owns the full header; skip the inner container
		// so full-width blocks can span the viewport.
		$constrain = ! ( 'header' === $slug && 'blocks' === self::header_mode() );

		printf(
			'<div class="cbv-%1$s__area">%2$s%3$s%4$s</div>',
			esc_attr( $slug ),
			$constrain ? '<div class="cbv-container">' : '',
			do_blocks( $post->post_content ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Block output, escaped at render.
			$constrain ? '</div>' : ''
		);
	}
}
