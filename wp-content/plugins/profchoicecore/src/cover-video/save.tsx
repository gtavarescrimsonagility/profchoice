import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Only the content is saved; render.php adds the video, the overlay and the
 * Play button bindings.
 */
export default function save() {
	return <InnerBlocks.Content />;
}
