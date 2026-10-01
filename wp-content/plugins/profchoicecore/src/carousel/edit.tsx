import { __, sprintf } from '@wordpress/i18n';
import {
	BlockControls,
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import {
	MenuGroup,
	MenuItemsChoice,
	PanelBody,
	RangeControl,
	SelectControl,
	TextControl,
	ToggleControl,
	ToolbarButton,
	ToolbarDropdownMenu,
	ToolbarGroup,
} from '@wordpress/components';
import {
	createBlock,
	createBlocksFromInnerBlocksTemplate,
	type BlockEditProps,
} from '@wordpress/blocks';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import type { TemplateArray } from '../template';
import { slidePlaceholder } from '../carousel-slide/placeholder';
import type { CarouselAttributes, Transition } from './types';

type EditorSelectors = {
	getBlocks: ( clientId: string ) => Array< {
		clientId: string;
		name: string;
		innerBlocks: Array< { name: string } >;
	} >;
	getSelectedBlockClientId: () => string | null;
	getBlockParents: ( clientId: string ) => string[];
};

const SLIDE = 'profchoice/carousel-slide';
const ALLOWED = [
	SLIDE,
	'profchoice/carousel-controls',
	'profchoice/carousel-dots',
];
const TEMPLATE: TemplateArray = [
	[ SLIDE, {}, slidePlaceholder( 1 ) ],
	[ 'profchoice/carousel-controls' ],
	[ 'profchoice/carousel-dots' ],
];

/**
 * Editor: one slide at a time, picked from the toolbar, instead of a live
 * carousel. Selecting a block inside a slide shows that slide.
 * @param root0
 * @param root0.attributes
 * @param root0.setAttributes
 * @param root0.clientId
 */
export default function Edit( {
	attributes,
	setAttributes,
	clientId,
}: BlockEditProps< CarouselAttributes > ) {
	const { ariaLabel, transition, loop, autoplay, autoplayDelay } = attributes;
	const [ active, setActive ] = useState( 0 );

	const { slideIds, slideContent, selectedSlide } = useSelect(
		( select ) => {
			const editor = select(
				blockEditorStore
			) as unknown as EditorSelectors;
			const slides = editor
				.getBlocks( clientId )
				.filter( ( block ) => block.name === SLIDE );
			const ids = slides.map( ( block ) => block.clientId );
			const selected = editor.getSelectedBlockClientId();
			const chain = selected
				? [ selected, ...editor.getBlockParents( selected ) ]
				: [];
			return {
				slideIds: ids,
				// The first block of each slide, so a new slide matches them.
				slideContent: slides.map(
					( block ) => block.innerBlocks[ 0 ]?.name
				),
				selectedSlide: ids.findIndex( ( id ) => chain.includes( id ) ),
			};
		},
		[ clientId ]
	);
	const { insertBlock } = useDispatch( blockEditorStore );

	useEffect( () => {
		if ( selectedSlide >= 0 ) {
			setActive( selectedSlide );
		}
	}, [ selectedSlide ] );

	const current = Math.min( active, Math.max( slideIds.length - 1, 0 ) );

	const blockProps = useBlockProps( {
		className: 'pc-carousel is-editing',
		'data-pc-active': current + 1,
	} );
	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		allowedBlocks: ALLOWED,
		template: TEMPLATE,
		orientation: 'horizontal',
		renderAppender: () => null,
	} );

	// A new slide gets the same placeholder as the first one ("Slide N"
	// Cover), unless the current slide starts with another kind of block
	// (e.g. Media & Text): then an empty one of those, so the carousel keeps
	// its structure.
	const addSlide = () => {
		const content =
			slideContent[ current ] ?? slideContent[ slideContent.length - 1 ];
		const inner =
			content && content !== 'core/cover'
				? [ createBlock( content ) ]
				: createBlocksFromInnerBlocksTemplate(
						slidePlaceholder( slideIds.length + 1 )
					);
		insertBlock(
			createBlock( SLIDE, {}, inner ),
			slideIds.length,
			clientId
		);
		setActive( slideIds.length );
	};

	return (
		<>
			<BlockControls group="block">
				<ToolbarGroup>
					<ToolbarDropdownMenu
						icon={ null }
						text={ sprintf(
							/* translators: %d: slide number. */
							__( 'Slide %d', 'profchoicecore' ),
							current + 1
						) }
						label={ __( 'Show slide', 'profchoicecore' ) }
					>
						{ ( { onClose } ) => (
							<MenuGroup>
								<MenuItemsChoice
									value={ String( current ) }
									choices={ slideIds.map(
										( _id, index ) => ( {
											value: String( index ),
											label: sprintf(
												/* translators: %d: slide number. */
												__(
													'Slide %d',
													'profchoicecore'
												),
												index + 1
											),
										} )
									) }
									onHover={ () => {} }
									onSelect={ ( value: string ) => {
										setActive( Number( value ) );
										onClose();
									} }
								/>
							</MenuGroup>
						) }
					</ToolbarDropdownMenu>
					<ToolbarButton icon="plus" onClick={ addSlide }>
						{ __( 'Add slide', 'profchoicecore' ) }
					</ToolbarButton>
				</ToolbarGroup>
			</BlockControls>
			<InspectorControls>
				<PanelBody title={ __( 'Carousel', 'profchoicecore' ) }>
					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Accessible name', 'profchoicecore' ) }
						value={ ariaLabel }
						onChange={ ( value: string ) =>
							setAttributes( { ariaLabel: value } )
						}
					/>
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Transition', 'profchoicecore' ) }
						value={ transition }
						options={ [
							{
								label: __( 'Fade', 'profchoicecore' ),
								value: 'fade',
							},
							{
								label: __( 'Slide', 'profchoicecore' ),
								value: 'slide',
							},
						] }
						onChange={ ( value: string ) =>
							setAttributes( { transition: value as Transition } )
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Loop', 'profchoicecore' ) }
						checked={ loop }
						onChange={ ( value: boolean ) =>
							setAttributes( { loop: value } )
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Autoplay', 'profchoicecore' ) }
						help={ __(
							'Pauses on hover and focus, and never runs for visitors who prefer reduced motion.',
							'profchoicecore'
						) }
						checked={ autoplay }
						onChange={ ( value: boolean ) =>
							setAttributes( { autoplay: value } )
						}
					/>
					{ autoplay && (
						<RangeControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __(
								'Seconds per slide',
								'profchoicecore'
							) }
							value={ autoplayDelay / 1000 }
							min={ 2 }
							max={ 20 }
							onChange={ ( value?: number ) =>
								setAttributes( {
									autoplayDelay: ( value ?? 6 ) * 1000,
								} )
							}
						/>
					) }
				</PanelBody>
			</InspectorControls>
			<div { ...innerBlocksProps } />
		</>
	);
}
