/**
 * Pure helpers of the wishlist store.
 */
import type { WishlistData } from './types';

/**
 * Replace the first `%s` or `%d` of a translated string.
 *
 * @param text  Translated string.
 * @param value Value.
 */
export const format = ( text: string, value: string | number ): string =>
	text.replace( /%[sd]/, String( value ) );

/**
 * Saved products after toggling one (optimistic update).
 *
 * @param saved     Saved product IDs.
 * @param productId Product toggled.
 */
export const toggled = ( saved: number[], productId: number ): number[] =>
	saved.includes( productId )
		? saved.filter( ( id ) => id !== productId )
		: [ ...saved, productId ];

/**
 * The visitor data a REST response carries, to copy into the state.
 *
 * @param response REST response.
 */
export const pickData = (
	response: Partial< WishlistData >
): WishlistData => ( {
	saved: Array.isArray( response.saved ) ? response.saved.map( Number ) : [],
	count: Number( response.count ?? 0 ),
	lists: Array.isArray( response.lists ) ? response.lists : [],
	where:
		response.where && typeof response.where === 'object'
			? response.where
			: {},
} );
