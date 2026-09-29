/**
 * Hydrates deferred product-page sections when they approach the viewport.
 *
 * All pending fragments are fetched in one batched REST request. Transient
 * failures retry automatically; exhausted requests expose a manual recovery
 * action instead of leaving an unexplained blank or permanent skeleton.
 */
( function () {
	'use strict';

	const config = window.cbvDeferred || {};
	const nodes = Array.prototype.slice.call( document.querySelectorAll( '.cbv-deferred[data-cbv-element]' ) );

	if ( ! nodes.length || ! config.endpoint ) {
		return;
	}

	let maxRetries = parseInt( config.retries, 10 );

	if ( isNaN( maxRetries ) || maxRetries < 0 ) {
		maxRetries = 3;
	}

	const maxAttempts = 1 + maxRetries;
	let inFlight = false;
	let done = false;
	let attempts = 0;
	let retryTimer = null;
	let focusAfterRetry = false;
	let retryNode = null;

	function set_loading( node ) {
		const error = node.querySelector( '.cbv-deferred__error' );
		const retry = node.querySelector( '.cbv-deferred__retry' );

		node.classList.remove( 'cbv-deferred--failed' );
		node.setAttribute( 'aria-busy', 'true' );
		node.style.minHeight = ( parseInt( node.dataset.cbvMinHeight, 10 ) || 300 ) + 'px';

		if ( error ) {
			error.hidden = true;
		}

		if ( retry ) {
			retry.disabled = true;
			retry.textContent = retry.dataset.busyLabel;
		}
	}

	function set_failed( node ) {
		const error = node.querySelector( '.cbv-deferred__error' );
		const retry = node.querySelector( '.cbv-deferred__retry' );

		node.classList.add( 'cbv-deferred--failed' );
		node.removeAttribute( 'aria-busy' );
		node.style.minHeight = '';

		if ( error ) {
			error.hidden = false;
		}

		if ( retry ) {
			retry.disabled = false;
			retry.textContent = retry.dataset.defaultLabel;
		}
	}

	function hydrate() {
		if ( inFlight || done ) {
			return;
		}

		inFlight = true;
		attempts += 1;

		const productId = parseInt( nodes[ 0 ].getAttribute( 'data-cbv-product' ), 10 );
		const elements = nodes.map( function ( node ) {
			return node.getAttribute( 'data-cbv-element' );
		} );
		const headers = { 'Content-Type': 'application/json' };

		if ( config.nonce ) {
			headers[ 'X-WP-Nonce' ] = config.nonce;
		}

		window.fetch( config.endpoint, {
			method: 'POST',
			credentials: 'same-origin',
			headers: headers,
			body: JSON.stringify( { product_id: productId, elements: elements } )
		} )
			.then( function ( response ) {
				if ( ! response.ok ) {
					throw new Error( 'fragment request failed: ' + response.status );
				}

				return response.json();
			} )
			.then( function ( data ) {
				done = true;
				inFlight = false;

				const fragments = ( data && data.fragments ) || {};
				let focusTarget = null;

				nodes.forEach( function ( node ) {
					const id = node.getAttribute( 'data-cbv-element' );
					const html = fragments[ id ];

					if ( 'string' === typeof html && '' !== html.trim() ) {
						const wrap = document.createElement( 'div' );
						wrap.innerHTML = html;

						while ( wrap.firstChild ) {
							const child = wrap.firstChild;

							node.parentNode.insertBefore( child, node );

							if ( focusAfterRetry && node === retryNode && ! focusTarget && 1 === child.nodeType ) {
								focusTarget = child;
							}
						}
					}

					node.remove();

					document.dispatchEvent( new CustomEvent( 'cbv:fragment:loaded', {
						detail: { element: id, hydrated: 'string' === typeof html && '' !== html.trim() }
					} ) );
				} );

				if ( focusAfterRetry && focusTarget ) {
					focusTarget.setAttribute( 'tabindex', '-1' );
					focusTarget.focus();
					focusTarget.addEventListener( 'blur', function () {
						focusTarget.removeAttribute( 'tabindex' );
					}, { once: true } );
				}

				focusAfterRetry = false;
				retryNode = null;

				// Let cfw_core / WooCommerce scripts re-scan injected cards.
				if ( window.jQuery ) {
					window.jQuery( document.body ).trigger( 'updated_wc_div' );
				}
			} )
			.catch( function () {
				inFlight = false;

				if ( attempts < maxAttempts ) {
					retryTimer = window.setTimeout( hydrate, 1000 * Math.pow( 2, attempts - 1 ) );
					return;
				}

				done = true;
				nodes.forEach( set_failed );

				if ( focusAfterRetry && retryNode ) {
					retryNode.removeAttribute( 'tabindex' );
					retryNode.querySelector( '.cbv-deferred__retry' )?.focus();
				}

				focusAfterRetry = false;
				retryNode = null;
			} );
	}

	nodes.forEach( function ( node ) {
		const retry = node.querySelector( '.cbv-deferred__retry' );

		if ( ! retry ) {
			return;
		}

		retry.addEventListener( 'click', function () {
			if ( retryTimer ) {
				window.clearTimeout( retryTimer );
				retryTimer = null;
			}

			attempts = 0;
			done = false;
			focusAfterRetry = true;
			retryNode = node;
			nodes.forEach( set_loading );
			node.setAttribute( 'tabindex', '-1' );
			node.focus();
			hydrate();
		} );
	} );

	if ( 'IntersectionObserver' in window ) {
		const observer = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						observer.disconnect();
						hydrate();
					}
				} );
			},
			{ rootMargin: config.margin || '400px 0px' }
		);

		nodes.forEach( function ( node ) {
			observer.observe( node );
		} );
	} else {
		hydrate();
	}

	window.addEventListener( 'pagehide', function () {
		if ( retryTimer ) {
			window.clearTimeout( retryTimer );
		}
	} );
} )();
