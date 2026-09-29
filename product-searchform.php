<?php
/**
 * Product search form (classic header mode).
 *
 * Overrides WooCommerce's product search form with the same markup the
 * header-search block renders, so both header modes share styling and the
 * in-field magnifier icon.
 *
 * @package Commercebuild_Velocity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cbv_search_id = wp_unique_id( 'cbv-psearch-' );

/**
 * Filter the product search form placeholder text.
 *
 * @since 0.11.1
 *
 * @param string $placeholder Placeholder text.
 */
$cbv_search_placeholder = apply_filters( 'cbv_product_searchform_placeholder', __( 'Search products…', 'commercebuild-velocity' ) );

/**
 * Filter the product search form submit button text.
 *
 * @since 0.11.1
 *
 * @param string $button_text Button text.
 */
$cbv_search_button_text = apply_filters( 'cbv_product_searchform_button_text', __( 'Search', 'commercebuild-velocity' ) );

/**
 * Fires before the product search form.
 *
 * @since 0.11.1
 */
do_action( 'cbv_product_searchform_before' );
?>
<form role="search" method="get" class="cbv-hblock__form cbv-hblock__form--icon" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $cbv_search_id ); ?>"><?php esc_html_e( 'Search products', 'commercebuild-velocity' ); ?></label>
	<span class="cbv-hblock__field">
		<span class="cbv-hblock__field-icon"><?php cbv_the_icon( 'search', array( 'size' => 20 ) ); ?></span>
		<input
			type="search"
			id="<?php echo esc_attr( $cbv_search_id ); ?>"
			name="s"
			value="<?php echo esc_attr( get_search_query() ); ?>"
			placeholder="<?php echo esc_attr( $cbv_search_placeholder ); ?>"
		>
	</span>
	<input type="hidden" name="post_type" value="product">
	<?php
	/**
	 * Fires inside the product search form, before the submit button
	 * (extra hidden fields, category selectors).
	 *
	 * @since 0.11.1
	 */
	do_action( 'cbv_product_searchform_fields' );
	?>
	<button type="submit" class="cbv-hblock__submit"><?php echo esc_html( $cbv_search_button_text ); ?></button>
</form>
<?php
/**
 * Fires after the product search form.
 *
 * @since 0.11.1
 */
do_action( 'cbv_product_searchform_after' );
