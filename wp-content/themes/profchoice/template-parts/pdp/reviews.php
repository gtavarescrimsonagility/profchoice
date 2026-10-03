<?php
/**
 * PDP reviews (profchoicecore profchoice/reviews block), after the related
 * products. Collapsed: the "N reviews" link (#reviews) opens it.
 *
 * @package profchoice
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

if ( ! class_exists( 'ProfChoiceCore\Reviews\Reviews' ) ) {
	return;
}

echo ProfChoiceCore\Reviews\Reviews::render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	array(
		'productId' => $product->get_id(),
		'collapsed' => true,
	)
);
