import { describe, expect, it, type Mock } from 'vitest';
import { getContext } from '@wordpress/interactivity';
import {
	applyTransitionOverrides,
	baseOptions,
} from '../../src/carousel/embla-options';
import { formatAnnouncement, state } from '../../src/carousel/view';

const mockContext = ( context: Record< string, unknown > ) =>
	( getContext as Mock ).mockReturnValue( context );

describe( 'embla options', () => {
	it( 'forces the fade requirements', () => {
		const options = applyTransitionOverrides(
			{ align: 'start', containScroll: 'trimSnaps', dragFree: true },
			'fade'
		);
		expect( options ).toMatchObject( {
			align: 'center',
			containScroll: false,
			dragFree: false,
			slidesToScroll: 1,
		} );
	} );

	it( 'leaves slide options alone', () => {
		const options = { align: 'start' as const };
		expect( applyTransitionOverrides( options, 'slide' ) ).toBe( options );
	} );

	it( 'uses the fastest duration for reduced motion', () => {
		expect( baseOptions( true, true ).duration ).toBe( 20 );
		expect( baseOptions( false, false ) ).toMatchObject( {
			loop: false,
			duration: 30,
		} );
	} );
} );

describe( 'carousel store', () => {
	it( 'formats the announcement', () => {
		expect(
			formatAnnouncement(
				'Slide {{currentSlide}} of {{totalSlides}}',
				2,
				4
			)
		).toBe( 'Slide 2 of 4' );
	} );

	it( 'marks the active slide', () => {
		mockContext( { selectedIndex: 1, index: 1 } );
		expect( state.isSlideActive ).toBe( true );
		mockContext( { selectedIndex: 1, index: 0 } );
		expect( state.isSlideActive ).toBe( false );
	} );

	it( 'marks the active dot and labels it', () => {
		mockContext( {
			selectedIndex: 2,
			snap: { index: 2 },
			dotLabelPattern: 'Go to slide %d',
		} );
		expect( state.isDotActive ).toBe( true );
		expect( state.dotLabel ).toBe( 'Go to slide 3' );
		mockContext( { selectedIndex: 0, snap: { index: 2 } } );
		expect( state.isDotActive ).toBe( false );
	} );

	it( 'exposes scroll availability', () => {
		mockContext( { canScrollPrev: false, canScrollNext: true } );
		expect( state.canPrev ).toBe( false );
		expect( state.canNext ).toBe( true );
	} );
} );
