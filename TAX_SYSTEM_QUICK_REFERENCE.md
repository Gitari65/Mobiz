# Kenya Tax System - Quick Reference Guide

## 🚀 Quick Start for Developers

### Accessing the Tax Configuration Admin Panel

**URL:** `http://localhost:3000/admin-tax-configuration`  
**Role Required:** Admin  
**Authentication:** Bearer token required

### API Endpoints Quick Reference

```bash
# List all tax configurations
GET /api/tax-configurations
Header: Authorization: Bearer {token}

# Create new tax configuration
POST /api/tax-configurations
Body: {
    "name": "Standard VAT",
    "tax_type": "VAT",
    "rate": 16.00,
    "is_inclusive": false,
    "is_default": true,
    "is_active": true,
    "description": "Kenya VAT..."
}

# Get single configuration
GET /api/tax-configurations/{id}

# Update configuration
PUT /api/tax-configurations/{id}
Body: { fields to update }

# Delete configuration
DELETE /api/tax-configurations/{id}

# Set as default
POST /api/tax-configurations/{id}/set-default

# Test tax calculation
POST /api/tax-configurations/calculate
Body: {
    "config_id": 1,
    "amount": 1000.00
}
```

---

## 📁 File Locations

### Backend
```
Server/
├── app/Models/TaxConfiguration.php               ← Model
├── app/Http/Controllers/
│   └── TaxConfigurationController.php            ← Controller
├── database/migrations/
│   ├── 2026_01_27_095353_*.php                  ← Sales table
│   ├── 2026_01_27_100406_*.php                  ← Tax table
│   ├── 2026_01_27_100448_*.php                  ← Products table
│   └── 2026_01_27_100520_*.php                  ← Purchases table
└── database/seeders/TaxConfigurationSeeder.php   ← Default data
```

### Frontend
```
client/src/
├── pages/Admin/
│   └── AdminTaxConfigurationPage.vue             ← Admin UI
├── router/index.js                              ← Routes
└── components/SideBarComponent.vue               ← Navigation
```

---

## 🧪 Testing Endpoints with cURL

### Create Tax Config
```bash
curl -X POST http://localhost:8000/api/tax-configurations \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Standard VAT",
    "tax_type": "VAT",
    "rate": 16.00,
    "is_inclusive": false,
    "is_active": true,
    "description": "Kenya standard VAT"
  }'
```

### List All Configs
```bash
curl -X GET http://localhost:8000/api/tax-configurations \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Calculate Tax
```bash
curl -X POST http://localhost:8000/api/tax-configurations/calculate \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "config_id": 1,
    "amount": 1000
  }'
```

---

## 🔑 Key Methods & Properties

### TaxConfiguration Model
```php
// Calculate tax amount
$tax = $config->calculateTax($amount);

// Get amount with tax
$total = $config->calculateAmountWithTax($amount);

// Get amount without tax
$net = $config->calculateAmountWithoutTax($amount);

// Get all active configs
$active = TaxConfiguration::active()->get();

// Get default for company
$default = TaxConfiguration::forCompany($companyId)->default();

// Relationship
$products = $config->products(); // Get products using this tax
$company = $config->company();   // Get company
```

### TaxConfigurationController Methods
```php
// List all
public function index()

// Create new
public function store(Request $request)

// Get single
public function show($id)

// Update
public function update(Request $request, $id)

// Delete
public function destroy($id)

// Set as default
public function setDefault($id)

// Calculate tax (test)
public function calculate(Request $request)
```

### Frontend Component Methods
```javascript
fetchTaxConfigs()          // Load all configurations
createTaxConfig()          // Create new
updateTaxConfig()          // Update existing
deleteTaxConfig(id)        // Delete
toggleDefault(config)      // Set as default
toggleActive(config)       // Activate/Deactivate
editTaxConfig(config)      // Load into form
cancelEdit()               // Cancel editing
calculateTestTax()         // Test calculation
showAlert(msg, type)       // Show notification
formatDate(dateString)     // Format date
formatCurrency(value)      // Format money
```

---

## 💾 Database Schema Quick Reference

### Tax Configurations Table
```sql
Column             Type           Notes
─────────────────────────────────────────────────────
id                 BIGINT PK      Auto-increment
company_id         BIGINT FK      Links to companies
name               VARCHAR(255)   Unique per company
tax_type           ENUM           VAT|Excise|Withholding|Other
rate               DECIMAL(5,2)   0.00 to 100.00
is_inclusive       BOOLEAN        Tax in price?
is_default         BOOLEAN        Default for new products?
is_active          BOOLEAN        Active?
description        TEXT           Optional description
created_at         TIMESTAMP      When created
updated_at         TIMESTAMP      Last update
```

### Related Fields in Other Tables
```sql
-- Products
tax_configuration_id    → FK to tax_configurations(id)
tax_category            → 'standard' | 'zero-rated' | 'exempt'
tax_rate                → DECIMAL(5,2) - cached rate

