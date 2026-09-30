<?php
/**
 * Plugin bootstrap.
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore;

use ProfChoiceCore\Newsletter\Exporter;
use ProfChoiceCore\Newsletter\Subscribe;
use ProfChoiceCore\Newsletter\Subscribers;

/**
 * Wires every component's hooks.
 */
final class Plugin {

	/**
	 * Register all hooks.
	 *
	 * @return void
	 */
	public static function boot() {
		Blocks::register_hooks();
		Subscribers::register_hooks();
		Subscribe::register_hooks();
		Exporter::register_hooks();
	}
}
