<?php
/**
 * Variation swatches: one attribute of a variable product as a radio group.
 * Inner content (the classic form's select) is kept, visually hidden, as the
 * field WooCommerce's script reads.
 *
 * @package ProfChoiceCore
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner HTML (the select, in the classic form).
 * @var WP_Block $block      Block instance.
 */

use ProfChoiceCore\Swatches;

if ( ! defined( 'ABSPATH' ) || ! function_exists( 'wc_get_product' ) ) {
	return;
}

$pc_product_id = ! empty( $attributes['productId'] ) ? absint( $attributes['productId'] ) : 0;
if ( ! $pc_product_id ) {
	$pc_product_id = isset( $block->context['postId'] ) ? absint( $block->context['postId'] ) : absint( get_the_ID() );
}
$pc_product  = wc_get_product( $pc_product_id );
$pc_taxonomy = isset( $attributes['attribute'] ) ? (string) $attributes['attribute'] : '';

if ( ! $pc_product || ! $pc_product->is_type( 'variable' ) || '' === $pc_taxonomy ) {
	return;
}

$pc_items = Swatches::items( $pc_product, $pc_taxonomy );
if ( ! $pc_items ) {
	return;
}

$pc_name = ! empty( $attributes['name'] ) ? (string) $attributes['name'] : 'attribute_' . sanitize_title( $pc_taxonomy );
if ( isset( $attributes['selected'] ) ) {
	$pc_selected = (string) $attributes['selected'];
} elseif ( isset( $_REQUEST[ $pc_name ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$pc_selected = wc_clean( wp_unslash( $_REQUEST[ $pc_name ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
} else {
	$pc_selected = $pc_product->get_variation_default_attribute( $pc_taxonomy );
}

$pc_labels   = array();
$pc_disabled = array();
foreach ( $pc_items as $pc_item ) {
	$pc_labels[ $pc_item['value'] ] = $pc_item['label'];
	if ( ! $pc_item['available'] ) {
		$pc_disabled[] = $pc_item['value'];
	}
}
if ( ! isset( $pc_labels[ $pc_selected ] ) ) {
	$pc_selected = '';
}
$pc_first = $pc_items[0]['value'];
$pc_kind  = 'wc-visual' === Swatches::attribute_type( $pc_taxonomy ) ? 'visual' : 'button';

// Derived state for server-side directive processing; view.ts mirrors it.
wp_interactivity_state(
	'profchoice/swatches',
	array(
		'isChecked'     => static function () {
			$context = wp_interactivity_get_context( 'profchoice/swatches' );
			return isset( $context['value'] ) && $context['selected'] === $context['value'];
		},
		'isDisabled'    => static function () {
			$context = wp_interactivity_get_context( 'profchoice/swatches' );
			return isset( $context['value'] ) && in_array( $context['value'], $context['disabled'], true );
		},
		'tabIndex'      => static function () {
			$context = wp_interactivity_get_context( 'profchoice/swatches' );
			$current = '' !== $context['selected'] ? $context['selected'] : $context['first'];
			return isset( $context['value'] ) && $context['value'] === $current ? 0 : -1;
		},
		'selectedLabel' => static function () {
			$context = wp_interactivity_get_context( 'profchoice/swatches' );
			return isset( $context['labels'][ $context['selected'] ] ) ? $context['labels'][ $context['selected'] ] : '';
		},
	)
);

// The select stays in the form for WooCommerce, out of sight and of the tab order.
$pc_select = new WP_HTML_Tag_Processor( (string) $content );
while ( $pc_select->next_tag( 'SELECT' ) ) {
	$pc_select->set_attribute( 'tabindex', '-1' );
	$pc_select->set_attribute( 'aria-hidden', 'true' );
}
$pc_select = trim( $pc_select->get_updated_html() );

$pc_label_id = wp_unique_id( 'pc-swatches-label-' );
$pc_wrapper  = get_block_wrapper_attributes(
	array(
		'class'           => 'pc-swatches pc-swatches--' . $pc_kind,
		'role'            => 'radiogroup',
		'aria-labelledby' => $pc_label_id,
	)
);
$pc_show_label = ! isset( $attributes['showLabel'] ) || $attributes['showLabel'];
$pc_show_value = ( ! isset( $attributes['showValue'] ) || $attributes['showValue'] ) && 'visual' === $pc_kind;
$pc_guide_url  = ! empty( $attributes['sizeGuideUrl'] ) ? (string) $attributes['sizeGuideUrl'] : '';
$pc_guide_text = ! empty( $attributes['sizeGuideText'] ) ? (string) $attributes['sizeGuideText'] : __( 'Size Guide', 'profchoicecore' );
$pc_label      = wc_attribute_label( $pc_taxonomy, $pc_product );
/**
 * Filters the guide link shown in the swatches' head (e.g. the Size Guide
 * module's link). Without one, the `sizeGuideUrl` attribute's plain link.
 *
 * @param string     $html       Guide markup (empty).
 * @param string     $taxonomy   Attribute taxonomy.
 * @param WC_Product $product    Product.
 * @param array      $attributes Block attributes.
 */
$pc_guide = (string) apply_filters( 'profchoicecore_swatches_guide', '', $pc_taxonomy, $pc_product, $attributes );
?>
<div
	<?php echo $pc_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	data-wp-interactive="profchoice/swatches"
	<?php
	echo wp_interactivity_data_wp_context( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		array(
			'name'     => $pc_name,
			'selected' => $pc_selected,
			'first'    => $pc_first,
			'labels'   => (object) $pc_labels,
			'disabled' => $pc_disabled,
		)
	);
	?>
	data-wp-init="callbacks.init"
>
	<div class="pc-swatches__head<?php echo $pc_show_label ? '' : ' screen-reader-text'; ?>">
		<p class="pc-swatches__label" id="<?php echo esc_attr( $pc_label_id ); ?>"><?php echo esc_html( $pc_label ); ?></p>
		<?php if ( $pc_show_value ) : ?>
			<p class="pc-swatches__value" data-wp-text="state.selectedLabel"></p>
		<?php endif; ?>
		<?php if ( '' !== $pc_guide ) : ?>
			<?php echo $pc_guide; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php elseif ( $pc_guide_url ) : ?>
			<a class="pc-swatches__guide" href="<?php echo esc_url( $pc_guide_url ); ?>"><?php echo esc_html( $pc_guide_text ); ?></a>
		<?php endif; ?>
	</div>
	<div class="pc-swatches__items">
		<?php
		foreach ( $pc_items as $pc_item ) :
			$pc_visual = $pc_item['visual'];
			$pc_class  = 'pc-swatch pc-swatch--' . ( 'visual' === $pc_kind && 'none' !== $pc_visual['type'] ? $pc_visual['type'] : 'button' );
			$pc_style  = '';
			if ( 'color' === $pc_visual['type'] && 'visual' === $pc_kind ) {
				$pc_style = '--swatch: ' . $pc_visual['value'];
			} elseif ( 'image' === $pc_visual['type'] && 'visual' === $pc_kind ) {
				$pc_style = '--swatch: url("' . esc_url_raw( $pc_visual['value'] ) . '") center / cover';
			}
			$pc_is_text = false === strpos( $pc_class, 'pc-swatch--button' ) ? false : true;
			?>
			<button
				class="<?php echo esc_attr( $pc_class ); ?>"
				type="button"
				role="radio"
				data-value="<?php echo esc_attr( $pc_item['value'] ); ?>"
				<?php if ( ! $pc_is_text ) : ?>
					aria-label="<?php echo esc_attr( $pc_item['label'] ); ?>"
					title="<?php echo esc_attr( $pc_item['label'] ); ?>"
				<?php endif; ?>
				<?php if ( $pc_style ) : ?>
					style="<?php echo esc_attr( $pc_style ); ?>"
				<?php endif; ?>
				<?php echo wp_interactivity_data_wp_context( array( 'value' => $pc_item['value'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				data-wp-bind--aria-checked="state.isChecked"
				data-wp-bind--aria-disabled="state.isDisabled"
				data-wp-bind--tabindex="state.tabIndex"
				data-wp-on--click="actions.select"
				data-wp-on--keydown="actions.navigate"
			><?php echo $pc_is_text ? esc_html( $pc_item['label'] ) : ''; ?></button>
		<?php endforeach; ?>
	</div>
	<?php if ( '' !== $pc_select ) : ?>
		<div class="pc-swatches__select"><?php echo $pc_select; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
	<?php endif; ?>
</div>
