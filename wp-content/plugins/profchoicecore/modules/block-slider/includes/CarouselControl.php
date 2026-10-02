<?php
/**
 * Carousel Previous and Next blocks: bind the inner Button block to the
 * carousel store.
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared render of profchoice/carousel-control-prev and -next.
 */
final class CarouselControl {

	/**
	 * Store action and disabled binding per direction.
	 *
	 * @var array<string, string[]>
	 */
	const DIRECTIONS = array(
		'prev' => array( 'actions.prev', '!state.canPrev' ),
		'next' => array( 'actions.next', '!state.canNext' ),
	);

	/**
	 * Render a control.
	 *
	 * @param string $direction  "prev" or "next".
	 * @param array  $attributes Block attributes.
	 * @param string $content    Inner blocks HTML.
	 * @return string
	 */
	public static function render( $direction, array $attributes, $content ) {
		if ( ! isset( self::DIRECTIONS[ $direction ] ) ) {
			return '';
		}
		list( $action, $disabled ) = self::DIRECTIONS[ $direction ];

		$button = new \WP_HTML_Tag_Processor( $content );
		while ( $button->next_tag() ) {
			if ( in_array( $button->get_tag(), array( 'BUTTON', 'A' ), true ) ) {
				$button->set_attribute( 'data-wp-on--click', $action );
				$button->set_attribute( 'data-wp-bind--disabled', $disabled );
				break;
			}
		}

		// Active/Inactive control settings; empty values leave the button's own styles.
		$states = StateStyle::props(
			'carousel-arrow',
			array(
				'active'   => $attributes['active'] ?? array(),
				'inactive' => $attributes['inactive'] ?? array(),
			)
		);

		$wrapper = get_block_wrapper_attributes(
			array(
				'class' => trim( 'pc-carousel__control' . ( ! empty( $attributes['overlay'] ) ? ' is-overlay ' : ' ' ) . $states['class'] ),
			)
		);

		return StateStyle::apply(
			sprintf( '<div %s>%s</div>', $wrapper, $button->get_updated_html() ),
			$states['style']
		);
	}
}
