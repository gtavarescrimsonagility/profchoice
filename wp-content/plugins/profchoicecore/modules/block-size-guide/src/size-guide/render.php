<?php
/**
 * Size Guide link: opens the size guide page in a modal (printed once in
 * the footer); without JavaScript it links to the page. Nothing is printed
 * when the attribute has no size guide page.
 *
 * @package ProfChoiceCore
 *
 * @var array $attributes Block attributes.
 */

use ProfChoiceCore\SizeGuide\SizeGuide;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pc_page = ! empty( $attributes['pageId'] ) ? absint( $attributes['pageId'] ) : SizeGuide::page_for( isset( $attributes['attribute'] ) ? (string) $attributes['attribute'] : '' );
if ( ! $pc_page || 'publish' !== get_post_status( $pc_page ) ) {
	return;
}

SizeGuide::need_dialog( $pc_page );

$pc_text    = ! empty( $attributes['text'] ) ? (string) $attributes['text'] : __( 'Size Guide', 'profchoicecore' );
$pc_wrapper = get_block_wrapper_attributes(
	array(
		'class'         => 'pc-size-guide__link',
		'href'          => esc_url( get_permalink( $pc_page ) ),
		'aria-haspopup' => 'dialog',
	)
);
?>
<a <?php echo $pc_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> data-wp-interactive="profchoice/size-guide" <?php echo wp_interactivity_data_wp_context( array( 'dialog' => 'pc-size-guide-' . $pc_page ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> data-wp-on--click="actions.open"><?php echo esc_html( $pc_text ); ?></a>
