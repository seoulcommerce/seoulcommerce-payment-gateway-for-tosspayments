=== SeoulCommerce Payment Gateway for TossPayments ===
Contributors: seoulcommerce
Tags: woocommerce, payment gateway, tosspayments, credit card, korea
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.3

License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Accept card payments in WooCommerce using TossPayments v2 API. Secure, fast, and fully compatible with WooCommerce checkout blocks.

== Description ==

SeoulCommerce Payment Gateway for TossPayments integrates TossPayments card payment functionality into your WooCommerce store. This plugin uses the TossPayments version 2 API/SDK and is compatible with WooCommerce checkout blocks.

Note: This plugin is developed by SeoulCommerce.com

= Features =

* Card payment support via TossPayments v2 API
* Full WooCommerce checkout blocks compatibility
* Test mode for sandbox testing
* Secure payment processing
* Refund support
* Webhook support for payment status updates
* Comprehensive backend configuration
* WordPress coding standards compliant

= Requirements =

* WordPress 6.0 or higher
* WooCommerce 8.0 or higher
* PHP 7.4 or higher
* A TossPayments merchant account (required)

= Installation =

1. **🎯 IMPORTANT: A TossPayments merchant account is required before using this plugin.**
   Use the official onboarding link:
   https://onboarding.tosspayments.com/registration/business-registration-number?utm_source=seoulwd&utm_medium=hosting&agencyCode=seoulwd

2. Upload the plugin files to the `/wp-content/plugins/seoulcommerce-payment-gateway-for-tosspayments` directory, or install the plugin through the WordPress plugins screen directly.
3. Activate the plugin through the 'Plugins' screen in WordPress.
4. Go to WooCommerce > Settings > Payments and configure TossPayments.
5. Enter your TossPayments API keys (Client Key and Secret Key) from your TossPayments dashboard.
6. Enable test mode for testing or disable for live payments.
7. Save changes and start accepting payments!

= Configuration =

1. **First, create your TossPayments merchant account (required)**:
   https://onboarding.tosspayments.com/registration/business-registration-number?utm_source=seoulwd&utm_medium=hosting&agencyCode=seoulwd
   
2. Navigate to WooCommerce > Settings > Payments
3. Click on "TossPayments" to configure
4. **You'll see a prominent signup notice at the top** with the special link
5. After signing up, get your API keys from TossPayments dashboard
6. Enter your TossPayments API credentials:
   * Test Client Key (for testing)
   * Test Secret Key (for testing)
   * Live Client Key (for production)
   * Live Secret Key (for production)
7. Configure other settings as needed
8. Save changes

= API Keys =

**STEP 1:** Sign up with our special affiliate link first (get special merchant rates!):
https://onboarding.tosspayments.com/registration/business-registration-number?utm_source=seoulwd&utm_medium=hosting&agencyCode=seoulwd

**STEP 2:** After signup, get your API keys from TossPayments developer dashboard:
https://developers.tosspayments.com/

= Support =

For support, please visit:
https://github.com/seoulcommerce/seoulcommerce-payment-gateway-for-tosspayments/issues

= Source code for generated assets =

This plugin includes generated build assets for WooCommerce Blocks:

* Generated file: `build/blocks/frontend.js`
* Human-readable source: `src/blocks/frontend.js`
* Build config: `webpack.config.js`
* Public source repository: https://github.com/seoulcommerce/seoulcommerce-payment-gateway-for-tosspayments

Build steps:

1. Run `npm install`
2. Run `npm run build`

= External services =

This plugin relies on TossPayments services to process payments.

1. **TossPayments JavaScript SDK** (`https://js.tosspayments.com/v2/standard`)
   * Used to open the payment window during checkout.
   * Data sent when customer starts payment: order ID, order name, amount, customer name/email/phone.
2. **TossPayments Payments API** (`https://api.tosspayments.com/v1`)
   * Used by the server to confirm/cancel payments and process refunds.
   * Data sent from server: payment key, order ID, amount, refund reason (for cancellation/refund).
3. **TossPayments merchant onboarding page** (`https://onboarding.tosspayments.com/registration/business-registration-number?utm_source=seoulwd&utm_medium=hosting&agencyCode=seoulwd`)
   * Used only when merchant/admin clicks signup/onboarding links in plugin settings/banner.

Service provider: TossPayments.

* Terms: https://pages.tosspayments.com/terms/user
* Privacy: https://pages.tosspayments.com/terms/privacy/consent1/en/

= Frequently Asked Questions =

= How do I get TossPayments API keys? =

**IMPORTANT:** First sign up using our special affiliate link to get preferential merchant rates:
https://onboarding.tosspayments.com/registration/business-registration-number?utm_source=seoulwd&utm_medium=hosting&agencyCode=seoulwd

After signup, get your API keys from the TossPayments developer dashboard: https://developers.tosspayments.com/

= Does this plugin support test mode? =

Yes, the plugin includes full test mode support. Enable test mode in the settings to use test API keys.

= Is this plugin compatible with WooCommerce checkout blocks? =

Yes, this plugin is fully compatible with WooCommerce checkout blocks and follows the latest checkout blocks standards.

= Can I process refunds? =

Yes, the plugin supports full and partial refunds through the WooCommerce order management interface. Partial cancellations keep the order in a paid status. Full cancellations set the order to refunded. Version 1.0.2 incorrectly mapped both CANCELED and PARTIAL_CANCELED to cancelled; use 1.0.3 or later.

