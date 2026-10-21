# TossPayments Refund Testing Plan

This document outlines the manual testing matrix for verifying the refund functionality fixes in version 1.0.3.

## Test Environment Setup

### Prerequisites
- WordPress 6.0+ with WooCommerce 8.0+
- TossPayments test account with test API keys configured
- Both Classic and Blocks checkout pages set up
- Test products configured

### TossPayments Test Credentials
Use TossPayments test mode with test API keys from: https://developers.tosspayments.com/

**Test Cards:**
- Success: `5570****0000****0000` (any future expiry, any CVC)
- Failure: `4000****0000****0001` (for testing error cases)

## Test Scenarios

### 1. Refund Button Availability

#### Test 1.1: Classic Checkout Order
- [ ] Place order via classic WooCommerce checkout with TossPayments
- [ ] Complete payment successfully
- [ ] Navigate to WP Admin > Orders > [Order]
- [ ] **Expected:** "Refund" button appears at bottom of order items section
- [ ] **Expected:** Gateway shown as "SeoulCommerce Payment Gateway for TossPayments"

#### Test 1.2: Blocks Checkout Order
- [ ] Place order via WooCommerce Blocks checkout with TossPayments
- [ ] Complete payment successfully
- [ ] Navigate to WP Admin > Orders > [Order]
- [ ] **Expected:** "Refund" button appears at bottom of order items section
- [ ] **Expected:** Gateway shown as "SeoulCommerce Payment Gateway for TossPayments"
- [ ] **Fixed:** Previously, Blocks orders would not show the refund button due to missing 'refunds' support declaration

---

### 2. Full Refund Processing

#### Test 2.1: Full Refund - Classic Checkout
- [ ] Place and complete order via classic checkout (e.g., ₩10,000)
- [ ] Go to order admin page
- [ ] Click "Refund" button
- [ ] Enter full refund amount (₩10,000)
- [ ] Add reason: "Customer requested full refund"
- [ ] Click "Refund ₩10,000 via TossPayments"
- [ ] **Expected:** Success message appears
- [ ] **Expected:** Order status changes to "Refunded"
- [ ] **Expected:** Order note added: "Full refund of ₩10,000 processed successfully via TossPayments. Reason: Customer requested full refund"
- [ ] **Expected:** No PHP errors or warnings

#### Test 2.2: Full Refund - Blocks Checkout
- [ ] Repeat Test 2.1 but with Blocks checkout order
- [ ] **Expected:** Same results as Test 2.1
- [ ] **Fixed:** Previously would fail or show no refund button

---

### 3. Partial Refund Processing

#### Test 3.1: Single Partial Refund
- [ ] Place and complete order (e.g., ₩10,000)
- [ ] Refund ₩3,000 with reason "Partial refund - damaged item"
- [ ] **Expected:** Success message
- [ ] **Expected:** Order status remains "Processing" or "Completed" (NOT "Cancelled" or "Refunded")
- [ ] **Expected:** Order note: "Partial refund of ₩3,000 processed successfully via TossPayments. Reason: Partial refund - damaged item"
- [ ] **Expected:** Refunded amount shown in order totals
- [ ] **Fixed:** Previously might have incorrectly set order to "Cancelled"

#### Test 3.2: Multiple Partial Refunds - Sum to Total
- [ ] Place and complete order (e.g., ₩10,000)
- [ ] First refund: ₩3,000 (reason: "Item 1 returned")
- [ ] **Expected:** Order status stays in paid status (Processing/Completed)
- [ ] Wait 2 seconds
- [ ] Second refund: ₩7,000 (reason: "Item 2 returned")
- [ ] **Expected:** Success message
- [ ] **Expected:** Order status changes to "Refunded" (now fully refunded)
- [ ] **Expected:** Two separate order notes documenting each refund
- [ ] **Fixed:** Previously, the second refund might fail due to incorrect balance checking

#### Test 3.3: Multiple Partial Refunds - Not Full Total
- [ ] Place and complete order (e.g., ₩10,000)
- [ ] First refund: ₩2,000
- [ ] Second refund: ₩3,000
- [ ] **Expected:** Both succeed
- [ ] **Expected:** Order status remains in paid status (not "Refunded")
- [ ] **Expected:** Total refunded = ₩5,000, balance = ₩5,000
- [ ] **Expected:** Two separate order notes

---

### 4. Refund Validation & Error Handling

