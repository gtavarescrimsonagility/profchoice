import type { StateStyle } from '../state-style';

export type Direction = 'prev' | 'next';

export type CarouselControlAttributes = {
	overlay: boolean;
	active: StateStyle;
	inactive: StateStyle;
	[ key: string ]: unknown;
};
