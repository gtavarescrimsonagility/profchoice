<?php
/**
 * Single product: the PDP design instead of the parent's wrapper, sidebar
 * and configurable layout (CBV_Product_Layout). WooCommerce's single
 * product hooks still fire in content-single-product.php.
 *
 * @package profchoice
 * @version 1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header( 'shop' );

while ( have_posts() ) {
	the_post();
	wc_get_template_part( 'content', 'single-product' );
}

get_footer( 'shop' );
