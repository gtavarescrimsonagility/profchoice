/**
 * Visually hidden: a rich text format that wraps the selection in
 * `<span class="pc-visually-hidden">`, hidden on screen but read by screen
 * readers (e.g. "Play video" next to an inline icon). The editor shows it,
 * dimmed, only while the text is focused, so it can still be edited.
 */
import { __ } from '@wordpress/i18n';
import { registerFormatType, toggleFormat } from '@wordpress/rich-text';
import { RichTextToolbarButton } from '@wordpress/block-editor';
import './style.scss';

export const NAME = 'profchoice/visually-hidden';

type EditProps = {
	value: Parameters< typeof toggleFormat >[ 0 ];
	onChange: ( value: Parameters< typeof toggleFormat >[ 0 ] ) => void;
	isActive: boolean;
};

/**
 * "Visually hidden" in the rich text toolbar's More menu.
 * @param root0
 * @param root0.value
 * @param root0.onChange
 * @param root0.isActive
 */
function Edit( { value, onChange, isActive }: EditProps ) {
	return (
		<RichTextToolbarButton
			icon="hidden"
			title={ __( 'Visually hidden', 'profchoicecore' ) }
			onClick={ () => onChange( toggleFormat( value, { type: NAME } ) ) }
			isActive={ isActive }
		/>
	);
}

registerFormatType( NAME, {
	title: __( 'Visually hidden', 'profchoicecore' ),
	tagName: 'span',
	className: 'pc-visually-hidden',
	edit: Edit,
} as unknown as Parameters< typeof registerFormatType >[ 1 ] );
