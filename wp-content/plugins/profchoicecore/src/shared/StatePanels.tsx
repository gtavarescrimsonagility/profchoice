import { __ } from '@wordpress/i18n';
import * as blockEditor from '@wordpress/block-editor';
import { InspectorControls, useSettings } from '@wordpress/block-editor';
import type { ComponentType } from '@wordpress/element';
import {
	BorderBoxControl,
	Button,
	ColorIndicator,
	ColorPalette,
	Dropdown,
	RangeControl,
	TabPanel,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis -- the panel core's block supports use; no stable export yet.
	__experimentalToolsPanel as ToolsPanel,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis -- see ToolsPanel.
	__experimentalToolsPanelItem as ToolsPanelItem,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis -- the padding wrapper used by core's color dropdowns.
	__experimentalDropdownContentWrapper as DropdownContentWrapper,
} from '@wordpress/components';
import {
	SIDES,
	type Side,
	type StateBorder,
	type StateBorders,
	type StateFeature,
	type StateRadius,
	type StateStyle,
} from './state-style';

/*
 * Core's radius control (linked/unlinked corners, presets and slider), as in
 * the block Border panel. Not typed by @wordpress/block-editor.
 */
const BorderRadiusControl = (
	blockEditor as unknown as {
		__experimentalBorderRadiusControl: ComponentType< {
			values?: StateRadius;
			onChange: ( value?: StateRadius ) => void;
		} >;
	}
 ).__experimentalBorderRadiusControl;

/*
 * Core's width/height control (preset slider with a custom value toggle), as
 * in the Button block's Width. Not typed by @wordpress/block-editor.
 */
const DimensionControl = (
	blockEditor as unknown as {
		DimensionControl: ComponentType< {
			label: string;
			value?: string;
			placeholder?: string;
			onChange: ( value?: string ) => void;
		} >;
	}
 ).DimensionControl;

export type StateDefinition = {
	/** Attribute name, e.g. `active`. */
	key: string;
	/** Tab label in the grouped color items, e.g. "Default". */
	tab: string;
	/** Panel title, e.g. "Default". */
	panel: string;
};

type Props = {
	states: StateDefinition[];
	values: Record< string, StateStyle | undefined >;
	features: StateFeature[];
	onChange: ( state: string, value: StateStyle ) => void;
	/** Label of the `color` feature, e.g. "Icon". */
	colorLabel?: string;
};

type Palette = Array< { name: string; slug: string; color: string } >;

const hasValue = ( value: unknown ) =>
	value !== undefined &&
	value !== '' &&
	! (
		typeof value === 'object' &&
		value &&
		Object.keys( value ).length === 0
	);

type AnyBorder = {
	color?: string;
	style?: string;
	width?: string | number;
};

/**
 * One BorderControl value (width may be a number) to the stored border.
 * @param border
 */
const toStateBorder = ( border?: AnyBorder ): StateBorder | undefined => {
	if ( ! border ) {
		return undefined;
	}
	const out: StateBorder = {};
	if ( border.color ) {
		out.color = border.color;
	}
	if ( border.style ) {
		out.style = border.style;
	}
	if ( border.width !== undefined && border.width !== '' ) {
		out.width = String( border.width );
	}
	return hasValue( out ) ? out : undefined;
};

/**
 * BorderBoxControl value (linked, or one border per side) to the stored one.
 * @param border
 */
const toStateBorders = (
	border?: AnyBorder | Partial< Record< Side, AnyBorder > >
): StateBorders | undefined => {
	if ( ! border ) {
		return undefined;
	}
	if ( ! SIDES.some( ( side ) => side in border ) ) {
		return toStateBorder( border as AnyBorder );
	}
	const sides: Partial< Record< Side, StateBorder > > = {};
	for ( const side of SIDES ) {
		const value = toStateBorder(
			( border as Partial< Record< Side, AnyBorder > > )[ side ]
		);
		if ( value ) {
			sides[ side ] = value;
		}
	}
	return hasValue( sides ) ? sides : undefined;
};

