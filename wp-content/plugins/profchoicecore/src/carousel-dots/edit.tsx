import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	PanelColorSettings,
	useBlockProps,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import type { BlockEditProps } from '@wordpress/blocks';
import type { CarouselDotsAttributes, DotStyle } from './types';
import { dotVars } from './vars';

type EditorSelectors = {
	getBlockParentsByBlockName: ( clientId: string, name: string ) => string[];
	getBlocks: ( clientId: string ) => Array< { name: string } >;
};

const SIZES: Array< [ keyof DotStyle, string ] > = [
	[ 'width', __( 'Width', 'profchoicecore' ) ],
	[ 'height', __( 'Height', 'profchoicecore' ) ],
	[ 'radius', __( 'Radius', 'profchoicecore' ) ],
	[ 'borderWidth', __( 'Border width', 'profchoicecore' ) ],
];

/**
 * Preview with one dot per slide (the first one active) and panels to style
 * the inactive and the active dot.
 * @param root0
 * @param root0.attributes
 * @param root0.setAttributes
 * @param root0.clientId
 */
export default function Edit( {
	attributes,
	setAttributes,
	clientId,
}: BlockEditProps< CarouselDotsAttributes > ) {
	const { overlay, inactive, active } = attributes;

	const count = useSelect(
		( select ) => {
			const editor = select(
				blockEditorStore
			) as unknown as EditorSelectors;
			const [ carousel ] = editor
				.getBlockParentsByBlockName( clientId, 'profchoice/carousel' )
				.slice( -1 );
			return carousel
				? editor
						.getBlocks( carousel )
						.filter(
							( b ) => b.name === 'profchoice/carousel-slide'
						).length
				: 3;
		},
		[ clientId ]
	);

	// The editor does not add layout classes to a block without inner blocks,
	// so the preview applies the flex layout and gap from the attributes.
	const justify: Record< string, string > = {
		left: 'flex-start',
		center: 'center',
		right: 'flex-end',
		'space-between': 'space-between',
	};
	const layout = attributes.layout as { justifyContent?: string } | undefined;
	const style = attributes.style as
		{ spacing?: { blockGap?: string } } | undefined;
	const blockProps = useBlockProps( {
		className: `pc-carousel__dots${ overlay ? ' is-overlay' : '' }`,
		style: {
			...dotVars( attributes ),
			display: 'flex',
			alignItems: 'center',
			gap: style?.spacing?.blockGap ?? '0.5em',
			justifyContent:
				justify[ layout?.justifyContent ?? 'center' ] ?? 'center',
		},
	} );

	const update =
		( state: 'inactive' | 'active', key: keyof DotStyle ) =>
		( value?: string ) =>
			setAttributes( {
				[ state ]: { ...attributes[ state ], [ key ]: value ?? '' },
			} as Partial< CarouselDotsAttributes > );

	const sizePanel = ( state: 'inactive' | 'active', title: string ) => (
		<PanelBody title={ title } initialOpen={ false }>
			{ SIZES.map( ( [ key, label ] ) => (
				<TextControl
					key={ key }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
					label={ label }
					help={ __(
						'Any CSS length, e.g. 12px or 0.5rem.',
						'profchoicecore'
					) }
					value={ attributes[ state ][ key ] }
					onChange={ update( state, key ) }
				/>
			) ) }
		</PanelBody>
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Dots', 'profchoicecore' ) }>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Overlay the slides', 'profchoicecore' ) }
						help={ __(
							'Centred at the bottom of the carousel. Adjust with --pc-carousel-dots-bottom.',
							'profchoicecore'
						) }
						checked={ overlay }
						onChange={ ( value: boolean ) =>
							setAttributes( { overlay: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<InspectorControls group="styles">
				<PanelColorSettings
					title={ __( 'Dot colors', 'profchoicecore' ) }
					enableAlpha
					colorSettings={ [
						{
							label: __( 'Inactive', 'profchoicecore' ),
							value: inactive.color,
							onChange: update( 'inactive', 'color' ),
						},
						{
							label: __( 'Inactive border', 'profchoicecore' ),
							value: inactive.borderColor,
							onChange: update( 'inactive', 'borderColor' ),
						},
						{
							label: __( 'Active', 'profchoicecore' ),
							value: active.color,
							onChange: update( 'active', 'color' ),
						},
						{
							label: __( 'Active border', 'profchoicecore' ),
							value: active.borderColor,
							onChange: update( 'active', 'borderColor' ),
						},
					] }
				/>
				{ sizePanel(
					'inactive',
					__( 'Inactive dot', 'profchoicecore' )
				) }
				{ sizePanel( 'active', __( 'Active dot', 'profchoicecore' ) ) }
			</InspectorControls>
			<div { ...blockProps }>
				{ Array.from(
					{ length: Math.max( count, 1 ) },
					( _, index ) => (
						<span
							key={ index }
							className={ `pc-carousel__dot${
								index === 0 ? ' is-active' : ''
							}` }
						/>
					)
				) }
			</div>
		</>
	);
}
