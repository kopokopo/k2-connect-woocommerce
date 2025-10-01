/*
* Controls whether the page should auto-refresh or allow redirection.
* When set to false, it prevents unintended page unloads or navigations, 
* helping us distinguish between user-initiated and system-initiated redirects.
*/
let autoRefreshPage = false;

window.addEventListener("beforeunload", function (e) {
  if(autoRefreshPage==false){
    e.preventDefault();
    e.returnValue = ""; // Required for modern browsers
  }
});

(function ($, templates, validations) {
    function renderSection(renderFn, withManualPaymentSection = false) {
      const modalContent = $(".k2 .modal-content");
      modalContent.empty();

      const htmlString = renderFn();
      const $newSection = $(htmlString);

      modalContent.append($newSection);
      if(withManualPaymentSection && KKWooData.selected_manual_payment_method){
        const selectedMethod = KKWooData.selected_manual_payment_method==='till'?'M-PESA Buy Goods ':'M-PESA Pay Bill'
        $(".switch-to-manual-payments").show()
        $("#switch-to-manual-payments").text(selectedMethod);

      }else{
        $(".switch-to-manual-payments").hide();
      }
    }

    function populateCheckoutInfo() {
      $("#currency").text(KKWooData.currency);
      $("#total-amount").text(KKWooData.total_amount);
      $("#store-name").text(KKWooData.store_name);
    }

    function populateManualPaymentInfo(data) {
      $("#currency").text(KKWooData.currency);
      $("#total-amount").text(KKWooData.total_amount);
      $("#store-name").text(KKWooData.store_name);
      $("#instruction-currency").text(KKWooData.currency);
      $("#instruction-total-amount").text(KKWooData.total_amount);

      switch (KKWooData.selected_manual_payment_method) {
        case 'till':
          $("#payment-method").text('Buy Goods and Services');
          $("#payment_method_title").text('Till');
          $("#till-or-paybill-number").text(data?.till);
          $(".for-paybill").hide();
          $("#currency").text(KKWooData.currency);
          break;
        case 'paybill':
          $("#payment-method").text('Pay Bill');
          $("#payment_method_title").text('Business');
          $("#till-or-paybill-number").text(data?.paybill?.business_no);
          $(".for-paybill").show();
          $("#account-number").text(data?.paybill?.account_no);
          break;
        default:
          break;
      }
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
      onSuccess: (data) => {
        autoRefreshPage = true;
        renderSection(templates.PaymentSuccess);
        populateCheckoutInfo();
        addRedirectToOrderReceived();
      },
      onFailure: (data) => {
        const errorMessage = data.data;
        renderSection(() => templates.PaymentError(errorMessage));
      },
      onNoResult: () => {
        autoRefreshPage = true;
        renderSection(templates.PaymentNoResultYet);
      },
    };

    function initiatePayment(phone) {
      $.ajax({
        url: "/wp-json/kkwoo/v1/stk-push",
        method: "POST",
        contentType: "application/json",
        headers: {
          "X-WP-Nonce": KKWooData.nonce,
        },
        data: JSON.stringify({
          phone: phone,
          order_key: KKWooData.order_key,
        }),
        success: function () {
          $("#proceed-to-poll").prop("disabled", false);

          PollingManager.timeout = setTimeout(function () {
            PollingManager.start(
              DefaultPollingCallbacks,
              "pin-instruction",
              false
            );
          }, 40 * 1000);
        },
        error: function (jqXHR) {
          PollingManager.stop();
          let errorMessage;
          try {
            const response = jqXHR.responseJSON;
            errorMessage =
              response?.data?.data?.errorMessage ??
              response?.data ??
              "Something went wrong. Please try again.";
          } catch (e) {
            errorMessage = "Something went wrong. Please try again.";
          }
          renderSection(() => templates.PaymentError(errorMessage), true);
        },
      });
    }

    function saveManualPaymentDetails(mpesaRefNo) {
      $.ajax({
        url: "/wp-json/kkwoo/v1/save-manual-payment-details",
        method: "POST",
        contentType: "application/json",
        headers: {
          "X-WP-Nonce": KKWooData.nonce,
        },
        data: JSON.stringify({
          mpesa_ref_no: mpesaRefNo,
          order_key: KKWooData.order_key,
        }),
        beforeSend: function () {
          $("#submit-manual-payment-details").prop("disabled", true);
        },
        success: function (data) {
          autoRefreshPage = true;
          renderSection(() => templates.PaymentNoResultYet(data.message??''));
        },
        error: function (jqXHR) {
          let errorMessage;
          try {
            const response = jqXHR.responseJSON;
            errorMessage =
              response?.data?.data?.errorMessage ??
              response?.data ??
              response?.message ??
              "Something went wrong. Please try again.";
          } catch (e) {
            errorMessage = "Something went wrong. Please try again.";
          }

          renderSection(() => templates.PaymentError(errorMessage), true);
        },
        complete: function () {
          $("#submit-manual-payment-details").prop("disabled", false);
        },
      });
    }

    function getSelectedManualPaymentMethod() {
      $.ajax({
        url: "/wp-json/kkwoo/v1/selected-manual-payment-method",
        method: "GET",
        contentType: "application/json",
        headers: {
          "X-WP-Nonce": KKWooData.nonce,
        },
        beforeSend: function () {
          $("#switch-to-manual-payments").prop("disabled", true);
        },
        success: function (data) {
          renderSection(templates.ManualPaymentInstructions);
          populateManualPaymentInfo(data.data);
        },
        error: function (jqXHR) {
          let errorMessage;
          try {
            const response = jqXHR.responseJSON;
            errorMessage =
              response?.data?.data?.errorMessage ??
              response?.data ??
              response?.message ??
              "Something went wrong. Please try again.";
          } catch (e) {
            errorMessage = "Something went wrong. Please try again.";
          }

          renderSection(() => templates.PaymentError(errorMessage), true);
        },
        complete: function () {
          $("#switch-to-manual-payments").prop("disabled", false);
        },
      });
    }

    $(document).on("click", "#proceed-to-pay-btn", (e) => {
      e.preventDefault();
      const phone = $("#mpesa-phone-input").val().trim();

      if (!validations.validMpesaNumber(phone)) return;

      renderSection(templates.PinInstruction);
      initiatePayment(phone);
    });

    $(document).on("click", "#proceed-to-poll", (e) => {
      e.preventDefault();
      PollingManager.stop();
      renderSection(templates.Polling);
      PollingManager.start(DefaultPollingCallbacks);
    });

    $(document).on("click", "#retry-payment", (e) => {
      e.preventDefault();
      renderSection(templates.MpesaNumberForm);
      populateCheckoutInfo();
    });
    
    $(document).on("click", "#redirect-to-order", (e) => {
      e.preventDefault();
      window.location.href = KKWooData.this_order_url;
    });

    $(document).on("click", "#switch-to-manual-payments", (e) => {
      e.preventDefault();
      getSelectedManualPaymentMethod();
    });

    $(document).on("click", "#submit-manual-payment-details", (e) => {
      e.preventDefault();
      const mpesaRefNo = $("#mpesa-ref-input").val().trim();

      if (!validations.validMpesaRefNo(mpesaRefNo)) return;

      saveManualPaymentDetails(mpesaRefNo);
    })


    // Initial setup --- Order statuses -> pending, on-hold, processing, completed, failed, cancelled, refunded
    const orderStatus = KKWooData.order_status;
    if(orderStatus === "pending" || orderStatus === "failed"){
      renderSection(templates.MpesaNumberForm);
      populateCheckoutInfo();
    }else if(orderStatus === "on-hold"){
      autoRefreshPage = true;
      renderSection(templates.PaymentNoResultYet);
      populateCheckoutInfo();
    }else if(orderStatus === "refunded"){
      autoRefreshPage = true;
      renderSection(templates.PaymentRefunded);
      populateCheckoutInfo();
    }else if(orderStatus === "processing" || orderStatus === "completed" || orderStatus === "cancelled" ) {
      autoRefreshPage = true;
      window.location.href = KKWooData.this_order_url;
    }
})(jQuery, window.KKWooTemplates, window.KKWooValidations);
