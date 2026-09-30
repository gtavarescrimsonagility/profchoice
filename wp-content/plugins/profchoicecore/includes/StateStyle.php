<?php
/**
 * Per-state styles stored as block attributes (mirrors src/shared/state-style.ts).
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Turns per-state style attributes into CSS custom properties and classes.
 */
final class StateStyle {

	/**
	 * CSS custom properties and modifier classes for a set of states.
	 *
	 * Each set value becomes `--pc-{prefix}-{state}-{property}` plus a
	 * `has-{state}-{property}` class. Unlinked borders and radii use
	 * per-side/per-corner properties, e.g. `border-top-width`, `radius-top-left`.
	 *
	 * @param string $prefix Variable prefix, e.g. `carousel-dot`.
	 * @param array  $states Styles keyed by state name.
	 * @return array{style: string, class: string}
	 */
	public static function props( $prefix, array $states ) {
		$style   = '';
		$classes = array();
		foreach ( $states as $state => $value ) {
			$value  = is_array( $value ) ? $value : array();
			$flat   = array(
				'background' => $value['background'] ?? '',
				'color'      => $value['color'] ?? '',
				'width'      => $value['width'] ?? '',
				'height'     => $value['height'] ?? '',
				'opacity'    => $value['opacity'] ?? '',
			);

			// One border for every side, or one per side (unlinked).
			$border = isset( $value['border'] ) && is_array( $value['border'] ) ? $value['border'] : array();
			$split  = array_intersect_key( $border, array_flip( array( 'top', 'right', 'bottom', 'left' ) ) );
			if ( $split ) {
				foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
					$side_border = isset( $border[ $side ] ) && is_array( $border[ $side ] ) ? $border[ $side ] : array();
					foreach ( array( 'color', 'style', 'width' ) as $part ) {
						$flat[ "border-$side-$part" ] = $side_border[ $part ] ?? '';
					}
				}
			} else {
				foreach ( array( 'color', 'style', 'width' ) as $part ) {
					$flat[ "border-$part" ] = $border[ $part ] ?? '';
				}
			}

			// One radius for every corner, or one per corner (unlinked).
			$radius = $value['radius'] ?? '';
			if ( is_array( $radius ) ) {
				$corners = array(
					'topLeft'     => 'top-left',
					'topRight'    => 'top-right',
					'bottomRight' => 'bottom-right',
					'bottomLeft'  => 'bottom-left',
				);
				foreach ( $corners as $corner => $css ) {
					$flat[ "radius-$css" ] = $radius[ $corner ] ?? '';
				}
			} else {
				$flat['radius'] = $radius;
			}

			foreach ( $flat as $property => $css ) {
				$css = is_scalar( $css ) ? trim( (string) $css ) : '';
				if ( self::is_safe( $css ) ) {
					$style    .= sprintf( '--pc-%s-%s-%s:%s;', $prefix, sanitize_key( $state ), $property, $css );
					$classes[] = sprintf( 'has-%s-%s', sanitize_key( $state ), $property );
				}
			}
		}
		return array(
			'style' => $style,
			'class' => implode( ' ', $classes ),
		);
	}

	/**
	 * Prepends CSS to the style attribute of the first tag in `$html`.
	 *
	 * Used instead of passing the variables to get_block_wrapper_attributes(),
	 * whose safecss_filter_attr() drops values with functions such as rgba().
	 * Values are already checked by props().
	 *
	 * @param string $html HTML whose first tag is the block wrapper.
	 * @param string $css  Declarations from props().
	 * @return string
	 */
	public static function apply( $html, $css ) {
		if ( '' === $css ) {
			return $html;
		}
		$tags = new \WP_HTML_Tag_Processor( $html );
		if ( ! $tags->next_tag() ) {
			return $html;
		}
		$style = (string) $tags->get_attribute( 'style' );
		$tags->set_attribute( 'style', $css . $style );
		return $tags->get_updated_html();
	}

	/**
	 * Whether a value is non-empty and cannot break out of a style attribute.
	 *
	 * @param string $css CSS value.
	 * @return bool
	 */
	private static function is_safe( $css ) {
		return '' !== $css && ! preg_match( '/[;{}<>"\'\\\\]|url\s*\(|expression/i', $css );
	}
}
