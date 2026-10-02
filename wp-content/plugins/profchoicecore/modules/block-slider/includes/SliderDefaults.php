<?php
/**
 * Default theme.json settings of the slider blocks.
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore;

/**
 * Size presets and spacing defaults of the carousel indicator and arrow blocks.
 */
final class SliderDefaults {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_filter( 'wp_theme_json_data_default', array( __CLASS__, 'default_settings' ) );
	}

	/**
	 * Default per-block settings and styles, like core's theme.json does for
	 * core/button: the width/height presets of the indicator and arrow state
	 * panels, and the indicators' gap. Themes can override them under
	 * settings.blocks and styles.blocks in their theme.json.
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
						'profchoice/carousel-indicators'   => array(
							'dimensions' => array(
								'dimensionSizes' => $sizes( array( '3px', '6px', '8px', '10px', '12px', '16px', '22px', '30px', '32px' ) ),
							),
						),
						'profchoice/carousel-control-prev' => $arrow_sizes,
						'profchoice/carousel-control-next' => $arrow_sizes,
					),
				),
				// No gap: the indicators' own 3px inline margins space them, as
				// Bootstrap's; a Block spacing adds to that.
				'styles'   => array(
					'blocks' => array(
						'profchoice/carousel-indicators' => array(
							'spacing' => array( 'blockGap' => '0' ),
						),
					),
				),
			)
		);
	}
}
