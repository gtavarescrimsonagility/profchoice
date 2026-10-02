<?php
/**
 * Info notices as alerts: icon, message (with its action link) and dismiss.
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
	<div class="woocommerce-info pc-alert pc-alert--info"<?php echo wc_get_notice_data_attr( $notice ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> role="status">
		<?php echo profchoice_alert_part( 'icon-info' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<div class="pc-alert__body"><?php echo wc_kses_notice( $notice['notice'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		<?php echo profchoice_alert_part( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
	<?php
endforeach;
