<?php
/**
 * Block registration.
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore;

/**
 * The block category, and block registration for modules.
 */
final class Blocks {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
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
	 * Register the blocks a module built into its build/ folder (through the
	 * blocks manifest `wp-scripts build --blocks-manifest` generates).
	 *
	 * @param string $module_dir Module folder.
	 * @return void
	 */
	public static function register_collection( $module_dir ) {
		add_action(
			'init',
			static function () use ( $module_dir ) {
				$build    = $module_dir . '/build';
				$manifest = $build . '/blocks-manifest.php';
				if ( file_exists( $manifest ) ) {
					wp_register_block_types_from_metadata_collection( $build, $manifest );
				}
			}
		);
	}
}
