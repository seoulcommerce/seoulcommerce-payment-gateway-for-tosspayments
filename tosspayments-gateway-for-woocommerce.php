<?php
/**
 * Plugin Name: SeoulCommerce Payment Gateway for TossPayments
 * Plugin URI: https://github.com/seoulcommerce/seoulcommerce-payment-gateway-for-tosspayments
 * Description: TossPayments payment gateway integration for WooCommerce. Supports card payments using TossPayments v2 API.
 * Version: 1.0.0
 * Author: seoulcommerce
 * Author URI: https://seoulcommerce.com
 * Text Domain: seoulcommerce-payment-gateway-for-tosspayments
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 8.0
 * WC tested up to: 9.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package WooCommerce_TossPayments
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define plugin constants (use a unique plugin prefix, not "wc_").
define( 'SEOULCOMMERCE_TPG_VERSION', '1.0.0' );
define( 'SEOULCOMMERCE_TPG_PLUGIN_FILE', __FILE__ );
define( 'SEOULCOMMERCE_TPG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SEOULCOMMERCE_TPG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SEOULCOMMERCE_TPG_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Logging helper function.
 *
 * @param string $message Log message.
 */
function seoulcommerce_tpg_log( $message ) {
	if ( function_exists( 'wc_get_logger' ) ) {
		$logger = wc_get_logger();
		$logger->debug( '[TossPayments] ' . $message, array( 'source' => 'tosspayments' ) );
	}
}

/**
 * Declare High-Performance Order Storage (HPOS) compatibility.
 */
function seoulcommerce_tpg_declare_hpos_compatibility() {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
}
add_action( 'before_woocommerce_init', 'seoulcommerce_tpg_declare_hpos_compatibility' );

/**
 * Check for WooCommerce dependency and initialize the gateway.
 */
function seoulcommerce_tpg_init_gateway() {
	if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'WC' ) ) {
		return;
	}

	// Include gateway classes.
	require_once SEOULCOMMERCE_TPG_PLUGIN_DIR . 'includes/class-wc-tosspayments-api.php';
	require_once SEOULCOMMERCE_TPG_PLUGIN_DIR . 'includes/class-wc-tosspayments-gateway.php';
	require_once SEOULCOMMERCE_TPG_PLUGIN_DIR . 'includes/class-wc-tosspayments-admin-notices.php';

	// Woo Blocks compatibility (Checkout block).
	if ( function_exists( 'register_block_type' ) && class_exists( '\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
		require_once SEOULCOMMERCE_TPG_PLUGIN_DIR . 'includes/blocks/class-wc-tosspayments-blocks-payment-method.php';

		add_action( 'woocommerce_blocks_payment_method_type_registration', function( $registry ) {
			if ( class_exists( 'SeoulCommerce_TPG_Blocks_Payment_Method' ) ) {
				$integration = new SeoulCommerce_TPG_Blocks_Payment_Method();
				$registry->register( $integration );
			}
		} );
	}
}
add_action( 'plugins_loaded', 'seoulcommerce_tpg_init_gateway', 20 );

/**
 * Register the gateway with WooCommerce.
 *
 * @param array $gateways Gateways.
 * @return array
 */
function seoulcommerce_tpg_register_gateway( $gateways ) {
	if ( class_exists( 'SeoulCommerce_TPG_Gateway' ) ) {
		$gateways[] = 'SeoulCommerce_TPG_Gateway';
	}
	return $gateways;
}
add_filter( 'woocommerce_payment_gateways', 'seoulcommerce_tpg_register_gateway' );

/**
 * Admin notice if WooCommerce is missing.
 */
function seoulcommerce_tpg_admin_notices() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'SeoulCommerce Payment Gateway for TossPayments requires WooCommerce to be installed and active.', 'seoulcommerce-payment-gateway-for-tosspayments' ) . '</p></div>';
	}
}
add_action( 'admin_notices', 'seoulcommerce_tpg_admin_notices' );

// Note: WordPress automatically loads translations for plugins hosted on WordPress.org.
// No need to call load_plugin_textdomain() as per WordPress 4.6+ best practices.
