# M-Pesa API Testing - Rate Limiting Guide

## Issue: 429 Rate Limit Error

**Error Message:**
```
Spike arrest violation. 
Allowed rate: MessageRate{messagesPerPeriod=5, periodInMicroseconds=60000000}
```

**Meaning:** Safaricom M-Pesa Sandbox limits queries to **5 requests per 60 seconds**.

## Solutions

### 1. Frontend: Implement Query Debouncing

**In your SalesPage.vue:**

```vue
<script>
data() {
  return {
    mpesaQueryTimeout: null,
    lastMpesaQueryTime: 0,
    mpesaQueryCooldown: 12000, // 12 seconds between queries
  }
},

methods: {
  checkMpesaPaymentStatus() {
    const now = Date.now();
    const timeSinceLastQuery = now - this.lastMpesaQueryTime;
    
    // Prevent queries within cooldown period
    if (timeSinceLastQuery < this.mpesaQueryCooldown) {
      const waitTime = this.mpesaQueryCooldown - timeSinceLastQuery;
      this.$notify.warning(`Please wait ${Math.ceil(waitTime / 1000)} seconds before checking status again`);
      return;
    }
    
    this.lastMpesaQueryTime = now;
    this.queryMpesaStatus(); // Your actual query method
  },
  
  queryMpesaStatus() {
    // Your POST request to /api/mpesa/stk-query
    fetch('/api/mpesa/stk-query', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${this.authToken}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        checkout_request_id: this.checkoutRequestId
      })
    })
    .then(response => {
      if (response.status === 429) {
        // Rate limited - tell user to wait
        this.$notify.warning('Too many requests. Please wait 60 seconds before trying again.');
        return;
      }
      return response.json();
    })
    .then(data => {
      if (data && data.transaction) {
        // Update UI with transaction status
        this.updatePaymentStatus(data.transaction);
      }
    })
    .catch(error => {
      console.error('Status query failed:', error);
      this.$notify.error('Failed to check payment status');
    });
  }
}
</script>
```

### 2. Backend: Automatic Status Updates with Webhooks

**Instead of polling, use M-Pesa callbacks:**

1. When user completes payment on their phone
2. M-Pesa sends callback to `/api/mpesa/callback`
3. Backend updates transaction status automatically
4. Frontend listens for real-time updates (WebSocket/Events)

**Example callback receiver is already in place:** [app/Http/Controllers/MpesaController.php](app/Http/Controllers/MpesaController.php#L150)

### 3. Server-Side Caching

**Cache query results to avoid duplicate requests:**

```php
// In MpesaService.php

public function queryStkStatusCached(string $checkoutRequestId): array
{
    $cacheKey = "mpesa_query_{$checkoutRequestId}";
    
    // Return cached result if available (cache for 30 seconds)
    if (Cache::has($cacheKey)) {
        return Cache::get($cacheKey);
    }
    
    // Make actual query
    $response = $this->queryStkStatus($checkoutRequestId);
    
    // Cache the result
    Cache::put($cacheKey, $response, now()->addSeconds(30));
    
    return $response;
}
```

## Rate Limit Details

| Parameter | Value |
|-----------|-------|
| **Limit** | 5 requests per 60 seconds |
| **Burst** | 1 message maximum burst |
| **Error Code** | 429 (Too Many Requests) |
| **Recommended** | Wait 60 seconds before retrying |
| **Production** | May have different limits |

## Best Practices

### ✅ DO:
- Wait 12+ seconds between status queries
- Use callbacks instead of polling
- Implement exponential backoff on retries
- Cache query results
- Show user a "checking..." message and disable button during query

### ❌ DON'T:
- Rapid-fire multiple queries in a loop
- Query on every keystroke
- Query before payment is initiated
- Make 5+ queries within 60 seconds

## Example Exponential Backoff

```javascript
async function queryWithBackoff(checkoutId, maxRetries = 3) {
  for (let attempt = 0; attempt < maxRetries; attempt++) {
    try {
      const response = await fetch('/api/mpesa/stk-query', {
        method: 'POST',
        body: JSON.stringify({ checkout_request_id: checkoutId })
      });
      
      if (response.status === 429) {
        // Wait before retry: 2^attempt * 15 seconds
        const waitTime = Math.pow(2, attempt) * 15000;
        console.log(`Rate limited. Waiting ${waitTime}ms before retry...`);
        await new Promise(resolve => setTimeout(resolve, waitTime));
        continue;
      }
      
      return await response.json();
    } catch (error) {
      if (attempt === maxRetries - 1) throw error;
    }
  }
}
```

## Testing with Sandbox

**To test without hitting rate limits:**

1. **Use different checkout IDs** - Don't query the same ID repeatedly
2. **Wait 60 seconds** between queries to the same transaction
3. **Use test-callback endpoint** - Simulates payment without API call:
   ```
   POST /api/mpesa/test-callback (dev-only endpoint)
   ```

4. **Check transaction status from DB** instead:
   ```sql
   SELECT * FROM mpesa_transactions WHERE id = ?;
   ```

## Production Deployment

**For production (live M-Pesa account):**
- Rate limits may be different
- Consider implementing a "poll every 30 seconds" with backoff
- Always prefer webhook callbacks
- Monitor logs for rate limit errors
- Implement proper retry logic

## New Error Response

With the fix applied, you now get:

**Status 429 Response (Rate Limited):**
```json
{
  "error": "Rate limit exceeded",
  "message": "Too many requests. Please wait a moment before trying again.",
  "retry_after": 60,
  "transaction": { ... }
}
```

**Instead of:** Generic 500 error

This allows your frontend to handle it gracefully!
