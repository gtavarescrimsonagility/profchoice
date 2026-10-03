/**
 * Reviews store (profchoice/reviews). The first page is server-rendered;
 * Previous/Next (and swiping, on mobile) move through the reviews, loading
 * the missing ones from the Store API with a skeleton while they come.
 * Collapsed, the block opens from any #reviews link (and on load when the
 * URL points at it), then scrolls to itself.
 */
import {
	store,
	getContext,
	getElement,
	withScope,
} from '@wordpress/interactivity';
import {
	fromApi,
	missing,
	range,
	type ApiReview,
	type Review,
} from './helpers';

type Context = {
	productId: number;
	total: number;
	start: number;
	perView: number;
	loading: boolean;
	expanded: boolean;
	formOpen: boolean;
	items: Review[];
	star?: string;
	review?: Review;
};

const MOBILE = '(width <= 30rem)';

const reduced = (): boolean =>
	window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

const htmlToText = ( html: string ): string =>
	new DOMParser().parseFromString( html, 'text/html' ).body.textContent ?? '';

/**
 * The block's list element.
 *
 * @param ref A node of the block.
 */
const listOf = ( ref: Element | null ): HTMLElement | null =>
	ref?.closest( '.pc-reviews' )?.querySelector( '.pc-reviews__list' ) ?? null;

// Lists being scrolled by the store (not by the visitor's swipe).
const moving = new WeakSet< HTMLElement >();

/**
 * Scroll the list so the review at `index` is the first visible one.
 *
 * @param list  List element.
 * @param index Review index (a skeleton holds the place of a missing one).
 */
const scrollToIndex = ( list: HTMLElement | null, index: number ) => {
	const card = list?.children[ index ] as HTMLElement | undefined;
	if ( ! list || ! card ) {
		return;
	}
	const left = card.offsetLeft - list.offsetLeft;
	if ( Math.abs( list.scrollLeft - left ) < 1 ) {
		return;
	}
	moving.add( list );
	const done = () => moving.delete( list );
	list.addEventListener( 'scrollend', done, { once: true } );
	window.setTimeout( done, 1000 );
	list.scrollTo( { left, behavior: reduced() ? 'auto' : 'smooth' } );
};

/**
 * Load `count` more reviews from the Store API into the context, with the
 * skeletons shown meanwhile.
 *
 * @param context Block context.
 * @param count   Reviews to load.
 */
function* fetchReviews(
	context: Context,
	count: number
): Generator< unknown, void, unknown > {
	context.loading = true;
	try {
		const url = new URL( state.restUrl, window.location.href );
		url.searchParams.set( 'product_id', String( context.productId ) );
		url.searchParams.set( 'per_page', String( count ) );
		url.searchParams.set( 'offset', String( context.items.length ) );
		url.searchParams.set( 'orderby', 'date' );
		url.searchParams.set( 'order', 'desc' );
		const response = ( yield fetch( url ) ) as Response;
		const data = ( yield response.json() ) as ApiReview[];
		const known = new Set( context.items.map( ( item ) => item.id ) );
		context.items = [
			...context.items,
			...data
				.map( ( item ) => fromApi( item, htmlToText ) )
				.filter( ( item ) => ! known.has( item.id ) ),
		];
	} finally {
		context.loading = false;
	}
}