/**
 * A color item grouping the states in tabs, as core's Elements > Link
 * (Default / Hover): the toggle shows one indicator per state, the dropdown
 * a tab per state with the theme palette. Later states show the color they
 * inherit from the first one while empty.
 * @param root0
 * @param root0.label
 * @param root0.property
 * @param root0.states
 * @param root0.values
 * @param root0.palette
 * @param root0.onChange
 */
function StateColorItem( {
	label,
	property,
	states,
	values,
	palette,
	onChange,
}: {
	label: string;
	property: 'background' | 'color';
	states: StateDefinition[];
	values: Record< string, StateStyle | undefined >;
	palette: Palette;
	onChange: ( state: string, color?: string ) => void;
} ) {
	const value = ( state: string ) => values[ state ]?.[ property ];
	const shown = ( state: string ) =>
		value( state ) ?? value( states[ 0 ].key );

	return (
		<ToolsPanelItem
			className="block-editor-color-gradient-item block-editor-tools-panel-color-gradient-settings__item"
			label={ label }
			hasValue={ () =>
				states.some( ( { key } ) => hasValue( value( key ) ) )
			}
			onDeselect={ () =>
				states.forEach( ( { key } ) => onChange( key, undefined ) )
			}
			isShownByDefault
		>
			<Dropdown
				className="block-editor-tools-panel-color-gradient-settings__dropdown"
				popoverProps={ {
					placement: 'left-start',
					offset: 36,
					shift: true,
				} }
				renderToggle={ ( { onToggle, isOpen } ) => (
					<Button
						__next40pxDefaultSize
						onClick={ onToggle }
						aria-expanded={ isOpen }
						className={ `block-editor-panel-color-gradient-settings__dropdown pc-state-color__button${
							isOpen ? ' is-open' : ''
						}` }
					>
						<span className="block-editor-panel-color-gradient-settings__labeled-indicators pc-state-color__toggle">
							<span className="block-editor-panel-color-gradient-settings__color-indicators">
								{ states.map( ( { key } ) => (
									<ColorIndicator
										key={ key }
										colorValue={ shown( key ) }
									/>
								) ) }
							</span>
							<span className="block-editor-panel-color-gradient-settings__color-name">
								{ label }
							</span>
						</span>
					</Button>
				) }
				renderContent={ () => (
					<DropdownContentWrapper paddingSize="none">
						<div className="block-editor-panel-color-gradient-settings__dropdown-content">
							<TabPanel
								className="pc-state-color__tabs"
								tabs={ states.map( ( { key, tab } ) => ( {
									name: key,
									title: tab,
								} ) ) }
							>
								{ ( tab ) => (
									<div className="pc-state-color__palette">
										<ColorPalette
											__experimentalIsRenderedInSidebar
											enableAlpha
											colors={ palette }
											value={ shown( tab.name ) }
											onChange={ ( color?: string ) =>
												onChange(
													tab.name,
													color || undefined
												)
											}
										/>
									</div>
								) }
							</TabPanel>
						</div>
					</DropdownContentWrapper>
				) }
			/>
		</ToolsPanelItem>
	);
}

/**
 * Style panels for a block with states (e.g. default/active dot): a Color
 * panel with each color grouping the states in tabs (as core's Elements >
 * Link), then one panel per state with width, height, border (linked or per
 * side), radius (linked or per corner) and opacity, as in core's panels.
 *
 * The first state is the base: later states inherit whatever they leave
 * empty (shown as placeholders), so only the differences are stored.
 * Nothing is stored by default: empty values fall back to the block's
 * stylesheet defaults. Resetting clears values so they inherit again.
 * @param props
 */
