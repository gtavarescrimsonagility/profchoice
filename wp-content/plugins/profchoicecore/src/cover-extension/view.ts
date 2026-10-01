/**
 * Cover video store (core/cover with a video background, see
 * includes/CoverVideo.php): a "Play video" button plays or pauses the video;
 * `isPlaying` drives the cover's `is-playing` class, which fades the button
 * out.
 */
import {
	store,
	getContext,
	getElement,
	withScope,
} from '@wordpress/interactivity';

export type CoverVideoContext = {
	isPlaying: boolean;
	playLabel: string;
	pauseLabel: string;
};

/**
 * The cover's background video, looked up from any element inside the cover.
 *
 * @param element Element inside the cover.
 * @return The video element, if any.
 */
export const findVideo = (
	element: Element | null | undefined
): HTMLVideoElement | null =>
	element
		?.closest( '[data-wp-interactive="profchoice/cover-video"]' )
		?.querySelector< HTMLVideoElement >(
			'video.wp-block-cover__video-background'
		) ?? null;

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
		video.play()?.catch( () => {
			context.isPlaying = false;
		} );
	} else {
		video.pause();
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
			return context.isPlaying ? context.pauseLabel : context.playLabel;
		},
	},
	actions: {
		toggle(): void {
			const { ref } = getElement();
			const video = findVideo( ref );
			if ( ! video ) {
				return;
			}
			const context = getContext< CoverVideoContext >();
			if ( video.paused ) {
				// Move focus from the (fading) button to the cover, which
				// becomes the active one: the next Space pauses instead of
				// scrolling. Tabbing back to the button shows it again.
				const cover = ( ref as Element | null )?.closest< HTMLElement >(
					'[data-wp-interactive="profchoice/cover-video"]'
				);
				cover?.focus?.( { preventScroll: true } );
				activeCover = cover ?? activeCover;
			}
			togglePlayback( video, context );
		},
	},
	callbacks: {
		// Keep isPlaying in sync with the video (autoplay, pause, end), and
		// honour reduced motion by stopping an autoplaying video.
		init(): () => void {
			const context = getContext< CoverVideoContext >();
			const { ref: cover } = getElement();
			const video = findVideo( cover );
			const play = withScope( () => {
				context.isPlaying = true;
			} );
			const stop = withScope( () => {
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
					! video ||
					isInteractive( event.target )
				) {
					return;
				}
				event.preventDefault();
				togglePlayback( video, context );
			} );

			video?.addEventListener( 'play', play );
			video?.addEventListener( 'pause', stop );
			video?.addEventListener( 'ended', stop );
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
				video?.removeEventListener( 'play', play );
				video?.removeEventListener( 'pause', stop );
				video?.removeEventListener( 'ended', stop );
				document.removeEventListener( 'pointerdown', activate );
				document.removeEventListener( 'keydown', onKeydown );
			};
		},
	},
} );
