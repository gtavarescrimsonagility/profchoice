<?php
/**
 * "Size guide" page select in Products > Attributes (add and edit).
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore\SizeGuide;

/**
 * Attribute screen fields.
 */
final class Admin {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'woocommerce_after_add_attribute_fields', array( __CLASS__, 'add_field' ) );
		add_action( 'woocommerce_after_edit_attribute_fields', array( __CLASS__, 'edit_field' ) );
		add_action( 'woocommerce_attribute_added', array( __CLASS__, 'save_added' ) );
		add_action( 'woocommerce_attribute_updated', array( __CLASS__, 'save_updated' ) );
		add_action( 'woocommerce_attribute_deleted', array( __CLASS__, 'forget' ) );
	}

	/**
	 * The page select.
	 *
	 * @param int $selected Selected page ID.
	 * @return string
	 */
	private static function select( $selected ) {
		return (string) wp_dropdown_pages(
			array(
				'name'              => 'profchoice_size_guide',
				'id'                => 'profchoice_size_guide',
				'selected'          => absint( $selected ),
				'show_option_none'  => __( '— None —', 'profchoicecore' ),
				'option_none_value' => '0',
				'echo'              => false,
			)
		);
	}

	/**
	 * Field in "Add new attribute".
	 *
	 * @return void
	 */
	public static function add_field() {
		?>
		<div class="form-field">
			<label for="profchoice_size_guide"><?php esc_html_e( 'Size guide', 'profchoicecore' ); ?></label>
			<?php echo self::select( 0 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<p class="description"><?php esc_html_e( 'Page shown in a modal from a "Size Guide" link next to this attribute on product pages. Without a page there is no link.', 'profchoicecore' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Field in "Edit attribute".
	 *
	 * @return void
	 */
	public static function edit_field() {
		$id    = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$pages = SizeGuide::pages();
		?>
		<tr class="form-field">
			<th scope="row" valign="top"><label for="profchoice_size_guide"><?php esc_html_e( 'Size guide', 'profchoicecore' ); ?></label></th>
			<td>
				<?php echo self::select( isset( $pages[ $id ] ) ? $pages[ $id ] : 0 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<p class="description"><?php esc_html_e( 'Page shown in a modal from a "Size Guide" link next to this attribute on product pages. Without a page there is no link.', 'profchoicecore' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save after "Add attribute".
	 *
	 * @param int $id Attribute ID.
	 * @return void
	 */
	public static function save_added( $id ) {
		if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ) ), 'woocommerce-add-new_attribute' ) ) {
			self::save( $id );
		}
	}

	/**
	 * Save after "Update".
	 *
	 * @param int $id Attribute ID.
	 * @return void
	 */
	public static function save_updated( $id ) {
		if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ) ), 'woocommerce-save-attribute_' . $id ) ) {
			self::save( $id );
		}
	}

	/**
	 * Store the posted page for an attribute.
	 *
	 * @param int $id Attribute ID.
	 * @return void
	 */
	private static function save( $id ) {
		if ( ! isset( $_POST['profchoice_size_guide'] ) || ! current_user_can( 'manage_product_terms' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified by the callers.
			return;
		}
		$pages    = SizeGuide::pages();
		$page     = absint( wp_unslash( $_POST['profchoice_size_guide'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$taxonomy = wc_attribute_taxonomy_name_by_id( (int) $id );
		// The previous page no longer guides this attribute.
		if ( ! empty( $pages[ (int) $id ] ) && get_post_meta( $pages[ (int) $id ], SizeGuide::META, true ) === $taxonomy ) {
			delete_post_meta( $pages[ (int) $id ], SizeGuide::META );
		}
		if ( $page ) {
			$pages[ (int) $id ] = $page;
			update_post_meta( $page, SizeGuide::META, $taxonomy );
		} else {
			unset( $pages[ (int) $id ] );
		}
		update_option( SizeGuide::OPTION, $pages );
	}

	/**
	 * Drop the page of a deleted attribute.
	 *
	 * @param int $id Attribute ID.
	 * @return void
	 */
	public static function forget( $id ) {
		$pages = SizeGuide::pages();
		unset( $pages[ (int) $id ] );
		update_option( SizeGuide::OPTION, $pages );
	}
}
