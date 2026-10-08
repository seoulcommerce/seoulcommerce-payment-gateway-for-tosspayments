<?php
/**
 * PHPUnit bootstrap file with fake WordPress/WooCommerce classes and functions.
 */

// Load composer autoloader.
require_once __DIR__ . '/../vendor/autoload.php';

// Define WordPress constants.
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

// Initialize test globals.
$GLOBALS['__test_orders'] = array();
$GLOBALS['__test_http_queue'] = array();
$GLOBALS['__test_http_log'] = array();
$GLOBALS['__test_refund_calls'] = array();
$GLOBALS['__test_logger_messages'] = array();

/**
 * FakeOrder class for testing.
 */
class FakeOrder {
	public $id;
	public $total;
	public $transaction_id;
	public $currency = 'KRW';
	public $meta = array();
	public $notes = array();
	public $status = 'processing';
	public $payment_method = 'tosspayments';
	public $total_refunded = 0;

	public function __construct( $id, $total, $transaction_id = '' ) {
		$this->id = $id;
		$this->total = $total;
		$this->transaction_id = $transaction_id;
	}

	public function get_id() {
		return $this->id;
	}

	public function get_total() {
		return $this->total;
	}

	public function get_transaction_id() {
		return $this->transaction_id;
	}

	public function get_currency() {
		return $this->currency;
	}

	public function get_payment_method() {
		return $this->payment_method;
	}

	public function get_total_refunded() {
		return $this->total_refunded;
	}

	public function get_meta( $key, $single = true ) {
		return isset( $this->meta[ $key ] ) ? $this->meta[ $key ] : ( $single ? '' : array() );
	}

	public function update_meta_data( $key, $value ) {
		$this->meta[ $key ] = $value;
	}

	public function add_order_note( $note ) {
		$this->notes[] = $note;
		return count( $this->notes );
	}

	public function update_status( $status, $note = '' ) {
		$this->status = $status;
		if ( $note ) {
			$this->add_order_note( $note );
		}
		return true;
	}

	public function has_status( $statuses ) {
		$statuses = is_array( $statuses ) ? $statuses : array( $statuses );
		return in_array( $this->status, $statuses, true );
	}

	public function save() {
		return true;
	}
}

// WordPress/WooCommerce function fakes.
if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( $text, $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( 'absint' ) ) {
	function absint( $maybeint ) {
		return abs( intval( $maybeint ) );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		return trim( strip_tags( $str ) );
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) {
		return stripslashes_deep( $value );
	}
}

if ( ! function_exists( 'stripslashes_deep' ) ) {
	function stripslashes_deep( $value ) {
		return is_array( $value ) ? array_map( 'stripslashes_deep', $value ) : stripslashes( $value );
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $option, $default = false ) {
		return $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( $option, $value, $autoload = null ) {
		return true;
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, $options = 0, $depth = 512 ) {
		return json_encode( $data, $options, $depth );
	}
}

if ( ! function_exists( 'add_query_arg' ) ) {
	function add_query_arg( $args, $url = '' ) {
		return 'http://example.com/return';
	}
}

if ( ! function_exists( 'home_url' ) ) {
	function home_url( $path = '', $scheme = null ) {
		return 'http://example.com' . $path;
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url, $protocols = null, $_context = 'display' ) {
		return $url;
	}
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		return true;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $hook, $value ) {
		return $value;
	}
}

if ( ! function_exists( 'wc_price' ) ) {
	function wc_price( $amount, $args = array() ) {
		return '₩' . number_format( $amount );
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}

if ( ! function_exists( 'wc_get_order' ) ) {
	function wc_get_order( $order_id ) {
		return isset( $GLOBALS['__test_orders'][ $order_id ] ) ? $GLOBALS['__test_orders'][ $order_id ] : false;
	}
}

if ( ! function_exists( 'wc_create_refund' ) ) {
	function wc_create_refund( $args = array() ) {
		$GLOBALS['__test_refund_calls'][] = $args;
		// Return a fake refund object with get_id method.
		return new class {
			public function get_id() {
				return 999;
			}
		};
	}
}

if ( ! function_exists( 'wc_get_orders' ) ) {
	function wc_get_orders( $args = array() ) {
		$return = isset( $args['return'] ) ? $args['return'] : 'objects';
		$orders = array_values( $GLOBALS['__test_orders'] );
		
		// Filter by meta if specified.
		if ( isset( $args['meta_key'] ) && isset( $args['meta_value'] ) ) {
			$orders = array_filter( $orders, function( $order ) use ( $args ) {
				return $order->get_meta( $args['meta_key'] ) === $args['meta_value'] || $order->get_transaction_id() === $args['meta_value'];
			});
		}
		
		// Limit results.
		if ( isset( $args['limit'] ) ) {
			$orders = array_slice( $orders, 0, $args['limit'] );
		}
		
		// Return format.
		if ( 'ids' === $return ) {
			return array_map( function( $order ) {
				return $order->get_id();
			}, $orders );
		}
		
		return $orders;
	}
}

if ( ! function_exists( 'wp_remote_request' ) ) {
	function wp_remote_request( $url, $args = array() ) {
		$GLOBALS['__test_http_log'][] = array(
			'url'     => $url,
			'method'  => isset( $args['method'] ) ? $args['method'] : 'GET',
			'headers' => isset( $args['headers'] ) ? $args['headers'] : array(),
			'body'    => isset( $args['body'] ) ? $args['body'] : '',
		);
		return array_shift( $GLOBALS['__test_http_queue'] );
	}
}

if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
	function wp_remote_retrieve_response_code( $response ) {
		return isset( $response['response']['code'] ) ? $response['response']['code'] : 200;
	}
}

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
	function wp_remote_retrieve_body( $response ) {
		return isset( $response['body'] ) ? $response['body'] : '';
	}
}

if ( ! function_exists( 'wc_get_logger' ) ) {
	function wc_get_logger() {
		return new class {
			public function debug( $message, $context = array() ) {
				$GLOBALS['__test_logger_messages'][] = $message;
			}
		};
	}
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
		public function is_available() {
			return true;
		}
		public function process_payment( $order_id ) {
			return array();
		}
	}
}

// Mock WC_Log_Handler_File class.
if ( ! class_exists( 'WC_Log_Handler_File' ) ) {
	class WC_Log_Handler_File {
		public static function get_log_file_path( $handle ) {
			return '/tmp/wc-logs/' . $handle . '.log';
		}
	}
}
