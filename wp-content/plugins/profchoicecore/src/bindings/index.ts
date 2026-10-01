/**
 * Editor side of the WooCommerce block bindings (includes/Bindings.php): the
 * bound blocks show the store's values while editing, read once from
 * GET /profchoicecore/v1/bindings. A binding without data keeps the block's
 * own value.
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { registerBlockBindingsSource } from '@wordpress/blocks';
import { createReduxStore, register } from '@wordpress/data';

type Item = Record< string, string | number | undefined >;

type Data = {
	categories: Record< string, Item | null >;
	featured: Item[];
	shop: string | null;
};

type Binding = { args?: Record< string, unknown > };

type SourceContext = {
	select: ( store: unknown ) => {
		getData: () => Data | null;
		getBlockAttributes: ( clientId: string ) => Record< string, unknown >;
	};
	bindings: Record< string, Binding >;
	clientId: string;
};

const STORE = 'profchoicecore/bindings';

const store = createReduxStore( STORE, {
	reducer: (
		state: Data | null = null,
		action: { type: string; data?: Data }
	) => ( action.type === 'SET' ? ( action.data ?? null ) : state ),
	actions: {
		set: ( data: Data ) => ( { type: 'SET', data } ),
	},
	selectors: {
		getData: ( state: Data | null ) => state,
	},
	resolvers: {
		getData:
			() =>
			async ( {
				dispatch,
			}: {
				dispatch: { set: ( data: Data ) => void };
			} ) => {
				const data = await apiFetch< Data >( {
					path: '/profchoicecore/v1/bindings',
				} );
				dispatch.set( data );
			},
	},
} );

register( store );

/**
 * Plain, comparable text: no tags, line breaks as spaces.
 *
 * @param text Text or HTML.
 * @return Lowercase plain text.
 */
const plain = ( text: unknown ): string =>
	String( text ?? '' )
		.replace( /<br\s*\/?>/gi, ' ' )
		.replace( /<[^>]+>/g, '' )
		.replace( /\s+/g, ' ' )
		.trim()
		.toLowerCase();

/**
 * Turns a source's lookup into getValues: each bound attribute gets the
 * looked-up item's `key`, or nothing (the saved value stays).
 *
 * @param lookup Finds the item for a binding's args.
 * @return The source's getValues.
 */
const values =
	(
		lookup: ( data: Data, args: Record< string, unknown > ) => Item | null
	) =>
	( { select, bindings, clientId }: SourceContext ) => {
		const data = select( store ).getData();
		const saved =
			select( 'core/block-editor' ).getBlockAttributes( clientId );
		const result: Record< string, unknown > = {};
		for ( const [ attribute, { args = {} } ] of Object.entries(
			bindings
		) ) {
			const item = data ? lookup( data, args ) : null;
			const value = item?.[ String( args.key ) ];
			if ( value === undefined || value === null || value === '' ) {
				continue;
			}
			// Same name as saved: keep its designed line breaks (as on the
			// front end, see Bindings::keep_breaks()).
			result[ attribute ] =
				args.key === 'name' &&
				plain( saved?.[ attribute ] ) === plain( value )
					? String( saved[ attribute ] )
					: value;
		}
		return result;
	};

registerBlockBindingsSource( {
	name: 'profchoice/product-category',
	label: __( 'Product category', 'profchoicecore' ),
	getValues: values(
		( data, args ) => data.categories[ String( args.slug ) ] ?? null
	),
	canUserEditValue: () => false,
} as never );

registerBlockBindingsSource( {
	name: 'profchoice/product',
	label: __( 'Product', 'profchoicecore' ),
	getValues: values( ( data, args ) =>
		args.id
			? ( data.featured.find(
					( item ) => item.id === Number( args.id )
				) ?? null )
			: ( data.featured[
					Math.max( 1, Number( args.featured ) || 1 ) - 1
				] ?? null )
	),
	canUserEditValue: () => false,
} as never );

registerBlockBindingsSource( {
	name: 'profchoice/shop',
	label: __( 'Shop', 'profchoicecore' ),
	getValues: values( ( data ) => ( data.shop ? { url: data.shop } : null ) ),
	canUserEditValue: () => false,
} as never );
