# Subscription Feature Access Control - Implementation Guide

## Overview

The system now has complete subscription feature-based access control implemented at both backend and frontend levels. Users can only access functionalities that are included in their company's subscription plan.

---

## Architecture

### Backend (Laravel)

**Feature Middleware** (`app/Http/Middleware/CheckFeature.php`)
- Intercepts requests to protected routes
- Checks if user's subscription includes required feature(s)
- Returns 403 (Forbidden) if feature unavailable
- Superuser bypasses all checks
- Returns detailed error messages with available/required features

**Route Protection** (`routes/api.php`)
- All feature-dependent routes are wrapped with `feature:feature_name` middleware
- Example: `Route::apiResource('sales', SaleController::class)->middleware(['auth:sanctum', 'feature:sales'])`

**Subscription Model** 
- Each company has one active subscription
- Subscription belongs to a Plan
- Plan contains array of feature slugs
- Features cascade from Plan to Subscription to User

### Frontend (Vue 3)

**Feature Composable** (`src/composables/useFeatureAccess.js`)
- Provides `useFeatureAccess()` composable for Vue components
- Caches subscription features (1-minute TTL) for performance
- Methods: `loadFeatures()`, `hasAccess()`, `clearCache()`

**Router Guards** (`src/router/index.js`)
- All routes mapped to required features in `routeFeatureRequirements`
- Before navigation, checks if user has required features
- Redirects to `/unauthorized` if access denied
- Superuser bypasses all checks

**Feature Display Labels**
- User-friendly names for all 22 features
- Descriptions and icons for UI display
- Organized by category

---

## Subscription Plans

### Essential Plan (Default)
- **Price**: Free (Ksh 0/month)
- **Best for**: Small businesses starting out
- **Features** (19 total):
  - Sales, Inventory, Purchases, Warehouse, Stock Transfers
  - Customer Management, Suppliers, Tax Configuration, Expenses
  - Invoicing, Price Groups, Reports, Data Export, Audit Logs
  - User Management, Printer Config, Returns, Advanced Settings
- **Excluded**: M-Pesa, SMS Notifications, Promotions

### Professional Plan
- **Price**: Ksh 5,000/month
- **Best for**: Growing businesses
- **Features** (22 total):
  - Everything in Essential +
  - M-Pesa Integration
  - SMS Notifications  
  - Promotions
- **Add-ons**: Mobile payments, customer communication, marketing

### Enterprise Plan
- **Price**: Ksh 15,000/month
- **Best for**: Large operations
- **Features** (22+ total):
  - All available features
  - Priority support
  - Custom integrations
  - Unlimited users/transactions

---

## How Access Control Works

### 1. Request Flow

```
User Request
    ↓
[Authentication Check] → auth:sanctum middleware
    ↓
[Feature Check] → CheckFeature middleware
    ↓
User Lookup → Load company relationship
    ↓
Company → Load subscription
    ↓
Subscription → Load plan
    ↓
Plan → Check features array
    ↓
[Feature Found?] → Allow/Deny
    ↓
Allow: Route Handler | Deny: 403 Response
```

### 2. Feature Checking Logic

```php
// Backend Check
if (!$user->company?->subscription?->plan->features->includes($feature)) {
    return 403 Forbidden;
}

// Frontend Check  
if (!user.subscription.features.includes(feature)) {
    redirect to /unauthorized;
}
```

### 3. Error Responses

**Denied Access Response** (HTTP 403):
```json
{
  "error": "Feature not available in your subscription",
  "reason": "subscription_feature_unavailable",
  "required_features": ["mpesa"],
  "available_features": ["sales", "inventory", "expenses", ...]
}
```

---

## Frontend Implementation

### Using Feature Checking in Components

#### Template Example
```vue
<template>
  <div v-if="hasAccess('sales')">
    <SalesComponent />
  </div>
  <div v-else>
    <FeatureUnavailableMessage feature="Sales Management" />
  </div>
</template>

<script setup>
import { useFeatureAccess } from '@/composables/useFeatureAccess'

const { hasAccess, features, loadFeatures } = useFeatureAccess()

onMounted(async () => {
  await loadFeatures()
})
</script>
```

