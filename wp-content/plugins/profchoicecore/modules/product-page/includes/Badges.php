<?php
/**
 * Product badges as product tags: "Sale" follows is_on_sale(), "New" the
 * first N days after publishing, and "Pro Pick" a checkbox in the product
 * data header. Badges show in the tags' order (Products > Tags), with each
 * tag's optional text and background colors as CSS variables.
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore;

/**
 * Badge tags and their sync.
 */
final class Badges {

	const SALE     = 'sale';
	const NEW      = 'new';
	const PRO_PICK = 'pro-pick';
	const DAYS     = 'profchoice_new_days';
	const CRON     = 'profchoicecore_badges_daily';

	/**
	 * Tag color meta keys => CSS variables.
	 *
	 * @var array<string, string>
	 */
	const COLORS = array(
		'color'            => '--badge-color',
		'background_color' => '--badge-background-color',
	);

	/**
	 * Products waiting for a sync at the end of the request.
	 *
	 * @var array<int, true>
	 */
	private static $queue = array();

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'init', array( __CLASS__, 'setup' ), 20 );
		add_filter( 'woocommerce_sortable_taxonomies', array( __CLASS__, 'sortable' ) );

		add_filter( 'product_type_options', array( __CLASS__, 'type_option' ) );
		add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'save_pro_pick' ) );

		add_action( 'woocommerce_new_product', array( __CLASS__, 'enqueue' ) );
		add_action( 'woocommerce_update_product', array( __CLASS__, 'enqueue' ) );
		add_action( 'woocommerce_new_product_variation', array( __CLASS__, 'enqueue_parent' ) );
		add_action( 'woocommerce_update_product_variation', array( __CLASS__, 'enqueue_parent' ) );
		add_action( 'transition_post_status', array( __CLASS__, 'published' ), 10, 3 );
		add_action( 'wc_after_products_starting_sales', array( __CLASS__, 'enqueue_many' ) );
		add_action( 'wc_after_products_ending_sales', array( __CLASS__, 'enqueue_many' ) );
		add_action( 'shutdown', array( __CLASS__, 'flush' ) );

		add_action( self::CRON, array( __CLASS__, 'daily' ) );
		add_filter( 'woocommerce_products_general_settings', array( __CLASS__, 'settings' ) );
		add_action( 'product_tag_add_form_fields', array( __CLASS__, 'add_color_fields' ) );
		add_action( 'product_tag_edit_form_fields', array( __CLASS__, 'edit_color_fields' ) );
		add_action( 'created_product_tag', array( __CLASS__, 'save_colors' ) );
		add_action( 'edited_product_tag', array( __CLASS__, 'save_colors' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'color_picker' ) );

		add_action( 'add_option_' . self::DAYS, array( __CLASS__, 'daily' ) );
		add_action( 'update_option_' . self::DAYS, array( __CLASS__, 'daily' ) );
		if ( defined( 'PROFCHOICECORE_FILE' ) ) {
			register_deactivation_hook( PROFCHOICECORE_FILE, array( __CLASS__, 'unschedule' ) );
		}
	}

	/**
	 * Stop the daily pass.
	 *
	 * @return void
	 */
	public static function unschedule() {
		wp_clear_scheduled_hook( self::CRON );
	}

	/**
	 * Badge tags, slug => name, in their default order.
	 *
	 * @return array<string, string>
	 */
	public static function tags() {
		/**
		 * Filters the product tags shown as badges.
		 *
		 * @param array<string, string> $tags Slug => name.
		 */
		return (array) apply_filters(
			'profchoicecore_badge_tags',
			array(
				self::SALE     => __( 'Sale', 'profchoicecore' ),
				self::PRO_PICK => __( 'Pro Pick', 'profchoicecore' ),
				self::NEW      => __( 'New', 'profchoicecore' ),
			)
		);
	}

	/**
	 * Days a product is "New" after publishing (0 turns it off).
	 *
	 * @return int
	 */
	public static function new_days() {
		/**
		 * Filters how many days a product is "New".
		 *
		 * @param int $days From WooCommerce > Settings > Products.
		 */
		return absint( apply_filters( 'profchoicecore_new_days', absint( get_option( self::DAYS, 30 ) ) ) );
	}

	/**
	 * The product's badges, slug => name, in the tags' order.
	 *
	 * @param int $product_id Product ID.
	 * @return array<string, string>
	 */
	public static function for_product( $product_id ) {
		$tags  = self::tags();
		$terms = wp_get_post_terms( $product_id, 'product_tag' );
		if ( is_wp_error( $terms ) ) {
			return array();
		}
		$terms = array_filter(
			$terms,
			static function ( $term ) use ( $tags ) {
				return isset( $tags[ $term->slug ] );
			}
		);
		$slugs = array_keys( $tags );
		usort(
			$terms,
			static function ( $a, $b ) use ( $slugs ) {
				$order = (int) get_term_meta( $a->term_id, 'order', true ) - (int) get_term_meta( $b->term_id, 'order', true );
				return $order ? $order : array_search( $a->slug, $slugs, true ) - array_search( $b->slug, $slugs, true );
			}
		);
		return wp_list_pluck( $terms, 'name', 'slug' );
	}

	/**
	 * A tag's colors as CSS variables for its badge (empty when it has
	 * none, so the theme's colors apply).
	 *
	 * @param string $slug Tag slug.
	 * @return string E.g. "--badge-color: #b71c1c;".
	 */
	public static function style( $slug ) {
		$term = get_term_by( 'slug', $slug, 'product_tag' );
		if ( ! $term ) {
			return '';
		}
		$style = '';
		foreach ( self::COLORS as $key => $var ) {
			$color = sanitize_hex_color( (string) get_term_meta( $term->term_id, $key, true ) );
			if ( $color ) {
				$style .= $var . ': ' . $color . ';';
			}
		}
		return $style;
	}

	/**
	 * Color field labels.
	 *
	 * @return array<string, array{0: string, 1: string}> Meta key => label, help.
	 */
	private static function color_fields() {
		return array(
			'color'            => array( __( 'Badge text color', 'profchoicecore' ), __( 'Empty uses the theme\'s color.', 'profchoicecore' ) ),
			'background_color' => array( __( 'Badge background color', 'profchoicecore' ), __( 'Empty uses the theme\'s background.', 'profchoicecore' ) ),
		);
	}

	/**
	 * Color fields on Products > Tags (add form).
	 *
	 * @return void
	 */
	public static function add_color_fields() {
		foreach ( self::color_fields() as $key => $field ) {
			?>
			<div class="form-field term-<?php echo esc_attr( $key ); ?>-wrap">
				<label for="profchoice_tag_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field[0] ); ?></label>
				<input type="text" class="pc-color-field" id="profchoice_tag_<?php echo esc_attr( $key ); ?>" name="profchoice_tag[<?php echo esc_attr( $key ); ?>]" value="" />
				<p><?php echo esc_html( $field[1] ); ?></p>
			</div>
			<?php
		}
	}

	/**
	 * Color fields on the tag's edit screen.
	 *
	 * @param \WP_Term $term Tag.
	 * @return void
	 */
	public static function edit_color_fields( $term ) {
		foreach ( self::color_fields() as $key => $field ) {
			?>
			<tr class="form-field term-<?php echo esc_attr( $key ); ?>-wrap">
				<th scope="row"><label for="profchoice_tag_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field[0] ); ?></label></th>
				<td>
					<input type="text" class="pc-color-field" id="profchoice_tag_<?php echo esc_attr( $key ); ?>" name="profchoice_tag[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( (string) get_term_meta( $term->term_id, $key, true ) ); ?>" />
					<p class="description"><?php echo esc_html( $field[1] ); ?></p>
				</td>
			</tr>
			<?php
		}
	}

	/**
	 * Save the colors (WordPress checked the tag form's nonce).
	 *
	 * @param int $term_id Tag ID.
	 * @return void
	 */
	public static function save_colors( $term_id ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified by the tag form.
		if ( ! isset( $_POST['profchoice_tag'] ) || ! is_array( $_POST['profchoice_tag'] ) || ! current_user_can( 'manage_product_terms' ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitize_hex_color().
		$colors = wp_unslash( $_POST['profchoice_tag'] );
		foreach ( array_keys( self::COLORS ) as $key ) {
			$color = isset( $colors[ $key ] ) ? sanitize_hex_color( (string) $colors[ $key ] ) : '';
			if ( $color ) {
				update_term_meta( $term_id, $key, $color );
			} else {
				delete_term_meta( $term_id, $key );
			}
		}
	}

	/**
	 * WordPress's color picker on the product tag screens.
	 *
	 * @param string $hook Admin page.
	 * @return void
	 */
	public static function color_picker( $hook ) {
		$screen = get_current_screen();
		if ( ! in_array( $hook, array( 'edit-tags.php', 'term.php' ), true ) || ! $screen || 'product_tag' !== $screen->taxonomy ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_add_inline_script(
			'wp-color-picker',
			'jQuery( function ( $ ) { $( ".pc-color-field" ).wpColorPicker(); $( document ).ajaxComplete( function ( e, xhr, settings ) { if ( settings.data && settings.data.indexOf( "action=add-tag" ) !== -1 ) { $( ".pc-color-field" ).wpColorPicker( "color", "" ); } } ); } );'
		);
	}

	/**
	 * Create the badge tags, migrate the old badge text and schedule the
	 * daily pass.
	 *
	 * @return void
	 */
	public static function setup() {
		if ( ! taxonomy_exists( 'product_tag' ) ) {
			return;
		}
		$order = 0;
		foreach ( self::tags() as $slug => $name ) {
			if ( ! get_term_by( 'slug', $slug, 'product_tag' ) ) {
				$term = wp_insert_term( $name, 'product_tag', array( 'slug' => $slug ) );
				if ( ! is_wp_error( $term ) ) {
					update_term_meta( $term['term_id'], 'order', $order );
				}
			}
			++$order;
		}
		if ( ! get_option( 'profchoice_badges_migrated' ) ) {
			self::migrate();
		}
		if ( ! wp_next_scheduled( self::CRON ) ) {
			wp_schedule_event( strtotime( 'tomorrow' ) + HOUR_IN_SECONDS, 'daily', self::CRON );
		}
	}

	/**
	 * The old free-text badge (`_profchoice_badge`): "Pro Pick" becomes the
	 * tag, Sale and New are computed now. The meta is removed.
	 *
	 * @return void
	 */
	private static function migrate() {
		update_option( 'profchoice_badges_migrated', 1, false );
		$ids = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_profchoice_badge', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_key
			)
		);
		foreach ( $ids as $id ) {
			if ( self::PRO_PICK === sanitize_title( get_post_meta( $id, '_profchoice_badge', true ) ) ) {
				wp_add_object_terms( $id, self::PRO_PICK, 'product_tag' );
			}
			delete_post_meta( $id, '_profchoice_badge' );
		}
		self::daily();
	}

	/**
	 * Product tags get WooCommerce's drag-and-drop order.
	 *
	 * @param array $taxonomies Sortable taxonomies.
	 * @return array
	 */
	public static function sortable( $taxonomies ) {
		$taxonomies[] = 'product_tag';
		return $taxonomies;
	}

	/**
	 * "Pro Pick" next to Virtual and Downloadable.
	 *
	 * @param array $options Product type options.
	 * @return array
	 */
	public static function type_option( $options ) {
		global $post;
		$options['pro_pick'] = array(
			'id'            => '_pro_pick',
			'wrapper_class' => 'show_if_simple show_if_variable show_if_grouped show_if_external',
			'label'         => __( 'Pro Pick', 'profchoicecore' ),
			'description'   => __( 'Shows the Pro Pick badge (the "Pro Pick" product tag).', 'profchoicecore' ),
			'default'       => $post && has_term( self::PRO_PICK, 'product_tag', $post ) ? 'yes' : 'no',
		);
		return $options;
	}

	/**
	 * Save the checkbox as the tag.
	 *
	 * @param \WC_Product $product Product being saved.
	 * @return void
	 */
	public static function save_pro_pick( $product ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verified the product save.
		if ( isset( $_POST['_pro_pick'] ) ) {
			wp_add_object_terms( $product->get_id(), self::PRO_PICK, 'product_tag' );
		} else {
			wp_remove_object_terms( $product->get_id(), self::PRO_PICK, 'product_tag' );
		}
	}

	/**
	 * Sync a product at the end of the request (after WooCommerce syncs
	 * variable prices).
	 *
	 * @param int $product_id Product ID.
	 * @return void
	 */
	public static function enqueue( $product_id ) {
		self::$queue[ absint( $product_id ) ] = true;
	}

	/**
	 * Sync a variation's parent.
	 *
	 * @param int $variation_id Variation ID.
	 * @return void
	 */
	public static function enqueue_parent( $variation_id ) {
		$parent = wp_get_post_parent_id( $variation_id );
		if ( $parent ) {
			self::enqueue( $parent );
		}
	}

	/**
	 * Sync products whose scheduled sale started or ended (variations
	 * resolve to their parent).
	 *
	 * @param int[] $ids Product or variation IDs.
	 * @return void
	 */
	public static function enqueue_many( $ids ) {
		foreach ( (array) $ids as $id ) {
			$parent = wp_get_post_parent_id( $id );
			self::enqueue( $parent ? $parent : $id );
		}
		self::flush();
	}

	/**
	 * A product was published.
	 *
	 * @param string   $new_status New status.
	 * @param string   $old_status Old status.
	 * @param \WP_Post $post       Post.
	 * @return void
	 */
	public static function published( $new_status, $old_status, $post ) {
		if ( 'product' === $post->post_type && 'publish' === $new_status && 'publish' !== $old_status ) {
			self::enqueue( $post->ID );
		}
	}

	/**
	 * Sync the queued products.
	 *
	 * @return void
	 */
	public static function flush() {
		$ids         = array_keys( self::$queue );
		self::$queue = array();
		foreach ( $ids as $id ) {
			self::sync( $id );
		}
	}

	/**
	 * Set the Sale and New tags from the product's state.
	 *
	 * @param int $product_id Product ID.
	 * @return void
	 */
	public static function sync( $product_id ) {
		$product = wc_get_product( $product_id );
		if ( ! $product || $product->is_type( 'variation' ) ) {
			return;
		}
		self::toggle( $product_id, self::SALE, $product->is_on_sale( 'edit' ) );
		self::toggle( $product_id, self::NEW, self::is_new( $product_id ) );
	}

	/**
	 * Whether a product was published within the "New" days.
	 *
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	private static function is_new( $product_id ) {
		$days = self::new_days();
		$post = get_post( $product_id );
		if ( ! $days || ! $post || 'publish' !== $post->post_status ) {
			return false;
		}
		return strtotime( $post->post_date_gmt . ' UTC' ) > time() - $days * DAY_IN_SECONDS;
	}

	/**
	 * Add or remove a badge tag.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $slug       Tag slug.
	 * @param bool   $on         Whether the product has it.
	 * @return void
	 */
	private static function toggle( $product_id, $slug, $on ) {
		if ( ! isset( self::tags()[ $slug ] ) || has_term( $slug, 'product_tag', $product_id ) === $on ) {
			return;
		}
		if ( $on ) {
			wp_add_object_terms( $product_id, $slug, 'product_tag' );
		} else {
			wp_remove_object_terms( $product_id, $slug, 'product_tag' );
		}
	}

	/**
	 * Daily pass: New expires, recent products get it, ended sales lose
	 * Sale.
	 *
	 * @return void
	 */
	public static function daily() {
		$recent = array();
		$days   = self::new_days();
		if ( $days ) {
			$recent = get_posts(
				array(
					'post_type'      => 'product',
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'date_query'     => array(
						array(
							'column' => 'post_date_gmt',
							'after'  => gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS ),
						),
					),
				)
			);
		}
		$tagged = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_tax_query
					array(
						'taxonomy' => 'product_tag',
						'field'    => 'slug',
						'terms'    => array( self::NEW, self::SALE ),
					),
				),
			)
		);
		foreach ( array_unique( array_merge( $recent, $tagged ) ) as $id ) {
			self::sync( $id );
		}
	}

	/**
	 * "New badge" days in WooCommerce > Settings > Products.
	 *
	 * @param array $settings General product settings.
	 * @return array
	 */
	public static function settings( $settings ) {
		$field = array(
			'title'             => __( 'New badge', 'profchoicecore' ),
			'desc'              => __( 'days after publishing (0 turns the badge off)', 'profchoicecore' ),
			'id'                => self::DAYS,
			'type'              => 'number',
			'default'           => '30',
			'custom_attributes' => array( 'min' => 0 ),
			'css'               => 'width: 6em;',
		);
		$out   = array();
		$added = false;
		foreach ( $settings as $setting ) {
			if ( ! $added && isset( $setting['type'], $setting['id'] ) && 'sectionend' === $setting['type'] && 'catalog_options' === $setting['id'] ) {
				$out[] = $field;
				$added = true;
			}
			$out[] = $setting;
		}
		if ( ! $added ) {
			$out[] = $field;
		}
		return $out;
	}
}
