(function ($, templates) {
    function togglePlaceOrderButton() {
      const $btn = $('#place_order');
      const chosenMethod = $('input[name="payment_method"]:checked').val();

      if (chosenMethod === 'kkwoo') {
          $btn
              .text('Proceed to Lipa NA M-PESA')
              .attr('data-value', 'Proceed to Lipa NA M-PESA')
              .addClass('k2');
      } else {
          $btn
              .text('Place order')
              .attr('data-value', 'Place order')
              .removeClass('k2');
      }
    }

    function renderSection(removeSectionId, renderFn) {
      const $existing = $("#" + removeSectionId);
      if ($existing.length) $existing.remove();

      const htmlString = renderFn();
      const $newSection = $(htmlString);

      $(".k2 .modal-content").append($newSection);
    }

    function populateCheckoutInfo() {
      $("#currency").text(KKWooData.currency);
      $("#total-amount").text(KKWooData.total_amount);
      $("#store-name").text(KKWooData.store_name);
    }

    function validMpesaNumber(phone) {
      const $error = $(".message.error");

      if (!phone) {
        $error.text("Phone number is required.").show();
        return false;
      }
      
      if (!/^\d{9}$/.test(phone)) {
        $error.text("Phone number must be 9 digits.").show();
        return false;
      }

      $error.hide();
      return true;
    }

    function addRetryPaymentListener(removeSectionId) {
      $("#retry-payment")
        .off("click")
        .on("click", () => {
          renderSection(removeSectionId, templates.MpesaNumberForm);
          populateCheckoutInfo();
        });
    }

    function addRedirectToOrderReceived() {
      let seconds = 10;
      const $countdown = $("#countdown");

      const timer = setInterval(() => {
        seconds--;
        $countdown.text(seconds < 10 ? "00:0" + seconds : "00:" + seconds);

        if (seconds <= 0) {
          clearInterval(timer);
          window.location.href = KKWooData.order_received_url;
        }
      }, 1000);

      $("#redirect-to-order-received")
      .off("click")
      .on("click", () => {
        clearInterval(timer);
        window.location.href = KKWooData.order_received_url;
      });
    }

    const DefaultPollingCallbacks = {
      onSuccess: (data, sectionId) => {
        renderSection(sectionId, templates.PaymentSuccess);
        populateCheckoutInfo();
        addRedirectToOrderReceived();
      },
      onFailure: (data, sectionId) => {
        renderSection(sectionId, templates.PaymentError);
        addRetryPaymentListener("payment-error");
      },
      onNoResult: (sectionId) => {
        renderSection(sectionId, templates.PaymentNoResultYet);
      },
    };
  
    async function initiatePayment(phone) {
      try {
        const response = await fetch(KKWooData.rest_url, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-WP-Nonce": KKWooData.nonce,
          },
          body: JSON.stringify({ phone, order_key: KKWooData.order_key }),
        });

        const data = await response.json();

        if (response.ok) {
          PollingManager.timeout = setTimeout(() => {
            PollingManager.start(DefaultPollingCallbacks, "pin-instruction", false);
          }, 40 * 1000);
        } else {
          renderSection("mpesa-number-form", () =>
            `<p class="error">Payment failed: ${data.message}</p>`
          );
        }
      } catch (err) {
        renderSection("mpesa-number-form", () =>
          `<p class="error">Unexpected error. Please try again.</p>`
        );
      }
    }

    // Run whenever payment method changes
    $(document).on('change', 'input[name="payment_method"]', togglePlaceOrderButton);

    // Run after Woo updates checkout (important for when page refreshes to ensure consistency of selection internally in Woo and whats visually selected)
    $(document.body).on('updated_checkout payment_method_selected', togglePlaceOrderButton);

    $(document).on("click", "#proceed-to-pay-btn", (e) => {
      e.preventDefault();
      const phone = $("#mpesa-phone-input").val().trim();

      if (!validMpesaNumber(phone)) return;

      renderSection("mpesa-number-form", templates.PinInstruction);
      initiatePayment(phone);
    });

    $(document).on("click", "#proceed-to-poll", (e) => {
      e.preventDefault();
      PollingManager.stop();
      renderSection("pin-instruction", templates.Polling);
      PollingManager.start(DefaultPollingCallbacks);
    });

    // Initial setup
    renderSection("mpesa-number-form", templates.MpesaNumberForm);
    populateCheckoutInfo();
})(jQuery, window.KKWooTemplates);

