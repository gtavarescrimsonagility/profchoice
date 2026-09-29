<?php
/**
 * Catalog query engine.
 *
 * Single choke point for every product/term lookup the theme performs.
 * Routes catalog queries through ElasticPress when it is active, keeps
 * MySQL fallbacks bounded (ID-only selects, no found-rows, no meta_query),
 * and caches results in the object cache.
 *
 * @package Commercebuild_Velocity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ElasticPress-aware, cache-backed product and term lookups.
 *
 * @since 0.1.0
 */
class CBV_Query_Engine {

	/**
	 * TTL for Hot Deals rankings.
	 *
	 * Longer than the general catalog TTL: these are expensive to build and a
	 * merchandising ranking does not need minute-level freshness.
	 *
	 * @var int
	 */
	const HOT_DEALS_TTL = 6 * HOUR_IN_SECONDS;

	/**
	 * Cron hook that warms an expensive Hot Deals ranking.
	 *
	 * @var string
	 */
	const WARM_HOOK = 'cbv_warm_hot_deals';

	/**
	 * WC_Query ordering callbacks that attach to posts_clauses and never detach.
	 *
	 * @var string[]
	 */
	const WC_ORDERING_CALLBACKS = array(
		'order_by_price_asc_post_clauses',
		'order_by_price_desc_post_clauses',
		'order_by_popularity_post_clauses',
		'order_by_rating_post_clauses',
	);

	/**
	 * Cache service.
	 *
	 * @var CBV_Cache
	 */
	private $cache;

	/**
	 * WC ordering callbacks THIS engine caused to be attached, pending removal.
	 *
	 * Only these are detached, so a filter WooCommerce attached for its own
	 * in-flight main catalog query is never pulled out from under it.
	 *
	 * @var string[]
	 */
	private $wc_ordering_attached = array();

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param CBV_Cache $cache Cache service.
	 */
	public function __construct( CBV_Cache $cache ) {
		$this->cache = $cache;
	}

	/**
	 * Register hooks. Called by cbv_services().
	 *
	 * @since 0.11.3
	 *
	 * @return void
	 */
	public function register() {
		add_action( self::WARM_HOOK, array( $this, 'warm_hot_deals' ), 10, 2 );
	}

