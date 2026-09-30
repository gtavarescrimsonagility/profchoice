import { registerBlockType, type BlockConfiguration } from '@wordpress/blocks';
import metadata from './block.json';
import Edit from './edit';
import save from './save';
import type { NewsletterFormAttributes } from './types';
import './style.scss';

registerBlockType< NewsletterFormAttributes >(
	metadata as unknown as BlockConfiguration< NewsletterFormAttributes >,
	{ edit: Edit, save }
);
