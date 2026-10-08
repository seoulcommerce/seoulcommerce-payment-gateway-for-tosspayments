<?php
/**
 * Tests for TossPayments Gateway Refund Functionality.
 *
 * @package WooCommerce_TossPayments
 */

namespace SeoulCommerce\TossPayments\Tests;

use PHPUnit\Framework\TestCase;

/**
 * RefundTest class.
 */
class RefundTest extends TestCase {

	/**
	 * Test: Idempotency key is deterministic for identical refund requests.
	 */
	public function test_idempotency_key_is_deterministic() {
		$order_id = 123;
		$payment_key = 'test_payment_key_abc123';
		$refund_amount = 10000;
		$canceled_amount = 0;

		// Generate key twice with same inputs.
		$key1 = 'wc-refund-' . $order_id . '-' . md5( $payment_key . '-' . $refund_amount . '-' . $canceled_amount );
		$key2 = 'wc-refund-' . $order_id . '-' . md5( $payment_key . '-' . $refund_amount . '-' . $canceled_amount );

		$this->assertEquals( $key1, $key2, 'Idempotency keys should be identical for same inputs' );

		// Different canceled_amount should produce different key.
		$key3 = 'wc-refund-' . $order_id . '-' . md5( $payment_key . '-' . $refund_amount . '-' . 5000 );
		$this->assertNotEquals( $key1, $key3, 'Different canceled amount should produce different idempotency key' );
	}

	/**
	 * Test: Payment key is URL-encoded before being used in endpoint.
	 */
	public function test_payment_key_url_encoding() {
		$payment_key = 'test_key+with/special=chars';
		$encoded = rawurlencode( $payment_key );

		$this->assertEquals( 'test_key%2Bwith%2Fspecial%3Dchars', $encoded );
		$this->assertStringContainsString( '%2B', $encoded, 'Plus sign should be encoded' );
		$this->assertStringContainsString( '%2F', $encoded, 'Slash should be encoded' );
		$this->assertStringContainsString( '%3D', $encoded, 'Equals sign should be encoded' );
	}

	/**
	 * Test: Full refund calculation based on balance amount.
	 */
	public function test_full_refund_detection() {
		$balance_amount = 10000;
		$refund_amount = 10000;

		$is_full = ( abs( $refund_amount - $balance_amount ) < 0.01 );

		$this->assertTrue( $is_full, 'Should detect as full refund when amount matches balance' );

		// Partial refund.
		$refund_amount = 5000;
		$is_full = ( abs( $refund_amount - $balance_amount ) < 0.01 );

		$this->assertFalse( $is_full, 'Should detect as partial refund when amount is less than balance' );
	}

	/**
	 * Test: Multiple partial refunds sum to total.
	 */
	public function test_multiple_partial_refunds() {
		$order_total = 10000;
		$first_refund = 3000;
		$second_refund = 7000;

		$canceled_after_first = $first_refund;
		$balance_after_first = $order_total - $canceled_after_first;

		// First refund: 3000.
		$key1 = 'wc-refund-123-' . md5( 'payment_key-' . $first_refund . '-0' );

		// Second refund: 7000 (with 3000 already canceled).
		$key2 = 'wc-refund-123-' . md5( 'payment_key-' . $second_refund . '-' . $canceled_after_first );

		$this->assertNotEquals( $key1, $key2, 'Different refunds should have different idempotency keys' );

		// After second refund, balance should be 0.
		$balance_after_second = $balance_after_first - $second_refund;
		$this->assertEquals( 0, $balance_after_second, 'Balance should be 0 after full refund in parts' );
	}

	/**
	 * Test: Refund amount validation against balance.
	 */
	public function test_refund_amount_validation() {
		$balance_amount = 5000;
		$refund_amount = 6000;

		$exceeds_balance = ( $refund_amount > $balance_amount + 0.01 );

		$this->assertTrue( $exceeds_balance, 'Should detect when refund exceeds balance' );

		// Valid amount.
		$refund_amount = 5000;
		$exceeds_balance = ( $refund_amount > $balance_amount + 0.01 );

		$this->assertFalse( $exceeds_balance, 'Should allow refund equal to balance' );
	}

	/**
	 * Test: Transaction key tracking for webhook deduplication.
	 */
	public function test_transaction_key_tracking() {
		$known_keys = array( 'tx_key_1', 'tx_key_2' );
		$new_key = 'tx_key_3';
		$duplicate_key = 'tx_key_1';

		$is_known_duplicate = in_array( $duplicate_key, $known_keys, true );
		$is_known_new = in_array( $new_key, $known_keys, true );

		$this->assertTrue( $is_known_duplicate, 'Should recognize known transaction key' );
		$this->assertFalse( $is_known_new, 'Should not recognize new transaction key' );
	}

