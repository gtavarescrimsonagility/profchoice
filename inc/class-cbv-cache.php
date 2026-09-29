<?php
/**
 * Cache abstraction.
 *
 * Prefers cfwCache (Commercebuild core) when the site runs cfw_core, and
 * falls back to the WordPress object cache otherwise. Never uses transients:
 * commercebuild sites run persistent object caches, and transients would
 * write to the options table.
 *
 * @package Commercebuild_Velocity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Thin cache wrapper with group-level invalidation.
 *
 * @since 0.1.0
 */
class CBV_Cache {

	/**
	 * Cache group for catalog-derived data.
	 *
	 * @var string
	 */
	const GROUP = 'cbv_catalog';

	/**
	 * Default TTL in seconds.
	 *
	 * @var int
	 */
	const TTL = 300;

	/**
	 * Whether cfwCache is available.
	 *
	 * @var bool
	 */
	private $has_cfw_cache;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->has_cfw_cache = class_exists( '\\Commercebuild\\WCCore\\cfwCache' );
	}

	/**
	 * Register invalidation hooks.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'save_post_product', array( $this, 'flush_catalog' ) );
		add_action( 'deleted_post', array( $this, 'flush_catalog' ) );
		add_action( 'edited_term', array( $this, 'flush_catalog' ) );
		add_action( 'created_term', array( $this, 'flush_catalog' ) );
		add_action( 'delete_term', array( $this, 'flush_catalog' ) );
		add_action( 'woocommerce_product_set_stock_status', array( $this, 'flush_catalog' ) );
		add_action( 'woocommerce_product_object_updated_props', array( $this, 'flush_catalog' ) );
	}

	/**
	 * Fetch a cached value, computing it on miss.
	 *
	 * @since 0.1.0
	 *
	 * @param string   $key      Cache key.
	 * @param callable $callback Produces the value on miss.
	 * @param int      $ttl      Optional. TTL in seconds. Default self::TTL.
	 * @return mixed
	 */
	public function remember( $key, $callback, $ttl = self::TTL ) {
		/**
		 * Filter the TTL for a theme cache entry.
		 *
		 * @since 0.3.0
		 *
		 * @param int    $ttl Seconds.
		 * @param string $key Cache key.
		 */
		$ttl = (int) apply_filters( 'cbv_cache_ttl', $ttl, $key );

		if ( $this->has_cfw_cache ) {
			return \Commercebuild\WCCore\cfwCache::remember( $key, $callback, $ttl, self::GROUP );
		}

		$found = false;
		$value = wp_cache_get( $key, self::GROUP, false, $found );

		if ( $found ) {
			return $value;
		}

		$value = call_user_func( $callback );
		wp_cache_set( $key, $value, self::GROUP, $ttl );

		return $value;
	}

	/**
	 * Read a cached value without computing it on miss.
	 *
	 * Lets a caller decide what to do about a cold entry (serve a cheaper
	 * stand-in, schedule a warm) instead of paying the full cost inline.
	 *
	 * @since 0.11.3
	 *
	 * @param string $key   Cache key.
	 * @param bool   $found Set by reference to true on hit.
	 * @return mixed Null on miss.
	 */
	public function get( $key, &$found = null ) {
		$found = false;

		if ( $this->has_cfw_cache ) {
			// cfwCache::getCache() returns null (not false) on miss.
			$value = \Commercebuild\WCCore\cfwCache::getCache( $key, self::GROUP );
			$found = null !== $value;

			return $value;
		}

		$value = wp_cache_get( $key, self::GROUP, false, $found );

		return $found ? $value : null;
	}

	/**
	 * Write a value directly.
	 *
	 * @since 0.11.3
	 *
	 * @param string $key   Cache key.
	 * @param mixed  $value Value.
	 * @param int    $ttl   Optional. TTL in seconds. Default self::TTL.
	 * @return void
	 */
	public function set( $key, $value, $ttl = self::TTL ) {
		if ( $this->has_cfw_cache ) {
			\Commercebuild\WCCore\cfwCache::setCache( $key, $value, $ttl, self::GROUP );
			return;
		}

		wp_cache_set( $key, $value, self::GROUP, $ttl );
	}

	/**
	 * Build a deterministic cache key from arbitrary params.
	 *
	 * @since 0.1.0
	 *
	 * @param string $prefix Key prefix.
	 * @param array  $params Params to hash.
	 * @param bool   $scoped Optional. Scope the key to the visitor's catalog
	 *                       visibility. Default true. Pass false ONLY for data
	 *                       that is provably identical for every visitor
	 *                       (schema facts, row-count estimates) - never for
	 *                       product IDs, prices or anything catalog-visible.
	 * @return string
	 */
	public function key( $prefix, array $params, $scoped = true ) {
		ksort( $params );

		// The salt and the visibility identity must be part of every scoped
		// key, on both backends. cfwCache::generateCacheKey() is a plain hash
		// of what it is handed; it adds no identity of its own. Scoping only
		// the object-cache fallback would leave cfw_core sites - the exact
		// sites where catalog visibility varies per customer - sharing one
		// cache entry between customers.
		$params['_cbv_salt'] = $this->salt();

		if ( $scoped ) {
			$params['_cbv_identity'] = self::visibility_identity();
		}

		if ( $this->has_cfw_cache ) {
			return \Commercebuild\WCCore\cfwCache::generateCacheKey( $prefix, $params );
		}

		return $prefix . '_' . md5( wp_json_encode( $params ) );
	}

	/**
	 * Fingerprint of the current visitor's catalog visibility context.
	 *
	 * On ERP sites cfw_core filters which products a customer may see by
	 * customer code, groups, B2B/B2C state, and warehouse. Cached ID lists
	 * must therefore be scoped per context, or the first visitor to warm a
	 * cache would serve their result set to everyone else.
	 *
	 * @since 0.8.0
	 *
	 * @return string
	 */
	public static function visibility_identity() {
		static $identity = null;

		if ( null !== $identity ) {
			return $identity;
		}

		// Locale and currency change rendered names, URLs and price ordering
		// for logged-out visitors too, so they scope every key - not just the
		// logged-in ones.
		$context = array(
			'user'     => 0,
			'locale'   => function_exists( 'determine_locale' ) ? determine_locale() : get_locale(),
			'currency' => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : '',
		);

		// Price list, warehouse and channel change both which products are
		// visible and how they are priced/ordered, and on ERP sites they are
		// driven by session state that a LOGGED-OUT visitor can carry too.
		// They are therefore resolved outside the is_user_logged_in() branch.
		if ( function_exists( 'cfw_get_price_list' ) ) {
			$context['price_list'] = (string) cfw_get_price_list();
		}

		if ( function_exists( 'cfw_get_warehouse' ) ) {
			$context['warehouse'] = (string) cfw_get_warehouse();
		}

		if ( function_exists( 'cfw_get_channel' ) ) {
			$context['channel'] = (string) cfw_get_channel();
		}

		if ( is_user_logged_in() ) {
			// Logged-in visibility varies per customer, so the user id is the
			// safe default granularity.
			$context['user'] = get_current_user_id();

			if ( function_exists( 'cfw_get_customer_code' ) ) {
				$context['customer'] = (string) cfw_get_customer_code();
			}

			if ( function_exists( 'cfw_get_user_groups' ) ) {
				// cfw_get_user_groups() requires the user id/email; it returns 0
				// when no user is found, which the array guard below normalizes.
				// Reuse the id already placed into the identity so the lookup
				// cannot drift from it.
				$groups = cfw_get_user_groups( $context['user'] );
				$groups = is_array( $groups ) ? $groups : array();
				sort( $groups );
				$context['groups'] = $groups;
			}

			if ( function_exists( 'cfw_is_b2b' ) ) {
				$context['b2b'] = (bool) cfw_is_b2b();
			}
		}

		/**
		 * Filter the catalog visibility context used to scope cache keys.
		 *
		 * Return a stable array describing everything that changes which
		 * products the current visitor may see. Sites with additional
		 * visibility axes (warehouse, price list, channel) must add them here.
		 *
		 * @since 0.8.0
		 *
		 * @param array $context Visibility context.
		 */
		$context = apply_filters( 'cbv_catalog_visibility_context', $context );

		// Canonicalize before hashing. A filter returning the same values in a
		// different insertion order must produce the same identity, or the
		// cache would miss nondeterministically for the same visitor.
		self::ksort_recursive( $context );

		$identity = md5( (string) wp_json_encode( $context ) );

		return $identity;
	}

	/**
	 * Sort an array by key, recursively, in place.
	 *
	 * Lists (sequential integer keys) keep their order - it is meaningful.
	 *
	 * @since 0.11.3
	 *
	 * @param array $data Array to sort.
	 * @return void
	 */
	private static function ksort_recursive( array &$data ) {
		foreach ( $data as &$value ) {
			if ( is_array( $value ) ) {
				self::ksort_recursive( $value );
			}
		}

		unset( $value );

		// array_is_list() is PHP 8.1+; the theme supports 7.4.
		if ( array() === $data ) {
			return;
		}

		$is_list = array_keys( $data ) === range( 0, count( $data ) - 1 );

		if ( ! $is_list ) {
			ksort( $data );
		}
	}

	/**
	 * Current cache namespace salt (fallback invalidation for object caches
	 * without group flushing).
	 *
	 * @since 0.1.0
	 *
	 * @return int
	 */
	private function salt() {
		$salt = wp_cache_get( 'cbv_cache_salt', self::GROUP );

		if ( false === $salt ) {
			$salt = 1;
			wp_cache_set( 'cbv_cache_salt', $salt, self::GROUP );
		}

		return (int) $salt;
	}

	/**
	 * Flush all catalog-derived cache entries.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function flush_catalog() {
		if ( $this->has_cfw_cache ) {
			\Commercebuild\WCCore\cfwCache::flushGroup( self::GROUP );
			return;
		}

		if ( function_exists( 'wp_cache_flush_group' ) && wp_cache_supports( 'flush_group' ) ) {
			wp_cache_flush_group( self::GROUP );
			return;
		}

		// Fallback: bump the namespace salt baked into every key via key().
		$bumped = wp_cache_incr( 'cbv_cache_salt', 1, self::GROUP );

		if ( false === $bumped ) {
			wp_cache_set( 'cbv_cache_salt', 2, self::GROUP );
		}
	}
}
