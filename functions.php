<?php
/**
 * Theme bootstrap for commercebuild Velocity.
 *
 * @package Commercebuild_Velocity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CBV_VERSION', '0.12.77' );
define( 'CBV_DIR', trailingslashit( get_template_directory() ) );
define( 'CBV_URI', trailingslashit( get_template_directory_uri() ) );

require CBV_DIR . 'inc/class-cbv-cache.php';
require CBV_DIR . 'inc/class-cbv-query-engine.php';
require CBV_DIR . 'inc/class-cbv-theme.php';
require CBV_DIR . 'inc/class-cbv-assets.php';
require CBV_DIR . 'inc/class-cbv-customizer.php';
require CBV_DIR . 'inc/class-cbv-icons.php';
require CBV_DIR . 'inc/class-cbv-design.php';
require CBV_DIR . 'inc/class-cbv-chrome.php';
require CBV_DIR . 'inc/class-cbv-blocks.php';
require CBV_DIR . 'inc/class-cbv-block-areas.php';
require CBV_DIR . 'inc/class-cbv-setup.php';
require CBV_DIR . 'inc/class-cbv-product-elements.php';
require CBV_DIR . 'inc/class-cbv-product-layout.php';
require CBV_DIR . 'inc/class-cbv-catalog-elements.php';
require CBV_DIR . 'inc/class-cbv-catalog-layout.php';
require CBV_DIR . 'inc/class-cbv-speculation.php';
require CBV_DIR . 'inc/class-cbv-deferred.php';
require CBV_DIR . 'inc/template-tags.php';

if ( class_exists( 'WooCommerce' ) ) {
	require CBV_DIR . 'inc/class-cbv-woocommerce.php';
}

/**
 * Access the shared theme container.
 *
 * @since 0.1.0
 *
 * @return array Instantiated theme services keyed by slug.
 */
function cbv_services() {
	static $services = null;

	if ( null === $services ) {
		$cache  = new CBV_Cache();
		$engine = new CBV_Query_Engine( $cache );

		$services = array(
			'cache'       => $cache,
			'engine'      => $engine,
			'theme'       => new CBV_Theme(),
			'assets'      => new CBV_Assets(),
			'customizer'  => new CBV_Customizer(),
			'design'      => new CBV_Design(),
			'chrome'      => new CBV_Chrome(),
			'blocks'      => new CBV_Blocks( $engine ),
			'areas'       => new CBV_Block_Areas(),
			'setup'       => new CBV_Setup(),
			'product'     => new CBV_Product_Layout(),
			'catalog'     => new CBV_Catalog_Layout(),
			'speculation' => new CBV_Speculation(),
			'deferred'    => new CBV_Deferred(),
		);

		if ( class_exists( 'WooCommerce' ) ) {
			$services['woocommerce'] = new CBV_WooCommerce( $engine );
		}

		foreach ( $services as $service ) {
			if ( method_exists( $service, 'register' ) ) {
				$service->register();
			}
		}
	}

	return $services;
}

/**
 * Convenience accessor for the query engine.
 *
 * @since 0.1.0
 *
 * @return CBV_Query_Engine
 */
function cbv_engine() {
	$services = cbv_services();

	return $services['engine'];
}

cbv_services();
