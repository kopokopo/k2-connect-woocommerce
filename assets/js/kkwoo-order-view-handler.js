/* global jQuery, KKWooData */
( function ( $ ) {
	function checkPaymentStatus( e ) {
		e.preventDefault();

		const $btn = $( e.currentTarget );
		const spinner = $(
			`<img src='${ KKWooData.spinner_icon }' alt='Spinner icon' class='k2 spinner sm'/>`
		);

		$.ajax( {
			url: `${ KKWooData.site_url }/wp-json/kkwoo/v1/query-incoming-payment-status`,
			method: 'GET',
			data: { order_key: KKWooData.order_key },
			dataType: 'json',
			beforeSend: () => {
				$btn.prop( 'disabled', true ).prepend( spinner );
				removeFlashMessage();
			},
			success: ( data ) => {
				showFlashMessage(
					data.message,
					data.status === 'error' ? 'error' : 'info'
				);
			},
			error: ( err ) => {
				showFlashMessage(
					typeof err.responseJSON.data === 'string'
						? err.responseJSON.data
						: 'Something went wrong. Please try again later.',
					'error'
				);
			},
			complete: () => {
				$btn.prop( 'disabled', false );
				spinner.remove();
			},
		} );
	}

	$( document ).on( 'click', '#check-payment-status', checkPaymentStatus );

	$( document ).ready( function () {
		setTimeout( function () {
			removeFlashMessage();
		}, 15 * 1000 );
	} );

	function showFlashMessage( message, type = 'success' ) {
		// Types: info(blue), error(red), message(green).
		const $container = $( '#kkwoo-flash-messages' );

		$container.empty();

		const $notice = $(
			`<div role=${ type }>
        <ul class="woocommerce-${ type } kkwoo-flash">
         <li>${ message }</li>
        </ul>
      </div>`
		);

		$container.append( $notice );
	}

	function removeFlashMessage() {
		$( '.kkwoo-flash' ).fadeOut( 600, function () {
			$( this ).remove();
		} );
	}
} )( jQuery );