#### Script Example
```javascript
import { useFeatureAccess, hasFeature } from '@/composables/useFeatureAccess'

const featureHelper = useFeatureAccess()

// Check single feature
if (featureHelper.hasAccess('mpesa')) {
  enableMpesaPayment()
}

// Check multiple features
if (featureHelper.hasAccess(['sms', 'email'])) {
  enableNotifications()
}
```

### Route Configuration

Routes automatically check features before navigation:

```javascript
// In router configuration
const routeFeatureRequirements = {
  '/sales': ['sales'],
  '/promotions': ['promotions'],        // Only Professional+
  '/mpesa-settings': ['mpesa'],          // Only Professional+
  '/sms-config': ['sms'],                // Only Professional+
  '/inventory': ['inventory'],           // Essential+
  '/reports': ['reports'],               // Essential+
}
```

---

## Backend Implementation

### Protecting Routes

```php
// Require single feature
Route::get('/sales', SalesController@index)
  ->middleware('feature:sales');

// Require one of multiple features
Route::post('/payments', PaymentController@process)
  ->middleware('feature:mpesa,credit');

// Group routes with same feature requirement
Route::middleware('feature:inventory')->group(function () {
  Route::apiResource('products', ProductController::class);
  Route::post('stock/transfer', StockController@transfer);
});
```

### Handling Feature Errors in Controllers

```php
public function processMpesa(Request $request)
{
  // Feature middleware already checked access,
  // but you can also check programmatically:
  
  $user = $request->user();
  if (!in_array('mpesa', $user->company->subscription->plan->features)) {
    return response()->json([
      'error' => 'M-Pesa not available in your plan'
    ], 403);
  }
  
  // Process M-Pesa payment...
}
```

### Accessing User Features in Controllers

```php
$user = $request->user();
$features = $user->company->subscription->plan->features;

// Check single feature
if (in_array('mpesa', $features)) {
  // Feature available
}

// Check multiple features
$requiredFeatures = ['mpesa', 'credit'];
$hasAll = !array_diff($requiredFeatures, $features);
```

---

## Testing Access Control

### Testing Backend Routes

```bash
# Test with unauthenticated request (should fail)
curl -X GET http://localhost:8000/api/sales

# Test without feature (should fail with 403)
curl -X GET http://localhost:8000/api/sales \
  -H "Authorization: Bearer {token_for_essential_user}"

# Test with feature (should succeed)
curl -X GET http://localhost:8000/api/sales \
  -H "Authorization: Bearer {token_for_professional_user}"

# Test superuser (should always succeed)
curl -X GET http://localhost:8000/api/sales \
  -H "Authorization: Bearer {superuser_token}"
```

### Testing Frontend Routes

1. Login as user with Essential plan
2. Try to access `/mpesa-settings` route
3. Should redirect to `/unauthorized?reason=subscription_feature&feature=mpesa`
4. Try to access `/inventory` route
5. Should succeed

---

## Adding New Features

### 1. Define Feature Slug
Choose a slug name: `my_new_feature`

### 2. Update Plan Seeder
```php
$allFeatures = [
  'sales',
  'mpesa',
  // ... existing features ...
  'my_new_feature',  // Add here
];
```

### 3. Add to Subscription Plans
```php
$professionalFeatures = array_merge(
  $essentialFeatures,
  ['mpesa', 'sms', 'promotions', 'my_new_feature']  // Add here
);
```

### 4. Create Middleware Protection
```php
// In routes/api.php
Route::middleware(['auth:sanctum', 'feature:my_new_feature'])
  ->post('/my-feature', MyFeatureController@store);
```

### 5. Add Frontend Mapping
```javascript
// In router/index.js
const routeFeatureRequirements = {
  '/my-feature': ['my_new_feature'],  // Add here
};

const featureDisplayLabels = {
  my_new_feature: 'My New Feature',  // Add here
};
```

