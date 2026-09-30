import { __ } from '@wordpress/i18n';
import {
	BlockControls,
	InspectorControls,
	MediaPlaceholder,
	MediaUpload,
	MediaUploadCheck,
	PanelColorSettings,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import {
	FocalPointPicker,
	PanelBody,
	RangeControl,
	ToggleControl,
	ToolbarButton,
	ToolbarGroup,
} from '@wordpress/components';
import type { BlockEditProps } from '@wordpress/blocks';
import type { TemplateArray } from '../template';
import type { CoverVideoAttributes } from './types';

type Media = { id?: number; url?: string };

const TEMPLATE: TemplateArray = [
	[
		'core/buttons',
		{ layout: { type: 'flex', justifyContent: 'center' } },
		[
			[
				'core/button',
				{
					tagName: 'button',
					type: 'button',
					text: __( 'Play video', 'profchoicecore' ),
					className: 'pc-cover-video__play',
					style: {
						color: { background: '#ffffff1a', text: '#ffffff' },
						border: {
							width: '1px',
							style: 'solid',
							color: '#ffffff8c',
							radius: '50%',
						},
						spacing: {
							padding: {
								top: '29px',
								right: '29px',
								bottom: '29px',
								left: '29px',
							},
						},
					},
				},
			],
		],
	],
	[ 'core/spacer', { height: '38px' } ],
	[
		'core/buttons',
		{ layout: { type: 'flex', justifyContent: 'center' } },
		[ [ 'core/button', { text: __( 'Shop now', 'profchoicecore' ) } ] ],
	],
];

/**
 * A cover with a poster-first background video. The preview shows the poster
 * (or the first frame); the inner blocks hold the Play button and the rest of
 * the content.
 * @param root0
 * @param root0.attributes
 * @param root0.setAttributes
 */
export default function Edit( {
	attributes,
	setAttributes,
}: BlockEditProps< CoverVideoAttributes > ) {
	const {
		videoUrl,
		posterId,
		posterUrl,
		focalPoint,
		dimRatio,
		overlayColor,
		loop,
	} = attributes;

	const blockProps = useBlockProps( { className: 'pc-cover-video' } );
	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'pc-cover-video__inner' },
		{ template: TEMPLATE }
	);

	const onSelectVideo = ( media: Media ) =>
		setAttributes( { videoId: media.id, videoUrl: media.url } );
	const onSelectPoster = ( media: Media ) =>
		setAttributes( { posterId: media.id, posterUrl: media.url } );

	if ( ! videoUrl ) {
		return (
			<div { ...blockProps }>
				<MediaPlaceholder
					icon="format-video"
					labels={ {
						title: __( 'Cover Video', 'profchoicecore' ),
						instructions: __(
							'Upload a video or pick one from the media library.',
							'profchoicecore'
						),
					} }
					accept="video/*"
					allowedTypes={ [ 'video' ] }
					onSelect={ onSelectVideo }
				/>
			</div>
		);
	}

	const position = focalPoint
		? `${ Math.round( focalPoint.x * 100 ) }% ${ Math.round(
				focalPoint.y * 100
			) }%`
		: undefined;

	return (
		<>
			<BlockControls group="other">
				<MediaUploadCheck>
					<ToolbarGroup>
						<MediaUpload
							allowedTypes={ [ 'video' ] }
							onSelect={ onSelectVideo }
							render={ ( { open } ) => (
								<ToolbarButton onClick={ open }>
									{ __( 'Replace video', 'profchoicecore' ) }
								</ToolbarButton>
							) }
						/>
						<MediaUpload
							allowedTypes={ [ 'image' ] }
							value={ posterId }
							onSelect={ onSelectPoster }
							render={ ( { open } ) => (
								<ToolbarButton onClick={ open }>
									{ posterUrl
										? __(
												'Replace poster',
												'profchoicecore'
											)
										: __( 'Add poster', 'profchoicecore' ) }
								</ToolbarButton>
							) }
						/>
					</ToolbarGroup>
				</MediaUploadCheck>
			</BlockControls>
			<InspectorControls>
				<PanelBody title={ __( 'Video', 'profchoicecore' ) }>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Loop', 'profchoicecore' ) }
						help={ __(
							'The video starts on its poster and plays muted from the Play button.',
							'profchoicecore'
						) }
						checked={ loop }
						onChange={ ( value: boolean ) =>
							setAttributes( { loop: value } )
						}
					/>
					{ posterUrl && (
						<FocalPointPicker
							__nextHasNoMarginBottom
							label={ __( 'Focal point', 'profchoicecore' ) }
							url={ posterUrl }
							value={ focalPoint ?? { x: 0.5, y: 0.5 } }
							onChange={ ( value ) =>
								setAttributes( { focalPoint: value } )
							}
						/>
					) }
				</PanelBody>
			</InspectorControls>
			<InspectorControls group="styles">
				<PanelColorSettings
					title={ __( 'Overlay', 'profchoicecore' ) }
					enableAlpha
					colorSettings={ [
						{
							label: __( 'Overlay', 'profchoicecore' ),
							// The component types the value as required; empty means none.
							value: overlayColor as string,
							onChange: ( value?: string ) =>
								setAttributes( { overlayColor: value } ),
						},
					] }
				>
					<RangeControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Overlay opacity', 'profchoicecore' ) }
						min={ 0 }
						max={ 100 }
						step={ 10 }
						value={ dimRatio }
						onChange={ ( value?: number ) =>
							setAttributes( { dimRatio: value ?? 0 } )
						}
					/>
				</PanelColorSettings>
			</InspectorControls>
			<div { ...blockProps }>
				<video
					className="pc-cover-video__video"
					src={ videoUrl }
					poster={ posterUrl }
					style={ { objectPosition: position } }
					muted
					playsInline
					preload="metadata"
				/>
				<span
					className="pc-cover-video__overlay"
					aria-hidden="true"
					style={ {
						backgroundColor: overlayColor,
						opacity: dimRatio / 100,
					} }
				/>
				<div { ...innerBlocksProps } />
			</div>
		</>
	);
}
