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
