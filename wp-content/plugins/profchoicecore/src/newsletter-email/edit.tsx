import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import type { BlockEditProps } from '@wordpress/blocks';
import type { NewsletterEmailAttributes } from './types';

export default function Edit( {
	attributes,
	setAttributes,
}: BlockEditProps< NewsletterEmailAttributes > ) {
	const { label, placeholder } = attributes;
	const blockProps = useBlockProps( {
		type: 'email',
		placeholder,
		'aria-label': label,
		readOnly: true,
	} as Record< string, unknown > );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Field', 'profchoicecore' ) }>
					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __(
							'Label (screen readers)',
							'profchoicecore'
						) }
						value={ label }
						onChange={ ( value: string ) =>
							setAttributes( { label: value } )
						}
					/>
					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Placeholder', 'profchoicecore' ) }
						value={ placeholder }
						onChange={ ( value: string ) =>
							setAttributes( { placeholder: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<input { ...blockProps } />
		</>
	);
}
