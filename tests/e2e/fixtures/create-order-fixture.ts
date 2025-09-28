import { test as base, expect } from "@playwright/test";
import { signRequest } from "../utils/woocommerce-auth";
import { WooCommerceOrder } from "../types/order";
import { createOrderData as baseOrderData } from "../json/order-data";

export const test = base.extend<{
  order: (status?: string) => Promise<WooCommerceOrder>;
}>({
  order: async ({ request }, use) => {
    // Store created orders for cleanup
    const createdOrders: WooCommerceOrder[] = [];

    // Expose a function to create an order with a specific status
    await use(async (status = "pending") => {
      const baseUrl = process.env.WC_REST_URL;
      const orderData = { ...baseOrderData, status };

      const url = `${baseUrl}/orders`;
      const signedUrl = signRequest(url, "POST", orderData);

      const response = await request.post(signedUrl, { data: orderData });
      const order = await response.json();

      console.log(`Created test order with status '${status}':`, order.id);

      // Keep track for cleanup
      createdOrders.push(order);

      return order;
    });

    // Cleanup all created orders automatically after test finishes
    for (const order of createdOrders) {
      try {
        const deleteUrl = signRequest(
          `${process.env.WC_REST_URL}/orders/${order.id}`,
          "DELETE",
          {
            force: true,
          }
        );
        const delResponse = await request.delete(deleteUrl);

        if (!delResponse.ok()) {
          console.error(`Failed to delete order ${order.id}`);
          console.error(await delResponse.text());
        } else {
          console.log("Deleted test order:", order.id);
        }
      } catch (err) {
        console.error(`Error deleting order ${order.id}:`, err);
      }
    }
  },
});

export { expect };
