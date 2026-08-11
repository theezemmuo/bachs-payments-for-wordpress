# bachs payments for wordpress

a secure wordpress integration that provides a core php client for bachs and a bundled woocommerce payment gateway.

## what it does

the plugin acts as a native payment method in woocommerce and securely handles the bachs checkout lifecycle. 

it forces server-side webhook verification as the single source of truth for payment states, avoiding frontend redirects as proof of payment. 

## architecture

the plugin is split into two layers:

1. core bachs sdk
- handles api authentication (live and sandbox).
- manages checkout sessions, transaction verification, and refunds.
- provides a webhook engine with cryptographic signature verification.
- includes strict idempotency checks to prevent duplicate processing.
- redacts sensitive api keys and headers from server logs.

2. woocommerce adapter
- registers bachs as a native payment gateway.
- maps woocommerce order totals and currencies to bachs checkout sessions.
- listens to webhook events to automatically update woocommerce order statuses (e.g. processing, failed, refunded).
- supports admin refunds directly from the woocommerce order screen.
