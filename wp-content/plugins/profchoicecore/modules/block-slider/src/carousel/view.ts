/**
 * Carousel store.
 *
 * Adapted from rtCamp/rt-carousel (GPL-2.0-or-later),
 * src/blocks/carousel/view.ts: lazy Embla init, per-instance lookup through a
 * WeakMap and post-interaction announcements. Added: inert/aria-hidden on
 * inactive slides (bound in render.php), arrow-key/Home/End navigation and
 * prefers-reduced-motion (instant scroll, no autoplay).
 */
import { store, getContext, getElement } from '@wordpress/interactivity';
import EmblaCarousel, { type EmblaCarouselType } from 'embla-carousel';
import Autoplay from 'embla-carousel-autoplay';
import Fade from 'embla-carousel-fade';
import { applyTransitionOverrides, baseOptions } from './embla-options';
import type { CarouselContext } from './types';

const ROOT_SELECTOR = '.pc-carousel';
const VIEWPORT_SELECTOR = '.pc-carousel__viewport';

export const emblaInstances = new WeakMap< HTMLElement, EmblaCarouselType >();

const INTERACTIVE_SELECTOR =
	'a[href], button, input, textarea, select, [contenteditable], [tabindex]:not([tabindex="-1"])';

const getRef = (): HTMLElement | null => {
	const { ref } = getElement();
	return ref instanceof HTMLElement ? ref : null;
};

/**
 * Find the Embla instance of the carousel that contains the element.
 *
 * @param element Any element inside the carousel.
 * @return The instance, or null before initialisation.
 */
export const getEmbla = (
	element: HTMLElement | null
): EmblaCarouselType | null => {
	const viewport = element
		?.closest( ROOT_SELECTOR )
		?.querySelector< HTMLElement >( VIEWPORT_SELECTOR );
	return viewport ? ( emblaInstances.get( viewport ) ?? null ) : null;
};

/**
 * Fill in the announcement pattern.
 *
 * @param pattern Pattern with {{currentSlide}} and {{totalSlides}}.
 * @param current One-based current slide.
 * @param total   Number of slides.
 * @return The announcement text.
 */
export const formatAnnouncement = (
	pattern: string,
	current: number,
	total: number
): string =>
	pattern
		.split( '{{currentSlide}}' )
		.join( String( current ) )
		.split( '{{totalSlides}}' )
		.join( String( total ) );

const prefersReducedMotion = (): boolean =>
	typeof window !== 'undefined' &&
	typeof window.matchMedia === 'function' &&
	window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

/**
 * Run a navigation command on the instance, announcing the result.
 *
 * @param command Receives the Embla instance.
 */
const navigate = ( command: ( embla: EmblaCarouselType ) => void ): void => {
	const embla = getEmbla( getRef() );
	if ( ! embla ) {
		return;
	}
	getContext< CarouselContext >().shouldAnnounce = true;
	command( embla );
};

