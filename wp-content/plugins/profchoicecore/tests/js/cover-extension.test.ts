import { beforeEach, describe, expect, it, vi, type Mock } from 'vitest';
import { getContext, getElement } from '@wordpress/interactivity';
import {
	actions,
	callbacks,
	findVideo,
	state,
} from '../../src/cover-extension/view';
import type { CoverVideoContext } from '../../src/cover-extension/view';

const fakeVideo = ( paused: boolean ) => {
	const listeners: Record< string, () => void > = {};
	return {
		paused,
		play: vi.fn( () => Promise.resolve() ),
		pause: vi.fn(),
		addEventListener: vi.fn( ( type: string, fn: () => void ) => {
			listeners[ type ] = fn;
		} ),
		removeEventListener: vi.fn(),
		listeners,
	};
};

const documentListeners: Record< string, ( event: unknown ) => void > = {};
vi.stubGlobal( 'document', {
	addEventListener: vi.fn(
		( type: string, fn: ( event: unknown ) => void ) => {
			documentListeners[ type ] = fn;
		}
	),
	removeEventListener: vi.fn(),
} );
vi.stubGlobal( 'window', { matchMedia: () => ( { matches: false } ) } );

const withVideo = ( video: ReturnType< typeof fakeVideo > | null ) => {
	const cover = { querySelector: () => video, focus: vi.fn() };
	const ref = {
		closest: () => cover,
		contains: ( node: unknown ) => node === 'inside',
	};
	( getElement as Mock ).mockReturnValue( { ref } );
	return { ref, cover };
};

describe( 'cover video store', () => {
	let context: CoverVideoContext;

	beforeEach( () => {
		context = {
			isPlaying: false,
			playLabel: 'Play video',
			pauseLabel: 'Pause video',
		};
		( getContext as Mock ).mockReturnValue( context );
	} );

	it( 'labels the button by state', () => {
		expect( state.playLabel ).toBe( 'Play video' );
		context.isPlaying = true;
		expect( state.playLabel ).toBe( 'Pause video' );
	} );

	it( 'plays a paused video and pauses a playing one', () => {
		const video = fakeVideo( true );
		const { cover } = withVideo( video );
		actions.toggle();
		expect( video.play ).toHaveBeenCalled();
		expect( context.isPlaying ).toBe( true );
		expect( cover.focus ).toHaveBeenCalledWith( { preventScroll: true } );

		video.paused = false;
		actions.toggle();
		expect( video.pause ).toHaveBeenCalled();
		expect( context.isPlaying ).toBe( false );
	} );

	it( 'follows the video when it plays, pauses or ends by itself', () => {
		const video = fakeVideo( false );
		withVideo( video );
		const cleanup = callbacks.init();
		video.listeners.play();
		expect( context.isPlaying ).toBe( true );
		video.listeners.ended();
		expect( context.isPlaying ).toBe( false );
		cleanup();
		expect( video.removeEventListener ).toHaveBeenCalledTimes( 3 );
	} );

	it( 'finds no video outside a video cover', () => {
		expect( findVideo( null ) ).toBeNull();
	} );

	it( 'toggles with Space after the cover is clicked', () => {
		const video = fakeVideo( false );
		withVideo( video );
		callbacks.init();
		const space = {
			key: ' ',
			target: null,
			preventDefault: vi.fn(),
		};

		documentListeners.keydown( space );
		expect( video.pause ).not.toHaveBeenCalled();

		documentListeners.pointerdown( { target: 'inside' } );
		documentListeners.keydown( space );
		expect( space.preventDefault ).toHaveBeenCalled();
		expect( video.pause ).toHaveBeenCalled();

		documentListeners.pointerdown( { target: 'outside' } );
		video.paused = true;
		documentListeners.keydown( space );
		expect( video.play ).not.toHaveBeenCalled();
	} );
} );
