/**
 * Product page store: gallery thumbnails pick a color (through the color
 * swatch), the quantity stepper, and the price, stock and add to cart label
 * of the chosen variation (from WooCommerce's variations form events).
 */
import {
	store,
	getContext,
	getElement,
	withScope,
} from '@wordpress/interactivity';

const jquery = () => window.jQuery;

const text = ( html ) => {
	const node = document.createElement( 'div' );
	node.innerHTML = html;
	return node.textContent.trim();
};

const { state } = store( 'profchoice/pdp', {
	state: {
		get isThumbActive() {
			const context = getContext();
			return context.value === context.color;
		},
		get cartLabel() {
			const { price } = getContext();
			return price ? `Add to Cart — ${ price }` : 'Add to Cart';
		},
	},
	actions: {
		pickColor() {
			const { value } = getContext();
			const { ref } = getElement();
			const swatch = ref
				.closest( '.pdp-main__layout' )
				?.querySelector(
					`.pc-swatches [data-value="${ CSS.escape( value ) }"]`
				);
			swatch?.click();
		},
		decrease() {
			step( -1 );
		},
		increase() {
			step( 1 );
		},
	},
	callbacks: {
		init() {
			const context = getContext();
			const { ref } = getElement();
			const form = ref.querySelector( 'form.variations_form' );
			const $ = jquery();
			if ( ! form || ! $ ) {
				return;
			}
			const initial = { price: context.price, stock: context.stock };
			const update = withScope( ( event, variation ) => {
				const color = form.querySelector(
					'select[name="attribute_pa_color"]'
				);
				context.color = color ? color.value : '';
				if ( event.type !== 'found_variation' || ! variation ) {
					context.price = initial.price;
					context.priceHtml = '';
					context.stock = initial.stock;
					return;
				}
				// price_html is empty when every variation costs the same.
				context.priceHtml = variation.price_html || '';
				context.price = variation.price_html
					? text( variation.price_html )
					: initial.price;
				context.stock = variation.is_in_stock
					? 'In Stock'
					: 'Out of Stock';
			} );
			$( form ).on(
				'found_variation.profchoicePdp reset_data.profchoicePdp',
				update
			);
			return () => $( form ).off( '.profchoicePdp' );
		},
		price() {
			const { priceHtml } = getContext();
			const { ref } = getElement();
			const html = priceHtml || ref.dataset.initial;
			if ( ! ref.dataset.initial ) {
				ref.dataset.initial = ref.innerHTML;
				return;
			}
			if ( ref.innerHTML !== html ) {
				ref.innerHTML = html;
			}
		},
	},
} );

/**
 * Change the quantity field by one step, within its min and max.
 *
 * @param {number} delta 1 or -1.
 */
function step( delta ) {
	const { ref } = getElement();
	const input = ref.parentElement.querySelector( 'input[name="quantity"]' );
	if ( ! input ) {
		return;
	}
	const min = Number( input.min ) || 1;
	const max = Number( input.max ) || Infinity;
	const value = Math.min(
		max,
		Math.max( min, ( Number( input.value ) || min ) + delta )
	);
	input.value = String( value );
	input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
}

export { state };