const { state } = store( 'profchoice/carousel', {
	state: {
		get canPrev(): boolean {
			return getContext< CarouselContext >().canScrollPrev;
		},
		get canNext(): boolean {
			return getContext< CarouselContext >().canScrollNext;
		},
		get isSlideActive(): boolean {
			const context = getContext< CarouselContext >();
			return context.index === context.selectedIndex;
		},
		get isDotActive(): boolean {
			const context = getContext< CarouselContext >();
			return context.snap?.index === context.selectedIndex;
		},
		get dotLabel(): string {
			const { dotLabelPattern, snap } = getContext< CarouselContext >();
			return dotLabelPattern.replace(
				'%d',
				String( ( snap?.index ?? 0 ) + 1 )
			);
		},
	},
	actions: {
		prev(): void {
			navigate( ( embla ) => embla.scrollPrev() );
		},
		next(): void {
			navigate( ( embla ) => embla.scrollNext() );
		},
		goTo(): void {
			const index = getContext< CarouselContext >().snap?.index;
			if ( typeof index === 'number' ) {
				navigate( ( embla ) => embla.scrollTo( index ) );
			}
		},
		// Clicking a slide (not a link, button or field in it) focuses the
		// carousel, so the arrow keys move it right away.
		focusOnPointer( event: PointerEvent ): void {
			const target = event.target as Element | null;
			const root = getRef();
			if (
				! root ||
				typeof target?.closest !== 'function' ||
				target.closest( INTERACTIVE_SELECTOR )
			) {
				return;
			}
			root.focus( { preventScroll: true } );
		},
		onKeydown( event: KeyboardEvent ): void {
			const target = event.target as HTMLElement | null;
			if (
				target?.closest( 'input, textarea, select, [contenteditable]' )
			) {
				return;
			}
			const commands: Record<
				string,
				( embla: EmblaCarouselType ) => void
			> = {
				ArrowLeft: ( embla ) => embla.scrollPrev(),
				ArrowRight: ( embla ) => embla.scrollNext(),
				Home: ( embla ) => embla.scrollTo( 0 ),
				End: ( embla ) =>
					embla.scrollTo( embla.scrollSnapList().length - 1 ),
			};
			const command = commands[ event.key ];
			if ( command ) {
				event.preventDefault();
				navigate( command );
			}
		},
	},
	callbacks: {
		init(): ( () => void ) | void {
			const context = getContext< CarouselContext >();
			const root = getRef();
			const viewport =
				root?.querySelector< HTMLElement >( VIEWPORT_SELECTOR );
			if ( ! viewport || context.slideCount < 2 ) {
				return;
			}

			const start = (): ( () => void ) => {
				const reducedMotion = prefersReducedMotion();
				const plugins = [];
				if ( context.transition === 'fade' ) {
					plugins.push( Fade() );
				}
				if ( context.autoplay && ! reducedMotion ) {
					plugins.push( Autoplay( context.autoplay ) );
				}

				const embla = EmblaCarousel(
					viewport,
					applyTransitionOverrides(
						baseOptions( context.loop, reducedMotion ),
						context.transition
					),
					plugins
				);
				emblaInstances.set( viewport, embla );

				const update = (): void => {
					const previous = context.selectedIndex;
					context.selectedIndex = embla.selectedScrollSnap();
					context.canScrollPrev = embla.canScrollPrev();
					context.canScrollNext = embla.canScrollNext();
					if (
						context.shouldAnnounce &&
						context.selectedIndex !== previous
					) {
						context.announcement = formatAnnouncement(
							context.announcementPattern,
							context.selectedIndex + 1,
							context.slideCount
						);
					}
					context.shouldAnnounce = false;
				};

				embla.on( 'select', update );
				embla.on( 'reInit', update );
				update();

				return () => {
					embla.destroy();
					emblaInstances.delete( viewport );
				};
			};

			// Start lazily: when the carousel is near the viewport and has a width.
			let cleanup: ( () => void ) | undefined;
			let resizeObserver: ResizeObserver | undefined;
			let intersectionObserver: IntersectionObserver | undefined;

			const init = (): void => {
				if ( viewport.getBoundingClientRect().width > 0 ) {
					cleanup = start();
					return;
				}
				resizeObserver = new ResizeObserver( ( entries ) => {
					if (
						entries.some( ( entry ) => entry.contentRect.width > 0 )
					) {
						resizeObserver?.disconnect();
						cleanup = start();
					}
				} );
				resizeObserver.observe( viewport );
			};

			if ( 'IntersectionObserver' in window ) {
				intersectionObserver = new IntersectionObserver(
					( entries ) => {
						if ( entries[ 0 ]?.isIntersecting ) {
							intersectionObserver?.disconnect();
							init();
						}
					},
					{ rootMargin: '200px' }
				);
				intersectionObserver.observe( viewport );
			} else {
				init();
			}

			return () => {
				intersectionObserver?.disconnect();
				resizeObserver?.disconnect();
				cleanup?.();
			};
		},
	},
} );

export { state };
