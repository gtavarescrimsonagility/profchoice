/**
 * Pure helpers: which swatches can be picked given the other selections.
 */
import type { Variation } from './types';

const matches = ( stored: string | undefined, wanted: string ): boolean =>
	! stored || stored === wanted;

/**
 * Values of `name` that no active, in-stock variation offers together with
 * the other selected attributes (an empty selection matches anything).
 *
 * @param variations WooCommerce variations.
 * @param name       Field name (`attribute_pa_size`).
 * @param values     Values of the swatches.
 * @param selections Current field values, by field name.
 */
export function unavailableValues(
	variations: Variation[],
	name: string,
	values: string[],
	selections: Record< string, string >
): string[] {
	const others = Object.entries( selections ).filter(
		( [ key, value ] ) => key !== name && value !== ''
	);

	return values.filter(
		( value ) =>
			! variations.some(
				( variation ) =>
					variation.is_in_stock !== false &&
					variation.variation_is_active !== false &&
					matches( variation.attributes[ name ], value ) &&
					others.every( ( [ key, other ] ) =>
						matches( variation.attributes[ key ], other )
					)
			)
	);
}

/**
 * Values the form's select no longer offers: options WooCommerce removed or
 * disabled after another attribute changed.
 *
 * @param values  Values of the swatches.
 * @param options The select's options.
 */
export function missingOptions(
	values: string[],
	options: Array< { value: string; disabled: boolean } >
): string[] {
	const enabled = new Set(
		options
			.filter( ( option ) => ! option.disabled && option.value !== '' )
			.map( ( option ) => option.value )
	);
	return values.filter( ( value ) => ! enabled.has( value ) );
}

/**
 * Next enabled value for arrow-key navigation, wrapping around.
 *
 * @param values   Values in order.
 * @param disabled Values that cannot be picked.
 * @param current  Value with focus.
 * @param step     1 (next) or -1 (previous).
 */
export function nextValue(
	values: string[],
	disabled: string[],
	current: string,
	step: 1 | -1
): string | undefined {
	const start = values.indexOf( current );
	for ( let i = 1; i <= values.length; i++ ) {
		const value =
			values[
				( start + step * i + values.length * ( i + 1 ) ) % values.length
			];
		if ( ! disabled.includes( value ) ) {
			return value;
		}
	}
	return undefined;
}
