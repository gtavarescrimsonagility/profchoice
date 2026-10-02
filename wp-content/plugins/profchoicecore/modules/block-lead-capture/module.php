<?php
/**
 * Module Name: Lead Capture
 * Description: Newsletter block (email, messages), subscribers list with CSV export and optional email confirmation.
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore;

use ProfChoiceCore\Newsletter\Confirmation;
use ProfChoiceCore\Newsletter\Exporter;
use ProfChoiceCore\Newsletter\Subscribe;
use ProfChoiceCore\Newsletter\Subscribers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/Csv.php';
require_once __DIR__ . '/includes/Newsletter/Subscribers.php';
require_once __DIR__ . '/includes/Newsletter/Confirmation.php';
require_once __DIR__ . '/includes/Newsletter/Subscribe.php';
require_once __DIR__ . '/includes/Newsletter/Exporter.php';

Subscribers::register_hooks();
Confirmation::register_hooks();
Subscribe::register_hooks();
Exporter::register_hooks();
Blocks::register_collection( __DIR__ );
