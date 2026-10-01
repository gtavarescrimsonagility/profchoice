import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import type { TemplateArray } from '../template';

const TEMPLATE: TemplateArray = [ [ 'core/cover', {} ] ];

export default function Edit() {
	const blockProps = useBlockProps();
	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		template: TEMPLATE,
		templateLock: false,
	} );

	return <div { ...innerBlocksProps } />;
}
