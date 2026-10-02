/**
 * Wishlist store (profchoice/wishlist), shared by the heart button, the
 * header link, the wishlist block and the "Saved to" dialog.
 *
 * A heart saves the product to the default list; with multiple lists on, it
 * opens the dialog, where the visitor can move it to another (or a new)
 * list. A filled heart opens the dialog to move or remove it. The server render sets the state;
 * every change goes through the REST API and copies its answer back.
 */
import { store, getContext, getElement } from '@wordpress/interactivity';
import { format, pickData, toggled } from './helpers';
import type {
	ItemContext,
	ListContext,
	WishlistData,
	WishlistState,
} from './types';

type Response = Partial< WishlistData > & {
	isSaved?: boolean;
	listId?: number;
	message?: string;
};

// The button that opened the dialog gets the focus back when it closes.
let opener: HTMLElement | null = null;

const dialogElement = (): HTMLDialogElement | null =>
	document.querySelector< HTMLDialogElement >( '.pc-wishlist-dialog' );

/**
 * A REST request as the current visitor.
 *
 * @param path   Path under profchoicecore/v1/.
 * @param method HTTP method.
 * @param body   JSON body.
 */
function* request(
	path: string,
	method = 'POST',
	body?: Record< string, unknown >
): Generator< unknown, Response, unknown > {
	const response = ( yield fetch( state.restUrl + path, {
		method,
		credentials: 'same-origin',
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': state.nonce,
		},
		body: body ? JSON.stringify( body ) : undefined,
	} ) ) as globalThis.Response;
	const json = ( yield response.json().catch( () => ( {} ) ) ) as Response;
	if ( ! response.ok ) {
		throw new Error( json.message ?? response.statusText );
	}
	return json;
}

/**
 * Copy the visitor data of a response into the state.
 *
 * @param response REST response.
 */
const apply = ( response: Response ) => {
	const data = pickData( response );
	state.saved = data.saved;
	state.count = data.count;
	state.lists = data.lists;
	state.where = data.where;
};

/**
 * Show a confirmation alert (if enabled) in a polite live region.
 *
 * @param kind added, removed or error.
 */
const notify = ( kind: 'added' | 'removed' | 'error' ) => {
	if ( ! state.showNotice && kind !== 'error' ) {
		return;
	}
	let region = document.querySelector< HTMLElement >( '.pc-wishlist-toast' );
	if ( ! region ) {
		region = document.createElement( 'div' );
		region.className = 'pc-wishlist-toast woocommerce-notices-wrapper';
		region.setAttribute( 'aria-live', 'polite' );
		document.body.append( region );
	}
	region.innerHTML = state.notices[ kind ];
	const alert = region.firstElementChild;
	window.setTimeout( () => alert?.remove(), 5000 );
};

const openDialog = ( productId: number, productName: string ) => {
	state.dialog = {
		productId,
		productName,
		listId: state.where[ productId ] ?? 0,
	};
	const dialog = dialogElement();
	if ( dialog && ! dialog.open ) {
		dialog.showModal();
	}
};

