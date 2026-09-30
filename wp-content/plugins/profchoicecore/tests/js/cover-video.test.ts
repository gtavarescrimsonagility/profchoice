import { beforeEach, describe, expect, it, vi, type Mock } from 'vitest';
import { getContext, getElement } from '@wordpress/interactivity';
import {
	actions,
	callbacks,
	findVideo,
	state,
} from '../../src/cover-video/view';
import type { CoverVideoContext } from '../../src/cover-video/types';

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

const withVideo = ( video: ReturnType< typeof fakeVideo > | null ) => {
	const ref = {
		closest: () => ( { querySelector: () => video } ),
	};
	( getElement as Mock ).mockReturnValue( { ref } );
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
		withVideo( video );
		actions.toggle();
		expect( video.play ).toHaveBeenCalled();
		expect( context.isPlaying ).toBe( true );

		video.paused = false;
		actions.toggle();
		expect( video.pause ).toHaveBeenCalled();
		expect( context.isPlaying ).toBe( false );
	} );

	it( 'clears isPlaying when the video pauses or ends by itself', () => {
		const video = fakeVideo( false );
		withVideo( video );
		const cleanup = callbacks.init();
		context.isPlaying = true;
		video.listeners.ended();
		expect( context.isPlaying ).toBe( false );
		cleanup();
		expect( video.removeEventListener ).toHaveBeenCalledTimes( 2 );
	} );

	it( 'finds no video outside a cover video', () => {
		expect( findVideo( null ) ).toBeNull();
	} );
} );
