<?php
/**
 * Video playback for core/cover: Autoplay, Loop and Muted settings, a "Play"
 * button style, and a Play/Pause button bound to the
 * `profchoice/cover-video` Interactivity store.
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extends core/cover with video playback settings.
 */
final class CoverVideo {

	const HANDLE = 'profchoicecore-cover-video';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_filter( 'register_block_type_args', array( __CLASS__, 'add_attributes' ), 10, 2 );
		add_action( 'init', array( __CLASS__, 'register_assets' ) );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_editor' ) );
		add_filter( 'render_block_core/cover', array( __CLASS__, 'render' ), 10, 2 );
	}

	/**
	 * Adds the playback attributes to core/cover (mirrored in the editor script).
	 *
	 * @param array  $args       Block type arguments.
	 * @param string $block_type Block name.
	 * @return array
	 */
	public static function add_attributes( $args, $block_type ) {
		if ( 'core/cover' === $block_type ) {
			$args['attributes'] = array_merge(
				isset( $args['attributes'] ) ? $args['attributes'] : array(),
				array(
					'autoplay' => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'loop'     => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'muted'    => array(
						'type'    => 'boolean',
						'default' => true,
					),
				)
			);
		}
		return $args;
	}

	/**
	 * Registers the editor script, the button style and its stylesheet, and the
	 * view script module.
	 *
	 * @return void
	 */
	public static function register_assets() {
		$build = dirname( __DIR__ ) . '/build/cover-extension/';
		$url   = plugins_url( 'build/cover-extension/', dirname( __DIR__ ) . '/module.php' );
		if ( ! file_exists( $build . 'index.asset.php' ) ) {
			return;
		}

		$editor = require $build . 'index.asset.php';
		wp_register_script( self::HANDLE . '-editor', $url . 'index.js', $editor['dependencies'], $editor['version'], true );
		wp_set_script_translations( self::HANDLE . '-editor', 'profchoicecore' );

		wp_register_style( self::HANDLE, $url . 'style-index.css', array(), $editor['version'] );
		wp_style_add_data( self::HANDLE, 'path', $build . 'style-index.css' );
		// Loaded with core/cover, on the front end and in the editor canvas.
		wp_enqueue_block_style(
			'core/cover',
			array(
				'handle' => self::HANDLE,
				'src'    => $url . 'style-index.css',
				'path'   => $build . 'style-index.css',
				'ver'    => $editor['version'],
			)
		);

		// The Play style only sets the shape (a circle: equal padding, 50%
		// radius; style.scss adds a 1:1 aspect ratio and the icon). Colors,
		// border and shadow, per state (Hover/Focus/Active), come from the
		// Button's own settings, like any button.
		register_block_style(
			'core/button',
			array(
				'name'       => 'play',
				'label'      => __( 'Play', 'profchoicecore' ),
				'style_data' => array(
					'border'  => array(
						'radius' => '50%',
					),
					'spacing' => array(
						'padding' => array(
							'top'    => '29px',
							'right'  => '29px',
							'bottom' => '29px',
							'left'   => '29px',
						),
					),
				),
			)
		);

		if ( file_exists( $build . 'view.asset.php' ) ) {
			$view = require $build . 'view.asset.php';
			wp_register_script_module( self::HANDLE, $url . 'view.js', $view['dependencies'], $view['version'] );
		}
	}

	/**
	 * Enqueues the editor script.
	 *
	 * @return void
	 */
	public static function enqueue_editor() {
		wp_enqueue_script( self::HANDLE . '-editor' );
	}

	/**
	 * Applies the playback settings to a video cover and binds its Play button
	 * (a Button with the Play style) to the store.
	 *
	 * @param string $content Rendered block.
	 * @param array  $block   Parsed block.
	 * @return string
	 */
	public static function render( $content, $block ) {
		$attributes = isset( $block['attrs'] ) ? $block['attrs'] : array();
		if ( ! isset( $attributes['backgroundType'] ) || 'video' !== $attributes['backgroundType'] ) {
			return $content;
		}

		$autoplay = ! isset( $attributes['autoplay'] ) || ! empty( $attributes['autoplay'] );
		$loop     = ! isset( $attributes['loop'] ) || ! empty( $attributes['loop'] );
		// Browsers only autoplay muted video.
		$muted = $autoplay || ! isset( $attributes['muted'] ) || ! empty( $attributes['muted'] );

		$tags = new \WP_HTML_Tag_Processor( $content );
		if ( ! $tags->next_tag() ) {
			return $content;
		}

		$tags->set_attribute( 'data-wp-interactive', 'profchoice/cover-video' );
		$tags->set_attribute(
			'data-wp-context',
			wp_json_encode(
				array(
					'isPlaying'  => $autoplay,
					'playLabel'  => __( 'Play video', 'profchoicecore' ),
					'pauseLabel' => __( 'Pause video', 'profchoicecore' ),
				),
				JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
			)
		);
		$tags->set_attribute( 'data-wp-class--is-playing', 'context.isPlaying' );
		$tags->set_attribute( 'data-wp-init', 'callbacks.init' );
		// Focusable from script (not a Tab stop): the Play button hands focus
		// to the cover so Space then pauses the video.
		$tags->set_attribute( 'tabindex', '-1' );
		if ( $autoplay ) {
			$tags->add_class( 'is-playing' );
		}

		$in_play_button = false;
		while ( $tags->next_tag() ) {
			if ( 'VIDEO' === $tags->get_tag() && $tags->has_class( 'wp-block-cover__video-background' ) ) {
				foreach ( array(
					'autoplay' => $autoplay,
					'loop'     => $loop,
					'muted'    => $muted,
				) as $name => $enabled ) {
					if ( $enabled ) {
						$tags->set_attribute( $name, true );
					} else {
						$tags->remove_attribute( $name );
					}
				}
				continue;
			}

			// A Button with the Play style: bind its inner button or link.
			if ( $tags->has_class( 'is-style-play' ) ) {
				$in_play_button = true;
				continue;
			}


			if ( $in_play_button && in_array( $tags->get_tag(), array( 'BUTTON', 'A' ), true ) ) {
				$tags->set_attribute( 'data-wp-on--click', 'actions.toggle' );
				$tags->set_attribute( 'data-wp-bind--aria-pressed', 'context.isPlaying' );
				$tags->set_attribute( 'data-wp-bind--aria-label', 'state.playLabel' );
				$tags->set_attribute( 'aria-pressed', $autoplay ? 'true' : 'false' );
				$tags->set_attribute( 'aria-label', $autoplay ? __( 'Pause video', 'profchoicecore' ) : __( 'Play video', 'profchoicecore' ) );
				$in_play_button = false;
			}
		}

		wp_enqueue_script_module( self::HANDLE );
		wp_interactivity_state(
			'profchoice/cover-video',
			array(
				'playLabel' => static function () {
					$context = wp_interactivity_get_context( 'profchoice/cover-video' );
					return empty( $context['isPlaying'] ) ? $context['playLabel'] : $context['pauseLabel'];
				},
			)
		);

		return $tags->get_updated_html();
	}
}
