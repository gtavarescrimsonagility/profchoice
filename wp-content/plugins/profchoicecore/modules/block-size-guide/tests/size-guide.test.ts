import { describe, expect, it } from 'vitest';
import { isBackdropClick } from '../src/store/helpers';

const dialog = {
	getBoundingClientRect: () => ( {
		left: 100,
		right: 500,
		top: 50,
		bottom: 450,
	} ),
};

describe( 'size guide backdrop', () => {
	it( 'closes on a click outside the dialog box', () => {
		expect( isBackdropClick( dialog as never, dialog, 20, 20 ) ).toBe(
			true
		);
	} );

	it( 'stays open on clicks inside the box or on its content', () => {
		expect( isBackdropClick( dialog as never, dialog, 200, 200 ) ).toBe(
			false
		);
		expect( isBackdropClick( {} as never, dialog, 20, 20 ) ).toBe( false );
	} );
} );
