<?php
/**
 * Speculative loading (prerender on hover) for near-instant navigations.
 *
 * WordPress core ships the Speculation Rules API but disables it entirely for
 * logged-in users. commercebuild storefronts are login-gated, so the theme
 * opts logged-in shoppers back in with prerendering and commerce-safe
 * exclusions for every URL that mutates state.
 *
 * @package Commercebuild_Velocity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Opinionated speculation-rules configuration.
 *
 * @since 0.9.10
 */
class CBV_Speculation {

	/**
	 * Register hooks.
	 *
	 * @since 0.9.10
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'wp_speculation_rules_configuration', array( $this, 'configuration' ) );
		add_filter( 'wp_speculation_rules_href_exclude_paths', array( $this, 'exclude_paths' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'dequeue_redundant_prefetchers' ), 100 );
	}

	/**
	 * Remove third-party hover-prefetch scripts while the theme prerenders.
	 *
	 * Breeze's "Preload Links" feature prefetches on hover too; running both
	 * speculators doubles every hover into two requests. When the theme's
	 * speculative loading is disabled (cbv_speculation_config returning
	 * null), third-party prefetchers are left untouched.
	 *
	 * @since 0.10.2
	 *
	 * @return void
	 */
	public function dequeue_redundant_prefetchers() {
		if ( null === $this->configuration( null ) ) {
			return;
		}

		/**
		 * Filter whether the theme dequeues third-party hover-prefetch scripts.
		 *
		 * @since 0.10.2
		 *
		 * @param bool $dequeue True to remove redundant prefetchers.
		 */
		if ( ! apply_filters( 'cbv_dequeue_third_party_prefetch', true ) ) {
			return;
		}

		wp_dequeue_script( 'breeze-prefetch' );
	}

	/**
	 * Enable hover-triggered prerendering, including for logged-in shoppers.
	 *
	 * @since 0.9.10
	 *
	 * @param array|null $config Core configuration (null when core disables it).
	 * @return array|null
	 */
	public function configuration( $config ) {
		if ( ! get_option( 'permalink_structure' ) ) {
			return $config;
		}

		/**
		 * Filter the theme's speculative-loading configuration.
		 *
		 * Return null to disable speculative loading entirely.
		 *
		 * @since 0.9.10
		 *
		 * @param array|null $config Array with 'mode' and 'eagerness' keys, or null.
		 */
		return apply_filters(
			'cbv_speculation_config',
			array(
				'mode'      => 'prerender',
				'eagerness' => 'moderate',
			)
		);
	}

	/**
	 * URLs that must never be prefetched or prerendered.
	 *
	 * Anything that mutates state on a GET request, plus checkout surfaces
	 * where a stale prerender could mislead the shopper.
	 *
	 * @since 0.9.10
	 *
	 * @param string[] $exclude_paths Core exclusion patterns.
	 * @return string[]
	 */
	public function exclude_paths( $exclude_paths ) {
		$theme_excludes = array(
			'/cart/*',
			'/checkout/*',
			'/my-account/*',
			'/*\\?*add-to-cart=*',
			'/*\\?*remove_item=*',
			'/*\\?*undo_item=*',
			'/*\\?*apply_coupon=*',
			'/*\\?*wc-ajax=*',
			'/*\\?*_wpnonce=*',
		);

		/**
		 * Filter the theme's speculative-loading exclusion patterns.
		 *
		 * @since 0.9.10
		 *
		 * @param string[] $theme_excludes URL patterns the theme excludes.
		 */
		$theme_excludes = apply_filters( 'cbv_speculation_exclude_paths', $theme_excludes );

		return array_merge( $exclude_paths, $theme_excludes );
	}
}
