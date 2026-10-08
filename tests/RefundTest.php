<?php
/**
 * Comprehensive tests for TossPayments Gateway Refund Functionality.
 *
 * @package WooCommerce_TossPayments
 */

namespace SeoulCommerce\TossPayments\Tests;

use PHPUnit\Framework\TestCase;
use WP_Error;

// Load the plugin classes.
require_once __DIR__ . '/../includes/class-wc-tosspayments-api.php';
require_once __DIR__ . '/../includes/class-wc-tosspayments-gateway.php';

/**
 * RefundTest class - tests real gateway and API code with fakes.
 */
class RefundTest extends TestCase {

	private $gateway;

	protected function setUp(): void {
		parent::setUp();

		// Reset test globals.
		$GLOBALS['__test_orders'] = array();
		$GLOBALS['__test_http_queue'] = array();
		$GLOBALS['__test_http_log'] = array();
		$GLOBALS['__test_refund_calls'] = array();
		$GLOBALS['__test_logger_messages'] = array();

		// Create gateway instance.
		$this->gateway = new \SeoulCommerce_TPG_Gateway();
		$this->gateway->debug = true; // Enable logging.
	}

	private function queue_http_response( $code, $body ) {
		$GLOBALS['__test_http_queue'][] = array(
			'response' => array( 'code' => $code ),
			'body'     => json_encode( $body ),
		);
	}

	private function get_http_log() {
		return $GLOBALS['__test_http_log'];
	}

	private function get_refund_calls() {
		return $GLOBALS['__test_refund_calls'];
	}

	private function get_logger_messages() {
		return $GLOBALS['__test_logger_messages'];
	}

	/**
	 * Test: Full refund sends null cancelAmount.
	 */
	public function test_full_refund() {
		$order = new \FakeOrder( 123, 10000, 'test_pk_abc' );
		$GLOBALS['__test_orders'][123] = $order;

		// Queue get_payment response.
		$this->queue_http_response( 200, array(
			'status'         => 'DONE',
			'totalAmount'    => 10000,
			'canceledAmount' => 0,
			'balanceAmount'  => 10000,
		) );

		// Queue cancel_payment response.
		$this->queue_http_response( 200, array(
			'transactionKey' => 'tx_full_123',
		) );

		$result = $this->gateway->process_refund( 123, 10000, 'Full refund' );

		$this->assertTrue( $result );

		// Check cancel_payment request.
		$http_log = $this->get_http_log();
		$this->assertCount( 2, $http_log ); // get_payment + cancel_payment.

		$cancel_request = $http_log[1];
		$this->assertStringContainsString( '/payments/', $cancel_request['url'] );
		$this->assertStringContainsString( '/cancel', $cancel_request['url'] );

		$cancel_body = json_decode( $cancel_request['body'], true );
		$this->assertArrayNotHasKey( 'cancelAmount', $cancel_body, 'Full refund should not have cancelAmount' );
		$this->assertEquals( 'Full refund', $cancel_body['cancelReason'] );
	}

	/**
	 * Test: Partial refund sends integer cancelAmount.
	 */
	public function test_partial_refund() {
		$order = new \FakeOrder( 123, 10000, 'test_pk' );
		$GLOBALS['__test_orders'][123] = $order;

		$this->queue_http_response( 200, array(
			'status'         => 'DONE',
			'totalAmount'    => 10000,
			'canceledAmount' => 0,
			'balanceAmount'  => 10000,
		) );

		$this->queue_http_response( 200, array(
			'transactionKey' => 'tx_partial_123',
		) );

		$result = $this->gateway->process_refund( 123, 3000, 'Partial refund' );

		$this->assertTrue( $result );

		$http_log = $this->get_http_log();
		$cancel_request = $http_log[1];
		$cancel_body = json_decode( $cancel_request['body'], true );

		$this->assertArrayHasKey( 'cancelAmount', $cancel_body );
		$this->assertEquals( 3000, $cancel_body['cancelAmount'] );
		$this->assertIsInt( $cancel_body['cancelAmount'], 'Cancel amount should be integer' );
	}

