<?php
/**
 * PHPUnit bootstrap file.
 */

// Load composer autoloader.
require_once __DIR__ . '/../vendor/autoload.php';

// Define WordPress constants for testing.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
}

if ( ! defined( 'SEOULCOMMERCE_TPG_VERSION' ) ) {
	define( 'SEOULCOMMERCE_TPG_VERSION', '1.0.3' );
}

if ( ! defined( 'SEOULCOMMERCE_TPG_PLUGIN_DIR' ) ) {
	define( 'SEOULCOMMERCE_TPG_PLUGIN_DIR', __DIR__ . '/../' );
}

if ( ! defined( 'SEOULCOMMERCE_TPG_PLUGIN_URL' ) ) {
	define( 'SEOULCOMMERCE_TPG_PLUGIN_URL', 'http://example.com/wp-content/plugins/tosspayments/' );
}
