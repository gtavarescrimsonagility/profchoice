import { describe, expect, it, vi, type Mock } from 'vitest';

vi.mock( '@wordpress/i18n', () => ( { __: ( text: string ) => text } ) );
vi.mock( '@wordpress/rich-text', () => ( {
	registerFormatType: vi.fn(),
	insertObject: vi.fn(),
} ) );
vi.mock( '@wordpress/block-editor', () => ( {
	RichTextToolbarButton: vi.fn(),
} ) );
vi.mock( '@wordpress/element', () => ( { useState: vi.fn() } ) );
vi.mock( '../src/inline-icon/icon-picker', () => ( { default: vi.fn() } ) );

import { registerFormatType } from '@wordpress/rich-text';
import { NAME } from '../src/inline-icon/index';
import { PLACEHOLDER_SRC } from '../src/inline-icon/placeholder';

// Registered on import; read before restoreMocks clears the calls.
const [ name, settings ] = ( registerFormatType as Mock ).mock.calls[ 0 ];

describe( 'inline icon format', () => {
	it( 'saves the icon, alt and placeholder src', () => {
		expect( name ).toBe( NAME );
		expect( settings ).toMatchObject( {
			tagName: 'img',
			attributes: { icon: 'data-icon', alt: 'alt', url: 'src' },
		} );
	} );

	it( 'uses a transparent GIF as the placeholder', () => {
		expect( PLACEHOLDER_SRC ).toMatch( /^data:image\/gif;base64,R0lGOD/ );
	} );
} );
