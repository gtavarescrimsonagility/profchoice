/**
 * Account Tabs behavior: tab switching, keyboard support, and #tab-slug
 * deep-linking. Loaded only on pages containing the block.
 */
( function () {
	'use strict';

	function init_tabs( root ) {
		const tabs = Array.prototype.slice.call( root.querySelectorAll( '.cbv-atabs__tab' ) );
		const nav = root.querySelector( '.cbv-atabs__nav' );

		if ( ! tabs.length ) {
			return;
		}

		const panels = tabs.reduce( function ( controlled_panels, tab ) {
			const panel_id = tab.getAttribute( 'aria-controls' );
			const panel = panel_id ? document.getElementById( panel_id ) : null;

			if (
				! panel ||
				! panel.classList.contains( 'cbv-atabs__panel' ) ||
				controlled_panels.includes( panel )
			) {
				return controlled_panels;
			}

			if ( ! root.contains( panel ) ) {
				root.appendChild( panel );
			}

			controlled_panels.push( panel );

			return controlled_panels;
		}, [] );

		function keep_visible( tab, animate ) {
			if ( ! nav || ! tab || nav.scrollWidth <= nav.clientWidth ) {
				return;
			}

			const gutter = 16;
			const tab_left = tab.offsetLeft;
			const tab_right = tab_left + tab.offsetWidth;
			const viewport_left = nav.scrollLeft;
			const viewport_right = viewport_left + nav.clientWidth;
			let next_scroll = viewport_left;

			if ( tab_left < viewport_left + gutter ) {
				next_scroll = tab_left - gutter;
			} else if ( tab_right > viewport_right - gutter ) {
				next_scroll = tab_right - nav.clientWidth + gutter;
			}

			next_scroll = Math.max( 0, Math.min( next_scroll, nav.scrollWidth - nav.clientWidth ) );

			if ( next_scroll === viewport_left ) {
				return;
			}

			if ( 'function' === typeof nav.scrollTo ) {
				nav.scrollTo( {
					left: next_scroll,
					behavior: animate ? 'smooth' : 'auto',
				} );
			} else {
				nav.scrollLeft = next_scroll;
			}
		}

		function activate( slug, update_hash ) {
			let active_tab = null;

			tabs.forEach( function ( tab ) {
				const is_active = tab.dataset.tab === slug;
				tab.classList.toggle( 'is-active', is_active );
				tab.setAttribute( 'aria-selected', is_active ? 'true' : 'false' );

				if ( is_active ) {
					active_tab = tab;
				}
			} );

			panels.forEach( function ( panel ) {
				const is_active = panel.id === 'cbv-apanel-' + slug;

				if ( is_active ) {
					panel.removeAttribute( 'hidden' );
				} else {
					panel.setAttribute( 'hidden', '' );
				}
			} );

			if ( update_hash && window.history && window.history.replaceState ) {
				window.history.replaceState( null, '', '#tab-' + slug );
			}

			keep_visible( active_tab, update_hash );
		}

		tabs.forEach( function ( tab, index ) {
			tab.addEventListener( 'click', function () {
				activate( tab.dataset.tab, true );
			} );

			tab.addEventListener( 'keydown', function ( event ) {
				let target = null;

				if ( 'ArrowRight' === event.key ) {
					target = tabs[ ( index + 1 ) % tabs.length ];
				} else if ( 'ArrowLeft' === event.key ) {
					target = tabs[ ( index - 1 + tabs.length ) % tabs.length ];
				}

				if ( target ) {
					event.preventDefault();
					target.focus( { preventScroll: true } );
					activate( target.dataset.tab, true );
				}
			} );
		} );

		if ( window.location.hash && 0 === window.location.hash.indexOf( '#tab-' ) ) {
			const slug = window.location.hash.slice( 5 );

			if ( tabs.some( function ( tab ) { return tab.dataset.tab === slug; } ) ) {
				activate( slug, false );
			}
		}
	}

	document.querySelectorAll( '.cbv-atabs' ).forEach( init_tabs );
} )();
