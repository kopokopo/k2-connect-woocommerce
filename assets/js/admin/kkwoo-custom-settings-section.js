/* global jQuery, KKWooData */
jQuery( document ).ready( function ( $ ) {
	$( '.kkwoo-action-button' ).on( 'click', function () {
		const $button = $( this );
		const endpoint = $button.data( 'endpoint' );
		const $messageBox = $( $button.data( 'target' ) );

		$messageBox.removeClass( 'message error' );

		$.ajax( {
			url: KKWooData.site_url + endpoint,
			method: 'POST',
			beforeSend: ( xhr ) => {
				xhr.setRequestHeader( 'X-WP-Nonce', KKWooData.nonce );
				$button.prop( 'disabled', true ).addClass( 'kkwoo-loading' );
			},
			success: ( response ) => {
				$messageBox
					.addClass( 'message' )
					.text(
						response.message || 'Action completed successfully.'
					);
			},
			error: ( xhr ) => {
				let errorMsg = 'Something went wrong.';
				if ( xhr.responseJSON && xhr.responseJSON.message ) {
					errorMsg = xhr.responseJSON.message;
				}
				$messageBox.addClass( 'error' ).text( errorMsg );
			},
			complete: () => {
				$button
					.prop( 'disabled', false )
					.removeClass( 'kkwoo-loading' );
			},
		} );
	} );

	function toggleManualPaymentFields() {
		const selected = $( '#woocommerce_kkwoo_manual_payment_method' ).val();
		const tillRow = $(
			'#woocommerce_kkwoo_manual_payments_till_no'
		).closest( 'tr' );
		const paybillBizRow = $(
			'#woocommerce_kkwoo_paybill_business_no'
		).closest( 'tr' );
		const paybillAccRow = $(
			'#woocommerce_kkwoo_paybill_account_no'
		).closest( 'tr' );

		if ( selected === 'till' ) {
			tillRow.show();
			paybillBizRow.hide();
			paybillAccRow.hide();
		} else if ( selected === 'paybill' ) {
			tillRow.hide();
			paybillBizRow.show();
			paybillAccRow.show();
		} else {
			tillRow.hide();
			paybillBizRow.hide();
			paybillAccRow.hide();
		}
	}

	// Run on load.
	toggleManualPaymentFields();

	// Run when dropdown changes.
	$( '#woocommerce_kkwoo_manual_payment_method' ).on(
		'change',
		toggleManualPaymentFields
	);
} );
