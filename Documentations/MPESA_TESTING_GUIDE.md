# M-Pesa Integration Testing Guide

## Overview

This guide explains how to test your M-Pesa integration to ensure STK push prompting and callbacks work correctly.

## Test Credentials (Safaricom Sandbox)

```
Initiator Name: testapi
Initiator Password: Safaricom123!
Party A (Payer): 600978
Party B (Payee): 600000
Phone No: 25470837149
Business ShortCode: 174379
Passkey: bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919
```

## Testing Components

### 1. Configuration Verification

**Run the configuration test:**

```bash
cd Server
php test-mpesa-laravel.php
```

**Expected output:**
- ✓ Base URL configured
- ✓ All credentials set
- ✓ Token generation successful

### 2. Full Flow Test (Local)

**Run the comprehensive test:**

```bash
cd Server
php test-mpesa-full-flow.php
```

This script will:
1. ✓ Verify all M-Pesa configuration
2. ✓ Generate access token from M-Pesa
3. ✓ Initiate an STK push (sends prompt to test phone)
4. ✓ Create database transaction record
5. ✓ Query payment status
6. ✓ Simulate M-Pesa callback
7. ✓ Verify database updates

**Expected flow:**
```
STEP 1: Configuration Verification → ✓ CONFIGURED
STEP 2: Access Token Generation   → ✓ Token generated
STEP 3: STK Push Initiation        → ✓ STK Push initiated
STEP 4: Database Record            → ✓ Transaction record created
STEP 5: Status Query               → ✓ Status query successful
STEP 6: Callback Simulation        → ✓ Callback processing verified
```

### 3. API Testing (REST Client or Postman)

**See:** `test-mpesa-flow.rest` for complete REST API test collection

#### Step-by-Step API Test:

**Step 1: Get Authentication Token**
```bash
POST /api/login
{
  "email": "your@email.com",
  "password": "your_password"
}
```

**Step 2: Initiate STK Push**
```bash
POST /api/mpesa/stk-push
Authorization: Bearer {token}
{
  "phone_number": "25470837149",
  "amount": 100,
  "account_reference": "TEST_REFERENCE",
  "transaction_desc": "Test Payment"
}
```

Response will include:
- `transaction.id` - Database record ID
- `transaction.checkout_request_id` - M-Pesa request ID
- `provider_response` - Full M-Pesa response

**Step 3: Query Payment Status (Before User Action)**
```bash
POST /api/mpesa/stk-query
Authorization: Bearer {token}
{
  "checkout_request_id": "{checkout_request_id_from_step2}"
}
```

