import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import type { BlockEditProps } from '@wordpress/blocks';
import type { TemplateArray } from '../template';
import metadata from './block.json';
import StatePanels from '../shared/StatePanels';
import { stateStyleProps, type StateStyle } from '../shared/state-style';
import type { CarouselControlsAttributes } from './types';
import '../shared/editor.scss';

const STATES = [
	{
		key: 'active',
		tab: __( 'Active', 'profchoicecore' ),
		panel: __( 'Active control', 'profchoicecore' ),
	},
	{
		key: 'inactive',
		tab: __( 'Inactive', 'profchoicecore' ),
		panel: __( 'Inactive control', 'profchoicecore' ),
	},
];

const DEFAULTS = {
	active: metadata.attributes.active.default as StateStyle,
	inactive: metadata.attributes.inactive.default as StateStyle,
};

const arrow = (
	direction: 'prev' | 'next',
	text: string,
	colors: { background: string; text: string }
): [ string, Record< string, unknown > ] => [
	'core/button',
	{
		tagName: 'button',
		type: 'button',
		text,
		className: `pc-carousel__arrow pc-carousel__arrow--${ direction }`,
		style: {
			color: colors,
			border: {
				width: '2px',
				style: 'solid',
				color: '#ffffff',
				radius: '50%',
			},
			spacing: {
				padding: {
					top: '13.5px',
					right: '13.5px',
					bottom: '13.5px',
					left: '13.5px',
				},
			},
		},
	},
];

const TEMPLATE: TemplateArray = [
	[
		'core/buttons',
		{ layout: { type: 'flex', justifyContent: 'space-between' } },
		[
			arrow( 'prev', __( 'Previous slide', 'profchoicecore' ), {
				background: '#ffffff00',
				text: '#ffffff',
			} ),
			arrow( 'next', __( 'Next slide', 'profchoicecore' ), {
				background: '#ffffff',
				text: '#14161a',
			} ),
		],
	],
];

/**
 * Two Button blocks locked in place (they can be styled, not removed). The
 * button text is the accessible name; the arrow icon is drawn in CSS.
 * @param root0
 * @param root0.attributes
 * @param root0.setAttributes
 */
export default function Edit( {
	attributes,
	setAttributes,
}: BlockEditProps< CarouselControlsAttributes > ) {
	const { overlay, active, inactive } = attributes;
	const stateProps = stateStyleProps( 'carousel-arrow', {
		active,
		inactive,
	} );
	const blockProps = useBlockProps( {
		className: `pc-carousel__controls${ overlay ? ' is-overlay' : '' } ${
			stateProps.className
		}`,
		style: stateProps.style,
	} );
	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		template: TEMPLATE,
		templateLock: 'insert',
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Arrows', 'profchoicecore' ) }>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Overlay the slides', 'profchoicecore' ) }
						help={ __(
							'Centred vertically at the carousel edges. Adjust with --pc-carousel-arrows-top and --pc-carousel-arrows-inset.',
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
				values={ { active, inactive } }
				defaults={ DEFAULTS }
				features={ [
					'background',
					'color',
					'border',
					'radius',
					'size',
					'opacity',
				] }
				colorLabel={ __( 'Icon', 'profchoicecore' ) }
				onChange={ ( state, value ) =>
					setAttributes( { [ state ]: value } )
				}
			/>
			<div { ...innerBlocksProps } />
		</>
	);
}
