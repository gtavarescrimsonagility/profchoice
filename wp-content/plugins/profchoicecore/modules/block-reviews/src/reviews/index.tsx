import {
	registerBlockType,
	type BlockConfiguration,
	BlockEditProps,
} from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, RangeControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './style.scss';

type Attributes = { productId?: number; perPage: number; collapsed: boolean };

/**
 * Editor placeholder and settings: the reviews come from the product shown.
 * @param root0
 * @param root0.attributes
 * @param root0.setAttributes
 */
function Edit( { attributes, setAttributes }: BlockEditProps< Attributes > ) {
	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Reviews', 'profchoicecore' ) }>
					<RangeControl
						__next40pxDefaultSize
						label={ __( 'Reviews per page', 'profchoicecore' ) }
						min={ 1 }
						max={ 6 }
						value={ attributes.perPage }
						onChange={ ( perPage ) =>
							setAttributes( { perPage: perPage ?? 2 } )
						}
					/>
					<ToggleControl
						label={ __( 'Collapsed', 'profchoicecore' ) }
						help={ __(
							'Hidden until a link to #reviews is clicked.',
							'profchoicecore'
						) }
						checked={ attributes.collapsed }
						onChange={ ( collapsed ) =>
							setAttributes( { collapsed } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps( { className: 'pc-reviews' } ) }>
				<p className="pc-reviews__title">
					{ __( 'Customer Reviews', 'profchoicecore' ) }
				</p>
				<p>
					{ __(
						"Shows the product's rating, its reviews in a slider and a Write a Review form.",
						'profchoicecore'
					) }
				</p>
			</div>
		</>
	);
}

registerBlockType< Attributes >(
	metadata as unknown as BlockConfiguration< Attributes >,
	{ edit: Edit, save: () => null }
);