### 6. Add to Feature Composable
```javascript
// In composables/useFeatureAccess.js
export const FEATURE_DESCRIPTIONS = {
  my_new_feature: {
    name: 'My New Feature',
    description: 'What this feature does',
    icon: 'fas fa-icon',
    category: 'Category Name',
  }
}
```

### 7. Re-seed Database
```bash
php artisan db:seed --class=PlanSeeder
```

---

## Common Issues & Solutions

### Issue: "Feature not available in your subscription"

**Cause**: User's plan doesn't include the feature

**Solution**: 
1. Check user's current plan: `GET /api/company/subscription`
2. Upgrade plan if needed: `POST /api/company/subscription/request-upgrade`
3. Contact superuser to manually upgrade subscription

### Issue: Superuser can't access admin features

**Cause**: Superuser is not in admin/cashier role

**Solution**: Feature checking only applies to admin/cashier roles. Superuser has unrestricted access.

### Issue: Frontend allows access but backend denies

**Cause**: Cache mismatch between frontend and backend

**Solution**: 
1. Clear browser cache: `localStorage.removeItem('subscriptionFeaturesCache')`
2. Refresh page to fetch fresh features
3. Check backend subscription configuration

### Issue: New feature not working after adding

**Cause**: Database not reseeded or cache not cleared

**Solution**:
1. Run: `php artisan db:seed --class=PlanSeeder`
2. Clear backend cache if using query cache
3. Clear frontend cache
4. Restart backend if using cache

---

## Security Notes

1. **Superuser Exemption**: Intentional design - superusers need access to debug and manage all features

2. **Feature Enumeration**: Error responses reveal available features but only to authenticated users

3. **Token Scope**: Features are tied to company subscription, not individual user roles

4. **Cache Expiration**: Frontend cache expires after 1 minute to catch upgrades quickly

5. **No Offline Access**: Features always checked against latest subscription state

---

## Database Structure

### Plans Table
```
id | name | slug | description | price | billing_cycle | features | is_active | timestamps
```

### Subscriptions Table
```
id | company_id | plan_id | status | starts_at | ends_at | trial_ends_at | on_trial | monthly_fee
```

### Relationship
```
Company → Subscription → Plan
                         ├─ features: JSON array
                         └─ Example: ["sales", "inventory", "expenses", ...]
```

---

## API Endpoints for Plan Management

### SuperUser Routes
```
GET    /api/super/plans                    - List all plans
POST   /api/super/plans                    - Create new plan
GET    /api/super/plans/{id}               - Get plan details
PATCH  /api/super/plans/{id}               - Update plan
DELETE /api/super/plans/{id}               - Delete plan

PUT    /api/super/subscriptions/{id}/plan  - Change subscription plan
```

### Admin Routes
```
GET    /api/company/subscription           - Get company's subscription
GET    /api/company/subscription/plans     - List available upgrade plans
POST   /api/company/subscription/request-upgrade - Request plan upgrade
POST   /api/company/subscription/renew     - Renew subscription
```

---

## Performance Considerations

1. **Caching**: Features cached for 1 minute on frontend to reduce API calls
2. **Lazy Loading**: Subscription loaded with company only when needed
3. **Route Guards**: Feature check happens before component loads
4. **Middleware Ordering**: Feature check after auth for efficiency
5. **No N+1 Queries**: Use eager loading `->with('company.subscription.plan')`

---

## Next Steps

1. **Test the system**: Try accessing features without proper subscription
2. **Monitor errors**: Check logs for feature-blocked access attempts
3. **Gather feedback**: Collect user feedback on plan feature set
4. **Adjust plans**: Add/remove features based on customer needs
5. **Marketing**: Promote plan features to customers for upsell

---

## Support

For issues or questions:
1. Check logs: `storage/logs/laravel.log`
2. Run tests: `php artisan test --filter FeatureAccess`
3. Contact: Development team

