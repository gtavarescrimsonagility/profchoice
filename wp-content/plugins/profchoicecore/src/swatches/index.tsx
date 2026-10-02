import { registerBlockType, type BlockConfiguration } from '@wordpress/blocks';
import metadata from './block.json';
import Edit from './edit';
import type { SwatchesAttributes } from './types';
import './style.scss';

registerBlockType< SwatchesAttributes >(
	metadata as unknown as BlockConfiguration< SwatchesAttributes >,
	{ edit: Edit, save: () => null }
);
