/**
 * Default @wordpress/scripts config plus entries that are not blocks: the
 * core/cover extension (editor script and view script module) and the inline
 * icon and visually hidden formats.
 */
const [
	scriptConfig,
	moduleConfig,
] = require( '@wordpress/scripts/config/webpack.config' );

const withEntries = ( config, extra ) => ( {
	...config,
	entry: () => ( { ...config.entry(), ...extra } ),
} );

module.exports = [
	withEntries( scriptConfig, {
		'cover-extension/index': './src/cover-extension/index.tsx',
		'inline-icon/index': './src/inline-icon/index.tsx',
		'visually-hidden/index': './src/visually-hidden/index.tsx',
	} ),
	withEntries( moduleConfig, {
		'cover-extension/view': './src/cover-extension/view.ts',
	} ),
];
