import { __, sprintf } from '@wordpress/i18n';
import type { TemplateArray } from '../template';

/**
 * Content of a new slide: a gray Cover labelled with the slide number, to be
 * replaced with real content.
 *
 * @param number Slide number (1-based).
 * @return Inner blocks template for a carousel slide.
 */
export const slidePlaceholder = ( number: number ): TemplateArray => [
	[
		'core/cover',
		{
			customOverlayColor: '#777777',
			isUserOverlayColor: true,
			contentPosition: 'center center',
			align: 'none',
		},
		[
			[
				'core/paragraph',
				{
					placeholder: __( 'Content…', 'profchoicecore' ),
					content: sprintf(
						/* translators: %d: slide number. */
						__( 'Slide %d', 'profchoicecore' ),
						number
					),
					style: {
						typography: {
							textAlign: 'center',
							fontSize: '3.5rem',
						},
						color: { text: '#555555' },
						elements: { link: { color: { text: '#555555' } } },
					},
				},
			],
		],
	],
];