= Which WordPress / WooCommerce / PHP versions are supported? =

Minimum: WordPress 6.0, WooCommerce 8.0, PHP 7.4. GitHub Actions tests PHP 7.4 and 8.3. HPOS may be enabled or disabled. WordPress 7.x / WooCommerce 11.x are not in the automated matrix; see the GitHub README compatibility section.

= How are cancelled orders protected from a late paid webhook? =

Orders in cancelled, refunded, failed, or trash are never restored to paid. The plugin re-queries TossPayments instead of trusting the webhook body. If TossPayments still reports DONE for an unpaid cancelled order, the plugin leaves WooCommerce unchanged and attempts to cancel that capture.

= Are TossPayments idempotency keys used? =

Yes. Confirm and cancel/refund requests send an Idempotency-Key header. If confirm times out, the plugin looks up GET /v1/payments/{paymentKey} before treating the payment as failed.

= Where are secret keys stored? =

By default in the WooCommerce settings option (wp_options), which is included in database backups. They are not stored in order meta or sent to the browser. You can instead set SEOULCOMMERCE_TPG_SECRET_KEY_TEST and SEOULCOMMERCE_TPG_SECRET_KEY_LIVE in wp-config.php or as environment variables. Debug logs redact secret-key patterns and never log the Authorization header.

= Screenshots =

1. TossPayments settings page in WooCommerce
2. Checkout page with TossPayments payment method
3. TossPayments payment window
4. Order details with payment information
5. Refund interface in order management

== Changelog ==

= 1.0.3 - Unreleased =
* **FIXED**: Locally cancelled, refunded, or failed orders are never restored to paid by a delayed DONE webhook or return URL.
* **FIXED**: PAYMENT_STATUS_CHANGED is now dispatched from the verified TossPayments payment status, not the event name.
* **FIXED**: Confirm uses the server-stored order total and a deterministic Idempotency-Key; timeouts and ALREADY_PROCESSED_PAYMENT are recovered via payment lookup.
* **ADDED**: Optional wp-config.php / environment secret keys (SEOULCOMMERCE_TPG_SECRET_KEY_TEST and SEOULCOMMERCE_TPG_SECRET_KEY_LIVE) so secrets can stay out of wp_options.
* **FIXED**: Refund amount validation now uses TossPayments' actual balanceAmount instead of comparing against order total.
* **FIXED**: Multiple partial refunds that sum to the total now work correctly by using already-refunded amount in idempotency key.
* **FIXED**: Webhook handlers for TossPayments cancellations no longer incorrectly change order status or create duplicate refunds.
* **FIXED**: Refunds that exceed the remaining cancelable balance now fail with a clear error message.
* **FIXED**: Payment keys are now properly URL-encoded in API endpoint paths.
* **FIXED**: Full refunds now properly set order status to 'refunded' instead of 'cancelled'.
* **FIXED**: Partial refunds now maintain the order in its paid status (processing/completed) as expected.
* **ADDED**: Deterministic idempotency keys for refunds prevent accidental duplicate processing.
* **ADDED**: Transaction key tracking to distinguish admin refunds from dashboard cancels in webhooks.
* **ADDED**: Webhook deduplication via tosspayments-webhook-transmission-id header.
* **ADDED**: Dashboard cancels now create WooCommerce refund records for accurate order totals.
* **ADDED**: Webhook reconciliation walks payment.cancels[] array to identify cancel source.
* **ADDED**: Enhanced error messages for TossPayments API failures (NOT_CANCELABLE_AMOUNT, ALREADY_CANCELED_PAYMENT, etc).
* **ADDED**: Comprehensive PHPUnit test suite with GitHub Actions CI for PHP 7.4 and 8.3.
* **IMPROVED**: Enhanced API logging that redacts sensitive data (secret keys, card numbers, payment keys).
* **IMPROVED**: Webhook cancellation events from TossPayments merchant dashboard properly reconciled with WooCommerce.
* **IMPROVED**: Order notes clearly distinguish between full and partial refunds with amount details.

= 1.0.2 - 2026-08-28 =
* Security improvements to checkout and payment data handling.
* Hardened webhook verification.

= 1.0.1 - 2026-03-05 =
* Fixed frontend JavaScript variable mismatch causing `wcTossPaymentsParams is not defined` errors.
* Updated plugin/readme URLs and external-service documentation for WordPress.org compliance.
* Improved webhook payload sanitization handling.

= 1.0.0 - 2025-12-05 =
* Initial release
* TossPayments v2 API integration with latest standards
* Card payment support (credit cards and debit cards)
* Full and partial refund support
* WooCommerce checkout blocks compatibility
* Traditional checkout support
* Test mode for safe testing
* Webhook support for real-time payment updates
* Secure payment processing with proper authentication
* Admin order refund functionality
* Korean phone number formatting
* Order ID sanitization for API compatibility
* Comprehensive error handling and logging
* WordPress coding standards compliant
* HPOS (High-Performance Order Storage) compatible
* Responsive design for mobile devices

== Upgrade Notice ==

= 1.0.2 =
Security release with improvements to checkout and webhook handling.

= 1.0.1 =
Fixes a checkout JavaScript variable mismatch and improves WordPress.org compliance documentation.

= 1.0.0 =
Initial release of SeoulCommerce Payment Gateway for TossPayments. Start accepting secure card payments through TossPayments v2 API.

