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
https://github.com/seoulcommerce/tosspayments-gateway-for-woocommerce/issues

## License

GPL v2 or later

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a list of changes.

