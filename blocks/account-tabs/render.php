<?php
/**
 * Account Tabs block render.
 *
 * Builds the tab navigation from the child account-tab blocks. Inactive
 * tabs are kept in the saved content but skipped here, so deactivating a
 * tab never loses its configuration. Guests get the WooCommerce login
 * form instead of the tabs.
 *
 * @package Commercebuild_Velocity
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Rendered inner blocks (unused; children are rendered individually).
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_user_logged_in() ) {
	/**
	 * Filter the guest fallback for the Account Tabs block.
	 *
	 * @since 0.4.0
	 *
	 * @param string $fallback Shortcode or HTML rendered for guests.
	 */
	$cbv_guest = apply_filters( 'cbv_account_tabs_guest_fallback', '[woocommerce_my_account]' );

	echo '<div ' . get_block_wrapper_attributes( array( 'class' => 'cbv-atabs cbv-atabs--guest' ) ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by WordPress core.

	/** This action is documented later in this file. */
	do_action( 'cbv_block_account_tabs_before', $attributes );

	echo do_shortcode( $cbv_guest ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shortcode/plugin output.

	/** This action is documented later in this file. */
	do_action( 'cbv_block_account_tabs_after', $attributes );

	echo '</div>';
	return;
}

$cbv_tabs  = array();
$cbv_slugs = array();

foreach ( $block->inner_blocks as $cbv_inner ) {
	if ( 'commercebuild/account-tab' !== $cbv_inner->name ) {
		continue;
	}

	$cbv_attrs = $cbv_inner->attributes;

	if ( empty( $cbv_attrs['active'] ) ) {
		continue;
	}

	$cbv_title = '' !== trim( (string) $cbv_attrs['title'] ) ? $cbv_attrs['title'] : __( 'Tab', 'commercebuild-velocity' );
	$cbv_slug  = sanitize_title( $cbv_title );

	if ( isset( $cbv_slugs[ $cbv_slug ] ) ) {
		++$cbv_slugs[ $cbv_slug ];
		$cbv_slug .= '-' . $cbv_slugs[ $cbv_slug ];
	} else {
		$cbv_slugs[ $cbv_slug ] = 1;
	}

	$cbv_tabs[] = array(
		'title'   => $cbv_title,
		'slug'    => $cbv_slug,
		'content' => $cbv_inner->render(),
	);
}

/**
 * Filter the assembled account tabs before rendering.
 *
 * @since 0.4.0
 *
 * @param array $cbv_tabs   Tabs: title, slug, content.
 * @param array $attributes Block attributes.
 */
$cbv_tabs = apply_filters( 'cbv_account_tabs', $cbv_tabs, $attributes );

if ( empty( $cbv_tabs ) ) {
	return;
}
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'cbv-atabs' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by WordPress core. ?>>
	<?php
	/**
	 * Fires inside the account-tabs block wrapper, before its content.
	 *
	 * @since 0.4.0
	 *
	 * @param array $attributes Block attributes.
	 */
	do_action( 'cbv_block_account_tabs_before', $attributes );
	?>
	<div class="cbv-atabs__nav" role="tablist" aria-label="<?php esc_attr_e( 'Account sections', 'commercebuild-velocity' ); ?>">
		<?php foreach ( $cbv_tabs as $cbv_i => $cbv_tab ) : ?>
			<button
				class="cbv-atabs__tab<?php echo 0 === $cbv_i ? ' is-active' : ''; ?>"
				type="button"
				role="tab"
				id="cbv-atab-<?php echo esc_attr( $cbv_tab['slug'] ); ?>"
				aria-controls="cbv-apanel-<?php echo esc_attr( $cbv_tab['slug'] ); ?>"
				aria-selected="<?php echo 0 === $cbv_i ? 'true' : 'false'; ?>"
				data-tab="<?php echo esc_attr( $cbv_tab['slug'] ); ?>"
			><?php echo esc_html( $cbv_tab['title'] ); ?></button>
		<?php endforeach; ?>
	</div>
	<?php foreach ( $cbv_tabs as $cbv_i => $cbv_tab ) : ?>
		<div
			class="cbv-atabs__panel"
			role="tabpanel"
			id="cbv-apanel-<?php echo esc_attr( $cbv_tab['slug'] ); ?>"
			aria-labelledby="cbv-atab-<?php echo esc_attr( $cbv_tab['slug'] ); ?>"
			<?php echo 0 === $cbv_i ? '' : 'hidden'; ?>
		>
			<?php echo $cbv_tab['content']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rendered inner blocks. ?>
		</div>
	<?php endforeach; ?>
	<?php
	/**
	 * Fires inside the account-tabs block wrapper, after its content.
	 *
	 * @since 0.4.0
	 *
	 * @param array $attributes Block attributes.
	 */
	do_action( 'cbv_block_account_tabs_after', $attributes );
	?>
</div>
