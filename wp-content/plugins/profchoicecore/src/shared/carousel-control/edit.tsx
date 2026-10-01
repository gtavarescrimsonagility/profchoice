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
	prev: __( 'Previous', 'profchoicecore' ),
	next: __( 'Next', 'profchoicecore' ),
};

const ICONS: Record< Direction, string > = {
	prev: 'core/chevron-left',
	next: 'core/chevron-right',
};

/**
 * The button text: a chevron Inline icon and a Visually hidden label, the
 * icon on the side the button points to.
 *
 * @param direction Previous or next.
 * @return Button text HTML.
 */
const buttonText = ( direction: Direction ): string => {
	const icon = `<img data-icon="${ ICONS[ direction ] }" alt="" class="pc-inline-icon">`;
	const label = `<span class="pc-visually-hidden">${ LABELS[ direction ] }</span>`;
	return direction === 'prev' ? icon + label : label + icon;
};

/**
 * A Button block inside Buttons (core/button only lives in core/buttons).
 * Its look (round, 2rem icon, 0.5rem padding) comes from style.scss, so the
 * block's own settings only hold customizations.
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
					text: buttonText( direction ),
				},
			],
		],
	],
];

/**
 * Edit component of the Previous or Next control: a Button block locked in
 * place (it can be styled, not removed).
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
					// See the :disabled rule in style.scss.
					opacityDefaults={ { active: 1, inactive: 0.35 } }
					onChange={ ( state, value ) =>
						setAttributes( { [ state ]: value } )
					}
				/>
				<div { ...innerBlocksProps } />
			</>
		);
	};
