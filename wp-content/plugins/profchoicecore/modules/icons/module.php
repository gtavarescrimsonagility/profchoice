<?php
/**
 * Module Name: Icons
 * Description: The "Professional's Choice" icon collection (Icons API) and the Inline icon text format.
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/Icons.php';
require_once __DIR__ . '/includes/InlineIcon.php';

Icons::register_hooks();
InlineIcon::register_hooks();
