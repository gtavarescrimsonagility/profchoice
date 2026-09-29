<?php
/**
 * Inline SVG icon system.
 *
 * A small registry of outline-style icons (24x24, stroke = currentColor) so
 * header components and templates get modern iconography without icon fonts
 * or external requests. Child themes add or replace icons via `cbv_icons`
 * and post-process any single render via `cbv_icon_svg`.
 *
 * @package Commercebuild_Velocity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Icon registry and renderer.
 *
 * @since 0.11.0
 */
class CBV_Icons {

	/**
	 * Icon path data keyed by slug.
	 *
	 * Each entry is the inner markup of a 24x24 viewBox SVG drawn with
	 * stroke="currentColor", so icons inherit text color.
	 *
	 * @since 0.11.0
	 *
	 * @return array
	 */
	public static function registry() {
		/**
		 * Filter the icon registry.
		 *
		 * @since 0.11.0
		 *
		 * @param array $icons slug => inner SVG markup (24x24 viewBox).
		 */
		return apply_filters(
			'cbv_icons',
			array(
				'bag'           => '<path d="M6 7h12l1.2 13.2a1 1 0 0 1-1 1.1H5.8a1 1 0 0 1-1-1.1L6 7Z"/><path d="M9 10V6a3 3 0 0 1 6 0v4"/>',
				'cart'          => '<circle cx="9" cy="20" r="1.6"/><circle cx="18" cy="20" r="1.6"/><path d="M2.5 3.5h2l2.6 12.2a1 1 0 0 0 1 .8h9.6a1 1 0 0 0 1-.8l1.8-8.2H6"/>',
				'basket'        => '<path d="M4.5 10h15l-1.4 9.1a1.5 1.5 0 0 1-1.5 1.4H7.4a1.5 1.5 0 0 1-1.5-1.4L4.5 10Z"/><path d="m8 10 3.2-6M16 10l-3.2-6"/><path d="M3 10h18"/>',
				'user'          => '<circle cx="12" cy="8" r="3.6"/><path d="M5 20.2c.9-3.5 3.7-5.4 7-5.4s6.1 1.9 7 5.4"/>',
				'search'        => '<circle cx="11" cy="11" r="6.5"/><path d="m20.5 20.5-4.9-4.9"/>',
				'heart'         => '<path d="M12 20.3 5.2 13.5a4.6 4.6 0 0 1 6.5-6.5l.3.3.3-.3a4.6 4.6 0 1 1 6.5 6.5L12 20.3Z"/>',
				'menu'          => '<path d="M4 6.5h16M4 12h16M4 17.5h16"/>',
				'close'         => '<path d="m6 6 12 12M18 6 6 18"/>',
				'chevron_down'  => '<path d="m6.5 9.5 5.5 5.5 5.5-5.5"/>',
				'chevron_left'  => '<path d="m14.5 6.5-5.5 5.5 5.5 5.5"/>',
				'chevron_right' => '<path d="m9.5 6.5 5.5 5.5-5.5 5.5"/>',
				'filter'        => '<path d="M4 6h16M7 12h10M10 18h4"/>',
				'grid'          => '<rect x="4" y="4" width="6" height="6" rx="1"/><rect x="14" y="4" width="6" height="6" rx="1"/><rect x="4" y="14" width="6" height="6" rx="1"/><rect x="14" y="14" width="6" height="6" rx="1"/>',
				'list'          => '<path d="M9 6h11M9 12h11M9 18h11"/><circle cx="5" cy="6" r="1"/><circle cx="5" cy="12" r="1"/><circle cx="5" cy="18" r="1"/>',
				'pause'         => '<path d="M9 7v10M15 7v10"/>',
				'play'          => '<path d="m9 7 8 5-8 5Z"/>',
				'pin'           => '<path d="M12 21s7-5.4 7-11a7 7 0 1 0-14 0c0 5.6 7 11 7 11Z"/><circle cx="12" cy="10" r="2.2"/>',
				'phone'         => '<path d="M7.2 3.8h3.1l1.2 3.1-2 1.2a12.5 12.5 0 0 0 6.4 6.4l1.2-2 3.1 1.2v3.1c0 .9-.7 1.7-1.6 1.8C9.8 19.2 4.8 14.2 4 7.4c-.1-.9.7-1.6 1.6-1.6Z"/>',
				'mail'          => '<path d="M4 6.5h16v11H4Z"/><path d="m4 7 8 6 8-6"/>',
				'compare'       => '<rect x="8" y="4" width="10" height="12" rx="1.5"/><rect x="4" y="8" width="10" height="12" rx="1.5"/>',
				'truck'         => '<path d="M3 7h11v9H3Z"/><path d="M14 10h4.2L21 13.2V16h-7"/><circle cx="7" cy="17.5" r="1.6"/><circle cx="17" cy="17.5" r="1.6"/>',
				'star'          => '<path d="m12 3.5 2.5 5.1 5.6.8-4 4 1 5.6L12 16.6 6.9 19l1-5.6-4-4 5.6-.8Z"/>',
				'check'         => '<path d="m5 12 5 5 9-10"/>',
				'clock'         => '<circle cx="12" cy="12" r="8.5"/><path d="M12 8v4.5l3 1.8"/>',
				'building'      => '<path d="M6 20V8l6-3.5L18 8v12"/><path d="M10 20v-5h4v5"/><path d="M9 11h.01M12 11h.01M15 11h.01M9 14h.01M15 14h.01"/>',
				'package'       => '<path d="M4.5 8 12 4l7.5 4v8L12 20l-7.5-4Z"/><path d="M4.5 8 12 12l7.5-4M12 12v8"/>',
				'history'       => '<path d="M4.5 12a7.5 7.5 0 1 0 2-5.2"/><path d="M4.5 5.5v4h4"/><path d="M12 8v5l3 2"/>',
				'book'          => '<path d="M5 5.5h10.5A3.5 3.5 0 0 1 19 9v10H8.5A3.5 3.5 0 0 0 5 22.5Z"/><path d="M5 5.5v17"/>',
				'reorder'       => '<path d="M4 7h10M4 12h16M4 17h10"/><path d="m16 14 4 3-4 3"/>',
			)
		);
	}

