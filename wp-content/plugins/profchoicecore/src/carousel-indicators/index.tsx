import { registerBlockType, type BlockConfiguration } from '@wordpress/blocks';
import metadata from './block.json';
import Edit from './edit';
import type { CarouselIndicatorsAttributes } from './types';
import './style.scss';

registerBlockType< CarouselIndicatorsAttributes >(
	metadata as unknown as BlockConfiguration< CarouselIndicatorsAttributes >,
	{ edit: Edit, save: () => null }
);
