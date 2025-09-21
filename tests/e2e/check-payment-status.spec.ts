import { chromium, Page } from "@playwright/test";
import { test, expect } from "./fixtures/create-order-fixture";
import { getAuthHeader } from "./utils/woocommerce-auth";

let page: Page;

test.describe("Check Payment Status (Guest User)", () => {
  test.beforeEach(async ({}, testInfo) => {
    // Launch browser in headed mode
    const browser = await chromium.launch({ headless: false, slowMo: 50 });
    const context = await browser.newContext();
    page = await context.newPage();
  });

  test("Check payment status: Payment(No Result) -> Order Received View -> Check Payment Status", async ({
    orderId,
    request,
  }) => {
    // Create order
    const url = `${process.env.WC_REST_URL}/orders/${orderId}`;
    const getOrderResponse = await request.get(url, {
      headers: getAuthHeader(url, "GET"),
    });

    expect(getOrderResponse.status()).toBe(200);
    const order = await getOrderResponse.json();
    expect(order.status).toBe("pending");

    // Make Payment
    await page.goto(
      `${process.env.WP_SITE_URL}/lipa-na-mpesa-k2/?order_key=${order.order_key}`
    );

    await page.fill("#mpesa-phone-input", "923456789");
    page.click("#proceed-to-pay-btn");

    const [response] = await Promise.all([
      page.waitForResponse(
        (res) =>
          res.url().includes("kkwoo/v1/stk-push") &&
          res.request().method() === "POST"
      ),
      page.click("#proceed-to-poll"),
    ]);
    expect(response.ok()).toBeTruthy();

    await Promise.all([
      page.waitForURL("**/lipa-na-mpesa-k2/**"),
      page.reload(), //This reload is intentional as we take the user to the no result view if they refresh the payment page before receiving the payment result
    ]);
    await expect(page.locator(".main-info")).toHaveText(
      "Waiting to receive funds"
    );

    // Redirect → Order received
    await Promise.all([
      page.waitForURL("**/checkout/order-received/**"),
      page.click("#redirect-to-order"),
    ]);
    await expect(page).toHaveURL(/checkout\/order-received/);
    await expect(page.locator("#check-payment-status")).toBeVisible();

    // Check payment status
    const [checkPaymentStatusResponse] = await Promise.all([
      page.waitForResponse(
        (res) =>
          res.url().includes("kkwoo/v1/query-incoming-payment-status") &&
          res.request().method() === "GET"
      ),
      page.locator("#check-payment-status").click(),
    ]);
    expect(checkPaymentStatusResponse.ok()).toBeTruthy();
    expect(page.locator("#kkwoo-flash-messages")).toHaveText(
      "Your payment has been processed."
    );
  });
});
