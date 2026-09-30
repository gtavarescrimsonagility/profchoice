/**
 * Embla options per transition.
 *
 * Adapted from rtCamp/rt-carousel (GPL-2.0-or-later),
 * src/blocks/carousel/embla-options.ts.
 */
import type { EmblaOptionsType } from 'embla-carousel';
import type { Transition } from './types';

/**
 * Fade requires align: 'center' and containScroll: false to render
 * correctly, and stacking makes dragFree/multi-slide slidesToScroll
 * meaningless. See https://www.embla-carousel.com/docs/plugins/fade
 *
 * @param options    Base Embla options.
 * @param transition Active transition.
 * @return Options with the transition's overrides applied.
 */
export const applyTransitionOverrides = (
	options: EmblaOptionsType,
	transition: Transition
): EmblaOptionsType => {
	if ( transition !== 'fade' ) {
		return options;
	}

	return {
		...options,
		align: 'center',
		containScroll: false,
		dragFree: false,
		slidesToScroll: 1,
	};
};

/**
 * Base options for one slide per view.
 *
 * @param loop          Whether the carousel wraps around.
 * @param reducedMotion Whether the user prefers reduced motion.
 * @return Embla options.
 */
export const baseOptions = (
	loop: boolean,
	reducedMotion: boolean
): EmblaOptionsType => ( {
	loop,
	align: 'start',
	containScroll: 'trimSnaps',
	slidesToScroll: 1,
	// Embla's scroll duration; 20 is its fastest setting.
	duration: reducedMotion ? 20 : 30,
} );
