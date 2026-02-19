/**
 * Manual Payment Instructions Form Template.
 *
 * Adds a template function to render manual payment instructions.
 *
 * @param {Object} templates Template registry object.
 */
( function ( templates ) {
	'use strict';

	templates.ManualPaymentInstructions = function () {
		return `
          <div id='manual-payment-instructions'>
            <header>
                <h3 class="modal-title">Lipa na M-PESA</h3 >
                
                <span id='store-name' class='store-name'></span> 
            </header>
            
            <div class='amount-card'>
                <div class='label'>
                Amount to pay
                </div>
                <div class='amount'>
                <span id='currency'></span> <span id='total-amount'></span>
                </div>
            </div>

            <div class='payment-instructions'>
              <div class='title'>How to pay</div>
              <ol class='instructions'>
                <li>Go to M-PESA on your phone</li>
                <li>Select Lipa na M-PESA, then <span id='payment-method'></span></li>
                <li>Enter <span id='payment_method_title'></span> Number: <span id='till-or-paybill-number' class='highlight'></span></li>
                <li class='for-paybill'>Enter Account Number: <span id='account-number' class='highlight'></span></li>
                <li>Enter amount: <span class='highlight'><span id='instruction-currency'></span> <span id='instruction-total-amount'></span></span></li>
                <li>Enter your M-PESA PIN and confirm</li>
                <li>You will receive an M-PESA confirmation message</li>
              </ol>
            </div>
            <form>
                <div class='form-group'>
                <label>M-PESA reference number</label>
                <div class='mpesa-ref-input'>
                    <input id='mpesa-ref-input' type='text' placeholder='Enter the M-PESA reference number'/>
                </div>
                <div class='message error'></div>
                </div>
            </form>

            <div class="modal-actions">
                <button
                id="submit-manual-payment-details"
                class="k2 modal-btn modal-btn-confirm"
                >
                Submit
                </button>
            </div>
        </div>
      `;
	};
} )( window.KKWooTemplates );
