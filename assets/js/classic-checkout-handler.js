(function ($) {
  function togglePlaceOrderButton() {
    const $btn = $('#place_order');
    const chosenMethod = $('input[name="payment_method"]:checked').val();

    if (chosenMethod === 'kkwoo') {
        $btn
            .text('Proceed to Lipa na M-PESA')
            .attr('data-value', 'Proceed to Lipa na M-PESA')
            .addClass('k2');
    } else {
        $btn
            .text('Place order')
            .attr('data-value', 'Place order')
            .removeClass('k2');
    }
  }

  // Run whenever payment method changes
  $(document).on('change', 'input[name="payment_method"]', togglePlaceOrderButton);

  // Run after Woo updates checkout (important for when page refreshes to ensure consistency of selection internally in Woo and whats visually selected)
  $(document.body).on('updated_checkout payment_method_selected', togglePlaceOrderButton);
})(jQuery);
