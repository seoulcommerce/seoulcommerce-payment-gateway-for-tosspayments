/**
 * TossPayments Payment Handler
 *
 * @package WooCommerce_TossPayments
 */

(function( $ ) {
	'use strict';

	/**
	 * TossPayments Payment Handler
	 */
	class TossPaymentsHandler {
		constructor() {
			this.clientKey = seoulcommerceTpgParams.clientKey;
			this.orderId = seoulcommerceTpgParams.orderId;
			this.tosspayments_orderId = seoulcommerceTpgParams.tosspayments_orderId || seoulcommerceTpgParams.orderId;
			this.amount = parseFloat( seoulcommerceTpgParams.amount );
			this.returnUrl = seoulcommerceTpgParams.returnUrl;
			this.checkoutUrl = seoulcommerceTpgParams.checkoutUrl;
			this.payment = null;

			this.init();
		}

		init() {
			// Wait for TossPayments SDK to load
			if ( typeof window.TossPayments === 'undefined' ) {
				$( document ).on( 'tosspayments-sdk-loaded', () => {
					this.initializePayment();
				} );
			} else {
				this.initializePayment();
			}

			// Handle form submission
			$( 'form.checkout, form#order_review' ).on( 'checkout_place_order_tosspayments', this.handlePayment.bind( this ) );
			
			// Auto-trigger payment only on order-pay page (flag from PHP - avoids firing on checkout)
			const isOrderPayPage = seoulcommerceTpgParams.isOrderPayPage && this.orderId && this.amount > 0;
			
			if ( isOrderPayPage ) {
				const doTrigger = () => {
					if ( this.payment ) {
						this.triggerPayment();
					} else if ( typeof window.TossPayments !== 'undefined' ) {
						// SDK loaded but we haven't initialized - may have missed the event
						this.initializePayment();
						setTimeout( doTrigger, 100 );
					} else {
						// SDK not ready yet, wait for it
						$( document ).one( 'tosspayments-sdk-loaded', () => {
							setTimeout( doTrigger, 100 );
						} );
					}
				};
				// Wait for SDK to load (async script), then trigger
				if ( typeof window.TossPayments !== 'undefined' ) {
					setTimeout( doTrigger, 300 );
				} else {
					$( document ).on( 'tosspayments-sdk-loaded', () => {
						setTimeout( doTrigger, 300 );
					} );
				}
			}
		}

		initializePayment() {
			if ( ! this.clientKey ) {
				console.error( 'TossPayments: Client key is missing' );
				return;
			}

			try {
				// Initialize TossPayments SDK v2
				const tossPaymentsInstance = window.TossPayments( this.clientKey );
				this.payment = tossPaymentsInstance.payment({
					customerKey: window.TossPayments.ANONYMOUS
				});
				$( document ).trigger( 'tosspayments-sdk-loaded' );
			} catch ( error ) {
				console.error( 'TossPayments: Failed to initialize SDK', error );
			}
		}

		handlePayment( event ) {
			// On main checkout page, order doesn't exist yet. Let the form submit so WooCommerce
			// creates the order and redirects to order-pay. Only intercept on order-pay page.
			const isOrderPayPage = $( 'form#order_review' ).length > 0 || $( '.order_details' ).length > 0;
			if ( ! isOrderPayPage || ! this.orderId ) {
				return true; // Allow form submission - order will be created, then redirect to order-pay
			}
			event.preventDefault();
			this.triggerPayment();
			return false;
		}

		triggerPayment() {
			if ( ! this.payment ) {
				console.error( 'TossPayments: Payment SDK not initialized' );
				alert( seoulcommerceTpgParams.i18n.error );
				return false;
			}

			const orderData = this.getOrderData();

			if ( ! orderData ) {
				console.error( 'TossPayments: Failed to get order data' );
				return false;
			}

			// Show loading state
			this.setLoadingState( true );

			// Build payment request - TossPayments v2 SDK format
			const paymentRequest = {
				method: 'CARD',
				amount: {
					currency: 'KRW',
					value: orderData.amount,
				},
				orderId: orderData.tosspayments_orderId,
				orderName: orderData.orderName,
				successUrl: this.returnUrl + '&order_id=' + this.orderId,
				failUrl: this.returnUrl + '&order_id=' + this.orderId + '&fail=1',
				customerEmail: orderData.customerEmail,
				customerName: orderData.customerName,
				customerMobilePhone: orderData.customerMobilePhone,
				card: {
					useEscrow: false,
					flowMode: 'DEFAULT',
					useCardPoint: false,
					useAppCardOnly: false,
				},
			};

			// Request payment
			this.payment
				.requestPayment( paymentRequest )
				.catch( ( error ) => {
					this.setLoadingState( false );
					console.error( 'TossPayments payment error:', error );
					alert( error.message || wcTossPaymentsParams.i18n.error );
				} );

			return true;
		}

		getOrderData() {
			const orderId = this.orderId;
			const tosspayments_orderId = this.tosspayments_orderId;

			if ( ! orderId ) {
				console.error( 'TossPayments: Order ID not provided' );
				return null;
			}

			if ( ! tosspayments_orderId ) {
				console.error( 'TossPayments: Sanitized order ID not provided' );
				return null;
			}

			// Get order total from server params
			const amount = Math.floor( parseFloat( this.amount ) );

			if ( ! amount || amount <= 0 ) {
				console.error( 'TossPayments: Invalid amount:', amount );
				return null;
			}

			// Get customer info from server params or form fields
			let customerEmail = wcTossPaymentsParams.customerEmail || $( '#billing_email' ).val() || 'customer@example.com';
			let customerName = seoulcommerceTpgParams.customerName || '';
			
			// Build name from form if not provided
			if ( ! customerName || customerName.trim().length === 0 ) {
				const firstName = $( '#billing_first_name' ).val() || '';
				const lastName = $( '#billing_last_name' ).val() || '';
				customerName = ( firstName + ' ' + lastName ).trim() || 'Customer';
			}

			// Get and format phone number
			let customerMobilePhone = seoulcommerceTpgParams.customerPhone || $( '#billing_phone' ).val() || '';
			if ( customerMobilePhone ) {
				// Remove all non-numeric characters
				customerMobilePhone = customerMobilePhone.replace( /[^0-9]/g, '' );
				
				// Convert international format to local
				if ( customerMobilePhone.startsWith( '82' ) ) {
					customerMobilePhone = '0' + customerMobilePhone.substring( 2 );
				}
				
				// Validate Korean mobile format
				if ( ! /^0(10|11|16|17|18|19)[0-9]{7,8}$/.test( customerMobilePhone ) ) {
					customerMobilePhone = '';
				}
			}

			// Get order name
			const orderName = seoulcommerceTpgParams.orderName || this.getOrderName();

			return {
				orderId: String( orderId ),
				tosspayments_orderId: String( tosspayments_orderId ),
				amount: parseInt( amount, 10 ),
				orderName: String( orderName ),
				customerEmail: String( customerEmail ),
				customerName: String( customerName ).trim(),
				customerMobilePhone: String( customerMobilePhone ).trim(),
			};
		}

		getOrderName() {
			// Try to get from order review
			const orderItems = $( '.woocommerce-checkout-review-order-table tbody tr' );
			if ( orderItems.length > 0 ) {
				const firstItem = orderItems.first().find( '.product-name' ).text().trim();
				if ( firstItem ) {
					return orderItems.length > 1
						? firstItem + ' 외 ' + ( orderItems.length - 1 ) + '개'
						: firstItem;
				}
			}

			// Fallback
			return '주문 #' + this.orderId;
		}

		setLoadingState( isLoading ) {
			const submitButton = $( 'button[name="woocommerce_checkout_place_order"]' );
			const form = $( 'form.checkout, form#order_review' );

			if ( isLoading ) {
				submitButton.prop( 'disabled', true ).text( seoulcommerceTpgParams.i18n.processing );
				form.addClass( 'processing' );
			} else {
				submitButton.prop( 'disabled', false ).text( submitButton.data( 'original-text' ) || '주문하기' );
				form.removeClass( 'processing' );
			}
		}
	}

	// Initialize when document is ready
	$( document ).ready( function() {
		// Order-pay: PHP sets isOrderPayPage. Checkout: TossPayments selected.
		const isOrderPayPage = wcTossPaymentsParams.isOrderPayPage;
		const isCheckoutPage = $( 'input[name="payment_method"][value="tosspayments"]' ).is( ':checked' ) || $( 'input#payment_method_tosspayments' ).is( ':checked' );

		if ( isOrderPayPage || isCheckoutPage ) {
			window.tosspaymentsHandler = new TossPaymentsHandler();
		}
	} );

	// Trigger SDK loaded event when TossPayments is available
	$( window ).on( 'load', function() {
		if ( typeof window.TossPayments !== 'undefined' ) {
			$( document ).trigger( 'tosspayments-sdk-loaded' );
		}
	} );

})( jQuery );
