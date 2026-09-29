import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const script = readFileSync( new URL( '../blocks/account-tabs/view.js', import.meta.url ), 'utf8' );

function classList( classes ) {
	const values = new Set( classes );

	return {
		contains: ( value ) => values.has( value ),
		toggle: ( value, active ) => active ? values.add( value ) : values.delete( value ),
	};
}

function tab( slug, offsetLeft = 0, offsetWidth = 120 ) {
	const listeners = {};
	const attributes = new Map( [
		[ 'aria-controls', `cbv-apanel-${ slug }` ],
		[ 'aria-selected', 'false' ],
	] );

	return {
		id: `cbv-atab-${ slug }`,
		dataset: { tab: slug },
		offsetLeft,
		offsetWidth,
		classList: classList( [ 'cbv-atabs__tab' ] ),
		addEventListener: ( type, listener ) => { listeners[ type ] = listener; },
		getAttribute: ( name ) => attributes.get( name ) || null,
		setAttribute: ( name, value ) => attributes.set( name, value ),
		click: () => listeners.click(),
		focus: () => {},
		keydown: ( event ) => listeners.keydown( event ),
	};
}

function panel( slug ) {
	const attributes = new Set( [ 'hidden' ] );

	return {
		id: `cbv-apanel-${ slug }`,
		classList: classList( [ 'cbv-atabs__panel' ] ),
		hasAttribute: ( name ) => attributes.has( name ),
		removeAttribute: ( name ) => attributes.delete( name ),
		setAttribute: ( name ) => attributes.add( name ),
	};
}

test( 'account tabs restore and activate a controlled panel displaced by rendered markup', () => {
	const summaryTab = tab( 'summary' );
	const accountTab = tab( 'account' );
	const summaryPanel = panel( 'summary' );
	const accountPanel = panel( 'account' );
	const children = [ summaryPanel ];
	const root = {
		querySelectorAll: ( selector ) => '.cbv-atabs__tab' === selector ? [ summaryTab, accountTab ] : children,
		querySelector: () => null,
		contains: ( element ) => children.includes( element ),
		appendChild: ( element ) => children.push( element ),
	};
	const elements = new Map( [
		[ summaryPanel.id, summaryPanel ],
		[ accountPanel.id, accountPanel ],
	] );
	const document = {
		querySelectorAll: () => [ root ],
		getElementById: ( id ) => elements.get( id ) || null,
	};

	vm.runInNewContext( script, {
		document,
		window: { location: { hash: '' }, history: { replaceState: () => {} } },
	} );

	assert.equal( root.contains( accountPanel ), true );

	accountTab.click();

	assert.equal( accountTab.getAttribute( 'aria-selected' ), 'true' );
	assert.equal( accountPanel.hasAttribute( 'hidden' ), false );
	assert.equal( summaryPanel.hasAttribute( 'hidden' ), true );
} );

test( 'account tabs keep the hash-selected tab visible without scrolling the page', () => {
	const summaryTab = tab( 'summary', 0, 112 );
	const ordersTab = tab( 'orders', 116, 128 );
	const accountTab = tab( 'account', 612, 176 );
	const summaryPanel = panel( 'summary' );
	const ordersPanel = panel( 'orders' );
	const accountPanel = panel( 'account' );
	const children = [ summaryPanel, ordersPanel, accountPanel ];
	const scrollCalls = [];
	const nav = {
		clientWidth: 360,
		scrollLeft: 0,
		scrollWidth: 812,
		scrollTo: ( options ) => {
			scrollCalls.push( options );
			nav.scrollLeft = options.left;
		},
	};
	const root = {
		querySelectorAll: ( selector ) => '.cbv-atabs__tab' === selector ? [ summaryTab, ordersTab, accountTab ] : children,
		querySelector: ( selector ) => '.cbv-atabs__nav' === selector ? nav : null,
		contains: ( element ) => children.includes( element ),
		appendChild: ( element ) => children.push( element ),
	};
	const elements = new Map( children.map( ( item ) => [ item.id, item ] ) );
	const document = {
		querySelectorAll: () => [ root ],
		getElementById: ( id ) => elements.get( id ) || null,
	};
	let historyValue = '';

	vm.runInNewContext( script, {
		document,
		window: {
			location: { hash: '#tab-account' },
			history: { replaceState: ( state, title, hash ) => { historyValue = hash; } },
		},
	} );

	assert.equal( accountTab.getAttribute( 'aria-selected' ), 'true' );
	assert.equal( accountPanel.hasAttribute( 'hidden' ), false );
	assert.equal( scrollCalls.length, 1 );
	assert.equal( scrollCalls[ 0 ].left, 444 );
	assert.equal( scrollCalls[ 0 ].behavior, 'auto' );
	assert.equal( historyValue, '' );

	accountTab.click();

	assert.equal( historyValue, '#tab-account' );
	assert.equal( scrollCalls.length, 1 );
} );