const { state } = store( 'profchoice/wishlist', {
	state: {
		get isSaved(): boolean {
			const { productId } = getContext< ItemContext >();
			return state.saved.includes( productId );
		},
		get buttonLabel(): string {
			const { productName = '' } = getContext< ItemContext >();
			return format(
				state.isSaved ? state.i18n.remove : state.i18n.add,
				productName
			);
		},
		get linkLabel(): string {
			return format( state.i18n.link, state.count );
		},
		get countDisplay(): string {
			return state.count ? '' : 'none';
		},
		get outlineDisplay(): string {
			return state.isSaved ? 'none' : 'contents';
		},
		get filledDisplay(): string {
			return state.isSaved ? 'contents' : 'none';
		},
		get dialogListName(): string {
			return (
				state.lists.find( ( list ) => list.id === state.dialog.listId )
					?.name ?? ''
			);
		},
		get isDialogList(): boolean {
			const { list } = getContext< ListContext >();
			return list?.id === state.dialog.listId;
		},
	} as WishlistState,
	actions: {
		// Heart: save to the default list and open the dialog; a filled
		// heart opens the dialog to move or remove.
		*toggle( event: MouseEvent ): Generator< unknown, void, unknown > {
			event.preventDefault();
			if ( ! state.canUse ) {
				window.location.assign( state.loginUrl );
				return;
			}
			const { productId, productName = '' } = getContext< ItemContext >();
			const { ref } = getElement();
			opener = ref as HTMLElement | null;

			if ( state.saved.includes( productId ) ) {
				openDialog( productId, productName );
				return;
			}

			// The chosen variation, from this product's variations form only
			// (a related product's heart sits inside another product's page).
			const variation = document.querySelector< HTMLInputElement >(
				`form.variations_form[data-product_id="${ productId }"] input.variation_id`
			);
			state.saved = toggled( state.saved, productId );
			try {
				const response = yield* request( 'wishlist/toggle', 'POST', {
					product_id: productId,
					variation_id: Number( variation?.value || 0 ),
				} );
				apply( response );
				// With several lists, the dialog lets the visitor pick another.
				if ( state.multiple ) {
					openDialog( productId, productName );
				}
				notify( 'added' );
			} catch {
				state.saved = state.saved.filter( ( id ) => id !== productId );
				notify( 'error' );
			}
		},
		// Dialog: move the product to the chosen list.
		*chooseList(): Generator< unknown, void, unknown > {
			const { list } = getContext< ListContext >();
			const from = state.dialog.listId;
			if ( ! list || list.id === from ) {
				return;
			}
			try {
				apply(
					yield* request(
						`wishlists/${ from }/items/${ state.dialog.productId }/move`,
						'POST',
						{ to: list.id }
					)
				);
				state.dialog.listId = list.id;
			} catch {
				notify( 'error' );
			}
		},
		// Dialog: create a list and move the product into it.
		*createList( event: SubmitEvent ): Generator< unknown, void, unknown > {
			event.preventDefault();
			const form = event.currentTarget as HTMLFormElement;
			const name = String(
				new FormData( form ).get( 'name' ) ?? ''
			).trim();
			if ( ! name ) {
				return;
			}
			try {
				const created = yield* request( 'wishlists', 'POST', { name } );
				apply( created );
				if ( created.listId && state.dialog.listId ) {
					apply(
						yield* request(
							`wishlists/${ state.dialog.listId }/items/${ state.dialog.productId }/move`,
							'POST',
							{ to: created.listId }
						)
					);
					state.dialog.listId = created.listId;
				}
				form.reset();
			} catch {
				notify( 'error' );
			}
		},
		// Dialog: remove the product from every list.
		*removeFromDialog(): Generator< unknown, void, unknown > {
			const { productId } = state.dialog;
			try {
				apply(
					yield* request( 'wishlist/toggle', 'POST', {
						product_id: productId,
					} )
				);
				dialogElement()?.close();
				notify( 'removed' );
			} catch {
				notify( 'error' );
			}
		},
		closeDialog(): void {
			dialogElement()?.close();
		},
		dialogClosed(): void {
			opener?.focus();
			opener = null;
		},

		// Wishlist block: remove an item from the shown list.
		*removeItem(): Generator< unknown, void, unknown > {
			const { productId, listId } = getContext< ItemContext >();
			const { ref } = getElement();
			try {
				apply(
					yield* request(
						`wishlists/${ listId }/items/${ productId }`,
						'DELETE'
					)
				);
				const item = ref?.closest( 'li' );
				const items = item?.parentElement;
				item?.remove();
				if ( items && ! items.children.length ) {
					window.location.reload();
				}
			} catch {
				notify( 'error' );
			}
		},
		// Wishlist block: move an item to another list.
		*moveItem( event: Event ): Generator< unknown, void, unknown > {
			const { productId, listId } = getContext< ItemContext >();
			const to = Number( ( event.target as HTMLSelectElement ).value );
			if ( ! to ) {
				return;
			}
			try {
				yield* request(
					`wishlists/${ listId }/items/${ productId }/move`,
					'POST',
					{ to }
				);
				window.location.reload();
			} catch {
				notify( 'error' );
			}
		},
		startRename(): void {
			getContext< ListContext >().renaming = true;
		},
		cancelRename(): void {
			getContext< ListContext >().renaming = false;
		},
		*renameList( event: SubmitEvent ): Generator< unknown, void, unknown > {
			event.preventDefault();
			const name = String(
				new FormData( event.currentTarget as HTMLFormElement ).get(
					'name'
				) ?? ''
			).trim();
			if ( ! name ) {
				return;
			}
			const { listId } = getContext< ListContext >();
			try {
				yield* request( `wishlists/${ listId }`, 'PATCH', { name } );
				window.location.reload();
			} catch {
				notify( 'error' );
			}
		},
		*setDefault(): Generator< unknown, void, unknown > {
			const { listId } = getContext< ListContext >();
			try {
				yield* request( `wishlists/${ listId }`, 'PATCH', {
					default: true,
				} );
				window.location.reload();
			} catch {
				notify( 'error' );
			}
		},
		*deleteList(): Generator< unknown, void, unknown > {
			// eslint-disable-next-line no-alert
			if ( ! window.confirm( state.i18n.confirm ) ) {
				return;
			}
			const { listId } = getContext< ListContext >();
			try {
				yield* request( `wishlists/${ listId }`, 'DELETE' );
				const url = new URL( window.location.href );
				url.searchParams.delete( 'list' );
				window.location.assign( url.toString() );
			} catch {
				notify( 'error' );
			}
		},
		// Wishlist block: create a list and show it.
		*createListPage(
			event: SubmitEvent
		): Generator< unknown, void, unknown > {
			event.preventDefault();
			const form = event.currentTarget as HTMLFormElement;
			const name = String(
				new FormData( form ).get( 'name' ) ?? ''
			).trim();
			if ( ! name ) {
				return;
			}
			try {
				const created = yield* request( 'wishlists', 'POST', { name } );
				const url = new URL( window.location.href );
				url.searchParams.set( 'list', String( created.listId ?? '' ) );
				window.location.assign( url.toString() );
			} catch {
				notify( 'error' );
			}
		},
	},
} );

export { state };
