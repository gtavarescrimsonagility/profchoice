<?php
/**
 * Module Name: Size Guide
 * Description: A page per attribute (Products > Attributes) shown as a Size Guide link next to its swatches, opened in a modal (profchoice/size-guide block, shortcode and action).
 * Requires Plugins: woocommerce
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore\SizeGuide;

use ProfChoiceCore\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/SizeGuide.php';
require_once __DIR__ . '/includes/Admin.php';

SizeGuide::register_hooks();
Admin::register_hooks();
Blocks::register_collection( __DIR__ );
