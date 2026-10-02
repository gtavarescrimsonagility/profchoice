#!/usr/bin/env node
/**
 * Builds every module that has sources: wp-scripts per module, from
 * modules/<name>/src into modules/<name>/build (with its blocks manifest).
 *
 * Usage: node bin/build.mjs [--watch] [module ...]
 */
import { spawn } from 'node:child_process';
import { existsSync, readdirSync } from 'node:fs';
import { join, resolve } from 'node:path';

const args = process.argv.slice( 2 );
const watch = args.includes( '--watch' );
const only = args.filter( ( arg ) => ! arg.startsWith( '--' ) );

const hasBlocks = ( dir ) =>
	readdirSync( dir, { withFileTypes: true } ).some( ( entry ) =>
		entry.isDirectory()
			? hasBlocks( join( dir, entry.name ) )
			: entry.name === 'block.json'
	);

const modules = readdirSync( 'modules', { withFileTypes: true } )
	.filter( ( entry ) => entry.isDirectory() )
	.map( ( entry ) => entry.name )
	.filter( ( name ) => existsSync( join( 'modules', name, 'src' ) ) )
	.filter( ( name ) => ! only.length || only.includes( name ) );

const run = ( name ) =>
	new Promise( ( done, fail ) => {
		const src = join( 'modules', name, 'src' );
		const command = [
			'wp-scripts',
			watch ? 'start' : 'build',
			'--experimental-modules',
			...( hasBlocks( src ) ? [ '--blocks-manifest' ] : [] ),
			`--webpack-src-dir=${ src }`,
			`--output-path=${ join( 'modules', name, 'build' ) }`,
		];
		// eslint-disable-next-line no-console
		console.log( `\n▶ ${ name }` );
		const child = spawn( 'npx', command, {
			stdio: 'inherit',
			env: { ...process.env, PC_MODULE_DIR: resolve( 'modules', name ) },
		} );
		child.on( 'exit', ( code ) =>
			code ? fail( new Error( `${ name } failed` ) ) : done()
		);
	} );

if ( watch ) {
	await Promise.all( modules.map( run ) );
} else {
	for ( const name of modules ) {
		await run( name );
	}
}