Expected status: `pending` (user hasn't responded yet)

**Step 4: Simulate M-Pesa Callback (Development Only)**

This endpoint simulates what happens when user accepts or declines the payment:

```bash
POST /api/mpesa/test-callback
{
  "checkout_request_id": "{checkout_request_id}",
  "result_code": "0",
  "result_desc": "The service request is processed successfully.",
  "receipt_number": "TEG8M0IY4U"
}
```

Result codes:
- `0` - Success
- `1032` - User cancelled
- `1`, `2` - Various failures

**Step 5: Query Payment Status (After Callback)**
```bash
POST /api/mpesa/stk-query
Authorization: Bearer {token}
{
  "checkout_request_id": "{checkout_request_id_from_step2}"
}
```

Expected status: `success` (or `failed` if result_code was not 0)

**Step 6: Get Transaction Details**
```bash
GET /api/mpesa/transactions/{checkout_request_id}
Authorization: Bearer {token}
```

## Real-World Testing (With Actual M-Pesa)

### Prerequisites

1. **Safaricom M-Pesa Account:** Production paybill number
2. **Public HTTPS Callback URL:** Required for production
3. **Valid Credentials:** Production API keys

### Setup for Production

1. **Update .env:**
```env
MPESA_CALLBACK_URL=https://your-domain.com/api/mpesa/callback
# Must be HTTPS and publicly accessible
```

2. **Test with Safaricom Sandbox First:**
```env
MPESA_BASE_URL=https://sandbox.safaricom.co.ke
```

3. **Enable Production Mode:**
```env
APP_ENV=production
```

### Production Testing Flow

1. **Initiate STK Push:**
   - User will receive actual STK prompt on their phone
   - They accept or decline within 30 seconds

2. **M-Pesa Sends Callback:**
   - Your callback URL receives POST request
   - `/api/mpesa/callback` endpoint processes it
   - Transaction status updated automatically

3. **Verify in Database:**
```sql
SELECT 
  id,
  checkout_request_id,
  status,
  result_code,
  result_desc,
  mpesa_receipt_number,
  transaction_date
FROM mpesa_transactions 
WHERE checkout_request_id = '{checkout_request_id}'
ORDER BY created_at DESC;
```

## Debugging

### View Transaction History

```bash
# Check recent transactions
tail -f storage/logs/laravel.log | grep -i mpesa
```

### Database Inspection

```sql
-- View all M-Pesa transactions
SELECT * FROM mpesa_transactions ORDER BY created_at DESC LIMIT 10;

-- View failed transactions
SELECT * FROM mpesa_transactions WHERE status = 'failed' ORDER BY created_at DESC;

-- View raw callback data
SELECT id, checkout_request_id, raw_callback FROM mpesa_transactions 
WHERE raw_callback IS NOT NULL ORDER BY created_at DESC;
```

### Common Issues

#### Issue: "M-Pesa env constants are missing"
**Solution:** Ensure all `MPESA_*` variables are set in `.env` file

#### Issue: "Unable to fetch M-Pesa access token"
**Solution:** Verify credentials with Safaricom support

#### Issue: "CheckoutRequestID is null"
**Solution:** M-Pesa rejected the request - check phone number format and amount

#### Issue: Callback not received
**Possible causes:**
- Callback URL not HTTPS in production
- Callback URL not publicly accessible
- Firewall/proxy blocking M-Pesa servers
- Token validation failing

**Debugging:**
```bash
# Check if callback endpoint is reachable
curl -X POST https://your-domain.com/api/mpesa/callback \
  -H "Content-Type: application/json" \
  -d '{"test": "payload"}'
```

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                        Your Application                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  1. Client initiates payment                                    │
│         ↓                                                         │
│  2. POST /api/mpesa/stk-push                                    │
│         ↓                                                         │
│  3. MpesaService.initiateStkPush()                              │
│         ↓ (HTTP)                                                │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │           Safaricom M-Pesa API (Sandbox/Prod)            │  │
│  │                                                            │  │
│  │  /mpesa/stkpush/v1/processrequest                         │  │
│  │  Returns: CheckoutRequestID                               │  │
│  │                                                            │  │
│  │  → Sends STK prompt to user's phone                       │  │
│  │  → Waits for user response (30 seconds)                   │  │
│  │  → Sends callback to your app                             │  │
│  └──────────────────────────────────────────────────────────┘  │
│         ↑                                                         │
│  4. POST /api/mpesa/callback (Webhook)                          │
│         ↓                                                         │
│  5. Process M-Pesa response                                     │
│         ↓                                                         │
│  6. Update transaction status in database                       │
│         ↓                                                         │
│  7. Return status to client                                     │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
```

## Feature Access Control

M-Pesa payment features are gated by subscription plan. Users must have the M-Pesa feature enabled:

```php
// Check if M-Pesa is available for this user
if ($user->company->subscription_plan->features->contains('name', 'mpesa')) {
    // M-Pesa available
}
```

## Performance Considerations

- **Token Caching:** Access tokens are cached by Laravel (no repeated requests)
- **Phone Normalization:** Automatically converts phone numbers to international format
- **Callback Validation:** Security token verification prevents unauthorized callbacks
- **Database Indexing:** Add index on `checkout_request_id` for faster lookups:

```sql
ALTER TABLE mpesa_transactions ADD INDEX idx_checkout_request_id (checkout_request_id);
```

## Security Best Practices

1. ✓ **HTTPS Only:** Production callback URLs must use HTTPS
2. ✓ **Token Validation:** Callback secret prevents unauthorized callbacks
3. ✓ **Company Scoping:** Transactions filtered by company_id (multi-tenant)
4. ✓ **Rate Limiting:** Consider adding rate limits to payment endpoints
5. ✓ **Audit Logging:** All failures logged with context
6. ✓ **Credential Protection:** Credentials stored in .env, never in code

## Next Steps

1. Run the full flow test: `php test-mpesa-full-flow.php`
2. Test via API using provided REST collection
3. Verify database updates are working
4. Test with actual M-Pesa in sandbox environment
5. Deploy to production with HTTPS callback URL
