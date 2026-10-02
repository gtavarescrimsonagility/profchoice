<?php
/**
 * Plugin bootstrap.
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore;

use ProfChoiceCore\Newsletter\Confirmation;
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
		Bindings::register_hooks();
		CoverVideo::register_hooks();
		Icons::register_hooks();
		InlineIcon::register_hooks();
		VisuallyHidden::register_hooks();
		Swatches::register_hooks();
		SwatchesAdmin::register_hooks();
		ProductMeta::register_hooks();
		Subscribers::register_hooks();
		Confirmation::register_hooks();
		Subscribe::register_hooks();
		Exporter::register_hooks();
	}
}
