<?php
/**
 * PDP notices (e.g. "added to your cart"), between the breadcrumb and the
 * main section. Nothing is printed without notices.
 *
 * @package profchoice
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wc_notice_count' ) || ! wc_notice_count() ) {
	return;
}
?>
<div class="pdp-notices">
	<div class="container wide">
		<?php woocommerce_output_all_notices(); ?>
	</div>
</div>
