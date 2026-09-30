import { describe, expect, it } from 'vitest';
import { resolvePreset, stateStyleProps } from '../../src/shared/state-style';

describe( 'stateStyleProps', () => {
	it( 'maps set values to variables and has-* classes', () => {
		const { style, className } = stateStyleProps( 'carousel-dot', {
			active: {
				background: '#c32627',
				border: { color: '#fff', width: '1px' },
				width: '22px',
				radius: '4px',
			},
			default: { opacity: '0.35' },
		} );

		expect( style ).toEqual( {
			'--pc-carousel-dot-active-background': '#c32627',
			'--pc-carousel-dot-active-width': '22px',
			'--pc-carousel-dot-active-border-color': '#fff',
			'--pc-carousel-dot-active-border-width': '1px',
			'--pc-carousel-dot-active-radius': '4px',
			'--pc-carousel-dot-default-opacity': '0.35',
		} );
		expect( className.split( ' ' ) ).toEqual( [
			'has-active-background',
			'has-active-width',
			'has-active-border-color',
			'has-active-border-width',
			'has-active-radius',
			'has-default-opacity',
		] );
	} );

	it( 'uses per-side and per-corner properties when unlinked', () => {
		const { style } = stateStyleProps( 'carousel-arrow', {
			active: {
				border: { top: { width: '2px' }, left: { color: '#000' } },
				radius: { topLeft: '50%', bottomRight: '0' },
			},
		} );

		expect( style ).toEqual( {
			'--pc-carousel-arrow-active-border-top-width': '2px',
			'--pc-carousel-arrow-active-border-left-color': '#000',
			'--pc-carousel-arrow-active-radius-top-left': '50%',
			'--pc-carousel-arrow-active-radius-bottom-right': '0',
		} );
	} );

	it( 'skips empty and missing states', () => {
		expect(
			stateStyleProps( 'carousel-arrow', {
				active: { background: '' },
				inactive: undefined,
			} )
		).toEqual( { style: {}, className: '' } );
	} );

	it( 'resolves preset references to CSS variables', () => {
		expect( resolvePreset( 'var:preset|dimension|22' ) ).toBe(
			'var(--wp--preset--dimension--22)'
		);
		expect( resolvePreset( '12px' ) ).toBe( '12px' );
	} );
} );
