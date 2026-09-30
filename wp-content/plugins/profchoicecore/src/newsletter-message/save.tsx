import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import type { BlockSaveProps } from '@wordpress/blocks';
import type { NewsletterMessageAttributes } from './types';

/**
 * Saved visible; the form's render.php hides it until its state matches.
 * @param root0
 * @param root0.attributes
 */
export default function save( {
	attributes,
}: BlockSaveProps< NewsletterMessageAttributes > ) {
	const blockProps = useBlockProps.save( {
		className: `pc-newsletter-message is-${ attributes.type }`,
		role: 'status',
		'aria-live': 'polite',
		'data-pc-message': attributes.type,
	} );

	return <div { ...useInnerBlocksProps.save( blockProps ) } />;
}
