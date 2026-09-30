import { registerBlockType, type BlockConfiguration } from '@wordpress/blocks';
import metadata from './block.json';
import Edit from './edit';
import save from './save';
import type { NewsletterEmailAttributes } from './types';
import './style.scss';

registerBlockType< NewsletterEmailAttributes >(
	metadata as unknown as BlockConfiguration< NewsletterEmailAttributes >,
	{ edit: Edit, save }
);
