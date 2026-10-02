<?php
/**
 * Error notices as one alert listing every error, with icon and dismiss.
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
?>
<div class="woocommerce-error pc-alert pc-alert--danger" role="alert">
	<?php echo profchoice_alert_part( 'icon-danger' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<ul class="pc-alert__body">
		<?php foreach ( $notices as $notice ) : ?>
			<li<?php echo wc_get_notice_data_attr( $notice ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo wc_kses_notice( $notice['notice'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></li>
		<?php endforeach; ?>
	</ul>
	<?php echo profchoice_alert_part( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
