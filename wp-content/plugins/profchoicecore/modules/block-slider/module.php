<?php
/**
 * Module Name: Slider
 * Description: Carousel block with Slide, Previous, Next and Indicators children (Embla, Interactivity API).
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/StateStyle.php';
require_once __DIR__ . '/includes/CarouselControl.php';
require_once __DIR__ . '/includes/SliderDefaults.php';

SliderDefaults::register_hooks();
Blocks::register_collection( __DIR__ );
