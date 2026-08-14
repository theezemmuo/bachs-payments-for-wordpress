# bachs payments for wordpress

the secure, native bachs integration for wordpress and woocommerce. 

this plugin seamlessly connects your woocommerce store to the bachs api, allowing you to securely accept and process payments, manage refunds, and automatically sync payment statuses via webhooks.

## features & recent updates

* **dynamic environment switching** 
  you no longer need to manually toggle between "sandbox" and "live" modes or juggle multiple sets of keys. simply paste your bachs api key into the woocommerce settings. the plugin automatically detects whether you are using a test key (e.g., `sk_sandbox_...`) or a live key, and dynamically routes all api requests and webhook validations to the correct bachs environment.
  
* **automatic sandbox currency fallback**
  bachs does not currently support ngn (nigerian naira) balances in their sandbox environment. to prevent checkout errors during testing, this plugin includes a seamless fallback: if you are using a sandbox api key and your woocommerce store is set to ngn, the plugin will silently convert the api request to `usd`. webhooks returning a successful usd transaction will be matched against your ngn order and processed smoothly so you can fully test your checkout flow end-to-end. (note: this fallback is ignored in live mode, where ngn is passed normally).

* **robust webhook security**
  all incoming webhooks are strictly verified using an hmac sha256 signature generated from your webhook secret. the plugin also actively queries the bachs api to re-verify the transaction payload directly with the server, completely eliminating the risk of spoofed webhook payloads marking unpaid orders as completed.

* **idempotency protections**
  the webhook handler protects your store from double-processing orders during simultaneous api retries by caching incoming event ids and gracefully ignoring duplicates.

## setup instructions

1. **install and activate** the plugin in your wordpress dashboard.
2. go to **woocommerce > settings > payments** and click "manage" on bachs.
3. **api key**: paste your bachs api key (either live or sandbox). 
4. **webhook url**: copy the displayed webhook url from the settings page and paste it into your bachs dashboard to create a new webhook endpoint.
5. **webhook secret**: copy the webhook secret provided by the bachs dashboard and paste it into the woocommerce settings.
6. click **save changes**. you can optionally click the **test connection** button to verify that your store is communicating correctly with bachs!
