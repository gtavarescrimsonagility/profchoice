import { registerBlockType, type BlockConfiguration } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './style.scss';

type Attributes = { productId?: number };

/**
 * Editor preview: the outline heart (the state depends on the visitor).
 */
function Edit() {
	return (
		<button
			{ ...useBlockProps( { className: 'pc-wishlist-button' } ) }
			type="button"
			aria-label={ __( 'Add to wishlist', 'profchoicecore' ) }
		>
			<svg
				className="pc-inline-icon"
				viewBox="0 0 24 24"
				aria-hidden="true"
				focusable="false"
			>
				<path d="M12 21.35 10.55 20C5.4 15.36 2 12.27 2 8.5 2 5.41 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.08C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.41 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35Z" />
			</svg>
		</button>
	);
}

registerBlockType< Attributes >(
	metadata as unknown as BlockConfiguration< Attributes >,
	{ edit: Edit, save: () => null }
);
