import { describe, expect, it } from 'vitest';
import { format, pickData, toggled } from '../src/store/helpers';

describe( 'wishlist helpers', () => {
	it( 'formats translated strings', () => {
		expect( format( 'Add %s to wishlist', 'Skid Boots' ) ).toBe(
			'Add Skid Boots to wishlist'
		);
		expect( format( 'Wishlist, %d items', 3 ) ).toBe( 'Wishlist, 3 items' );
	} );

	it( 'toggles a saved product', () => {
		expect( toggled( [ 1, 2 ], 3 ) ).toEqual( [ 1, 2, 3 ] );
		expect( toggled( [ 1, 2, 3 ], 2 ) ).toEqual( [ 1, 3 ] );
	} );

	it( 'reads the visitor data of a response', () => {
		expect(
			pickData( {
				saved: [ '748' as unknown as number ],
				count: 1,
				lists: [
					{ id: 9, name: 'Wishlist', isDefault: true, count: 1 },
				],
				where: { 748: 9 },
			} )
		).toEqual( {
			saved: [ 748 ],
			count: 1,
			lists: [ { id: 9, name: 'Wishlist', isDefault: true, count: 1 } ],
			where: { 748: 9 },
		} );
		expect( pickData( {} ) ).toEqual( {
			saved: [],
			count: 0,
			lists: [],
			where: {},
		} );
	} );
} );
