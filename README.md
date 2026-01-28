# Kopo Kopo for WooCommerce
Enable instant, secure Lipa na M-PESA payments on your WooCommerce shop and make checkout simple for your customers.

### Accept payments in real-time
- Let your customers pay securely using Lipa na M-PESA at checkout.
- Receive funds directly to your Kopo Kopo account in real time.
- Provide a seamless checkout experience.
- Ensure no payment is lost with optional manual fallback via Lipa na M-PESA till number or Paybill.

# Get started
New to Kopo Kopo? Open an account today and make it easy for your customers to pay you.

Already a Kopo Kopo customer? Simply install the plugin, connect your account and start receiving payments instantly.


# Installation
#### Requirements:
- WordPress Version 6.7 or newer (installed).
- WooCommerce Version 9.2 or newer (installed and activated).
- PHP Version 7.4 or newer.
- Kopo Kopo account.

## Installation Instructions
1. Log in to your WordPress admin dashboard.
2. Navigate to **Plugins > Add New**.
3. In the search bar, type **Kopo Kopo for WooCommerce**.
4. Click Install Now and wait for the plugin to finish installing.
5. After installation, click **Activate Now** to enable the plugin immediately.

**Note**: You can activate it later via **Plugins > Installed Plugins**.

## Setup and configuration
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
   * To have orders move to **Processing** automatically after a manual payment, make sure you subscribe to the appropriate Webhooks in your Kopo Kopo App.

## Troubleshooting
If Kopo Kopo for WooCommerce does not appear in the payment options:
1. Check for any warnings or error messages at the top of the plugin settings page.
2. Confirm the following:
   * **Enable/Disable** checkbox is checked.
   * Your **Till Number** is correctly entered under STK Push settings.
   * All **K2 Connect API credentials** are entered correctly.
   * You clicked **Save Changes** during setup.

## License
[License](LICENSE)

## Contributing
All feedback / bug reports / pull requests are welcome.

See the [Contributing guidelines](CONTRIBUTING.md) for:
- Branching and commit guidelines
- Running tests and submitting PRs

### Issues & Support
- Report bugs or request features on the issue tracker
- Include Wordpress/WooCommerce/PHP version, reproduction steps, and logs where possible
- Response times are on a best-effort basis
For urgent production issues, please use official Kopo Kopo support channels

## Changelog
See the [CHANGELOG.md](CHANGELOG.md) for release history.

Latest release:

**1.0.0 - Initial Release**
- Initial release of k2_connect_woocommerce
- Support for Lipa na M-PESA STK Push payments at checkout.
- Webhooks for processing manual payments completed through Paybill and Till numbers acquired with Kopo Kopo.
