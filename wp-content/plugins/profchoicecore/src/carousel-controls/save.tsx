import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Only the buttons are saved; render.php adds the wrapper and the
 * navigation bindings.
 */
export default function save() {
	return <InnerBlocks.Content />;
}
