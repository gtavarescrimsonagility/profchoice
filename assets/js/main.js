/**
 * commercebuild Velocity frontend script.
 *
 * Deliberately tiny and dependency-free. Anything heavier belongs in a
 * conditionally-loaded module, not here.
 */
( function () {
	'use strict';

	document.documentElement.classList.add( 'cbv-nav-js' );

	/**
	 * Whether this specific event came from a pointer that hovers.
	 *
	 * A media query answers for the device's PRIMARY pointer, not for the
	 * input that fired this event. On a hybrid touchscreen laptop
	 * `(hover: hover) and (pointer: fine)` matches, so a touch-generated
	 * mouseenter passed the old guard and opened the submenu. The synthesized
	 * click that followed saw aria-expanded="true" and closed it again, leaving
	 * the first tap closed. Read the event's own pointerType.
	 */
	function hover_event_from_pointer( event ) {
		if ( event && typeof event.pointerType === 'string' ) {
			// Only 'mouse'. pointerType names the device CLASS, not whether that
			// device hovers. A pen that does not support hover still dispatches
			// pointerenter as part of activation, so accepting 'pen' reopens the
			// same defect this guard exists to fix: the submenu opens on that
			// enter, then the activation click sees it open and closes it.
			// https://www.w3.org/TR/pointerevents/#mapping-for-devices-that-do-not-support-hover
			return 'mouse' === event.pointerType;
		}

		// No PointerEvent (older Safari): fall back to the device query.
		return window.matchMedia( '(hover: hover) and (pointer: fine)' ).matches;
	}

	function direct_submenu( item ) {
		return Array.prototype.find.call( item.children, function ( child ) {
			return child.classList.contains( 'sub-menu' );
		} );
	}

	function set_submenu_open( item, open ) {
		const toggle = Array.prototype.find.call( item.children, function ( child ) {
			return child.classList.contains( 'cbv-nav__toggle' );
		} );

		if ( ! toggle ) {
			return;
		}

		item.classList.toggle( 'is-submenu-open', open );
		toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );

		if ( ! open ) {
			item.querySelectorAll( '.is-submenu-open' ).forEach( function ( child ) {
				child.classList.remove( 'is-submenu-open' );
				child.querySelector( '.cbv-nav__toggle' )?.setAttribute( 'aria-expanded', 'false' );
			} );
		}
	}

	document.querySelectorAll( '.cbv-nav__list' ).forEach( function ( menu, menu_index ) {
		const toggles = menu.querySelectorAll( '.cbv-nav__toggle' );
		const hover_submenus = ! menu.closest( '#cbv-mobile-nav' );

		toggles.forEach( function ( toggle, toggle_index ) {
			const item = toggle.parentElement;
			const submenu = direct_submenu( item );

			if ( ! submenu ) {
				return;
			}

			if ( ! submenu.id ) {
				submenu.id = 'cbv-submenu-' + menu_index + '-' + toggle_index;
			}

			toggle.setAttribute( 'aria-controls', submenu.id );

			toggle.addEventListener( 'click', function () {
				const open = 'true' !== toggle.getAttribute( 'aria-expanded' );

				Array.prototype.forEach.call( item.parentElement.children, function ( sibling ) {
					if ( sibling !== item && sibling.classList.contains( 'is-submenu-open' ) ) {
						set_submenu_open( sibling, false );
					}
				} );

				set_submenu_open( item, open );
			} );

			var hover_enter = 'onpointerenter' in item ? 'pointerenter' : 'mouseenter';
			var hover_leave = 'onpointerleave' in item ? 'pointerleave' : 'mouseleave';

			item.addEventListener( hover_enter, function ( event ) {
				if ( ! hover_submenus || ! hover_event_from_pointer( event ) ) {
					return;
				}

				set_submenu_open( item, true );
			} );

			item.addEventListener( hover_leave, function ( event ) {
				if ( ! hover_submenus || ! hover_event_from_pointer( event ) ) {
					return;
				}

				if ( ! item.contains( document.activeElement ) ) {
					set_submenu_open( item, false );
				}
			} );
		} );

		menu.addEventListener( 'focusout', function () {
			window.setTimeout( function () {
				if ( ! menu.contains( document.activeElement ) ) {
					menu.querySelectorAll( '.is-submenu-open' ).forEach( function ( item ) {
						set_submenu_open( item, false );
					} );
				}
			}, 0 );
		} );
	} );

	document.addEventListener( 'click', function ( event ) {
		if ( event.target.closest( '.cbv-nav__list' ) ) {
			return;
		}

		document.querySelectorAll( '.cbv-nav__list .is-submenu-open' ).forEach( function ( item ) {
			set_submenu_open( item, false );
		} );
	} );

	document.addEventListener( 'keydown', function ( event ) {
		if ( 'Escape' !== event.key ) {
			return;
		}

		const open_item = document.activeElement?.closest( '.is-submenu-open' ) || document.querySelector( '.cbv-nav__list .is-submenu-open' );

		if ( open_item ) {
			const toggle = open_item.querySelector( '.cbv-nav__toggle' );

			set_submenu_open( open_item, false );
			toggle?.focus();
		}
	} );

	// Catalog grid/list view toggle, persisted in a cookie so the choice
	// survives navigation without any server round trip.
	const view_buttons = document.querySelectorAll( '.cbv-view-btn' );

	if ( view_buttons.length ) {
		const stored = ( document.cookie.match( /(?:^|;\s*)cbv_view=(grid|list)/ ) || [] )[ 1 ];

		if ( 'list' === stored ) {
			document.body.classList.add( 'cbv-view-list' );
		}

		view_buttons.forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				const view = button.dataset.view;

				document.body.classList.toggle( 'cbv-view-list', 'list' === view );
				document.cookie = 'cbv_view=' + view + ';path=/;max-age=31536000;samesite=lax';

				view_buttons.forEach( function ( other ) {
					const active = other === button;

					other.classList.toggle( 'is-active', active );
					other.setAttribute( 'aria-pressed', active ? 'true' : 'false' );
				} );
			} );
		} );
	}
} )();

