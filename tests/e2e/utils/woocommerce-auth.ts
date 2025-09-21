import OAuth from "oauth-1.0a";
import crypto from "crypto";

// Setup OAuth1 -- Since tests run locally hence non-ssl
const oauth = new OAuth({
  consumer: {
    key: process.env.WC_CONSUMER_KEY!,
    secret: process.env.WC_CONSUMER_SECRET!,
  },
  signature_method: "HMAC-SHA256",
  hash_function(base_string, key) {
    return crypto
      .createHmac("sha256", key)
      .update(base_string)
      .digest("base64");
  },
});

export function getAuthHeader(
  url: string,
  method: string
): Record<string, string> {
  const request_data = { url, method };
  const header = oauth.toHeader(oauth.authorize(request_data));

  return {
    Authorization: header.Authorization,
  };
}

// Generate signed query params (WooCommerce expects these on non-SSL)
export function signRequest(url: string, method: string, data: any = {}) {
  const requestData = { url, method, data };
  const authParams = oauth.authorize(requestData);
  return (
    url +
    "?" +
    new URLSearchParams(
      authParams as unknown as Record<string, string>
    ).toString()
  );
}
