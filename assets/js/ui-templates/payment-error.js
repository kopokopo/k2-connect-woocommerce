/**
 * Payment Error Information Section Template
 */
(function(templates) {
    'use strict';
    
    templates.PaymentError = function() {
      return`
         <div id='payment-error'>
            <img src='${templates.getImageUrl('error_circle_icon')}' alt='Error circle icon'/>
            <div>
                <p class='main-info'>Payment declined</p>
                <p class='side-note'>Please ensure that you have enough money on your M-PESA and try again.</p>
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
      `
    };
})(window.KKWooTemplates);
