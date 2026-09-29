import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import test from 'node:test';

const mainScript = readFileSync( new URL( '../assets/js/main.js', import.meta.url ), 'utf8' );

class FakeClassList {
	constructor( names = [] ) {
		this.names = new Set( names );
	}

	add( name ) {
		this.names.add( name );
	}

	remove( name ) {
		this.names.delete( name );
	}

	contains( name ) {
		return this.names.has( name );
	}

	toggle( name, force ) {
		if ( force ) {
			this.add( name );
			return true;
		}

		this.remove( name );
		return false;
	}
}

class FakeElement {
	constructor( classes = [] ) {
		this.attributes = new Map();
		this.children = [];
		this.classList = new FakeClassList( classes );
		this.id = '';
		this.listeners = new Map();
		this.parentElement = null;
	}

	append( ...children ) {
		children.forEach( ( child ) => {
			child.parentElement = this;
			this.children.push( child );
		} );
	}

	addEventListener( type, listener ) {
		const listeners = this.listeners.get( type ) || [];
		listeners.push( listener );
		this.listeners.set( type, listeners );
	}

	dispatch( type, event = {} ) {
		( this.listeners.get( type ) || [] ).forEach( ( listener ) => listener( {
			target: this,
			...event,
		} ) );
	}

	setAttribute( name, value ) {
		this.attributes.set( name, value );
	}

	getAttribute( name ) {
		return this.attributes.get( name ) || null;
	}

	descendants() {
		return this.children.flatMap( ( child ) => [ child, ...child.descendants() ] );
	}

	querySelectorAll( selector ) {
		if ( selector.startsWith( '.' ) ) {
			const className = selector.slice( 1 );
			return this.descendants().filter( ( child ) => child.classList.contains( className ) );
		}

		return [];
	}

	querySelector( selector ) {
		return this.querySelectorAll( selector )[ 0 ] || null;
	}

	contains( candidate ) {
		return this === candidate || this.descendants().includes( candidate );
	}

	closest( selector ) {
		let candidate = this;

		while ( candidate ) {
			if ( selector.startsWith( '#' ) && candidate.id === selector.slice( 1 ) ) {
				return candidate;
			}

			candidate = candidate.parentElement;
		}

		return null;
	}
}

function navigationFixture( { mobileDrawer = false } = {} ) {
	const menu = new FakeElement( [ 'cbv-nav__list' ] );
	const item = new FakeElement();
	const toggle = new FakeElement( [ 'cbv-nav__toggle' ] );
	const submenu = new FakeElement( [ 'sub-menu' ] );

	item.onpointerenter = null;
	item.onpointerleave = null;
	item.append( toggle, submenu );
	menu.append( item );
	toggle.setAttribute( 'aria-expanded', 'false' );
	if ( mobileDrawer ) {
		const drawer = new FakeElement();
		drawer.id = 'cbv-mobile-nav';
		drawer.append( menu );
	}

	const documentListeners = new Map();
	const document = {
		activeElement: null,
		body: { classList: new FakeClassList() },
		cookie: '',
		documentElement: { classList: new FakeClassList() },
		addEventListener( type, listener ) {
			documentListeners.set( type, listener );
		},
		querySelectorAll( selector ) {
			if ( '.cbv-nav__list' === selector ) {
				return [ menu ];
			}

			if ( '.cbv-nav__list .is-submenu-open' === selector ) {
				return item.classList.contains( 'is-submenu-open' ) ? [ item ] : [];
			}

			return [];
		},
		querySelector( selector ) {
			return this.querySelectorAll( selector )[ 0 ] || null;
		},
	};
	const window = {
		matchMedia: () => ( { matches: true } ),
		setTimeout: ( callback ) => callback(),
	};
	const navigationScript = mainScript.match( /\( function \(\) \{[\s\S]*?\n} \)\(\);/ );

	assert.ok( navigationScript, 'The navigation module must be available to exercise.' );
	runInNewContext( navigationScript[ 0 ], { document, window } );

	return { item, toggle };
}

test( 'a touch tap stays open on a hybrid hover-capable device', () => {
	const { item, toggle } = navigationFixture();

	item.dispatch( 'pointerenter', { pointerType: 'touch' } );
	item.dispatch( 'mouseenter' );
	assert.equal( toggle.getAttribute( 'aria-expanded' ), 'false' );

	toggle.dispatch( 'click' );
	assert.equal( toggle.getAttribute( 'aria-expanded' ), 'true' );
	assert.equal( item.classList.contains( 'is-submenu-open' ), true );
} );

test( 'a non-hover pen tap stays open through activation', () => {
	const { item, toggle } = navigationFixture();

	// pointerType names the device class, not its hover capability. A pen that
	// does not support hover still dispatches pointerenter as part of
	// activation, so treating every pen as hover-capable opens the submenu here
	// and the activation click then closes it — the same one-tap-ending-closed
	// defect this guard exists to fix.
	// https://www.w3.org/TR/pointerevents/#mapping-for-devices-that-do-not-support-hover
	item.dispatch( 'pointerenter', { pointerType: 'pen' } );
	assert.equal( toggle.getAttribute( 'aria-expanded' ), 'false' );

	toggle.dispatch( 'click' );
	assert.equal( toggle.getAttribute( 'aria-expanded' ), 'true' );
	assert.equal( item.classList.contains( 'is-submenu-open' ), true );
} );

test( 'mouse hover continues to open and close the submenu', () => {
	const { item, toggle } = navigationFixture();

	item.dispatch( 'pointerenter', { pointerType: 'mouse' } );
	assert.equal( toggle.getAttribute( 'aria-expanded' ), 'true' );

	item.dispatch( 'pointerleave', { pointerType: 'mouse' } );
	assert.equal( toggle.getAttribute( 'aria-expanded' ), 'false' );
} );

test( 'drawer submenus wait for explicit mouse activation', () => {
	const { item, toggle } = navigationFixture( { mobileDrawer: true } );

	item.dispatch( 'pointerenter', { pointerType: 'mouse' } );
	assert.equal( toggle.getAttribute( 'aria-expanded' ), 'false' );

	toggle.dispatch( 'click' );
	assert.equal( toggle.getAttribute( 'aria-expanded' ), 'true' );
	assert.equal( item.classList.contains( 'is-submenu-open' ), true );
} );
