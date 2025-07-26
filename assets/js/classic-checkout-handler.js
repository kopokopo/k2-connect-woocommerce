jQuery(function ($) {
    function togglePlaceOrderButton() {
        const selectedPayment = $('input[name="payment_method"]:checked').val();

        if (selectedPayment === 'kkwoo') {
            $('#place_order').hide();

            if (!$('#kkwoo-custom-button').length) {
                $('#place_order').after(`
                    <button type="button" id="kkwoo-custom-button" class="button alt">
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
});

function openSTKModal() {
  const modal = createModal({
    title: 'Lipa na M-PESA',
    onCancel: () => {
      document.body.removeChild(modal);
    },
    onConfirm: () => {
      alert('Payment confirmed!');
      document.body.removeChild(modal);
    },
    children: `
      <p>This will initiate an M-Pesa STK push to your phone.</p>
      <p>Please check your phone and enter your M-Pesa PIN to complete the payment.</p>
    `,
  });

  document.body.appendChild(modal);
}

function getOrderIdFromUrl() {
  const pathParts = window.location.pathname.split('/');
  const idx = pathParts.indexOf('order-received');
  if (idx !== -1 && pathParts[idx + 1]) {
    return pathParts[idx + 1];
  }
  return null;
}


function createModal({ title, onCancel, onConfirm, children }) {
  // Overlay
  const overlay = document.createElement('div');
  overlay.className = 'modal-overlay';

  // Modal body
  const body = document.createElement('div');
  body.className = 'modal-body';

  // Prevent click inside modal from closing it
  body.addEventListener('click', (e) => e.stopPropagation());

  // Modal title
  const modalTitle = document.createElement('h3');
  modalTitle.className = 'modal-title';
  modalTitle.textContent = title;

  // Close button
  const closeButton = document.createElement('button');
  closeButton.className = 'modal-close-btn';
  closeButton.setAttribute('aria-label', 'Close modal');
  closeButton.textContent = '×';
  closeButton.addEventListener('click', onCancel);

  // Modal content
  const content = document.createElement('div');
  content.className = 'modal-content';

  if (typeof children === 'string') {
    content.innerHTML = children;
  } else if (children instanceof Node) {
    content.appendChild(children);
  }

  // Action buttons container
  const actions = document.createElement('div');
  actions.className = 'modal-actions';

  // Confirm button
  const confirmBtn = document.createElement('button');
  confirmBtn.className = 'modal-btn modal-btn-confirm';
  confirmBtn.textContent = 'Proceed to pay';
  confirmBtn.addEventListener('click', onConfirm);

  // Assemble actions
  actions.appendChild(confirmBtn);

  // Assemble modal content
  body.appendChild(modalTitle);
  body.appendChild(closeButton);
  body.appendChild(content);
  body.appendChild(actions);

  // Assemble overlay
  overlay.appendChild(body);

  // Append to body
  document.body.appendChild(overlay);

  return overlay;
}
