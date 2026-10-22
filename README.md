# SeoulCommerce TossPayments

A WordPress plugin that integrates TossPayments payment gateway into WooCommerce stores. This plugin supports card payments using TossPayments version 2 API and is fully compatible with WooCommerce checkout blocks.

## Features

- ✅ Card payment support via TossPayments v2 API
- ✅ Full WooCommerce checkout blocks compatibility
- ✅ Test mode for sandbox testing
- ✅ Secure payment processing
- ✅ Full and partial refund support from admin
- ✅ Webhook support for payment status updates
- ✅ Comprehensive backend configuration
- ✅ WordPress coding standards compliant
- ✅ **Full Korean translation** (완전한 한국어 지원)
- ✅ Translation-ready for other languages
- ✅ **Merchant signup banner** - Prominent Korean banner promoting special affiliate rates
- ✅ TossPayments logo display on checkout

## Requirements

- WordPress 6.0 or higher
- WooCommerce 8.0 or higher
- PHP 7.4 or higher
- TossPayments merchant account
https://onboarding.tosspayments.com/registration/business-registration-number?utm_source=seoulwd&utm_medium=hosting&agencyCode=seoulwd

## Installation

1. Upload the plugin files to the `/wp-content/plugins/seoulcommerce-tosspayments` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to WooCommerce > Settings > Payments and configure TossPayments.
4. Enter your TossPayments API keys (Client Key and Secret Key).
5. Enable test mode for testing or disable for live payments.
6. Save changes and start accepting payments!

## Configuration

1. Navigate to **WooCommerce > Settings > Payments**
2. Click on **"TossPayments"** to configure
3. Enable the payment method
4. Enter your TossPayments API credentials:
   - **Test Client Key** (for testing)
   - **Test Secret Key** (for testing)
   - **Live Client Key** (for production)
   - **Live Secret Key** (for production)
5. Configure other settings as needed
6. Save changes

## API Keys

Get your API keys from your TossPayments developer dashboard:
https://developers.tosspayments.com/

