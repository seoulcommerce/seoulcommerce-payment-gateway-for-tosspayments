<?php
/**
 * Tests for webhook conflict handling, confirm recovery, and secret resolution.
 *
 * @package WooCommerce_TossPayments
 */

namespace SeoulCommerce\TossPayments\Tests;

use PHPUnit\Framework\TestCase;
use WP_Error;

require_once __DIR__ . '/../includes/class-wc-tosspayments-api.php';
require_once __DIR__ . '/../includes/class-wc-tosspayments-gateway.php';

/**
 * ReconciliationTest class.
 */
class ReconciliationTest extends TestCase {

	private $gateway;

	protected function setUp(): void {
		parent::setUp();

		$GLOBALS['__test_orders']          = array();
		$GLOBALS['__test_http_queue']      = array();
		$GLOBALS['__test_http_log']        = array();
		$GLOBALS['__test_refund_calls']    = array();
		$GLOBALS['__test_logger_messages'] = array();

		$this->gateway        = new \SeoulCommerce_TPG_Gateway();
		$this->gateway->debug = true;
	}

	private function queue_http_response( $code, $body ) {
		$GLOBALS['__test_http_queue'][] = array(
			'response' => array( 'code' => $code ),
			'body'     => json_encode( $body ),
		);
	}

	private function invoke_private( $method_name, array $args = array() ) {
		$reflection = new \ReflectionClass( $this->gateway );
		$method     = $reflection->getMethod( $method_name );
		$method->setAccessible( true );
		return $method->invokeArgs( $this->gateway, $args );
	}

	/**
	 * A locally cancelled unpaid order must not be restored to paid by a DONE webhook.
	 */
	public function test_cancelled_order_not_restored_by_done_webhook() {
		$order             = new \FakeOrder( 123, 10000, '' );
		$order->status     = 'cancelled';
		$order->date_paid  = false;
		$order->update_meta_data( '_tosspayments_order_id', 'order123' );
		$GLOBALS['__test_orders'][123] = $order;

		$this->queue_http_response(
			200,
			array(
				'status'      => 'DONE',
				'totalAmount' => 10000,
				'orderId'     => 'order123',
			)
		);
		$this->queue_http_response( 200, array( 'transactionKey' => 'tx_late_cancel' ) );

		$webhook_data = array(
			'eventType' => 'PAYMENT_STATUS_CHANGED',
			'data'      => array(
				'paymentKey' => 'test_pk',
				'orderId'    => 'order123',
				'status'     => 'DONE',
			),
		);

		$this->invoke_private( 'handle_payment_confirmed', array( $webhook_data ) );

		$this->assertEquals( 'cancelled', $order->status, 'Cancelled order must not be restored to paid' );
		$this->assertFalse( $order->is_paid() );
		$this->assertStringContainsString( '/cancel', $GLOBALS['__test_http_log'][1]['url'] );
		$this->assertNotEmpty( $order->notes );
	}

	/**
	 * Already-paid orders ignore a delayed DONE webhook without changing status.
	 */
	public function test_paid_order_done_webhook_is_noop() {
		$order         = new \FakeOrder( 123, 10000, 'test_pk' );
		$order->status = 'processing';
		$GLOBALS['__test_orders'][123] = $order;

		$this->queue_http_response(
			200,
			array(
				'status'      => 'DONE',
				'totalAmount' => 10000,
				'orderId'     => 'order123',
			)
		);

		$this->invoke_private(
			'handle_payment_confirmed',
			array(
				array(
					'eventType' => 'PAYMENT_CONFIRMED',
					'data'      => array( 'paymentKey' => 'test_pk' ),
				),
			)
		);

		$this->assertEquals( 'processing', $order->status );
		$this->assertCount( 1, $GLOBALS['__test_http_log'], 'Paid order should only re-query payment, not cancel' );
	}

	/**
	 * PAYMENT_STATUS_CHANGED with verified CANCELED must not mark the order paid.
	 */
	public function test_status_changed_canceled_does_not_mark_paid() {
		$order         = new \FakeOrder( 123, 10000, 'test_pk' );
		$order->status = 'processing';
		$GLOBALS['__test_orders'][123] = $order;

		$this->queue_http_response(
			200,
			array(
				'status'          => 'CANCELED',
				'totalAmount'     => 10000,
				'canceledAmount'  => 10000,
				'balanceAmount'   => 0,
				'orderId'         => 'order123',
				'cancels'         => array(
					array(
						'transactionKey' => 'tx_dashboard_full',
						'cancelAmount'   => 10000,
						'cancelReason'   => 'Dashboard cancel',
					),
				),
			)
		);

		$this->invoke_private(
			'dispatch_payment_status_changed',
			array(
				array(
					'eventType' => 'PAYMENT_STATUS_CHANGED',
					'data'      => array(
						'paymentKey' => 'test_pk',
						'status'     => 'CANCELED',
					),
				),
			)
		);

		$this->assertEquals( 'refunded', $order->status );
		$this->assertCount( 1, $GLOBALS['__test_refund_calls'] );
	}

