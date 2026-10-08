<?php
/**
 * TossPayments Payment Gateway.
 *
 * @package WooCommerce_TossPayments
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Ensure WooCommerce is active.
if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
	return;
}

/**
 * SeoulCommerce_TPG_Gateway class.
 */
class SeoulCommerce_TPG_Gateway extends WC_Payment_Gateway {

	/**
	 * Test mode flag.
	 *
	 * @var bool
	 */
	public $testmode;

	/**
	 * Test client key.
	 *
	 * @var string
	 */
	public $client_key_test;

	/**
	 * Test secret key.
	 *
	 * @var string
	 */
	public $secret_key_test;

	/**
	 * Live client key.
	 *
	 * @var string
	 */
	public $client_key_live;

	/**
	 * Live secret key.
	 *
	 * @var string
	 */
	public $secret_key_live;

	/**
	 * Debug mode flag.
	 *
	 * @var bool
	 */
	public $debug;

	/**
	 * API instance.
	 *
	 * @var SeoulCommerce_TPG_API
	 */
	public $api;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id                 = 'tosspayments';
		$this->icon               = SEOULCOMMERCE_TPG_PLUGIN_URL . 'assets/TossPayments_Logo_Primary.png';
		$this->has_fields         = false;
		$this->method_title       = __( 'SeoulCommerce Payment Gateway for TossPayments', 'seoulcommerce-payment-gateway-for-tosspayments' );
		$this->method_description = __( 'Accept card payments via TossPayments using version 2 API.', 'seoulcommerce-payment-gateway-for-tosspayments' );
		$this->supports           = array(
			'products',
			'refunds',
		);

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
		$this->title                = $this->get_option( 'title' );
		$this->description          = $this->get_option( 'description' );
		$this->enabled              = $this->get_option( 'enabled' );
		$this->testmode             = 'yes' === $this->get_option( 'testmode', 'yes' );
		$this->client_key_test      = $this->get_option( 'client_key_test' );
		$this->secret_key_test      = $this->get_option( 'secret_key_test' );
		$this->client_key_live      = $this->get_option( 'client_key_live' );
		$this->secret_key_live      = $this->get_option( 'secret_key_live' );
		$this->debug                = 'yes' === $this->get_option( 'debug', 'yes' );

		// Get API instance.
		$this->api = new SeoulCommerce_TPG_API( $this );

		// Actions.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
		add_action( 'woocommerce_api_seoulcommerce_tpg_return', array( $this, 'handle_return' ) );
		add_action( 'woocommerce_api_seoulcommerce_tpg_webhook', array( $this, 'handle_webhook' ) );
		
