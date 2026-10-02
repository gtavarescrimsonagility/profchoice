import { registerBlockType, type BlockConfiguration } from '@wordpress/blocks';
import metadata from './block.json';
import Edit from './edit';
import save from './save';
import type { CarouselAttributes } from './types';
import './style.scss';
import './editor.scss';

registerBlockType< CarouselAttributes >(
	metadata as unknown as BlockConfiguration< CarouselAttributes >,
	{ edit: Edit, save }
);
