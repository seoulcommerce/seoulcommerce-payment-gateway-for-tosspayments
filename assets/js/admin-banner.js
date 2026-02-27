/* global seoulcommerceTpgAdminBanner */

( function( $ ) {
	'use strict';

	$( function() {
		$( document ).on( 'click', '.tosspayments-merchant-banner .notice-dismiss', function() {
			if ( typeof seoulcommerceTpgAdminBanner === 'undefined' ) {
				return;
			}

			// Non-blocking dismissal ping; banner is purely informational.
			fetch( seoulcommerceTpgAdminBanner.ajaxUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: new URLSearchParams( {
					action: seoulcommerceTpgAdminBanner.action,
					nonce: seoulcommerceTpgAdminBanner.nonce,
				} ),
			} ).catch( function() {
				// Ignore network errors.
			} );
		} );
	} );
} )( jQuery );

