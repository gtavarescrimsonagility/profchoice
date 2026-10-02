import { describe, expect, it } from 'vitest';
import {
	missingOptions,
	nextValue,
	unavailableValues,
} from '../src/swatches/availability';
import type { Variation } from '../src/swatches/types';

const variation = (
	color: string,
	size: string,
	inStock = true
): Variation => ( {
	attributes: { attribute_pa_color: color, attribute_pa_size: size },
	is_in_stock: inStock,
	variation_is_active: true,
} );

const variations: Variation[] = [
	variation( 'black', 'xs', false ),
	variation( 'black', 's' ),
	variation( 'black', 'm' ),
	variation( 'navy', 's' ),
	variation( 'navy', 'm', false ),
];

describe( 'unavailableValues', () => {
	it( 'disables sizes no in-stock variation offers', () => {
		expect(
			unavailableValues(
				variations,
				'attribute_pa_size',
				[ 'xs', 's', 'm' ],
				{ attribute_pa_color: '', attribute_pa_size: '' }
			)
		).toEqual( [ 'xs' ] );
	} );

	it( 'takes the other selections into account', () => {
		expect(
			unavailableValues(
				variations,
				'attribute_pa_size',
				[ 'xs', 's', 'm' ],
				{ attribute_pa_color: 'navy', attribute_pa_size: 's' }
			)
		).toEqual( [ 'xs', 'm' ] );
		expect(
			unavailableValues(
				variations,
				'attribute_pa_color',
				[ 'black', 'navy' ],
				{ attribute_pa_color: 'black', attribute_pa_size: 'm' }
			)
		).toEqual( [ 'navy' ] );
	} );

	it( 'treats an empty variation attribute as any value', () => {
		expect(
			unavailableValues(
				[ variation( '', 'm' ) ],
				'attribute_pa_color',
				[ 'black', 'white' ],
				{ attribute_pa_size: 'm' }
			)
		).toEqual( [] );
	} );
} );

describe( 'missingOptions', () => {
	it( 'returns values the select removed or disabled', () => {
		expect(
			missingOptions(
				[ 'black', 'navy', 'white' ],
				[
					{ value: '', disabled: false },
					{ value: 'black', disabled: false },
					{ value: 'navy', disabled: true },
				]
			)
		).toEqual( [ 'navy', 'white' ] );
	} );
} );

describe( 'nextValue', () => {
	const values = [ 'xs', 's', 'm', 'l' ];

	it( 'skips disabled values and wraps around', () => {
		expect( nextValue( values, [ 'xs' ], 'l', 1 ) ).toBe( 's' );
		expect( nextValue( values, [ 'xs' ], 's', -1 ) ).toBe( 'l' );
		expect( nextValue( values, [], 's', 1 ) ).toBe( 'm' );
	} );

	it( 'returns undefined when everything else is disabled', () => {
		expect( nextValue( [ 'a', 'b' ], [ 'a', 'b' ], 'a', 1 ) ).toBe(
			undefined
		);
	} );
} );