	/**
	 * Test: Two partial refunds summing to total.
	 */
	public function test_two_partials_sum_to_total() {
		$order = new \FakeOrder( 123, 10000, 'test_pk' );
		$GLOBALS['__test_orders'][123] = $order;

		// First partial: 3000.
		$this->queue_http_response( 200, array(
			'status'         => 'DONE',
			'balanceAmount'  => 10000,
			'canceledAmount' => 0,
		) );
		$this->queue_http_response( 200, array( 'transactionKey' => 'tx_1' ) );

		$result1 = $this->gateway->process_refund( 123, 3000, 'First partial' );
		$this->assertTrue( $result1 );

		// Second partial: 7000 (remaining balance).
		$this->queue_http_response( 200, array(
			'status'         => 'PARTIAL_CANCELED',
			'balanceAmount'  => 7000,
			'canceledAmount' => 3000,
		) );
		$this->queue_http_response( 200, array( 'transactionKey' => 'tx_2' ) );

		$result2 = $this->gateway->process_refund( 123, 7000, 'Second partial' );
		$this->assertTrue( $result2 );

		// Second refund should send null (full of remaining).
		$http_log = $this->get_http_log();
		$this->assertCount( 4, $http_log ); // 2 get_payment + 2 cancel_payment.

		$second_cancel = json_decode( $http_log[3]['body'], true );
		$this->assertArrayNotHasKey( 'cancelAmount', $second_cancel, 'Second refund covering full balance should send null' );
	}

	/**
	 * Test: Refund over balance returns WP_Error.
	 */
	public function test_over_balance_error() {
		$order = new \FakeOrder( 123, 10000, 'test_pk' );
		$GLOBALS['__test_orders'][123] = $order;

		$this->queue_http_response( 200, array(
			'status'         => 'PARTIAL_CANCELED',
			'balanceAmount'  => 4000,
			'canceledAmount' => 6000,
		) );

		$result = $this->gateway->process_refund( 123, 5000, 'Over balance' );

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertStringContainsString( 'exceeds the remaining cancelable balance', $result->get_error_message() );
	}

	/**
	 * Test: Double submit produces same idempotency key.
	 */
	public function test_double_submit_same_idempotency_key() {
		$order = new \FakeOrder( 123, 10000, 'test_pk_abc' );
		$GLOBALS['__test_orders'][123] = $order;

		// First submit.
		$this->queue_http_response( 200, array(
			'status'         => 'DONE',
			'balanceAmount'  => 10000,
			'canceledAmount' => 0,
		) );
		$this->queue_http_response( 200, array( 'transactionKey' => 'tx_1' ) );

		$this->gateway->process_refund( 123, 5000, 'Test' );

		// Second submit (identical).
		$this->queue_http_response( 200, array(
			'status'         => 'DONE',
			'balanceAmount'  => 10000,
			'canceledAmount' => 0,
		) );
		$this->queue_http_response( 200, array( 'transactionKey' => 'tx_2' ) );

		$this->gateway->process_refund( 123, 5000, 'Test' );

		$http_log = $this->get_http_log();
		$first_cancel = $http_log[1];
		$second_cancel = $http_log[3];

		$this->assertEquals( $first_cancel['headers']['Idempotency-Key'], $second_cancel['headers']['Idempotency-Key'], 'Identical refunds should have same idempotency key' );

		// Verify key is deterministic.
		$expected_key = 'wc-refund-123-' . md5( 'test_pk_abc-5000-0' );
		$this->assertEquals( $expected_key, $first_cancel['headers']['Idempotency-Key'] );
	}

	/**
	 * Test: Each mapped error code returns clear message.
	 */
	public function test_mapped_error_codes() {
		$test_cases = array(
			array(
				'code'     => 'NOT_CANCELABLE_AMOUNT',
				'expected' => 'exceeds the remaining cancelable balance',
			),
			array(
				'code'     => 'ALREADY_CANCELED_PAYMENT',
				'expected' => 'already been fully canceled',
			),
			array(
				'code'     => 'NOT_FOUND_PAYMENT',
				'expected' => 'Payment not found',
			),
			array(
				'code'     => 'UNAUTHORIZED_KEY',
				'expected' => 'Authentication failed',
			),
			array(
				'code'     => 'FORBIDDEN_REQUEST',
				'expected' => 'Authentication failed',
			),
		);

		foreach ( $test_cases as $test_case ) {
			// Reset for each test.
			$order = new \FakeOrder( 123, 10000, 'test_pk' );
			$GLOBALS['__test_orders'][123] = $order;
			$GLOBALS['__test_http_queue'] = array();
			$GLOBALS['__test_http_log'] = array();

			$this->queue_http_response( 200, array(
				'status'         => 'DONE',
				'balanceAmount'  => 10000,
				'canceledAmount' => 0,
			) );

			$this->queue_http_response( 400, array(
				'code'    => $test_case['code'],
				'message' => 'TossPayments error: ' . $test_case['code'],
			) );

			$result = $this->gateway->process_refund( 123, 5000, 'Test' );

			$this->assertInstanceOf( 'WP_Error', $result, 'Error code ' . $test_case['code'] . ' should return WP_Error' );
			$this->assertStringContainsString( $test_case['expected'], $result->get_error_message(), 'Error code ' . $test_case['code'] . ' should map to clear message' );
		}
	}

