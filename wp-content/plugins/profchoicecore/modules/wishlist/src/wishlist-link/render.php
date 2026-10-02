<?php
/**
 * Wishlist link: heart, count badge (hidden at zero) and label.
 *
 * `innerClasses` (icon, count, label) lets a theme reuse its own header
 * classes, e.g. to look like the account and cart links.
 *
 * @package ProfChoiceCore
 *
 * @var array $attributes Block attributes.
 */

use ProfChoiceCore\Wishlist\Settings;
use ProfChoiceCore\Wishlist\Wishlist;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

Wishlist::prepare();

$pc_state   = wp_interactivity_state( Wishlist::STORE );
$pc_count   = (int) $pc_state['count'];
$pc_classes = isset( $attributes['innerClasses'] ) && is_array( $attributes['innerClasses'] ) ? $attributes['innerClasses'] : array();
$pc_class   = static function ( $base, $key ) use ( $pc_classes ) {
	return trim( $base . ' ' . ( isset( $pc_classes[ $key ] ) ? sanitize_text_field( $pc_classes[ $key ] ) : '' ) );
};
$pc_label   = ! empty( $attributes['label'] ) ? $attributes['label'] : __( 'Wishlist', 'profchoicecore' );
$pc_wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'pc-wishlist-link',
		'href'  => esc_url( Settings::page_url() ),
	)
);
?>
<a
	<?php echo $pc_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	data-wp-interactive="profchoice/wishlist"
	data-wp-bind--aria-label="state.linkLabel"
>
	<span class="<?php echo esc_attr( $pc_class( 'pc-wishlist-link__icon', 'icon' ) ); ?>">
		<?php echo Wishlist::heart( false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<span class="<?php echo esc_attr( $pc_class( 'pc-wishlist-link__count', 'count' ) ); ?>" data-wp-text="state.count" data-wp-style--display="state.countDisplay"><?php echo esc_html( (string) $pc_count ); ?></span>
	</span>
	<?php if ( ! isset( $attributes['showLabel'] ) || $attributes['showLabel'] ) : ?>
		<span class="<?php echo esc_attr( $pc_class( 'pc-wishlist-link__label', 'label' ) ); ?>"><?php echo esc_html( $pc_label ); ?></span>
	<?php endif; ?>
</a>
