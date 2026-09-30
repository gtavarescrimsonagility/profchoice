import { registerBlockType, type BlockConfiguration } from '@wordpress/blocks';
import metadata from './block.json';
import Edit from './edit';
import type { CarouselDotsAttributes } from './types';
import './style.scss';

registerBlockType< CarouselDotsAttributes >(
	metadata as unknown as BlockConfiguration< CarouselDotsAttributes >,
	{ edit: Edit, save: () => null }
);
