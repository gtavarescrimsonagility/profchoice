<?php
/**
 * Wishlist: the visitor's lists (tabs), the shown list's actions and its
 * products. `?list=<id>` picks the list; the default list otherwise.
 *
 * @package ProfChoiceCore
 *
 * @var array $attributes Block attributes.
 */

use ProfChoiceCore\Wishlist\Lists;
use ProfChoiceCore\Wishlist\Settings;
use ProfChoiceCore\Wishlist\Wishlist;

if ( ! defined( 'ABSPATH' ) || ! function_exists( 'wc_get_product' ) ) {
	return;
}

Wishlist::prepare();

$pc_owner    = Lists::current_owner();
$pc_lists    = Lists::lists( $pc_owner );
$pc_multiple = Settings::multiple_lists();
$pc_wrapper  = get_block_wrapper_attributes( array( 'class' => 'pc-wishlist' ) );
$pc_shop     = wc_get_page_permalink( 'shop' );
$pc_base     = remove_query_arg( 'list' );

// The list to show.
$pc_current = $pc_lists ? $pc_lists[0] : null;
$pc_asked   = isset( $_GET['list'] ) ? absint( $_GET['list'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
foreach ( $pc_lists as $pc_list ) {
	if ( $pc_list->ID === $pc_asked ) {
		$pc_current = $pc_list;
	}
}
$pc_items = $pc_current ? Lists::items( $pc_current->ID ) : array();
?>
<div <?php echo $pc_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> data-wp-interactive="profchoice/wishlist">
	<?php if ( ! $pc_owner && ! is_user_logged_in() && ! Settings::guests_allowed() ) : ?>
		<div class="pc-wishlist__empty">
			<p><?php esc_html_e( 'Log in to see your wishlist.', 'profchoicecore' ); ?></p>
			<a class="pc-wishlist__cta" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"><?php esc_html_e( 'Log in', 'profchoicecore' ); ?></a>
		</div>
	<?php else : ?>
		<?php if ( $pc_multiple && count( $pc_lists ) > 1 ) : ?>
			<nav class="pc-wishlist__tabs" aria-label="<?php esc_attr_e( 'Your lists', 'profchoicecore' ); ?>">
				<?php foreach ( $pc_lists as $pc_list ) : ?>
					<?php $pc_active = $pc_current && $pc_list->ID === $pc_current->ID; ?>
					<a class="pc-wishlist__tab<?php echo $pc_active ? ' is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'list', $pc_list->ID, $pc_base ) ); ?>"<?php echo $pc_active ? ' aria-current="page"' : ''; ?>>
						<?php echo esc_html( $pc_list->post_title ); ?>
						<span class="pc-wishlist__tab-count"><?php echo esc_html( number_format_i18n( count( Lists::items( $pc_list->ID ) ) ) ); ?></span>
					</a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>

		<?php if ( $pc_current ) : ?>
			<div class="pc-wishlist__head" <?php echo wp_interactivity_data_wp_context( array( 'listId' => $pc_current->ID, 'renaming' => false ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<h2 class="pc-wishlist__title" data-wp-bind--hidden="context.renaming">
					<?php echo esc_html( $pc_current->post_title ); ?>
					<?php if ( $pc_multiple && Lists::is_default( $pc_current->ID ) ) : ?>
						<span class="pc-wishlist__badge"><?php esc_html_e( 'Default', 'profchoicecore' ); ?></span>
					<?php endif; ?>
				</h2>
				<?php if ( $pc_multiple ) : ?>
					<form class="pc-wishlist__rename" hidden data-wp-bind--hidden="!context.renaming" data-wp-on--submit="actions.renameList">
						<label class="screen-reader-text" for="pc-wishlist-rename"><?php esc_html_e( 'List name', 'profchoicecore' ); ?></label>
						<input class="pc-wishlist__input" id="pc-wishlist-rename" name="name" type="text" maxlength="80" required value="<?php echo esc_attr( $pc_current->post_title ); ?>">
						<button class="pc-wishlist__button pc-wishlist__button--primary" type="submit"><?php esc_html_e( 'Save', 'profchoicecore' ); ?></button>
						<button class="pc-wishlist__text" type="button" data-wp-on--click="actions.cancelRename"><?php esc_html_e( 'Cancel', 'profchoicecore' ); ?></button>
					</form>
					<div class="pc-wishlist__actions" data-wp-bind--hidden="context.renaming">
						<button class="pc-wishlist__text" type="button" data-wp-on--click="actions.startRename"><?php esc_html_e( 'Rename', 'profchoicecore' ); ?></button>
						<?php if ( ! Lists::is_default( $pc_current->ID ) ) : ?>
							<button class="pc-wishlist__text" type="button" data-wp-on--click="actions.setDefault"><?php esc_html_e( 'Make default', 'profchoicecore' ); ?></button>
						<?php endif; ?>
						<?php if ( count( $pc_lists ) > 1 ) : ?>
							<button class="pc-wishlist__text pc-wishlist__text--danger" type="button" data-wp-on--click="actions.deleteList"><?php esc_html_e( 'Delete list', 'profchoicecore' ); ?></button>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( $pc_items ) : ?>
			<ul class="pc-wishlist__items">
				<?php
				foreach ( $pc_items as $pc_item ) :
					$pc_product = wc_get_product( $pc_item['product_id'] );
					if ( ! $pc_product ) {
						continue;
					}
					$pc_variation = ! empty( $pc_item['variation_id'] ) ? wc_get_product( $pc_item['variation_id'] ) : null;
					$pc_shown     = $pc_variation ? $pc_variation : $pc_product;
					$pc_link      = $pc_shown->get_permalink();
					$pc_name      = $pc_product->get_name();
					$pc_details   = $pc_variation ? wc_get_formatted_variation( $pc_variation, true, false, false ) : '';
					$pc_buyable   = $pc_shown->is_purchasable() && $pc_shown->is_in_stock();
					?>
					<li class="pc-wishlist-item" <?php echo wp_interactivity_data_wp_context( array( 'productId' => $pc_product->get_id(), 'listId' => $pc_current->ID ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
						<a class="pc-wishlist-item__media" href="<?php echo esc_url( $pc_link ); ?>" tabindex="-1" aria-hidden="true">
							<?php echo $pc_shown->get_image( 'woocommerce_thumbnail', array( 'alt' => '', 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</a>
						<div class="pc-wishlist-item__body">
							<h3 class="pc-wishlist-item__name"><a href="<?php echo esc_url( $pc_link ); ?>"><?php echo esc_html( $pc_name ); ?></a></h3>
							<?php if ( $pc_details ) : ?>
								<p class="pc-wishlist-item__details"><?php echo esc_html( $pc_details ); ?></p>
							<?php endif; ?>
							<p class="pc-wishlist-item__price"><?php echo wp_kses_post( $pc_shown->get_price_html() ); ?></p>
							<p class="pc-wishlist-item__stock<?php echo $pc_shown->is_in_stock() ? '' : ' is-out'; ?>"><?php echo $pc_shown->is_in_stock() ? esc_html__( 'In stock', 'profchoicecore' ) : esc_html__( 'Out of stock', 'profchoicecore' ); ?></p>
						</div>
						<div class="pc-wishlist-item__actions">
							<?php if ( $pc_buyable && $pc_product->is_type( 'simple' ) ) : ?>
								<a class="pc-wishlist__button pc-wishlist__button--primary add_to_cart_button ajax_add_to_cart" href="<?php echo esc_url( $pc_product->add_to_cart_url() ); ?>" data-product_id="<?php echo absint( $pc_product->get_id() ); ?>" data-quantity="1" rel="nofollow"><?php esc_html_e( 'Add to cart', 'profchoicecore' ); ?></a>
							<?php elseif ( $pc_buyable && $pc_variation ) : ?>
								<a class="pc-wishlist__button pc-wishlist__button--primary" href="<?php echo esc_url( add_query_arg( array_merge( array( 'add-to-cart' => $pc_product->get_id(), 'variation_id' => $pc_variation->get_id() ), $pc_variation->get_variation_attributes() ), wc_get_cart_url() ) ); ?>" rel="nofollow"><?php esc_html_e( 'Add to cart', 'profchoicecore' ); ?></a>
							<?php else : ?>
								<a class="pc-wishlist__button" href="<?php echo esc_url( $pc_link ); ?>"><?php esc_html_e( 'View product', 'profchoicecore' ); ?></a>
							<?php endif; ?>
							<?php if ( $pc_multiple && count( $pc_lists ) > 1 ) : ?>
								<?php $pc_select_id = 'pc-wishlist-move-' . $pc_product->get_id(); ?>
								<label class="screen-reader-text" for="<?php echo esc_attr( $pc_select_id ); ?>"><?php echo esc_html( sprintf( /* translators: %s: product name. */ __( 'Move %s to another list', 'profchoicecore' ), $pc_name ) ); ?></label>
								<select class="pc-wishlist__select" id="<?php echo esc_attr( $pc_select_id ); ?>" data-wp-on--change="actions.moveItem">
									<option value=""><?php esc_html_e( 'Move to…', 'profchoicecore' ); ?></option>
									<?php foreach ( $pc_lists as $pc_list ) : ?>
										<?php if ( $pc_list->ID !== $pc_current->ID ) : ?>
											<option value="<?php echo esc_attr( (string) $pc_list->ID ); ?>"><?php echo esc_html( $pc_list->post_title ); ?></option>
										<?php endif; ?>
									<?php endforeach; ?>
								</select>
							<?php endif; ?>
							<button class="pc-wishlist__text pc-wishlist__text--danger" type="button" data-wp-on--click="actions.removeItem" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: product name. */ __( 'Remove %s', 'profchoicecore' ), $pc_name ) ); ?>"><?php esc_html_e( 'Remove', 'profchoicecore' ); ?></button>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<div class="pc-wishlist__empty">
				<p><?php esc_html_e( 'No products saved here yet. Tap the heart on a product to save it.', 'profchoicecore' ); ?></p>
				<a class="pc-wishlist__cta" href="<?php echo esc_url( $pc_shop ); ?>"><?php esc_html_e( 'Continue shopping', 'profchoicecore' ); ?></a>
			</div>
		<?php endif; ?>

		<?php if ( $pc_multiple ) : ?>
			<form class="pc-wishlist__create" data-wp-on--submit="actions.createListPage">
				<label class="pc-wishlist__label" for="pc-wishlist-create"><?php esc_html_e( 'New list', 'profchoicecore' ); ?></label>
				<div class="pc-wishlist__row">
					<input class="pc-wishlist__input" id="pc-wishlist-create" name="name" type="text" maxlength="80" required placeholder="<?php esc_attr_e( 'e.g. Show season', 'profchoicecore' ); ?>">
					<button class="pc-wishlist__button" type="submit"><?php esc_html_e( 'Create list', 'profchoicecore' ); ?></button>
				</div>
			</form>
		<?php endif; ?>
	<?php endif; ?>
</div>
