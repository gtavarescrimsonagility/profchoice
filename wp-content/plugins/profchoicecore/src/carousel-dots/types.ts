import type { StateStyle } from '../shared/state-style';

export type CarouselDotsAttributes = {
	overlay: boolean;
	/** Every dot; the active dot inherits what it leaves empty. */
	default: StateStyle;
	active: StateStyle;
	[ key: string ]: unknown;
};
