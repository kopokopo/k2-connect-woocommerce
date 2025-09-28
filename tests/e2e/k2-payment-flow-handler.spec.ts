import { Browser, BrowserContext, chromium, Page } from "@playwright/test";
import { test, expect } from "./fixtures/create-order-fixture";
import { initSTKPushError, initSTKPushSuccess } from "./json/stk-push-data";
import {
  getPaymentStatusError,
  getPaymentStatusOnHold,
  getPaymentStatusProcessing,
} from "./json/payment-status-data";

let browser: Browser;
let context: BrowserContext;
let page: Page;

const WP_SITE_URL = process.env.WP_SITE_URL;
const WP_CUSTOMER_USER_NAME = process.env.WP_CUSTOMER_USER_NAME;
const WP_CUSTOMER_PASSWORD = process.env.WP_CUSTOMER_PASSWORD;

test.describe("Unit Tests for the Lipa na M-PESA payment flow", () => {
  test("the mpesa number section details are correct", async ({
    page,
    order,
  }) => {
    const newOrder = await order();
    await page.goto(
      `${WP_SITE_URL}/lipa-na-mpesa-k2/?order_key=${newOrder.order_key}`
    );
    await expect(page.locator("#currency")).toHaveText("KSh");
    await expect(page.locator("#total-amount")).toHaveText(`${newOrder.total}`);
  });

  test("the phone number validations work as desired", async ({
    page,
    order,
  }) => {
    const newOrder = await order();
    await page.goto(
      `${WP_SITE_URL}/lipa-na-mpesa-k2/?order_key=${newOrder.order_key}`
    );
    page.click("#proceed-to-pay-btn");
    await expect(page.locator(".message.error")).toHaveText(
      "Phone number is required."
    );

    await page.fill("#mpesa-phone-input", "9234");
    page.click("#proceed-to-pay-btn");
    await expect(page.locator(".message.error")).toHaveText(
      "Phone number must be 9 digits."
    );
  });

  test("the user is taken to the error page when there is failure in initiating an STK push request", async ({
    page,
    order,
  }) => {
    const newOrder = await order();
    await page.goto(
      `${WP_SITE_URL}/lipa-na-mpesa-k2/?order_key=${newOrder.order_key}`
    );

    await page.fill("#mpesa-phone-input", "923456789");
    page.click("#proceed-to-pay-btn");

    await page.route("**/wp-json/kkwoo/v1/stk-push", async (route) => {
      await route.fulfill({
        status: 400,
        contentType: "application/json",
        body: JSON.stringify(initSTKPushError),
      });
    });
    await expect(page.locator(".side-note")).toHaveText(
      "Invalid phone number format"
    );
  });

  test("the user is taken to the error page when an error result is received when checking status", async ({
    page,
    order,
  }) => {
    const newOrder = await order();
    await page.goto(
      `${WP_SITE_URL}/lipa-na-mpesa-k2/?order_key=${newOrder.order_key}`
    );

    await page.fill("#mpesa-phone-input", "923456789");
    page.click("#proceed-to-pay-btn");

    await page.route("**/wp-json/kkwoo/v1/stk-push", async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify(initSTKPushSuccess),
      });
    });
    await expect(page.locator("#proceed-to-poll")).not.toBeDisabled();
    page.click("#proceed-to-poll");

    await page.route("**/wp-json/kkwoo/v1/payment-status", async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify(getPaymentStatusError),
      });
    });
    await expect(page.locator(".main-info")).toHaveText("Processing payment");
  });

  test("the user is taken to the 'waiting to receive funds' view when the result is not received before the set timeout", async ({
    page,
    order,
  }) => {
    const newOrder = await order();
    await page.clock.install({ time: new Date("2025-02-02T08:00:00") });
    await page.goto(
      `${WP_SITE_URL}/lipa-na-mpesa-k2/?order_key=${newOrder.order_key}`
    );

    await page.fill("#mpesa-phone-input", "923456789");
    page.click("#proceed-to-pay-btn");

    await page.clock.pauseAt(new Date("2025-02-02T08:10:00"));
    await page.route("**/wp-json/kkwoo/v1/stk-push", async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify(initSTKPushSuccess),
      });
    });
    await expect(page.locator("#proceed-to-poll")).not.toBeDisabled();
    await page.clock.fastForward("00:45");

    await page.route("**/wp-json/kkwoo/v1/payment-status", async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify(getPaymentStatusOnHold),
      });
    });

    await expect(page.locator(".main-info")).toHaveText(
      "Waiting to receive funds"
    );
  });

  test("the user is taken to the 'M-PESA number form' view when they reload page while payment is pending", async ({
    page,
    order,
  }) => {
    const newOrder = await order("pending");
    await page.goto(
      `${WP_SITE_URL}/lipa-na-mpesa-k2/?order_key=${newOrder.order_key}`
    );

    await page.reload();

    await expect(page.locator(".modal-title")).toHaveText("Lipa na M-PESA");
  });

  test("the user is taken to the 'waiting to receive funds' view when they reload page while payment is on hold", async ({
    page,
    order,
  }) => {
    const newOrder = await order("on-hold");
    await page.goto(
      `${WP_SITE_URL}/lipa-na-mpesa-k2/?order_key=${newOrder.order_key}`
    );

    await page.reload();

    await expect(page.locator(".main-info")).toHaveText(
      "Waiting to receive funds"
    );
  });

  test("the user is taken to the 'payment failed' view when they reload page if payment failed", async ({
    page,
    order,
  }) => {
    const newOrder = await order("failed");
    await page.goto(
      `${WP_SITE_URL}/lipa-na-mpesa-k2/?order_key=${newOrder.order_key}`
    );

    await page.reload();

    await expect(page.locator(".modal-title")).toHaveText("Lipa na M-PESA");
  });

  test("the user is taken to the 'order received' page when they reload page if payment is successful", async ({
    page,
    order,
  }) => {
    const newOrder = await order("processing");
    await page.goto(
      `${WP_SITE_URL}/lipa-na-mpesa-k2/?order_key=${newOrder.order_key}`
    );

    await page.reload();

    await expect(page).toHaveURL(/checkout\/order-received/);
  });

  test("the user is taken to the 'order received' page when they click 'Done' in the 'waiting to receive funds' view", async ({
    page,
    order,
  }) => {
    const newOrder = await order("on-hold");
    await page.goto(
      `${WP_SITE_URL}/lipa-na-mpesa-k2/?order_key=${newOrder.order_key}`
    );

    await page.reload();

    await expect(page.locator(".main-info")).toHaveText(
      "Waiting to receive funds"
    );
  });
});

