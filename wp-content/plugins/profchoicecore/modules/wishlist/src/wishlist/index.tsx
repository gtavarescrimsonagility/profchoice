import { registerBlockType, type BlockConfiguration } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './style.scss';

/**
 * Editor placeholder: the lists belong to the visitor viewing the page.
 */
function Edit() {
	return (
		<div { ...useBlockProps( { className: 'pc-wishlist' } ) }>
			<p className="pc-wishlist__title">
				{ __( 'Wishlist', 'profchoicecore' ) }
			</p>
			<p>
				{ __(
					"Shows the visitor's lists and saved products.",
					'profchoicecore'
				) }
			</p>
		</div>
	);
}

registerBlockType( metadata as unknown as BlockConfiguration, {
	edit: Edit,
	save: () => null,
} );
