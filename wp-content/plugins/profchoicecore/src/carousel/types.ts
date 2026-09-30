export type Transition = 'fade' | 'slide';

export type CarouselAttributes = {
	ariaLabel: string;
	transition: Transition;
	loop: boolean;
	autoplay: boolean;
	autoplayDelay: number;
	showArrows: boolean;
	showDots: boolean;
	[ key: string ]: unknown;
};

export type AutoplayContext = {
	delay: number;
	stopOnInteraction: boolean;
	stopOnMouseEnter: boolean;
	stopOnFocusIn: boolean;
};

/**
 * Per-instance context serialized by render.php.
 */
export type CarouselContext = {
	transition: Transition;
	loop: boolean;
	autoplay: AutoplayContext | false;
	selectedIndex: number;
	slideCount: number;
	canScrollPrev: boolean;
	canScrollNext: boolean;
	announcement: string;
	shouldAnnounce: boolean;
	announcementPattern: string;
	/** Set on each dot by render.php. */
	snap?: number;
	/** Set on each slide by render.php. */
	index?: number;
};
