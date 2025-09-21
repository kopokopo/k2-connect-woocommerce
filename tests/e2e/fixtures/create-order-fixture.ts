import { test as base, expect } from "@playwright/test";
import { signRequest } from "../utils/woocommerce-auth";

export const test = base.extend<{ orderId: string }>({
  orderId: async ({ request }, use) => {
    const orderData = {
      payment_method: "kkwoo",
      payment_method_title: "Lipa na M-PESA",
      set_paid: false,
      billing: {
        first_name: "Test",
        last_name: "User",
        email: "test@example.com",
        phone: "123456789",
        address_1: "123 Test St",
        city: "Nairobi",
        country: "KE",
      },
      line_items: [{ product_id: 123, quantity: 1 }],
    };

    const baseUrl = process.env.WC_REST_URL;

    const url = `${baseUrl}/orders`;
    const signedUrl = signRequest(url, "POST", orderData);

    const response = await request.post(signedUrl, {
      data: orderData,
    });

    const order = await response.json();
    const orderId = order.id;

    console.log("Created test order:", orderId);

    await use(order.id);
  },
});

export { expect };
