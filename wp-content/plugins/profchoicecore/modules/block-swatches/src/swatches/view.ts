/**
 * Swatches store. Inside a classic variations form, the hidden select stays
 * the field WooCommerce's add-to-cart-variation script reads: a swatch sets
 * it and fires `change`, and the form's events keep the swatches in sync
 * (selection, and values that are out of stock or not offered with the
 * other selections).
 */
import {
	store,
	getContext,
	getElement,
	withScope,
} from '@wordpress/interactivity';
import { missingOptions, nextValue, unavailableValues } from './availability';
import type { SwatchesContext, Variation } from './types';

type JQueryLike = ( target: Element ) => {
	on: ( events: string, handler: () => void ) => unknown;
	off: ( events: string ) => unknown;
	trigger: ( event: string ) => unknown;
};

const EVENTS = [
	'woocommerce_update_variation_values',
	'found_variation',
	'reset_data',
	'check_variations',
];

// One jQuery event namespace per block, so each removes only its handlers.
let instances = 0;

const jquery = (): JQueryLike | undefined =>
	( window as unknown as { jQuery?: JQueryLike } ).jQuery;

const values = ( context: SwatchesContext ): string[] =>
	Object.keys( context.labels );

/**
 * The block's form and select, when it sits in a variations form.
 * @param element
 * @param name
 */
const field = ( element: Element | null, name: string ) => {
	const form = element?.closest< HTMLFormElement >( 'form.variations_form' );
	const select = form?.querySelector< HTMLSelectElement >(
		`select[name="${ CSS.escape( name ) }"]`
	);
	return form && select ? { form, select } : undefined;
};

/**
 * Copy the form's state to the swatches.
 * @param context
 * @param form
 * @param select
 */
const sync = (
	context: SwatchesContext,
	form: HTMLFormElement,
	select: HTMLSelectElement
) => {
	const selections: Record< string, string > = {};
	form.querySelectorAll< HTMLSelectElement >( '.variations select' ).forEach(
		( other ) => {
			selections[ other.name ] = other.value;
		}
	);

	let variations: Variation[] = [];
	try {
		const data = JSON.parse( form.dataset.product_variations ?? 'false' );
		variations = Array.isArray( data ) ? data : [];
	} catch {
		variations = [];
	}

	const all = values( context );
	const disabled = new Set( [
		...missingOptions( all, Array.from( select.options ) ),
		...( variations.length
			? unavailableValues( variations, context.name, all, selections )
			: [] ),
	] );

	context.selected = select.value;
	context.disabled = all.filter( ( value ) => disabled.has( value ) );
};

const { state, actions } = store( 'profchoice/swatches', {
	state: {
		get isChecked(): boolean {
			const context = getContext< SwatchesContext >();
			return context.selected === context.value;
		},
		get isDisabled(): boolean {
			const context = getContext< SwatchesContext >();
			return context.disabled.includes( context.value ?? '' );
		},
		// Roving tabindex: only the checked swatch (or the first) is tabbable.
		get tabIndex(): number {
			const context = getContext< SwatchesContext >();
			const current = context.selected || context.first;
			return context.value === current ? 0 : -1;
		},
		get selectedLabel(): string {
			const context = getContext< SwatchesContext >();
			return context.labels[ context.selected ] ?? '';
		},
	},
	actions: {
		select(): void {
			const context = getContext< SwatchesContext >();
			const value = context.value ?? '';
			if ( context.disabled.includes( value ) ) {
				return;
			}
			context.selected = value;

			const { ref } = getElement();
			const target = field( ref, context.name );
			if ( ! target || target.select.value === value ) {
				return;
			}
			target.select.value = value;
			const $ = jquery();
			if ( $ ) {
				$( target.select ).trigger( 'change' );
			} else {
				target.select.dispatchEvent(
					new Event( 'change', { bubbles: true } )
				);
			}
		},
		navigate( event: KeyboardEvent ): void {
			const steps: Record< string, 1 | -1 > = {
				ArrowRight: 1,
				ArrowDown: 1,
				ArrowLeft: -1,
				ArrowUp: -1,
			};
			const step = steps[ event.key ];
			if ( ! step ) {
				return;
			}
			event.preventDefault();

			const context = getContext< SwatchesContext >();
			const next = nextValue(
				values( context ),
				context.disabled,
				context.value ?? '',
				step
			);
			const { ref } = getElement();
			const group = ref?.closest( '[role="radiogroup"]' );
			const button = group?.querySelector< HTMLButtonElement >(
				`[data-value="${ CSS.escape( next ?? '' ) }"]`
			);
			if ( button ) {
				button.focus();
				button.click();
			}
		},
	},
	callbacks: {
		init(): ( () => void ) | void {
			const context = getContext< SwatchesContext >();
			const { ref } = getElement();
			const target = field( ref, context.name );
			if ( ! target ) {
				return;
			}
			const { form, select } = target;
			const update = withScope( () => sync( context, form, select ) );
			update();

			const $ = jquery();
			if ( ! $ ) {
				return;
			}
			const namespace = `.profchoiceSwatches${ ++instances }`;
			$( form ).on(
				EVENTS.map( ( event ) => event + namespace ).join( ' ' ),
				update
			);
			return () => {
				$( form ).off( namespace );
			};
		},
	},
} );

export { state, actions };
