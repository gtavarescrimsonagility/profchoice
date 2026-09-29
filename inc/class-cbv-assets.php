<?php
/**
 * Asset loading.
 *
 * One small stylesheet, one deferred script, palette variables inlined.
 * No jQuery dependency, no icon fonts, no render-blocking third parties.
 *
 * @package Commercebuild_Velocity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lean asset strategy.
 *
 * @since 0.1.0
 */
class CBV_Assets {

	/**
	 * Register hooks.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_filter( 'script_loader_tag', array( $this, 'defer_scripts' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'trim_emoji' ), 1 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor' ) );
	}

	/**
	 * Load the frontend styles in the editor so server-rendered block
	 * previews match the site.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function enqueue_editor() {
		wp_enqueue_style(
			'cbv-main',
			CBV_URI . 'assets/css/main.css',
			array(),
			CBV_VERSION
		);
	}

	/**
	 * Enqueue theme CSS/JS.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function enqueue() {
		wp_enqueue_style(
			'cbv-main',
			CBV_URI . 'assets/css/main.css',
			array(),
			CBV_VERSION
		);

		wp_enqueue_script(
			'cbv-main',
			CBV_URI . 'assets/js/main.js',
			array(),
			CBV_VERSION,
			true
		);
	}

	/**
	 * Defer the theme script.
	 *
	 * @since 0.1.0
	 *
	 * @param string $tag    Script tag HTML.
	 * @param string $handle Script handle.
	 * @return string
	 */
	public function defer_scripts( $tag, $handle ) {
		if ( 'cbv-main' === $handle && false === strpos( $tag, 'defer' ) ) {
			$tag = str_replace( ' src=', ' defer src=', $tag );
		}

		return $tag;
	}

	/**
	 * Drop the emoji polyfill on the frontend.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function trim_emoji() {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
	}
}
