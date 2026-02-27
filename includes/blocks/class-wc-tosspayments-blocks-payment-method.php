<?php
/**
 * TossPayments Blocks Payment Method.
 *
 * @package WooCommerce_TossPayments
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Ensure WooCommerce Blocks is available.
if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
	return;
}

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * SeoulCommerce_TPG_Blocks_Payment_Method class.
 */
final class SeoulCommerce_TPG_Blocks_Payment_Method extends AbstractPaymentMethodType {

	/**
	 * Payment method name (must match gateway id).
	 *
	 * @var string
	 */
	protected $name = 'tosspayments';

	/**
	 * Settings array from gateway options.
	 *
	 * @var array
	 */
	protected $settings = array();

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_tosspayments_settings', array() );
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		$enabled = isset( $this->settings['enabled'] ) ? $this->settings['enabled'] : 'no';
		$testmode = isset( $this->settings['testmode'] ) && 'yes' === $this->settings['testmode'];
		$client_key = $testmode 
			? ( isset( $this->settings['client_key_test'] ) ? $this->settings['client_key_test'] : '' )
			: ( isset( $this->settings['client_key_live'] ) ? $this->settings['client_key_live'] : '' );
		$secret_key = $testmode 
			? ( isset( $this->settings['secret_key_test'] ) ? $this->settings['secret_key_test'] : '' )
			: ( isset( $this->settings['secret_key_live'] ) ? $this->settings['secret_key_live'] : '' );
		
		// In test mode, allow without keys. In live mode, require keys.
		if ( 'yes' === $enabled ) {
			if ( $testmode ) {
				return true;
			} else {
				return ! empty( $client_key ) && ! empty( $secret_key );
			}
		}
		
		return false;
	}

	/**
	 * Returns an array of script handles to enqueue in the frontend context.
	 *
	 * @return string[]
	 */
	public function get_payment_method_script_handles() {
		// Register TossPayments SDK v2 (standard) first
		wp_register_script(
			'tosspayments-sdk',
			'https://js.tosspayments.com/v2/standard',
			array(),
			'2.0.0',
			false // Load in head, not footer
		);

		$script_asset_path = SEOULCOMMERCE_TPG_PLUGIN_DIR . 'build/blocks/frontend.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => SEOULCOMMERCE_TPG_VERSION,
			);

		$script_url = SEOULCOMMERCE_TPG_PLUGIN_URL . 'build/blocks/frontend.js';

		// Add TossPayments SDK as a dependency
		$dependencies = array_merge( $script_asset['dependencies'], array( 'tosspayments-sdk' ) );

		wp_register_script(
			'seoulcommerce-tpg-blocks',
			$script_url,
			$dependencies,
			$script_asset['version'],
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'seoulcommerce-tpg-blocks', 'seoulcommerce-payment-gateway-for-tosspayments', SEOULCOMMERCE_TPG_PLUGIN_DIR . 'languages' );
		}

		return array( 'seoulcommerce-tpg-blocks' );
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		// Get gateway instance to access methods.
		$gateway = null;
		if ( class_exists( 'SeoulCommerce_TPG_Gateway' ) ) {
			$gateways = WC()->payment_gateways()->payment_gateways();
			if ( isset( $gateways['tosspayments'] ) ) {
				$gateway = $gateways['tosspayments'];
			}
		}

		$client_key = '';
		if ( $gateway && method_exists( $gateway, 'get_client_key' ) ) {
			$client_key = $gateway->get_client_key();
		} else {
			// Fallback: get from settings.
			$testmode = isset( $this->settings['testmode'] ) && 'yes' === $this->settings['testmode'];
			$client_key = $testmode 
				? ( isset( $this->settings['client_key_test'] ) ? $this->settings['client_key_test'] : '' )
				: ( isset( $this->settings['client_key_live'] ) ? $this->settings['client_key_live'] : '' );
		}

		return array(
			'title'       => $this->get_setting( 'title' ),
			'description' => $this->get_setting( 'description' ),
			'supports'    => array( 'products' ),
			'clientKey'   => $client_key,
			'returnUrl'   => add_query_arg( 'wc-api', 'seoulcommerce_tpg_return', home_url( '/' ) ),
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'nonce'       => wp_create_nonce( 'seoulcommerce-tpg' ),
			'icon'        => SEOULCOMMERCE_TPG_PLUGIN_URL . 'assets/TossPayments_Logo_Primary.png',
		);
	}
}

