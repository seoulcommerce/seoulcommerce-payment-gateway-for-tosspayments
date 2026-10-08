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

// Define global test state.
global $wp_test_mocks;
$wp_test_mocks = array(
	'wc_create_refund_calls' => 0,
	'order_meta' => array(),
);

// Mock WordPress/WooCommerce functions.
function __( $text, $domain = 'default' ) {
	return $text;
}

function esc_html__( $text, $domain = 'default' ) {
	return $text;
}

function esc_attr__( $text, $domain = 'default' ) {
	return $text;
}

function absint( $maybeint ) {
	return intval( $maybeint );
}

function sanitize_text_field( $str ) {
	return $str;
}

function wp_unslash( $value ) {
	return $value;
}

function get_current_user_id() {
	return 1;
}

function is_user_logged_in() {
	return true;
}

function get_option( $option, $default = false ) {
	return $default;
}

function update_option( $option, $value, $autoload = null ) {
	return true;
}

function wp_json_encode( $data, $options = 0, $depth = 512 ) {
	return json_encode( $data, $options, $depth );
}

function add_query_arg( $args, $url = '' ) {
	return 'http://example.com/return';
}

function home_url( $path = '', $scheme = null ) {
	return 'http://example.com' . $path;
}

function wc_price( $amount, $args = array() ) {
	return '₩' . number_format( $amount );
}

function is_wp_error( $thing ) {
	return ( $thing instanceof WP_Error );
}

function wc_get_order( $order_id ) {
	// Returns mock - will be overridden in tests.
	return null;
}

function wc_create_refund( $args = array() ) {
	global $wp_test_mocks;
	$wp_test_mocks['wc_create_refund_calls']++;
	$refund = Mockery::mock( 'WC_Order_Refund' );
	$refund->shouldReceive( 'get_id' )->andReturn( 999 );
	return $refund;
}

// Mock WP_Error class.
if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		private $code;
		private $message;
		private $data;

		public function __construct( $code = '', $message = '', $data = '' ) {
			$this->code = $code;
			$this->message = $message;
			$this->data = $data;
		}

		public function get_error_code() {
			return $this->code;
		}

		public function get_error_message( $code = '' ) {
			return $this->message;
		}

		public function get_error_data( $code = '' ) {
			return $this->data;
		}
	}
}

// Mock WC_Payment_Gateway class.
if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
	class WC_Payment_Gateway {
		public $id;
		public $method_title;
		public $method_description;
		public $has_fields = false;
		public $supports = array();
		protected $form_fields = array();
		protected $settings = array();

		public function init_form_fields() {}
		public function init_settings() {}
		public function get_option( $key, $default = '' ) {
			return isset( $this->settings[ $key ] ) ? $this->settings[ $key ] : $default;
		}
		public function is_available() { return true; }
		public function process_payment( $order_id ) { return array(); }
	}
}
