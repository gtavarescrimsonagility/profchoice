import { describe, expect, it, type Mock } from 'vitest';
import { getContext } from '@wordpress/interactivity';
import { state } from '../src/newsletter-form/view';

const withStatus = ( status: string, hasPending = true ) =>
	( getContext as Mock ).mockReturnValue( { status, hasPending } );

describe( 'newsletter store', () => {
	it.each( [
		[ 'idle', false, false, false, false, false ],
		[ 'submitting', true, false, false, false, false ],
		[ 'success', false, true, false, true, false ],
		[ 'pending', false, false, true, true, false ],
		[ 'error', false, false, false, false, true ],
	] )(
		'derives flags from %s',
		( status, isSubmitting, isSuccess, isPending, isDone, isError ) => {
			withStatus( status );
			expect( state.isSubmitting ).toBe( isSubmitting );
			expect( state.isSuccess ).toBe( isSuccess );
			expect( state.isPending ).toBe( isPending );
			expect( state.isDone ).toBe( isDone );
			expect( state.isError ).toBe( isError );
		}
	);

	it( 'shows the success message when a form has no pending one', () => {
		withStatus( 'pending', false );
		expect( state.isSuccess ).toBe( true );
		expect( state.isPending ).toBe( false );
	} );
} );
