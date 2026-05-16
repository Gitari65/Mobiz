# M-Pesa API Testing Complete Guide

## Overview

Your M-Pesa integration is now fully tested and working. Here's how to test the complete flow:

## Quick Start: Test with REST Client

### Option 1: Using VS Code REST Client Extension

1. **Open the test file:**
   ```
   Server/api.rest
   ```

2. **Run requests in order:**
   - Click "Send Request" on each endpoint
   - Watch the response and variable outputs
   - Each response automatically sets variables for the next request

3. **Expected flow:**
   ```
   STEP 1: STK Push → Get CheckoutRequestID
   STEP 2: Query Status → Status should be "pending"
   STEP 3: Simulate Callback → User accepts/rejects
   STEP 4: Query Status → Status updates to "success" or "failed"
   STEP 5: Get Details → Full transaction info
   ```

### Option 2: Using Postman or Insomnia

1. **Get your auth token:**
   ```bash
   cd Server
   php artisan mpesa:token 1
   ```

2. **Copy the token from output**

3. **Test each endpoint:**
   ```
   STEP 1: POST /api/mpesa/stk-push
           Authorization: Bearer {token}
           Body: phone_number, amount, account_reference, transaction_desc
   
   STEP 2: POST /api/mpesa/stk-query
           Authorization: Bearer {token}
           Body: checkout_request_id
   
   STEP 3: POST /api/mpesa/test-callback
           No auth needed (dev endpoint)
           Body: checkout_request_id, result_code, receipt_number
   
   STEP 4: POST /api/mpesa/stk-query (again)
           Authorization: Bearer {token}
   
   STEP 5: GET /api/mpesa/transactions/{checkoutRequestId}
           Authorization: Bearer {token}
   ```

## Generate New Test Token

If your token expires, generate a new one:

```bash
cd Server
php artisan mpesa:token 1
```

Or for a different user:
```bash
php artisan mpesa:token 4  # For cashier1@example.com
```

## Test with Actual M-Pesa (Sandbox)

### 1. Safaricom Simulator Setup

Visit: https://simulator.safaricom.co.ke

**Test Credentials:**
- Initiator Name: `testapi`
- Initiator Password: `Safaricom123!`
- Business ShortCode: `174379`
- Passkey: `bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919`
- Test Phone: `254708374149`

### 2. Initiate STK Push

```bash
curl -X POST http://localhost:8000/api/mpesa/stk-push \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "phone_number": "+254708374149",
    "amount": 100,
    "account_reference": "INVOICE001",
    "transaction_desc": "Test Payment"
  }'
```

**Response:**
```json
{
  "message": "M-Pesa request sent successfully",
  "transaction": {
    "id": 1,
    "checkout_request_id": "ws_CO_14052026091433174708374149",
    "status": "pending",
    "amount": 100
  },
  "provider_response": {
    "ResponseCode": "0",
    "Description": "Success. Request accepted for processing"
  }
}
```

### 3. Check Status in Safaricom Simulator

1. Login to simulator with test credentials
2. Look for STK push prompt on the test phone number
3. Accept or reject the payment

### 4. M-Pesa Sends Callback

When user accepts/rejects:
- M-Pesa sends callback to: `https://your-domain.com/api/mpesa/callback`
- Your app automatically updates transaction status
- Database stores receipt number and transaction date

### 5. Verify in Database

```sql
SELECT * FROM mpesa_transactions 
WHERE reference = 'INVOICE001'
ORDER BY created_at DESC;
```

**Output should show:**
- `status`: "success" (if user accepted) or "failed" (if rejected)
- `result_code`: "0" (success) or other code
- `mpesa_receipt_number`: Receipt from M-Pesa
- `transaction_date`: When payment was made

## API Endpoints Reference

### 1. Initiate Payment (STK Push)

```http
POST /api/mpesa/stk-push
Authorization: Bearer {token}
Content-Type: application/json

{
  "phone_number": "+254748344757",
  "amount": 100,
  "account_reference": "INVOICE128",
  "transaction_desc": "Product payment"
}
```

**Response (Success):**
```json
{
  "message": "M-Pesa request sent successfully",
  "transaction": {
    "id": 123,
    "checkout_request_id": "ws_CO_...",
    "status": "pending",
    "amount": 100,
    "phone_number": "254748344757"
  },
  "provider_response": {
    "ResponseCode": "0",
    "CheckoutRequestID": "ws_CO_...",
    "MerchantRequestID": "7787-4b0a-..."
  }
}
```

### 2. Query Payment Status

```http
POST /api/mpesa/stk-query
Authorization: Bearer {token}
Content-Type: application/json

{
  "checkout_request_id": "ws_CO_14052026091433174708374149"
}
```

**Response:**
```json
{
  "transaction": {
    "id": 123,
    "status": "pending",
    "result_code": "1037",
    "result_desc": "DS timeout user cannot be reached",
    "checkout_request_id": "ws_CO_..."
  },
  "provider_response": {
    "ResponseCode": "0",
    "ResultCode": "1037",
    "ResultDesc": "DS timeout..."
  }
}
```