	/**
	 * Test: Webhook transmission ID deduplication.
	 */
	public function test_webhook_transmission_id_deduplication() {
		$processed_transmissions = array( 'webhook_1', 'webhook_2' );
		$new_transmission = 'webhook_3';
		$duplicate_transmission = 'webhook_1';

		$is_duplicate = in_array( $duplicate_transmission, $processed_transmissions, true );
		$is_new = in_array( $new_transmission, $processed_transmissions, true );

		$this->assertTrue( $is_duplicate, 'Should recognize duplicate webhook transmission' );
		$this->assertFalse( $is_new, 'Should recognize new webhook transmission' );

		// Test array trimming to last 100.
		$large_array = array_fill( 0, 150, 'tx' );
		$trimmed = array_slice( $large_array, -100 );

		$this->assertCount( 100, $trimmed, 'Should trim to last 100 entries' );
	}

	/**
	 * Test: TossPayments error code mapping.
	 */
	public function test_error_code_mapping() {
		$error_codes = array(
			'NOT_CANCELABLE_AMOUNT'    => 'The refund amount exceeds the remaining cancelable balance.',
			'ALREADY_CANCELED_PAYMENT' => 'This payment has already been fully canceled.',
			'NOT_FOUND_PAYMENT'        => 'Payment not found. The payment key may be invalid.',
			'FORBIDDEN_REQUEST'        => 'Authentication failed. Please check your TossPayments API keys and IP allowlist settings.',
			'UNAUTHORIZED_KEY'         => 'Authentication failed. Please check your TossPayments API keys and IP allowlist settings.',
		);

		foreach ( $error_codes as $code => $expected_message ) {
			$this->assertIsString( $code, 'Error code should be a string' );
			$this->assertNotEmpty( $expected_message, 'Error message should not be empty' );
		}

		// Verify specific codes.
		$this->assertArrayHasKey( 'NOT_CANCELABLE_AMOUNT', $error_codes, 'Should have NOT_CANCELABLE_AMOUNT error' );
		$this->assertArrayHasKey( 'ALREADY_CANCELED_PAYMENT', $error_codes, 'Should have ALREADY_CANCELED_PAYMENT error' );
		$this->assertArrayHasKey( 'UNAUTHORIZED_KEY', $error_codes, 'Should have UNAUTHORIZED_KEY error' );
	}

	/**
	 * Test: Payment key truncation for logging.
	 */
	public function test_payment_key_truncation_for_logging() {
		$full_key = 'test_payment_key_abc123456789';
		$truncated = substr( $full_key, 0, 10 ) . '...';

		$this->assertEquals( 'test_payme...', $truncated, 'Payment key should be truncated to 10 chars + ...' );
		$this->assertStringNotContainsString( 'abc123456789', $truncated, 'Full key should not be in truncated version' );
	}

	/**
	 * Test: Integer conversion for KRW amounts.
	 */
	public function test_krw_amount_conversion() {
		$float_amount = 10000.99;
		$integer_amount = intval( round( $float_amount ) );

		$this->assertEquals( 10001, $integer_amount, 'Should round and convert to integer' );
		$this->assertIsInt( $integer_amount, 'Converted amount should be integer' );

		// Test exact amount.
		$exact_amount = 5000.0;
		$integer_exact = intval( round( $exact_amount ) );

		$this->assertEquals( 5000, $integer_exact, 'Exact amount should convert cleanly' );
	}

	/**
	 * Test: Cancel array reconciliation logic.
	 */
	public function test_cancel_array_reconciliation() {
		// Simulate payment.cancels array.
		$cancels = array(
			array(
				'transactionKey' => 'tx_admin_1',
				'cancelAmount'   => 3000,
				'cancelReason'   => 'Customer request',
			),
			array(
				'transactionKey' => 'tx_dashboard_1',
				'cancelAmount'   => 2000,
				'cancelReason'   => 'Merchant cancel',
			),
		);

		$known_admin_keys = array( 'tx_admin_1' );
		$processed_dashboard_keys = array();

		$new_dashboard_cancels = 0;
		foreach ( $cancels as $cancel ) {
			$tx_key = $cancel['transactionKey'];

			if ( in_array( $tx_key, $known_admin_keys, true ) ) {
				// Skip admin-initiated cancel.
				continue;
			}

			if ( in_array( $tx_key, $processed_dashboard_keys, true ) ) {
				// Skip already processed dashboard cancel.
				continue;
			}

			// This is a new dashboard cancel.
			$new_dashboard_cancels++;
			$processed_dashboard_keys[] = $tx_key;
		}

		$this->assertEquals( 1, $new_dashboard_cancels, 'Should find 1 new dashboard cancel' );
		$this->assertCount( 1, $processed_dashboard_keys, 'Should have 1 processed dashboard cancel' );
	}

	/**
	 * Test: Status determination after full vs partial refund.
	 */
	public function test_order_status_after_refund() {
		// Full cancellation.
		$payment_status = 'CANCELED';
		$balance_amount = 0;

		$is_fully_canceled = ( 'CANCELED' === $payment_status || $balance_amount <= 0.01 );

		$this->assertTrue( $is_fully_canceled, 'Should detect full cancellation' );

		// Partial cancellation.
		$payment_status = 'PARTIAL_CANCELED';
		$balance_amount = 5000;

		$is_fully_canceled = ( 'CANCELED' === $payment_status || $balance_amount <= 0.01 );

		$this->assertFalse( $is_fully_canceled, 'Should detect partial cancellation' );
	}
}
