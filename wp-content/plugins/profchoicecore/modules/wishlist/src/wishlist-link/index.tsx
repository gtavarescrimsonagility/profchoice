import {
	registerBlockType,
	type BlockConfiguration,
	BlockEditProps,
} from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './style.scss';

type Attributes = { showLabel: boolean; label?: string };

/**
 * Editor preview with a sample count, and the label settings.
 * @param root0
 * @param root0.attributes
 * @param root0.setAttributes
 */
function Edit( { attributes, setAttributes }: BlockEditProps< Attributes > ) {
	const label = attributes.label || __( 'Wishlist', 'profchoicecore' );
	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'profchoicecore' ) }>
					<ToggleControl
						label={ __( 'Show label', 'profchoicecore' ) }
						checked={ attributes.showLabel }
						onChange={ ( showLabel ) =>
							setAttributes( { showLabel } )
						}
					/>
					<TextControl
						__next40pxDefaultSize
						label={ __( 'Label', 'profchoicecore' ) }
						value={ attributes.label ?? '' }
						placeholder={ __( 'Wishlist', 'profchoicecore' ) }
						onChange={ ( value ) =>
							setAttributes( { label: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<span { ...useBlockProps( { className: 'pc-wishlist-link' } ) }>
				<span className="pc-wishlist-link__icon">
					<svg
						className="pc-inline-icon"
						viewBox="0 0 24 24"
						aria-hidden="true"
						focusable="false"
					>
						<path d="M12 21.35 10.55 20C5.4 15.36 2 12.27 2 8.5 2 5.41 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.08C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.41 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35Z" />
					</svg>
					<span className="pc-wishlist-link__count">2</span>
				</span>
				{ attributes.showLabel && (
					<span className="pc-wishlist-link__label">{ label }</span>
				) }
			</span>
		</>
	);
}

registerBlockType< Attributes >(
	metadata as unknown as BlockConfiguration< Attributes >,
	{ edit: Edit, save: () => null }
);
