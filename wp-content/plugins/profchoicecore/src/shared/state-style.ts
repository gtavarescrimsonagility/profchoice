/**
 * Per-state styles (e.g. the active and inactive dot) stored as block
 * attributes and applied through CSS custom properties. Mirrored in
 * includes/StateStyle.php.
 */
export type StateBorder = {
	color?: string;
	style?: string;
	width?: string;
};

export type StateStyle = {
	background?: string;
	color?: string;
	border?: StateBorder;
	radius?: string;
	width?: string;
	height?: string;
	opacity?: string;
};

export type StateFeature =
	'background' | 'color' | 'border' | 'radius' | 'size' | 'opacity';

const flatten = (
	style: StateStyle
): Record< string, string | undefined > => ( {
	background: style.background,
	color: style.color,
	'border-color': style.border?.color,
	'border-style': style.border?.style,
	'border-width': style.border?.width,
	radius: style.radius,
	width: style.width,
	height: style.height,
	opacity: style.opacity,
} );

/**
 * CSS custom properties and modifier classes for a set of states.
 *
 * Each set value becomes `--pc-{prefix}-{state}-{property}` plus a
 * `has-{state}-{property}` class, so stylesheets can apply a property only
 * when it is set (and otherwise leave the inner block's own style alone).
 *
 * @param prefix Variable prefix, e.g. `carousel-dot`.
 * @param states Styles keyed by state name.
 * @return Inline style variables and class names.
 */
export const stateStyleProps = (
	prefix: string,
	states: Record< string, StateStyle | undefined >
): { style: Record< string, string >; className: string } => {
	const style: Record< string, string > = {};
	const classes: string[] = [];
	for ( const [ state, value ] of Object.entries( states ) ) {
		for ( const [ property, css ] of Object.entries(
			flatten( value ?? {} )
		) ) {
			if ( css !== undefined && css !== '' ) {
				style[ `--pc-${ prefix }-${ state }-${ property }` ] = css;
				classes.push( `has-${ state }-${ property }` );
			}
		}
	}
	return { style, className: classes.join( ' ' ) };
};
