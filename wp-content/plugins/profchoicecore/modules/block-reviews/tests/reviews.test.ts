import { describe, expect, it } from 'vitest';
import { fromApi, missing, range, stars } from '../src/store/helpers';

describe( 'review stars', () => {
	it( 'shows only the stars of the rating', () => {
		expect( stars( 4 ) ).toEqual( [ 'full', 'full', 'full', 'full' ] );
		expect( stars( 5 ) ).toHaveLength( 5 );
		expect( stars( 4.86 ) ).toEqual( Array( 5 ).fill( 'full' ) );
		expect( stars( 4.3 ) ).toEqual( [
			'full',
			'full',
			'full',
			'full',
			'half',
		] );
		expect( stars( 4.2 ) ).toEqual( [ 'full', 'full', 'full', 'full' ] );
	} );
} );

describe( 'reviews pager', () => {
	it( 'describes the visible range', () => {
		expect( range( 0, 2, 7 ) ).toEqual( { from: 1, toText: '–2' } );
		expect( range( 6, 2, 7 ) ).toEqual( { from: 7, toText: '' } );
		expect( range( 0, 1, 7 ) ).toEqual( { from: 1, toText: '' } );
	} );

	it( 'counts the reviews to load for a view', () => {
		expect( missing( 2, 2, 2, 7 ) ).toBe( 2 );
		expect( missing( 6, 2, 6, 7 ) ).toBe( 1 );
		expect( missing( 1, 1, 2, 7 ) ).toBe( 0 );
	} );

	it( 'maps a Store API review', () => {
		expect(
			fromApi(
				{
					id: 3,
					reviewer: 'Daniel R.',
					rating: 5,
					verified: false,
					review: '<p>Great fit.</p>\n',
					formatted_date_created: 'May 18, 2026',
					date_created: '2026-05-18T09:40:00',
				},
				( html ) => html.replace( /<[^>]+>/g, '' )
			)
		).toEqual( {
			id: 3,
			author: 'Daniel R.',
			rating: 5,
			stars: [ 'full', 'full', 'full', 'full', 'full' ],
			verified: false,
			date: 'May 18, 2026',
			datetime: '2026-05-18',
			text: 'Great fit.',
		} );
	} );
} );
