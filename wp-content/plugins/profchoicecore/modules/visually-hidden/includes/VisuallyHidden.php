<?php
/**
 * Visually hidden rich text format (src/visually-hidden).
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the format's editor script and its stylesheet, loaded in the
 * editor and on pages whose blocks use it.
 */
final class VisuallyHidden {

	const HANDLE = 'profchoicecore-visually-hidden';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'init', array( __CLASS__, 'register_assets' ), 20 );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_editor' ) );
		add_action( 'enqueue_block_assets', array( __CLASS__, 'enqueue_editor_canvas' ) );
		add_filter( 'render_block', array( __CLASS__, 'render' ) );
	}

	/**
	 * Registers the editor script and the stylesheet.
	 *
	 * @return void
	 */
	public static function register_assets() {
		$build = dirname( __DIR__ ) . '/build/visually-hidden/';
		$url   = plugins_url( 'build/visually-hidden/', dirname( __DIR__ ) . '/module.php' );
		if ( ! file_exists( $build . 'index.asset.php' ) ) {
			return;
		}
		$asset = require $build . 'index.asset.php';
		wp_register_script( self::HANDLE, $url . 'index.js', $asset['dependencies'], $asset['version'], true );
		wp_set_script_translations( self::HANDLE, 'profchoicecore' );
		wp_register_style( self::HANDLE, $url . 'style-index.css', array(), $asset['version'] );
		// Small enough for core to print inline instead of a render-blocking link.
		wp_style_add_data( self::HANDLE, 'path', $build . 'style-index.css' );
	}

	/**
	 * Editor (admin document).
	 *
	 * @return void
	 */
	public static function enqueue_editor() {
		wp_enqueue_script( self::HANDLE );
	}

	/**
	 * Editor canvas (iframe).
	 *
	 * @return void
	 */
	public static function enqueue_editor_canvas() {
		if ( is_admin() ) {
			wp_enqueue_style( self::HANDLE );
		}
	}

	/**
	 * Loads the stylesheet on the front end when a block uses the format.
	 *
	 * @param string $content Rendered block.
	 * @return string
	 */
	public static function render( $content ) {
		if ( false !== strpos( $content, 'pc-visually-hidden' ) ) {
			wp_enqueue_style( self::HANDLE );
		}
		return $content;
	}
}
