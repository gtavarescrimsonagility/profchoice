import {
	useBlockProps,
	useInnerBlocksProps,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import type { BlockEditProps } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
import { slidePlaceholder } from './placeholder';

type EditorSelectors = {
	getBlockIndex: ( clientId: string ) => number;
};

export default function Edit( {
	clientId,
}: BlockEditProps< Record< string, unknown > > ) {
	// Only used to label the placeholder of an empty slide.
	const index = useSelect(
		( select ) =>
			(
				select( blockEditorStore ) as unknown as EditorSelectors
			 ).getBlockIndex( clientId ),
		[ clientId ]
	);
	const blockProps = useBlockProps();
	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		template: slidePlaceholder( Math.max( index, 0 ) + 1 ),
		templateLock: false,
	} );

	return <div { ...innerBlocksProps } />;
}
