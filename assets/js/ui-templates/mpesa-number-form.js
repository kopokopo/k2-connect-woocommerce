/**
 * M-PESA Number Form Template
 */
(function(templates) {
    'use strict';
    
    templates.MpesaNumberForm = function({onconfirm}) {
        return `
          <div id='mpesa-number-form'>
            <header>
              <h3 class="modal-title">Lipa na M-PESA</h3 >
              
              <button
                class="modal-close-btn close-modal"
                aria-label="Close modal"
              >
                ×
              </button>
            </header>
            
            <div class='amount-card'>
              <div class='label'>
                Amount to pay
              </div>
              <div class='amount'>
                Ksh 32,000
              </div>
            </div>
            <form>
              <div class='form-group'>
                <label>Enter M-PESA phone number</label>
                <div class='amount-input'>
                  <span class='country-code'>
                    <img src='${templates.getImageUrl('kenyan_flag_img')}' alt='Kenyan flag' class='k2'/> 
                    <span> +254</span>
                  </span>
                  <input type='text' placeholder='7xx xxx xxx'/>
                </div>
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
    `
    };
})(window.KKWooTemplates);
