import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Only the slides are saved; render.php builds the viewport and controls.
 */
export default function save() {
	return <InnerBlocks.Content />;
}
