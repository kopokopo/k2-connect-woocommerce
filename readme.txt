=== Kopo Kopo for WooCommerce ===
Contributors: chemwen0
Tags: payment gateway, kopo kopo, payments, lipa na mpesa, ecommerce
Requires at least: 6.8
Tested up to: 6.9
Stable tag: 1.0.1
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Enable instant, secure Lipa na M-PESA payments on your WooCommerce shop and make checkout simple for your customers.

== Description ==

**Kopo Kopo for WooCommerce** helps your online sales grow by providing smooth, secure Lipa na M-PESA payments for your customers, while allowing you receive funds directly to your Kopo Kopo account.

**Tens of thousands of Kenyan businesses trust Kopo Kopo to accept digital payments, manage outgoing payments and access credit.** 

### Accept payments in real-time
- Let your customers pay securely using Lipa na M-PESA at checkout.
- Receive funds directly to your Kopo Kopo account in real time.
- Provide a seamless checkout experience.
- Ensure no payment is lost with optional manual fallback via Lipa na M-PESA till number or Paybill.

### Manage your payments effortlessly
- Connect your WooCommerce store to your Kopo Kopo account with a few simple steps.
- Use K2 Connect API keys for secure and reliable transactions.
- Keep track of payments, orders and reports from your WooCommerce dashboard.
- Automatically update orders when manual payments are received using Kopo Kopo Webhooks.

### Data retention
To preserve order history and payment integrity, uninstalling this plugin removes its settings but keeps order-related payment data. This allows existing orders to remain verifiable and enables recovery if the plugin is reinstalled.

