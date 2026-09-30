import { registerBlockType, type BlockConfiguration } from '@wordpress/blocks';
import metadata from './block.json';
import Edit from './edit';
import save from './save';
import type { CarouselControlsAttributes } from './types';
import './style.scss';

registerBlockType< CarouselControlsAttributes >(
	metadata as unknown as BlockConfiguration< CarouselControlsAttributes >,
	{ edit: Edit, save }
);