const { state, actions } = store( 'profchoice/reviews', {
	state: {
		get starFull(): string {
			return getContext< Context >().star === 'full'
				? 'contents'
				: 'none';
		},
		get starHalf(): string {
			return getContext< Context >().star === 'half'
				? 'contents'
				: 'none';
		},
		get ratingLabel(): string {
			const { review } = getContext< Context >();
			return review
				? state.ratingOf.replace( '%d', String( review.rating ) )
				: '';
		},
		get from(): number {
			const { start, perView, total } = getContext< Context >();
			return range( start, perView, total ).from;
		},
		get toText(): string {
			const { start, perView, total } = getContext< Context >();
			return range( start, perView, total ).toText;
		},
		get cannotPrev(): boolean {
			const { start, loading } = getContext< Context >();
			return loading || start <= 0;
		},
		get cannotNext(): boolean {
			const { start, perView, total, loading } = getContext< Context >();
			return loading || start + perView >= total;
		},
	} as unknown as {
		restUrl: string;
		ratingOf: string;
		[ key: string ]: unknown;
	},
	actions: {
		toggleForm(): void {
			const context = getContext< Context >();
			context.formOpen = ! context.formOpen;
			if ( context.formOpen ) {
				const { ref } = getElement();
				const id = ref?.getAttribute( 'aria-controls' );
				window.requestAnimationFrame( () =>
					document
						.getElementById( id ?? '' )
						?.querySelector< HTMLElement >( 'input, textarea' )
						?.focus()
				);
			}
		},
		*next(): Generator< unknown, void, unknown > {
			const context = getContext< Context >();
			const list = listOf( getElement().ref );
			// The last view is full too (6–7 of 7), not one review alone.
			const start = Math.min(
				context.start + context.perView,
				Math.max( 0, context.total - context.perView )
			);
			context.start = start;
			const count = missing(
				start,
				context.perView,
				context.items.length,
				context.total
			);
			if ( ! count ) {
				scrollToIndex( list, start );
				return;
			}
			if ( context.loading ) {
				return;
			}
			// One move: the skeletons (shown with loading) hold the places of
			// the reviews to load, so the list scrolls once, to them.
			const fetching = fetchReviews( context, count );
			window.requestAnimationFrame( () => scrollToIndex( list, start ) );
			yield* fetching;
		},
		prev(): void {
			const context = getContext< Context >();
			context.start = Math.max( 0, context.start - context.perView );
			scrollToIndex( listOf( getElement().ref ), context.start );
		},
		// Mobile swipe: follow the visible review, and load the next one
		// when the last loaded review comes into view. Moves made by the
		// store itself (Previous/Next) are ignored.
		*scrolled( event: Event ): Generator< unknown, void, unknown > {
			const context = getContext< Context >();
			const list = event.currentTarget as HTMLElement;
			const card = list.firstElementChild as HTMLElement | null;
			if (
				! card ||
				context.loading ||
				context.perView !== 1 ||
				moving.has( list )
			) {
				return;
			}
			const gap = parseFloat( getComputedStyle( list ).columnGap ) || 0;
			const index = Math.round(
				list.scrollLeft / ( card.offsetWidth + gap )
			);
			context.start = Math.min( index, Math.max( 0, context.total - 1 ) );
			if (
				context.perView === 1 &&
				index >= context.items.length - 1 &&
				context.items.length < context.total
			) {
				yield* fetchReviews( context, 1 );
			}
		},
	},
	callbacks: {
		init(): () => void {
			const context = getContext< Context >();
			const { ref } = getElement();
			const media = window.matchMedia( MOBILE );
			const setView = withScope( () => {
				context.perView = media.matches ? 1 : 2;
			} );
			setView();
			media.addEventListener( 'change', setView );

			const reveal = withScope( () => {
				context.expanded = true;
				window.requestAnimationFrame( () =>
					( ref as HTMLElement | null )?.scrollIntoView( {
						behavior: reduced() ? 'auto' : 'smooth',
					} )
				);
			} );
			const onClick = ( event: MouseEvent ) => {
				const link = ( event.target as Element | null )?.closest( 'a' );
				if (
					link &&
					link.hash === '#reviews' &&
					link.pathname === window.location.pathname
				) {
					event.preventDefault();
					window.history.replaceState( null, '', '#reviews' );
					reveal();
				}
			};
			document.addEventListener( 'click', onClick );
			if ( /^#(reviews|comment-\d+)$/.test( window.location.hash ) ) {
				reveal();
			}
			return () => {
				media.removeEventListener( 'change', setView );
				document.removeEventListener( 'click', onClick );
			};
		},
	},
} );

export { state, actions };
