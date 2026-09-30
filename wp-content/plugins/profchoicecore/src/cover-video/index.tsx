import { registerBlockType, type BlockConfiguration } from '@wordpress/blocks';
import metadata from './block.json';
import Edit from './edit';
import save from './save';
import type { CoverVideoAttributes } from './types';
import './style.scss';
import './editor.scss';

registerBlockType< CoverVideoAttributes >(
	metadata as unknown as BlockConfiguration< CoverVideoAttributes >,
	{ edit: Edit, save }
);
