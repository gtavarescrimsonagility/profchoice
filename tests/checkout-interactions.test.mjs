import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import test from 'node:test';

const mainScript = readFileSync( new URL( '../assets/js/main.js', import.meta.url ), 'utf8' );

function checkoutFixture( methodValue ) {
	const listeners = new Map();
	const checkbox = { checked: false };
	const addressSelect = {
		value: 'default_shipping',
	};
	const checkboxContainer = {
		style: {
			removed: [],
			removeProperty( name ) {
				this.removed.push( name );
			},
		},
	};
	const shippingAddress = {
		classList: {
			removed: [],
			remove( name ) {
				this.removed.push( name );
			},
		},
		hidden: true,
		style: {
			removed: [],
			removeProperty( name ) {
				this.removed.push( name );
			},
		},
	};
	const selectedMethod = { value: methodValue };
	const checkout = {
		querySelector( selector ) {
			if ( 'input[name^="shipping_method"]:checked' === selector ) {
				return selectedMethod;
			}

			if ( '.shipping_address' === selector ) {
				return shippingAddress;
			}

			if ( 'select[name="shipping_address_select"], select[id^="shipping_address_select_"]' === selector ) {
				return addressSelect;
			}

			return null;
		},
	};
	const document = {
		body: {},
		addEventListener( type, listener ) {
			listeners.set( type, listener );
		},
		getElementById( id ) {
			if ( 'ship-to-different-address-checkbox' === id ) {
				return checkbox;
			}

			return 'ship-to-different-address' === id ? checkboxContainer : null;
		},
		querySelector( selector ) {
			return 'form.woocommerce-checkout' === selector ? checkout : null;
		},
	};
	const window = {
		setTimeout( callback ) {
			callback();
		},
	};
	const checkoutScript = mainScript.match( /\/\* Keep cfw_core's ERP ship-to contract intact across checkout updates\. \*\/[\s\S]*?\n} \)\(\);/ );

	assert.ok( checkoutScript, 'The checkout address guard must be available to exercise.' );
	runInNewContext( checkoutScript[ 0 ], { document, window } );

	return { addressSelect, checkbox, checkboxContainer, listeners, selectedMethod, shippingAddress };
}

test( 'a standard shipping method keeps the ERP ship-to fields active', () => {
	const { checkbox, shippingAddress } = checkoutFixture( 'flat_rate:2' );

	assert.equal( checkbox.checked, true );
	assert.equal( shippingAddress.hidden, false );
	assert.deepEqual( shippingAddress.classList.removed, [ 'cfw-pickup-hidden' ] );
	assert.deepEqual( shippingAddress.style.removed, [ 'display' ] );
} );

test( 'leaving pickup restores the ERP ship-to selection after the change event', () => {
	const { addressSelect, checkbox, listeners, selectedMethod, shippingAddress } = checkoutFixture( 'local_pickup:1' );

	assert.equal( checkbox.checked, true );
	assert.equal( shippingAddress.hidden, true );
	selectedMethod.value = 'flat_rate:2';
	checkbox.checked = false;
	addressSelect.value = '';
	listeners.get( 'change' )( {
		target: {
			matches: ( selector ) => selector.startsWith( 'input' ),
		},
	} );

	assert.equal( checkbox.checked, true );
	assert.equal( shippingAddress.hidden, false );
	assert.equal( addressSelect.value, 'default_shipping' );
} );
