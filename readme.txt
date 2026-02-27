=== SeoulCommerce Payment Gateway for TossPayments ===
Contributors: seoulcommerce
Tags: woocommerce, payment gateway, tosspayments, credit card, korea
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
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
* TossPayments merchant account

= Installation =

1. **🎯 IMPORTANT: Sign up for TossPayments with our special affiliate link to get preferential merchant rates:**
   https://onboarding.tosspayments.com/registration/business-registration-number?utm_source=seoulwd&utm_medium=hosting&agencyCode=seoulwd
   (You'll get the lowest transaction fees and special SeoulCommerce benefits!)

2. Upload the plugin files to the `/wp-content/plugins/tosspayments-gateway-for-woocommerce` directory, or install the plugin through the WordPress plugins screen directly.
3. Activate the plugin through the 'Plugins' screen in WordPress.
4. Go to WooCommerce > Settings > Payments and configure TossPayments.
5. Enter your TossPayments API keys (Client Key and Secret Key) from your TossPayments dashboard.
6. Enable test mode for testing or disable for live payments.
7. Save changes and start accepting payments!

= Configuration =

1. **First, sign up for TossPayments using our special link** (if you haven't already):
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
https://github.com/seoulcommerce/tosspayments-gateway-for-woocommerce/issues

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

Yes, the plugin supports full and partial refunds through the WooCommerce order management interface.

= Screenshots =

1. TossPayments settings page in WooCommerce
2. Checkout page with TossPayments payment method
3. TossPayments payment window
4. Order details with payment information
5. Refund interface in order management

== Changelog ==

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

= 1.0.0 =
Initial release of SeoulCommerce TossPayments plugin. Start accepting secure card payments through TossPayments v2 API.

