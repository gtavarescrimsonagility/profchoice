/**
 * Stub for `@wordpress/interactivity`: store() returns its config so tests can
 * call state getters and actions directly.
 *
 * Adapted from rtCamp/rt-carousel (GPL-2.0-or-later).
 */
import { vi } from 'vitest';

export const store = vi.fn( ( _namespace: string, config: unknown ) => config );
export const getContext = vi.fn( () => ( {} ) );
export const getElement = vi.fn( () => ( { ref: null } ) );
