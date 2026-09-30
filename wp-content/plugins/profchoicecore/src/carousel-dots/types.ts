export type DotStyle = {
	color: string;
	borderColor: string;
	borderWidth: string;
	width: string;
	height: string;
	radius: string;
};

export type CarouselDotsAttributes = {
	overlay: boolean;
	inactive: DotStyle;
	active: DotStyle;
	[ key: string ]: unknown;
};