	/**
	 * Render an icon.
	 *
	 * @since 0.11.0
	 *
	 * @param string $name Icon slug from the registry.
	 * @param array  $args {
	 *     Optional rendering arguments.
	 *
	 *     @type int    $size         Rendered square size in px. Default 24.
	 *     @type string $class        Extra CSS classes appended to `cbv-icon`.
	 *     @type float  $stroke_width Stroke width in px. Default 2.
	 * }
	 * @return string SVG markup, empty string for unknown icons.
	 */
	public static function get( $name, $args = array() ) {
		$icons = self::registry();

		if ( empty( $icons[ $name ] ) ) {
			return '';
		}

		$args = wp_parse_args(
			$args,
			array(
				'size'         => 24,
				'class'        => '',
				'stroke_width' => 2,
			)
		);

		$class        = trim( 'cbv-icon cbv-icon--' . $name . ' ' . $args['class'] );
		$stroke_width = min( 3, max( 1, (float) $args['stroke_width'] ) );

		$svg = sprintf(
			'<svg class="%1$s" xmlns="http://www.w3.org/2000/svg" width="%2$d" height="%2$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="%3$s" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%4$s</svg>',
			esc_attr( $class ),
			absint( $args['size'] ),
			esc_attr( (string) $stroke_width ),
			$icons[ $name ]
		);

		/**
		 * Filter a rendered icon's SVG markup.
		 *
		 * @since 0.11.0
		 *
		 * @param string $svg  SVG markup.
		 * @param string $name Icon slug.
		 * @param array  $args Rendering arguments.
		 */
		return apply_filters( 'cbv_icon_svg', $svg, $name, $args );
	}

	/**
	 * Allowed SVG markup for `wp_kses` when echoing icons.
	 *
	 * @since 0.11.0
	 *
	 * @return array
	 */
	public static function kses_allowed() {
		return array(
			'svg'    => array(
				'class'           => true,
				'xmlns'           => true,
				'width'           => true,
				'height'          => true,
				'viewbox'         => true,
				'fill'            => true,
				'stroke'          => true,
				'stroke-width'    => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
				'aria-hidden'     => true,
				'focusable'       => true,
			),
			'path'   => array( 'd' => true ),
			'circle' => array(
				'cx' => true,
				'cy' => true,
				'r'  => true,
			),
			'rect'   => array(
				'x'      => true,
				'y'      => true,
				'width'  => true,
				'height' => true,
				'rx'     => true,
			),
			'line'   => array(
				'x1' => true,
				'y1' => true,
				'x2' => true,
				'y2' => true,
			),
		);
	}
}
