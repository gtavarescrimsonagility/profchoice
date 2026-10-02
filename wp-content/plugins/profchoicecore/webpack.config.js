/**
 * The wp-scripts webpack config for one module (bin/build.mjs sets PC_MODULE_DIR
 * and passes the module's src/ and build/ folders): its blocks, plus the
 * non-block entries listed in the module's entries.json (`script` for
 * classic scripts, `module` for script modules), paths relative to the module.
 */
const fs = require( 'node:fs' );
const path = require( 'node:path' );
const [
	scriptConfig,
	moduleConfig,
] = require( '@wordpress/scripts/config/webpack.config' );

const moduleDir = process.env.PC_MODULE_DIR;
if ( ! moduleDir ) {
	throw new Error( 'Build modules with `npm run build` (bin/build.mjs).' );
}

const entriesFile = path.join( moduleDir, 'entries.json' );
const extra = fs.existsSync( entriesFile )
	? JSON.parse( fs.readFileSync( entriesFile, 'utf8' ) )
	: {};

const resolve = ( entries = {} ) =>
	Object.fromEntries(
		Object.entries( entries ).map( ( [ name, file ] ) => [
			name,
			path.resolve( moduleDir, file ),
		] )
	);

// Drop a config with nothing to build (e.g. a module without view modules).
const withEntries = ( config, entries ) => {
	const all = { ...config.entry(), ...resolve( entries ) };
	return Object.keys( all ).length ? { ...config, entry: () => all } : null;
};

module.exports = [
	withEntries( scriptConfig, extra.script ),
	withEntries( moduleConfig, extra.module ),
].filter( Boolean );
