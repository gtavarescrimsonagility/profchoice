import { registerBlockType, type BlockConfiguration } from '@wordpress/blocks';
import metadata from './block.json';
import { createEdit } from '../shared/carousel-control/edit';
import save from '../shared/carousel-control/save';
import type { CarouselControlAttributes } from '../shared/carousel-control/types';
import './style.scss';

registerBlockType< CarouselControlAttributes >(
	metadata as unknown as BlockConfiguration< CarouselControlAttributes >,
	{ edit: createEdit( 'next' ), save }
);
