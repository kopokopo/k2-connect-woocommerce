/**
 * Payment - Refunded Section Template.
 *
 * We do NOT handle refunds, however, if the user is on the payment flow and a refund
 * is done manually by the merchant, this section is what we show the customer
 * when payment is in refunded status.
 *
 * @param {Object} templates Template registry object.
 */
( function ( templates ) {
	'use strict';

	templates.PaymentRefunded = function () {
		return `
         <div id='payment-refunded'>
            <img src='${ templates.getImageUrl(
				'info_circle_icon'
			) }' alt='Error circle icon'/>
            <div>
                <p class='main-info'>Payment refunded</p>
                <p class='side-note'>It seems that <span id='store-name' class='store-name'></span>  has refunded you. Please contact them for support.</p>
            </div>
            <div class="modal-actions">
                <button
                id="redirect-to-order"
                class="k2 modal-btn close-modal modal-btn-confirm outline w-full"
                >
                Close
                </button>
            </div>
        </div>   
      `;
	};
} )( window.KKWooTemplates );
