<?php
defined('ABSPATH') || exit;
?>

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
