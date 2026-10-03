<?php
/**
 * Swatches in the product editor.
 *
 * Term colors and images are edited in Products > Attributes with
 * WooCommerce's own "Color / image" fields. This adds what core lacks: the
 * term picker for "Button" attributes in a product's Attributes tab. Whether
 * an attribute shows swatches is its type ("Select" keeps the dropdown).
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore;

/**
 * Product editor screens for swatches.
 */
final class SwatchesAdmin {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'woocommerce_product_option_terms', array( __CLASS__, 'option_terms' ), 10, 3 );
	}

	/**
	 * Core only prints the term picker for "select" and "Color / image"
	 * attributes; "Button" attributes get the same one.
	 *
	 * @param object                $taxonomy  Attribute taxonomy row.
	 * @param int                   $i         Attribute index.
	 * @param \WC_Product_Attribute $attribute Attribute.
	 * @return void
	 */
	public static function option_terms( $taxonomy, $i, $attribute ) {
		if ( ! $taxonomy || 'button' !== $taxonomy->attribute_type ) {
			return;
		}
		$orderby = ! empty( $taxonomy->attribute_orderby ) ? $taxonomy->attribute_orderby : 'name';
		/** This filter is documented in woocommerce/includes/admin/meta-boxes/views/html-product-attribute-inner.php */
		$limit = absint( apply_filters( 'woocommerce_admin_terms_metabox_datalimit', 50 ) );
		?>
		<select multiple="multiple"
			data-minimum_input_length="0"
			data-limit="<?php echo esc_attr( $limit ); ?>" data-return_id="id"
			data-placeholder="<?php esc_attr_e( 'Select values', 'profchoicecore' ); ?>"
			data-orderby="<?php echo esc_attr( $orderby ); ?>"
			class="multiselect attribute_values wc-taxonomy-term-search"
			name="attribute_values[<?php echo esc_attr( $i ); ?>][]"
			data-taxonomy="<?php echo esc_attr( $attribute->get_taxonomy() ); ?>">
			<?php foreach ( (array) $attribute->get_terms() as $term ) : ?>
				<option value="<?php echo esc_attr( $term->term_id ); ?>" selected="selected"><?php echo esc_html( $term->name ); ?></option>
			<?php endforeach; ?>
		</select>
		<button class="button plus select_all_attributes"><?php esc_html_e( 'Select all', 'profchoicecore' ); ?></button>
		<button class="button minus select_no_attributes"><?php esc_html_e( 'Select none', 'profchoicecore' ); ?></button>
		<button class="button fr plus add_new_attribute"><?php esc_html_e( 'Create value', 'profchoicecore' ); ?></button>
		<?php
	}
}
