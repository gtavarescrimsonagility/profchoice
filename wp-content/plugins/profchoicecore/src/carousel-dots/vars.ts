import type { CarouselDotsAttributes } from './types';

/**
 * CSS custom properties for the dots, mirrored in render.php.
 *
 * @param attributes Inactive and active dot styles.
 * @return Inline style with the --pc-carousel-dot-* variables.
 */
export const dotVars = (
	attributes: Pick< CarouselDotsAttributes, 'inactive' | 'active' >
): Record< string, string > => {
	const { inactive, active } = attributes;
	return {
		'--pc-carousel-dot-color': inactive.color,
		'--pc-carousel-dot-border': `${ inactive.borderWidth } solid ${ inactive.borderColor }`,
		'--pc-carousel-dot-width': inactive.width,
		'--pc-carousel-dot-height': inactive.height,
		'--pc-carousel-dot-radius': inactive.radius,
		'--pc-carousel-dot-active-color': active.color,
		'--pc-carousel-dot-active-border': `${ active.borderWidth } solid ${ active.borderColor }`,
		'--pc-carousel-dot-active-width': active.width,
		'--pc-carousel-dot-active-height': active.height,
		'--pc-carousel-dot-active-radius': active.radius,
	};
};
