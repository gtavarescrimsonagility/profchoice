<?php
/**
 * Plugin bootstrap.
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore;

/**
 * Wires the core and the module loader. Features live in modules/.
 */
final class Plugin {

	/**
	 * Register all hooks.
	 *
	 * @return void
	 */
	public static function boot() {
		Blocks::register_hooks();
		Modules::register_hooks();
	}
}
