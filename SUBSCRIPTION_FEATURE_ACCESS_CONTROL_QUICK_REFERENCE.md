# Subscription Feature Access Control - Quick Reference

## What Was Implemented

✅ **Three-tier subscription system**:
- **Essential** (Ksh 0/month): 19 core features - everything except M-Pesa, SMS, Promotions
- **Professional** (Ksh 5000/month): 22 features - adds M-Pesa, SMS, Promotions  
- **Enterprise** (Ksh 15000/month): All 23 features

✅ **Backend Feature Middleware** (`app/Http/Middleware/CheckFeature.php`)
- Protects all sensitive routes
- Applied to: Sales, Inventory, Expenses, Invoices, Returns, Credit management
- Superuser bypasses all checks

✅ **Frontend Feature Routing**
- Routes mapped to required features in `router/index.js`
- Automatic redirection to `/unauthorized` if access denied
- Feature cache with 1-minute expiration

✅ **Feature Composable** (`composables/useFeatureAccess.js`)
- For programmatic feature checks in components
- Methods: `hasAccess()`, `loadFeatures()`, `clearCache()`

✅ **Comprehensive Documentation**
- Full implementation guide
- Database structure
- Testing procedures
- Common issues & solutions

---

## 22 Available Features

### Sales & Transactions (4)
- `sales` - POS sales/transactions
- `mpesa` - M-Pesa mobile payments ⭐ Professional+
- `credit` - Customer credit system
- `promotions` - Promotions & discounts ⭐ Professional+

### Inventory & Stock (4)
- `inventory` - Stock management
- `purchases` - Purchase orders
- `warehouse` - Warehouse management
- `stock_transfers` - Inter-warehouse transfers

### Customers & Suppliers (3)
- `customer_management` - Customer database
- `suppliers` - Supplier management
- `sms` - SMS notifications ⭐ Professional+

### Financial & Tax (4)
- `tax_configuration` - Tax settings
- `expenses` - Expense tracking
- `invoicing` - Invoice generation
- `price_groups` - Customer pricing tiers

### Reporting & Analytics (3)
- `reports` - Sales/inventory reports
- `data_export` - CSV/Excel exports
- `audit_logs` - Activity logs

### System & Administration (4)
- `user_management` - User management
- `printer_config` - Receipt printer settings
- `returns` - Product returns
- `advanced_settings` - System configuration

---

## How to Assign Default Plans to Companies

### Option 1: During Company Registration
When a new company signs up, automatically assign Essential plan.

```php
// In CompanyController or registration logic
$company = Company::create($data);
$plan = Plan::where('slug', 'essential')->first();
Subscription::create([
  'company_id' => $company->id,
  'plan_id' => $plan->id,
  'status' => 'active',
  'starts_at' => now(),
]);
```

### Option 2: Superuser Manual Assignment
Superuser can assign/change plans via UI: Superuser → Subscriptions → Change Plan

### Option 3: SQL Insert
```sql
-- Get essential plan ID
SELECT id FROM plans WHERE slug = 'essential';

-- Create subscription for company
INSERT INTO subscriptions (company_id, plan_id, status, starts_at, created_at)
VALUES (1, 1, 'active', NOW(), NOW());
```

---

## Testing Feature Access

### Test M-Pesa (Professional+ only)
1. Login as Essential user
2. Try to access M-Pesa settings
3. Should see "Feature not available" message
4. Upgrade to Professional
5. Now should have access

### Test SMS (Professional+ only)
1. Login as Essential user  
2. Try to send SMS notification
3. Backend should return 403
4. Upgrade to Professional
5. SMS should work

### Test Inventory (Essential+)
1. Login as Essential user
2. Access Inventory page
3. Should work (feature included in Essential)

---

## Files Modified/Created

### Backend
- ✅ `app/Http/Middleware/CheckFeature.php` - Feature checking middleware
- ✅ `app/Http/Kernel.php` - Register middleware
- ✅ `routes/api.php` - Apply middleware to routes
- ✅ `database/seeders/PlanSeeder.php` - Create default plans
- ✅ `database/seeders/DatabaseSeeder.php` - Include PlanSeeder

