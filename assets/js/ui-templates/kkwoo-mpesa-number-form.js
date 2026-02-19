/**
 * M-PESA Number Form Template.
 *
 * Adds a template function to render the M-PESA number input.
 *
 * @param {Object} templates Template registry object.
 */
( function ( templates ) {
	'use strict';

	templates.MpesaNumberForm = function () {
		return `
          <div id='mpesa-number-form'>
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
            <form>
                <div class='form-group'>
                <label>Enter M-PESA phone number</label>
                <div class='amount-input'>
                    <span class='country-code'>
                    <img src='${ templates.getImageUrl(
						'kenyan_flag_img'
					) }' alt='Kenyan flag' class='k2'/> 
                    <span> +254</span>
                    </span>
                    <input id='mpesa-phone-input' type='text' placeholder='7xx xxx xxx'/>
                </div>
                <div class='message error'></div>
                </div>
            </form>

            <div class="modal-actions">
                <button
                id="proceed-to-pay-btn"
                class="k2 modal-btn modal-btn-confirm"
                >
                Proceed to pay
                </button>
            </div>
        </div>
      `;
	};
} )( window.KKWooTemplates );