### Get started
**New to Kopo Kopo?** [Open an account today](https://kopokopo.co.ke/get-started/) and make it easy for your customers to pay you.

**Already a Kopo Kopo customer?** Simply install the plugin, connect your account and start receiving payments instantly.

### We value your feedback
If you have a specific feature request, please [let us know](https://kopokopo.co.ke/contact-us/?referrer=wordpress.com) so we can make the service perfect for you.

== External Services ==

This plugin connects to the [K2 Connect API](https://developers.kopokopo.com/) provided by [Kopo Kopo](https://kopokopo.co.ke/) to enable Lipa na M-PESA payment processing. K2 Connect is used to initiate STK Push payment requests, receive payment confirmations and send transaction updates to the shop. This service is required for the core functionality of the plugin.
### What data is sent and when:
**Data sent**: order reference number, order amount, customer phone number, till or paybill number(s) and K2 Connect credentials required to authenticate and process the payment.

**Data received**: payment status, transaction responses, transaction results and webhook notifications.

**When**: data is transmitted each time a customer selects this payment method at checkout, when an STK Push request is initiated or when the plugin verifies or receives payment confirmation through callbacks or webhooks.
### Where the data is sent:
The data is sent to Kopo Kopo servers.
### Under which conditions:
Data is only sent when:
* The Kopo Kopo for WooCommerce plugin is activated and is being setup.
* The customer chooses Lipa na M-PESA as the payment method and initiates a payment.
* The plugin verifies or receives payment status updates

No data is transmitted without user action. All data is transmitted for the purposes of authentication of payment requests, creation of webhooks to receive payment updates and processing of transactions.
### Terms of Service and Privacy Policy:
- [Kopo Kopo Terms and Conditions](https://kopokopo.co.ke/terms-conditions/)
- [Kopo Kopo Privacy Policy](https://app.kopokopo.com/privacy)

== Installation ==
#### Requirements:
- WordPress Version 6.7 or newer (installed).
- WooCommerce Version 9.2 or newer (installed and activated).
- PHP Version 7.4 or newer.
- Kopo Kopo account.

### Installation Instructions
1. Log in to your WordPress admin dashboard.
2. Navigate to **Plugins > Add New**.
3. In the search bar, type **Kopo Kopo for WooCommerce**.
4. Click Install Now and wait for the plugin to finish installing.
5. After installation, click **Activate Now** to enable the plugin immediately.

**Note**: You can activate it later via **Plugins > Installed Plugins**.

### Setup and configuration
#### Follow these steps to connect the plugin to your Kopo Kopo account:
1. Go to **WooCommerce > Settings**.
2. Click the **Payments** tab.
3. Locate **Kopo Kopo for WooCommerce** and click **Manage**.
4. Configure the plugin settings as described below:
   * **Enable/Disable** - Check this box to activate Kopo Kopo for WooCommerce on your online shop checkout page.
   * **Title** - This appears as the payment option during checkout. The default title is Lipa na M-PESA. **This cannot be changed**.
   * **Description** - This message appears under the payment fields to guide your customers. Default text: Click "Proceed to Lipa na M-PESA" below to pay with M-PESA. **This cannot be changed**.
 
   ##### STK Push settings
   * **Till number** - Enter the Lipa na M-PESA number that will receive STK Push payments. This must be an online payment account in your Kopo Kopo dashboard under **My Tills**.
   * **Client ID** - Enter your K2 Connect Client ID.
   * **Client secret** - Enter your K2 Connect Client Secret.
   * **API key** - Enter your K2 Connect API Key.
   * **Environment** - Select the environment you want to use: Sandbox for testing or Production for live payments. **Ensure your credentials match the selected environment** to avoid errors.

   ##### Manual payment settings
   * **Enable/Disable Manual Payments** - Use this option to provide a Lipa na M-PESA till number or Paybill details as a fallback to STK Push. After enabling, click **Create Webhook Subscriptions** at the bottom of the page. 
   * **Manual Payment Method** - Choose either **Till** or **Paybill** from the dropdown. If both are provided, the Till option will take priority.
   * **Till Number / Paybill Business Number & Paybill Account Number** - Enter the account details for the manual fallback.

##### Webhooks
To automatically update orders when manual payments are enabled, the plugin uses Webhooks to notify your site when a payment is received.
- To have orders move to **Processing** automatically after a manual payment, make sure you subscribe to the appropriate Webhooks in your Kopo Kopo App.

### Troubleshooting
If Kopo Kopo for WooCommerce does not appear in the payment options:
1. Check for any warnings or error messages at the top of the plugin settings page.
2. Confirm the following:
   * **Enable/Disable** checkbox is checked.
   * Your **Till Number** is correctly entered under STK Push settings.
   * All **K2 Connect API credentials** are entered correctly.
   * You clicked **Save Changes** during setup.

== Frequently Asked Questions ==

= What do I need to use the plugin? =
- A Kopo Kopo account. Use an existing account or [open an account](https://kopokopo.co.ke/get-started/).
- An active WooCommerce store.
- A valid SSL certificate.

= Is this plugin available outside Kenya? =
No. This plugin is built specifically for businesses in Kenya with Kopo Kopo accounts.

= What is K2 Connect? =
K2 Connect provides everything you need, including powerful APIs and SDKs, to seamlessly integrate payments into your business, including your WooCommerce shop, helping you provide a smooth checkout experience for your customers. [Learn more about K2 Connect](https://kopokopo.co.ke/developers/).

= Can I test payments before going live? =
To test payments before going live, sign up for a sandbox account here and configure your sandbox credentials in the plugin settings.

= What happens to my data if I uninstall the plugin? =
When you uninstall the plugin, only the plugin settings are removed. All order-related payment data is retained to protect order history and payment records. This ensures existing orders remain verifiable and allows you to restore functionality if the plugin is reinstalled.

= Where can I get support or talk to other users? =
Reach out to our support team through any of the following channels: call 0709 376 000 or 020 790 3030, WhatsApp 0709 376 000, email support@kopokopo.com, or join our Discord channel.

== Changelog ==

= 1.0.0 =
* Initial release of k2_connect_woocommerce
* Support for Lipa na M-PESA STK Push payments at checkout.
* Webhooks for processing manual payments completed through Paybill and Till numbers acquired with Kopo Kopo.

