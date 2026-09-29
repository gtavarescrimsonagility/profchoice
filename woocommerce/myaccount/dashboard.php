<?php
/**
 * My Account dashboard.
 *
 * @package Commercebuild_Velocity
 * @version 4.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cbv_user     = wp_get_current_user();
$cbv_customer = new WC_Customer( get_current_user_id() );
$edit_account = wc_get_endpoint_url( 'edit-account' );
$edit_address = wc_get_endpoint_url( 'edit-address' );
$orders_url   = wc_get_endpoint_url( 'orders' );

$cbv_name = trim( $cbv_customer->get_first_name() . ' ' . $cbv_customer->get_last_name() );

if ( '' === $cbv_name ) {
	$cbv_name = $cbv_user->display_name;
}

$cbv_contact_lines = array_filter(
	array(
		$cbv_name,
		$cbv_customer->get_billing_company(),
		$cbv_user->user_email,
		$cbv_customer->get_billing_phone(),
	)
);

$cbv_cards = array(
	array(
		'title'   => __( 'Contact Information', 'commercebuild-velocity' ),
		'lines'   => $cbv_contact_lines,
		'actions' => array(
			array(
				'label' => __( 'Edit', 'commercebuild-velocity' ),
				'url'   => $edit_account,
			),
			array(
				'label' => __( 'Change Password', 'commercebuild-velocity' ),
				'url'   => $edit_account,
			),
		),
	),
);

/**
 * Filter dashboard info cards after Contact Information.
 *
 * @since 0.12.41
 *
 * @param array       $cbv_cards    Cards: title, lines, actions.
 * @param WC_Customer $cbv_customer Current customer.
 */
$cbv_cards = apply_filters( 'cbv_account_dashboard_cards', $cbv_cards, $cbv_customer );