	/**
	 * Confirm requests send a deterministic Idempotency-Key.
	 */
	public function test_confirm_sends_idempotency_key() {
		$order = new \FakeOrder( 123, 10000 );
		$GLOBALS['__test_orders'][123] = $order;

		$this->queue_http_response(
			200,
			array(
				'status'      => 'DONE',
				'totalAmount' => 10000,
			)
		);

		$result = $this->gateway->confirm_payment_for_order( $order, 'test_pk_abc', 10000 );

		$this->assertIsArray( $result );
		$confirm = $GLOBALS['__test_http_log'][0];
		$this->assertEquals( 'wc-confirm-123-' . md5( 'test_pk_abc' ), $confirm['headers']['Idempotency-Key'] );
		$body = json_decode( $confirm['body'], true );
		$this->assertEquals( 10000, $body['amount'] );
		$this->assertEquals( 'order123', $body['orderId'] );
	}

	/**
	 * A timed-out confirm is recovered when GET payment returns DONE.
	 */
	public function test_confirm_timeout_recovers_when_payment_is_done() {
		$order = new \FakeOrder( 123, 10000 );
		$GLOBALS['__test_orders'][123] = $order;

		$GLOBALS['__test_http_queue'][] = new WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out' );
		$this->queue_http_response(
			200,
			array(
				'status'      => 'DONE',
				'totalAmount' => 10000,
			)
		);

		$result = $this->gateway->confirm_payment_for_order( $order, 'test_pk_abc', 10000 );

		$this->assertIsArray( $result );
		$this->assertEquals( 'DONE', $result['status'] );
		$this->assertCount( 2, $GLOBALS['__test_http_log'] );
	}

	/**
	 * ALREADY_PROCESSED_PAYMENT is treated as success when GET payment is DONE.
	 */
	public function test_already_processed_payment_recovers_when_done() {
		$order = new \FakeOrder( 123, 10000 );
		$GLOBALS['__test_orders'][123] = $order;

		$this->queue_http_response(
			400,
			array(
				'code'    => 'ALREADY_PROCESSED_PAYMENT',
				'message' => 'This is a payment that has already been processed.',
			)
		);
		$this->queue_http_response(
			200,
			array(
				'status'      => 'DONE',
				'totalAmount' => 10000,
			)
		);

		$result = $this->gateway->confirm_payment_for_order( $order, 'test_pk_abc', 10000 );

		$this->assertIsArray( $result );
		$this->assertEquals( 'DONE', $result['status'] );
	}

	/**
	 * After timeout, IN_PROGRESS retries confirm with the same idempotency key.
	 */
	public function test_confirm_timeout_retries_in_progress_with_same_key() {
		$order = new \FakeOrder( 123, 10000 );
		$GLOBALS['__test_orders'][123] = $order;

		$GLOBALS['__test_http_queue'][] = new WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out' );
		$this->queue_http_response(
			200,
			array(
				'status' => 'IN_PROGRESS',
			)
		);
		$this->queue_http_response(
			200,
			array(
				'status'      => 'DONE',
				'totalAmount' => 10000,
			)
		);

		$result = $this->gateway->confirm_payment_for_order( $order, 'test_pk_abc', 10000 );

		$this->assertIsArray( $result );
		$expected_key = 'wc-confirm-123-' . md5( 'test_pk_abc' );
		$this->assertEquals( $expected_key, $GLOBALS['__test_http_log'][0]['headers']['Idempotency-Key'] );
		$this->assertEquals( $expected_key, $GLOBALS['__test_http_log'][2]['headers']['Idempotency-Key'] );
	}

	/**
	 * A genuine card decline is not recovered via lookup.
	 */
	public function test_card_decline_is_not_recovered() {
		$order = new \FakeOrder( 123, 10000 );
		$GLOBALS['__test_orders'][123] = $order;

		$this->queue_http_response(
			403,
			array(
				'code'    => 'REJECT_CARD_PAYMENT',
				'message' => 'Payment failed due to limit exceeded or insufficient balance.',
			)
		);

		$result = $this->gateway->confirm_payment_for_order( $order, 'test_pk_abc', 10000 );

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertCount( 1, $GLOBALS['__test_http_log'], 'Non-recoverable confirm errors must not trigger a lookup' );
	}

	/**
	 * Environment variables override database secret keys.
	 */
	public function test_secret_key_env_overrides_db() {
		$previous = getenv( 'SEOULCOMMERCE_TPG_SECRET_KEY_LIVE' );
		putenv( 'SEOULCOMMERCE_TPG_SECRET_KEY_LIVE=live_sk_from_env_value' );

		$resolved = \SeoulCommerce_TPG_Gateway::resolve_secret_key( 'live', 'db_secret_value' );
		$this->assertEquals( 'live_sk_from_env_value', $resolved );

		if ( false === $previous ) {
			putenv( 'SEOULCOMMERCE_TPG_SECRET_KEY_LIVE' );
		} else {
			putenv( 'SEOULCOMMERCE_TPG_SECRET_KEY_LIVE=' . $previous );
		}
	}

	/**
	 * Database secret is used when no constant or environment value is set.
	 */
	public function test_secret_key_falls_back_to_db() {
		$previous = getenv( 'SEOULCOMMERCE_TPG_SECRET_KEY_TEST' );
		putenv( 'SEOULCOMMERCE_TPG_SECRET_KEY_TEST' );

		$resolved = \SeoulCommerce_TPG_Gateway::resolve_secret_key( 'test', 'db_test_secret' );
		$this->assertEquals( 'db_test_secret', $resolved );

		if ( false !== $previous ) {
			putenv( 'SEOULCOMMERCE_TPG_SECRET_KEY_TEST=' . $previous );
		}
	}
}
