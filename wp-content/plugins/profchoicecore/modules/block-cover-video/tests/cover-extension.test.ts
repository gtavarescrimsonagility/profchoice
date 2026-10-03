import { beforeEach, describe, expect, it, vi, type Mock } from 'vitest';
import { getContext, getElement } from '@wordpress/interactivity';
import {
	actions,
	callbacks,
	findVideo,
	loadVideo,
	state,
} from '../src/cover-extension/view';
import type { CoverVideoContext } from '../src/cover-extension/view';

const fakeVideo = ( paused: boolean ) => {
	const attributes: Record< string, string > = {};
	return {
		paused,
		attributes,
		className: '',
		src: '',
		poster: '',
		muted: false,
		loop: false,
		playsInline: false,
		setAttribute: vi.fn( ( name: string, value: string ) => {
			attributes[ name ] = value;
		} ),
		play: vi.fn( () => Promise.resolve() ),
		pause: vi.fn(),
		addEventListener: vi.fn(),
	};
};

type FakeVideo = ReturnType< typeof fakeVideo >;

const fakePoster = ( attributes: Record< string, string > ) => ( {
	currentSrc: 'poster-1024.webp',
	src: 'poster.webp',
	hasAttribute: ( name: string ) => name in attributes,
	getAttribute: ( name: string ) => attributes[ name ] ?? null,
	after: vi.fn(),
	remove: vi.fn(),
} );

let created: FakeVideo;
const documentListeners: Record< string, ( event: unknown ) => void > = {};
vi.stubGlobal( 'document', {
	addEventListener: vi.fn(
		( type: string, fn: ( event: unknown ) => void ) => {
			documentListeners[ type ] = fn;
		}
	),
	removeEventListener: vi.fn(),
	createElement: vi.fn( () => {
		created = fakeVideo( true );
		return created;
	} ),
} );
vi.stubGlobal( 'window', { matchMedia: () => ( { matches: false } ) } );

/**
 * A cover (also the element the store gets from getElement) holding a video
 * or a lazy poster; a video created from the poster takes its place.
 *
 * @param video  The cover's video.
 * @param poster The cover's lazy poster image.
 */
const withCover = (
	video: FakeVideo | null,
	poster: ReturnType< typeof fakePoster > | null = null
) => {
	const dom = { video, poster };
	const listeners: Record< string, () => void > = {};
	const cover = {
		closest: () => cover,
		contains: ( node: unknown ) => node === 'inside',
		querySelector: ( selector: string ) =>
			selector.startsWith( 'video' ) ? dom.video : dom.poster,
		focus: vi.fn(),
		addEventListener: vi.fn( ( type: string, fn: () => void ) => {
			listeners[ type ] = fn;
		} ),
		removeEventListener: vi.fn(),
		listeners,
	};
	poster?.after.mockImplementation( ( element: FakeVideo ) => {
		dom.video = element;
	} );
	( getElement as Mock ).mockReturnValue( { ref: cover } );
	return cover;
};

describe( 'cover video store', () => {
	let context: CoverVideoContext;

	beforeEach( () => {
		context = {
			isPlaying: false,
			isLoading: false,
			playLabel: 'Play video',
			pauseLabel: 'Pause video',
			loadingLabel: 'Loading video',
		};
		( getContext as Mock ).mockReturnValue( context );
	} );

	it( 'labels the button by state', () => {
		expect( state.playLabel ).toBe( 'Play video' );
		context.isPlaying = true;
		expect( state.playLabel ).toBe( 'Pause video' );
		context.isLoading = true;
		expect( state.playLabel ).toBe( 'Loading video' );
	} );

	it( 'creates a lazy video from its poster on Play', async () => {
		const poster = fakePoster( {
			'data-video': 'video.mp4',
			'data-muted': '',
			style: 'object-position:50% 84%',
			'data-object-fit': 'cover',
		} );
		withCover( null, poster );
		actions.toggle();
		expect( created.src ).toBe( 'video.mp4' );
		expect( created.poster ).toBe( 'poster-1024.webp' );
		expect( created.muted ).toBe( true );
		expect( created.loop ).toBe( false );
		expect( created.playsInline ).toBe( true );
		expect( created.attributes ).toEqual( {
			style: 'object-position:50% 84%',
			'data-object-fit': 'cover',
		} );
		expect( poster.after ).toHaveBeenCalledWith( created );
		expect( created.play ).toHaveBeenCalled();
		expect( context.isLoading ).toBe( true );
		expect( context.isPlaying ).toBe( true );
		await Promise.resolve();
		expect( context.isLoading ).toBe( false );

		// The next click uses the video created on the first one.
		created.paused = false;
		actions.toggle();
		expect( created.pause ).toHaveBeenCalled();
		expect( document.createElement ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'removes the poster once the video plays', () => {
		const poster = fakePoster( { 'data-video': 'video.mp4' } );
		const cover = withCover( null, poster );
		loadVideo( cover as unknown as Element );
		const [ type, listener, options ] =
			created.addEventListener.mock.calls[ 0 ];
		expect( [ type, options ] ).toEqual( [ 'playing', { once: true } ] );
		listener();
		expect( poster.remove ).toHaveBeenCalled();
	} );

	it( 'shows the Play button again when a lazy video fails to play', async () => {
		const poster = fakePoster( { 'data-video': 'video.mp4' } );
		withCover( null, poster );
		( document.createElement as Mock ).mockImplementationOnce( () => {
			created = fakeVideo( true );
			created.play.mockReturnValueOnce( Promise.reject( new Error() ) );
			return created;
		} );
		actions.toggle();
		await Promise.resolve();
		await Promise.resolve();
		expect( context.isLoading ).toBe( false );
		expect( context.isPlaying ).toBe( false );
	} );

	it( 'plays a paused video and pauses a playing one', () => {
		const video = fakeVideo( true );
		const cover = withCover( video );
		actions.toggle();
		expect( video.play ).toHaveBeenCalled();
		expect( context.isPlaying ).toBe( true );
		expect( context.isLoading ).toBe( false );
		expect( cover.focus ).toHaveBeenCalledWith( { preventScroll: true } );

		video.paused = false;
		actions.toggle();
		expect( video.pause ).toHaveBeenCalled();
		expect( context.isPlaying ).toBe( false );
	} );

	it( 'follows the video when it plays, pauses or ends by itself', () => {
		const cover = withCover( fakeVideo( false ) );
		const cleanup = callbacks.init();
		expect( cover.addEventListener ).toHaveBeenCalledWith(
			'play',
			expect.any( Function ),
			true
		);
		cover.listeners.play();
		expect( context.isPlaying ).toBe( true );
		cover.listeners.ended();
		expect( context.isPlaying ).toBe( false );
		context.isLoading = true;
		cover.listeners.playing();
		expect( context.isLoading ).toBe( false );
		context.isLoading = true;
		cover.listeners.error();
		expect( context.isLoading ).toBe( false );
		expect( context.isPlaying ).toBe( false );
		cleanup();
		expect( cover.removeEventListener ).toHaveBeenCalledTimes( 5 );
	} );

	it( 'finds no video outside a video cover', () => {
		expect( findVideo( null ) ).toBeNull();
		expect( loadVideo( null ) ).toBeNull();
	} );

	it( 'toggles with Space after the cover is clicked', () => {
		const video = fakeVideo( false );
		withCover( video );
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
