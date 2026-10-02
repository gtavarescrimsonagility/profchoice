<?php
/**
 * Wishlist (profchoicecore wishlist module): hearts on the product page and
 * cards, and the header link next to My Account and Cart.
 *
 * @package profchoice
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A wishlist heart for a product: the profchoice/wishlist-button block, or
 * a static heart when the wishlist module is not there.
 *
 * @param int    $product_id Product ID.
 * @param string $class      Classes of the button (the design's).
 * @param string $label      Accessible label of the static fallback.
 * @return string
 */
function profchoice_wishlist_button( $product_id, $class, $label ) {
	if ( class_exists( 'ProfChoiceCore\Wishlist\Wishlist' ) ) {
		$html = ProfChoiceCore\Wishlist\Wishlist::render_button( $product_id, array( 'className' => $class ) );
		if ( '' !== trim( $html ) ) {
			return $html;
		}
	}
	return sprintf(
		'<button class="%1$s" type="button" aria-label="%2$s">%3$s</button>',
		esc_attr( $class ),
		esc_attr( $label ),
		profchoice_icon( 'heart' )
	);
}

/*
 * Header: the wishlist link with the parent's action classes, so it looks
 * like My Account and Cart (icon, count badge, label).
 */
add_action(
	'cbv_header_actions',
	function () {
		if ( ! class_exists( 'ProfChoiceCore\Wishlist\Wishlist' ) ) {
			return;
		}
		echo ProfChoiceCore\Wishlist\Wishlist::render_link( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			array(
				'className'    => 'cbv-header__action cbv-hblock__link cbv-header__wishlist',
				'innerClasses' => array(
					'icon'  => 'cbv-hblock__icon',
					'count' => 'cbv-header__cart-count cbv-header__cart-count--badge',
					'label' => 'cbv-hblock__label',
				),
			)
		);
	},
	5
);
