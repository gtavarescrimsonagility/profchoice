import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

/**
 * Slide semantics from rtCamp/rt-carousel (GPL-2.0-or-later); render.php of
 * the carousel adds the "n of N" label and the active-state bindings.
 */
export default function save() {
	const blockProps = useBlockProps.save( {
		role: 'group',
		'aria-roledescription': 'slide',
	} );

	return <div { ...useInnerBlocksProps.save( blockProps ) } />;
}
