<?php
/**
 * Module Name: WooCommerce Block Bindings
 * Description: Block bindings for product, product category and shop data (profchoice/product, profchoice/product-category, profchoice/shop).
 * Requires Plugins: woocommerce
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/Bindings.php';

Bindings::register_hooks();