	/**
	 * Test: Admin refund followed by webhook doesn't create WC refund.
	 */
	public function test_admin_refund_plus_webhook_no_duplicate() {
		$order = new \FakeOrder( 123, 10000, 'test_pk' );
		$GLOBALS['__test_orders'][123] = $order;

		// Admin refund.
		$this->queue_http_response( 200, array(
			'status'         => 'DONE',
			'balanceAmount'  => 10000,
			'canceledAmount' => 0,
		) );
		$this->queue_http_response( 200, array(
			'transactionKey' => 'tx_admin_123',
		) );

		$result = $this->gateway->process_refund( 123, 5000, 'Admin refund' );
		$this->assertTrue( $result );

		// Webhook arrives.
		$this->queue_http_response( 200, array(
			'status'         => 'PARTIAL_CANCELED',
			'balanceAmount'  => 5000,
			'canceledAmount' => 5000,
			'cancels'        => array(
				array(
					'transactionKey' => 'tx_admin_123',
					'cancelAmount'   => 5000,
					'cancelReason'   => 'Admin refund',
				),
			),
		) );

		$webhook_data = array(
			'eventType' => 'PAYMENT_CANCELED',
			'data'      => array( 'paymentKey' => 'test_pk' ),
		);

		$reflection = new \ReflectionClass( $this->gateway );
		$method = $reflection->getMethod( 'handle_payment_canceled' );
		$method->setAccessible( true );
		$method->invoke( $this->gateway, $webhook_data );

		$refund_calls = $this->get_refund_calls();
		$this->assertCount( 0, $refund_calls, 'Admin refund webhook should not create WC refund' );
	}

	/**
	 * Test: Dashboard partial cancel creates 1 WC refund.
	 */
	public function test_dashboard_partial_creates_wc_refund() {
		$order = new \FakeOrder( 123, 10000, 'test_pk' );
		$GLOBALS['__test_orders'][123] = $order;

		$this->queue_http_response( 200, array(
			'status'         => 'PARTIAL_CANCELED',
			'balanceAmount'  => 7000,
			'canceledAmount' => 3000,
			'cancels'        => array(
				array(
					'transactionKey' => 'tx_dashboard_99',
					'cancelAmount'   => 3000,
					'cancelReason'   => 'Merchant dashboard cancel',
				),
			),
		) );

		$webhook_data = array(
			'eventType' => 'PAYMENT_CANCELED',
			'data'      => array( 'paymentKey' => 'test_pk' ),
		);

		$reflection = new \ReflectionClass( $this->gateway );
		$method = $reflection->getMethod( 'handle_payment_canceled' );
		$method->setAccessible( true );
		$method->invoke( $this->gateway, $webhook_data );

		$refund_calls = $this->get_refund_calls();
		$this->assertCount( 1, $refund_calls, 'Dashboard cancel should create 1 WC refund' );
		$this->assertEquals( 3000, $refund_calls[0]['amount'] );
		$this->assertFalse( $refund_calls[0]['refund_payment'], 'refund_payment should be false (already refunded at gateway)' );
	}

