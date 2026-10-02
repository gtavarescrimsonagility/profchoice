import { __, sprintf } from '@wordpress/i18n';
import type { TemplateArray } from '../template';

/** Overlay and text grays of the placeholders, repeated every four slides. */
const SHADES = [
	[ '#777777', '#555555' ],
	[ '#666666', '#444444' ],
	[ '#555555', '#333333' ],
	[ '#444444', '#222222' ],
];

/**
 * Content of a new slide: a gray Cover labelled with the slide number, to be
 * replaced with real content. Each slide is a shade darker than the last,
 * so they tell apart while switching.
 *
 * @param number Slide number (1-based).
 * @return Inner blocks template for a carousel slide.
 */
export const slidePlaceholder = ( number: number ): TemplateArray => {
	const [ background, text ] = SHADES[ ( number - 1 ) % SHADES.length ];
	return [
		[
			'core/cover',
			{
				customOverlayColor: background,
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
							color: { text },
							elements: { link: { color: { text } } },
						},
					},
				],
			],
		],
	];
};