export default function StatePanels( props: Props ) {
	const { states, values, features, onChange, colorLabel } = props;
	const [ themePalette, customPalette, defaultPalette ] = useSettings(
		'color.palette.theme',
		'color.palette.custom',
		'color.palette.default'
	) as Array< Palette | undefined >;
	const palette: Palette = [
		...( themePalette ?? [] ),
		...( customPalette ?? [] ),
		...( themePalette?.length ? [] : ( defaultPalette ?? [] ) ),
	];

	const has = ( feature: StateFeature ) => features.includes( feature );
	const set = ( state: string, patch: StateStyle ) =>
		onChange( state, { ...values[ state ], ...patch } );

	return (
		<InspectorControls group="styles">
			{ ( has( 'background' ) || has( 'color' ) ) && (
				<ToolsPanel
					label={ __( 'Color', 'profchoicecore' ) }
					resetAll={ () =>
						states.forEach( ( { key } ) =>
							set( key, {
								background: undefined,
								color: undefined,
							} )
						)
					}
					hasInnerWrapper
					className="color-block-support-panel"
					__experimentalFirstVisibleItemClass="first"
					__experimentalLastVisibleItemClass="last"
				>
					<div className="color-block-support-panel__inner-wrapper">
						{ has( 'background' ) && (
							<StateColorItem
								label={ __( 'Background', 'profchoicecore' ) }
								property="background"
								states={ states }
								values={ values }
								palette={ palette }
								onChange={ ( state, background ) =>
									set( state, { background } )
								}
							/>
						) }
						{ has( 'color' ) && (
							<StateColorItem
								label={
									colorLabel ?? __( 'Text', 'profchoicecore' )
								}
								property="color"
								states={ states }
								values={ values }
								palette={ palette }
								onChange={ ( state, color ) =>
									set( state, { color } )
								}
							/>
						) }
					</div>
				</ToolsPanel>
			) }
			{ states.map( ( { key, panel }, index ) => {
				const value = values[ key ] ?? {};
				// Later states inherit what they leave empty from the first one.
				const base =
					index > 0 ? ( values[ states[ 0 ].key ] ?? {} ) : {};
				const item = (
					prop: keyof StateStyle,
					label: string,
					control: JSX.Element,
					isShownByDefault = true
				) => (
					<ToolsPanelItem
						key={ prop }
						label={ label }
						hasValue={ () => hasValue( value[ prop ] ) }
						onDeselect={ () => set( key, { [ prop ]: undefined } ) }
						isShownByDefault={ isShownByDefault }
					>
						{ control }
					</ToolsPanelItem>
				);
				const unit = ( prop: 'width' | 'height', label: string ) =>
					item(
						prop,
						label,
						<DimensionControl
							label={ label }
							placeholder={ base[ prop ] }
							value={ value[ prop ] }
							onChange={ ( next?: string ) =>
								set( key, { [ prop ]: next || undefined } )
							}
						/>,
						false
					);
				return (
					<ToolsPanel
						key={ key }
						className="pc-state-panel"
						label={ panel }
						resetAll={ () => onChange( key, {} ) }
					>
						{ has( 'size' ) &&
							unit( 'width', __( 'Width', 'profchoicecore' ) ) }
						{ has( 'size' ) &&
							unit( 'height', __( 'Height', 'profchoicecore' ) ) }
						{ has( 'border' ) &&
							item(
								'border',
								__( 'Border', 'profchoicecore' ),
								<BorderBoxControl
									label={ __( 'Border', 'profchoicecore' ) }
									colors={ palette }
									enableAlpha
									enableStyle
									popoverOffset={ 40 }
									popoverPlacement="left-start"
									__experimentalIsRenderedInSidebar
									value={ value.border }
									onChange={ ( border ) =>
										set( key, {
											border: toStateBorders( border ),
										} )
									}
								/>
							) }
						{ has( 'radius' ) &&
							item(
								'radius',
								__( 'Radius', 'profchoicecore' ),
								<BorderRadiusControl
									values={ value.radius }
									onChange={ ( radius?: StateRadius ) =>
										set( key, {
											radius: hasValue( radius )
												? radius
												: undefined,
										} )
									}
								/>
							) }
						{ has( 'opacity' ) &&
							item(
								'opacity',
								__( 'Opacity', 'profchoicecore' ),
								<RangeControl
									__next40pxDefaultSize
									__nextHasNoMarginBottom
									label={ __( 'Opacity', 'profchoicecore' ) }
									min={ 0 }
									max={ 1 }
									step={ 0.05 }
									value={
										value.opacity !== undefined
											? Number( value.opacity )
											: undefined
									}
									onChange={ ( opacity?: number ) =>
										set( key, {
											opacity:
												opacity !== undefined
													? String( opacity )
													: undefined,
										} )
									}
								/>,
								false
							) }
					</ToolsPanel>
				);
			} ) }
		</InspectorControls>
	);
}
