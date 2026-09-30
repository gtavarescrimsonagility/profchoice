import { describe, expect, it, type Mock } from 'vitest';
import { getContext } from '@wordpress/interactivity';
import { state } from '../../src/newsletter-form/view';

const withStatus = ( status: string ) =>
	( getContext as Mock ).mockReturnValue( { status } );

describe( 'newsletter store', () => {
	it.each( [
		[ 'idle', false, false, false ],
		[ 'submitting', true, false, false ],
		[ 'success', false, true, false ],
		[ 'error', false, false, true ],
	] )(
		'derives flags from %s',
		( status, isSubmitting, isSuccess, isError ) => {
			withStatus( status );
			expect( state.isSubmitting ).toBe( isSubmitting );
			expect( state.isSuccess ).toBe( isSuccess );
			expect( state.isError ).toBe( isError );
		}
	);
} );
