/* global jQuery, KKWooData, PollingManager */

/*
 * Controls whether the page should auto-refresh or allow redirection.
 * When set to false, it prevents unintended page unloads or navigations,
 * helping us distinguish between user-initiated and system-initiated redirects.
 */
let autoRefreshPage = false;

window.addEventListener( 'beforeunload', function ( e ) {
	if ( autoRefreshPage === false ) {
		e.preventDefault();
		e.returnValue = ''; // Required for modern browsers.
	}
} );

( function ( $, templates, validations ) {
	function renderSection( renderFn, withManualPaymentSection = false ) {
		const overlay = $( '.k2.modal-overlay' );
		const modalContent = $( '.k2 .modal-content' );
		modalContent.empty();

		const htmlString = renderFn();
		const $newSection = $( htmlString );

		modalContent.append( $newSection );
		if (
			withManualPaymentSection &&
			KKWooData.selected_manual_payment_method
		) {
			const selectedMethod =
				KKWooData.selected_manual_payment_method === 'till'
					? 'M-PESA Buy Goods '
					: 'M-PESA Paybill';
			$( '.switch-to-manual-payments' ).show();
			$( '#switch-to-manual-payments' ).text( selectedMethod );
		} else {
			$( '.switch-to-manual-payments' ).hide();
		}

		overlay.show();
	}

	function populateCheckoutInfo() {
		$( '#currency' ).text( KKWooData.currency );
		$( '#total-amount' ).text( KKWooData.total_amount );
		$( '#store-name' ).text( KKWooData.store_name );
	}

	function populateManualPaymentInfo( data ) {
		$( '#currency' ).text( KKWooData.currency );
		$( '#total-amount' ).text( KKWooData.total_amount );
		$( '#store-name' ).text( KKWooData.store_name );
		$( '#instruction-currency' ).text( KKWooData.currency );
		$( '#instruction-total-amount' ).text( KKWooData.total_amount );

		switch ( KKWooData.selected_manual_payment_method ) {
			case 'till':
				$( '#payment-method' ).text( 'Buy Goods and Services' );
				$( '#payment_method_title' ).text( 'Till' );
				$( '#till-or-paybill-number' ).text( data?.till );
				$( '.for-paybill' ).hide();
				$( '#currency' ).text( KKWooData.currency );
				break;
			case 'paybill':
				$( '#payment-method' ).text( 'Paybill' );
				$( '#payment_method_title' ).text( 'Business' );
				$( '#till-or-paybill-number' ).text(
					data?.paybill?.business_no
				);
				$( '.for-paybill' ).show();
				$( '#account-number' ).text( data?.paybill?.account_no );
				break;
			default:
				break;
		}
	}

	function addRedirectToOrderReceived() {
		let seconds = 10;
		const $countdown = $( '#countdown' );

		const timer = setInterval( () => {
			seconds--;
			$countdown.text(
				seconds < 10 ? '00:0' + seconds : '00:' + seconds
			);

			if ( seconds <= 0 ) {
				clearInterval( timer );
				window.location.href = KKWooData.order_received_url;
			}
		}, 1000 );

		$( '#redirect-to-order-received' )
			.off( 'click' )
			.on( 'click', () => {
				clearInterval( timer );
				window.location.href = KKWooData.order_received_url;
			} );
	}

	const DefaultPollingCallbacks = {
		onSuccess: () => {
			autoRefreshPage = true;
			renderSection( templates.PaymentSuccess );
			populateCheckoutInfo();
			addRedirectToOrderReceived();
		},
		onFailure: ( data ) => {
			const errorMessage = data.data;
			renderSection( () => templates.PaymentError( errorMessage ), true );
		},
		onNoResult: () => {
			autoRefreshPage = true;
			renderSection( templates.PaymentNoResultYet );
		},
	};

	/**
	 *
	 * Safely extracts a string value (e.g., an API error message).
	 *
	 * This is necessary as there are multiple error structures returned,
	 * based on whether the error is returned by K2 Connect, wordpress or this plugin.
	 * Returns the fallback if the value is not a valid string that can be displayed to users.
	 *
	 * @param {*}      value    The actual value
	 * @param {string} fallback The value to be returned if the actual value is not a string
	 * @return {string} Either the actual value or fallback value
	 */
	const ensureString = ( value, fallback ) => {
		if ( typeof value !== 'string' ) {
			return fallback;
		}
		return value;
	};

	function initiatePayment( phone ) {
		$.ajax( {
			url: `${ KKWooData.site_url }/wp-json/kkwoo/v1/stk-push`,
			method: 'POST',
			contentType: 'application/json',
			headers: {
				'X-WP-Nonce': KKWooData.nonce,
			},
			data: JSON.stringify( {
				phone,
				order_key: KKWooData.order_key,
			} ),
			success: () => {
				$( '#proceed-to-poll' ).prop( 'disabled', false );

				PollingManager.timeout = setTimeout( function () {
					PollingManager.start(
						DefaultPollingCallbacks,
						'pin-instruction',
						false
					);
				}, 40 * 1000 );
			},
			error: ( jqXHR ) => {
				PollingManager.stop();
				let errorMessage;
				try {
					const response = jqXHR.responseJSON;
					errorMessage = ensureString(
						response?.data?.data?.errorMessage ??
							response?.message ??
							response?.data,
						'Something went wrong. Please try again.'
					);
				} catch ( e ) {
					errorMessage = 'Something went wrong. Please try again.';
				}
				renderSection(
					() => templates.PaymentError( errorMessage ),
					true
				);
			},
		} );
	}

	function saveManualPaymentDetails( mpesaRefNo ) {
		$.ajax( {
			url: `${ KKWooData.site_url }/wp-json/kkwoo/v1/save-manual-payment-details`,
			method: 'POST',
			contentType: 'application/json',
			headers: {
				'X-WP-Nonce': KKWooData.nonce,
			},
			data: JSON.stringify( {
				mpesa_ref_no: mpesaRefNo,
				order_key: KKWooData.order_key,
			} ),
			beforeSend: () => {
				$( '#submit-manual-payment-details' ).prop( 'disabled', true );
			},
			success: ( data ) => {
				autoRefreshPage = true;
				if ( data.status === 'success' ) {
					renderSection( () =>
						templates.PaymentSuccess( data.message ?? '' )
					);
					addRedirectToOrderReceived();
				} else {
					renderSection( () =>
						templates.PaymentNoResultYet( data.message ?? '' )
					);
				}
			},
			error: ( jqXHR ) => {
				let errorMessage;
				try {
					const response = jqXHR.responseJSON;
					errorMessage = ensureString(
						response?.data?.data?.errorMessage ??
							response?.message ??
							response?.data,
						'Something went wrong. Please try again.'
					);
				} catch ( e ) {
					errorMessage = 'Something went wrong. Please try again.';
				}

				renderSection(
					() => templates.PaymentError( errorMessage ),
					true
				);
			},
			complete: () => {
				$( '#submit-manual-payment-details' ).prop( 'disabled', false );
			},
		} );
	}

	function getSelectedManualPaymentMethod() {
		$.ajax( {
			url: `${ KKWooData.site_url }/wp-json/kkwoo/v1/selected-manual-payment-method/${ KKWooData.order_key }`,
			method: 'GET',
			contentType: 'application/json',
			headers: {
				'X-WP-Nonce': KKWooData.nonce,
			},
			beforeSend: () => {
				$( '#switch-to-manual-payments' ).prop( 'disabled', true );
			},
			success: ( data ) => {
				renderSection( templates.ManualPaymentInstructions );
				populateManualPaymentInfo( data.data );
			},
			error: ( jqXHR ) => {
				let errorMessage;
				try {
					const response = jqXHR.responseJSON;
					errorMessage = ensureString(
						response?.data?.data?.errorMessage ??
							response?.message ??
							response?.data,
						'Something went wrong. Please try again.'
					);
				} catch ( e ) {
					errorMessage = 'Something went wrong. Please try again.';
				}

				renderSection(
					() => templates.PaymentError( errorMessage ),
					true
				);
			},
			complete: () => {
				$( '#switch-to-manual-payments' ).prop( 'disabled', false );
			},
		} );
	}

	$( document ).on( 'click', '#proceed-to-pay-btn', ( e ) => {
		e.preventDefault();
		const phone = $( '#mpesa-phone-input' ).val().trim();

		if ( ! validations.validMpesaNumber( phone ) ) {
			return;
		}

		renderSection( templates.PinInstruction );
		initiatePayment( phone );
	} );

	$( document ).on( 'click', '#proceed-to-poll', ( e ) => {
		e.preventDefault();
		PollingManager.stop();
		renderSection( templates.Polling );
		PollingManager.start( DefaultPollingCallbacks );
	} );

	$( document ).on( 'click', '#retry-payment', ( e ) => {
		e.preventDefault();
		renderSection( templates.MpesaNumberForm );
		populateCheckoutInfo();
	} );

	$( document ).on( 'click', '#redirect-to-order', ( e ) => {
		e.preventDefault();
		window.location.href = KKWooData.this_order_url;
	} );

	$( document ).on( 'click', '#switch-to-manual-payments', ( e ) => {
		e.preventDefault();
		getSelectedManualPaymentMethod();
	} );

	$( document ).on( 'click', '#submit-manual-payment-details', ( e ) => {
		e.preventDefault();
		const mpesaRefNo = $( '#mpesa-ref-input' ).val().trim();

		if ( ! validations.validMpesaRefNo( mpesaRefNo ) ) {
			return;
		}

		saveManualPaymentDetails( mpesaRefNo );
	} );

	// Initial setup --- Order statuses -> pending, on-hold, processing, completed, failed, cancelled, refunded.
	const orderStatus = KKWooData.order_status;
	if ( orderStatus === 'pending' || orderStatus === 'failed' ) {
		renderSection( templates.MpesaNumberForm );
		populateCheckoutInfo();
	} else if ( orderStatus === 'on-hold' ) {
		autoRefreshPage = true;
		renderSection( templates.PaymentNoResultYet );
		populateCheckoutInfo();
	} else if ( orderStatus === 'refunded' ) {
		autoRefreshPage = true;
		renderSection( templates.PaymentRefunded );
		populateCheckoutInfo();
	} else if (
		orderStatus === 'processing' ||
		orderStatus === 'completed' ||
		orderStatus === 'cancelled'
	) {
		autoRefreshPage = true;
		window.location.href = KKWooData.this_order_url;
	}

	$( document ).ready( function () {
		if ( $( 'body' ).hasClass( 'admin-bar' ) ) {
			const adminBarHeight = $( '#wpadminbar' ).outerHeight();

			$( 'body' ).css( 'margin-top', adminBarHeight + 'px' );
			$( '.modal-overlay' ).css( {
				'min-height': 'calc(100vh - ' + adminBarHeight + 'px)',
				top: adminBarHeight + 'px',
			} );
		}
	} );
} )( jQuery, window.KKWooTemplates, window.KKWooValidations );