test.describe("Kopo Kopo for WooCommerce Payment Flow (Guest User)", () => {
  test.beforeAll(async ({}, testInfo) => {
    // Launch browser in headed mode
    browser = await chromium.launch({ headless: false, slowMo: 50 });
    const context = await browser.newContext();
    page = await context.newPage();
  });

  test.afterAll(async () => {
    await browser.close();
  });

  test("Full flow: Shop → Checkout → Payment(Success) → Order Received", async () => {
    test.setTimeout(45_000);

    // Shop
    await page.goto(`${WP_SITE_URL}/shop/`);
    await page.click(".product a");
    await page.click("button.single_add_to_cart_button");

    // Cart → Checkout
    await page.goto(`${WP_SITE_URL}/cart/`);
    await page.locator("text=Proceed to Checkout").click();

    // Billing
    await page.fill("#billing_first_name", "Doreen");
    await page.fill("#billing_last_name", "Chemweno");
    await page.fill("#billing_email", "test@example.com");
    await page.fill("#billing_phone", "0923456789");
    await page.fill("#billing_address_1", "123 Street");
    await page.fill("#billing_city", "Nairobi");
    await page.fill("#billing_postcode", "00100");
    await page.selectOption("#billing_country", "KE");
    await page.selectOption("#billing_state", "Nairobi County");

    // Payment method
    await page.check("#payment_method_kkwoo");

    // Place order
    await Promise.all([
      page.waitForURL("**/lipa-na-mpesa-k2/**"),
      page.click("#place_order"),
    ]);
    await expect(page).toHaveURL(/lipa-na-mpesa-k2/);

    // Payment
    await page.fill("#mpesa-phone-input", "923456789");
    page.click("#proceed-to-pay-btn");

    await page.route("**/wp-json/kkwoo/v1/stk-push", async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify(initSTKPushSuccess),
      });
    });
    await page.route("**/wp-json/kkwoo/v1/payment-status*", async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify(getPaymentStatusProcessing),
      });
    });
    await expect(page.locator("#proceed-to-poll")).toBeVisible();
    await page.click("#proceed-to-poll");

    await expect(page.locator(".side-note")).toHaveText(
      /You have paid KSh \d+\.\d{2} to .+/
    );
    await expect(page.locator("#redirect-to-order-received")).toBeVisible();
    await page.click("#redirect-to-order-received");

    // Redirect → Order received
    await Promise.all([page.waitForURL("**/checkout/order-received/**")]);
    await expect(page).toHaveURL(/checkout\/order-received/);
  });
});

