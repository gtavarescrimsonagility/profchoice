<?php
/**
 * Single product gallery.
 *
 * Adds a badge hook inside the image frame so thumbs can sit outside the border.
 *
 * @package Commercebuild_Velocity
 * @version 11.1.0
 */

use Automattic\WooCommerce\Enums\ProductType;
use Automattic\WooCommerce\Internal\ProductGallery\ProductMediaGallery;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wc_get_gallery_image_html' ) ) {
	return;
}

global $product;

if ( ! $product instanceof WC_Product ) {
	return;
}

$columns           = apply_filters( 'woocommerce_product_thumbnails_columns', 4 );
$post_thumbnail_id = $product->get_image_id();
$first_media_id    = $post_thumbnail_id;
$first_media_item  = array();
$has_media         = (bool) $post_thumbnail_id;
$is_video          = false;

// Retain image-only rendering on WooCommerce versions without the media API.
if ( is_callable( array( ProductMediaGallery::class, 'get_product_media_gallery_items_for_display' ) ) ) {
	$media_items      = ProductMediaGallery::get_product_media_gallery_items_for_display( $product );
	$first_media_item = $media_items[0] ?? array();
	$first_media_id   = isset( $first_media_item['id'] ) ? absint( $first_media_item['id'] ) : $post_thumbnail_id;
	$has_media        = ! empty( $first_media_item ) && 'placeholder' !== ( $first_media_item['source_type'] ?? '' );
	$is_video         = $has_media && 'video' === ( $first_media_item['media_type'] ?? '' )
		&& is_callable( array( ProductMediaGallery::class, 'get_gallery_video_html' ) );
}

$wrapper_classes = apply_filters(
	'woocommerce_single_product_image_gallery_classes',
	array(
		'woocommerce-product-gallery',
		'woocommerce-product-gallery--' . ( $has_media ? 'with-images' : 'without-images' ),
		'woocommerce-product-gallery--columns-' . absint( $columns ),
		'images',
	)
);
?>
<div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $wrapper_classes ) ) ); ?>" data-columns="<?php echo esc_attr( $columns ); ?>" style="opacity: 0; transition: opacity 0.25s ease-in-out;">
	<?php
	/**
	 * Fires inside the product gallery, before the image frame (badges).
	 *
	 * @since 0.12.21
	 *
	 * @param WC_Product $product Product.
	 */
	do_action( 'cbv_product_gallery_before', $product );
	?>
	<div class="woocommerce-product-gallery__wrapper">
		<?php
		if ( $is_video ) {
			$html = ProductMediaGallery::get_gallery_video_html( $first_media_item, true );
		} elseif ( $has_media && $first_media_id ) {
			$html = wc_get_gallery_image_html( $first_media_id, true );
		} else {
			/**
			 * Filter the PDP gallery HTML when the product has no featured image.
			 *
			 * Return a full gallery image wrapper to replace the Woo placeholder.
			 *
			 * @since 0.12.30
			 *
			 * @param string     $html    Empty string to keep the Woo placeholder.
			 * @param WC_Product $product Product.
			 * @param string     $size    Image size slug.
			 */
			$html = apply_filters( 'cbv_product_fallback_image_html', '', $product, 'woocommerce_single' );

			if ( is_string( $html ) && '' !== $html && false === strpos( $html, 'woocommerce-product-gallery__image' ) ) {
				$html = '<div class="woocommerce-product-gallery__image">' . $html . '</div>';
			}

			if ( ! is_string( $html ) || '' === $html ) {
				$wrapper_classname = $product->is_type( ProductType::VARIABLE ) && ! empty( $product->get_visible_children() ) && '' !== $product->get_price()
					? 'woocommerce-product-gallery__image woocommerce-product-gallery__image--placeholder'
					: 'woocommerce-product-gallery__image--placeholder';
				$src               = function_exists( 'wc_placeholder_img_src' ) ? wc_placeholder_img_src( 'woocommerce_single' ) : '';
				$html              = '<div class="' . esc_attr( $wrapper_classname ) . '">';
				$html             .= sprintf(
					'<img src="%s" alt="%s" class="wp-post-image">',
					esc_url( $src ),
					esc_attr__( 'Awaiting product image', 'commercebuild-velocity' )
				);
				$html             .= '</div>';
			}
		}

		if ( $is_video ) {
			// This filter is documented in WooCommerce's product-image.php template.
			echo apply_filters( 'woocommerce_single_product_video_thumbnail_html', $html, $first_media_id, $first_media_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce gallery HTML.
		} else {
			echo apply_filters( 'woocommerce_single_product_image_thumbnail_html', $html, $first_media_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce gallery HTML.
		}

		do_action( 'woocommerce_product_thumbnails' );
		?>
	</div>
	<?php
	if ( ! $has_media && is_string( $html ) && preg_match( '/src="([^"]+)"/', $html, $cbv_thumb ) ) {
		$cbv_n = (int) apply_filters( 'cbv_product_fallback_thumb_count', 4, $product );

		if ( $cbv_n > 0 ) {
			echo '<ol class="flex-control-thumbs">';
			for ( $cbv_i = 0; $cbv_i < $cbv_n; $cbv_i++ ) {
				printf(
					'<li><img src="%s" alt=""%s></li>',
					esc_url( $cbv_thumb[1] ),
					0 === $cbv_i ? ' class="flex-active"' : ''
				);
			}
			echo '</ol>';
		}
	}

	?>
</div>