/* Keep cfw_core's ERP ship-to contract intact across checkout updates. */
( function () {
	'use strict';

	const checkout = document.querySelector( 'form.woocommerce-checkout' );

	if ( ! checkout ) {
		return;
	}

	let selected_shipping_address = '';

	function shipping_address_select() {
		return checkout.querySelector( 'select[name="shipping_address_select"], select[id^="shipping_address_select_"]' );
	}

	function remember_shipping_address() {
		const address_select = shipping_address_select();

		if ( address_select?.value ) {
			selected_shipping_address = address_select.value;
		}
	}

	function preserve_shipping_address() {
		const checkbox = document.getElementById( 'ship-to-different-address-checkbox' );

		if ( ! checkbox ) {
			return;
		}

		checkbox.checked = true;

		const selected_method = checkout.querySelector( 'input[name^="shipping_method"]:checked' );

		if ( selected_method && selected_method.value.includes( 'local_pickup' ) ) {
			return;
		}

		const address_select = shipping_address_select();

		if ( address_select && ! address_select.value && selected_shipping_address ) {
			address_select.value = selected_shipping_address;
		}

		const shipping_address = checkout.querySelector( '.shipping_address' );

		if ( shipping_address ) {
			shipping_address.hidden = false;
			shipping_address.classList.remove( 'cfw-pickup-hidden' );
			shipping_address.style.removeProperty( 'display' );
		}

		document.getElementById( 'ship-to-different-address' )?.style.removeProperty( 'display' );
	}

	document.addEventListener( 'change', function ( event ) {
		if ( event.target.matches( 'select[name="shipping_address_select"], select[id^="shipping_address_select_"]' ) ) {
			remember_shipping_address();
			return;
		}

		if ( event.target.matches( 'input[name^="shipping_method"]' ) ) {
			remember_shipping_address();
			window.setTimeout( preserve_shipping_address, 0 );
		}
	}, true );

	if ( 'function' === typeof window.jQuery ) {
		window.jQuery( document.body ).on( 'updated_checkout cfw_updated_checkout', preserve_shipping_address );
	}

	remember_shipping_address();
	preserve_shipping_address();
} )();