#### Test 4.1: Exceed Remaining Balance
- [ ] Place and complete order (e.g., ₩10,000)
- [ ] Refund ₩6,000 successfully
- [ ] Try to refund ₩5,000 (would exceed remaining ₩4,000)
- [ ] **Expected:** Error message: "Refund amount exceeds the remaining cancelable balance of ₩4,000"
- [ ] **Expected:** Order note: "Refund attempt failed for ₩5,000. Error: ..."
- [ ] **Expected:** Order status unchanged
- [ ] **Fixed:** Previously would attempt and fail at TossPayments API level with unclear error

#### Test 4.2: Refund Already Fully Canceled Payment
- [ ] Place and complete order (e.g., ₩10,000)
- [ ] Refund full ₩10,000 successfully
- [ ] Wait for order status to update to "Refunded"
- [ ] Try to refund any amount again
- [ ] **Expected:** Error message: "This payment has already been fully canceled"
- [ ] **Expected:** No status change

#### Test 4.3: Empty Refund Reason
- [ ] Place and complete order
- [ ] Click refund button
- [ ] Enter refund amount but leave reason empty
- [ ] Click refund button
- [ ] **Expected:** Refund processes successfully with default reason "Refund requested"
- [ ] **Expected:** Order note shows "Reason: Refund requested"

#### Test 4.4: Invalid Payment Key (Edge Case)
- [ ] Create order but manually remove transaction ID from order meta
  - In database: `DELETE FROM wp_postmeta WHERE meta_key = '_transaction_id' AND post_id = [order_id]`
  - Or via code: `$order->delete_meta_data('_transaction_id'); $order->save();`
- [ ] Try to refund the order
- [ ] **Expected:** Error message: "Payment key not found. This order cannot be refunded via TossPayments."
- [ ] **Expected:** No fatal errors

---

### 5. Webhook Handling

#### Test 5.1: Admin Refund Followed by Webhook
- [ ] Enable debug logging (WooCommerce > Settings > Payments > TossPayments > Debug Log)
- [ ] Place and complete order
- [ ] Process partial refund via WooCommerce admin (₩3,000 of ₩10,000)
- [ ] Wait 30 seconds for TossPayments webhook to arrive
- [ ] Check order notes
- [ ] **Expected:** Only ONE order note about the ₩3,000 refund
- [ ] **Expected:** Order status remains in paid status
- [ ] **Expected:** Log shows webhook was ignored due to recent admin refund
- [ ] **Fixed:** Previously webhook might duplicate order notes or incorrectly change status

#### Test 5.2: Refund from TossPayments Merchant Dashboard - Partial
- [ ] Place and complete order via WooCommerce
- [ ] Log into TossPayments merchant dashboard: https://dashboard.tosspayments.com/
- [ ] Find the payment and cancel ₩3,000 from ₩10,000
- [ ] Wait 30 seconds for webhook
- [ ] Check WooCommerce order
- [ ] **Expected:** Order note added: "Partial refund processed in TossPayments merchant dashboard. Canceled: ₩3,000, Remaining balance: ₩7,000"
- [ ] **Expected:** Order status remains in paid status (NOT "Cancelled")
- [ ] **Fixed:** Previously would incorrectly set entire order to "Cancelled"

#### Test 5.3: Refund from TossPayments Merchant Dashboard - Full
- [ ] Place and complete order
- [ ] From TossPayments merchant dashboard, cancel full payment
- [ ] Wait for webhook
- [ ] **Expected:** Order status changes to "Refunded"
- [ ] **Expected:** Order note: "Payment fully canceled in TossPayments merchant dashboard. Canceled amount: ₩10,000"

---

### 6. Idempotency & Race Conditions

#### Test 6.1: Rapid Duplicate Refund Attempts
- [ ] Place and complete order
- [ ] Open order in two browser tabs
- [ ] In both tabs, attempt to refund the same amount simultaneously
- [ ] **Expected:** One should succeed, the other may succeed (idempotency key makes it safe) or may fail with balance error
- [ ] **Expected:** No duplicate refunds processed at TossPayments
- [ ] **Fixed:** Added idempotency key to prevent duplicate processing

---

### 7. Error Message Clarity

#### Test 7.1: Authentication Error (Wrong API Key)
- [ ] Temporarily change Secret Key to invalid value in settings
- [ ] Try to refund an order
- [ ] **Expected:** Clear error: "Authentication failed. Please check your TossPayments API keys and IP allowlist settings."
- [ ] **Expected:** No PHP fatal errors
- [ ] **Expected:** No secret key value in error message or logs
- [ ] **Fixed:** Previously showed generic API error

#### Test 7.2: IP Allowlist Error (if applicable)
- [ ] If using TossPayments IP allowlist feature, remove server IP
- [ ] Try to refund
- [ ] **Expected:** Same clear auth error message as Test 7.1
- [ ] Restore settings

---

### 8. Logging & Security

