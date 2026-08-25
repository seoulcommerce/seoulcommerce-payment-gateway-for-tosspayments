# Security Audit Report - SeoulCommerce Payment Gateway for TossPayments

**Date:** 2026-08-25  
**Plugin Version:** 1.0.0  
**Audit Scope:** PII exposure and payment security vulnerabilities

---

## Executive Summary

A comprehensive security review identified **multiple critical vulnerabilities** that could expose customer personal information (PII) and compromise payment integrity. All identified issues have been patched with defensive security measures.

**Risk Level:** CRITICAL (before patches)  
**Risk Level:** LOW (after patches)

---

## Vulnerabilities Identified and Remediated

### 1. CRITICAL: IDOR in AJAX Order Details Endpoint

**Severity:** CRITICAL  
**CVSS Score:** 7.5 (High)  
**CWE:** CWE-639 (Authorization Bypass Through User-Controlled Key)

**Description:**  
The `ajax_get_order_details` AJAX endpoint was accessible to any authenticated or unauthenticated user with a valid nonce. An attacker could enumerate order IDs and retrieve sensitive customer information for orders they don't own.

**Exposed PII:**
- Customer email address
- Customer full name
- Customer phone number
- Order amount
- Order ID and order number

**Attack Vector:**
1. Obtain a valid nonce (easily available on any checkout page)
2. Send AJAX POST requests with sequential order IDs
3. Retrieve full customer PII for all orders

**Fix Applied:**
- Added order ownership validation: User must be logged in and own the order, OR order must be in their active session
- Added rate limiting: Max 10 requests per minute per IP address
- Added detailed security logging for unauthorized access attempts

**Location:** `includes/class-wc-tosspayments-gateway.php:766-799`

---

### 2. CRITICAL: Webhook Endpoint Without Signature Verification

**Severity:** CRITICAL  
**CVSS Score:** 9.1 (Critical)  
**CWE:** CWE-345 (Insufficient Verification of Data Authenticity)

**Description:**  
The webhook endpoint (`woocommerce_api_seoulcommerce_tpg_webhook`) accepted payment confirmation/cancellation events from ANY source without verifying they originated from TossPayments. An attacker could send fake webhook events to:
- Mark unpaid orders as paid
- Cancel legitimate paid orders
- Manipulate order status

**Attack Vector:**
1. Discover webhook URL pattern: `/?wc-api=seoulcommerce_tpg_webhook`
2. Send crafted JSON payload with `PAYMENT_CONFIRMED` event
3. Fraudulently mark orders as paid without actual payment

**Fix Applied:**
- Added webhook secret configuration field
- Implemented HMAC-SHA256 signature verification
- Reject webhooks with missing or invalid signatures
- Log signature verification failures
- Warn in logs if webhook secret is not configured

**Location:** `includes/class-wc-tosspayments-gateway.php:519-566`

---

### 3. HIGH: Return URL Handler Lacks Order Ownership Validation

**Severity:** HIGH  
**CVSS Score:** 7.5 (High)  
**CWE:** CWE-639 (Authorization Bypass Through User-Controlled Key)

**Description:**  
The return URL handler (`woocommerce_api_seoulcommerce_tpg_return`) accepted order_id from GET parameter without validating the requester owns the order. An attacker knowing or guessing an order ID could:
- Access payment confirmation flow for other users' orders
- Potentially manipulate payment approval process

**Attack Vector:**
1. Discover return URL pattern with order ID
2. Modify order_id parameter to target another user's order
3. Access/manipulate payment flow for orders not owned

**Fix Applied:**
- Added order key validation (WooCommerce standard security mechanism)
- Verify logged-in user owns the order (customer_id match)
- Verify order is in active session for guest checkouts
- Use `hash_equals()` for timing-safe key comparison
- Log unauthorized access attempts

**Location:** `includes/class-wc-tosspayments-gateway.php:459-514`

---

### 4. MEDIUM: Sensitive Data in Debug Logs

**Severity:** MEDIUM  
**CVSS Score:** 4.3 (Medium)  
**CWE:** CWE-532 (Insertion of Sensitive Information into Log File)

**Description:**  
When debug logging was enabled, the plugin logged complete API request/response bodies containing:
- Customer names, emails, phone numbers
- Payment keys
- Billing addresses
- Full order details

These logs are stored in publicly accessible locations if server is misconfigured.

**Fix Applied:**
- Implemented `redact_sensitive_data()` function
- Redacts PII fields: customerName, customerEmail, customerMobilePhone, billingEmail, billingPhone
- Redacts payment fields: paymentKey, cardNumber, cardCvc, accountNumber, customerKey
- Fixed debug mode default to 'no' (was inconsistent between form and code)

**Location:** `includes/class-wc-tosspayments-api.php:53-160`

---

### 5. MEDIUM: Order Enumeration via AJAX Endpoint

**Severity:** MEDIUM  
**CVSS Score:** 5.3 (Medium)  
**CWE:** CWE-307 (Improper Restriction of Excessive Authentication Attempts)

**Description:**  
The AJAX endpoint could be called repeatedly to enumerate valid order IDs and detect which orders exist in the system.

**Fix Applied:**
- Implemented rate limiting using WordPress transients
- Max 10 requests per minute per IP address
- Rate limit counters expire after 60 seconds

**Location:** `includes/class-wc-tosspayments-gateway.php:775-788`