/* Mobile filters drawer toggle. */
( function () {
	'use strict';

	const toggle = document.querySelector( '.cbv-filters-toggle' );
	const drawer = document.querySelector( '.cbv-catalog__sidebar--drawer' );
	const overlay = document.querySelector( '.cbv-filters-overlay' );
	const close_button = drawer?.querySelector( '.cbv-filters-close' );
	const mobile = window.matchMedia( '(max-width: 900px)' );
	const reduced_motion = window.matchMedia( '(prefers-reduced-motion: reduce)' );
	const focusable_selector = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
	let last_focus = null;
	let overlay_hide_timeout = null;

	if ( ! toggle || ! drawer || ! close_button ) {
		return;
	}

	function set_overlay_visible( visible ) {
		if ( ! overlay ) {
			return;
		}

		if ( null !== overlay_hide_timeout ) {
			window.clearTimeout( overlay_hide_timeout );
			overlay_hide_timeout = null;
		}

		if ( visible ) {
			overlay.hidden = false;

			window.requestAnimationFrame( function () {
				if ( drawer.classList.contains( 'is-open' ) ) {
					overlay.classList.add( 'is-visible' );
				}
			} );
			return;
		}

		overlay.classList.remove( 'is-visible' );

		if ( overlay.hidden || reduced_motion.matches ) {
			overlay.hidden = true;
			return;
		}

		overlay_hide_timeout = window.setTimeout( function () {
			if ( ! drawer.classList.contains( 'is-open' ) ) {
				overlay.hidden = true;
			}
			overlay_hide_timeout = null;
		}, 250 );
	}

	function set_open( open, restore_focus ) {
		open = Boolean( open && mobile.matches );

		drawer.classList.toggle( 'is-open', open );
		drawer.inert = ! open;
		document.body.classList.toggle( 'cbv-drawer-open', open );
		toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );

		if ( open ) {
			last_focus = document.activeElement;
			drawer.setAttribute( 'role', 'dialog' );
			drawer.setAttribute( 'aria-modal', 'true' );
			drawer.setAttribute( 'aria-hidden', 'false' );

			window.requestAnimationFrame( function () {
				close_button.focus();
			} );
		} else {
			drawer.removeAttribute( 'role' );
			drawer.removeAttribute( 'aria-modal' );
			drawer.setAttribute( 'aria-hidden', mobile.matches ? 'true' : 'false' );

			if ( restore_focus && last_focus && document.contains( last_focus ) ) {
				last_focus.focus();
			} else if ( restore_focus ) {
				toggle.focus();
			}
		}

		set_overlay_visible( open );
	}

	function sync_mode() {
		if ( mobile.matches ) {
			set_open( false, false );
			return;
		}

		drawer.classList.remove( 'is-open' );
		drawer.inert = false;
		drawer.removeAttribute( 'role' );
		drawer.removeAttribute( 'aria-modal' );
		drawer.removeAttribute( 'aria-hidden' );
		document.body.classList.remove( 'cbv-drawer-open' );
		toggle.setAttribute( 'aria-expanded', 'false' );

		if ( overlay ) {
			if ( null !== overlay_hide_timeout ) {
				window.clearTimeout( overlay_hide_timeout );
				overlay_hide_timeout = null;
			}
			overlay.classList.remove( 'is-visible' );
			overlay.hidden = true;
		}
	}

	toggle.addEventListener( 'click', function () {
		set_open( ! drawer.classList.contains( 'is-open' ), true );
	} );

	close_button.addEventListener( 'click', function () {
		set_open( false, true );
	} );

	overlay?.addEventListener( 'click', function () {
		set_open( false, true );
	} );

	document.addEventListener( 'keydown', function ( event ) {
		if ( ! drawer.classList.contains( 'is-open' ) ) {
			return;
		}

		if ( 'Escape' === event.key ) {
			event.preventDefault();
			set_open( false, true );
			return;
		}

		if ( 'Tab' !== event.key ) {
			return;
		}

		const focusable = Array.prototype.filter.call( drawer.querySelectorAll( focusable_selector ), function ( element ) {
			return null !== element.offsetParent;
		} );

		if ( ! focusable.length ) {
			event.preventDefault();
			close_button.focus();
			return;
		}

		const first = focusable[ 0 ];
		const last = focusable[ focusable.length - 1 ];

		if ( event.shiftKey && document.activeElement === first ) {
			event.preventDefault();
			last.focus();
		} else if ( ! event.shiftKey && document.activeElement === last ) {
			event.preventDefault();
			first.focus();
		}
	} );

	if ( mobile.addEventListener ) {
		mobile.addEventListener( 'change', sync_mode );
	} else {
		mobile.addListener( sync_mode );
	}

	sync_mode();
} )();

/* Commerce header mobile drawer. */
( function () {
	'use strict';

	const toggle = document.querySelector( '.cbv-header__menu-toggle' );
	const drawer = document.getElementById( 'cbv-mobile-nav' );

	if ( ! toggle || ! drawer ) {
		return;
	}

	function set_open( open ) {
		drawer.hidden = ! open;
		drawer.classList.toggle( 'is-open', open );
		toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		document.body.classList.toggle( 'cbv-mobile-nav-open', open );
	}

	toggle.addEventListener( 'click', function () {
		set_open( drawer.hidden );
	} );

	document.addEventListener( 'keydown', function ( event ) {
		if ( 'Escape' === event.key && ! drawer.hidden ) {
			set_open( false );
			toggle.focus();
		}
	} );
} )();

/* Classic cart qty steppers. */
( function () {
	'use strict';

	document.addEventListener( 'click', function ( event ) {
		const button = event.target.closest( '[data-cbv-qty]' );

		if ( ! button ) {
			return;
		}

		const wrap = button.closest( '.cbv-qty, .cbv-dash-qty' );
		const input = wrap ? wrap.querySelector( 'input.qty' ) : null;

		if ( ! input ) {
			return;
		}

		const step = parseInt( button.getAttribute( 'data-cbv-qty' ), 10 ) || 0;
		const min = '' === input.min ? 0 : parseInt( input.min, 10 );
		const max = '' === input.max ? NaN : parseInt( input.max, 10 );
		let next = ( parseInt( input.value, 10 ) || 0 ) + step;

		if ( ! isNaN( min ) ) {
			next = Math.max( min, next );
		}

		if ( ! isNaN( max ) ) {
			next = Math.min( max, next );
		}

		if ( String( next ) === String( input.value ) ) {
			return;
		}

		input.value = String( next );
		input.dispatchEvent( new Event( 'change', { bubbles: true } ) );

		const form = input.form;
		const submit = form ? form.querySelector( 'button[name="update_cart"]' ) : null;

		if ( submit ) {
			submit.disabled = false;
			submit.click();
		}
	} );
} )();
