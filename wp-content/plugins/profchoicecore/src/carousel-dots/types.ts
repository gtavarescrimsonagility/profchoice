import type { StateStyle } from '../shared/state-style';

export type CarouselDotsAttributes = {
	overlay: boolean;
	inactive: StateStyle;
	active: StateStyle;
	[ key: string ]: unknown;
};
