<?php
/**
 * Wishlist front end: the Interactivity state shared by the blocks, the
 * "Saved to" dialog, shortcodes and template helpers.
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore\Wishlist;

/**
 * Public API of the wishlist module.
 */
final class Wishlist {

	const STORE  = 'profchoice/wishlist';
	const MODULE = 'profchoice-wishlist-store';

	/**
	 * Whether the dialog must be printed in the footer.
	 *
	 * @var bool
	 */
	private static $needs_dialog = false;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'init', array( __CLASS__, 'register_store' ), 5 );
		add_action( 'wp_footer', array( __CLASS__, 'print_dialog' ), 5 );
		add_shortcode( 'profchoice_wishlist', array( __CLASS__, 'shortcode_page' ) );
		add_shortcode( 'profchoice_wishlist_button', array( __CLASS__, 'shortcode_button' ) );
		add_shortcode( 'profchoice_wishlist_link', array( __CLASS__, 'shortcode_link' ) );
	}

	/**
	 * The store script module the blocks share (`viewScriptModule` in their
	 * block.json), built from src/store.
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
	 * The visitor's lists and saved products.
	 *
	 * @param array|null $owner Owner (default: the current visitor).
	 * @return array
	 */
	public static function state( $owner = null ) {
		$owner = null === $owner ? Lists::current_owner() : $owner;
		$lists = array();
		$where = array();
		foreach ( Lists::lists( $owner ) as $list ) {
			foreach ( Lists::items( $list->ID ) as $item ) {
				if ( ! isset( $where[ (int) $item['product_id'] ] ) ) {
					$where[ (int) $item['product_id'] ] = $list->ID;
				}
			}
			$lists[] = array(
				'id'        => $list->ID,
				'name'      => $list->post_title,
				'isDefault' => Lists::is_default( $list->ID ),
				'count'     => count( Lists::items( $list->ID ) ),
			);
		}
		$saved = Lists::saved_products( $owner );
		return array(
			'saved' => $saved,
			'count' => count( $saved ),
			'lists' => $lists,
			// Product ID => the list it is in (the first, when in several).
			'where' => (object) $where,
		);
	}

	/**
	 * Set the store's server state (once per request) and enqueue it.
	 *
	 * @return void
	 */
	public static function prepare() {
		static $done = false;
		if ( $done ) {
			return;
		}
		$done = true;

		$current = '';
		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$current = home_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
		}
		$login = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url();

		wp_interactivity_state(
			self::STORE,
			array_merge(
				self::state(),
				array(
					'restUrl'    => rest_url( Rest::NAMESPACE_ . '/' ),
					'nonce'      => wp_create_nonce( 'wp_rest' ),
					'canUse'     => is_user_logged_in() || Settings::guests_allowed(),
					'loginUrl'   => add_query_arg( 'redirect_to', rawurlencode( $current ), $login ),
					'pageUrl'    => Settings::page_url(),
					'multiple'   => Settings::multiple_lists(),
					'showNotice' => Settings::show_notice(),
					'dialog'     => array(
						'productId'   => 0,
						'productName' => '',
						'listId'      => 0,
					),
					'notices'    => array(
						'added'   => self::notice_html( 'success', __( 'Added to your wishlist.', 'profchoicecore' ), true ),
						'removed' => self::notice_html( 'info', __( 'Removed from your wishlist.', 'profchoicecore' ), false ),
						'error'   => self::notice_html( 'danger', __( 'The wishlist could not be updated. Please try again.', 'profchoicecore' ), false ),
					),
					'i18n'       => array(
						/* translators: %s: product name. */
						'add'     => __( 'Add %s to wishlist', 'profchoicecore' ),
						/* translators: %s: product name. */
						'remove'  => __( 'Remove %s from wishlist', 'profchoicecore' ),
						/* translators: %d: number of saved products. */
						'link'    => __( 'Wishlist, %d items', 'profchoicecore' ),
						'confirm' => __( 'Delete this list and its items?', 'profchoicecore' ),
					),
				)
			)
		);
		// Derived state for server-side directive processing (src/store mirrors it).
		$saved   = static function () {
			$context = wp_interactivity_get_context( self::STORE );
			$state   = wp_interactivity_state( self::STORE );
			return isset( $context['productId'] ) && in_array( (int) $context['productId'], (array) $state['saved'], true );
		};
		$i18n    = wp_interactivity_state( self::STORE )['i18n'];
		wp_interactivity_state(
			self::STORE,
			array(
				'isSaved'        => $saved,
				'buttonLabel'    => static function () use ( $saved, $i18n ) {
					$context = wp_interactivity_get_context( self::STORE );
					return sprintf( $saved() ? $i18n['remove'] : $i18n['add'], isset( $context['productName'] ) ? $context['productName'] : '' );
				},
				'linkLabel'      => static function () use ( $i18n ) {
					return sprintf( $i18n['link'], (int) wp_interactivity_state( self::STORE )['count'] );
				},
				'countDisplay'   => static function () {
					return wp_interactivity_state( self::STORE )['count'] ? '' : 'none';
				},
				'outlineDisplay' => static function () use ( $saved ) {
					return $saved() ? 'none' : '';
				},
				'filledDisplay'  => static function () use ( $saved ) {
					return $saved() ? '' : 'none';
				},
				// Closures are not sent to the client, so the store's getters keep working.
				'dialogListName' => static function () {
					return '';
				},
				'isDialogList'   => static function () {
					return false;
				},
			)
		);
		wp_enqueue_script_module( self::MODULE );
		wp_enqueue_style( 'profchoice-wishlist-button-style' );
	}

	/**
	 * Alert markup for the store's confirmations (WooCommerce notice classes,
	 * so the theme's alert styles apply).
	 *
	 * @param string $type     success, info or danger.
	 * @param string $message  Text.
	 * @param bool   $has_link With a "View wishlist" link.
	 * @return string
	 */
	private static function notice_html( $type, $message, $has_link ) {
		$classes = array(
			'success' => 'woocommerce-message',
			'info'    => 'woocommerce-info',
			'danger'  => 'woocommerce-error',
		);
		$icons   = array(
			'success' => 'check-circle',
			'info'    => 'info-circle',
			'danger'  => 'alert-circle',
		);
		$icon    = function_exists( 'wp_get_icon' ) ? trim( wp_get_icon( 'profchoice/' . $icons[ $type ], array( 'size' => null, 'class' => 'pc-alert__icon' ) ) ) : '';
		$close   = function_exists( 'wp_get_icon' ) ? trim( wp_get_icon( 'profchoice/close', array( 'size' => null, 'class' => 'pc-alert__close-icon' ) ) ) : '';
		$link    = $has_link ? sprintf( ' <a class="button wc-forward" href="%s">%s</a>', esc_url( Settings::page_url() ), esc_html__( 'View wishlist', 'profchoicecore' ) ) : '';
		return sprintf(
			'<div class="%1$s pc-alert pc-alert--%2$s" role="status">%3$s<div class="pc-alert__body">%4$s%5$s</div><button class="pc-alert__close" type="button" aria-label="%6$s">%7$s</button></div>',
			esc_attr( $classes[ $type ] ),
			esc_attr( $type ),
			$icon,
			esc_html( $message ),
			$link,
			esc_attr__( 'Dismiss', 'profchoicecore' ),
			$close ? $close : '<span aria-hidden="true">&times;</span>'
		);
	}

	/**
	 * Inline SVG of the outline or filled heart: the profchoice icon
	 * collection when present, else the module's own copy.
	 *
	 * @param bool   $filled Filled heart.
	 * @param string $class  Extra class.
	 * @return string
	 */
	public static function heart( $filled, $class = '' ) {
		$name  = $filled ? 'heart-filled' : 'heart';
		$class = trim( 'pc-inline-icon ' . $class );
		if ( function_exists( 'wp_get_icon' ) ) {
			$svg = trim( wp_get_icon( 'profchoice/' . $name, array( 'size' => null, 'class' => $class ) ) );
			if ( '' !== $svg ) {
				return $svg;
			}
		}
		$file = dirname( __DIR__ ) . '/icons/' . $name . '.svg';
		if ( ! is_readable( $file ) ) {
			return '';
		}
		$svg = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		return (string) preg_replace( '#<svg\b#', '<svg aria-hidden="true" focusable="false" class="' . esc_attr( $class ) . '"', trim( $svg ), 1 );
	}

	/**
	 * Mark the dialog as needed (a heart is on the page).
	 *
	 * @return void
	 */
	public static function need_dialog() {
		self::$needs_dialog = true;
	}

	/**
	 * "Saved to" dialog: after a heart saves a product to the default list,
	 * or when a filled heart is clicked, the visitor can move it to another
	 * list or a new one, or remove it.
	 *
	 * @return void
	 */
	public static function print_dialog() {
		if ( ! self::$needs_dialog ) {
			return;
		}
		ob_start();
		require dirname( __DIR__ ) . '/templates/dialog.php';
		echo wp_interactivity_process_directives( (string) ob_get_clean() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Render the heart button block for a product (theme templates).
	 *
	 * @param int   $product_id Product ID.
	 * @param array $attributes Block attributes (e.g. className).
	 * @return string
	 */
	public static function render_button( $product_id, array $attributes = array() ) {
		$attributes = array_merge( array( 'productId' => (int) $product_id ), $attributes );
		/**
		 * Filters the attributes of a wishlist button rendered from code.
		 *
		 * @param array $attributes Block attributes.
		 * @param int   $product_id Product ID.
		 */
		$attributes = apply_filters( 'profchoicecore_wishlist_button_attributes', $attributes, (int) $product_id );
		return render_block(
			array(
				'blockName'    => 'profchoice/wishlist-button',
				'attrs'        => $attributes,
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array(),
			)
		);
	}

	/**
	 * Render the header link block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public static function render_link( array $attributes = array() ) {
		return render_block(
			array(
				'blockName'    => 'profchoice/wishlist-link',
				'attrs'        => $attributes,
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array(),
			)
		);
	}

	/**
	 * Render the wishlist (lists and items) block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public static function render_page( array $attributes = array() ) {
		return render_block(
			array(
				'blockName'    => 'profchoice/wishlist',
				'attrs'        => $attributes,
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array(),
			)
		);
	}

	/**
	 * `[profchoice_wishlist]`.
	 *
	 * @return string
	 */
	public static function shortcode_page() {
		return self::render_page();
	}

	/**
	 * `[profchoice_wishlist_button product="123" class="…"]`.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function shortcode_button( $atts ) {
		$atts = shortcode_atts(
			array(
				'product' => 0,
				'class'   => '',
			),
			$atts,
			'profchoice_wishlist_button'
		);
		$product = $atts['product'] ? absint( $atts['product'] ) : absint( get_the_ID() );
		return self::render_button( $product, $atts['class'] ? array( 'className' => sanitize_text_field( $atts['class'] ) ) : array() );
	}

	/**
	 * `[profchoice_wishlist_link label="false"]`.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function shortcode_link( $atts ) {
		$atts = shortcode_atts(
			array(
				'label' => 'true',
				'class' => '',
			),
			$atts,
			'profchoice_wishlist_link'
		);
		$attributes = array( 'showLabel' => 'false' !== $atts['label'] );
		if ( $atts['class'] ) {
			$attributes['className'] = sanitize_text_field( $atts['class'] );
		}
		return self::render_link( $attributes );
	}
}
