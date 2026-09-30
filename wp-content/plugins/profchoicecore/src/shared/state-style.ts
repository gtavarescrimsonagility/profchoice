/**
 * Per-state styles (e.g. the default and active dot) stored as block
 * attributes and applied through CSS custom properties. Mirrored in
 * includes/StateStyle.php.
 */
export type StateBorder = {
	color?: string;
	style?: string;
	width?: string;
};

export const SIDES = [ 'top', 'right', 'bottom', 'left' ] as const;
export type Side = ( typeof SIDES )[ number ];

/** One border for every side, or one per side (unlinked). */
export type StateBorders = StateBorder | Partial< Record< Side, StateBorder > >;

export const CORNERS = {
	topLeft: 'top-left',
	topRight: 'top-right',
	bottomRight: 'bottom-right',
	bottomLeft: 'bottom-left',
} as const;
export type Corner = keyof typeof CORNERS;

/** One radius for every corner, or one per corner (unlinked). */
export type StateRadius = string | Partial< Record< Corner, string > >;

export type StateStyle = {
	background?: string;
	color?: string;
	border?: StateBorders;
	radius?: StateRadius;
	width?: string;
	height?: string;
	opacity?: string;
};

export type StateFeature =
	'background' | 'color' | 'border' | 'radius' | 'size' | 'opacity';

export const isSplitBorder = (
	border?: StateBorders
): border is Partial< Record< Side, StateBorder > > =>
	!! border && SIDES.some( ( side ) => side in border );

const flatten = ( style: StateStyle ): Record< string, string | undefined > => {
	const flat: Record< string, string | undefined > = {
		background: style.background,
		color: style.color,
		width: style.width,
		height: style.height,
		opacity: style.opacity,
	};

	const { border, radius } = style;
	if ( isSplitBorder( border ) ) {
		for ( const side of SIDES ) {
			flat[ `border-${ side }-color` ] = border[ side ]?.color;
			flat[ `border-${ side }-style` ] = border[ side ]?.style;
			flat[ `border-${ side }-width` ] = border[ side ]?.width;
		}
	} else {
		flat[ 'border-color' ] = border?.color;
		flat[ 'border-style' ] = border?.style;
		flat[ 'border-width' ] = border?.width;
	}

	if ( radius && typeof radius === 'object' ) {
		for ( const [ corner, css ] of Object.entries( CORNERS ) ) {
			flat[ `radius-${ css }` ] = radius[ corner as Corner ];
		}
	} else {
		flat.radius = radius;
	}
	return flat;
};

/**
 * Turns a preset reference saved by the editor controls
 * (`var:preset|dimension|12`) into its CSS custom property.
 *
 * @param css CSS value or preset reference.
 * @return CSS value.
 */
export const resolvePreset = ( css: string ): string => {
	const match = /^var:preset\|([a-z0-9-]+)\|([a-z0-9-]+)$/i.exec( css );
	return match ? `var(--wp--preset--${ match[ 1 ] }--${ match[ 2 ] })` : css;
};

/**
 * CSS custom properties and modifier classes for a set of states.
 *
 * Each set value becomes `--pc-{prefix}-{state}-{property}` plus a
 * `has-{state}-{property}` class, so stylesheets can apply a property only
 * when it is set (and otherwise leave the inner block's own style alone).
 * Unlinked borders and radii use per-side/per-corner properties, e.g.
 * `border-top-width` and `radius-top-left`.
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
				style[ `--pc-${ prefix }-${ state }-${ property }` ] =
					resolvePreset( css );
				classes.push( `has-${ state }-${ property }` );
			}
		}
	}
	return { style, className: classes.join( ' ' ) };
};