Secret keys can stay out of the WordPress database. See [Secret key storage](#secret-key-storage).

## Compatibility

This plugin states **minimum requirements**, not a guarantee that every newer combination has been run in our test matrix.

| | Minimum | Notes |
|---|---|---|
| WordPress | 6.0 | `Tested up to` in `readme.txt` is **6.9**. Newer WordPress releases are expected to work via standard WP/WooCommerce APIs; they are not in the automated matrix. |
| WooCommerce | 8.0 | Plugin header `WC tested up to` is **9.0**. WooCommerce 10/11 (including 11.1.2) is not in the automated matrix. |
| PHP | 7.4 | GitHub Actions runs lint and PHPUnit on **PHP 7.4 and 8.3**. |
| HPOS | Either on or off | Compatible with High-Performance Order Storage **enabled or disabled**. Order lookups use `wc_get_orders()` / `wc_get_order()`, not direct `wp_posts` queries. Compatibility is declared on `before_woocommerce_init`. |

A WordPress 7.1.2 / WooCommerce 11.1.2 / PHP 8.3 / HPOS-disabled stack is **not a formally certified combination**. PHP 8.3 and HPOS-disabled are supported. We have not run an end-to-end store on WP 7.1.2 + WC 11.1.2.

## Payment reconciliation

TossPayments [general payment webhooks have no signature header](https://docs.tosspayments.com/reference/using-api/webhook-events). The plugin never trusts the webhook body for amount or status. It re-queries `GET /v1/payments/{paymentKey}` and acts on the current PG state.

**Conflict rule:** a WooCommerce order in `cancelled`, `refunded`, `failed`, or `trash` is never restored to paid by a later DONE webhook or return URL. If that order is still unpaid and TossPayments reports `DONE`, the plugin leaves the WooCommerce status unchanged and attempts to cancel the late capture at TossPayments so the customer is not charged for a closed order.

Additional webhook rules:

- Duplicate deliveries are ignored using the `tosspayments-webhook-transmission-id` header (last 100 IDs). TossPayments retries up to 7 times when the endpoint does not return HTTP 200.
- `PAYMENT_STATUS_CHANGED` is dispatched from the **verified** PG status, not the event name. `DONE` is handled as a confirmation; `CANCELED` / `PARTIAL_CANCELED` are handled as cancellations. Delayed or out-of-order events therefore follow current PG state.
- Already-paid orders ignore a second DONE event (`is_paid()`).
- Admin refunds store each cancel `transactionKey`. A later cancel webhook for the same key does not create another WooCommerce refund.
- Dashboard cancels create a WooCommerce refund with `refund_payment => false` and are recorded on `_tosspayments_processed_dashboard_cancels` so replays do not double-apply.

## Partial refunds

`PARTIAL_CANCELED` does **not** set the WooCommerce order to `cancelled`.

- Remaining balance comes from TossPayments `balanceAmount`, not the WooCommerce order total.
- Each dashboard cancel in `payment.cancels[]` becomes one WooCommerce refund object unless that `transactionKey` is already known.
- Full cancel (`CANCELED` or `balanceAmount` ≈ 0) sets the order to `refunded`.
- Partial cancel keeps the order in its paid status (`processing` / `completed`).
- Repeated cancel events are skipped by `transactionKey` (admin-initiated and dashboard-processed lists) and by webhook transmission ID.

This is the 1.0.3 behavior. 1.0.2 mapped both `CANCELED` and `PARTIAL_CANCELED` to `cancelled`; do not use 1.0.2 in production.

## Timeouts and idempotency

Confirm (`POST /v1/payments/confirm`) and cancel (`POST /v1/payments/{paymentKey}/cancel`) send TossPayments [`Idempotency-Key`](https://docs.tosspayments.com/en/api-guide) headers. The same key is valid for 15 days and returns the original response.

| Operation | Key formula | Recovery if the HTTP call times out |
|---|---|---|
| Confirm | `wc-confirm-{orderId}-{md5(paymentKey)}` | `GET /v1/payments/{paymentKey}`. `DONE` is treated as success. `IN_PROGRESS` retries confirm once with the **same** key. `ALREADY_PROCESSED_PAYMENT` is recovered the same way. |
| Refund / cancel | `wc-refund-{orderId}-{md5(paymentKey-amount-canceledAmount)}` | Identical retry uses the same key, so TossPayments does not create a second cancel. |
| Late auto-cancel of a closed WooCommerce order | `wc-late-cancel-{orderId}-{md5(paymentKey)}` | Same-key retry. |

Confirm always uses the **server-stored order total**, not the `amount` query parameter from `successUrl`. The return URL still rejects a mismatch before calling confirm.

There is no background job that retries unknown confirms later. Recovery happens on the customer return request (and a later DONE webhook can still complete a still-pending order). If lookup also fails, the customer is sent back to the pay page; the payment is not marked paid.

## Secret key storage

| Location | Client key (`*_ck_*`) | Secret key (`*_sk_*` / `*_gsk_*`) |
|---|---|---|
| WooCommerce settings option `woocommerce_tosspayments_settings` | Yes, if entered in admin | Yes, if entered in admin (password field) |
| `wp-config.php` constants | No | Optional: `SEOULCOMMERCE_TPG_SECRET_KEY_TEST` / `SEOULCOMMERCE_TPG_SECRET_KEY_LIVE` |
| Environment variables of the same names | No | Optional; constants win, then env, then the database |
| Order meta, REST checkout payload, frontend JS | Client key is public by design | No |
| Debug logs | Not logged as a secret | Authorization header is never logged. `live_sk_` / `test_sk_` patterns, virtual-account `secret`, and card numbers are redacted. Payment keys are truncated. |
| Database backups of `wp_options` | Yes, if stored in settings | Yes, if stored in settings |

There is no HashiCorp Vault / AWS Secrets Manager adapter. For stores that must keep secrets out of `wp_options` (and therefore out of ordinary DB backups of that table), define the constants in `wp-config.php` or inject the environment variables, and leave the secret fields empty in WooCommerce settings.

Client keys are sent to the browser (TossPayments JS SDK). Secret keys are used only in server-side `wp_remote_request` calls to `https://api.tosspayments.com/v1`.

Shop managers with `manage_woocommerce` can see gateway settings in wp-admin. WooCommerce REST payment-gateway endpoints can also expose settings to users with that capability; password fields are the supported UI control, not a substitute for `wp-config.php` / env storage.

Follow the TossPayments [security checklist](https://docs.tosspayments.com/reference/using-api/security-checklist): keep secret keys server-only, separate test and live keys, and register an API-key IP allowlist for live keys.

## Development

### Building Blocks Assets

To build the blocks checkout integration:

```bash
npm install
npm run build
```

### Code Standards

This plugin follows WordPress and WooCommerce coding standards. To check your code:

```bash
composer install
composer run phpcs
```

To auto-fix issues:

```bash
composer run phpcbf
```

## Support

For support, please visit:
https://seoulcommerce.com

## License

GPL v2 or later

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a list of changes.
