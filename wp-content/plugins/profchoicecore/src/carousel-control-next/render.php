<?php
/**
 * Carousel next-slide control (see ProfChoiceCore\CarouselControl).
 *
 * @package ProfChoiceCore
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner blocks HTML (core/buttons with one core/button).
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo ProfChoiceCore\CarouselControl::render( 'next', $attributes, $content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
