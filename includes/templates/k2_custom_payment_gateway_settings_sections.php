<?php
defined('ABSPATH') || exit;
?>
<style>
    .kkwoo-admin-buttons {
      display: flex;
      gap: 40px;
      margin-top: 20px;
    }

    .kkwoo-admin-buttons .kkwoo-button-block {
      flex: 1;
      padding: 20px;
      border: 1px solid #ccd0d4;
      background: #fff;
      border-radius: 4px;
    }

    .kkwoo-admin-buttons h2 {
      margin-top: 0;
    }

    .kkwoo-message {
      margin-top: 10px;
      font-size: 13px;
    }

    .kkwoo-message.message {
      color: #46b450;
    }

    .kkwoo-message.error {
      color: #dc3232;
    }
    
    .kkwoo-loading {
      opacity: 0.6;
      pointer-events: none;
      position: relative;
    }

    .kkwoo-loading::before {
      content: '';
      position: absolute;
      left: calc(50% - 8px);
      top: 50%;
      width: 16px;
      height: 16px;
      margin-top: -8px;
      border: 2px solid #ccc;
      border-top-color: #333;
      border-radius: 50%;
      animation: kkwoo-spin 0.6s linear infinite;
    }

    @keyframes kkwoo-spin {
      to {
        transform: rotate(360deg);
      }
}
</style>

<div class="kkwoo-admin-buttons" id="kkwoo-admin-buttons">
    <!-- Webhook Subscriptions -->
    <div class="kkwoo-button-block">
        <h2>Create Webhook Subscriptions</h2>
        <p>To automatically update orders when manual payments are enabled, Kopo Kopo for WooCommerce relies on Webhooks to notify your site when a payment is received. If you want orders to move to "Processing" automatically after payment, make sure you have subscribed to the appropriate Webhooks in your Kopo Kopo App.</p>

        <p>Verify the following URLs are listed among your webhook subscriptions:<br>
            <code>&lt;your-domain&gt;/wp-json/kkwoo/v1/buygoods_transaction_received</code><br>
            <code>&lt;your-domain&gt;/wp-json/kkwoo/v1/b2b_transaction_received</code></p>

        <button type="button"
            class="button button-secondary kkwoo-action-button"
            data-endpoint="/wp-json/kkwoo/v1/create-webhook-subscriptions"
            data-target="#kkwoo-webhook-message">
            Create Webhook Subscriptions
        </button>
        <div class="kkwoo-message" id="kkwoo-webhook-message"></div>
    </div>

    <!-- Refresh Access Token -->
    <div class="kkwoo-button-block">
        <h2>Refresh Access Token</h2>
        <p>Manually refresh your Kopo Kopo access token. This is typically handled automatically, but if you're encountering authorization errors, use this button to force a refresh.</p>

        <button type="button"
            class="button button-secondary kkwoo-action-button"
            data-endpoint="/wp-json/kkwoo/v1/force-refresh-access-token"
            data-target="#kkwoo-token-message">
            Refresh Access Token
        </button>
        <div class="kkwoo-message" id="kkwoo-token-message"></div>
    </div>
</div>

<script>
jQuery(document).ready(function ($) {
  $('.kkwoo-action-button').on('click', function () {
    const $button = $(this);
    const endpoint = $button.data('endpoint');
    const $messageBox = $($button.data('target'));

    $messageBox.removeClass('message error');

    $.ajax({
      url: endpoint,
      method: 'POST',
      beforeSend: function (xhr) {
        xhr.setRequestHeader('X-WP-Nonce', '<?php echo esc_js(wp_create_nonce("wp_rest")); ?>');
        $button.prop("disabled", true).addClass('kkwoo-loading');;
      },
      success: function (response) {
          $messageBox.addClass('message').text(response.message || 'Action completed successfully.');
      },
      error: function (xhr) {
          let errorMsg = 'Something went wrong.';
          if (xhr.responseJSON && xhr.responseJSON.message) {
              errorMsg = xhr.responseJSON.message;
          }
          $messageBox.addClass('error').text(errorMsg);
      },
      complete: function () {
        $button.prop("disabled", false).removeClass('kkwoo-loading');
      },
    });
  });

  function toggleManualPaymentFields() {
      const selected = $('#woocommerce_kkwoo_manual_payment_method').val();
      const tillRow = $('#woocommerce_kkwoo_manual_payments_till_no').closest('tr');
      const paybillBizRow = $('#woocommerce_kkwoo_paybill_business_no').closest('tr');
      const paybillAccRow = $('#woocommerce_kkwoo_paybill_account_no').closest('tr');

      if (selected === 'till') {
          tillRow.show();
          paybillBizRow.hide();
          paybillAccRow.hide();
      } else if (selected === 'paybill') {
          tillRow.hide();
          paybillBizRow.show();
          paybillAccRow.show();
      } else {
          tillRow.hide();
          paybillBizRow.hide();
          paybillAccRow.hide();
      }
  }

  // Run on load
  toggleManualPaymentFields();

  // Run when dropdown changes
  $('#woocommerce_kkwoo_manual_payment_method').on('change', toggleManualPaymentFields);
});
</script>
