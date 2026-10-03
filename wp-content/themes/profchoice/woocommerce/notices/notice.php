<?php
/**
 * Info notices as alerts: icon, message (with its action link) and dismiss.
 * A notice added with `array( 'pc_variant' => 'warning' )` as its data is a
 * warning (amber, alert icon): WooCommerce has no warning type.
 *
 * @package profchoice
 * @version 8.6.0
 *
 * @var array $notices Notices.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! $notices ) {
	return;
}

foreach ( $notices as $notice ) :
	?>
	<?php $pc_warning = isset( $notice['data']['pc_variant'] ) && 'warning' === $notice['data']['pc_variant']; ?>
	<div class="woocommerce-info pc-alert pc-alert--<?php echo $pc_warning ? 'warning' : 'info'; ?>"<?php echo wc_get_notice_data_attr( $notice ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> role="<?php echo $pc_warning ? 'alert' : 'status'; ?>">
		<?php echo profchoice_alert_part( $pc_warning ? 'icon-warning' : 'icon-info' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<div class="pc-alert__body"><?php echo wc_kses_notice( $notice['notice'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		<?php echo profchoice_alert_part( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
	<?php
endforeach;
