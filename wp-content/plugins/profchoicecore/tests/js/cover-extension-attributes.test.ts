import { describe, expect, it, vi } from 'vitest';

vi.mock( '@wordpress/hooks', () => ( { addFilter: vi.fn() } ) );
vi.mock( '@wordpress/compose', () => ( {
	createHigherOrderComponent: vi.fn( ( fn: unknown ) => fn ),
} ) );
vi.mock( '@wordpress/block-editor', () => ( { InspectorControls: vi.fn() } ) );
vi.mock( '@wordpress/components', () => ( {} ) );
vi.mock( '@wordpress/i18n', () => ( { __: ( text: string ) => text } ) );

import {
	addAttributes,
	PLAYBACK_ATTRIBUTES,
} from '../../src/cover-extension/index';

describe( 'cover playback attributes', () => {
	it( 'adds autoplay, loop and muted to core/cover', () => {
		const settings = addAttributes(
			{ attributes: { url: { type: 'string' } } },
			'core/cover'
		);
		expect( settings.attributes ).toEqual( {
			url: { type: 'string' },
			...PLAYBACK_ATTRIBUTES,
		} );
	} );

	it( 'leaves other blocks alone', () => {
		const settings = { attributes: {} };
		expect( addAttributes( settings, 'core/group' ) ).toBe( settings );
	} );
} );
