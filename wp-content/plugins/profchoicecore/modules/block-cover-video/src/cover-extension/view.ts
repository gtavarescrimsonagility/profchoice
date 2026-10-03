/**
 * Cover video store (core/cover with a video background, see
 * includes/CoverVideo.php): a "Play video" button plays or pauses the video;
 * `isPlaying` drives the cover's `is-playing` class, which fades the button
 * out. A lazy-loaded video comes as its poster image only (`data-video` holds
 * the URL); Play creates the video, and `isLoading` (the `is-loading` class)
 * turns the button into a spinner until it plays.
 */
import {
	store,
	getContext,
	getElement,
	withScope,
} from '@wordpress/interactivity';

export type CoverVideoContext = {
	isPlaying: boolean;
	isLoading: boolean;
	playLabel: string;
	pauseLabel: string;
	loadingLabel: string;
};

/**
 * The cover's background video, looked up from any element inside the cover.
 *
 * @param element Element inside the cover.
 * @return The video element, if any.
 */
const COVER = '[data-wp-interactive="profchoice/cover-video"]';

export const findVideo = (
	element: Element | null | undefined
): HTMLVideoElement | null =>
	element
		?.closest( COVER )
		?.querySelector< HTMLVideoElement >(
			'video.wp-block-cover__video-background'
		) ?? null;

/**
 * Creates a lazy-loaded cover's video from its poster image (see
 * CoverVideo::poster_image()). The video goes over the image, which is
 * removed once the video plays.
 *
 * @param element Element inside the cover.
 * @return The new video, or null when the cover has no lazy poster.
 */
export const loadVideo = (
	element: Element | null | undefined
): HTMLVideoElement | null => {
	const poster = element
		?.closest( COVER )
		?.querySelector< HTMLImageElement >(
			'img.pc-cover-video__poster[data-video]'
		);
	if ( ! poster ) {
		return null;
	}
	const video = document.createElement( 'video' );
	video.className = 'wp-block-cover__video-background intrinsic-ignore';
	video.playsInline = true;
	video.muted = poster.hasAttribute( 'data-muted' );
	video.loop = poster.hasAttribute( 'data-loop' );
	video.poster = poster.currentSrc || poster.src;
	for ( const name of [
		'style',
		'data-object-fit',
		'data-object-position',
	] ) {
		const value = poster.getAttribute( name );
		if ( value !== null ) {
			video.setAttribute( name, value );
		}
	}
	video.addEventListener( 'playing', () => poster.remove(), {
		once: true,
	} );
	video.src = poster.getAttribute( 'data-video' ) ?? '';
	poster.after( video );
	return video;
};

/**
 * The cover's video, created from a lazy poster when needed; a new video
 * puts the cover in the loading state.
 *
 * @param element Element inside the cover.
 * @param context The cover's context.
 * @return The video, if any.
 */
const resolveVideo = (
	element: Element | null | undefined,
	context: CoverVideoContext
): HTMLVideoElement | null => {
	const video = findVideo( element );
	if ( video ) {
		return video;
	}
	const created = loadVideo( element );
	if ( created ) {
		context.isLoading = true;
	}
	return created;
};

/**
 * Plays a paused video or pauses a playing one, updating the context.
 *
 * @param video   The cover's video.
 * @param context The cover's context.
 */
const togglePlayback = (
	video: HTMLVideoElement,
	context: CoverVideoContext
): void => {
	if ( video.paused ) {
		context.isPlaying = true;
		video.play()?.then(
			() => {
				context.isLoading = false;
			},
			() => {
				context.isLoading = false;
				context.isPlaying = false;
			}
		);
	} else {
		video.pause();
		context.isLoading = false;
		context.isPlaying = false;
	}
};

/**
 * Whether a key event comes from an element that handles Space itself.
 *
 * @param target Event target.
 * @return True for form fields, buttons, links and editable content.
 */
export const isInteractive = ( target: EventTarget | null ): boolean =>
	typeof ( target as Element | null )?.closest === 'function' &&
	!! ( target as Element ).closest(
		'input, textarea, select, button, a[href], [contenteditable], [role="button"]'
	);

/** The last cover clicked: Space plays/pauses its video. */
let activeCover: Element | null = null;

export const { state, actions, callbacks } = store( 'profchoice/cover-video', {
	state: {
		get playLabel(): string {
			const context = getContext< CoverVideoContext >();
			if ( context.isLoading ) {
				return context.loadingLabel;
			}
			return context.isPlaying ? context.pauseLabel : context.playLabel;
		},
	},
	actions: {
		toggle(): void {
			const { ref } = getElement();
			const context = getContext< CoverVideoContext >();
			const video = resolveVideo( ref, context );
			if ( ! video ) {
				return;
			}
			if ( video.paused ) {
				// Move focus from the (fading) button to the cover, which
				// becomes the active one: the next Space pauses instead of
				// scrolling. Tabbing back to the button shows it again.
				const cover = ( ref as Element | null )?.closest< HTMLElement >(
					COVER
				);
				cover?.focus?.( { preventScroll: true } );
				activeCover = cover ?? activeCover;
			}
			togglePlayback( video, context );
		},
	},
	callbacks: {
		// Keep isPlaying and isLoading in sync with the video (autoplay,
		// playing, pause, end, error), and honour reduced motion by stopping
		// an autoplaying video. Media events don't bubble, so the cover
		// listens in the capture phase: that also covers a video created
		// later from a lazy poster.
		init(): () => void {
			const context = getContext< CoverVideoContext >();
			const { ref: cover } = getElement();
			const video = findVideo( cover );
			const play = withScope( () => {
				context.isPlaying = true;
			} );
			const playing = withScope( () => {
				context.isLoading = false;
				context.isPlaying = true;
			} );
			const stop = withScope( () => {
				context.isLoading = false;
				context.isPlaying = false;
			} );
			// Clicking the cover makes it the active one; clicking elsewhere
			// releases it.
			const activate = ( event: Event ) => {
				if ( cover?.contains( event.target as Node ) ) {
					activeCover = cover;
				} else if ( activeCover === cover ) {
					activeCover = null;
				}
			};
			const onKeydown = withScope( ( event: KeyboardEvent ) => {
				if (
					event.key !== ' ' ||
					activeCover !== cover ||
					isInteractive( event.target )
				) {
					return;
				}
				const current = resolveVideo( cover, context );
				if ( ! current ) {
					return;
				}
				event.preventDefault();
				togglePlayback( current, context );
			} );

			const listeners: Array< [ string, () => void ] > = [
				[ 'play', play ],
				[ 'playing', playing ],
				[ 'pause', stop ],
				[ 'ended', stop ],
				[ 'error', stop ],
			];
			for ( const [ type, listener ] of listeners ) {
				cover?.addEventListener( type, listener, true );
			}
			document.addEventListener( 'pointerdown', activate );
			document.addEventListener( 'keydown', onKeydown );
			if (
				video?.autoplay &&
				window.matchMedia?.( '(prefers-reduced-motion: reduce)' )
					.matches
			) {
				video.pause();
				context.isPlaying = false;
			}
			return () => {
				for ( const [ type, listener ] of listeners ) {
					cover?.removeEventListener( type, listener, true );
				}
				document.removeEventListener( 'pointerdown', activate );
				document.removeEventListener( 'keydown', onKeydown );
			};
		},
	},
} );
