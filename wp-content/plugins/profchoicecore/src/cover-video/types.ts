export type FocalPoint = { x: number; y: number };

export type CoverVideoAttributes = {
	videoId?: number;
	videoUrl?: string;
	posterId?: number;
	posterUrl?: string;
	focalPoint?: FocalPoint;
	dimRatio: number;
	overlayColor?: string;
	loop: boolean;
	[ key: string ]: unknown;
};

export type CoverVideoContext = {
	isPlaying: boolean;
	playLabel: string;
	pauseLabel: string;
};