#### Test 8.1: Debug Log Contents
- [ ] Enable debug logging
- [ ] Process a refund
- [ ] Check log file: `wp-content/uploads/wc-logs/tosspayments-[date].log`
- [ ] **Expected:** Log contains:
  - Request/response summaries
  - Refund amount and reason
  - Success/failure outcomes
- [ ] **Expected:** Log DOES NOT contain:
  - Full payment keys (should be truncated to first 10 chars + "...")
  - Secret API keys
  - Full card numbers
  - Authorization headers
- [ ] **Fixed:** Previously logged full sensitive data

---

### 9. Order Status Verification

#### Test 9.1: Status After Partial Refund
- [ ] Place order, status = "Processing"
- [ ] Partial refund ₩3,000 of ₩10,000
- [ ] **Expected:** Status remains "Processing"
- [ ] Manually change status to "Completed"
- [ ] Partial refund another ₩2,000
- [ ] **Expected:** Status remains "Completed"

#### Test 9.2: Status After Full Refund
- [ ] Place order, status = "Processing"
- [ ] Full refund ₩10,000
- [ ] **Expected:** Status changes to "Refunded"
- [ ] **Fixed:** Previously might have set to "Cancelled"

---

### 10. Edge Cases

#### Test 10.1: Refund with Special Characters in Reason
- [ ] Place order
- [ ] Refund with reason containing: `Customer said "not as described" & wants refund!`
- [ ] **Expected:** Refund succeeds
- [ ] **Expected:** Order note displays reason correctly with special characters

#### Test 10.2: Refund in Different Currency (if supported)
- [ ] If store uses currency other than KRW
- [ ] Place and complete order
- [ ] Refund
- [ ] **Expected:** Amount correctly converted to integer for TossPayments API
- [ ] **Expected:** Display shows proper currency symbol in order notes

#### Test 10.3: Very Small Partial Refund
- [ ] Place order for ₩10,000
- [ ] Refund ₩1
- [ ] **Expected:** Succeeds
- [ ] **Expected:** Remaining balance = ₩9,999

---

## Test Result Summary

| Test ID | Description | Pass/Fail | Notes |
|---------|-------------|-----------|-------|
| 1.1 | Classic checkout refund button | | |
| 1.2 | Blocks checkout refund button | | |
| 2.1 | Full refund - Classic | | |
| 2.2 | Full refund - Blocks | | |
| 3.1 | Single partial refund | | |
| 3.2 | Multiple partial to total | | |
| 3.3 | Multiple partial not total | | |
| 4.1 | Exceed balance | | |
| 4.2 | Already canceled | | |
| 4.3 | Empty reason | | |
| 4.4 | Invalid payment key | | |
| 5.1 | Admin refund + webhook | | |
| 5.2 | Dashboard partial refund | | |
| 5.3 | Dashboard full refund | | |
| 6.1 | Duplicate attempts | | |
| 7.1 | Auth error message | | |
| 7.2 | IP allowlist error | | |
| 8.1 | Debug log security | | |
| 9.1 | Status after partial | | |
| 9.2 | Status after full | | |
| 10.1 | Special chars in reason | | |
| 10.2 | Different currency | | |
| 10.3 | Small partial refund | | |

---

## Critical Issues Fixed

1. **Blocks Checkout Support**: Added 'refunds' to Blocks payment method supports array
2. **Webhook Status Handling**: Partial cancellations no longer flip order to "Cancelled"
3. **Balance Validation**: Now checks TossPayments' `balanceAmount` before processing
4. **Multiple Partial Refunds**: Correctly handles sequential partial refunds
5. **Error Messages**: Clear, actionable errors for common failures
6. **Logging Security**: Sensitive data redacted from logs
7. **Idempotency**: Prevents duplicate refund processing

---

## Automated Testing Notes

This plugin does not currently have a PHPUnit test suite. For future development, consider adding:
- Unit tests for `SeoulCommerce_TPG_API::cancel_payment()`
- Integration tests for `SeoulCommerce_TPG_Gateway::process_refund()`
- Mock TossPayments API responses for various scenarios
- Webhook handler tests with sample payloads

---

## Deployment Checklist

Before releasing to production:
- [ ] All manual tests pass
- [ ] Test with TossPayments sandbox/test keys
- [ ] Test with TossPayments live keys in staging environment
- [ ] Verify no PHP warnings/notices in debug.log
- [ ] Verify no sensitive data in wc-logs
- [ ] Test with both Classic and Blocks checkout
- [ ] Test webhook receipt and processing
- [ ] Verify HPOS compatibility (if using WC 8.2+)
- [ ] Update changelog with release date
- [ ] Tag version 1.0.3 in git
