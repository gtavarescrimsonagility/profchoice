<?php
/**
 * Professional's Choice icon collection, registered with the Icons API so its
 * icons show up in the Icon block (e.g. "profchoice/play").
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the `profchoice` icon collection from icons/manifest.php.
 */
final class Icons {

	const COLLECTION = 'profchoice';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		// After core registers its collections (init, priority 0).
		add_action( 'init', array( __CLASS__, 'register' ), 5 );
	}

	/**
	 * Registers the collection and its icons.
	 *
	 * @return void
	 */
	public static function register() {
		if ( ! function_exists( 'wp_register_icon_collection' ) ) {
			return;
		}

		wp_register_icon_collection(
			self::COLLECTION,
			array(
				'label'       => __( "Professional's Choice", 'profchoicecore' ),
				'description' => __( 'Icons from the Professional\'s Choice Core plugin.', 'profchoicecore' ),
			)
		);

		$directory = dirname( __DIR__ ) . '/icons/';
		$manifest  = include $directory . 'manifest.php';
		foreach ( (array) $manifest as $name => $icon ) {
			wp_register_icon(
				self::COLLECTION . '/' . $name,
				array(
					'label'     => $icon['label'],
					'file_path' => $directory . $icon['filePath'],
				)
			);
		}
	}
}
