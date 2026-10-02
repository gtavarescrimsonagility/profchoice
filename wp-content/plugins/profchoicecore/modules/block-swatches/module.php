<?php
/**
 * Module Name: Variation Swatches
 * Description: Color, image and button swatches for variable products (profchoice/swatches block, classic form integration, shortcode, attribute types and product settings).
 * Requires Plugins: woocommerce
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/Swatches.php';
require_once __DIR__ . '/includes/SwatchesAdmin.php';

Swatches::register_hooks();
SwatchesAdmin::register_hooks();
Blocks::register_collection( __DIR__ );
