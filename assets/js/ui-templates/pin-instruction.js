/**
 * Pin Instruction Section Template
 */
(function(templates) {
    'use strict';
    
    templates.PinInstruction = function() {
      return `
        <div id='pin-instruction'>
          <img src='${templates.getImageUrl('phone_icon')}' alt='Phone icon' class='k2'/>
          <div>
            <p class='main-info'>Enter your M-PESA PIN when prompted to complete payment</p>
            <p class='side-note'>Please note the message will come from Kopo Kopo</p>
          </div>

          <img src='${templates.getImageUrl('spinner_icon')}' alt='Spinner icon' class='k2 spinner'/>

          <div class="modal-actions">
            <button
              id="proceed-to-poll"
              class="k2 modal-btn close-modal modal-btn-confirm outline w-full"
            >
              Done
            </button>
          </div>
        </div> 
      `
    };
})(window.KKWooTemplates);
