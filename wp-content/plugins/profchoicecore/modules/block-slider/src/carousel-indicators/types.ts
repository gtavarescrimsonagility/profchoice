import type { StateStyle } from '../shared/state-style';

export type CarouselIndicatorsAttributes = {
	overlay: boolean;
	/** Every indicator; the active one inherits what it leaves empty. */
	default: StateStyle;
	active: StateStyle;
	[ key: string ]: unknown;
};
