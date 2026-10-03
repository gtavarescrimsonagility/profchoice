<?php
/**
 * Reviews: data for the profchoice/reviews block (summary, first page of
 * reviews), its store module, and the shortcode and action that render it.
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore\Reviews;

/**
 * Product reviews.
 */
final class Reviews {

	const STORE  = 'profchoice/reviews';
	const MODULE = 'profchoice-reviews-store';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'init', array( __CLASS__, 'register_store' ), 5 );
		add_shortcode( 'profchoice_reviews', array( __CLASS__, 'shortcode' ) );
		add_action( 'profchoicecore_reviews', array( __CLASS__, 'action' ), 10, 2 );
		add_filter( 'rest_post_dispatch', array( __CLASS__, 'store_api_names' ), 10, 3 );
		add_filter( 'comment_post_redirect', array( __CLASS__, 'after_review' ), 10, 2 );
	}

	/**
	 * After a review is posted, back to the product's reviews (not to a
	 * #comment-N anchor the block doesn't have) with its result, so the
	 * block opens and shows a confirmation.
	 *
	 * @param string      $location Redirect URL.
	 * @param \WP_Comment $comment  The new comment.
	 * @return string
	 */
	public static function after_review( $location, $comment ) {
		if ( ! $comment instanceof \WP_Comment || 'review' !== $comment->comment_type || 'product' !== get_post_type( $comment->comment_post_ID ) ) {
			return $location;
		}
		$status   = '1' === (string) $comment->comment_approved ? 'published' : 'pending';
		$location = strtok( $location, '#' );
		return add_query_arg( 'pc-review', $status, $location ) . '#reviews';
	}

	/**
	 * A reviewer's public name: first name and last initial, whether the
	 * surname was typed in full or as an initial ("Jessica Turner",
	 * "jessica t" -> "Jessica T."); one word stays as it is.
	 *
	 * @param string $name Name as stored.
	 * @return string
	 */
	public static function display_name( $name ) {
		$parts = preg_split( '/\s+/u', trim( (string) $name ) );
		if ( ! $parts || count( $parts ) < 2 ) {
			return trim( (string) $name );
		}
		$first = $parts[0];
		$last  = end( $parts );
		return mb_strtoupper( mb_substr( $first, 0, 1 ) ) . mb_substr( $first, 1 ) . ' ' . mb_strtoupper( mb_substr( $last, 0, 1 ) ) . '.';
	}

	/**
	 * The Store API's product reviews show the same public names and text
	 * as the block's server render.
	 *
	 * @param \WP_REST_Response $response Response.
	 * @param \WP_REST_Server   $server   Server.
	 * @param \WP_REST_Request  $request  Request.
	 * @return \WP_REST_Response
	 */
	public static function store_api_names( $response, $server, $request ) {
		if ( ! $response instanceof \WP_REST_Response || 0 !== strpos( $request->get_route(), '/wc/store/v1/products/reviews' ) ) {
			return $response;
		}
		$data = $response->get_data();
		if ( is_array( $data ) ) {
			foreach ( $data as $i => $item ) {
				if ( is_array( $item ) && isset( $item['reviewer'] ) ) {
					$data[ $i ]['reviewer'] = self::display_name( $item['reviewer'] );
				}
				// Same typography as the server render (curly quotes, dashes).
				if ( is_array( $item ) && isset( $item['review'] ) ) {
					$data[ $i ]['review'] = wptexturize( $item['review'] );
				}
			}
			$response->set_data( $data );
		}
		return $response;
	}

	/**
	 * The store script module (`viewScriptModule` of the block).
	 *
	 * @return void
	 */
	public static function register_store() {
		$build = dirname( __DIR__ ) . '/build/store/';
		if ( ! file_exists( $build . 'index.asset.php' ) ) {
			return;
		}
		$asset = require $build . 'index.asset.php';
		wp_register_script_module(
			self::MODULE,
			plugins_url( 'build/store/index.js', dirname( __DIR__ ) . '/module.php' ),
			$asset['dependencies'],
			$asset['version']
		);
	}

	/**
	 * Stars of a rating: whole stars full; the last one full from .5, half
	 * from .25; nothing for the rest (no empty stars).
	 *
	 * @param float $rating Rating, 0 to 5.
	 * @return string[] 'full' or 'half' per star.
	 */
	public static function stars( $rating ) {
		$stars = array();
		for ( $i = 1; $i <= 5; $i++ ) {
			$left = (float) $rating - ( $i - 1 );
			if ( $left >= 0.5 ) {
				$stars[] = 'full';
			} elseif ( $left >= 0.25 ) {
				$stars[] = 'half';
			}
		}
		return $stars;
	}

	/**
	 * A review as the block and the Store API mapping in the store use it.
	 *
	 * @param \WP_Comment $comment Review.
	 * @return array
	 */
	public static function item( $comment ) {
		$rating = (int) get_comment_meta( $comment->comment_ID, 'rating', true );
		return array(
			'id'       => (int) $comment->comment_ID,
			'author'   => self::display_name( $comment->comment_author ),
			'rating'   => $rating,
			'stars'    => self::stars( $rating ),
			'verified' => function_exists( 'wc_review_is_from_verified_owner' ) && wc_review_is_from_verified_owner( $comment->comment_ID ),
			'date'     => date_i18n( get_option( 'date_format' ), strtotime( $comment->comment_date ) ),
			'datetime' => gmdate( 'Y-m-d', strtotime( $comment->comment_date ) ),
			'text'     => trim( html_entity_decode( wp_strip_all_tags( wptexturize( $comment->comment_content ) ), ENT_QUOTES, 'UTF-8' ) ),
		);
	}

	/**
	 * The newest approved reviews of a product.
	 *
	 * @param int $product_id Product ID.
	 * @param int $number     How many.
	 * @return array[]
	 */
	public static function latest( $product_id, $number ) {
		$comments = get_comments(
			array(
				'post_id' => $product_id,
				'status'  => 'approve',
				'type'    => 'review',
				'number'  => $number,
				'orderby' => 'comment_date_gmt',
				'order'   => 'DESC',
			)
		);
		return array_map( array( __CLASS__, 'item' ), $comments );
	}

	/**
	 * Render the block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public static function render( array $attributes ) {
		return render_block(
			array(
				'blockName'    => 'profchoice/reviews',
				'attrs'        => $attributes,
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array(),
			)
		);
	}

	/**
	 * `[profchoice_reviews product="123" collapsed="true" class=""]`.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'product'   => 0,
				'collapsed' => 'false',
				'class'     => '',
			),
			$atts,
			'profchoice_reviews'
		);
		$attributes = array(
			'productId' => absint( $atts['product'] ),
			'collapsed' => 'true' === $atts['collapsed'],
		);
		if ( '' !== $atts['class'] ) {
			$attributes['className'] = sanitize_text_field( $atts['class'] );
		}
		return self::render( $attributes );
	}

	/**
	 * `do_action( 'profchoicecore_reviews', $product_id, array( 'collapsed' => true ) )`.
	 *
	 * @param int   $product_id Product ID.
	 * @param array $attributes Extra block attributes.
	 * @return void
	 */
	public static function action( $product_id, $attributes = array() ) {
		echo self::render( array_merge( (array) $attributes, array( 'productId' => (int) $product_id ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
