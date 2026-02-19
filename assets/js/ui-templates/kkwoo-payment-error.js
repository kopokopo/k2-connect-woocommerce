/**
 * Payment Error Information Section Template.
 *
 * Adds a template function to render error messages during payment.
 *
 * @param {Object} templates Template registry object.
 */
( function ( templates ) {
	'use strict';

	templates.PaymentError = function ( errorMessage ) {
		return `
         <div id='payment-error'>
            <img src='${ templates.getImageUrl(
				'error_circle_icon'
			) }' alt='Error circle icon'/>
            <div>
                <p class='main-info'>Payment failed</p>
                <p class='side-note'>${
					errorMessage ?? 'Unexpected error. Please try again.'
				}</p>
            </div>
            <div class="modal-actions">
                <button
                id="retry-payment"
                class="k2 modal-btn close-modal modal-btn-confirm outline w-full"
                >
                Retry payment
                </button>
            </div>
        </div>   
      `;
	};
} )( window.KKWooTemplates );
