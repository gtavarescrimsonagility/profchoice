<?php
/**
 * Width and height for content images that core leaves without them.
 *
 * Core adds them from the attachment metadata (wp_img_tag_add_width_and_height_attr).
 * When that metadata has no size (media imported or copied without it),
 * the tag keeps no dimensions, and a lazy-loaded image starts as a 0x0 box
 * that shifts the layout when it loads. This reads the size from the file.
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore;

/**
 * Fallback dimensions for content images.
 */
final class ImageDimensions {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_filter( 'wp_content_img_tag', array( __CLASS__, 'add' ), 20, 3 );
	}

	/**
	 * Add width and height from the file when the tag has neither.
	 *
	 * @param string $image         Image tag.
	 * @param string $context       Filter context.
	 * @param int    $attachment_id Attachment ID, or 0.
	 * @return string
	 */
	public static function add( $image, $context, $attachment_id ) {
		$tag = new \WP_HTML_Tag_Processor( $image );
		if ( ! $tag->next_tag( 'img' ) || null !== $tag->get_attribute( 'width' ) || null !== $tag->get_attribute( 'height' ) ) {
			return $image;
		}
		$size = self::size( (string) $tag->get_attribute( 'src' ), (int) $attachment_id );
		if ( ! $size ) {
			return $image;
		}
		$tag->set_attribute( 'width', (string) $size[0] );
		$tag->set_attribute( 'height', (string) $size[1] );
		return $tag->get_updated_html();
	}

	/**
	 * Size of an uploaded image, from its file (the attachment's, or the
	 * file the URL points to in the uploads folder, for sized copies).
	 *
	 * @param string $src           Image URL.
	 * @param int    $attachment_id Attachment ID, or 0.
	 * @return array{0: int, 1: int}|null
	 */
	private static function size( $src, $attachment_id ) {
		static $cache = array();
		if ( isset( $cache[ $src ] ) ) {
			return $cache[ $src ];
		}
		$uploads = wp_get_upload_dir();
		$path    = (string) wp_parse_url( $src, PHP_URL_PATH );
		$base    = (string) wp_parse_url( $uploads['baseurl'], PHP_URL_PATH );
		$file    = '' !== $base && 0 === strpos( $path, $base . '/' ) ? $uploads['basedir'] . substr( $path, strlen( $base ) ) : '';
		if ( ( '' === $file || ! is_file( $file ) ) && $attachment_id ) {
			$file = (string) get_attached_file( $attachment_id );
		}
		$size = '' !== $file && is_file( $file ) ? wp_getimagesize( $file ) : false;

		$cache[ $src ] = $size && $size[0] && $size[1] ? array( (int) $size[0], (int) $size[1] ) : null;
		return $cache[ $src ];
	}
}
