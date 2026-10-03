<?php
/**
 * Product page extras: an "As ridden by" card, edited in an "As ridden by"
 * tab of the product data.
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore;

/**
 * Rider post meta.
 */
final class ProductMeta {

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
			'image'        => __( 'Image', 'profchoicecore' ),
			'image_mobile' => __( 'Mobile image', 'profchoicecore' ),
			'alt'          => __( 'Image alternative text', 'profchoicecore' ),
			'link_text'    => __( 'Link text', 'profchoicecore' ),
			'link_url'     => __( 'Link URL', 'profchoicecore' ),
		);
	}

	/**
	 * Texts used when a field is empty (shown as its placeholder).
	 *
	 * @return array<string, string>
	 */
	public static function rider_defaults() {
		/**
		 * Filters the "As ridden by" card's default texts.
		 *
		 * @param array<string, string> $defaults Field => text.
		 */
		return (array) apply_filters(
			'profchoicecore_rider_defaults',
			array(
				'eyebrow'   => __( 'As ridden by', 'profchoicecore' ),
				'link_text' => __( 'Watch the setup', 'profchoicecore' ),
			)
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
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ), 20 );
	}

	/**
	 * Register the meta (REST and sanitization).
	 *
	 * @return void
	 */
	public static function register_meta() {
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
	 * The product's rider card, or null without a rider name.
	 *
	 * @param int $product_id Product ID.
	 * @return array|null
	 */
	public static function rider( $product_id ) {
		$rider = self::sanitize_rider( get_post_meta( $product_id, self::RIDER, true ) );
		if ( '' === $rider['name'] ) {
			return null;
		}
		foreach ( self::rider_defaults() as $key => $text ) {
			if ( isset( $rider[ $key ] ) && '' === $rider[ $key ] ) {
				$rider[ $key ] = $text;
			}
		}
		return $rider;
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
	 * "As ridden by" tab.
	 *
	 * @param array $tabs Product data tabs.
	 * @return array
	 */
	public static function add_tab( $tabs ) {
		$tabs['profchoice_rider'] = array(
			'label'    => __( 'As ridden by', 'profchoicecore' ),
			'target'   => 'profchoice_page_data',
			'class'    => array(),
			'priority' => 66,
		);
		return $tabs;
	}

	/**
	 * Rider fields.
	 *
	 * @return void
	 */
	public static function panel() {
		global $product_object;
		$id    = $product_object instanceof \WC_Product ? $product_object->get_id() : 0;
		$rider    = self::sanitize_rider( get_post_meta( $id, self::RIDER, true ) );
		$defaults = self::rider_defaults();
		?>
		<div id="profchoice_page_data" class="panel woocommerce_options_panel hidden">
			<div class="options_group">
				<?php
				foreach ( self::rider_fields() as $key => $label ) {
					if ( in_array( $key, array( 'image', 'image_mobile' ), true ) ) {
						self::image_field( $key, $label, $rider[ $key ] );
						continue;
					}
					woocommerce_wp_text_input(
						array(
							'id'          => 'profchoice_rider_' . $key,
							'name'        => 'profchoice_rider[' . $key . ']',
							'label'       => $label,
							'type'        => 'link_url' === $key ? 'url' : 'text',
							'value'       => $rider[ $key ] ? $rider[ $key ] : '',
							'placeholder' => isset( $defaults[ $key ] ) ? $defaults[ $key ] : '',
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
	 * An image chosen from the Media Library (stored as its ID).
	 *
	 * @param string $key   Rider field.
	 * @param string $label Label.
	 * @param int    $id    Attachment ID.
	 * @return void
	 */
	private static function image_field( $key, $label, $id ) {
		$field = 'profchoice_rider_' . $key;
		?>
		<p class="form-field pc-media-field <?php echo esc_attr( $field ); ?>_field">
			<label for="<?php echo esc_attr( $field ); ?>"><?php echo esc_html( $label ); ?></label>
			<input type="hidden" id="<?php echo esc_attr( $field ); ?>" name="profchoice_rider[<?php echo esc_attr( $key ); ?>]" value="<?php echo $id ? absint( $id ) : ''; ?>" />
			<span class="pc-media-field__preview"><?php echo $id ? wp_get_attachment_image( $id, 'thumbnail', false, array( 'style' => 'display:block;max-width:150px;height:auto;margin-bottom:0.5em' ) ) : ''; ?></span>
			<button type="button" class="button pc-media-field__select" data-title="<?php echo esc_attr( $label ); ?>"><?php esc_html_e( 'Select image', 'profchoicecore' ); ?></button>
			<button type="button" class="button-link pc-media-field__remove"<?php echo $id ? '' : ' hidden'; ?>><?php esc_html_e( 'Remove', 'profchoicecore' ); ?></button>
		</p>
		<?php
	}

	/**
	 * Media Library picker and the tab's icon.
	 *
	 * @param string $hook Admin page.
	 * @return void
	 */
	public static function enqueue( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || 'product' !== get_post_type() ) {
			return;
		}
		wp_enqueue_media();
		wp_add_inline_style( 'woocommerce_admin_styles', '#woocommerce-product-data ul.wc-tabs li.profchoice_rider_options a::before { content: "\\f110"; }' );
		wp_add_inline_script(
			'media-editor',
			<<<'JS'
jQuery( function ( $ ) {
	$( document ).on( 'click', '.pc-media-field__select', function ( event ) {
		event.preventDefault();
		const field = $( this ).closest( '.pc-media-field' );
		const frame = wp.media( { title: $( this ).data( 'title' ), library: { type: 'image' }, multiple: false } );
		frame.on( 'select', function () {
			const image = frame.state().get( 'selection' ).first().toJSON();
			const size = image.sizes && image.sizes.thumbnail ? image.sizes.thumbnail : image;
			field.find( 'input[type=hidden]' ).val( image.id );
			field.find( '.pc-media-field__preview' ).html( $( '<img>', { src: size.url, alt: '', style: 'display:block;max-width:150px;height:auto;margin-bottom:0.5em' } ) );
			field.find( '.pc-media-field__remove' ).prop( 'hidden', false );
		} );
		frame.open();
	} );
	$( document ).on( 'click', '.pc-media-field__remove', function ( event ) {
		event.preventDefault();
		const field = $( this ).closest( '.pc-media-field' );
		field.find( 'input[type=hidden]' ).val( '' );
		field.find( '.pc-media-field__preview' ).empty();
		$( this ).prop( 'hidden', true );
	} );
} );
JS
		);
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
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitize_rider().
		$rider = self::sanitize_rider( isset( $_POST['profchoice_rider'] ) ? wp_unslash( $_POST['profchoice_rider'] ) : array() );

		if ( '' !== $rider['name'] ) {
			$product->update_meta_data( self::RIDER, $rider );
		} else {
			$product->delete_meta_data( self::RIDER );
		}
	}
}
