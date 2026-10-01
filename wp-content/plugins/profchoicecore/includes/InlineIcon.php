<?php
/**
 * Inline icon rich text format: registers its editor script and styles, draws
 * icons in the editor with a generated mask stylesheet, and renders the SVG
 * on the front end.
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The `profchoice/inline-icon` format (src/inline-icon).
 */
final class InlineIcon {

	const HANDLE = 'profchoicecore-inline-icon';

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
		$build = PROFCHOICECORE_DIR . 'build/inline-icon/';
		$url   = plugins_url( 'build/inline-icon/', PROFCHOICECORE_FILE );
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
	 * Editor (admin document): the format, plus styles for the icon picker.
	 *
	 * @return void
	 */
	public static function enqueue_editor() {
		wp_enqueue_script( self::HANDLE );
		self::enqueue_masks();
	}

	/**
	 * Editor canvas (iframe): the icons inside the content.
	 *
	 * @return void
	 */
	public static function enqueue_editor_canvas() {
		if ( is_admin() ) {
			self::enqueue_masks();
		}
	}

	/**
	 * The stylesheet plus one mask per registered icon, so an inline icon
	 * (`[data-icon]`) shows its icon in the editor.
	 *
	 * @return void
	 */
	private static function enqueue_masks() {
		if ( ! class_exists( '\WP_Icons_Registry' ) || wp_style_is( self::HANDLE, 'enqueued' ) ) {
			return;
		}
		$css = '';
		foreach ( \WP_Icons_Registry::get_instance()->get_registered_icons() as $icon ) {
			if ( empty( $icon['name'] ) || empty( $icon['content'] ) ) {
				continue;
			}
			// Intrinsic size of the 24px icon grid; the mask scales it to 1em.
			$svg = new \WP_HTML_Tag_Processor( trim( (string) $icon['content'] ) );
			if ( $svg->next_tag( 'svg' ) ) {
				$svg->set_attribute( 'width', '24' );
				$svg->set_attribute( 'height', '24' );
			}
			$css .= sprintf(
				'[data-icon="%s"]{--pc-inline-icon:url("data:image/svg+xml,%s")}',
				esc_attr( $icon['name'] ),
				rawurlencode( $svg->get_updated_html() )
			);
		}
		wp_enqueue_style( self::HANDLE );
		wp_add_inline_style( self::HANDLE, $css );
	}

	/**
	 * Replaces saved inline icons (`<img class="pc-inline-icon" data-icon>`)
	 * with the icon's `<svg class="pc-inline-icon">`, so it follows the text
	 * color (currentColor).
	 *
	 * @param string $content Rendered block.
	 * @return string
	 */
	public static function render( $content ) {
		if ( false === strpos( $content, 'pc-inline-icon' ) || ! function_exists( 'wp_get_icon' ) ) {
			return $content;
		}
		$found   = false;
		$updated = preg_replace_callback(
			'#<img\b[^>]*\bclass="pc-inline-icon"[^>]*>#',
			static function ( $matches ) use ( &$found ) {
				if ( ! preg_match( '#\bdata-icon="([a-z0-9_-]+/[a-z0-9_-]+)"#', $matches[0], $icon ) ) {
					return $matches[0];
				}
				$svg = wp_get_icon(
					$icon[1],
					array(
						'size'  => null,
						'class' => 'pc-inline-icon',
					)
				);
				if ( '' === $svg ) {
					return $matches[0];
				}
				$found = true;
				return trim( $svg );
			},
			$content
		);
		if ( $found ) {
			wp_enqueue_style( self::HANDLE );
		}
		return is_string( $updated ) ? $updated : $content;
	}
}
