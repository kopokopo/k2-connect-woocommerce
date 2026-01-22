/**
 * Payment Success Information Section Template
 */
(function(templates) {
    'use strict';
    
    templates.PaymentSuccess = function(success_message) {
      return `
        <div id='payment-success'>
          <img src='${templates.getImageUrl('success_circle_icon')}' alt='Success circle icon'/>
          <div>
              <p class='side-note'>${success_message || "You have paid <span id='currency'></span> <span id='total-amount'></span> to <span id='store-name'></span>."}</p>
              <p class='timer-info'>Redirecting in <span id='countdown'>00:10<span/></p>
          </div>
          <div class="modal-actions">
              <button
              id="redirect-to-order-received"
              class="k2 modal-btn close-modal modal-btn-confirm outline w-full"
              >
              Done
              </button>
          </div>
        </div> 
      `
    };
})(window.KKWooTemplates);
