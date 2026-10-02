<?php
/**
 * "Saved to" dialog (printed once in the footer when a heart is on the page).
 *
 * @package ProfChoiceCore
 */

use ProfChoiceCore\Wishlist\Settings;
use ProfChoiceCore\Wishlist\Wishlist;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pc_close = function_exists( 'wp_get_icon' ) ? trim( wp_get_icon( 'profchoice/close', array( 'size' => null, 'class' => 'pc-wishlist-dialog__close-icon' ) ) ) : '';
?>
<dialog class="pc-wishlist-dialog" aria-labelledby="pc-wishlist-dialog-title" data-wp-interactive="<?php echo esc_attr( Wishlist::STORE ); ?>" data-wp-on--close="actions.dialogClosed">
	<div class="pc-wishlist-dialog__head">
		<div class="pc-wishlist-dialog__heading">
			<p class="pc-wishlist-dialog__eyebrow"><?php esc_html_e( 'Saved to', 'profchoicecore' ); ?></p>
			<h2 class="pc-wishlist-dialog__title" id="pc-wishlist-dialog-title" data-wp-text="state.dialogListName"></h2>
		</div>
		<button class="pc-wishlist-dialog__close" type="button" aria-label="<?php esc_attr_e( 'Close', 'profchoicecore' ); ?>" data-wp-on--click="actions.closeDialog">
			<?php echo $pc_close ? $pc_close : '<span aria-hidden="true">&times;</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</button>
	</div>
	<p class="pc-wishlist-dialog__product" data-wp-text="state.dialog.productName"></p>

	<?php if ( Settings::multiple_lists() ) : ?>
	<fieldset class="pc-wishlist-dialog__lists">
		<legend class="pc-wishlist-dialog__legend"><?php esc_html_e( 'Choose a list', 'profchoicecore' ); ?></legend>
		<template data-wp-each--list="state.lists" data-wp-each-key="context.list.id">
			<label class="pc-wishlist-dialog__option">
				<input type="radio" name="pc-wishlist-list" data-wp-bind--value="context.list.id" data-wp-bind--checked="state.isDialogList" data-wp-on--change="actions.chooseList">
				<span class="pc-wishlist-dialog__name" data-wp-text="context.list.name"></span>
				<span class="pc-wishlist-dialog__count" data-wp-text="context.list.count"></span>
			</label>
		</template>
	</fieldset>

	<form class="pc-wishlist-dialog__create" data-wp-on--submit="actions.createList">
		<label class="pc-wishlist-dialog__legend" for="pc-wishlist-new"><?php esc_html_e( 'New list', 'profchoicecore' ); ?></label>
		<div class="pc-wishlist-dialog__row">
			<input class="pc-wishlist-dialog__input" id="pc-wishlist-new" name="name" type="text" maxlength="80" required placeholder="<?php esc_attr_e( 'e.g. Show season', 'profchoicecore' ); ?>">
			<button class="pc-wishlist-dialog__button" type="submit"><?php esc_html_e( 'Create', 'profchoicecore' ); ?></button>
		</div>
	</form>
	<?php endif; ?>

	<div class="pc-wishlist-dialog__foot">
		<button class="pc-wishlist-dialog__remove" type="button" data-wp-on--click="actions.removeFromDialog"><?php esc_html_e( 'Remove from wishlist', 'profchoicecore' ); ?></button>
		<a class="pc-wishlist-dialog__link" href="<?php echo esc_url( Settings::page_url() ); ?>"><?php esc_html_e( 'View wishlist', 'profchoicecore' ); ?></a>
		<button class="pc-wishlist-dialog__button pc-wishlist-dialog__button--primary" type="button" data-wp-on--click="actions.closeDialog"><?php esc_html_e( 'Done', 'profchoicecore' ); ?></button>
	</div>
</dialog>
