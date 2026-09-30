import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Only the inner blocks are saved; render.php wraps them in the <form>.
 */
export default function save() {
	return <InnerBlocks.Content />;
}
