<?php
/**
 * Module Name: Product Page
 * Description: Product badges (Sale, New, Pro Pick) as product tags kept in sync, and the "As ridden by" card in the product's Product page tab.
 * Requires Plugins: woocommerce
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/ProductMeta.php';
require_once __DIR__ . '/includes/Badges.php';

ProductMeta::register_hooks();
Badges::register_hooks();
