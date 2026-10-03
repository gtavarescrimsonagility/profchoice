<?php
/**
 * Error notices as one alert with icon and dismiss: a single error as its
 * text, several (a form) as a title and a bulleted list. The list items keep
 * WooCommerce's data attributes (checkout links each error to its field).
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
	<?php if ( 1 === count( $notices ) ) : ?>
		<?php $pc_notice = reset( $notices ); ?>
		<div class="pc-alert__body"<?php echo wc_get_notice_data_attr( $pc_notice ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo wc_kses_notice( $pc_notice['notice'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
	<?php else : ?>
		<div class="pc-alert__body pc-alert__body--list">
			<p class="pc-alert__title"><?php esc_html_e( 'Please fix the following:', 'profchoice' ); ?></p>
			<ul class="pc-alert__list">
				<?php foreach ( $notices as $notice ) : ?>
					<li<?php echo wc_get_notice_data_attr( $notice ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo wc_kses_notice( $notice['notice'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>
	<?php echo profchoice_alert_part( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
