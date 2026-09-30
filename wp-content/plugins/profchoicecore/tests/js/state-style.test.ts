import { describe, expect, it } from 'vitest';
import { stateStyleProps } from '../../src/shared/state-style';

describe( 'stateStyleProps', () => {
	it( 'maps set values to variables and has-* classes', () => {
		const { style, className } = stateStyleProps( 'carousel-dot', {
			active: {
				background: '#c32627',
				border: { color: '#fff', width: '1px' },
				width: '22px',
			},
			inactive: { opacity: '0.35' },
		} );

		expect( style ).toEqual( {
			'--pc-carousel-dot-active-background': '#c32627',
			'--pc-carousel-dot-active-border-color': '#fff',
			'--pc-carousel-dot-active-border-width': '1px',
			'--pc-carousel-dot-active-width': '22px',
			'--pc-carousel-dot-inactive-opacity': '0.35',
		} );
		expect( className.split( ' ' ) ).toEqual( [
			'has-active-background',
			'has-active-border-color',
			'has-active-border-width',
			'has-active-width',
			'has-inactive-opacity',
		] );
	} );

	it( 'skips empty and missing states', () => {
		expect(
			stateStyleProps( 'carousel-arrow', {
				active: { background: '' },
				inactive: undefined,
			} )
		).toEqual( { style: {}, className: '' } );
	} );
} );
