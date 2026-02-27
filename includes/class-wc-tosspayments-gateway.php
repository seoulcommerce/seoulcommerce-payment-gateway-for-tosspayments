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
				'nonce'           => wp_create_nonce( 'seoulcommerce-tpg' ),
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
		$body = file_get_contents( 'php://input' );
		$data = json_decode( $body, true );

		if ( empty( $data ) || ! is_array( $data ) ) {
			status_header( 400 );
			exit;
		}

		// Log only minimal safe data; webhook payload is attacker-controlled.
		$event_type_raw = isset( $data['eventType'] ) ? $data['eventType'] : '';
		$event_type     = strtoupper( sanitize_text_field( (string) $event_type_raw ) );
		$this->log( 'Webhook received. eventType=' . $event_type );

		// Verify webhook signature if needed.
		// Process webhook based on event type.
		switch ( $event_type ) {
			case 'PAYMENT_CONFIRMED':
				$this->handle_payment_confirmed( $data );
				break;
			case 'PAYMENT_CANCELED':
				$this->handle_payment_canceled( $data );
				break;
		}

		status_header( 200 );
		exit;
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
		$order_id    = $this->get_order_id_by_payment_key( $payment_key );

		if ( ! $order_id ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
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
		$order_id    = $this->get_order_id_by_payment_key( $payment_key );

		if ( ! $order_id ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$order->update_status( 'cancelled', __( 'Payment canceled via webhook.', 'seoulcommerce-payment-gateway-for-tosspayments' ) );
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
	 * Process refund.
	 *
	 * @param int    $order_id Order ID.
	 * @param float  $amount Refund amount.
	 * @param string $reason Refund reason.
	 * @return bool|WP_Error
	 */
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
			return new WP_Error( 'error', __( 'Payment key not found. Cannot process refund.', 'seoulcommerce-payment-gateway-for-tosspayments' ) );
		}

		// TossPayments requires a refund reason.
		if ( empty( $reason ) ) {
			$reason = __( 'Refund requested', 'seoulcommerce-payment-gateway-for-tosspayments' );
		}

		// Determine if this is a partial or full refund.
		$order_total = floatval( $order->get_total() );
		$refund_amount = null !== $amount ? floatval( $amount ) : $order_total;
		$is_full_refund = ( abs( $refund_amount - $order_total ) < 0.01 );

		$this->log( 
			sprintf( 
				'Processing %s refund for order #%s. Amount: %s (Order Total: %s), Reason: %s, Payment Key: %s',
				$is_full_refund ? 'FULL' : 'PARTIAL',
				$order_id,
				$refund_amount,
				$order_total,
				$reason,
				$payment_key
			)
		);

		// Call TossPayments API to cancel/refund payment.
		$result = $this->api->cancel_payment( $payment_key, $is_full_refund ? null : $refund_amount, $reason );

		if ( is_wp_error( $result ) ) {
			$error_message = $result->get_error_message();
			$this->log( 'Refund failed: ' . $error_message );
			
			// Add order note about failed refund.
			$order->add_order_note(
				sprintf(
					/* translators: 1: Refund amount, 2: Error message */
					__( 'Refund attempt failed for %1$s. Error: %2$s', 'seoulcommerce-payment-gateway-for-tosspayments' ),
					wc_price( $refund_amount ),
					$error_message
				)
			);
			
			return new WP_Error( 'error', $error_message );
		}

		// Success! Add order note.
		$this->log( 'Refund successful for order #' . $order_id );
		
		$order->add_order_note(
			sprintf(
				/* translators: 1: Refund type, 2: Refund amount, 3: Reason */
				__( '%1$s refund of %2$s processed successfully via TossPayments. Reason: %3$s', 'seoulcommerce-payment-gateway-for-tosspayments' ),
				$is_full_refund ? __( 'Full', 'seoulcommerce-payment-gateway-for-tosspayments' ) : __( 'Partial', 'seoulcommerce-payment-gateway-for-tosspayments' ),
				wc_price( $refund_amount ),
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
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'seoulcommerce-tpg' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed', 'seoulcommerce-payment-gateway-for-tosspayments' ) ) );
		}

		// Get order ID.
		if ( ! isset( $_POST['order_id'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Order ID missing', 'seoulcommerce-payment-gateway-for-tosspayments' ) ) );
		}

		$order_id = absint( $_POST['order_id'] );
		$order    = wc_get_order( $order_id );

		if ( ! $order ) {
			wp_send_json_error( array( 'message' => __( 'Order not found', 'seoulcommerce-payment-gateway-for-tosspayments' ) ) );
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

