<?php
/**
 * Admin Notices Handler
 *
 * @package WooCommerce_TossPayments
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SeoulCommerce_TPG_Admin_Notices class.
 */
class SeoulCommerce_TPG_Admin_Notices {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'show_merchant_signup_banner' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'wp_ajax_seoulcommerce_tpg_dismiss_banner', array( $this, 'dismiss_banner' ) );
	}

	/**
	 * Check if the banner should be shown.
	 *
	 * @return bool
	 */
	private function should_show_banner() {
		// Check if WooCommerce is active first.
		if ( ! class_exists( 'WooCommerce' ) ) {
			return false;
		}

		// Don't show if user has dismissed it.
		if ( get_user_meta( get_current_user_id(), 'seoulcommerce_tpg_banner_dismissed', true ) ) {
			return false;
		}

		// Check if API keys are already configured.
		$gateway_settings = get_option( 'woocommerce_tosspayments_settings', array() );
		$has_test_keys = ! empty( $gateway_settings['client_key_test'] ) && ! empty( $gateway_settings['secret_key_test'] );
		$has_live_keys = ! empty( $gateway_settings['client_key_live'] ) && ! empty( $gateway_settings['secret_key_live'] );

		// If they have keys, assume they're already signed up.
		if ( $has_test_keys || $has_live_keys ) {
			return false;
		}

		// Show on ALL admin pages until dismissed or API keys configured.
		return true;
	}

	/**
	 * Show merchant signup banner.
	 */
	public function show_merchant_signup_banner() {
		if ( ! $this->should_show_banner() ) {
			return;
		}

		$onboarding_url = 'https://onboarding.tosspayments.com/registration/business-registration-number?utm_source=seoulwd&utm_medium=hosting&agencyCode=seoulwd';
		?>
		<div class="notice notice-info is-dismissible tosspayments-merchant-banner">
			<div class="tosspayments-banner-content">
				<div class="tosspayments-banner-icon">
					<img src="<?php echo esc_url( SEOULCOMMERCE_TPG_PLUGIN_URL . 'assets/TossPayments_Logo_Primary.png' ); ?>" alt="TossPayments" />
				</div>
				<div class="tosspayments-banner-text">
					<h2>🎉 토스페이먼츠 가맹점 가입하고 특별 우대 수수료 받으세요!</h2>
					<p>
						<strong>SeoulCommerce 제휴 링크</strong>를 통해 가입하시면 <strong>업계 최저 수수료율</strong>로 결제 서비스를 이용하실 수 있습니다.
					</p>
					<ul class="tosspayments-benefits">
						<li>✅ <strong>특별 우대 수수료율</strong> 적용</li>
						<li>✅ 신용카드, 간편결제, 계좌이체 등 <strong>모든 결제수단</strong> 지원</li>
						<li>✅ <strong>실시간 정산</strong> 및 자동 입금</li>
						<li>✅ 강력한 보안과 안정적인 결제 시스템</li>
						<li>✅ 24시간 고객 지원</li>
					</ul>
					<p class="tosspayments-note">
						💡 사업자등록번호만 있으면 <strong>5분 안에 가입</strong>할 수 있습니다!
					</p>
				</div>
				<div class="tosspayments-banner-action">
					<a href="<?php echo esc_url( $onboarding_url ); ?>" class="button button-primary button-hero tosspayments-signup-button" target="_blank" rel="noopener noreferrer">
						<span class="dashicons dashicons-external"></span>
						지금 가입하고 특별 혜택 받기
					</a>
					<p class="tosspayments-small-text">
						이미 가입하셨나요? 
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=tosspayments' ) ); ?>">
							API 키 설정하기
						</a>
					</p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Enqueue admin scripts and styles.
	 */
	public function enqueue_admin_scripts() {
		if ( ! $this->should_show_banner() ) {
			return;
		}

		// Enqueue a dedicated handle so we can safely attach inline CSS/JS.
		wp_register_style( 'seoulcommerce-tpg-admin-banner', false, array(), SEOULCOMMERCE_TPG_VERSION );
		wp_enqueue_style( 'seoulcommerce-tpg-admin-banner' );
		wp_add_inline_style( 'seoulcommerce-tpg-admin-banner', $this->get_banner_styles() );

		wp_register_script(
			'seoulcommerce-tpg-admin-banner',
			SEOULCOMMERCE_TPG_PLUGIN_URL . 'assets/js/admin-banner.js',
			array( 'jquery' ),
			SEOULCOMMERCE_TPG_VERSION,
			true
		);
		wp_enqueue_script( 'seoulcommerce-tpg-admin-banner' );
		wp_localize_script(
			'seoulcommerce-tpg-admin-banner',
			'seoulcommerceTpgAdminBanner',
			array(
				'action' => 'seoulcommerce_tpg_dismiss_banner',
				'nonce'  => wp_create_nonce( 'seoulcommerce-tpg-dismiss-banner' ),
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			)
		);
	}

	/**
	 * Get banner CSS styles.
	 *
	 * @return string
	 */
	private function get_banner_styles() {
		return '
			.tosspayments-merchant-banner {
				border-left: 4px solid #1e88e5 !important;
				padding: 0 !important;
				position: relative;
				background: linear-gradient(135deg, #f8fbff 0%, #e3f2fd 100%);
				margin: 20px 0 20px 0 !important;
				box-shadow: 0 2px 8px rgba(0,0,0,0.1);
			}
			
			.tosspayments-banner-content {
				display: flex;
				align-items: center;
				gap: 30px;
				padding: 25px 30px;
			}
			
			.tosspayments-banner-icon {
				flex-shrink: 0;
			}
			
			.tosspayments-banner-icon img {
				height: 60px;
				width: auto;
			}
			
			.tosspayments-banner-text {
				flex: 1;
			}
			
			.tosspayments-banner-text h2 {
				margin: 0 0 12px 0;
				font-size: 20px;
				color: #1e88e5;
				font-weight: 600;
			}
			
			.tosspayments-banner-text p {
				margin: 0 0 15px 0;
				font-size: 14px;
				line-height: 1.6;
				color: #333;
			}
			
			.tosspayments-benefits {
				list-style: none;
				margin: 15px 0;
				padding: 0;
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
				gap: 8px;
			}
			
			.tosspayments-benefits li {
				font-size: 14px;
				color: #333;
				padding: 4px 0;
			}
			
			.tosspayments-benefits li strong {
				color: #1e88e5;
			}
			
			.tosspayments-note {
				background: #fff;
				padding: 12px 16px;
				border-radius: 6px;
				border-left: 3px solid #ffa726;
				margin: 15px 0 0 0 !important;
			}
			
			.tosspayments-banner-action {
				flex-shrink: 0;
				text-align: center;
			}
			
			.tosspayments-signup-button {
				font-size: 16px !important;
				padding: 12px 30px !important;
				height: auto !important;
				line-height: 1.4 !important;
				background: #1e88e5 !important;
				border-color: #1565c0 !important;
				text-shadow: none !important;
				box-shadow: 0 3px 12px rgba(30, 136, 229, 0.3) !important;
				transition: all 0.3s ease !important;
				white-space: nowrap !important;
			}
			
			.tosspayments-signup-button:hover {
				background: #1565c0 !important;
				border-color: #0d47a1 !important;
				transform: translateY(-2px);
				box-shadow: 0 5px 16px rgba(30, 136, 229, 0.4) !important;
			}
			
			.tosspayments-signup-button .dashicons {
				font-size: 18px;
				width: 18px;
				height: 18px;
				vertical-align: middle;
				margin-right: 4px;
			}
			
			.tosspayments-small-text {
				margin-top: 12px !important;
				font-size: 12px;
				color: #666;
			}
			
			.tosspayments-small-text a {
				color: #1e88e5;
				text-decoration: none;
			}
			
			.tosspayments-small-text a:hover {
				text-decoration: underline;
			}
			
			.tosspayments-dismiss-banner {
				z-index: 10;
			}
			
			/* Mobile responsive */
			@media (max-width: 960px) {
				.tosspayments-banner-content {
					flex-direction: column;
					text-align: center;
					gap: 20px;
				}
				
				.tosspayments-benefits {
					grid-template-columns: 1fr;
				}
				
				.tosspayments-banner-icon img {
					height: 45px;
				}
				
				.tosspayments-banner-text h2 {
					font-size: 18px;
				}
			}
		';
	}

	/**
	 * Handle banner dismissal via AJAX.
	 */
	public function dismiss_banner() {
		check_ajax_referer( 'seoulcommerce-tpg-dismiss-banner', 'nonce' );
		update_user_meta( get_current_user_id(), 'seoulcommerce_tpg_banner_dismissed', true );
		wp_die();
	}
}

// Initialize admin notices.
new SeoulCommerce_TPG_Admin_Notices();

