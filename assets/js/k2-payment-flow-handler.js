/*
* Controls whether the page should auto-refresh or allow redirection.
* When set to false, it prevents unintended page unloads or navigations, 
* helping us distinguish between user-initiated and system-initiated redirects.
*/
let autoRefreshPage = false;

window.addEventListener("beforeunload", function (e) {
  if(autoRefreshPage==false){
    console.log("in beforeunload: ", autoRefreshPage? 'true': 'false')
    e.preventDefault();
    e.returnValue = ""; // Required for modern browsers
  }
});

(function ($, templates, validations) {
    function renderSection(renderFn) {
      const modalContent = $(".k2 .modal-content");
      modalContent.empty();

      const htmlString = renderFn();
      const $newSection = $(htmlString);

      modalContent.append($newSection);
    }

    function populateCheckoutInfo() {
      $("#currency").text(KKWooData.currency);
      $("#total-amount").text(KKWooData.total_amount);
      $("#store-name").text(KKWooData.store_name);
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
        console.log('on failure data: ', errorMessage)
        renderSection(()=>templates.PaymentError(errorMessage));
      },
      onNoResult: () => {
        autoRefreshPage = true;
        renderSection(templates.PaymentNoResultYet);
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
          $('#proceed-to-poll').prop('disabled', false);
          PollingManager.timeout = setTimeout(() => {
            PollingManager.start(DefaultPollingCallbacks, "pin-instruction", false);
          }, 40 * 1000);
        } else {
          PollingManager.stop();
          const errorMessage = data.data.data.errorMessage ?? data.data;
          renderSection(()=>templates.PaymentError(errorMessage));
        }
      } catch (err) {
          PollingManager.stop();
          renderSection(templates.PaymentError);
      }
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
      window.location.href = KKWooData.this_order_url;
    }
})(jQuery, window.KKWooTemplates, window.KKWooValidations);


