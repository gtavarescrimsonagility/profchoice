<?php
/**
 * Wishlist data: lists are private `pc_wishlist` posts owned by a user, or
 * by a guest session token (cookie) when guests are allowed.
 *
 * @package ProfChoiceCore
 */

namespace ProfChoiceCore\Wishlist;

/**
 * Lists, items, guest sessions and per-product counts.
 */
final class Lists {

	const POST_TYPE = 'pc_wishlist';
	const COOKIE    = 'pc_wishlist_session';
	const ITEMS     = '_pc_items';
	const IS_DEFAULT = '_pc_default';
	const SESSION   = '_pc_session';
	const COUNT     = '_pc_wishlist_count';
	const CRON      = 'profchoicecore_wishlist_cleanup';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'wp_login', array( __CLASS__, 'merge_on_login' ), 10, 2 );
		add_action( self::CRON, array( __CLASS__, 'cleanup' ) );
		add_action(
			'init',
			static function () {
				if ( ! wp_next_scheduled( self::CRON ) ) {
					wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON );
				}
			}
		);
	}

	/**
	 * The lists post type: private, no screens of its own.
	 *
	 * @return void
	 */
	public static function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'label'           => __( 'Wishlists', 'profchoicecore' ),
				'public'          => false,
				'show_ui'         => false,
				'show_in_rest'    => false,
				'supports'        => array( 'title', 'author' ),
				'capability_type' => 'post',
				'rewrite'         => false,
				'query_var'       => false,
			)
		);
	}

	/**
	 * The current visitor as an owner: `array( 'user' => id )`, or
	 * `array( 'session' => token )` for a guest with a session, or null.
	 *
	 * @param bool $create Start a guest session (sets the cookie) when needed.
	 * @return array|null
	 */
	public static function current_owner( $create = false ) {
		$user_id = get_current_user_id();
		if ( $user_id ) {
			return array( 'user' => $user_id );
		}
		if ( ! Settings::guests_allowed() ) {
			return null;
		}
		$token = self::session_token();
		if ( ! $token && $create ) {
			$token = bin2hex( random_bytes( 16 ) );
			if ( ! headers_sent() ) {
				setcookie(
					self::COOKIE,
					$token,
					array(
						'expires'  => time() + Settings::retention_days() * DAY_IN_SECONDS,
						'path'     => COOKIEPATH ? COOKIEPATH : '/',
						'domain'   => COOKIE_DOMAIN,
						'secure'   => is_ssl(),
						'httponly' => true,
						'samesite' => 'Lax',
					)
				);
			}
			$_COOKIE[ self::COOKIE ] = $token;
		}
		return $token ? array( 'session' => $token ) : null;
	}

	/**
	 * The guest session token from the cookie, if valid.
	 *
	 * @return string
	 */
	public static function session_token() {
		$token = isset( $_COOKIE[ self::COOKIE ] ) ? sanitize_key( wp_unslash( $_COOKIE[ self::COOKIE ] ) ) : '';
		return preg_match( '/^[a-f0-9]{32}$/', $token ) ? $token : '';
	}

	/**
	 * Lists of an owner, the default one first.
	 *
	 * @param array|null $owner Owner.
	 * @return \WP_Post[]
	 */
	public static function lists( $owner ) {
		if ( ! $owner ) {
			return array();
		}
		$args = array(
			'post_type'      => self::POST_TYPE,
			'post_status'    => 'private',
			'posts_per_page' => 100,
			'orderby'        => 'date',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		);
		if ( isset( $owner['user'] ) ) {
			$args['author'] = (int) $owner['user'];
		} else {
			$args['author']     = 0;
			$args['meta_key']   = self::SESSION; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_key
			$args['meta_value'] = $owner['session']; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_value
		}
		$lists = get_posts( $args );
		usort(
			$lists,
			static function ( $a, $b ) {
				return (int) self::is_default( $b->ID ) - (int) self::is_default( $a->ID );
			}
		);
		return $lists;
	}

	/**
	 * Whether a list is its owner's default.
	 *
	 * @param int $list_id List ID.
	 * @return bool
	 */
	public static function is_default( $list_id ) {
		return (bool) get_post_meta( $list_id, self::IS_DEFAULT, true );
	}

	/**
	 * The owner's default list, created ("Wishlist") when asked.
	 *
	 * @param array|null $owner  Owner.
	 * @param bool       $create Create it when missing.
	 * @return \WP_Post|null
	 */
	public static function default_list( $owner, $create = false ) {
		$lists = self::lists( $owner );
		if ( $lists ) {
			return $lists[0];
		}
		if ( ! $create || ! $owner ) {
			return null;
		}
		$id = self::create( $owner, __( 'Wishlist', 'profchoicecore' ) );
		return $id ? get_post( $id ) : null;
	}

	/**
	 * Create a list. The owner's first list is the default.
	 *
	 * @param array  $owner Owner.
	 * @param string $name  List name.
	 * @return int List ID, or 0.
	 */
	public static function create( $owner, $name ) {
		$first = ! self::lists( $owner );
		$id    = wp_insert_post(
			array(
				'post_type'   => self::POST_TYPE,
				'post_status' => 'private',
				'post_title'  => sanitize_text_field( $name ) ? sanitize_text_field( $name ) : __( 'Wishlist', 'profchoicecore' ),
				'post_author' => isset( $owner['user'] ) ? (int) $owner['user'] : 0,
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			return 0;
		}
		update_post_meta( $id, self::ITEMS, array() );
		if ( isset( $owner['session'] ) ) {
			update_post_meta( $id, self::SESSION, $owner['session'] );
		}
		if ( $first ) {
			update_post_meta( $id, self::IS_DEFAULT, 1 );
		}
		/**
		 * Fires after a wishlist is created.
		 *
		 * @param int   $id    List ID.
		 * @param array $owner Owner (user or session).
		 */
		do_action( 'profchoicecore_wishlist_list_created', $id, $owner );
		return (int) $id;
	}

	/**
	 * Whether the owner owns a list.
	 *
	 * @param int        $list_id List ID.
	 * @param array|null $owner   Owner.
	 * @return bool
	 */
	public static function owns( $list_id, $owner ) {
		$post = get_post( $list_id );
		if ( ! $owner || ! $post || self::POST_TYPE !== $post->post_type ) {
			return false;
		}
		if ( isset( $owner['user'] ) ) {
			return (int) $post->post_author === (int) $owner['user'];
		}
		return 0 === (int) $post->post_author && hash_equals( (string) get_post_meta( $list_id, self::SESSION, true ), $owner['session'] );
	}

	/**
	 * Rename a list.
	 *
	 * @param int    $list_id List ID.
	 * @param string $name    New name.
	 * @return void
	 */
	public static function rename( $list_id, $name ) {
		$name = sanitize_text_field( $name );
		if ( '' !== $name ) {
			wp_update_post(
				array(
					'ID'         => $list_id,
					'post_title' => $name,
				)
			);
		}
	}

	/**
	 * Make a list its owner's default.
	 *
	 * @param int   $list_id List ID.
	 * @param array $owner   Owner.
	 * @return void
	 */
	public static function set_default( $list_id, $owner ) {
		foreach ( self::lists( $owner ) as $list ) {
			delete_post_meta( $list->ID, self::IS_DEFAULT );
		}
		update_post_meta( $list_id, self::IS_DEFAULT, 1 );
	}

	/**
	 * Delete a list (not the owner's last one); the next becomes default.
	 *
	 * @param int   $list_id List ID.
	 * @param array $owner   Owner.
	 * @return bool
	 */
	public static function delete( $list_id, $owner ) {
		$lists = self::lists( $owner );
		if ( count( $lists ) < 2 ) {
			return false;
		}
		$was_default = self::is_default( $list_id );
		foreach ( self::items( $list_id ) as $item ) {
			self::bump_count( $item['product_id'], -1 );
		}
		wp_delete_post( $list_id, true );
		/**
		 * Fires after a wishlist is deleted.
		 *
		 * @param int   $list_id List ID.
		 * @param array $owner   Owner.
		 */
		do_action( 'profchoicecore_wishlist_list_deleted', $list_id, $owner );
		if ( $was_default ) {
			$rest = self::lists( $owner );
			if ( $rest ) {
				update_post_meta( $rest[0]->ID, self::IS_DEFAULT, 1 );
			}
		}
		return true;
	}

	/**
	 * Items of a list.
	 *
	 * @param int $list_id List ID.
	 * @return array<int, array{product_id: int, variation_id: int, added: int}>
	 */
	public static function items( $list_id ) {
		$items = get_post_meta( $list_id, self::ITEMS, true );
		return is_array( $items ) ? array_values( $items ) : array();
	}

	/**
	 * Add a product (and variation) to a list; nothing when it is there.
	 *
	 * @param int $list_id      List ID.
	 * @param int $product_id   Product ID.
	 * @param int $variation_id Variation ID or 0.
	 * @return bool Whether it was added.
	 */
	public static function add_item( $list_id, $product_id, $variation_id = 0 ) {
		$items = self::items( $list_id );
		foreach ( $items as $item ) {
			if ( (int) $item['product_id'] === (int) $product_id ) {
				return false;
			}
		}
		$items[] = array(
			'product_id'   => (int) $product_id,
			'variation_id' => (int) $variation_id,
			'added'        => time(),
		);
		update_post_meta( $list_id, self::ITEMS, $items );
		self::touch( $list_id );
		self::bump_count( $product_id, 1 );
		/**
		 * Fires after a product is added to a wishlist.
		 *
		 * @param int $product_id   Product ID.
		 * @param int $list_id      List ID.
		 * @param int $user_id      User ID (0 for guests).
		 * @param int $variation_id Variation ID or 0.
		 */
		do_action( 'profchoicecore_wishlist_item_added', (int) $product_id, (int) $list_id, get_current_user_id(), (int) $variation_id );
		return true;
	}

	/**
	 * Remove a product from a list.
	 *
	 * @param int $list_id    List ID.
	 * @param int $product_id Product ID.
	 * @return bool Whether it was removed.
	 */
	public static function remove_item( $list_id, $product_id ) {
		$items = self::items( $list_id );
		$kept  = array_values(
			array_filter(
				$items,
				static function ( $item ) use ( $product_id ) {
					return (int) $item['product_id'] !== (int) $product_id;
				}
			)
		);
		if ( count( $kept ) === count( $items ) ) {
			return false;
		}
		update_post_meta( $list_id, self::ITEMS, $kept );
		self::touch( $list_id );
		self::bump_count( $product_id, -1 );
		/**
		 * Fires after a product is removed from a wishlist.
		 *
		 * @param int $product_id Product ID.
		 * @param int $list_id    List ID.
		 * @param int $user_id    User ID (0 for guests).
		 */
		do_action( 'profchoicecore_wishlist_item_removed', (int) $product_id, (int) $list_id, get_current_user_id() );
		return true;
	}

	/**
	 * Move a product from one list to another of the same owner.
	 *
	 * @param int $from       Source list ID.
	 * @param int $to         Target list ID.
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	public static function move_item( $from, $to, $product_id ) {
		$item = null;
		foreach ( self::items( $from ) as $candidate ) {
			if ( (int) $candidate['product_id'] === (int) $product_id ) {
				$item = $candidate;
			}
		}
		if ( ! $item || $from === $to ) {
			return false;
		}
		self::remove_item( $from, $product_id );
		self::add_item( $to, $product_id, (int) $item['variation_id'] );
		return true;
	}

	/**
	 * Products saved in any of the owner's lists.
	 *
	 * @param array|null $owner Owner.
	 * @return int[]
	 */
	public static function saved_products( $owner ) {
		$ids = array();
		foreach ( self::lists( $owner ) as $list ) {
			foreach ( self::items( $list->ID ) as $item ) {
				$ids[] = (int) $item['product_id'];
			}
		}
		return array_values( array_unique( $ids ) );
	}

	/**
	 * Add to the default list, or remove from every list when saved.
	 *
	 * @param array $owner        Owner.
	 * @param int   $product_id   Product ID.
	 * @param int   $variation_id Variation ID or 0.
	 * @return bool Whether the product is saved now.
	 */
	public static function toggle( $owner, $product_id, $variation_id = 0 ) {
		if ( in_array( (int) $product_id, self::saved_products( $owner ), true ) ) {
			foreach ( self::lists( $owner ) as $list ) {
				self::remove_item( $list->ID, $product_id );
			}
			return false;
		}
		$list = self::default_list( $owner, true );
		if ( $list ) {
			self::add_item( $list->ID, $product_id, $variation_id );
		}
		return (bool) $list;
	}

	/**
	 * Guest lists move to the user who logs in; the guest default list
	 * merges into the user's default list.
	 *
	 * @param string   $login User login.
	 * @param \WP_User $user  User.
	 * @return void
	 */
	public static function merge_on_login( $login, $user ) {
		$token = self::session_token();
		if ( ! $token || ! $user instanceof \WP_User ) {
			return;
		}
		$guest = array( 'session' => $token );
		$owner = array( 'user' => $user->ID );
		$lists = self::lists( $guest );
		$into  = self::lists( $owner ) ? self::default_list( $owner ) : null;

		foreach ( $lists as $list ) {
			if ( $into && self::is_default( $list->ID ) ) {
				foreach ( self::items( $list->ID ) as $item ) {
					self::add_item( $into->ID, $item['product_id'], (int) $item['variation_id'] );
					self::bump_count( $item['product_id'], -1 );
				}
				wp_delete_post( $list->ID, true );
				continue;
			}
			wp_update_post(
				array(
					'ID'          => $list->ID,
					'post_author' => $user->ID,
				)
			);
			delete_post_meta( $list->ID, self::SESSION );
			if ( $into ) {
				delete_post_meta( $list->ID, self::IS_DEFAULT );
			}
		}

		unset( $_COOKIE[ self::COOKIE ] );
		if ( ! headers_sent() ) {
			setcookie( self::COOKIE, '', time() - YEAR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN );
		}
	}

	/**
	 * Remove guest lists not updated within the retention setting.
	 *
	 * @return void
	 */
	public static function cleanup() {
		$old = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'private',
				'author'         => 0,
				'posts_per_page' => 200,
				'fields'         => 'ids',
				'date_query'     => array(
					array(
						'column' => 'post_modified_gmt',
						'before' => gmdate( 'Y-m-d H:i:s', time() - Settings::retention_days() * DAY_IN_SECONDS ),
					),
				),
			)
		);
		foreach ( $old as $list_id ) {
			foreach ( self::items( $list_id ) as $item ) {
				self::bump_count( $item['product_id'], -1 );
			}
			wp_delete_post( $list_id, true );
		}
	}

	/**
	 * Mark a list as updated (for sorting and guest retention).
	 *
	 * @param int $list_id List ID.
	 * @return void
	 */
	private static function touch( $list_id ) {
		wp_update_post( array( 'ID' => $list_id ) );
	}

	/**
	 * Change a product's wishlist count (admin column).
	 *
	 * @param int $product_id Product ID.
	 * @param int $delta      +1 or -1.
	 * @return void
	 */
	private static function bump_count( $product_id, $delta ) {
		$count = max( 0, (int) get_post_meta( $product_id, self::COUNT, true ) + $delta );
		update_post_meta( $product_id, self::COUNT, $count );
	}
}
