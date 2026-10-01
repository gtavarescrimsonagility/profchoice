import { __ } from '@wordpress/i18n';
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import type { TemplateArray } from '../template';

const TEMPLATE: TemplateArray = [
	[ 'profchoice/newsletter-email', {} ],
	[
		'core/buttons',
		{},
		[
			[
				'core/button',
				{
					tagName: 'button',
					type: 'submit',
					text: __( 'Sign Me Up', 'profchoicecore' ),
				},
			],
		],
	],
	[
		'profchoice/newsletter-message',
		{ type: 'success' },
		[
			[
				'core/paragraph',
				{
					content: __(
						"Thanks! You're on the list.",
						'profchoicecore'
					),
				},
			],
		],
	],
	[
		'profchoice/newsletter-message',
		{ type: 'pending' },
		[
			[
				'core/paragraph',
				{
					content: __(
						'Almost there! Check your email to confirm your subscription.',
						'profchoicecore'
					),
				},
			],
		],
	],
	[
		'profchoice/newsletter-message',
		{ type: 'error' },
		[
			[
				'core/paragraph',
				{
					content: __(
						'Something went wrong. Please try again.',
						'profchoicecore'
					),
				},
			],
		],
	],
];

/**
 * Editor view: the inner blocks inside a plain wrapper. The real <form> is
 * rendered by render.php so forms are never nested inside the editor.
 */
export default function Edit() {
	const blockProps = useBlockProps();
	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		template: TEMPLATE,
		templateLock: false,
	} );

	return <div { ...innerBlocksProps } />;
}
