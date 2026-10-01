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
		// After the theme's category (commercebuild, priority 10), so ours is first.
		add_filter( 'block_categories_all', array( __CLASS__, 'add_category' ), 20 );
	}

	/**
	 * Adds the "Professional's Choice" block category, first in the inserter.
	 *
	 * @param array $categories Block categories.
	 * @return array
	 */
	public static function add_category( $categories ) {
		$categories = array_filter(
			(array) $categories,
			static function ( $category ) {
				return 'profchoice' !== $category['slug'];
			}
		);
		return array_merge(
			array(
				array(
					'slug'  => 'profchoice',
					'title' => __( "Professional's Choice", 'profchoicecore' ),
					'icon'  => null,
				),
			),
			array_values( $categories )
		);
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

		$arrow_sizes = array(
			'dimensions' => array(
				'dimensionSizes' => $sizes( array( '32px', '40px', '48px', '56px', '64px' ) ),
			),
		);

		return $theme_json->update_with(
			array(
				'version'  => 3,
				'settings' => array(
					'blocks' => array(
						'profchoice/carousel-indicators'     => array(
							'dimensions' => array(
								'dimensionSizes' => $sizes( array( '6px', '8px', '10px', '12px', '16px', '22px', '32px' ) ),
							),
						),
						'profchoice/carousel-control-prev' => $arrow_sizes,
						'profchoice/carousel-control-next' => $arrow_sizes,
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
