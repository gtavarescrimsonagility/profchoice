/**
 * core/cover video playback: Autoplay, Loop, Muted and Lazy load settings for covers
 * with a video background (applied on render by includes/CoverVideo.php),
 * and the "Play" button style, styled in style.scss.
 */
import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { InspectorControls } from '@wordpress/block-editor';
import {
	ToggleControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis -- the panel core's block supports use; no stable export yet.
	__experimentalToolsPanel as ToolsPanel,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis -- see ToolsPanel.
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';
import type { ComponentType } from '@wordpress/element';
import './style.scss';

type Playback = {
	autoplay: boolean;
	loop: boolean;
	muted: boolean;
	lazyLoad: boolean;
};

type BlockSettings = {
	attributes?: Record< string, unknown >;
	[ key: string ]: unknown;
};

type EditProps = {
	name: string;
	attributes: Playback & { backgroundType?: string; poster?: string };
	setAttributes: ( attributes: Partial< Playback > ) => void;
};

export const PLAYBACK_ATTRIBUTES = {
	autoplay: { type: 'boolean', default: true },
	loop: { type: 'boolean', default: true },
	muted: { type: 'boolean', default: true },
	lazyLoad: { type: 'boolean', default: false },
};

/**
 * Adds the playback attributes to core/cover (mirrored in PHP).
 *
 * @param settings Block settings.
 * @param name     Block name.
 * @return Block settings.
 */
export const addAttributes = (
	settings: BlockSettings,
	name: string
): BlockSettings =>
	name === 'core/cover'
		? {
				...settings,
				attributes: {
					...settings.attributes,
					...PLAYBACK_ATTRIBUTES,
				},
			}
		: settings;

addFilter(
	'blocks.registerBlockType',
	'profchoicecore/cover-video/attributes',
	addAttributes
);

/**
 * Playback toggles for a cover with a video background, above the cover's
 * own Settings panel.
 * @param root0
 * @param root0.attributes
 * @param root0.setAttributes
 */
function PlaybackPanel( { attributes, setAttributes }: EditProps ) {
	const { autoplay, loop, muted, lazyLoad, poster } = attributes;
	let lazyLoadHelp: string | undefined;
	if ( autoplay ) {
		lazyLoadHelp = __( 'Only without Autoplay.', 'profchoicecore' );
	} else if ( ! poster ) {
		lazyLoadHelp = __( 'Needs a poster image.', 'profchoicecore' );
	}
	return (
		<InspectorControls>
			<ToolsPanel
				label={ __( 'Video', 'profchoicecore' ) }
				resetAll={ () =>
					setAttributes( {
						autoplay: true,
						loop: true,
						muted: true,
						lazyLoad: false,
					} )
				}
			>
				<ToolsPanelItem
					label={ __( 'Autoplay', 'profchoicecore' ) }
					hasValue={ () => ! autoplay }
					onDeselect={ () => setAttributes( { autoplay: true } ) }
					isShownByDefault
				>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Autoplay', 'profchoicecore' ) }
						help={
							autoplay
								? undefined
								: __(
										'Requires a poster image.',
										'profchoicecore'
									)
						}
						checked={ autoplay }
						onChange={ ( value: boolean ) =>
							setAttributes( { autoplay: value } )
						}
					/>
				</ToolsPanelItem>
				<ToolsPanelItem
					label={ __( 'Loop', 'profchoicecore' ) }
					hasValue={ () => ! loop }
					onDeselect={ () => setAttributes( { loop: true } ) }
					isShownByDefault
				>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Loop', 'profchoicecore' ) }
						checked={ loop }
						onChange={ ( value: boolean ) =>
							setAttributes( { loop: value } )
						}
					/>
				</ToolsPanelItem>
				<ToolsPanelItem
					label={ __( 'Muted', 'profchoicecore' ) }
					hasValue={ () => ! muted }
					onDeselect={ () => setAttributes( { muted: true } ) }
					isShownByDefault
				>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Muted', 'profchoicecore' ) }
						// Browsers only autoplay muted video.
						checked={ autoplay || muted }
						disabled={ autoplay }
						onChange={ ( value: boolean ) =>
							setAttributes( { muted: value } )
						}
					/>
				</ToolsPanelItem>
				<ToolsPanelItem
					label={ __( 'Lazy load', 'profchoicecore' ) }
					hasValue={ () => lazyLoad }
					onDeselect={ () => setAttributes( { lazyLoad: false } ) }
					isShownByDefault
				>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Lazy load', 'profchoicecore' ) }
						help={ lazyLoadHelp }
						// The video loads when Play is clicked, so not with Autoplay.
						checked={ ! autoplay && lazyLoad }
						disabled={ autoplay }
						onChange={ ( value: boolean ) =>
							setAttributes( { lazyLoad: value } )
						}
					/>
				</ToolsPanelItem>
			</ToolsPanel>
		</InspectorControls>
	);
}

const withPlaybackPanel = createHigherOrderComponent(
	( BlockEdit: ComponentType< EditProps > ) => ( props: EditProps ) =>
		props.name === 'core/cover' &&
		props.attributes.backgroundType === 'video' ? (
			<>
				<PlaybackPanel { ...props } />
				<BlockEdit { ...props } />
			</>
		) : (
			<BlockEdit { ...props } />
		),
	'withPlaybackPanel'
);

addFilter(
	'editor.BlockEdit',
	'profchoicecore/cover-video/playback-panel',
	withPlaybackPanel
);
