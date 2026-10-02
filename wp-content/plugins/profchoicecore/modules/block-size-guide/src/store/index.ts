/**
 * Size guide store: the link opens its page's dialog (a native modal:
 * focus trap, Esc, inert page); the close button and the backdrop close
 * it, and the focus goes back to the link.
 */
import { store, getContext, getElement } from '@wordpress/interactivity';
import { isBackdropClick } from './helpers';

let opener: HTMLElement | null = null;

store( 'profchoice/size-guide', {
	actions: {
		open( event: MouseEvent ): void {
			const { dialog } = getContext< { dialog: string } >();
			const element = document.getElementById(
				dialog
			) as HTMLDialogElement | null;
			if ( ! element ) {
				return;
			}
			event.preventDefault();
			opener = getElement().ref as HTMLElement | null;
			element.showModal();
		},
		close(): void {
			const { ref } = getElement();
			ref?.closest( 'dialog' )?.close();
		},
		backdrop( event: MouseEvent ): void {
			const dialog = event.currentTarget as HTMLDialogElement;
			if (
				isBackdropClick(
					event.target,
					dialog,
					event.clientX,
					event.clientY
				)
			) {
				dialog.close();
			}
		},
		closed(): void {
			opener?.focus();
			opener = null;
		},
	},
} );
