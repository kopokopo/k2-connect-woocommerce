/**
 * Payment - No Result Yet Information Section Template.
 *
 * Adds a template function to render a section when the payment result is not yet available.
 *
 * @param {Object} templates Template registry object.
 */
( function ( templates ) {
	'use strict';

	templates.PaymentNoResultYet = function ( message ) {
		return `
         <div id='payment-no-result-yet'>
            <img src='${ templates.getImageUrl(
				'info_circle_icon'
			) }' alt='Error circle icon'/>
            <div>
                <p class='main-info'>Waiting to receive funds</p>
                <p class='side-note'>${
					message ??
					'Your payment is being processed. Your order will be updated once complete.'
				}</p>
            </div>
            <div class="modal-actions">
                <button
                id="redirect-to-order"
                class="k2 modal-btn close-modal modal-btn-confirm outline w-full"
                >
                Done
                </button>
            </div>
        </div>   
      `;
	};
} )( window.KKWooTemplates );
