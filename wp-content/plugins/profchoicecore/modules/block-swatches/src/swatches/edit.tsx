import { __, sprintf } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import type { BlockEditProps } from '@wordpress/blocks';
import type { SwatchesAttributes } from './types';

type WooAttribute = { id: number; name: string; slug: string; type: string };

/**
 * Settings and a placeholder preview: the swatches come from the product
 * being displayed, which the editor does not have.
 * @param root0
 * @param root0.attributes
 * @param root0.setAttributes
 */
export default function Edit( {
	attributes,
	setAttributes,
}: BlockEditProps< SwatchesAttributes > ) {
	const { attribute, showLabel, showValue, sizeGuideUrl } = attributes;
	const [ options, setOptions ] = useState< WooAttribute[] >( [] );

	useEffect( () => {
		apiFetch< WooAttribute[] >( { path: '/wc/v3/products/attributes' } )
			.then( setOptions )
			.catch( () => setOptions( [] ) );
	}, [] );

	const current = options.find( ( option ) => option.slug === attribute );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Swatches', 'profchoicecore' ) }>
					<SelectControl
						__next40pxDefaultSize
						label={ __( 'Attribute', 'profchoicecore' ) }
						value={ attribute }
						options={ [
							{
								label: __( 'Select…', 'profchoicecore' ),
								value: '',
							},
							...options.map( ( option ) => ( {
								label: option.name,
								value: option.slug,
							} ) ),
						] }
						onChange={ ( value ) =>
							setAttributes( { attribute: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show label', 'profchoicecore' ) }
						checked={ showLabel }
						onChange={ ( value ) =>
							setAttributes( { showLabel: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show selected value', 'profchoicecore' ) }
						help={ __(
							'Color and image swatches only.',
							'profchoicecore'
						) }
						checked={ showValue }
						onChange={ ( value ) =>
							setAttributes( { showValue: value } )
						}
					/>
					<TextControl
						__next40pxDefaultSize
						label={ __( 'Size guide link', 'profchoicecore' ) }
						type="url"
						value={ sizeGuideUrl }
						onChange={ ( value ) =>
							setAttributes( { sizeGuideUrl: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps( { className: 'pc-swatches' } ) }>
				<p className="pc-swatches__label">
					{ current
						? sprintf(
								/* translators: %s: attribute name. */
								__( '%s swatches', 'profchoicecore' ),
								current.name
							)
						: __( 'Variation swatches', 'profchoicecore' ) }
				</p>
				<p>
					{ __(
						'Shows the values of the displayed product.',
						'profchoicecore'
					) }
				</p>
			</div>
		</>
	);
}