$cbv_address_lines = static function ( $customer, $which ) {
	$method = 'get_formatted_' . $which . '_address';
	$html   = is_callable( array( $customer, $method ) ) ? $customer->{$method}() : '';
	$lines  = array_filter( array_map( 'trim', preg_split( '/<br\s*\/?>/i', (string) $html ) ) );
	$phone  = 'billing' === $which ? $customer->get_billing_phone() : $customer->get_shipping_phone();

	if ( $phone ) {
		$lines[] = sprintf(
			/* translators: %s: phone number. */
			__( 'T: %s', 'commercebuild-velocity' ),
			$phone
		);
	}

	return $lines;
};
?>
<div class="cbv-account-dash">
	<div class="cbv-account-dash__head">
		<h2><?php esc_html_e( 'Account Information', 'commercebuild-velocity' ); ?></h2>
	</div>
	<div class="cbv-account-dash__grid">
		<?php foreach ( $cbv_cards as $cbv_card ) : ?>
			<?php
			if ( empty( $cbv_card['title'] ) ) {
				continue;
			}
			?>
			<section class="cbv-account-card">
				<h3><?php echo esc_html( $cbv_card['title'] ); ?></h3>
				<?php
				$cbv_card_lines = isset( $cbv_card['lines'] ) ? (array) $cbv_card['lines'] : array();
				foreach ( $cbv_card_lines as $cbv_line ) :
					?>
					<p><?php echo esc_html( $cbv_line ); ?></p>
				<?php endforeach; ?>
				<?php if ( ! empty( $cbv_card['notice'] ) ) : ?>
					<p class="cbv-account-card__notice"><?php echo esc_html( $cbv_card['notice'] ); ?></p>
				<?php endif; ?>
				<?php if ( ! empty( $cbv_card['actions'] ) ) : ?>
					<p class="cbv-account-card__actions">
						<?php foreach ( (array) $cbv_card['actions'] as $cbv_action ) : ?>
							<?php if ( ! empty( $cbv_action['label'] ) && ! empty( $cbv_action['url'] ) ) : ?>
								<a href="<?php echo esc_url( $cbv_action['url'] ); ?>"><?php echo esc_html( $cbv_action['label'] ); ?></a>
							<?php endif; ?>
						<?php endforeach; ?>
					</p>
				<?php endif; ?>
			</section>
		<?php endforeach; ?>
	</div>

	<div class="cbv-account-dash__head">
		<h2><?php esc_html_e( 'Address Book', 'commercebuild-velocity' ); ?></h2>
		<a href="<?php echo esc_url( $edit_address ); ?>"><?php esc_html_e( 'Manage Addresses', 'commercebuild-velocity' ); ?></a>
	</div>
	<div class="cbv-account-dash__grid">
		<section class="cbv-account-card">
			<div class="cbv-account-card__title-row">
				<h3><?php esc_html_e( 'Default Billing Address', 'commercebuild-velocity' ); ?></h3>
				<a href="<?php echo esc_url( wc_get_endpoint_url( 'edit-address', 'billing' ) ); ?>"><?php esc_html_e( 'Edit', 'commercebuild-velocity' ); ?></a>
			</div>
			<?php
			$cbv_billing = $cbv_address_lines( $cbv_customer, 'billing' );
			if ( empty( $cbv_billing ) ) :
				?>
				<p><?php esc_html_e( 'No billing address saved yet.', 'commercebuild-velocity' ); ?></p>
			<?php else : ?>
				<?php foreach ( $cbv_billing as $cbv_line ) : ?>
					<p><?php echo wp_kses_post( $cbv_line ); ?></p>
				<?php endforeach; ?>
			<?php endif; ?>
		</section>
		<section class="cbv-account-card">
			<div class="cbv-account-card__title-row">
				<h3><?php esc_html_e( 'Default Shipping Address', 'commercebuild-velocity' ); ?></h3>
				<a href="<?php echo esc_url( wc_get_endpoint_url( 'edit-address', 'shipping' ) ); ?>"><?php esc_html_e( 'Edit', 'commercebuild-velocity' ); ?></a>
			</div>
			<?php
			$cbv_shipping = $cbv_address_lines( $cbv_customer, 'shipping' );
			if ( empty( $cbv_shipping ) ) :
				?>
				<p><?php esc_html_e( 'No shipping address saved yet.', 'commercebuild-velocity' ); ?></p>
			<?php else : ?>
				<?php foreach ( $cbv_shipping as $cbv_line ) : ?>
					<p><?php echo wp_kses_post( $cbv_line ); ?></p>
				<?php endforeach; ?>
			<?php endif; ?>
		</section>
	</div>

	<?php
	/**
	 * Extra dashboard sections (company profile, etc).
	 *
	 * @since 0.12.42
	 *
	 * @param array       $sections  Sections: title, link, cards.
	 * @param WC_Customer $customer  Current customer.
	 */
	$cbv_sections = apply_filters( 'cbv_account_dashboard_sections', array(), $cbv_customer );

	foreach ( $cbv_sections as $cbv_section ) :
		if ( empty( $cbv_section['title'] ) ) {
			continue;
		}
		?>
		<div class="cbv-account-dash__head">
			<h2><?php echo esc_html( $cbv_section['title'] ); ?></h2>
			<?php if ( ! empty( $cbv_section['link']['label'] ) && ! empty( $cbv_section['link']['url'] ) ) : ?>
				<a href="<?php echo esc_url( $cbv_section['link']['url'] ); ?>"><?php echo esc_html( $cbv_section['link']['label'] ); ?></a>
			<?php endif; ?>
		</div>
		<?php if ( ! empty( $cbv_section['cards'] ) ) : ?>
			<div class="cbv-account-dash__grid">
				<?php foreach ( (array) $cbv_section['cards'] as $cbv_scard ) : ?>
					<?php
					if ( empty( $cbv_scard['title'] ) && empty( $cbv_scard['lines'] ) ) {
						continue;
					}
					?>
					<section class="cbv-account-card">
						<?php if ( ! empty( $cbv_scard['title'] ) ) : ?>
							<h3><?php echo esc_html( $cbv_scard['title'] ); ?></h3>
						<?php endif; ?>
						<?php foreach ( (array) ( isset( $cbv_scard['lines'] ) ? $cbv_scard['lines'] : array() ) as $cbv_line ) : ?>
							<p><?php echo esc_html( $cbv_line ); ?></p>
						<?php endforeach; ?>
					</section>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	<?php endforeach; ?>

	<?php
	$cbv_orders = array();

	if ( function_exists( 'wc_get_orders' ) ) {
		$cbv_orders = wc_get_orders(
			array(
				'customer' => get_current_user_id(),
				'limit'    => 4,
				'orderby'  => 'date',
				'order'    => 'DESC',
			)
		);
	}
	?>
	<div class="cbv-account-dash__head">
		<h2><?php esc_html_e( 'Recent Orders', 'commercebuild-velocity' ); ?></h2>
		<a href="<?php echo esc_url( $orders_url ); ?>"><?php esc_html_e( 'View All', 'commercebuild-velocity' ); ?></a>
	</div>
	<?php if ( empty( $cbv_orders ) ) : ?>
		<section class="cbv-account-card">
			<p><?php esc_html_e( 'No orders yet.', 'commercebuild-velocity' ); ?></p>
		</section>
	<?php else : ?>
		<table class="cbv-account-orders shop_table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Order #', 'commercebuild-velocity' ); ?></th>
					<th><?php esc_html_e( 'Date', 'commercebuild-velocity' ); ?></th>
					<th><?php esc_html_e( 'Ship to', 'commercebuild-velocity' ); ?></th>
					<th><?php esc_html_e( 'Order total', 'commercebuild-velocity' ); ?></th>
					<th><?php esc_html_e( 'Status', 'commercebuild-velocity' ); ?></th>
					<th><?php esc_html_e( 'Action', 'commercebuild-velocity' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $cbv_orders as $cbv_order ) : ?>
					<?php
					if ( ! $cbv_order instanceof WC_Order ) {
						continue;
					}
					$view = $cbv_order->get_view_order_url();
					?>
					<tr>
						<td><?php echo esc_html( $cbv_order->get_order_number() ); ?></td>
						<td><?php echo esc_html( wc_format_datetime( $cbv_order->get_date_created() ) ); ?></td>
						<td><?php echo esc_html( $cbv_order->get_formatted_shipping_full_name() ); ?></td>
						<td><?php echo wp_kses_post( $cbv_order->get_formatted_order_total() ); ?></td>
						<td><span class="cbv-account-orders__status"><?php echo esc_html( wc_get_order_status_name( $cbv_order->get_status() ) ); ?></span></td>
						<td>
							<a href="<?php echo esc_url( $view ); ?>"><?php esc_html_e( 'View Order', 'commercebuild-velocity' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
<?php
/** This action is documented in WooCommerce templates. */
do_action( 'woocommerce_account_dashboard' );

/** This deprecated action remains part of the WooCommerce template contract. */
do_action( 'woocommerce_before_my_account' );

/** This deprecated action remains part of the WooCommerce template contract. */
do_action( 'woocommerce_after_my_account' );