		// AJAX handlers for blocks checkout.
		add_action( 'wp_ajax_seoulcommerce_tpg_get_order_details', array( $this, 'ajax_get_order_details' ) );
		add_action( 'wp_ajax_nopriv_seoulcommerce_tpg_get_order_details', array( $this, 'ajax_get_order_details' ) );
	}

	/**
	 * Initialize gateway settings form fields.
	 */
	public function init_form_fields() {
		$onboarding_url = 'https://onboarding.tosspayments.com/registration/business-registration-number?utm_source=seoulwd&utm_medium=hosting&agencyCode=seoulwd';
		
		$this->form_fields = array(
			'signup_notice'   => array(
				'title'       => __( '🎉 특별 우대 수수료 혜택', 'seoulcommerce-payment-gateway-for-tosspayments' ),
				'type'        => 'title',
				'description' => sprintf(
					'<div style="background: linear-gradient(135deg, #f8fbff 0%%, #e3f2fd 100%%); border-left: 4px solid #1e88e5; padding: 20px; margin: 10px 0; border-radius: 4px;">
						<h3 style="margin-top: 0; color: #1e88e5;">💰 SeoulCommerce 제휴 특별 혜택</h3>
						<p style="font-size: 15px; line-height: 1.6;"><strong>아직 가입하지 않으셨나요?</strong> 아래 링크로 가입하시면 <strong style="color: #1e88e5;">업계 최저 수수료율</strong>을 받으실 수 있습니다!</p>
					<ul style="margin: 15px 0; padding-left: 20px;">
						<li>✅ 특별 우대 수수료율 적용</li>
						<li>✅ 모든 결제수단 지원</li>
						<li>✅ 사업자등록번호만으로 5분 만에 가입 완료</li>
						<li>✅ 실시간 정산 및 24시간 고객 지원</li>
					</ul>
						<p style="margin: 20px 0;">
							<a href="%s" class="button button-primary button-hero" target="_blank" rel="noopener noreferrer" style="background: #1e88e5 !important; border-color: #1565c0 !important; text-decoration: none; font-size: 16px; padding: 12px 30px;">
								<span class="dashicons dashicons-external" style="vertical-align: middle;"></span>
								지금 가입하고 특별 혜택 받기
							</a>
						</p>
						<p style="font-size: 13px; color: #666; margin-bottom: 0;">💡 가입 후 이 페이지에서 API 키를 설정하시면 바로 사용하실 수 있습니다.</p>
					</div>',
					esc_url( $onboarding_url )
				),
			),
			'enabled'         => array(
				'title'   => __( 'Enable/Disable', 'seoulcommerce-payment-gateway-for-tosspayments' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable TossPayments', 'seoulcommerce-payment-gateway-for-tosspayments' ),
				'default' => 'no',
			),
			'title'           => array(
				'title'       => __( 'Title', 'seoulcommerce-payment-gateway-for-tosspayments' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'seoulcommerce-payment-gateway-for-tosspayments' ),
				'default'     => __( 'SeoulCommerce Payment Gateway for TossPayments', 'seoulcommerce-payment-gateway-for-tosspayments' ),
				'desc_tip'    => true,
			),
			'description'     => array(
				'title'       => __( 'Description', 'seoulcommerce-payment-gateway-for-tosspayments' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'seoulcommerce-payment-gateway-for-tosspayments' ),
				'default'     => __( 'Pay securely with your card via TossPayments.', 'seoulcommerce-payment-gateway-for-tosspayments' ),
				'desc_tip'    => true,
			),
			'testmode'        => array(
				'title'       => __( 'Test Mode', 'seoulcommerce-payment-gateway-for-tosspayments' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable Test Mode', 'seoulcommerce-payment-gateway-for-tosspayments' ),
				'default'     => 'yes',
				'description' => __( 'Place the payment gateway in test mode using test API keys.', 'seoulcommerce-payment-gateway-for-tosspayments' ),
			),
			'client_key_test' => array(
				'title'       => __( 'Test Client Key', 'seoulcommerce-payment-gateway-for-tosspayments' ),
				'type'        => 'text',
				'description' => __( 'Get your API keys from your TossPayments account.', 'seoulcommerce-payment-gateway-for-tosspayments' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'secret_key_test' => array(
				'title'       => __( 'Test Secret Key', 'seoulcommerce-payment-gateway-for-tosspayments' ),
				'type'        => 'password',
				'description' => __( 'Get your API keys from your TossPayments account.', 'seoulcommerce-payment-gateway-for-tosspayments' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'client_key_live' => array(
				'title'       => __( 'Live Client Key', 'seoulcommerce-payment-gateway-for-tosspayments' ),
				'type'        => 'text',
				'description' => __( 'Get your API keys from your TossPayments account.', 'seoulcommerce-payment-gateway-for-tosspayments' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'secret_key_live' => array(
				'title'       => __( 'Live Secret Key', 'seoulcommerce-payment-gateway-for-tosspayments' ),
				'type'        => 'password',
				'description' => __( 'Get your API keys from your TossPayments account.', 'seoulcommerce-payment-gateway-for-tosspayments' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'debug'           => array(
				'title'       => __( 'Debug Log', 'seoulcommerce-payment-gateway-for-tosspayments' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable logging', 'seoulcommerce-payment-gateway-for-tosspayments' ),
				'default'     => 'no',
				'description' => sprintf(
					/* translators: %s: Log file path */
					__( 'Log TossPayments events, such as API requests, inside %s', 'seoulcommerce-payment-gateway-for-tosspayments' ),
					'<code>' . WC_Log_Handler_File::get_log_file_path( 'tosspayments' ) . '</code>'
				),
			),
		);
	}

	/**
	 * Get client key based on test mode.
	 *
	 * @return string
	 */
	public function get_client_key() {
		return $this->testmode ? $this->client_key_test : $this->client_key_live;
	}

	/**
	 * Get secret key based on test mode.
	 *
	 * @return string
	 */
	public function get_secret_key() {
		return $this->testmode ? $this->secret_key_test : $this->secret_key_live;
	}

	/**
	 * Check if gateway is available.
	 *
	 * @return bool
	 */
	public function is_available() {
		$available = true;
		$reason = '';

		if ( 'yes' !== $this->enabled ) {
			$available = false;
			$reason = 'Gateway not enabled';
		}

		// In test mode, allow even without keys for easier setup.
		// In live mode, require both keys.
		if ( $available && ! $this->testmode ) {
			$client_key = $this->get_client_key();
			$secret_key = $this->get_secret_key();
			if ( empty( $client_key ) || empty( $secret_key ) ) {
				$available = false;
				$reason = 'Missing API keys in live mode';
			}
		}

		// Check cart total (match inicis exactly).
		if ( $available && WC()->cart && WC()->cart->total <= 0 ) {
			$available = false;
			$reason = 'Cart total is zero';
		}

		// Check parent availability.
		if ( $available ) {
			$parent_available = parent::is_available();
			if ( ! $parent_available ) {
				$available = false;
				$reason = 'Parent is_available() returned false';
			}
		}

		return $available;
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			wc_add_notice( __( 'Order not found.', 'seoulcommerce-payment-gateway-for-tosspayments' ), 'error' );
			return array(
				'result'   => 'fail',
				'redirect' => '',
			);
		}

		// Store order ID in session for return handling.
		if ( WC()->session ) {
			WC()->session->set( 'seoulcommerce_tpg_order_id', $order_id );
		}

		// Mark order as pending payment.
		$order->update_status( 'pending', __( 'Awaiting TossPayments payment', 'seoulcommerce-payment-gateway-for-tosspayments' ) );

		// Store TossPayments order ID for webhook lookup before payment completes.
		$order->update_meta_data( '_tosspayments_order_id', $this->get_tosspayments_order_id( $order_id ) );

		// Ensure order is marked as needing payment.
		$order->set_date_paid( null );
		$order->save();

		// Get payment URL and redirect to payment page.
		$payment_url = $order->get_checkout_payment_url( true );
		
		return array(
			'result'   => 'success',
			'redirect' => $payment_url,
		);
	}

	/**
	 * Get sanitized order ID for TossPayments (alphanumeric only).
	 *
	 * @param int $order_id Order ID.
	 * @return string Sanitized order ID.
	 */
	private function get_tosspayments_order_id( $order_id ) {
		// TossPayments orderId only allows letters and numbers, no special characters.
		// Remove # and any other special characters from WooCommerce order number.
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return 'order-' . $order_id;
		}
		
		// Get order number (may have prefix/suffix from plugins).
		$order_number = $order->get_order_number();
		
		// Strip all non-alphanumeric characters.
		$sanitized = preg_replace( '/[^a-zA-Z0-9]/', '', $order_number );
		
		// Ensure it's not empty and has a prefix.
		if ( empty( $sanitized ) ) {
			$sanitized = 'order' . $order_id;
		} elseif ( ! preg_match( '/^[a-zA-Z]/', $sanitized ) ) {
			// TossPayments recommends starting with a letter.
			$sanitized = 'order' . $sanitized;
		}
		
		return $sanitized;
	}

	/**
	 * Output payment fields.
	 */
	public function payment_fields() {
		if ( $this->description ) {
			echo wp_kses_post( wpautop( wptexturize( $this->description ) ) );
		}
	}

	/**
	 * Enqueue payment scripts.
	 */
	public function payment_scripts() {
		// Load on checkout page AND order-pay page.
		if ( ( ! is_checkout() && ! is_checkout_pay_page() ) || ! $this->is_available() ) {
			return;
		}

		$client_key = $this->get_client_key();
		if ( ! $client_key ) {
			return;
		}

		// Enqueue checkout styles.
		wp_enqueue_style(
			'seoulcommerce-tpg-checkout',
			SEOULCOMMERCE_TPG_PLUGIN_URL . 'assets/css/checkout.css',
			array(),
			SEOULCOMMERCE_TPG_VERSION
		);

		// Enqueue TossPayments SDK v2 (standard).
		wp_enqueue_script(
			'tosspayments-sdk',
			'https://js.tosspayments.com/v2/standard',
			array(),
			'2.0.0',
			true
		);

		// Enqueue custom payment script.
		wp_enqueue_script(
			'seoulcommerce-tpg',
			SEOULCOMMERCE_TPG_PLUGIN_URL . 'assets/js/payment.js',
			array( 'jquery', 'tosspayments-sdk', 'wc-checkout' ),
			SEOULCOMMERCE_TPG_VERSION,
			true
		);

		// Get order data.
		$order_id = 0;
		$amount   = 0;
		$order_key = '';
		$tosspayments_order_id = '';

		// If on order-pay page, get order from URL.
		if ( is_checkout_pay_page() ) {
			global $wp;
			$order_id = absint( $wp->query_vars['order-pay'] );
			$order    = wc_get_order( $order_id );
			
			if ( $order ) {
				$amount           = $order->get_total();
				$order_key        = $order->get_order_key();
			$customer_email   = $order->get_billing_email();
			$customer_name    = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
			$customer_phone   = $order->get_billing_phone();
			/* translators: %s: Order number */
			$order_name       = sprintf( __( 'Order #%s', 'seoulcommerce-payment-gateway-for-tosspayments' ), $order->get_order_number() );
			$tosspayments_order_id = $this->get_tosspayments_order_id( $order_id );
			}
		} else {
			// On checkout page, get from session/cart.
			$order_id         = WC()->session ? WC()->session->get( 'seoulcommerce_tpg_order_id' ) : 0;
			$amount           = WC()->cart ? WC()->cart->get_total( '' ) : 0;
			$customer_email   = '';
			$customer_name    = '';
			$customer_phone   = '';
			$order_name       = '';
			
			if ( $order_id ) {
				$tosspayments_order_id = $this->get_tosspayments_order_id( $order_id );
			}
		}

		// Localize script.
		wp_localize_script(
			'seoulcommerce-tpg',
			'seoulcommerceTpgParams',
			array(
				'clientKey'            => $client_key,
				'orderId'              => $order_id,
				'tosspayments_orderId' => $tosspayments_order_id,
				'isOrderPayPage'       => is_checkout_pay_page(),
				'amount'               => $amount,
				'orderKey'             => $order_key,
				'orderName'            => $order_name,
				'customerEmail'        => $customer_email,
				'customerName'         => $customer_name,
				'customerPhone'        => $customer_phone,
				'checkoutUrl'          => wc_get_checkout_url(),
				'returnUrl'            => add_query_arg( 'wc-api', 'seoulcommerce_tpg_return', home_url( '/' ) ),
				'ajaxUrl'              => admin_url( 'admin-ajax.php' ),
				'nonce'                => $order_id ? wp_create_nonce( $this->get_order_nonce_action( $order_id ) ) : '',
				'i18n'            => array(
					'processing' => __( 'Processing payment...', 'seoulcommerce-payment-gateway-for-tosspayments' ),
					'error'      => __( 'Payment failed. Please try again.', 'seoulcommerce-payment-gateway-for-tosspayments' ),
				),
			)
		);
	}

	/**
	 * Handle return from TossPayments.
	 */
	public function handle_return() {
		$order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $order_id ) {
			$order_id = WC()->session->get( 'seoulcommerce_tpg_order_id' );
		}

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			wc_add_notice( __( 'Order not found.', 'seoulcommerce-payment-gateway-for-tosspayments' ), 'error' );
			wp_safe_redirect( wc_get_checkout_url() );
			exit;
		}

		// Get payment key and amount from query parameters.
		$payment_key = isset( $_GET['paymentKey'] ) ? sanitize_text_field( wp_unslash( $_GET['paymentKey'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$amount      = isset( $_GET['amount'] ) ? floatval( $_GET['amount'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order_id_param = isset( $_GET['orderId'] ) ? sanitize_text_field( wp_unslash( $_GET['orderId'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Verify amount matches order total.
		$order_amount = floatval( $order->get_total() );
		if ( abs( $amount - $order_amount ) > 0.01 ) {
			$this->log( 'Amount mismatch. Order: ' . $order_amount . ', Payment: ' . $amount );
			wc_add_notice( __( 'Payment amount mismatch. Please contact support.', 'seoulcommerce-payment-gateway-for-tosspayments' ), 'error' );
			wp_safe_redirect( $order->get_checkout_payment_url() );
			exit;
		}

		// Approve payment using sanitized order ID.
		$tosspayments_order_id = $this->get_tosspayments_order_id( $order_id );
		$result = $this->api->approve_payment( $payment_key, $tosspayments_order_id, $amount );

		if ( is_wp_error( $result ) ) {
			$this->log( 'Payment approval failed: ' . $result->get_error_message() );
			wc_add_notice( $result->get_error_message(), 'error' );
			wp_safe_redirect( $order->get_checkout_payment_url() );
			exit;
		}

	// Payment successful.
	$order->payment_complete( $payment_key );
	
	/* translators: 1: Payment key from TossPayments, 2: TossPayments order ID */
	$order_note_text = __( 'TossPayments payment approved. Payment Key: %1$s, TossPayments Order ID: %2$s', 'seoulcommerce-payment-gateway-for-tosspayments' );
	$order->add_order_note( sprintf( $order_note_text, $payment_key, $tosspayments_order_id ) );

		// Clear session.
		if ( WC()->session ) {
			WC()->session->__unset( 'seoulcommerce_tpg_order_id' );
		}

		// Redirect to thank you page.
		wp_safe_redirect( $this->get_return_url( $order ) );
		exit;
	}

	/**
	 * Handle webhook from TossPayments.
	 */
	public function handle_webhook() {
		// Get webhook data.
		$body     = file_get_contents( 'php://input' );
		$raw_data = json_decode( $body, true );
		$data     = $this->sanitize_webhook_payload( $raw_data );

		if ( empty( $data ) ) {
			status_header( 400 );
			exit;
		}

		// Log only minimal safe data; webhook payload is attacker-controlled.
		$event_type_raw = isset( $data['eventType'] ) ? $data['eventType'] : '';
		$event_type     = strtoupper( sanitize_text_field( (string) $event_type_raw ) );
		$this->log( 'Webhook received. eventType=' . $event_type );

		// General payment webhooks have no signature header; re-verify via TossPayments API.
		switch ( $event_type ) {
			case 'PAYMENT_CONFIRMED':
			case 'PAYMENT_STATUS_CHANGED':
				$this->handle_payment_confirmed( $data );
				break;
			case 'PAYMENT_CANCELED':
			case 'CANCEL_STATUS_CHANGED':
				$this->handle_payment_canceled( $data );
				break;
		}

		status_header( 200 );
		exit;
	}

	/**
	 * Sanitize and validate webhook payload.
	 *
	 * @param mixed $raw_data Decoded webhook payload.
	 * @return array Sanitized payload, or empty array when invalid.
	 */
	private function sanitize_webhook_payload( $raw_data ) {
		if ( empty( $raw_data ) || ! is_array( $raw_data ) ) {
			return array();
		}

		$sanitized = array(
			'eventType' => '',
			'data'      => array(),
		);

		if ( isset( $raw_data['eventType'] ) ) {
			$sanitized['eventType'] = strtoupper( sanitize_text_field( (string) $raw_data['eventType'] ) );
		}

		if ( isset( $raw_data['data'] ) && is_array( $raw_data['data'] ) ) {
			if ( isset( $raw_data['data']['paymentKey'] ) ) {
				$sanitized['data']['paymentKey'] = sanitize_text_field( (string) $raw_data['data']['paymentKey'] );
			}
			if ( isset( $raw_data['data']['orderId'] ) ) {
				$sanitized['data']['orderId'] = sanitize_text_field( (string) $raw_data['data']['orderId'] );
			}
			if ( isset( $raw_data['data']['status'] ) ) {
				$sanitized['data']['status'] = strtoupper( sanitize_text_field( (string) $raw_data['data']['status'] ) );
			}
			if ( isset( $raw_data['data']['totalAmount'] ) ) {
				$sanitized['data']['totalAmount'] = floatval( $raw_data['data']['totalAmount'] );
			}
		}

		return $sanitized;
	}

	/**
	 * Handle payment confirmed webhook.
	 *
	 * @param array $data Webhook data.
	 */
	private function handle_payment_confirmed( $data ) {
		if ( empty( $data['data'] ) || ! is_array( $data['data'] ) || empty( $data['data']['paymentKey'] ) ) {
			return;
		}

		$payment_key = sanitize_text_field( (string) $data['data']['paymentKey'] );
		if ( '' === $payment_key ) {
			return;
		}

		$payment = $this->api->get_payment( $payment_key );
		if ( is_wp_error( $payment ) ) {
			$this->log( 'Webhook payment verification failed: ' . $payment->get_error_message() );
			return;
		}

		if ( empty( $payment['status'] ) || 'DONE' !== strtoupper( (string) $payment['status'] ) ) {
			$this->log( 'Webhook ignored: payment status is not DONE.' );
			return;
		}

		$order_id = $this->get_order_id_by_payment_key( $payment_key );
		if ( ! $order_id && ! empty( $payment['orderId'] ) ) {
			$order_id = $this->get_order_id_by_tosspayments_order_id( sanitize_text_field( (string) $payment['orderId'] ) );
		}
		if ( ! $order_id && ! empty( $data['data']['orderId'] ) ) {
			$order_id = $this->get_order_id_by_tosspayments_order_id( sanitize_text_field( (string) $data['data']['orderId'] ) );
		}

		if ( ! $order_id ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$verified_amount = isset( $payment['totalAmount'] ) ? floatval( $payment['totalAmount'] ) : 0;
		$order_amount    = floatval( $order->get_total() );
		if ( $verified_amount <= 0 || abs( $verified_amount - $order_amount ) > 0.01 ) {
			$this->log( 'Webhook amount mismatch for order #' . $order_id );
			return;
		}

		if ( ! $order->is_paid() ) {
			$order->payment_complete( $payment_key );
			$order->add_order_note( __( 'Payment confirmed via webhook.', 'seoulcommerce-payment-gateway-for-tosspayments' ) );
		}
	}

	/**
	 * Handle payment canceled webhook.
	 *
	 * @param array $data Webhook data.
	 */
	private function handle_payment_canceled( $data ) {
		if ( empty( $data['data'] ) || ! is_array( $data['data'] ) || empty( $data['data']['paymentKey'] ) ) {
			return;
		}

		$payment_key = sanitize_text_field( (string) $data['data']['paymentKey'] );
		if ( '' === $payment_key ) {
			return;
		}

		// Deduplicate on webhook transmission ID if present.
		if ( isset( $_SERVER['HTTP_TOSSPAYMENTS_WEBHOOK_TRANSMISSION_ID'] ) ) {
			$transmission_id = sanitize_text_field( wp_unslash( $_SERVER['HTTP_TOSSPAYMENTS_WEBHOOK_TRANSMISSION_ID'] ) );
			$processed_transmissions = get_option( 'tosspayments_processed_webhooks', array() );
			
			if ( in_array( $transmission_id, $processed_transmissions, true ) ) {
				$this->log( 'Webhook ignored: already processed transmission ID ' . $transmission_id );
				return;
			}
			
			// Store transmission ID (keep last 100 to prevent unbounded growth).
			$processed_transmissions[] = $transmission_id;
			if ( count( $processed_transmissions ) > 100 ) {
				$processed_transmissions = array_slice( $processed_transmissions, -100 );
			}
			update_option( 'tosspayments_processed_webhooks', $processed_transmissions, false );
		}

		// Verify payment status by fetching from TossPayments API.
		$payment = $this->api->get_payment( $payment_key );
		if ( is_wp_error( $payment ) ) {
			$this->log( 'Webhook cancel verification failed: ' . $payment->get_error_message() );
			return;
		}

		$payment_status = isset( $payment['status'] ) ? strtoupper( (string) $payment['status'] ) : '';
		if ( ! in_array( $payment_status, array( 'CANCELED', 'PARTIAL_CANCELED' ), true ) ) {
			$this->log( 'Webhook ignored: payment status is not canceled (status: ' . $payment_status . ')' );
			return;
		}

		// Find the order.
		$order_id = $this->get_order_id_by_payment_key( $payment_key );
		if ( ! $order_id && ! empty( $payment['orderId'] ) ) {
			$order_id = $this->get_order_id_by_tosspayments_order_id( sanitize_text_field( (string) $payment['orderId'] ) );
		}
		if ( ! $order_id && ! empty( $data['data']['orderId'] ) ) {
			$order_id = $this->get_order_id_by_tosspayments_order_id( sanitize_text_field( (string) $data['data']['orderId'] ) );
		}

		if ( ! $order_id ) {
			$this->log( 'Webhook ignored: order not found for payment key' );
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		// Get our known cancel transactionKeys (from admin refunds we initiated).
		$known_cancel_keys = $order->get_meta( '_tosspayments_cancel_keys' );
		if ( ! is_array( $known_cancel_keys ) ) {
			$known_cancel_keys = array();
		}

		// Walk through the payment.cancels array to reconcile.
		$cancels = isset( $payment['cancels'] ) && is_array( $payment['cancels'] ) ? $payment['cancels'] : array();
		$total_amount = isset( $payment['totalAmount'] ) ? floatval( $payment['totalAmount'] ) : 0;
		$canceled_amount = isset( $payment['canceledAmount'] ) ? floatval( $payment['canceledAmount'] ) : 0;
		$balance_amount = isset( $payment['balanceAmount'] ) ? floatval( $payment['balanceAmount'] ) : 0;

		$this->log( sprintf(
			'Processing cancel webhook for order #%s: Status=%s, Total=%s, Canceled=%s, Balance=%s, CancelsCount=%d',
			$order_id,
			$payment_status,
			$total_amount,
			$canceled_amount,
			$balance_amount,
			count( $cancels )
		) );

		// Process each cancel in the array.
		foreach ( $cancels as $cancel ) {
			if ( empty( $cancel['transactionKey'] ) ) {
				continue;
			}

		$transaction_key = $cancel['transactionKey'];
		$cancel_amount = isset( $cancel['cancelAmount'] ) ? floatval( $cancel['cancelAmount'] ) : 0;
		$cancel_reason = isset( $cancel['cancelReason'] ) ? sanitize_text_field( $cancel['cancelReason'] ) : '';

		// Skip if we already know about this cancel (we initiated it from admin).
		if ( in_array( $transaction_key, $known_cancel_keys, true ) ) {
				$this->log( sprintf(
					'Skipping cancel transactionKey=%s (initiated by admin)',
					$transaction_key
				) );
				continue;
			}

			// This is a dashboard cancel - check if we've already processed it.
			$processed_dashboard_cancels = $order->get_meta( '_tosspayments_processed_dashboard_cancels' );
			if ( ! is_array( $processed_dashboard_cancels ) ) {
				$processed_dashboard_cancels = array();
			}

			if ( in_array( $transaction_key, $processed_dashboard_cancels, true ) ) {
				$this->log( sprintf(
					'Skipping cancel transactionKey=%s (already processed)',
					$transaction_key
				) );
				continue;
			}

			// This is a new dashboard cancel - create a WooCommerce refund record.
			$this->log( sprintf(
				'Processing dashboard cancel: transactionKey=%s, amount=%s, reason=%s',
				$transaction_key,
				$cancel_amount,
				$cancel_reason
			) );

			// Create WooCommerce refund with refund_payment => false (already refunded at gateway).
			$refund = wc_create_refund(
				array(
					'order_id'       => $order_id,
					'amount'         => $cancel_amount,
					'reason'         => sprintf(
						/* translators: %s: Cancel reason from TossPayments */
						__( 'Refunded in TossPayments merchant dashboard: %s', 'seoulcommerce-payment-gateway-for-tosspayments' ),
						$cancel_reason
					),
					'refund_payment' => false, // Already refunded at gateway.
				)
			);

			if ( is_wp_error( $refund ) ) {
				$this->log( 'Failed to create WC refund for dashboard cancel: ' . $refund->get_error_message() );
				$order->add_order_note(
					sprintf(
						/* translators: 1: Cancel amount, 2: Error message */
						__( 'Dashboard refund detected (%1$s) but failed to create WC refund record: %2$s', 'seoulcommerce-payment-gateway-for-tosspayments' ),
						wc_price( $cancel_amount, array( 'currency' => $order->get_currency() ) ),
						$refund->get_error_message()
					)
				);
			} else {
				$this->log( sprintf(
					'Created WC refund #%d for dashboard cancel',
					$refund->get_id()
				) );
			}

			// Mark this cancel as processed.
			$processed_dashboard_cancels[] = $transaction_key;
			$order->update_meta_data( '_tosspayments_processed_dashboard_cancels', $processed_dashboard_cancels );
		}

		// After processing all cancels, update order status if appropriate.
		$is_fully_canceled = ( 'CANCELED' === $payment_status || $balance_amount <= 0.01 );

		if ( $is_fully_canceled ) {
			// Full cancellation - let WooCommerce's own logic handle status transition to 'refunded'.
			// This happens automatically when the order is fully refunded.
			// Just add a note if not already in final state.
			if ( ! $order->has_status( array( 'refunded', 'cancelled' ) ) ) {
				// Force status update if WC hasn't caught up yet.
				$order->update_status(
					'refunded',
					sprintf(
						/* translators: %s: Total canceled amount */
						__( 'Payment fully canceled. Total refunded: %s', 'seoulcommerce-payment-gateway-for-tosspayments' ),
						wc_price( $canceled_amount, array( 'currency' => $order->get_currency() ) )
					)
				);
			}
		} else {
			// Partial cancellation - order should stay in paid status.
			// Add note only if this was a dashboard cancel we just processed.
			if ( ! empty( $processed_dashboard_cancels ) ) {
				$order->add_order_note(
					sprintf(
						/* translators: 1: Canceled amount, 2: Remaining balance */
						__( 'Partial refund processed in TossPayments dashboard. Total refunded: %1$s, Remaining balance: %2$s', 'seoulcommerce-payment-gateway-for-tosspayments' ),
						wc_price( $canceled_amount, array( 'currency' => $order->get_currency() ) ),
						wc_price( $balance_amount, array( 'currency' => $order->get_currency() ) )
					)
				);
			}
		}

		$order->save();
	}

	/**
	 * Get order ID by payment key.
	 *
	 * @param string $payment_key Payment key.
	 * @return int|false
	 */
	private function get_order_id_by_payment_key( $payment_key ) {
	// Use WooCommerce order query for HPOS compatibility.
	$order_ids = wc_get_orders(
		array(
			'limit'        => 1,
			'return'       => 'ids',
			'meta_key'     => '_transaction_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'   => $payment_key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'meta_compare' => '=',
		)
	);

		if ( ! empty( $order_ids ) ) {
			return absint( $order_ids[0] );
		}

		return false;
	}

	/**
	 * Get order ID by TossPayments order ID stored in order meta.
	 *
	 * @param string $tosspayments_order_id TossPayments order ID.
	 * @return int|false
	 */
	private function get_order_id_by_tosspayments_order_id( $tosspayments_order_id ) {
		if ( '' === $tosspayments_order_id ) {
			return false;
		}

		$order_ids = wc_get_orders(
			array(
				'limit'        => 1,
				'return'       => 'ids',
				'meta_key'     => '_tosspayments_order_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'   => $tosspayments_order_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'meta_compare' => '=',
			)
		);

		if ( ! empty( $order_ids ) ) {
			return absint( $order_ids[0] );
		}

		return false;
	}

	/**
	 * Nonce action string bound to a specific order.
	 *
	 * @param int $order_id Order ID.
	 * @return string
	 */
	private function get_order_nonce_action( $order_id ) {
		return 'seoulcommerce-tpg-order-' . absint( $order_id );
	}

	/**
	 * Verify the current request may access the given order.
	 *
	 * @param WC_Order $order Order object.
	 * @return bool
	 */
	private function verify_order_access( $order ) {
		if ( ! $order ) {
			return false;
		}

		$order_id = $order->get_id();

		if ( is_user_logged_in() ) {
			$customer_id = (int) $order->get_customer_id();
			if ( $customer_id && (int) get_current_user_id() === $customer_id ) {
				return true;
			}
		}

		if ( isset( $_POST['order_key'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$order_key = sanitize_text_field( wp_unslash( $_POST['order_key'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( hash_equals( $order->get_order_key(), $order_key ) ) {
				return true;
			}
		}

		if ( WC()->session ) {
			$session_order_id = absint( WC()->session->get( 'seoulcommerce_tpg_order_id' ) );
			if ( $session_order_id && $session_order_id === $order_id ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Process refund.
	 *
	 * @param int    $order_id Order ID.
	 * @param float  $amount Refund amount.
	 * @param string $reason Refund reason.
	 * @return bool|WP_Error
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			$this->log( 'Refund failed: Order not found - #' . $order_id );
			return new WP_Error( 'error', __( 'Order not found.', 'seoulcommerce-payment-gateway-for-tosspayments' ) );
		}

		// Get payment key (stored as transaction ID).
		$payment_key = $order->get_transaction_id();

		if ( ! $payment_key ) {
			$this->log( 'Refund failed: Payment key not found for order #' . $order_id );
			return new WP_Error( 'error', __( 'Payment key not found. This order cannot be refunded via TossPayments.', 'seoulcommerce-payment-gateway-for-tosspayments' ) );
		}

		// Get current payment status from TossPayments to verify refundable amount.
		$payment_data = $this->api->get_payment( $payment_key );
		if ( is_wp_error( $payment_data ) ) {
			$error_message = $payment_data->get_error_message();
			$this->log( 'Refund failed: Could not retrieve payment status - ' . $error_message );
			return new WP_Error( 'error', sprintf(
				/* translators: %s: Error message */
				__( 'Could not verify payment status. %s', 'seoulcommerce-payment-gateway-for-tosspayments' ),
				$error_message
			) );
		}

		// Verify payment is in a refundable state.
		$payment_status = isset( $payment_data['status'] ) ? strtoupper( $payment_data['status'] ) : '';
		if ( ! in_array( $payment_status, array( 'DONE', 'PARTIAL_CANCELED' ), true ) ) {
			$this->log( 'Refund failed: Payment status is ' . $payment_status . ', not refundable' );
			return new WP_Error( 'error', sprintf(
				/* translators: %s: Payment status */
				__( 'This payment cannot be refunded. Current status: %s', 'seoulcommerce-payment-gateway-for-tosspayments' ),
				$payment_status
			) );
		}

		// Get remaining cancelable amount from TossPayments.
		$balance_amount = isset( $payment_data['balanceAmount'] ) ? floatval( $payment_data['balanceAmount'] ) : 0;
		$total_amount = isset( $payment_data['totalAmount'] ) ? floatval( $payment_data['totalAmount'] ) : floatval( $order->get_total() );
		$canceled_amount = isset( $payment_data['canceledAmount'] ) ? floatval( $payment_data['canceledAmount'] ) : 0;

		$this->log( sprintf(
			'Payment status for order #%s: Status=%s, Total=%s, Canceled=%s, Balance=%s',
			$order_id,
			$payment_status,
			$total_amount,
			$canceled_amount,
			$balance_amount
		) );

		// TossPayments requires a refund reason.
		if ( empty( $reason ) ) {
			$reason = __( 'Refund requested', 'seoulcommerce-payment-gateway-for-tosspayments' );
		}

		// Determine refund amount.
		$order_total = floatval( $order->get_total() );
		$refund_amount = null !== $amount ? floatval( $amount ) : $balance_amount;

		// Validate refund amount.
		if ( $refund_amount <= 0 ) {
			$this->log( 'Refund failed: Invalid refund amount - ' . $refund_amount );
			return new WP_Error( 'error', __( 'Invalid refund amount.', 'seoulcommerce-payment-gateway-for-tosspayments' ) );
		}

		// Check if refund amount exceeds remaining balance (with small tolerance for rounding).
		if ( $refund_amount > $balance_amount + 0.01 ) {
			$this->log( sprintf(
				'Refund failed: Refund amount (%s) exceeds remaining balance (%s)',
				$refund_amount,
				$balance_amount
			) );
			return new WP_Error( 'error', sprintf(
				/* translators: %s: Remaining balance amount */
				__( 'Refund amount exceeds the remaining cancelable balance of %s.', 'seoulcommerce-payment-gateway-for-tosspayments' ),
				wc_price( $balance_amount, array( 'currency' => $order->get_currency() ) )
			) );
		}

		// Determine if this will be a full refund (based on remaining balance).
		$will_be_full_refund = ( abs( $refund_amount - $balance_amount ) < 0.01 );

		// Generate deterministic idempotency key based on stable factors.
		// This ensures identical refund attempts use the same key.
		$idempotency_key = 'wc-refund-' . $order_id . '-' . md5( $payment_key . '-' . $refund_amount . '-' . $canceled_amount );

		$this->log( sprintf(
			'Processing %s refund for order #%s. Amount: %s (Balance: %s), Reason: %s, IdempotencyKey: %s',
			$will_be_full_refund ? 'FULL' : 'PARTIAL',
			$order_id,
			$refund_amount,
			$balance_amount,
			$reason,
			$idempotency_key
		) );

		// Call TossPayments API to cancel/refund payment.
		// For full refund (remaining balance), pass null to cancel all.
		// For partial refund, pass the specific amount.
		$cancel_amount = $will_be_full_refund ? null : $refund_amount;
		$result = $this->api->cancel_payment( $payment_key, $cancel_amount, $reason, $idempotency_key );

		if ( is_wp_error( $result ) ) {
			$error_message = $result->get_error_message();
			$this->log( 'Refund failed: ' . $error_message );
			
			// Add order note about failed refund.
			$order->add_order_note(
				sprintf(
					/* translators: 1: Refund amount, 2: Error message */
					__( 'Refund attempt failed for %1$s. Error: %2$s', 'seoulcommerce-payment-gateway-for-tosspayments' ),
					wc_price( $refund_amount, array( 'currency' => $order->get_currency() ) ),
					$error_message
				)
			);
			
			return new WP_Error( 'error', $error_message );
		}

		// Success! Store the transactionKey from the cancel response for webhook reconciliation.
		if ( isset( $result['transactionKey'] ) ) {
			$known_cancel_keys = $order->get_meta( '_tosspayments_cancel_keys' );
			if ( ! is_array( $known_cancel_keys ) ) {
				$known_cancel_keys = array();
			}
			$known_cancel_keys[] = $result['transactionKey'];
			$order->update_meta_data( '_tosspayments_cancel_keys', $known_cancel_keys );
		}

		// Store refund metadata to help track webhook events.
		$order->update_meta_data( '_tosspayments_last_refund_time', time() );
		$order->save();

		$this->log( sprintf(
			'Refund successful for order #%s. Type: %s, Amount: %s, TransactionKey: %s',
			$order_id,
			$will_be_full_refund ? 'FULL' : 'PARTIAL',
			$refund_amount,
			isset( $result['transactionKey'] ) ? $result['transactionKey'] : 'N/A'
		) );
		
		$order->add_order_note(
			sprintf(
				/* translators: 1: Refund type, 2: Refund amount, 3: Reason */
				__( '%1$s refund of %2$s processed successfully via TossPayments. Reason: %3$s', 'seoulcommerce-payment-gateway-for-tosspayments' ),
				$will_be_full_refund ? __( 'Full', 'seoulcommerce-payment-gateway-for-tosspayments' ) : __( 'Partial', 'seoulcommerce-payment-gateway-for-tosspayments' ),
				wc_price( $refund_amount, array( 'currency' => $order->get_currency() ) ),
				$reason
			)
		);

		return true;
	}

	/**
	 * Log message.
	 *
	 * @param string $message Log message.
	 */
	public function log( $message ) {
		if ( $this->debug ) {
			$logger = wc_get_logger();
			$logger->debug( $message, array( 'source' => 'tosspayments' ) );
		}
	}

	/**
	 * AJAX handler to get order details for blocks checkout.
	 */
	public function ajax_get_order_details() {
		if ( ! isset( $_POST['order_id'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Order ID missing', 'seoulcommerce-payment-gateway-for-tosspayments' ) ) );
		}

		$order_id = absint( $_POST['order_id'] );
		$order    = wc_get_order( $order_id );

		if ( ! $order ) {
			wp_send_json_error( array( 'message' => __( 'Order not found', 'seoulcommerce-payment-gateway-for-tosspayments' ) ) );
		}

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), $this->get_order_nonce_action( $order_id ) ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed', 'seoulcommerce-payment-gateway-for-tosspayments' ) ) );
		}

		if ( ! $this->verify_order_access( $order ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'seoulcommerce-payment-gateway-for-tosspayments' ) ) );
		}

	// Build response data.
	/* translators: %s: Order number */
	$order_name_text = sprintf( __( 'Order #%s', 'seoulcommerce-payment-gateway-for-tosspayments' ), $order->get_order_number() );
	
	$data = array(
		'order_id'        => $order->get_id(),
		'amount'          => $order->get_total(),
		'order_name'      => $order_name_text,
		'customer_email'  => $order->get_billing_email(),
		'customer_name'   => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
		'customer_phone'  => $order->get_billing_phone(),
		'return_url'      => add_query_arg( 'wc-api', 'seoulcommerce_tpg_return', home_url( '/' ) ),
	);

		wp_send_json_success( $data );
	}
}

