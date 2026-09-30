<?php
/**
 * Plugin Name:       Professional's Choice Core
 * Plugin URI:        https://github.com/gtavarescrimsonagility/profchoice
 * Description:       Blocks for Professional's Choice: newsletter form, carousel and cover video.
 * Version:           0.10.0
 * Requires at least: 7.1
 * Requires PHP:      7.4
 * Author:            Crimson Agility
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       profchoicecore
 *
 * @package ProfChoiceCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PROFCHOICECORE_VERSION', '0.10.0' );
define( 'PROFCHOICECORE_FILE', __FILE__ );
define( 'PROFCHOICECORE_DIR', plugin_dir_path( __FILE__ ) );

require_once PROFCHOICECORE_DIR . 'includes/Csv.php';
require_once PROFCHOICECORE_DIR . 'includes/StateStyle.php';
require_once PROFCHOICECORE_DIR . 'includes/Blocks.php';
require_once PROFCHOICECORE_DIR . 'includes/Newsletter/Subscribers.php';
require_once PROFCHOICECORE_DIR . 'includes/Newsletter/Subscribe.php';
require_once PROFCHOICECORE_DIR . 'includes/Newsletter/Exporter.php';
require_once PROFCHOICECORE_DIR . 'includes/Plugin.php';

ProfChoiceCore\Plugin::boot();
