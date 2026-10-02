<?php
/**
 * Plugin Name:       Professional's Choice Core
 * Plugin URI:        https://github.com/gtavarescrimsonagility/profchoice
 * Description:       Blocks and editor tools for Professional's Choice: newsletter form, carousel, video covers, icons, inline text formats, WooCommerce block bindings and variation swatches.
 * Version:           0.2.0
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

define( 'PROFCHOICECORE_VERSION', '0.2.0' );
define( 'PROFCHOICECORE_FILE', __FILE__ );
define( 'PROFCHOICECORE_DIR', plugin_dir_path( __FILE__ ) );

require_once PROFCHOICECORE_DIR . 'includes/Blocks.php';
require_once PROFCHOICECORE_DIR . 'includes/Modules.php';
require_once PROFCHOICECORE_DIR . 'includes/Plugin.php';

ProfChoiceCore\Plugin::boot();