test.describe("Kopo Kopo for WooCommerce Payment Flow (Logged In User)", () => {
  test.beforeAll(async () => {
    // Launch once in headed mode with slowMo
    browser = await chromium.launch({ headless: false, slowMo: 50 });
    context = await browser.newContext();
    page = await context.newPage();
  });

  test.afterAll(async () => {
    await browser.close();
  });

  test.beforeEach(async ({}, testInfo) => {
    await page.goto(`${WP_SITE_URL}/my-account/`);
    await page.fill("#username", WP_CUSTOMER_USER_NAME!);
    await page.fill("#password", WP_CUSTOMER_PASSWORD!);
    await page.click("button[name='login']");
    await expect(page).toHaveURL(/my-account/);
  });

  test("Full flow: Shop → Checkout → Payment(Success) → Order Received", async () => {
    test.setTimeout(45_000);

    // Shop
    await page.goto(`${WP_SITE_URL}/shop/`);
    await page.click(".product a");
    await page.click("button.single_add_to_cart_button");

    // Cart → Checkout
    await page.goto(`${WP_SITE_URL}/cart/`);
    await page.locator("text=Proceed to Checkout").click();

    // Billing
    await page.fill("#billing_first_name", "Doreen");
    await page.fill("#billing_last_name", "Chemweno");
    await page.fill("#billing_email", "test@example.com");
    await page.fill("#billing_phone", "0923456789");
    await page.fill("#billing_address_1", "123 Street");
    await page.fill("#billing_city", "Nairobi");
    await page.fill("#billing_postcode", "00100");
    await page.selectOption("#billing_country", "KE");
    await page.selectOption("#billing_state", "Nairobi County");

    // Payment method
    await page.check("#payment_method_kkwoo");

    // Place order
    await Promise.all([
      page.waitForURL("**/lipa-na-mpesa-k2/**"),
      page.click("#place_order"),
    ]);
    await expect(page).toHaveURL(/lipa-na-mpesa-k2/);

    // Payment
    await page.fill("#mpesa-phone-input", "923456789");
    page.click("#proceed-to-pay-btn");

    await page.route("**/wp-json/kkwoo/v1/stk-push", async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify(initSTKPushSuccess),
      });
    });

    await page.route("**/wp-json/kkwoo/v1/payment-status*", async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify(getPaymentStatusProcessing),
      });
    });
    await expect(page.locator("#proceed-to-poll")).toBeVisible();
    page.click("#proceed-to-poll");

    await expect(page.locator(".side-note")).toHaveText(
      /You have paid KSh \d+\.\d{2} to .+/
    );
    await expect(page.locator("#redirect-to-order-received")).toBeVisible();
    page.click("#redirect-to-order-received");

    // Redirect → Order received
    await Promise.all([page.waitForURL("**/checkout/order-received/**")]);
    await expect(page).toHaveURL(/checkout\/order-received/);
  });
});
