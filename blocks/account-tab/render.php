<?php
/**
 * Account Tab child render: outputs the tab's inner block content.
 * The parent Account Tabs block wraps this in the tab panel markup.
 *
 * @package Commercebuild_Velocity
 *
 * @var array  $attributes Block attributes.
 * @var string $content    Rendered inner blocks.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fires inside an account tab, before its content.
 *
 * @since 0.8.0
 *
 * @param array $attributes Block attributes.
 */
do_action( 'cbv_block_account_tab_before', $attributes );

echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rendered inner blocks.

/**
 * Fires inside an account tab, after its content.
 *
 * @since 0.8.0
 *
 * @param array $attributes Block attributes.
 */
do_action( 'cbv_block_account_tab_after', $attributes );
