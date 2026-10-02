<?php
/**
 * Module Name: Visually Hidden
 * Description: Rich text format that hides text on screen and keeps it for screen readers.
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/VisuallyHidden.php';

VisuallyHidden::register_hooks();
