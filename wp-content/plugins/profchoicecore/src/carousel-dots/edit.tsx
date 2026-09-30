import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	useBlockProps,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import type { BlockEditProps } from '@wordpress/blocks';
import metadata from './block.json';
import StatePanels from '../shared/StatePanels';
import { stateStyleProps, type StateStyle } from '../shared/state-style';
import type { CarouselDotsAttributes } from './types';
import '../shared/editor.scss';

type EditorSelectors = {
	getBlockParentsByBlockName: ( clientId: string, name: string ) => string[];
	getBlocks: ( clientId: string ) => Array< { name: string } >;
};

const STATES = [
	{
		key: 'default',
		tab: __( 'Default', 'profchoicecore' ),
		panel: __( 'Default', 'profchoicecore' ),
	},
	{
		key: 'active',
		tab: __( 'Active', 'profchoicecore' ),
		panel: __( 'Active', 'profchoicecore' ),
	},
];

const DEFAULTS = {
	default: metadata.attributes.default.default as StateStyle,
	active: metadata.attributes.active.default as StateStyle,
};

/**
 * Preview with one dot per slide (the first one active) and panels to style
 * the default and the active dot.
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
	const { overlay, active } = attributes;
	const base = attributes.default;
	const stateProps = stateStyleProps( 'carousel-dot', {
		default: base,
		active,
	} );

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
			...stateProps.style,
			display: 'flex',
			alignItems: 'center',
			gap: style?.spacing?.blockGap ?? '0.5em',
			justifyContent:
				justify[ layout?.justifyContent ?? 'center' ] ?? 'center',
		},
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Dots', 'profchoicecore' ) }>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Overlay the slides', 'profchoicecore' ) }
						help={ __(
							'Along the bottom of the carousel, placed by the justification and the padding (1rem top and bottom by default).',
							'profchoicecore'
						) }
						checked={ overlay }
						onChange={ ( value: boolean ) =>
							setAttributes( { overlay: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<StatePanels
				states={ STATES }
				values={ { default: base, active } }
				defaults={ DEFAULTS }
				features={ [ 'background', 'border', 'radius', 'size' ] }
				onChange={ ( state, value ) =>
					setAttributes( { [ state ]: value } )
				}
			/>
			<div { ...blockProps }>
				{ Array.from(
					{ length: Math.max( count, 1 ) },
					( _, index ) => (
						<span
							key={ index }
							className={ `pc-carousel__dot${ index === 0 ? ' is-active' : '' }` }
						/>
					)
				) }
			</div>
		</>
	);
}
