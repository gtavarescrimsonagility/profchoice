/**
 * Pure helpers of the reviews store.
 */

export type Star = 'full' | 'half';

export type Review = {
	id: number;
	author: string;
	rating: number;
	stars: Star[];
	verified: boolean;
	date: string;
	datetime: string;
	text: string;
};

/** A review as the Store API returns it (fields the block uses). */
export type ApiReview = {
	id: number;
	reviewer: string;
	rating: number;
	verified: boolean;
	review: string;
	formatted_date_created: string;
	date_created: string;
};

/**
 * Stars of a rating: whole stars full; the last one full from .5, half from
 * .25; nothing for the rest (no empty stars).
 *
 * @param rating Rating, 0 to 5.
 */
export function stars( rating: number ): Star[] {
	const out: Star[] = [];
	for ( let i = 1; i <= 5; i++ ) {
		const left = rating - ( i - 1 );
		if ( left >= 0.5 ) {
			out.push( 'full' );
		} else if ( left >= 0.25 ) {
			out.push( 'half' );
		}
	}
	return out;
}

/**
 * The visible range: "1" and "–2" for the first two of a page of two.
 *
 * @param start   Index of the first visible review.
 * @param perView Reviews per view.
 * @param total   Reviews in all.
 */
export function range(
	start: number,
	perView: number,
	total: number
): { from: number; toText: string } {
	const from = Math.min( start + 1, total );
	const to = Math.min( start + perView, total );
	return { from, toText: to > from ? `–${ to }` : '' };
}

/**
 * How many reviews to load before showing the view at `start`.
 *
 * @param start   Index of the first review of the view.
 * @param perView Reviews per view.
 * @param loaded  Reviews already loaded.
 * @param total   Reviews in all.
 */
export function missing(
	start: number,
	perView: number,
	loaded: number,
	total: number
): number {
	return Math.max( 0, Math.min( start + perView, total ) - loaded );
}

/**
 * A Store API review in the block's shape.
 *
 * @param item   Store API review.
 * @param toText Turns the review's HTML into text.
 */
export function fromApi(
	item: ApiReview,
	toText: ( html: string ) => string
): Review {
	return {
		id: item.id,
		author: item.reviewer,
		rating: item.rating,
		stars: stars( item.rating ),
		verified: Boolean( item.verified ),
		date: item.formatted_date_created,
		datetime: item.date_created.slice( 0, 10 ),
		text: toText( item.review ).trim(),
	};
}
