export type Transition = 'fade' | 'slide';

export type CarouselAttributes = {
	ariaLabel: string;
	transition: Transition;
	loop: boolean;
	autoplay: boolean;
	autoplayDelay: number;
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
	dotLabelPattern: string;
	/** One entry per slide, iterated by the dots block. */
	snaps: Array< { index: number } >;
	/** The dot's item inside data-wp-each. */
	snap?: { index: number };
	/** Set on each slide by render.php. */
	index?: number;
};
