<?php
/**
 * Product page extras: a badge ("Pro Pick", "New", "Sale") and an
 * "As ridden by" card, edited in a "Product page" tab of the product data.
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore;

/**
 * Badge and rider post meta.
 */
final class ProductMeta {

	const BADGE = '_profchoice_badge';
	const RIDER = '_profchoice_rider';

	/**
	 * Rider fields => labels.
	 *
	 * @return array<string, string>
	 */
	private static function rider_fields() {
		return array(
			'eyebrow'      => __( 'Eyebrow', 'profchoicecore' ),
			'name'         => __( 'Rider', 'profchoicecore' ),
			'image'        => __( 'Image ID', 'profchoicecore' ),
			'image_mobile' => __( 'Mobile image ID', 'profchoicecore' ),
			'alt'          => __( 'Image alternative text', 'profchoicecore' ),
			'link_text'    => __( 'Link text', 'profchoicecore' ),
			'link_url'     => __( 'Link URL', 'profchoicecore' ),
		);
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'init', array( __CLASS__, 'register_meta' ) );
		add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'add_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( __CLASS__, 'panel' ) );
		add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'save' ) );
	}

	/**
	 * Register the meta (REST and sanitization).
	 *
	 * @return void
	 */
	public static function register_meta() {
		register_post_meta(
			'product',
			self::BADGE,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => static function () {
					return current_user_can( 'edit_products' );
				},
			)
		);
		register_post_meta(
			'product',
			self::RIDER,
			array(
				'type'              => 'object',
				'single'            => true,
				'show_in_rest'      => array(
					'schema' => array(
						'type'       => 'object',
						'properties' => array(
							'eyebrow'      => array( 'type' => 'string' ),
							'name'         => array( 'type' => 'string' ),
							'image'        => array( 'type' => 'integer' ),
							'image_mobile' => array( 'type' => 'integer' ),
							'alt'          => array( 'type' => 'string' ),
							'link_text'    => array( 'type' => 'string' ),
							'link_url'     => array( 'type' => 'string' ),
						),
					),
				),
				'sanitize_callback' => array( __CLASS__, 'sanitize_rider' ),
				'auth_callback'     => static function () {
					return current_user_can( 'edit_products' );
				},
			)
		);
	}

	/**
	 * The product's badge text.
	 *
	 * @param int $product_id Product ID.
	 * @return string
	 */
	public static function badge( $product_id ) {
		return (string) get_post_meta( $product_id, self::BADGE, true );
	}

	/**
	 * The product's rider card, or null without a rider name.
	 *
	 * @param int $product_id Product ID.
	 * @return array|null
	 */
	public static function rider( $product_id ) {
		$rider = self::sanitize_rider( get_post_meta( $product_id, self::RIDER, true ) );
		return '' !== $rider['name'] ? $rider : null;
	}

	/**
	 * Sanitize a rider card.
	 *
	 * @param mixed $value Raw value.
	 * @return array
	 */
	public static function sanitize_rider( $value ) {
		$value = is_array( $value ) ? $value : array();
		$clean = array();
		foreach ( array_keys( self::rider_fields() ) as $key ) {
			$raw = isset( $value[ $key ] ) ? $value[ $key ] : '';
			if ( in_array( $key, array( 'image', 'image_mobile' ), true ) ) {
				$clean[ $key ] = absint( $raw );
			} elseif ( 'link_url' === $key ) {
				$clean[ $key ] = esc_url_raw( (string) $raw );
			} else {
				$clean[ $key ] = sanitize_text_field( (string) $raw );
			}
		}
		return $clean;
	}

	/**
	 * "Product page" tab.
	 *
	 * @param array $tabs Product data tabs.
	 * @return array
	 */
	public static function add_tab( $tabs ) {
		$tabs['profchoice_page'] = array(
			'label'    => __( 'Product page', 'profchoicecore' ),
			'target'   => 'profchoice_page_data',
			'class'    => array(),
			'priority' => 66,
		);
		return $tabs;
	}

	/**
	 * Badge and rider fields.
	 *
	 * @return void
	 */
	public static function panel() {
		global $product_object;
		$id    = $product_object instanceof \WC_Product ? $product_object->get_id() : 0;
		$rider = self::sanitize_rider( get_post_meta( $id, self::RIDER, true ) );
		?>
		<div id="profchoice_page_data" class="panel woocommerce_options_panel hidden">
			<div class="options_group">
				<?php
				woocommerce_wp_text_input(
					array(
						'id'          => 'profchoice_badge',
						'label'       => __( 'Badge', 'profchoicecore' ),
						'value'       => self::badge( $id ),
						'description' => __( 'E.g. Pro Pick, New or Sale. Shown on the product photo and on product cards.', 'profchoicecore' ),
						'desc_tip'    => true,
					)
				);
				?>
			</div>
			<div class="options_group">
				<p class="form-field"><strong><?php esc_html_e( '"As ridden by" card', 'profchoicecore' ); ?></strong></p>
				<?php
				foreach ( self::rider_fields() as $key => $label ) {
					woocommerce_wp_text_input(
						array(
							'id'    => 'profchoice_rider_' . $key,
							'name'  => 'profchoice_rider[' . $key . ']',
							'label' => $label,
							'type'  => in_array( $key, array( 'image', 'image_mobile' ), true ) ? 'number' : ( 'link_url' === $key ? 'url' : 'text' ),
							'value' => $rider[ $key ] ? $rider[ $key ] : '',
						)
					);
				}
				wp_nonce_field( 'profchoice_page', 'profchoice_page_nonce' );
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Save the tab.
	 *
	 * @param \WC_Product $product Product being saved.
	 * @return void
	 */
	public static function save( $product ) {
		if ( ! isset( $_POST['profchoice_page_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['profchoice_page_nonce'] ) ), 'profchoice_page' ) ) {
			return;
		}
		$badge = isset( $_POST['profchoice_badge'] ) ? sanitize_text_field( wp_unslash( $_POST['profchoice_badge'] ) ) : '';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitize_rider().
		$rider = self::sanitize_rider( isset( $_POST['profchoice_rider'] ) ? wp_unslash( $_POST['profchoice_rider'] ) : array() );

		if ( '' !== $badge ) {
			$product->update_meta_data( self::BADGE, $badge );
		} else {
			$product->delete_meta_data( self::BADGE );
		}
		if ( '' !== $rider['name'] ) {
			$product->update_meta_data( self::RIDER, $rider );
		} else {
			$product->delete_meta_data( self::RIDER );
		}
	}
}
