import { __ } from '@wordpress/i18n';
import { InspectorControls, useSettings } from '@wordpress/block-editor';
import {
	BorderControl,
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
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis -- the unit control used by core's dimension panels; no stable export yet.
	__experimentalUnitControl as UnitControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis -- the padding wrapper used by core's color dropdowns.
	__experimentalDropdownContentWrapper as DropdownContentWrapper,
} from '@wordpress/components';
import type { StateBorder, StateFeature, StateStyle } from './state-style';

export type StateDefinition = {
	/** Attribute name, e.g. `active`. */
	key: string;
	/** Tab label in the color dropdowns, e.g. "Default". */
	tab: string;
	/** Title of the state's panel, e.g. "Inactive dot". */
	panel: string;
};

type Props = {
	states: StateDefinition[];
	values: Record< string, StateStyle | undefined >;
	defaults: Record< string, StateStyle | undefined >;
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

/**
 * BorderControl value (width may be a number) to the stored border.
 * @param border
 * @param border.color
 * @param border.style
 * @param border.width
 */
const toStateBorder = ( border?: {
	color?: string;
	style?: string;
	width?: string | number;
} ): StateBorder | undefined =>
	border && hasValue( border )
		? {
				color: border.color,
				style: border.style,
				width:
					border.width !== undefined
						? String( border.width )
						: undefined,
			}
		: undefined;

/**
 * Color item in the style of core's color panel: one dropdown with a tab per
 * state (e.g. Default / Active), each with the theme palette.
 * @param root0
 * @param root0.label
 * @param root0.property
 * @param root0.props
 * @param root0.palette
 */
function StateColorItem( {
	label,
	property,
	props,
	palette,
}: {
	label: string;
	property: 'background' | 'color';
	props: Props;
	palette: Palette;
} ) {
	const { states, values, defaults, onChange } = props;
	const value = ( state: string ) => values[ state ]?.[ property ];
	// Later states inherit an empty color from the first one.
	const shown = ( state: string ) =>
		value( state ) ?? value( states[ 0 ].key );
	const isSet = () =>
		states.some(
			( { key } ) => value( key ) !== defaults[ key ]?.[ property ]
		);

	return (
		<ToolsPanelItem
			className="block-editor-tools-panel-color-gradient-settings__item"
			label={ label }
			hasValue={ isSet }
			onDeselect={ () =>
				states.forEach( ( { key } ) =>
					onChange( key, {
						...values[ key ],
						[ property ]: defaults[ key ]?.[ property ],
					} )
				)
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
											value={ value( tab.name ) }
											onChange={ ( color?: string ) =>
												onChange( tab.name, {
													...values[ tab.name ],
													[ property ]: color,
												} )
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
 * panel with the states grouped per property, then one panel per state with
 * size, border (color, style and width together), radius and opacity. The
 * first state is the base: later states inherit whatever they leave empty
 * (shown as placeholders), so only the differences are stored.
 * @param props
 */
export default function StatePanels( props: Props ) {
	const { states, values, defaults, features, onChange, colorLabel } = props;
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
	const differs = ( state: string, key: keyof StateStyle ) => () =>
		JSON.stringify( values[ state ]?.[ key ] ?? null ) !==
		JSON.stringify( defaults[ state ]?.[ key ] ?? null );
	const reset = ( state: string, keys: Array< keyof StateStyle > ) => () =>
		set(
			state,
			Object.fromEntries(
				keys.map( ( key ) => [ key, defaults[ state ]?.[ key ] ] )
			)
		);

	return (
		<InspectorControls group="styles">
			{ ( has( 'background' ) || has( 'color' ) ) && (
				<ToolsPanel
					label={ __( 'Color', 'profchoicecore' ) }
					resetAll={ () =>
						states.forEach( ( { key } ) =>
							set( key, {
								background: defaults[ key ]?.background,
								color: defaults[ key ]?.color,
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
								props={ props }
								palette={ palette }
							/>
						) }
						{ has( 'color' ) && (
							<StateColorItem
								label={
									colorLabel ?? __( 'Text', 'profchoicecore' )
								}
								property="color"
								props={ props }
								palette={ palette }
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
				const unit = (
					prop: 'width' | 'height' | 'radius',
					label: string,
					units?: Array< { value: string; label: string } >
				) =>
					has( prop === 'radius' ? 'radius' : 'size' ) && (
						<ToolsPanelItem
							key={ prop }
							className="single-column"
							label={ label }
							hasValue={ differs( key, prop ) }
							onDeselect={ reset( key, [ prop ] ) }
							isShownByDefault
						>
							<UnitControl
								__next40pxDefaultSize
								label={ label }
								units={ units }
								placeholder={ base[ prop ] }
								value={ value[ prop ] ?? '' }
								onChange={ ( next?: string ) =>
									set( key, { [ prop ]: next || undefined } )
								}
							/>
						</ToolsPanelItem>
					);
				return (
					<ToolsPanel
						key={ key }
						className="pc-state-panel"
						label={ panel }
						resetAll={ reset( key, [
							'width',
							'height',
							'border',
							'radius',
							'opacity',
						] ) }
					>
						{ unit( 'width', __( 'Width', 'profchoicecore' ) ) }
						{ unit( 'height', __( 'Height', 'profchoicecore' ) ) }
						{ has( 'border' ) && (
							<ToolsPanelItem
								className="single-column"
								label={ __( 'Border', 'profchoicecore' ) }
								hasValue={ differs( key, 'border' ) }
								onDeselect={ reset( key, [ 'border' ] ) }
								isShownByDefault
							>
								<BorderControl
									__next40pxDefaultSize
									label={ __( 'Border', 'profchoicecore' ) }
									colors={ palette }
									enableAlpha
									enableStyle
									__experimentalIsRenderedInSidebar
									placeholder={ base.border?.width }
									value={ value.border }
									onChange={ ( border ) =>
										set( key, {
											border: toStateBorder( border ),
										} )
									}
								/>
							</ToolsPanelItem>
						) }
						{ unit( 'radius', __( 'Radius', 'profchoicecore' ), [
							{ value: 'px', label: 'px' },
							{ value: '%', label: '%' },
							{ value: 'em', label: 'em' },
							{ value: 'rem', label: 'rem' },
						] ) }
						{ has( 'opacity' ) && (
							<ToolsPanelItem
								label={ __( 'Opacity', 'profchoicecore' ) }
								hasValue={ differs( key, 'opacity' ) }
								onDeselect={ reset( key, [ 'opacity' ] ) }
								isShownByDefault
							>
								<RangeControl
									__next40pxDefaultSize
									__nextHasNoMarginBottom
									label={ __( 'Opacity', 'profchoicecore' ) }
									min={ 0 }
									max={ 1 }
									step={ 0.05 }
									allowReset
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
								/>
							</ToolsPanelItem>
						) }
					</ToolsPanel>
				);
			} ) }
		</InspectorControls>
	);
}
