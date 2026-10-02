import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	useInnerBlocksProps,
	InspectorControls,
} from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import type { BlockEditProps } from '@wordpress/blocks';
import type { TemplateArray } from '../template';
import type { NewsletterMessageAttributes } from './types';

const TEMPLATE: TemplateArray = [ [ 'core/paragraph', {} ] ];

export default function Edit( {
	attributes,
	setAttributes,
}: BlockEditProps< NewsletterMessageAttributes > ) {
	const { type } = attributes;
	const labels = {
		success: __( 'Shown on success', 'profchoicecore' ),
		pending: __( 'Shown when pending confirmation', 'profchoicecore' ),
		error: __( 'Shown on error', 'profchoicecore' ),
	};
	const label = labels[ type ] ?? labels.success;
	const blockProps = useBlockProps( {
		className: `pc-newsletter-message is-${ type }`,
		'data-pc-label': label,
	} );
	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		allowedBlocks: [ 'core/paragraph', 'core/heading' ],
		template: TEMPLATE,
		templateLock: false,
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Message', 'profchoicecore' ) }>
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Show on', 'profchoicecore' ) }
						value={ type }
						options={ [
							{
								label: __( 'Success', 'profchoicecore' ),
								value: 'success',
							},
							{
								label: __(
									'Pending confirmation',
									'profchoicecore'
								),
								value: 'pending',
							},
							{
								label: __( 'Error', 'profchoicecore' ),
								value: 'error',
							},
						] }
						onChange={ ( value: string ) =>
							setAttributes( {
								type: value as NewsletterMessageAttributes[ 'type' ],
							} )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...innerBlocksProps } />
		</>
	);
}
