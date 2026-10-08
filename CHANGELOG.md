# Changelog

All notable changes to SeoulCommerce TossPayments will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.3] - Unreleased

### Fixed
- Refund amount validation now uses TossPayments' actual `balanceAmount` instead of comparing against order total.
- Multiple partial refunds that sum to the total now work correctly by using already-refunded amount in idempotency key.
- Webhook handlers for TossPayments cancellations no longer incorrectly change order status or create duplicate refunds.
- Refunds that exceed the remaining cancelable balance now fail with a clear error message.
- Payment keys are now properly URL-encoded in API endpoint paths.
- Full refunds now properly set order status to 'refunded' instead of 'cancelled'.
- Partial refunds now maintain the order in its paid status (processing/completed) as expected.

### Added
- Deterministic idempotency keys for refunds prevent accidental duplicate processing (based on order ID, payment key, amount, and already-refunded total).
- Transaction key tracking: admin refunds store their `transactionKey` to distinguish from dashboard cancels in webhooks.
- Webhook deduplication via `tosspayments-webhook-transmission-id` header (stores last 100 IDs).
- Dashboard cancels now create WooCommerce refund records (`wc_create_refund`) for accurate order totals and reports.
- Webhook reconciliation walks `payment.cancels[]` array to identify dashboard vs admin-initiated cancels.
- Enhanced error messages for TossPayments API failures based on official error codes (https://docs.tosspayments.com/reference/error-codes):
  - `NOT_CANCELABLE_AMOUNT` - Exceeds remaining balance
  - `ALREADY_CANCELED_PAYMENT` - Payment fully canceled
  - `NOT_FOUND_PAYMENT` - Invalid payment key
  - `FORBIDDEN_REQUEST` / `UNAUTHORIZED_KEY` - Auth failures, IP allowlist issues
- Comprehensive PHPUnit test suite (12 tests, 39 assertions) covering idempotency, validation, webhook logic, and error handling.
- GitHub Actions CI workflow running lint and tests on PHP 7.4 and 8.3.

### Improved
- Enhanced API request/response logging that redacts sensitive data (secret keys truncated, card numbers removed, payment keys shortened).
- Webhook cancellation events from TossPayments merchant dashboard are now properly reconciled with WooCommerce order status.
- Partial cancellation webhooks no longer incorrectly flip order status to 'cancelled'.
- Full cancellation webhooks now set order to 'refunded' status appropriately.
- Order notes now clearly distinguish between full and partial refunds with amount details.

## [1.0.2] - 2026-08-28

### Security
- Security improvements to checkout and payment data handling.
- Hardened webhook verification.

## [1.0.1] - 2026-03-05

### Fixed
- Resolved frontend JavaScript variable mismatch that caused `wcTossPaymentsParams is not defined` errors.
- Updated readme/plugin URLs and external-service documentation for WordPress.org review compliance.
- Hardened webhook payload sanitization flow.

## [1.0.0] - 2026-02-27

### Added
- Initial release
- TossPayments v2 API integration
- Card payment support
- WooCommerce checkout blocks compatibility
- Test mode support
- Refund functionality (full and partial refunds from admin)
- Webhook support for payment status updates
- Comprehensive backend configuration options
- WordPress coding standards compliance
- Full localization support
- Complete Korean (ko_KR) translation included
- Translation-ready for other languages (.pot template provided)
- Korean readme file (readme-ko_KR.txt)
- TossPayments logo display on checkout pages
- Merchant signup banner with special affiliate rate promotion
  - Korean-language admin notice for new merchants
  - Smart auto-hide when API keys are configured
  - Dismissible with per-user memory
  - Affiliate tracking via UTM parameters (seoulwd agency code)
  - Responsive design with benefits list and prominent CTA

