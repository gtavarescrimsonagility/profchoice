/**
 * Cover video store: the Play button plays or pauses the (muted) background
 * video; `isPlaying` drives the wrapper's `is-playing` class, which fades the
 * button out.
 */
import {
	store,
	getContext,
	getElement,
	withScope,
} from '@wordpress/interactivity';
import type { CoverVideoContext } from './types';

/**
 * The block's video element, looked up from any element inside the block.
 *
 * @param element Element inside the cover video block.
 * @return The video element, if any.
 */
export const findVideo = (
	element: Element | null | undefined
): HTMLVideoElement | null =>
	element
		?.closest( '.pc-cover-video' )
		?.querySelector< HTMLVideoElement >( '.pc-cover-video__video' ) ?? null;

export const { state, actions, callbacks } = store( 'profchoice/cover-video', {
	state: {
		get playLabel(): string {
			const context = getContext< CoverVideoContext >();
			return context.isPlaying ? context.pauseLabel : context.playLabel;
		},
	},
	actions: {
		toggle(): void {
			const video = findVideo( getElement().ref );
			if ( ! video ) {
				return;
			}
			const context = getContext< CoverVideoContext >();

			if ( video.paused ) {
				context.isPlaying = true;
				video.play()?.catch( () => {
					context.isPlaying = false;
				} );
			} else {
				video.pause();
				context.isPlaying = false;
			}
		},
	},
	callbacks: {
		// Keep isPlaying in sync when the video stops on its own.
		init(): () => void {
			const context = getContext< CoverVideoContext >();
			const video = findVideo( getElement().ref );
			const stop = withScope( () => {
				context.isPlaying = false;
			} );
			video?.addEventListener( 'pause', stop );
			video?.addEventListener( 'ended', stop );
			return () => {
				video?.removeEventListener( 'pause', stop );
				video?.removeEventListener( 'ended', stop );
			};
		},
	},
} );
