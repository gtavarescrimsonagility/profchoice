import { registerBlockType, type BlockConfiguration } from '@wordpress/blocks';
import metadata from './block.json';
import Edit from './edit';
import save from './save';

registerBlockType( metadata as unknown as BlockConfiguration, {
	edit: Edit,
	save,
} );
