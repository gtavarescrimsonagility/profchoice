<?php
/**
 * Swatches in the product editor.
 *
 * Term colors and images are edited in Products > Attributes with
 * WooCommerce's own "Color / image" fields. This adds what core lacks: the
 * term picker for "Button" attributes in a product's Attributes tab, and a
 * Swatches tab with the per-product display and term overrides.
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
		add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'add_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( __CLASS__, 'panel' ) );
		add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
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

	/**
	 * Swatches tab, for variable products.
	 *
	 * @param array $tabs Product data tabs.
	 * @return array
	 */
	public static function add_tab( $tabs ) {
		$tabs['profchoice_swatches'] = array(
			'label'    => __( 'Swatches', 'profchoicecore' ),
			'target'   => 'profchoice_swatches_data',
			'class'    => array( 'show_if_variable' ),
			'priority' => 65,
		);
		return $tabs;
	}

	/**
	 * Swatch attributes of a product: global attributes used for variations
	 * whose type is "Color / image" or "Button".
	 *
	 * @param \WC_Product $product Product.
	 * @return \WC_Product_Attribute[]
	 */
	private static function swatch_attributes( $product ) {
		return array_filter(
			$product->get_attributes(),
			static function ( $attribute ) {
				return $attribute instanceof \WC_Product_Attribute
					&& $attribute->is_taxonomy()
					&& $attribute->get_variation()
					&& in_array( Swatches::attribute_type( $attribute->get_name() ), array( 'wc-visual', 'button' ), true );
			}
		);
	}

	/**
	 * Swatches panel: display style per attribute and, for "Color / image"
	 * attributes, a color or image per term for this product only.
	 *
	 * @return void
	 */
	public static function panel() {
		global $product_object;
		$product = $product_object instanceof \WC_Product ? $product_object : null;
		?>
		<div id="profchoice_swatches_data" class="panel woocommerce_options_panel hidden">
			<?php
			$attributes = $product ? self::swatch_attributes( $product ) : array();
			if ( ! $attributes ) :
				?>
				<p class="form-field"><?php esc_html_e( 'Add a "Color / image" or "Button" attribute used for variations, then save, to set its swatches here. Term colors and images are set in Products > Attributes.', 'profchoicecore' ); ?></p>
				<?php
			endif;
			foreach ( $attributes as $attribute ) :
				$taxonomy = $attribute->get_name();
				$settings = Swatches::product_settings( $product->get_id(), $taxonomy );
				$field    = 'profchoice_swatches[' . $taxonomy . ']';
				?>
				<div class="options_group">
					<p class="form-field">
						<label for="<?php echo esc_attr( 'pc-swatches-style-' . $taxonomy ); ?>"><?php echo esc_html( wc_attribute_label( $taxonomy ) ); ?></label>
						<select id="<?php echo esc_attr( 'pc-swatches-style-' . $taxonomy ); ?>" name="<?php echo esc_attr( $field . '[style]' ); ?>">
							<?php
							$styles = array(
								'auto'     => __( 'Swatches', 'profchoicecore' ),
								'dropdown' => __( 'Dropdown (WooCommerce default)', 'profchoicecore' ),
							);
							foreach ( $styles as $value => $label ) :
								?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['style'], $value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</p>
					<?php
					if ( 'wc-visual' === Swatches::attribute_type( $taxonomy ) ) :
						foreach ( (array) $attribute->get_terms() as $term ) :
							$override = isset( $settings['terms'][ $term->slug ] ) ? (array) $settings['terms'][ $term->slug ] : array();
							$default  = Swatches::term_visual( $term );
							$id       = 'pc-swatch-' . $taxonomy . '-' . $term->slug;
							?>
							<p class="form-field">
								<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $term->name ); ?></label>
								<input type="text" class="pc-swatch-color" id="<?php echo esc_attr( $id ); ?>"
									name="<?php echo esc_attr( $field . '[terms][' . $term->slug . '][color]' ); ?>"
									value="<?php echo esc_attr( isset( $override['color'] ) ? $override['color'] : '' ); ?>"
									data-default-color="<?php echo esc_attr( 'color' === $default['type'] ? $default['value'] : '' ); ?>" />
								<input type="number" min="0" class="short" style="margin-left: 0.5em; width: 7em;"
									name="<?php echo esc_attr( $field . '[terms][' . $term->slug . '][image]' ); ?>"
									value="<?php echo esc_attr( ! empty( $override['image'] ) ? absint( $override['image'] ) : '' ); ?>"
									placeholder="<?php esc_attr_e( 'Image ID', 'profchoicecore' ); ?>"
									aria-label="<?php echo esc_attr( sprintf( /* translators: %s: term name. */ __( '%s image ID', 'profchoicecore' ), $term->name ) ); ?>" />
							</p>
							<?php
						endforeach;
						?>
						<p class="form-field description"><?php esc_html_e( 'Empty values use the color or image of the term (Products > Attributes).', 'profchoicecore' ); ?></p>
						<?php
					endif;
					?>
				</div>
			<?php endforeach; ?>
			<?php wp_nonce_field( 'profchoice_swatches', 'profchoice_swatches_nonce' ); ?>
		</div>
		<?php
	}

	/**
	 * Save the Swatches panel.
	 *
	 * @param \WC_Product $product Product being saved.
	 * @return void
	 */
	public static function save( $product ) {
		if ( ! isset( $_POST['profchoice_swatches_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['profchoice_swatches_nonce'] ) ), 'profchoice_swatches' ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized per field below.
		$input = isset( $_POST['profchoice_swatches'] ) && is_array( $_POST['profchoice_swatches'] ) ? wp_unslash( $_POST['profchoice_swatches'] ) : array();
		$clean = array();

		foreach ( $input as $taxonomy => $settings ) {
			$taxonomy = sanitize_key( $taxonomy );
			$style    = isset( $settings['style'] ) && 'dropdown' === $settings['style'] ? 'dropdown' : 'auto';
			$terms    = array();
			foreach ( isset( $settings['terms'] ) && is_array( $settings['terms'] ) ? $settings['terms'] : array() as $slug => $values ) {
				$color = isset( $values['color'] ) ? sanitize_hex_color( $values['color'] ) : '';
				$image = isset( $values['image'] ) ? absint( $values['image'] ) : 0;
				if ( $color || $image ) {
					$terms[ sanitize_title( $slug ) ] = array_filter(
						array(
							'color' => $color,
							'image' => $image,
						)
					);
				}
			}
			if ( 'auto' !== $style || $terms ) {
				$clean[ $taxonomy ] = array(
					'style' => $style,
					'terms' => $terms,
				);
			}
		}

		if ( $clean ) {
			$product->update_meta_data( Swatches::META, $clean );
		} else {
			$product->delete_meta_data( Swatches::META );
		}
	}

	/**
	 * Color picker on the product screen.
	 *
	 * @param string $hook Admin page.
	 * @return void
	 */
	public static function enqueue( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || 'product' !== get_post_type() ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_add_inline_script( 'wp-color-picker', 'jQuery( function ( $ ) { $( ".pc-swatch-color" ).wpColorPicker(); } );' );
	}
}
