import type { StateStyle } from '../shared/state-style';

export type CarouselControlsAttributes = {
	overlay: boolean;
	active: StateStyle;
	inactive: StateStyle;
	[ key: string ]: unknown;
};
