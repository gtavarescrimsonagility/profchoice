/**
 * Dismiss buttons of the alerts (WooCommerce notices). Delegated from the
 * document: checkout adds notices with JavaScript after load.
 */
document.addEventListener( 'click', ( event ) => {
	const button = event.target.closest( '.pc-alert__close' );
	const alert = button?.closest( '.pc-alert' );
	if ( ! alert ) {
		return;
	}
	const reduce = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	if ( reduce ) {
		alert.remove();
		return;
	}
	alert.classList.add( 'is-dismissing' );
	alert.addEventListener( 'transitionend', () => alert.remove(), { once: true } );
} );
