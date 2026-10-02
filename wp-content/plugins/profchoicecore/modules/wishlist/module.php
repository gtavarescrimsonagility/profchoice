<?php
/**
 * Module Name: Wishlist
 * Description: Hearts that save products to lists (default list plus lists the visitor creates), a wishlist page and My Account > Wishlists, a header link with a counter, for customers and optionally guests.
 * Requires Plugins: woocommerce
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore\Wishlist;

use ProfChoiceCore\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/Settings.php';
require_once __DIR__ . '/includes/Lists.php';
require_once __DIR__ . '/includes/Rest.php';
require_once __DIR__ . '/includes/Wishlist.php';
require_once __DIR__ . '/includes/Account.php';
require_once __DIR__ . '/includes/Admin.php';

Settings::register_hooks();
Lists::register_hooks();
Rest::register_hooks();
Wishlist::register_hooks();
Account::register_hooks();
Admin::register_hooks();
Blocks::register_collection( __DIR__ );