-- Sales
company_id              → FK to companies(id)
customer_id             → FK to customers(id)
user_id                 → FK to users(id)
payment_method          → VARCHAR(255)
discount                → DECIMAL(10,2)
tax                     → DECIMAL(10,2) ← Tax amount

-- Purchases
tax_rate                → DECIMAL(5,2) ← Tax rate used
```

---

## 🧮 Tax Calculation Formulas

### Exclusive Tax (is_inclusive = false)
```
For 1000 units at 16% VAT:
─────────────────────────────
Net Amount = 1000.00
Tax = 1000 × 0.16 = 160.00
Total = 1000 + 160 = 1160.00
```

### Inclusive Tax (is_inclusive = true)
```
For 1160 total (includes 16% VAT):
──────────────────────────────────
Total Amount = 1160.00
Net = 1160 ÷ 1.16 = 1000.00
Tax = 1160 - 1000 = 160.00
```

### Model Implementation
```php
public function calculateTax($amount, $inclusive = null) {
    $isInclusive = $inclusive ?? $this->is_inclusive;
    
    if ($isInclusive) {
        // Extract: Total / (1 + rate%)
        return $amount - ($amount / (1 + $this->rate / 100));
    } else {
        // Add: Amount × rate%
        return $amount * ($this->rate / 100);
    }
}
```

---

## 🔐 Authentication & Authorization

### Required Credentials
- **User Role:** admin (role_id = 2)
- **Middleware:** auth:sanctum
- **Token Header:** Authorization: Bearer {token}

### Company Isolation
```php
// All queries automatically filter by user's company
$user = auth()->user();
$company_id = $user->company_id;

// Query construction
where('company_id', $company_id)
```

---

## 🎨 Frontend Component Structure

### Data Properties
```javascript
taxConfigs           // Array of tax configurations
testResult           // Result from tax calculation test
testData             // Test input form data
formData             // Form for create/edit
editingId            // Currently editing which config
openCardMenu         // Which card menu is open
alert                // Alert message state
loading              // Loading indicators
```

### Computed Properties
```javascript
activeTaxConfigs     // Only active configurations
```

### Key Sections
1. **Page Header** - Title and description
2. **Create/Edit Form** - Form for CRUD operations
3. **Configuration List** - Grid of cards showing all configs
4. **Test Section** - Tax calculation test tool

---

## ⚙️ Configuration Options

### Tax Type Options
```
- VAT (Value Added Tax) - Most common in Kenya
- Excise (Excise Duty) - On specific goods
- Withholding (Withholding Tax) - On payments
- Other (Custom) - For other tax types
```

### Kenya Default Configurations
```
1. Standard VAT
   - Rate: 16%
   - Type: VAT
   - Inclusive: false
   - Default: true

2. Zero-Rated
   - Rate: 0%
   - Type: VAT
   - Inclusive: false
   - Default: false
   - Use: Exports, agriculture, medical

3. Exempt
   - Rate: 0%
   - Type: VAT
   - Inclusive: false
   - Default: false
   - Use: Financial services, education
