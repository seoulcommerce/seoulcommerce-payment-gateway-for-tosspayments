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
	 * @return array|WP_Error
	 */
	private function request( $endpoint, $args = array(), $method = 'POST' ) {
		$url = $this->api_url . $endpoint;

		$headers = array(
			'Authorization' => $this->get_auth_header(),
			'Content-Type'  => 'application/json',
		);

		$request_args = array(
			'method'  => $method,
			'headers' => $headers,
			'timeout' => 30,
		);

		if ( 'POST' === $method && ! empty( $args ) ) {
			$request_args['body'] = wp_json_encode( $args );
		}

		$this->gateway->log( 'API Request: ' . $method . ' ' . $url );
		$this->gateway->log( 'Request Body: ' . wp_json_encode( $args ) );

		$response = wp_remote_request( $url, $request_args );

		if ( is_wp_error( $response ) ) {
			$this->gateway->log( 'API Error: ' . $response->get_error_message() );
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		$code = wp_remote_retrieve_response_code( $response );

		$this->gateway->log( 'API Response Code: ' . $code );
		$this->gateway->log( 'API Response Body: ' . $body );

		$data = json_decode( $body, true );

		if ( 200 !== $code ) {
			$error_message = isset( $data['message'] ) ? $data['message'] : __( 'API request failed.', 'seoulcommerce-payment-gateway-for-tosspayments' );
			return new WP_Error( 'api_error', $error_message, $data );
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
	 * @return array|WP_Error
	 */
	public function cancel_payment( $payment_key, $amount = null, $reason = '' ) {
		$endpoint = '/payments/' . $payment_key . '/cancel';

		$args = array();

		// Add cancel reason (required by TossPayments).
		if ( empty( $reason ) ) {
			$reason = 'Refund requested';
		}
		$args['cancelReason'] = $reason;

		// Add cancel amount for partial refunds.
		// If amount is null, TossPayments will process a full refund.
		if ( null !== $amount ) {
			// Convert to integer (TossPayments expects amount in KRW without decimals).
			$args['cancelAmount'] = intval( $amount );
		}

		$this->gateway->log( 
			sprintf( 
				'Canceling payment: Payment Key=%s, Amount=%s, Reason=%s',
				$payment_key,
				null !== $amount ? $args['cancelAmount'] : 'full refund',
				$reason
			)
		);

		return $this->request( $endpoint, $args );
	}

	/**
	 * Get payment details.
	 *
	 * @param string $payment_key Payment key.
	 * @return array|WP_Error
	 */
	public function get_payment( $payment_key ) {
		$endpoint = '/payments/' . $payment_key;
		return $this->request( $endpoint, array(), 'GET' );
	}
}

