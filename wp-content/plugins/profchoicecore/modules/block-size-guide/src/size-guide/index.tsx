import {
	registerBlockType,
	type BlockConfiguration,
	BlockEditProps,
} from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './style.scss';

type Attributes = { attribute: string; pageId?: number; text?: string };

/**
 * Editor preview: the link, with the attribute or page it opens.
 * @param root0
 * @param root0.attributes
 * @param root0.setAttributes
 */
function Edit( { attributes, setAttributes }: BlockEditProps< Attributes > ) {
	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Size guide', 'profchoicecore' ) }>
					<TextControl
						__next40pxDefaultSize
						label={ __( 'Attribute', 'profchoicecore' ) }
						help={ __(
							'e.g. pa_size: opens the page set in Products > Attributes.',
							'profchoicecore'
						) }
						value={ attributes.attribute }
						onChange={ ( attribute ) =>
							setAttributes( { attribute } )
						}
					/>
					<TextControl
						__next40pxDefaultSize
						label={ __( 'Page ID (optional)', 'profchoicecore' ) }
						type="number"
						value={
							attributes.pageId ? String( attributes.pageId ) : ''
						}
						onChange={ ( value ) =>
							setAttributes( {
								pageId: Number( value ) || undefined,
							} )
						}
					/>
					<TextControl
						__next40pxDefaultSize
						label={ __( 'Link text', 'profchoicecore' ) }
						value={ attributes.text ?? '' }
						placeholder={ __( 'Size Guide', 'profchoicecore' ) }
						onChange={ ( text ) => setAttributes( { text } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<span { ...useBlockProps( { className: 'pc-size-guide__link' } ) }>
				{ attributes.text || __( 'Size Guide', 'profchoicecore' ) }
			</span>
		</>
	);
}

registerBlockType< Attributes >(
	metadata as unknown as BlockConfiguration< Attributes >,
	{ edit: Edit, save: () => null }
);
