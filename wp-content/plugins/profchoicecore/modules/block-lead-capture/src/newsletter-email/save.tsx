import { useBlockProps } from '@wordpress/block-editor';
import type { BlockSaveProps } from '@wordpress/blocks';
import type { NewsletterEmailAttributes } from './types';

/**
 * The block's root element is the <input> itself, so the block supports
 * (typography, color, spacing, border) style the field directly.
 * @param root0
 * @param root0.attributes
 */
export default function save( {
	attributes,
}: BlockSaveProps< NewsletterEmailAttributes > ) {
	const blockProps = useBlockProps.save( {
		type: 'email',
		name: 'email',
		required: true,
		autoComplete: 'email',
		placeholder: attributes.placeholder,
		'aria-label': attributes.label,
	} as Record< string, unknown > );

	return <input { ...blockProps } />;
}
