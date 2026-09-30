import { registerBlockType, type BlockConfiguration } from '@wordpress/blocks';
import metadata from './block.json';
import Edit from './edit';
import save from './save';
import type { NewsletterMessageAttributes } from './types';
import './style.scss';
import './editor.scss';

registerBlockType< NewsletterMessageAttributes >(
	metadata as unknown as BlockConfiguration< NewsletterMessageAttributes >,
	{ edit: Edit, save }
);
