type Box = { left: number; right: number; top: number; bottom: number };

/**
 * Whether a click on an open modal dialog landed on its backdrop: the
 * event targets the dialog itself, outside its box.
 *
 * @param target                       Event target.
 * @param dialog                       The dialog.
 * @param dialog.getBoundingClientRect
 * @param x                            Client X.
 * @param y                            Client Y.
 */
export function isBackdropClick(
	target: unknown,
	dialog: { getBoundingClientRect: () => Box },
	x: number,
	y: number
): boolean {
	if ( target !== dialog ) {
		return false;
	}
	const box = dialog.getBoundingClientRect();
	return x < box.left || x > box.right || y < box.top || y > box.bottom;
}
