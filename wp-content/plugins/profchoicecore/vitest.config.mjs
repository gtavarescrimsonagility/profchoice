import { fileURLToPath } from 'node:url';
import { defineConfig } from 'vitest/config';

export default defineConfig( {
	resolve: {
		alias: {
			// The Interactivity API only runs in the browser; tests use a stub.
			'@wordpress/interactivity': fileURLToPath(
				new URL(
					'./tests/js/__mocks__/wordpress-interactivity.ts',
					import.meta.url
				)
			),
		},
	},
	test: {
		environment: 'node',
		globals: false,
		restoreMocks: true,
		include: [ 'tests/js/**/*.test.ts' ],
		setupFiles: [ './tests/js/setup.ts' ],
	},
} );
