import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import type { BlockEditProps } from '@wordpress/blocks';
import type { TemplateArray } from '../../template';
import StatePanels from '../StatePanels';
import { stateStyleProps } from '../state-style';
import type { CarouselControlAttributes, Direction } from './types';
import '../editor.scss';

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

const LABELS: Record< Direction, string > = {
	prev: __( 'Previous slide', 'profchoicecore' ),
	next: __( 'Next slide', 'profchoicecore' ),
};

const COLORS: Record< Direction, { background: string; text: string } > = {
	prev: { background: '#ffffff00', text: '#ffffff' },
	next: { background: '#ffffff', text: '#14161a' },
};

/**
 * A Button block inside Buttons (core/button only lives in core/buttons).
 *
 * @param direction Previous or next.
 * @return Inner blocks template.
 */
const template = ( direction: Direction ): TemplateArray => [
	[
		'core/buttons',
		{},
		[
			[
				'core/button',
				{
					tagName: 'button',
					type: 'button',
					text: LABELS[ direction ],
					className: `pc-carousel__arrow pc-carousel__arrow--${ direction }`,
					style: {
						color: COLORS[ direction ],
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
			],
		],
	],
];

/**
 * Edit component of the Previous or Next control: a Button block locked in
 * place (it can be styled, not removed). The button text is the accessible
 * name; the arrow icon is drawn in CSS.
 *
 * @param direction Previous or next.
 * @return Edit component.
 */
export const createEdit = ( direction: Direction ) =>
	function Edit( {
		attributes,
		setAttributes,
	}: BlockEditProps< CarouselControlAttributes > ) {
		const { overlay, active, inactive } = attributes;
		const stateProps = stateStyleProps( 'carousel-arrow', {
			active,
			inactive,
		} );
		const blockProps = useBlockProps( {
			className: `pc-carousel__control${ overlay ? ' is-overlay' : '' } ${
				stateProps.className
			}`,
			style: stateProps.style,
		} );
		const innerBlocksProps = useInnerBlocksProps( blockProps, {
			template: template( direction ),
			templateLock: 'all',
		} );

		return (
			<>
				<InspectorControls>
					<PanelBody title={ __( 'Arrow', 'profchoicecore' ) }>
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __(
								'Overlay the slides',
								'profchoicecore'
							) }
							help={ __(
								'Centred vertically at the carousel edge. Adjust with --pc-carousel-arrows-top and --pc-carousel-arrows-inset.',
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
	};
