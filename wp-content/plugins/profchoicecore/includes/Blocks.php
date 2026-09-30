<?php
/**
 * Block registration.
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore;

/**
 * Registers every block found in build/ through the blocks manifest that
 * `wp-scripts build --blocks-manifest` generates.
 */
final class Blocks {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_filter( 'wp_theme_json_data_default', array( __CLASS__, 'default_settings' ) );
	}

	/**
	 * Default per-block settings, like core's theme.json does for core/button:
	 * the width/height presets of the dot and arrow state panels. Themes can
	 * override them under settings.blocks in their theme.json.
	 *
	 * @param \WP_Theme_JSON_Data $theme_json Core default theme.json data.
	 * @return \WP_Theme_JSON_Data
	 */
	public static function default_settings( $theme_json ) {
		$sizes = static function ( array $values ) {
			return array_map(
				static function ( $size ) {
					return array(
						'name' => $size,
						'slug' => (string) (float) $size,
						'size' => $size,
					);
				},
				$values
			);
		};

		return $theme_json->update_with(
			array(
				'version'  => 3,
				'settings' => array(
					'blocks' => array(
						'profchoice/carousel-dots'     => array(
							'dimensions' => array(
								'dimensionSizes' => $sizes( array( '6px', '8px', '10px', '12px', '16px', '22px', '32px' ) ),
							),
						),
						'profchoice/carousel-controls' => array(
							'dimensions' => array(
								'dimensionSizes' => $sizes( array( '32px', '40px', '48px', '56px', '64px' ) ),
							),
						),
					),
				),
			)
		);
	}

	/**
	 * Register the block types from build/blocks-manifest.php.
	 *
	 * @return void
	 */
	public static function register() {
		$build    = PROFCHOICECORE_DIR . 'build';
		$manifest = $build . '/blocks-manifest.php';

		if ( ! file_exists( $manifest ) ) {
			return;
		}

		wp_register_block_types_from_metadata_collection( $build, $manifest );
	}
}
