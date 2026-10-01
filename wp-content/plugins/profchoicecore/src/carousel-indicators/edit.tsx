import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	useBlockProps,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useRefEffect } from '@wordpress/compose';
import { useState } from '@wordpress/element';
import type { BlockEditProps } from '@wordpress/blocks';
import StatePanels from '../shared/StatePanels';
import { stateStyleProps } from '../shared/state-style';
import type { CarouselIndicatorsAttributes } from './types';
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

/**
 * Preview with one indicator per slide, the one of the slide shown in the
 * editor active, and panels to style the default and the active indicator.
 * @param root0
 * @param root0.attributes
 * @param root0.setAttributes
 * @param root0.clientId
 */
export default function Edit( {
	attributes,
	setAttributes,
	clientId,
}: BlockEditProps< CarouselIndicatorsAttributes > ) {
	const { overlay, active } = attributes;
	const base = attributes.default;
	const stateProps = stateStyleProps( 'carousel-indicator', {
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
	// The carousel's edit keeps the shown slide in its data-pc-active
	// (1-based); follow it from the DOM, as the blocks live in separate
	// bundles.
	const [ current, setCurrent ] = useState( 0 );
	const ref = useRefEffect( ( element: HTMLElement ) => {
		const carousel = element.closest( '.wp-block-profchoice-carousel' );
		if ( ! carousel ) {
			return;
		}
		const sync = () =>
			setCurrent(
				Math.max(
					Number( carousel.getAttribute( 'data-pc-active' ) ) - 1,
					0
				)
			);
		sync();
		const observer = new window.MutationObserver( sync );
		observer.observe( carousel, {
			attributes: true,
			attributeFilter: [ 'data-pc-active' ],
		} );
		return () => observer.disconnect();
	}, [] );
	const blockProps = useBlockProps( {
		ref,
		className: `pc-carousel-indicators${ overlay ? ' is-overlay' : '' }`,
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
							className={ `pc-carousel-indicator${ index === current ? ' is-active' : '' }` }
						/>
					)
				) }
			</div>
		</>
	);
}