### Frontend
- ✅ `src/composables/useFeatureAccess.js` - Feature helper composable
- ✅ `src/router/index.js` - Route mappings & feature requirements

### Documentation
- ✅ `SUBSCRIPTION_FEATURE_ACCESS_CONTROL.md` - Full implementation guide
- ✅ `PLAN_FEATURES_REFERENCE.md` - Feature descriptions & plan details
- ✅ `SUBSCRIPTION_FEATURE_ACCESS_CONTROL_QUICK_REFERENCE.md` - This file

---

## Common Tasks

### Check Current User Subscription
```javascript
// Frontend
const { features } = useFeatureAccess()
console.log('Available features:', features)

// Backend
$features = auth()->user()->company->subscription->plan->features;
```

### Upgrade User Plan
```php
// Backend
$subscription = Subscription::find($id);
$newPlan = Plan::where('slug', 'professional')->first();
$subscription->update(['plan_id' => $newPlan->id]);
```

### Restrict Feature in Component
```vue
<template>
  <button v-if="hasAccess('mpesa')" @click="processMpesa">
    Pay with M-Pesa
  </button>
  <button v-else disabled class="disabled">
    M-Pesa (Upgrade required)
  </button>
</template>

<script setup>
import { useFeatureAccess } from '@/composables/useFeatureAccess'
const { hasAccess } = useFeatureAccess()
</script>
```

### Create New Feature
1. Add slug to `allFeatures` array in `PlanSeeder.php`
2. Update plan feature arrays
3. Run: `php artisan db:seed --class=PlanSeeder`
4. Add to frontend mappings in `router/index.js`
5. Add middleware to routes in `routes/api.php`

---

## Status Dashboard

### Subscriptions Created
- ✅ Essential (Free, 19 features)
- ✅ Professional (Ksh 5,000, 22 features)
- ✅ Enterprise (Ksh 15,000, 23 features)

### Access Control
- ✅ Backend: Feature middleware working
- ✅ Frontend: Route guards in place
- ✅ Error handling: Proper 403 responses

### Tested Features
- ✅ Essential plan (no M-Pesa, SMS, Promotions)
- ✅ Professional additions
- ✅ Feature caching
- ✅ Superuser bypass

### Ready for Production
- ✅ All 22 features mapped
- ✅ Database seeded
- ✅ API protected
- ✅ Frontend guided access

---

## API Response Examples

### Success (Feature Available)
```json
{
  "data": {
    "id": 1,
    "name": "Sale 1",
    "amount": 5000,
    ...
  }
}
```

### Denied (Feature Unavailable)
```json
{
  "error": "Feature not available in your subscription",
  "reason": "subscription_feature_unavailable",
  "required_features": ["mpesa"],
  "available_features": ["sales", "inventory", "credit", ...]
}
// HTTP 403
```

### Denied (No Subscription)
```json
{
  "error": "No subscription found for company",
  "reason": "no_subscription"
}
// HTTP 403
```

---

## Environment Variables

No new environment variables required.

Database migration status:
- Plans table: ✅ Exists
- Subscriptions table: ✅ Exists
- Default plans: ✅ Seeded

---

## Monitoring & Debugging

### View Plan Features
```bash
php artisan db:show plans
```

### Check Company Subscription
```bash
php artisan tinker
>>> $company = \App\Models\Company::find(1)->load('subscription.plan');
>>> $company->subscription->plan->features;
```

### Monitor Feature Blocks
Check `storage/logs/laravel.log` for HTTP 403 errors with `subscription_feature_unavailable` reason.

### Clear Feature Cache (Frontend)
```javascript
// In browser console
localStorage.removeItem('subscriptionFeaturesCache')
location.reload()
```

---

## Next Phase Features

Future enhancements to consider:
- [ ] Feature usage analytics/metrics
- [ ] Custom plan builder for superuser
- [ ] Feature trial periods
- [ ] Temporary feature unlock campaigns
- [ ] Feature usage notifications
- [ ] Plan downgrade restrictions
- [ ] Multiple subscription tiers per company

---

## Support & Questions

Refer to: `SUBSCRIPTION_FEATURE_ACCESS_CONTROL.md` for complete documentation
