<?php
/**
 * Module Name: Reviews
 * Description: Product reviews block (profchoice/reviews): summary, reviews slider loaded page by page from the Store API, and a Write a Review form, with a collapsed mode opened by #reviews links. Shortcode and action render the block.
 * Requires Plugins: woocommerce
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore\Reviews;

use ProfChoiceCore\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/Reviews.php';

Reviews::register_hooks();
Blocks::register_collection( __DIR__ );
