/**
 * Embla stubs: the carousel view module imports Embla at load time.
 *
 * Adapted from rtCamp/rt-carousel (GPL-2.0-or-later).
 */
import { vi } from 'vitest';

vi.mock( 'embla-carousel', () => ( {
	default: vi.fn( () => ( {
		canScrollPrev: vi.fn( () => true ),
		canScrollNext: vi.fn( () => true ),
		scrollPrev: vi.fn(),
		scrollNext: vi.fn(),
		scrollTo: vi.fn(),
		selectedScrollSnap: vi.fn( () => 0 ),
		scrollSnapList: vi.fn( () => [ 0, 1, 2 ] ),
		on: vi.fn(),
		destroy: vi.fn(),
	} ) ),
} ) );

vi.mock( 'embla-carousel-autoplay', () => ( {
	default: vi.fn( () => ( { name: 'autoplay' } ) ),
} ) );

vi.mock( 'embla-carousel-fade', () => ( {
	default: vi.fn( () => ( { name: 'fade' } ) ),
} ) );
