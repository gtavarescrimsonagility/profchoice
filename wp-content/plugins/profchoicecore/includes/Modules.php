<?php
/**
 * Module loader.
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore;

/**
 * Loads every modules/<name>/module.php. Each module is self-contained
 * (PHP, sources, build, assets, tests): deleting its folder removes the
 * feature and nothing else.
 */
final class Modules {

	/**
	 * Loaded modules: slug => header data.
	 *
	 * @var array<string, array>
	 */
	private static $loaded = array();

	/**
	 * Load the modules once every plugin is loaded, so a module can check
	 * the plugins it needs (`Requires Plugins` header, e.g. woocommerce).
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'plugins_loaded', array( __CLASS__, 'load' ), 5 );
	}

	/**
	 * Require each module whose required plugins are active.
	 *
	 * @return void
	 */
	public static function load() {
		$modules = array();
		foreach ( (array) glob( PROFCHOICECORE_DIR . 'modules/*/module.php' ) as $file ) {
			$modules[ basename( dirname( $file ) ) ] = $file;
		}

		/**
		 * Filters the modules to load: slug => path of its module.php. Remove
		 * a slug to turn a module off without deleting its folder.
		 *
		 * @param array<string, string> $modules Modules found in modules/.
		 */
		$modules = (array) apply_filters( 'profchoicecore_modules', $modules );

		foreach ( $modules as $slug => $file ) {
			if ( ! is_readable( $file ) ) {
				continue;
			}
			$data = get_file_data(
				$file,
				array(
					'name'     => 'Module Name',
					'requires' => 'Requires Plugins',
				)
			);
			if ( ! self::requirements_met( $data['requires'] ) ) {
				continue;
			}
			require_once $file;
			self::$loaded[ $slug ] = $data;
		}
	}

	/**
	 * Loaded modules (slug => Module Name / Requires Plugins).
	 *
	 * @return array<string, array>
	 */
	public static function loaded() {
		return self::$loaded;
	}

	/**
	 * Whether every plugin slug in a comma-separated list is active.
	 *
	 * @param string $requires Plugin slugs (folder names), comma separated.
	 * @return bool
	 */
	private static function requirements_met( $requires ) {
		$slugs = array_filter( array_map( 'trim', explode( ',', (string) $requires ) ) );
		if ( ! $slugs ) {
			return true;
		}
		$active = (array) get_option( 'active_plugins', array() );
		if ( is_multisite() ) {
			$active = array_merge( $active, array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) );
		}
		foreach ( $slugs as $slug ) {
			$found = false;
			foreach ( $active as $plugin ) {
				if ( 0 === strpos( $plugin, $slug . '/' ) ) {
					$found = true;
					break;
				}
			}
			if ( ! $found ) {
				return false;
			}
		}
		return true;
	}
}
