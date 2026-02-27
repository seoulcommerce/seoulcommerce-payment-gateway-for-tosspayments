/**
 * TossPayments Blocks Checkout Integration
 *
 * @package WooCommerce_TossPayments
 */

( function() {
	'use strict';

	// Get WooCommerce Blocks dependencies from global scope
	const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
	const { getSetting } = window.wc.wcSettings;
	const { createElement } = window.wp.element;
	const { decodeEntities } = window.wp.htmlEntities;
	const { __ } = window.wp.i18n;

	// Get payment method data
	const settings = getSetting( 'tosspayments_data', {} );

	/**
	 * TossPayments Content Component
	 * Simple component that just shows the description
	 */
	const TossPaymentsContent = () => {
		return createElement(
			'div',
			{ className: 'wc-block-components-payment-method-tosspayments' },
			settings.description && createElement(
				'div',
				{ className: 'wc-block-components-payment-method-description' },
				decodeEntities( settings.description || '' )
			)
		);
	};

	/**
	 * Payment Method Configuration
	 */
	const TossPayments = {
		name: 'tosspayments',
		label: createElement(
			'span',
			{ 
				className: 'wc-block-components-payment-method__label',
				style: { display: 'flex', alignItems: 'center', gap: '8px' }
			},
			settings.icon && createElement(
				'img',
				{
					src: settings.icon,
					alt: 'TossPayments',
					style: { 
						height: '24px',
						width: 'auto',
						verticalAlign: 'middle'
					}
				}
			),
			decodeEntities( settings.title || __( 'TossPayments', 'seoulcommerce-payment-gateway-for-tosspayments' ) )
		),
		content: createElement( TossPaymentsContent ),
		edit: createElement( TossPaymentsContent ),
		canMakePayment: () => true,
		ariaLabel: decodeEntities( settings.description || __( 'TossPayments payment method', 'seoulcommerce-payment-gateway-for-tosspayments' ) ),
		supports: {
			features: settings.supports || [ 'products' ],
		},
	};

	// Register the payment method
	registerPaymentMethod( TossPayments );
} )();
