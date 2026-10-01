import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Only the button is saved; render.php adds the wrapper and the
 * navigation bindings.
 */
export default function save() {
	return <InnerBlocks.Content />;
}
