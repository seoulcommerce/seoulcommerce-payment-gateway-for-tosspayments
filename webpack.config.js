/**
 * Webpack configuration for WooCommerce TossPayments Blocks
 */

const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const WooCommerceDependencyExtractionWebpackPlugin = require( '@woocommerce/dependency-extraction-webpack-plugin' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		'blocks/frontend': path.resolve( __dirname, 'src/blocks/frontend.js' ),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'build' ),
	},
	plugins: [
		...defaultConfig.plugins.filter(
			( plugin ) =>
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
		),
		new WooCommerceDependencyExtractionWebpackPlugin( {
			requestToExternal( request ) {
				// WooCommerce blocks packages are provided by WooCommerce core
				if ( request.startsWith( '@woocommerce/' ) ) {
					return [ 'wc', request.replace( '@woocommerce/', '' ).replace( /-/g, '' ) ];
				}
			},
			requestToHandle( request ) {
				// Map to WooCommerce script handles
				if ( request === '@woocommerce/blocks-registry' ) {
					return 'wc-blocks-registry';
				}
				if ( request === '@woocommerce/settings' ) {
					return 'wc-settings';
				}
			},
		} ),
	],
};