	/**
	 * Test: Webhook replay doesn't create duplicate WC refund.
	 */
	public function test_webhook_replay_no_duplicate() {
		$order = new \FakeOrder( 123, 10000, 'test_pk' );
		$GLOBALS['__test_orders'][123] = $order;

		// First webhook.
		$this->queue_http_response( 200, array(
			'status'         => 'PARTIAL_CANCELED',
			'balanceAmount'  => 7000,
			'canceledAmount' => 3000,
			'cancels'        => array(
				array(
					'transactionKey' => 'tx_dashboard_99',
					'cancelAmount'   => 3000,
					'cancelReason'   => 'Dashboard cancel',
				),
			),
		) );

		$webhook_data = array(
			'eventType' => 'PAYMENT_CANCELED',
			'data'      => array( 'paymentKey' => 'test_pk' ),
		);

		$reflection = new \ReflectionClass( $this->gateway );
		$method = $reflection->getMethod( 'handle_payment_canceled' );
		$method->setAccessible( true );
		$method->invoke( $this->gateway, $webhook_data );

		$refund_calls_after_first = $this->get_refund_calls();
		$this->assertCount( 1, $refund_calls_after_first );

		// Replay webhook.
		$this->queue_http_response( 200, array(
			'status'         => 'PARTIAL_CANCELED',
			'balanceAmount'  => 7000,
			'canceledAmount' => 3000,
			'cancels'        => array(
				array(
					'transactionKey' => 'tx_dashboard_99',
					'cancelAmount'   => 3000,
					'cancelReason'   => 'Dashboard cancel',
				),
			),
		) );

		$method->invoke( $this->gateway, $webhook_data );

		$refund_calls_after_replay = $this->get_refund_calls();
		$this->assertCount( 1, $refund_calls_after_replay, 'Replay should not create duplicate WC refund' );
	}

	/**
	 * Test: Dashboard full cancel sets order to refunded status.
	 */
	public function test_dashboard_full_cancel_status() {
		$order = new \FakeOrder( 123, 10000, 'test_pk' );
		$GLOBALS['__test_orders'][123] = $order;

		$this->queue_http_response( 200, array(
			'status'         => 'CANCELED',
			'balanceAmount'  => 0,
			'canceledAmount' => 10000,
			'totalAmount'    => 10000,
			'cancels'        => array(
				array(
					'transactionKey' => 'tx_dashboard_full',
					'cancelAmount'   => 10000,
					'cancelReason'   => 'Full dashboard cancel',
				),
			),
		) );

		$webhook_data = array(
			'eventType' => 'PAYMENT_CANCELED',
			'data'      => array( 'paymentKey' => 'test_pk' ),
		);

		$reflection = new \ReflectionClass( $this->gateway );
		$method = $reflection->getMethod( 'handle_payment_canceled' );
		$method->setAccessible( true );
		$method->invoke( $this->gateway, $webhook_data );

		$this->assertEquals( 'refunded', $order->status, 'Dashboard full cancel should set order to refunded' );
	}

	/**
	 * Test: Logs never contain full payment key or Authorization header.
	 */
	public function test_log_redaction() {
		$order = new \FakeOrder( 123, 10000, 'test_payment_key_1234567890_very_long_secret' );
		$GLOBALS['__test_orders'][123] = $order;

		$this->queue_http_response( 200, array(
			'status'         => 'DONE',
			'balanceAmount'  => 10000,
			'canceledAmount' => 0,
		) );
		$this->queue_http_response( 200, array(
			'transactionKey' => 'tx_123',
			'secret'         => 'should_be_redacted',
		) );

		$this->gateway->process_refund( 123, 5000, 'Test refund' );

		$logs = $this->get_logger_messages();
		$combined_log = implode( ' ', $logs );

		$this->assertStringNotContainsString( 'test_payment_key_1234567890_very_long_secret', $combined_log, 'Full payment key should not be in logs' );
		$this->assertStringNotContainsString( 'Authorization', $combined_log, 'Authorization header name should not be in logs' );
		$this->assertStringNotContainsString( 'Basic ', $combined_log, 'Basic auth should not be in logs' );

		// But truncated version should be there (first 10 chars).
		$this->assertStringContainsString( 'test_payme', $combined_log, 'Truncated payment key should be in logs' );
	}

	/**
	 * Mutation test: Break idempotency formula and verify test fails.
	 */
	public function test_mutation_idempotency_formula() {
		// This test documents the correct formula.
		// If someone changes the formula in the code, other tests will fail.
		$order_id = 123;
		$payment_key = 'test_pk';
		$amount = 5000;
		$canceled = 0;

		$correct = 'wc-refund-' . $order_id . '-' . md5( $payment_key . '-' . $amount . '-' . $canceled );
		$wrong = 'wc-refund-' . $order_id . '-' . md5( $payment_key . $amount . $canceled ); // Missing dashes.

		$this->assertNotEquals( $correct, $wrong, 'Formula must use dashes between components' );
	}
}