	/**
	 * Whether ElasticPress is active and able to serve product queries.
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	public function elasticpress_active() {
		static $active = null;

		if ( null === $active ) {
			$active = defined( 'EP_VERSION' );

			/**
			 * Filter whether the theme should route catalog queries through ElasticPress.
			 *
			 * @since 0.1.0
			 *
			 * @param bool $active True when ElasticPress is detected.
			 */
			$active = (bool) apply_filters( 'cbv_use_elasticpress', $active );
		}

		return $active;
	}

	/**
	 * Fetch product IDs for a set of catalog args.
	 *
	 * Supported args (deliberately narrow so every path stays fast):
	 *   - limit      (int)    Number of products. Default 12, hard cap 48.
	 *   - page       (int)    Page number. Default 1.
	 *   - category   (int[])  product_cat term IDs.
	 *   - tag        (int[])  product_tag term IDs.
	 *   - attributes (array)  taxonomy => term slugs, e.g. array( 'pa_color' => array( 'red' ) ).
	 *   - include    (int[])  Explicit product IDs (skips the query).
	 *   - exclude    (int[])  Product IDs to omit.
	 *   - featured   (bool)   Featured products only.
	 *   - on_sale    (bool)   On-sale products only.
	 *   - orderby    (string) date|title|price|popularity|rand|menu_order. Default date.
	 *   - order      (string) ASC|DESC. Default DESC.
	 *   - search     (string) Keyword search (ElasticPress strongly recommended).
	 *
	 * @since 0.1.0
	 *
	 * @param array $args Catalog args.
	 * @return int[] Product IDs.
	 */
	public function get_product_ids( array $args = array() ) {
		$args = $this->normalize_args( $args );

		if ( ! empty( $args['include'] ) ) {
			/** This filter is documented in this class's query_product_ids(). */
			$curated = apply_filters( 'cbv_product_ids', array_map( 'absint', $args['include'] ), $args );

			// Visibility is always the final, non-bypassable stage.
			return array_slice( $this->filter_visible_ids( $curated ), 0, $args['limit'] );
		}

		// rand cannot be cached meaningfully; serve a cached pool and shuffle in PHP.
		$is_random = ( 'rand' === $args['orderby'] );

		if ( $is_random ) {
			$args['orderby'] = 'date';
			$args['limit']   = min( 48, $args['limit'] * 4 );
		}

		$key = $this->cache->key( 'cbv_pids', $args );

		$ids = $this->cache->remember(
			$key,
			function () use ( $args ) {
				return $this->query_product_ids( $args );
			}
		);

		if ( $is_random ) {
			shuffle( $ids );
		}

		return array_slice( $ids, 0, $args['limit'] );
	}

	/**
	 * Whether the site hides products whose only category is "Uncategorized".
	 *
	 * The setting `cfw_config_restrict_uncategorized_products` (cfw_core,
	 * default on) is only enforced on the main query and search, so theme
	 * blocks would otherwise advertise products whose pages cfw_core 404s.
	 *
	 * @since 0.5.1
	 *
	 * @return bool
	 */
	public function restrict_uncategorized() {
		$active = class_exists( '\\Commercebuild\\WCCore\\cfwProducts' )
			&& (bool) get_option( 'cfw_config_restrict_uncategorized_products', true );

		/**
		 * Filter whether uncategorized-only products are hidden from theme queries.
		 *
		 * @since 0.5.1
		 *
		 * @param bool $active True when the restriction applies.
		 */
		return (bool) apply_filters( 'cbv_restrict_uncategorized', $active );
	}

	/**
	 * Reduce a candidate ID list to products the current visitor may see.
	 *
	 * Every ID source in the theme funnels through here, including manually
	 * curated lists, upsells, cross-sells, related products, and lookup-table
	 * results, because WC_Product::is_visible() does not know about
	 * cfw_core's uncategorized-only restriction or per-customer access.
	 *
	 * Cost is bounded by the candidate count, never by catalog size: one
	 * indexed term query for the batch plus WooCommerce's own visibility
	 * check on already-primed product objects.
	 *
	 * @since 0.8.0
	 *
	 * @param int[] $ids Candidate product IDs, in priority order.
	 * @return int[] Visible IDs, order preserved.
	 */
	public function filter_visible_ids( array $ids ) {
		$ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );

		if ( empty( $ids ) ) {
			return array();
		}

		if ( $this->restrict_uncategorized() ) {
			$ids = $this->drop_uncategorized( $ids );
		}

		/**
		 * Filter a candidate ID list against site-specific visibility rules.
		 *
		 * Runs for every ID source, so integrations only need to hook here to
		 * enforce per-customer catalog access consistently.
		 *
		 * @since 0.8.0
		 *
		 * @param int[] $ids Candidate product IDs.
		 */
		return array_values( apply_filters( 'cbv_filter_visible_ids', $ids ) );
	}

	/**
	 * Remove products whose only product_cat is "Uncategorized".
	 *
	 * Checks just the supplied candidates, so it stays correct on sites with
	 * more uncategorized products than the exclusion-list cap.
	 *
	 * @since 0.8.0
	 *
	 * @param int[] $ids Candidate IDs.
	 * @return int[]
	 */
	private function drop_uncategorized( array $ids ) {
		global $wpdb;

		$term = get_term_by( 'slug', 'uncategorized', 'product_cat' );

		if ( ! $term instanceof WP_Term ) {
			return $ids;
		}

		$key = $this->cache->key(
			'cbv_uncat_batch',
			array(
				'ids'  => $ids,
				'term' => (int) $term->term_id,
			)
		);

		$hidden = $this->cache->remember(
			$key,
			function () use ( $ids, $term, $wpdb ) {
				$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

				// Candidates tagged Uncategorized that have no other category.
				$params = array_merge( array( (int) $term->term_id ), $ids, array( (int) $term->term_id ) );

				// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Placeholders are generated from a counted int array.
				$rows = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT tr.object_id
						FROM {$wpdb->term_relationships} tr
						JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
						WHERE tt.taxonomy = 'product_cat' AND tt.term_id = %d
						AND tr.object_id IN ({$placeholders})
						AND NOT EXISTS (
							SELECT 1 FROM {$wpdb->term_relationships} tr2
							JOIN {$wpdb->term_taxonomy} tt2 ON tt2.term_taxonomy_id = tr2.term_taxonomy_id
							WHERE tr2.object_id = tr.object_id
							AND tt2.taxonomy = 'product_cat' AND tt2.term_id <> %d
						)",
						$params
					)
				);
				// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

				return array_map( 'absint', (array) $rows );
			},
			15 * MINUTE_IN_SECONDS
		);

		if ( empty( $hidden ) ) {
			return $ids;
		}

		return array_values( array_diff( $ids, $hidden ) );
	}

	/**
	 * Bounded on-sale ID lookup.
	 *
	 * WooCommerce's wc_get_product_ids_on_sale() materializes every on-sale
	 * product, which is unusable during a large promotion on a million-product
	 * catalog. The lookup table answers the same question with a LIMIT.
	 *
	 * @since 0.8.0
	 *
	 * @param int $limit Maximum IDs.
	 * @return int[]
	 */
	private function on_sale_ids( $limit ) {
		global $wpdb;

		$limit = min( 500, max( 1, absint( $limit ) ) );

		return $this->cache->remember(
			$this->cache->key( 'cbv_on_sale_pool', array( 'limit' => $limit ) ),
			function () use ( $wpdb, $limit ) {
				$ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT lookup.product_id
						FROM {$wpdb->wc_product_meta_lookup} lookup
						INNER JOIN {$wpdb->posts} p
							ON p.ID = lookup.product_id
							AND p.post_type = 'product'
							AND p.post_status = 'publish'
						WHERE lookup.onsale = 1
						ORDER BY lookup.product_id DESC
						LIMIT %d",
						$limit
					)
				);

				return array_map( 'absint', (array) $ids );
			},
			15 * MINUTE_IN_SECONDS
		);
	}

	/**
	 * Run the underlying WP_Query for product IDs.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args Normalized catalog args.
	 * @return int[]
	 */
	private function query_product_ids( array $args ) {
		$query_args = array(
			'post_type'              => 'product',
			'post_status'            => 'publish',
			'fields'                 => 'ids',
			'posts_per_page'         => $args['limit'],
			'paged'                  => $args['page'],
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'ignore_sticky_posts'    => true,
			'order'                  => $args['order'],
		);

		$exclude = array_map( 'absint', (array) $args['exclude'] );

		if ( ! empty( $exclude ) ) {
			$query_args['post__not_in'] = array_values( array_unique( $exclude ) );
		}

		/*
		 * When a visibility restriction is active the query over-fetches and
		 * the shared visibility stage trims the result. That keeps the cost
		 * bounded by the requested page rather than by how many restricted
		 * products the catalog happens to contain.
		 */
		$overfetch = $this->restrict_uncategorized();

		if ( $overfetch ) {
			$query_args['posts_per_page'] = min( 150, $args['limit'] * 3 );
			$query_args['offset']         = ( $args['page'] - 1 ) * $args['limit'];
			unset( $query_args['paged'] );
		}

		if ( $args['on_sale'] ) {
			$on_sale = $this->on_sale_ids( max( 200, $args['limit'] * 10 ) );

			if ( empty( $on_sale ) ) {
				return array();
			}

			$query_args['post__in'] = $on_sale;
		}

		if ( '' !== $args['search'] ) {
			$query_args['s'] = $args['search'];
		}

		$query_args = $this->apply_orderby( $query_args, $args['orderby'] );

		$tax_query = $this->build_tax_query( $args );

		if ( ! empty( $tax_query ) ) {
			$query_args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		if ( $this->elasticpress_active() ) {
			$query_args['ep_integrate'] = true;
		}

		/**
		 * Filter the WP_Query args for engine product lookups.
		 *
		 * @since 0.3.0
		 *
		 * @param array $query_args WP_Query args.
		 * @param array $args       Normalized catalog args.
		 */
		$query_args = apply_filters( 'cbv_product_query_args', $query_args, $args );

		$query = new WP_Query( $query_args );

		$this->detach_wc_ordering_clauses();

		/**
		 * Filter the product IDs returned by the engine.
		 *
		 * @since 0.3.0
		 *
		 * @param int[] $ids  Product IDs.
		 * @param array $args Normalized catalog args.
		 */
		$ids = apply_filters( 'cbv_product_ids', array_map( 'absint', $query->posts ), $args );

		// Visibility runs last so no filter can reintroduce hidden products.
		return array_slice( $this->filter_visible_ids( $ids ), 0, $args['limit'] );
	}

	/**
	 * Map an orderby keyword onto fast query args.
	 *
	 * Price and popularity use WooCommerce's denormalized lookup table via
	 * the ordering args filter instead of meta_value joins.
	 *
	 * @since 0.1.0
	 *
	 * @param array  $query_args WP_Query args.
	 * @param string $orderby    Orderby keyword.
	 * @return array
	 */
	private function apply_orderby( array $query_args, $orderby ) {
		switch ( $orderby ) {
			case 'price':
			case 'popularity':
			case 'rating':
				if ( function_exists( 'WC' ) ) {
					// Record what was already attached so detach only removes
					// what this call adds - WooCommerce may have attached the
					// same callback for its own in-flight main query.
					$before = $this->wc_ordering_snapshot();

					$ordering = WC()->query->get_catalog_ordering_args( $orderby, $query_args['order'] );

					foreach ( $this->wc_ordering_snapshot() as $callback => $attached ) {
						if ( $attached && empty( $before[ $callback ] ) ) {
							$this->wc_ordering_attached[] = $callback;
						}
					}

					$query_args['orderby'] = $ordering['orderby'];
					$query_args['order']   = $ordering['order'];

					if ( ! empty( $ordering['meta_key'] ) ) {
						$query_args['meta_key'] = $ordering['meta_key']; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					}
				}
				break;
			case 'title':
				$query_args['orderby'] = 'title';
				break;
			case 'menu_order':
				$query_args['orderby'] = 'menu_order title';
				break;
			default:
				$query_args['orderby'] = 'date ID';
		}

		return $query_args;
	}

	/**
	 * Detach the posts_clauses filters WC()->query->get_catalog_ordering_args()
	 * leaves behind.
	 *
	 * WooCommerce adds these for price/popularity/rating ordering and never
	 * removes them itself - it relies on the main catalog query being the last
	 * one that matters. The engine runs secondary queries, so a leftover
	 * filter would splice wc_product_meta_lookup ordering into every later
	 * WP_Query in the request (menus, related products, widgets).
	 *
	 * @since 0.11.3
	 *
	 * @return void
	 */
	private function detach_wc_ordering_clauses() {
		if ( empty( $this->wc_ordering_attached ) ) {
			return;
		}

		foreach ( $this->wc_ordering_attached as $callback ) {
			remove_filter( 'posts_clauses', array( WC()->query, $callback ) );
		}

		$this->wc_ordering_attached = array();
	}

	/**
	 * Snapshot which WC ordering clause filters are attached right now.
	 *
	 * @since 0.11.3
	 *
	 * @return array<string, bool> Callback name => attached.
	 */
	private function wc_ordering_snapshot() {
		$state = array();

		if ( ! function_exists( 'WC' ) || ! isset( WC()->query ) ) {
			return $state;
		}

		foreach ( self::WC_ORDERING_CALLBACKS as $callback ) {
			$state[ $callback ] = false !== has_filter( 'posts_clauses', array( WC()->query, $callback ) );
		}

		return $state;
	}

	/**
	 * Build a bounded tax_query from normalized args.
	 *
	 * Featured and on-sale states resolve through cached ID pools
	 * (wc_get_featured_product_ids / wc_get_product_ids_on_sale are already
	 * transient/option backed inside WooCommerce) rather than meta lookups.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args Normalized catalog args.
	 * @return array
	 */
	private function build_tax_query( array $args ) {
		$tax_query = array();

		if ( ! empty( $args['category'] ) ) {
			$tax_query[] = array(
				'taxonomy'         => 'product_cat',
				'field'            => 'term_id',
				'terms'            => array_map( 'absint', $args['category'] ),
				'include_children' => true,
			);
		}

		if ( ! empty( $args['tag'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'product_tag',
				'field'    => 'term_id',
				'terms'    => array_map( 'absint', $args['tag'] ),
			);
		}

		foreach ( $args['attributes'] as $taxonomy => $slugs ) {
			$taxonomy = sanitize_key( $taxonomy );

			if ( 0 !== strpos( $taxonomy, 'pa_' ) || empty( $slugs ) ) {
				continue;
			}

			$tax_query[] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => array_map( 'sanitize_title', (array) $slugs ),
			);
		}

		if ( $args['featured'] ) {
			$tax_query[] = array(
				'taxonomy' => 'product_visibility',
				'field'    => 'name',
				'terms'    => array( 'featured' ),
			);
		}

		if ( count( $tax_query ) > 1 ) {
			$tax_query['relation'] = 'AND';
		}

		return $tax_query;
	}

	/**
	 * Related product IDs for a product, cached per product.
	 *
	 * @since 0.1.0
	 *
	 * @param int $product_id Product ID.
	 * @param int $limit      Number of related products. Default 4.
	 * @return int[]
	 */
	public function get_related_ids( $product_id, $limit = 4 ) {
		$product_id = absint( $product_id );
		$limit      = min( 12, max( 1, absint( $limit ) ) );

		if ( ! $product_id || ! function_exists( 'wc_get_related_products' ) ) {
			return array();
		}

		$key = $this->cache->key(
			'cbv_related',
			array(
				'product' => $product_id,
				'limit'   => $limit,
			)
		);

		$ids = $this->cache->remember(
			$key,
			function () use ( $product_id, $limit ) {
				return array_map( 'absint', wc_get_related_products( $product_id, $limit * 3 ) );
			},
			HOUR_IN_SECONDS
		);

		/**
		 * Filter related product IDs (e.g. replace with ERP relationships).
		 *
		 * @since 0.3.0
		 *
		 * @param int[] $ids        Related product IDs.
		 * @param int   $product_id Source product ID.
		 * @param int   $limit      Requested count.
		 */
		$ids = apply_filters( 'cbv_related_ids', $ids, $product_id, $limit );

		// Visibility runs last so no filter can reintroduce hidden products.
		return array_slice( $this->filter_visible_ids( $ids ), 0, $limit );
	}

	/**
	 * Product category terms with cached counts and thumbnail IDs.
	 *
	 * One bounded term query, no per-term meta round trips: thumbnail IDs are
	 * primed in a single warmed meta-cache pass.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args {
	 *     Optional args.
	 *
	 *     @type int   $parent  Parent term ID. Default 0 (top level).
	 *     @type int   $limit   Max terms. Default 12, cap 48.
	 *     @type int[] $include Explicit term IDs.
	 * }
	 * @return array[] Each: term_id, name, slug, count, url, thumbnail_id.
	 */
	public function get_category_tiles( array $args = array() ) {
		$args = array(
			'parent'  => isset( $args['parent'] ) ? absint( $args['parent'] ) : 0,
			'limit'   => isset( $args['limit'] ) ? min( 48, max( 1, absint( $args['limit'] ) ) ) : 12,
			'include' => isset( $args['include'] ) ? array_map( 'absint', (array) $args['include'] ) : array(),
		);

		$key = $this->cache->key( 'cbv_cat_tiles', $args );

		$tiles = $this->cache->remember(
			$key,
			function () use ( $args ) {
				$term_args = array(
					'taxonomy'   => 'product_cat',
					'hide_empty' => true,
					'number'     => $args['limit'],
					'orderby'    => 'name',
				);

				if ( ! empty( $args['include'] ) ) {
					$term_args['include'] = $args['include'];
				} else {
					$term_args['parent'] = $args['parent'];
				}

				if ( $this->restrict_uncategorized() ) {
					// The catalog hides uncategorized-only products, so a tile
					// linking to that category would lead to an empty archive.
					$uncategorized = get_term_by( 'slug', 'uncategorized', 'product_cat' );

					if ( $uncategorized instanceof WP_Term ) {
						$term_args['exclude'] = array( (int) $uncategorized->term_id );
					}
				}

				$terms = get_terms( $term_args );

				if ( is_wp_error( $terms ) || empty( $terms ) ) {
					return array();
				}

				// One shot to warm term meta for every tile.
				update_termmeta_cache( wp_list_pluck( $terms, 'term_id' ) );

				$tiles = array();

				foreach ( $terms as $term ) {
					$tiles[] = array(
						'term_id'      => (int) $term->term_id,
						'name'         => $term->name,
						'slug'         => $term->slug,
						'count'        => (int) $term->count,
						'url'          => get_term_link( $term ),
						'thumbnail_id' => (int) get_term_meta( $term->term_id, 'thumbnail_id', true ),
					);
				}

				return $tiles;
			},
			HOUR_IN_SECONDS
		);

		/**
		 * Filter the category tiles data.
		 *
		 * @since 0.3.0
		 *
		 * @param array[] $tiles Tile rows (term_id, name, slug, count, url, thumbnail_id).
		 * @param array   $args  Normalized tile args.
		 */
		return apply_filters( 'cbv_category_tiles', $tiles, $args );
	}

	/**
	 * Whether a Hot Deals source must be computed off the request path.
	 *
	 * @since 0.11.3
	 *
	 * @param string $source Deal source.
	 * @return bool
	 */
	private function hot_deals_needs_warm( $source ) {
		return in_array( $source, array( 'best_selling', 'top_rated' ), true )
			&& ! $this->elasticpress_can_sort_catalog()
			&& ! $this->lookup_scan_is_cheap();
	}

	/**
	 * Queue a background computation of a Hot Deals ranking.
	 *
	 * @since 0.11.3
	 *
	 * @param string $source Deal source.
	 * @param int    $limit  Requested count.
	 * @return void
	 */
	private function schedule_hot_deals_warm( $source, $limit ) {
		$args = array( $source, (int) $limit );

		if ( wp_next_scheduled( self::WARM_HOOK, $args ) ) {
			return;
		}

		// Due immediately so this request's own cron spawn can run it, keeping
		// the fallback window to a single page view where possible.
		wp_schedule_single_event( time(), self::WARM_HOOK, $args );
	}

	/**
	 * Cron target: compute and cache a Hot Deals ranking.
	 *
	 * Runs the expensive lookup-table sort away from a visitor request. The
	 * entry it writes is the same unscoped key get_hot_deal_ids() reads.
	 *
	 * @since 0.11.3
	 *
	 * @param string $source Deal source.
	 * @param int    $limit  Requested count.
	 * @return void
	 */
	public function warm_hot_deals( $source, $limit ) {
		// force_compute bypasses the needs-warm branch so this actually runs
		// the real query instead of re-queueing itself.
		$this->get_hot_deal_ids( $source, $limit, array(), true );
	}

	/**
	 * Stand-in ranking used while a real one is still warming.
	 *
	 * Ordered by the indexed onsale column, falling back to newest products so
	 * the block never renders empty on a store with no active sale.
	 *
	 * @since 0.11.3
	 *
	 * @param int $limit Requested count.
	 * @return int[]
	 */
	private function cold_ranking_fallback( $limit ) {
		/*
		 * on_sale_ids() is a GLOBAL pool, so testing it for emptiness says
		 * nothing about what this visitor may see. A B2B customer with access
		 * to none of the on-sale products would otherwise skip the newest
		 * fallback and get an empty block once filter_visible_ids() ran.
		 * Filter first, then decide whether a top-up is needed.
		 */
		$ids = $this->filter_visible_ids( $this->on_sale_ids( max( 20, $limit * 4 ) ) );

		if ( count( $ids ) < $limit ) {
			// get_product_ids() applies visibility itself, and over-fetches
			// internally when the uncategorized restriction is on.
			$newest = $this->get_product_ids(
				array(
					'limit'   => min( 48, max( 24, $limit * 4 ) ),
					'orderby' => 'date',
				)
			);

			$ids = array_values( array_unique( array_merge( $ids, $newest ) ) );
		}

		/*
		 * Best effort, deliberately bounded: if this visitor can see neither
		 * the on-sale pool nor the newest products, the block renders empty
		 * for this ONE request rather than paying for a deeper search. The
		 * warm scheduled alongside it lands within a cron tick, after which
		 * the true ranking serves and the empty window closes. The fallback is
		 * never written to cache, so nothing wrong is persisted.
		 */

		/**
		 * Filter the stand-in IDs served while a Hot Deals ranking warms.
		 *
		 * @since 0.11.3
		 *
		 * @param int[] $ids   Stand-in product IDs.
		 * @param int   $limit Requested count.
		 */
		return apply_filters( 'cbv_hot_deals_cold_fallback', $ids, $limit );
	}

	/**
	 * Whether ElasticPress can serve WooCommerce catalog ORDERING.
	 *
	 * The elasticpress_active() check only tells us ElasticPress is installed.
	 * Ordering by popularity/rating also needs its WooCommerce feature, which
	 * supplies the _wc_average_rating / total_sales mappings - base
	 * ep_integrate does not. Routing those sorts to an index without the
	 * mapping yields wrong ordering or an Elasticsearch error.
	 *
	 * @since 0.11.3
	 *
	 * @return bool
	 */
	public function elasticpress_can_sort_catalog() {
		static $can = null;

		if ( null !== $can ) {
			return $can;
		}

		$can = false;

		if ( $this->elasticpress_active() && class_exists( '\\ElasticPress\\Features' ) ) {
			$features = \ElasticPress\Features::factory();
			$woo      = method_exists( $features, 'get_registered_feature' ) ? $features->get_registered_feature( 'woocommerce' ) : null;

			$can = $woo && method_exists( $woo, 'is_active' ) && $woo->is_active();
		}

		/**
		 * Filter whether ElasticPress may serve popularity/rating ordering.
		 *
		 * @since 0.11.3
		 *
		 * @param bool $can True when the WooCommerce feature is active.
		 */
		$can = (bool) apply_filters( 'cbv_elasticpress_can_sort_catalog', $can );

		return $can;
	}

	/**
	 * Whether wc_product_meta_lookup is small enough to sort without an index.
	 *
	 * @since 0.11.3
	 *
	 * @return bool
	 */
	private function lookup_scan_is_cheap() {
		global $wpdb;

		/**
		 * Filter the row count below which an unindexed lookup-table sort is
		 * considered acceptable.
		 *
		 * Raise this only if the site has added its own index on total_sales /
		 * average_rating, or the catalog is known to stay small.
		 *
		 * @since 0.11.3
		 *
		 * @param int $threshold Row count. Default 50000.
		 */
		$threshold = (int) apply_filters( 'cbv_lookup_scan_row_threshold', 50000 );

		$rows = $this->cache->remember(
			$this->cache->key( 'cbv_lookup_rows', array(), false ),
			static function () use ( $wpdb ) {
				// Approximate row count from table stats: no scan of its own.
				$estimate = $wpdb->get_var(
					$wpdb->prepare(
						'SELECT table_rows FROM information_schema.tables
						WHERE table_schema = DATABASE() AND table_name = %s',
						$wpdb->wc_product_meta_lookup
					)
				);

				return (int) $estimate;
			},
			DAY_IN_SECONDS
		);

		return $rows <= $threshold;
	}

	/**
	 * Product IDs for the Hot Deals block.
	 *
	 * Algorithmic sources read the denormalized wc_product_meta_lookup table
	 * directly (one indexed-join query, bounded LIMIT, no postmeta joins) and
	 * are cached, so they stay fast on very large catalogs.
	 *
	 * @since 0.2.0
	 *
	 * @param string $source  manual|on_sale|best_selling|top_rated|newest.
	 * @param int    $limit   Number of products.
	 * @param int[]  $include Product IDs for the manual source.
	 * @param bool   $force   Optional. Compute an expensive ranking inline
	 *                        instead of deferring it to a background warm.
	 *                        Used by the cron target. Default false.
	 * @return int[]
	 */
	public function get_hot_deal_ids( $source, $limit, array $include = array(), $force = false ) {
		$limit  = min( 48, max( 1, absint( $limit ) ) );
		$source = in_array( $source, array( 'manual', 'on_sale', 'best_selling', 'top_rated', 'newest' ), true ) ? $source : 'on_sale';

		if ( 'manual' === $source ) {
			/** This filter is documented at the end of this method. */
			$manual = apply_filters( 'cbv_hot_deal_ids', array_map( 'absint', $include ), $source, $limit );

			return array_slice( $this->filter_visible_ids( $manual ), 0, $limit );
		}

		if ( 'newest' === $source ) {
			return $this->get_product_ids(
				array(
					'limit'   => $limit,
					'orderby' => 'date',
				)
			);
		}

		// wc_product_meta_lookup carries no index on total_sales,
		// average_rating or rating_count - that is WooCommerce's own schema,
		// not a broken install - so ordering by them is a full table scan plus
		// filesort. Route to ElasticPress when it can serve the sort, and
		// otherwise only touch the lookup table on catalogs small enough for
		// the scan to be cheap.
		if ( in_array( $source, array( 'best_selling', 'top_rated' ), true ) && $this->elasticpress_can_sort_catalog() ) {
			return $this->get_product_ids(
				array(
					'limit'   => $limit,
					'orderby' => 'best_selling' === $source ? 'popularity' : 'rating',
				)
			);
		}

		$restrict_uncategorized = $this->restrict_uncategorized();

		/*
		 * This entry is the GLOBAL ranking - filter_visible_ids() applies the
		 * per-visitor cut further down, outside the cache - so the key is
		 * deliberately unscoped. That keeps one entry per ranking instead of
		 * one per customer, and lets a background warm populate the same key a
		 * visitor will read. The restriction flag is a site setting rather
		 * than visitor state, so it goes in the key explicitly.
		 */
		$key = $this->cache->key(
			'cbv_hot_deals',
			array(
				'source'        => $source,
				'limit'         => $limit,
				'uncategorized' => $restrict_uncategorized,
			),
			false
		);

		$builder = function () use ( $source, $limit, $restrict_uncategorized ) {
				global $wpdb;

			switch ( $source ) {
				case 'best_selling':
					$where = 'lookup.total_sales > 0';
					$order = 'lookup.total_sales DESC';
					break;
				case 'top_rated':
					$where = 'lookup.rating_count > 0';
					$order = 'lookup.average_rating DESC, lookup.rating_count DESC';
					break;
				default:
					$where = 'lookup.onsale = 1';
					$order = 'lookup.total_sales DESC, lookup.product_id DESC';
			}

				$uncategorized_sql = '';
				$params            = array();

			if ( $restrict_uncategorized ) {
				$term = get_term_by( 'slug', 'uncategorized', 'product_cat' );

				if ( $term instanceof WP_Term ) {
					// Mirrors cfw_core: hide a product only when Uncategorized
					// is its *only* category. Both subqueries hit the
					// term_relationships primary key.
					$uncategorized_sql = "
						AND (
							NOT EXISTS (
								SELECT 1 FROM {$wpdb->term_relationships} tru
								JOIN {$wpdb->term_taxonomy} ttu ON ttu.term_taxonomy_id = tru.term_taxonomy_id
								WHERE tru.object_id = lookup.product_id AND ttu.taxonomy = 'product_cat' AND ttu.term_id = %d
							)
							OR EXISTS (
								SELECT 1 FROM {$wpdb->term_relationships} tro
								JOIN {$wpdb->term_taxonomy} tto ON tto.term_taxonomy_id = tro.term_taxonomy_id
								WHERE tro.object_id = lookup.product_id AND tto.taxonomy = 'product_cat' AND tto.term_id <> %d
							)
						)";

					$params[] = (int) $term->term_id;
					$params[] = (int) $term->term_id;
				}
			}

				// Over-fetch so the visibility stage can drop candidates
				// without under-filling the block.
				$params[] = min( 200, $limit * 4 );

				// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $where/$order come from the fixed whitelist above and $uncategorized_sql uses placeholders; results are cached by the caller.
				$ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT lookup.product_id
						FROM {$wpdb->wc_product_meta_lookup} lookup
						INNER JOIN {$wpdb->posts} p
							ON p.ID = lookup.product_id
							AND p.post_type = 'product'
							AND p.post_status = 'publish'
						WHERE {$where}
						{$uncategorized_sql}
						ORDER BY {$order}
						LIMIT %d",
						$params
					)
				);
				// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

				return array_map( 'absint', $ids );
		};

		/*
		 * best_selling / top_rated sort columns WooCommerce does not index, so
		 * a cold entry on a big catalog is a full scan plus filesort. Never
		 * substitute a different ranking - that silently shows sale items
		 * where the merchant asked for best sellers, and renders empty when
		 * nothing is on sale. Instead serve a clearly-labelled stand-in for
		 * this one request and warm the real ranking in the background, so
		 * every later request gets the correct answer from cache.
		 */
		if ( ! $force && $this->hot_deals_needs_warm( $source ) ) {
			$found = false;
			$ids   = $this->cache->get( $key, $found );

			if ( ! $found ) {
				$this->schedule_hot_deals_warm( $source, $limit );
				$ids = $this->cold_ranking_fallback( $limit );
			}
		} else {
			$ids = $this->cache->remember( $key, $builder, self::HOT_DEALS_TTL );
		}

		/**
		 * Filter Hot Deals product IDs.
		 *
		 * @since 0.3.0
		 *
		 * @param int[]  $ids    Product IDs.
		 * @param string $source Deal source.
		 * @param int    $limit  Requested count.
		 */
		$ids = apply_filters( 'cbv_hot_deal_ids', $ids, $source, $limit );

		// Visibility runs last so no filter can reintroduce hidden products.
		return array_slice( $this->filter_visible_ids( $ids ), 0, $limit );
	}

	/**
	 * Normalize and clamp public catalog args.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args Raw args.
	 * @return array
	 */
	private function normalize_args( array $args ) {
		$defaults = array(
			'limit'      => 12,
			'page'       => 1,
			'category'   => array(),
			'tag'        => array(),
			'attributes' => array(),
			'include'    => array(),
			'exclude'    => array(),
			'featured'   => false,
			'on_sale'    => false,
			'orderby'    => 'date',
			'order'      => 'DESC',
			'search'     => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$args['limit']    = min( 48, max( 1, absint( $args['limit'] ) ) );
		$args['page']     = max( 1, absint( $args['page'] ) );
		$args['order']    = ( 'ASC' === strtoupper( $args['order'] ) ) ? 'ASC' : 'DESC';
		$args['featured'] = ! empty( $args['featured'] );
		$args['on_sale']  = ! empty( $args['on_sale'] );
		$args['search']   = sanitize_text_field( (string) $args['search'] );

		$allowed_orderby = array( 'date', 'title', 'price', 'popularity', 'rand', 'menu_order' );

		if ( ! in_array( $args['orderby'], $allowed_orderby, true ) ) {
			$args['orderby'] = 'date';
		}

		foreach ( array( 'category', 'tag', 'include', 'exclude' ) as $id_list ) {
			$args[ $id_list ] = array_values( array_filter( array_map( 'absint', (array) $args[ $id_list ] ) ) );
		}

		$args['attributes'] = is_array( $args['attributes'] ) ? $args['attributes'] : array();

		return $args;
	}
}
