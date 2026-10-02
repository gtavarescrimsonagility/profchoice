<?php
/**
 * Module Name: Product Page
 * Description: Product badge and "As ridden by" card, edited in the product's Product page tab.
 * Requires Plugins: woocommerce
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/ProductMeta.php';

ProductMeta::register_hooks();