```

---

## 🚨 Common Issues & Solutions

### Issue: "Cannot delete tax configuration"
**Cause:** Products are using this tax configuration  
**Solution:** 
1. Reassign products to different tax config
2. Or deactivate instead of deleting

### Issue: Wrong tax calculation
**Cause:** Incorrect is_inclusive flag  
**Solution:** 
- false = Tax added to price (Kenya retail)
- true = Tax already in price (wholesale)

### Issue: Changes not appearing
**Cause:** Browser cache  
**Solution:** 
1. Clear browser cache
2. Or Ctrl+Shift+Delete and reload

### Issue: "Company isolation error"
**Cause:** Not logged in or not admin  
**Solution:**
1. Verify logged in as admin user
2. Check localStorage has valid token

### Issue: API returns empty list
**Cause:** No configs for this company yet  
**Solution:**
1. Run seeder: `php artisan db:seed --class=TaxConfigurationSeeder`
2. Or create new configuration

---

## 📊 Integration Checklist

### For Product Management
- [ ] Add tax_configuration_id field to product form
- [ ] Make field required or use default
- [ ] Display tax rate on product listing
- [ ] Save tax_configuration_id to database

### For POS/Sales
- [ ] Load product's tax configuration on add-to-cart
- [ ] Calculate tax based on price and tax config
- [ ] Display tax breakdown on receipt
- [ ] Store tax amount in sales table

### For Reports
- [ ] Calculate Input VAT from purchases
- [ ] Calculate Output VAT from sales
- [ ] Create tax period reports
- [ ] Show net VAT payable

### For Company Settings
- [ ] Add default tax config selection
- [ ] Add VAT-inclusive/exclusive toggle
- [ ] Add tax return frequency setting
- [ ] Display company tax summary

---

## 🧑‍💻 Development Tips

### Add Feature to Use Tax Config
```javascript
// 1. Import axios
import axios from 'axios'

// 2. Fetch config
const config = await axios.get(
  `/api/tax-configurations/${configId}`,
  { headers: { Authorization: `Bearer ${token}` } }
)

// 3. Calculate tax (frontend)
const taxAmount = 
  price * (config.data.rate / 100)

// 4. Or POST to calculate endpoint
const result = await axios.post(
  '/api/tax-configurations/calculate',
  { config_id: configId, amount: price },
  { headers: { Authorization: `Bearer ${token}` } }
)

// 5. Use result.data.tax_amount
console.log(result.data.tax_amount)
```

### Debug Tax Calculation
```php
// In TaxConfiguration model or controller
$config = TaxConfiguration::find(1);
dd($config->calculateTax(1000));  // Output: 160

// With different flag
dd($config->calculateTax(1160, true));  // Output: 160
```

### Check Company Isolation
```bash
# Should only show own company's configs
php artisan tinker
> $user = User::where('email', 'admin@test.com')->first();
> auth()->login($user);
> auth()->user()->company_id;  // Shows company
> TaxConfiguration::where('company_id', auth()->user()->company_id)->count();
```

---

## 📚 Documentation Files

| File | Purpose | Read Time |
|------|---------|-----------|
| TAX_SYSTEM_IMPLEMENTATION.md | Complete technical guide | 15 min |
| TAX_SYSTEM_CHECKLIST.md | Progress and roadmap | 10 min |
| TAX_SYSTEM_ARCHITECTURE.md | Diagrams and data flows | 10 min |
| TAX_SYSTEM_COMPLETION_REPORT.md | Project summary | 10 min |
| This file (Quick Reference) | Quick lookup | 5 min |

---

## 🆘 Getting Help

### For API Issues
- Check route: `php artisan route:list --path=tax`
- Check middleware: Look at web.php route definitions
- Check controller: TaxConfigurationController.php
- Test with: curl or Postman

### For Frontend Issues
- Check browser console for errors
- Check Network tab for API responses
- Clear localStorage and re-login
- Check router configuration in index.js

### For Database Issues
- Check migrations: `php artisan migrate:status`
- Check tables: `SHOW TABLES LIKE 'tax%'`
- Run seeder: `php artisan db:seed --class=TaxConfigurationSeeder`
- Check foreign keys: `SHOW CREATE TABLE tax_configurations\G`

### For Business Logic Issues
- Review calculateTax() method in TaxConfiguration.php
- Check is_inclusive flag setting
- Test with /calculate endpoint
- Verify rate values (0-100)

---

## 📞 Support Contacts

- **Laravel Docs:** https://laravel.com/docs
- **Vue 3 Docs:** https://vuejs.org
- **MySQL Docs:** https://dev.mysql.com
- **Kenya KRA:** https://www.kra.go.ke

---

**Last Updated:** January 27, 2026  
**Version:** 1.0  
**Status:** ✅ Current & Accurate
