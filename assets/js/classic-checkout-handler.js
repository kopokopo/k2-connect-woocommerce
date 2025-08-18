(function ($, templates) {
    function togglePlaceOrderButton() {
        const selectedPayment = $('input[name="payment_method"]:checked').val();

        if (selectedPayment === 'kkwoo') {
            $('#place_order').hide();

            if (!$('#kkwoo-custom-button').length) {
                $('#place_order').after(`
                    <button type="button" id="kkwoo-custom-button" class="k2 button alt">
                        Pay with Kopo Kopo
                    </button>
                `);
            }
        } else {
            $('#place_order').show();
            $('#kkwoo-custom-button').remove();
        }
    }

    togglePlaceOrderButton();

    $('form.checkout').on('change', 'input[name="payment_method"]', togglePlaceOrderButton);

    $('form.checkout').on('click', '#kkwoo-custom-button', function (e) {
        e.preventDefault();

        openSTKModal();

        $('form.checkout').append(`<input type="hidden" name="kkwoo_phone" value="${phone}" />`);
        $('#place_order').trigger('click');
    });

    function openSTKModal() {
      const modal = createModal({
        children: templates.MpesaNumberForm({
          onCancel: () => {
            document.body.removeChild(modal);
          },
          onConfirm: () => {
            let mpesaNumberForm = $('#mpesa-number-form');
            let pinInstruction = $('#pin-instruction');
            modal.removeChild(mpesaNumberForm);
            modal.appendChild(pinInstruction)
          },
        })
      });

      document.body.appendChild(modal);
      let modalContent = modal.querySelector('.modal-content');
      
      modal.querySelector('.close-modal').addEventListener('click', () => {
        modal.remove();
      });

      modal.querySelector('#proceed-to-pay-btn').addEventListener('click', (e) => {
        e.preventDefault(); // prevent form submit
        let mpesaNumberForm = document.querySelector('#mpesa-number-form');
        let pinInstruction = templates.PinInstruction();

        let temp = document.createElement("div");
        temp.innerHTML = pinInstruction;
        let pinInstructionNode = temp.firstElementChild;

        modalContent.removeChild(mpesaNumberForm);
        modalContent.appendChild(pinInstructionNode);
        modal.querySelector('.close-modal').addEventListener('click', () => {
          modal.remove();
        });
      });
    }
})(jQuery, window.KKWooTemplates);


function getOrderIdFromUrl() {
  const pathParts = window.location.pathname.split('/');
  const idx = pathParts.indexOf('order-received');
  if (idx !== -1 && pathParts[idx + 1]) {
    return pathParts[idx + 1];
  }
  return null;
}


function createModal({ children }) {
  // Overlay
  const overlay = document.createElement('div');
  overlay.className = 'k2 modal-overlay';

  // Modal body
  const body = document.createElement('div');
  body.className = 'modal-body';

  // Prevent click inside modal from closing it
  body.addEventListener('click', (e) => e.stopPropagation());

  // Modal content
  const content = document.createElement('div');
  content.className = 'modal-content';

  if (typeof children === 'string') {
    content.innerHTML = children;
  } else if (children instanceof Node) {
    content.appendChild(children);
  }

  // Modal Footer
  const modalFooter = document.createElement('div');
  modalFooter.className = 'modal-footer';
  modalFooter.innerHTML = `Powered by <img src='${KKWooData.k2_logo_with_name_img}' alt='Kopo Kopo (Logo)' /> `;

  // Assemble modal content
  body.appendChild(content);
  body.appendChild(modalFooter);

  // Assemble overlay
  overlay.appendChild(body);

  // Append to body
  document.body.appendChild(overlay);

  return overlay;
}

