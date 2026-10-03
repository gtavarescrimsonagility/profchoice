<?php
/**
 * Module Name: Image Dimensions
 * Description: Content images always get width and height: from the file when the attachment has no size metadata (e.g. media imported without it), so lazy-loaded images never shift the layout.
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/ImageDimensions.php';

ImageDimensions::register_hooks();