**Result Codes:**
- `0` - Success (payment completed)
- `1032` - User cancelled
- `1037` - Timeout (no response from user)
- Other codes - Various failures

### 3. Simulate Callback (Development Only)

```http
POST /api/mpesa/test-callback
Content-Type: application/json

{
  "checkout_request_id": "ws_CO_...",
  "result_code": "0",
  "result_desc": "The service request is processed successfully.",
  "receipt_number": "TEG8M0IY4U"
}
```

**Response:**
```json
{
  "message": "Test callback processed successfully",
  "transaction": {
    "id": 123,
    "status": "success",
    "result_code": "0",
    "mpesa_receipt_number": "TEG8M0IY4U"
  }
}
```

### 4. Get Transaction Details

```http
GET /api/mpesa/transactions/{checkoutRequestId}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "transaction": {
    "id": 123,
    "checkout_request_id": "ws_CO_...",
    "merchant_request_id": "7787-4b0a-...",
    "phone_number": "254748344757",
    "amount": 100,
    "status": "success",
    "result_code": "0",
    "mpesa_receipt_number": "TEG8M0IY4U",
    "transaction_date": "2026-05-14 06:14:36"
  }
}
```

## Callback URL Configuration

Your current callback URL is set to:
```
https://5dea-41-139-186-41.ngrok-free.app/api/mpesa/callback
```

**In Production, update to:**
```env
MPESA_CALLBACK_URL=https://your-domain.com/api/mpesa/callback
```

**Requirements:**
- Must be HTTPS (not HTTP)
- Must be publicly accessible
- M-Pesa servers must be able to reach it

## Troubleshooting

### Error: "Invalid PhoneNumber"
- **Cause:** Phone number format incorrect
- **Fix:** Use format `+254708374149` or ensure it normalizes correctly
- **Check:** Test with `+254708374149`

### Error: "Unauthenticated"
- **Cause:** Missing or invalid Bearer token
- **Fix:** 
  ```bash
  php artisan mpesa:token 1
  ```
  Then copy the token to api.rest

### Error: "M-Pesa API returned status 400"
- **Cause:** API validation error from Safaricom
- **Check logs:**
  ```bash
  tail -f storage/logs/laravel.log | grep -i mpesa
  ```

### Callback Not Received
- **Cause:** Callback URL not publicly accessible
- **In development:** Use ngrok tunnel
  ```bash
  ngrok http 8000
  ```
  Then update `.env`:
  ```env
  MPESA_CALLBACK_URL=https://xxx-xxx-xxx.ngrok.io/api/mpesa/callback
  ```

## Database Schema

**mpesa_transactions table:**
```sql
CREATE TABLE mpesa_transactions (
  id BIGINT PRIMARY KEY,
  company_id BIGINT,
  user_id BIGINT,
  sale_id BIGINT,
  phone_number VARCHAR(20),
  amount DECIMAL(15, 2),
  reference VARCHAR(100),
  description TEXT,
  merchant_request_id VARCHAR(100),
  checkout_request_id VARCHAR(100) UNIQUE,
  status ENUM('pending', 'success', 'failed'),
  result_code VARCHAR(10),
  result_desc TEXT,
  mpesa_receipt_number VARCHAR(20),
  transaction_date DATETIME,
  raw_callback JSON,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

## Feature Access Control

M-Pesa endpoints require the `mpesa` feature in subscription plan:

```php
// In subscription features
{
  "name": "mpesa",
  "enabled": true
}
```

User won't see M-Pesa unless their company subscription includes this feature.

## Production Deployment Checklist

- [ ] Update MPESA_BASE_URL to production endpoint
- [ ] Use production API credentials (Consumer Key, Secret)
- [ ] Set MPESA_CALLBACK_URL to production HTTPS URL
- [ ] Remove test callback endpoint (dev only)
- [ ] Enable SSL verification (remove withoutVerifying())
- [ ] Set APP_ENV=production
- [ ] Test with actual M-Pesa account
- [ ] Monitor logs for callback errors
- [ ] Set up payment alerts/notifications
- [ ] Test with real transactions

## Quick Reference

**Test Commands:**
```bash
# Run full flow test
php artisan mpesa:test-flow

# Generate new token
php artisan mpesa:token 1

# View config
php artisan config:show services.mpesa

# Check logs
tail -f storage/logs/laravel.log
```

**Test Users:**
- Email: `test@example.com` (ID: 1)
- Email: `superuser@example.com` (ID: 2)
- Email: `cashier1@example.com` (ID: 4)

**Test Phone (Safaricom Sandbox):**
```
+254708374149 (normalized: 254708374149)
```

**M-Pesa Test Credentials:**
```
ShortCode: 174379
Passkey: bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919
Account: CustomerPayBillOnline
```