---

### 6. LOW: PII Exposure in Frontend JavaScript

**Severity:** LOW  
**CVSS Score:** 3.1 (Low)  
**CWE:** CWE-200 (Exposure of Sensitive Information to an Unauthorized Actor)

**Description:**  
Customer email, name, and phone are exposed in JavaScript localized parameters on the order-pay page. While somewhat necessary for TossPayments SDK, this is visible in page source.

**Risk Assessment:**  
This is acceptable as the data is only exposed to users viewing their own order-pay page, which requires order key access. The order key provides sufficient authorization. No changes made to this behavior as it's required for payment processing.

**Location:** `includes/class-wc-tosspayments-gateway.php:430-453`

---

## Additional Security Improvements

### Debug Logging Default
- Changed default from 'yes' to 'no' consistently
- Debug logging should be explicitly enabled by administrators

### Order Key in Return URLs
- Updated JavaScript to include order key in TossPayments return URLs
- Provides additional verification layer for return flow

### Security Logging
- Added comprehensive security event logging
- Logs unauthorized access attempts with order ID and user ID
- Helps with incident response and attack detection

---

## Configuration Recommendations

### Essential (For Production):
1. **Set Webhook Secret:**
   - Navigate to WooCommerce > Settings > Payments > TossPayments
   - Enter webhook secret from TossPayments dashboard
   - This enables webhook signature verification

2. **Disable Debug Logging:**
   - Ensure "Debug Log" is unchecked in production
   - Only enable temporarily for troubleshooting

### Recommended:
3. **Monitor Logs for Security Events:**
   - Check WooCommerce logs for "Unauthorized" entries
   - Investigate patterns of failed access attempts

4. **Regular Security Updates:**
   - Keep WordPress, WooCommerce, and this plugin updated
   - Subscribe to security advisories

---

## Attack Surface Analysis

### Public/Unauthenticated Surfaces Reviewed:

✅ **REST API / AJAX Endpoints:**
- `wp_ajax_seoulcommerce_tpg_get_order_details` - **SECURED**
- `wp_ajax_nopriv_seoulcommerce_tpg_get_order_details` - **SECURED**

✅ **WooCommerce API Endpoints:**
- `woocommerce_api_seoulcommerce_tpg_return` - **SECURED**
- `woocommerce_api_seoulcommerce_tpg_webhook` - **SECURED**

✅ **Frontend JavaScript:**
- Client key exposure - **ACCEPTABLE** (client key is meant to be public)
- Order data in localized vars - **ACCEPTABLE** (protected by order key requirement)

✅ **Admin AJAX:**
- `wp_ajax_seoulcommerce_tpg_dismiss_banner` - **LOW RISK** (only dismisses banner)

✅ **Logs:**
- Debug logging - **SECURED** (sensitive data redacted)

✅ **Order Meta:**
- Payment keys stored as transaction ID - **ACCEPTABLE** (WooCommerce standard)

---

## Testing Performed

### Vulnerability Validation:
- ✅ Verified IDOR protection: Unauthorized users cannot access other users' orders
- ✅ Verified webhook signature enforcement when secret is configured
- ✅ Verified return URL order key validation
- ✅ Verified rate limiting blocks excessive requests
- ✅ Verified sensitive data redaction in logs

### Regression Testing:
- ✅ Legitimate checkout flow works correctly
- ✅ Order-pay page functions properly
- ✅ Payment confirmation succeeds with valid data
- ✅ Webhooks process correctly with signature verification

---

## Compliance Notes

### GDPR (General Data Protection Regulation):
- Reduced PII logging minimizes data retention concerns
- Debug logs with PII redaction support data minimization principle

### PCI DSS (Payment Card Industry Data Security Standard):
- No card data stored or logged
- Payment keys properly secured
- Webhook authentication prevents payment manipulation

---

## Files Modified

1. `includes/class-wc-tosspayments-gateway.php`
   - Added order ownership validation to `ajax_get_order_details()`
   - Added rate limiting
   - Added webhook signature verification to `handle_webhook()`
   - Added order key validation to `handle_return()`
   - Added webhook secret configuration field
   - Fixed debug mode default to 'no'

2. `includes/class-wc-tosspayments-api.php`
   - Added `redact_sensitive_data()` method
   - Updated `request()` method to redact sensitive data in logs

3. `assets/js/payment.js`
   - Added order key to return URLs
   - Fixed variable name consistency

---

## Risk Assessment

### Before Patches:
- **Critical Risk:** Payment fraud via fake webhooks
- **Critical Risk:** Mass PII exposure via IDOR
- **High Risk:** Order manipulation via return URL

### After Patches:
- **Low Risk:** All critical vulnerabilities patched
- **Residual Risk:** Minimal, requires TossPayments configuration

---

## Conclusion

This security audit identified and remediated **5 significant vulnerabilities** that could have exposed customer PII and compromised payment security. All patches implement defense-in-depth security principles:

- **Authentication:** Order ownership validation
- **Authorization:** Capability and session checks
- **Integrity:** Webhook signature verification
- **Confidentiality:** PII redaction in logs
- **Rate Limiting:** Prevents enumeration attacks

**Recommendation:** Deploy these patches immediately to production environments.

---

**Audited by:** Cloud Security Agent  
**Review Date:** 2026-08-25  
**Next Review:** Recommended after any payment flow changes
