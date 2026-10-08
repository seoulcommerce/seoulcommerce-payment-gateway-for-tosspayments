<?php
/**
 * TossPayments API Handler.
 *
 * @package WooCommerce_TossPayments
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * SeoulCommerce_TPG_API class.
 */
class SeoulCommerce_TPG_API {

	/**
	 * Gateway instance.
	 *
	 * @var SeoulCommerce_TPG_Gateway
	 */
	private $gateway;

	/**
	 * API base URL.
	 *
	 * @var string
	 */
	private $api_url = 'https://api.tosspayments.com/v1';

	/**
	 * Constructor.
	 *
	 * @param SeoulCommerce_TPG_Gateway $gateway Gateway instance.
	 */
	public function __construct( $gateway ) {
		$this->gateway = $gateway;
	}

	/**
	 * Get authorization header.
	 *
	 * @return string
	 */
	private function get_auth_header() {
		$secret_key = $this->gateway->get_secret_key();
		// Secret key with colon for Basic auth.
		$auth_string = $secret_key . ':';
		return 'Basic ' . base64_encode( $auth_string ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Make API request.
	 *
	 * @param string $endpoint API endpoint.
	 * @param array  $args Request arguments.
	 * @param string $method HTTP method.
	 * @param array  $extra_headers Additional headers.
	 * @return array|WP_Error
	 */
	private function request( $endpoint, $args = array(), $method = 'POST', $extra_headers = array() ) {
		$url = $this->api_url . $endpoint;

		$headers = array(
			'Authorization' => $this->get_auth_header(),
			'Content-Type'  => 'application/json',
		);

		// Add any extra headers.
		if ( ! empty( $extra_headers ) ) {
			$headers = array_merge( $headers, $extra_headers );
		}

		$request_args = array(
			'method'  => $method,
			'headers' => $headers,
			'timeout' => 30,
		);

		if ( 'POST' === $method && ! empty( $args ) ) {
			$request_args['body'] = wp_json_encode( $args );
		}

		// Log request without sensitive data.
		$log_args = $args;
		if ( isset( $log_args['paymentKey'] ) ) {
			$log_args['paymentKey'] = substr( $log_args['paymentKey'], 0, 10 ) . '...';
		}
		$this->gateway->log( 'API Request: ' . $method . ' ' . $endpoint );
		$this->gateway->log( 'Request Args: ' . wp_json_encode( $log_args ) );

		$response = wp_remote_request( $url, $request_args );

		if ( is_wp_error( $response ) ) {
			$this->gateway->log( 'API Error: ' . $response->get_error_message() );
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		$code = wp_remote_retrieve_response_code( $response );

		$this->gateway->log( 'API Response Code: ' . $code );

		$data = json_decode( $body, true );

		// Log response without sensitive data.
		$log_data = $data;
		if ( is_array( $log_data ) ) {
			if ( isset( $log_data['secret'] ) ) {
				$log_data['secret'] = '[REDACTED]';
			}
			if ( isset( $log_data['card'] ) && is_array( $log_data['card'] ) ) {
				if ( isset( $log_data['card']['number'] ) ) {
					$log_data['card']['number'] = '[REDACTED]';
				}
			}
		}
		$this->gateway->log( 'API Response: ' . wp_json_encode( $log_data ) );

		if ( 200 !== $code ) {
			$error_code = isset( $data['code'] ) ? $data['code'] : 'UNKNOWN_ERROR';
			$error_message = isset( $data['message'] ) ? $data['message'] : __( 'API request failed.', 'seoulcommerce-payment-gateway-for-tosspayments' );
			
			// Provide clearer error messages for common cases.
			// Error codes reference: https://docs.tosspayments.com/reference/error-codes
			if ( 'NOT_CANCELABLE_AMOUNT' === $error_code ) {
				$error_message = __( 'The refund amount exceeds the remaining cancelable balance.', 'seoulcommerce-payment-gateway-for-tosspayments' );
			} elseif ( 'ALREADY_CANCELED_PAYMENT' === $error_code ) {
				$error_message = __( 'This payment has already been fully canceled.', 'seoulcommerce-payment-gateway-for-tosspayments' );
			} elseif ( 'NOT_FOUND_PAYMENT' === $error_code ) {
				$error_message = __( 'Payment not found. The payment key may be invalid.', 'seoulcommerce-payment-gateway-for-tosspayments' );
			} elseif ( in_array( $error_code, array( 'FORBIDDEN_REQUEST', 'UNAUTHORIZED_KEY' ), true ) ) {
				$error_message = __( 'Authentication failed. Please check your TossPayments API keys and IP allowlist settings.', 'seoulcommerce-payment-gateway-for-tosspayments' );
			}
			
			return new WP_Error( 'api_error', $error_message, array( 'code' => $error_code, 'data' => $data ) );
		}

		return $data;
	}

	/**
	 * Approve payment.
	 *
	 * @param string $payment_key Payment key from TossPayments.
	 * @param string $order_id Order ID.
	 * @param float  $amount Payment amount.
	 * @return array|WP_Error
	 */
	public function approve_payment( $payment_key, $order_id, $amount ) {
		$endpoint = '/payments/confirm';

		$args = array(
			'paymentKey' => $payment_key,
			'orderId'    => $order_id,
			'amount'     => intval( $amount ),
		);

		return $this->request( $endpoint, $args );
	}

	/**
	 * Cancel payment (refund).
	 *
	 * @param string $payment_key Payment key.
	 * @param float  $amount Cancel amount (null for full cancel).
	 * @param string $reason Cancel reason.
	 * @param string $idempotency_key Idempotency key (optional).
	 * @return array|WP_Error
	 */
	public function cancel_payment( $payment_key, $amount = null, $reason = '', $idempotency_key = '' ) {
		// Validate and encode payment key for URL.
		if ( empty( $payment_key ) || ! is_string( $payment_key ) ) {
			return new WP_Error( 'invalid_payment_key', __( 'Invalid payment key.', 'seoulcommerce-payment-gateway-for-tosspayments' ) );
		}
		$encoded_key = rawurlencode( $payment_key );
		$endpoint = '/payments/' . $encoded_key . '/cancel';

		$args = array();

		// Add cancel reason (required by TossPayments).
		if ( empty( $reason ) ) {
			$reason = __( 'Refund requested', 'seoulcommerce-payment-gateway-for-tosspayments' );
		}
		$args['cancelReason'] = $reason;

		// Add cancel amount for partial refunds.
		// If amount is null, TossPayments will process a full refund.
		if ( null !== $amount ) {
			// Convert to integer (TossPayments expects amount in KRW without decimals).
			$args['cancelAmount'] = intval( round( $amount ) );
		}

		// Add idempotency key if provided.
		$extra_headers = array();
		if ( ! empty( $idempotency_key ) ) {
			$extra_headers['Idempotency-Key'] = $idempotency_key;
		}

		$this->gateway->log( 
			sprintf( 
				'Canceling payment: Amount=%s, Reason=%s, HasIdempotencyKey=%s',
				null !== $amount ? $args['cancelAmount'] : 'full refund',
				$reason,
				! empty( $idempotency_key ) ? 'yes' : 'no'
			)
		);

		return $this->request( $endpoint, $args, 'POST', $extra_headers );
	}

	/**
	 * Get payment details.
	 *
	 * @param string $payment_key Payment key.
	 * @return array|WP_Error
	 */
	public function get_payment( $payment_key ) {
		// Validate and encode payment key for URL.
		if ( empty( $payment_key ) || ! is_string( $payment_key ) ) {
			return new WP_Error( 'invalid_payment_key', __( 'Invalid payment key.', 'seoulcommerce-payment-gateway-for-tosspayments' ) );
		}
		$encoded_key = rawurlencode( $payment_key );
		$endpoint = '/payments/' . $encoded_key;
		return $this->request( $endpoint, array(), 'GET' );
	}
}

