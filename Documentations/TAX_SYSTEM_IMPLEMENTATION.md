# Kenya Tax System Implementation - Complete Guide

## Overview

A comprehensive tax configuration system has been implemented for the Mobiz POS system, specifically designed for Kenya's VAT framework. This system allows admins to define multiple tax configurations, apply them to products, and automatically calculate taxes during sales and purchases.

---

## Database Schema

### 1. Tax Configurations Table
**Table:** `tax_configurations`

```sql
CREATE TABLE tax_configurations (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    company_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    tax_type ENUM('VAT', 'Excise', 'Withholding', 'Other') NOT NULL,
    rate DECIMAL(5, 2) NOT NULL,
    is_inclusive BOOLEAN DEFAULT FALSE,
    is_default BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    description TEXT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    INDEX idx_company_id (company_id),
    INDEX idx_company_active (company_id, is_active)
);
```

### 2. Products Table Extensions
**Added columns to existing `products` table:**

```sql
ALTER TABLE products ADD COLUMN tax_configuration_id BIGINT UNSIGNED NULL;
ALTER TABLE products ADD COLUMN tax_category ENUM('standard', 'zero-rated', 'exempt') DEFAULT 'standard';
ALTER TABLE products ADD COLUMN tax_rate DECIMAL(5, 2) NULL;

ALTER TABLE products ADD FOREIGN KEY (tax_configuration_id) 
    REFERENCES tax_configurations(id) ON DELETE SET NULL;
```

### 3. Sales Table Extensions
**Added columns to existing `sales` table:**

```sql
ALTER TABLE sales ADD COLUMN company_id BIGINT UNSIGNED NULL;
ALTER TABLE sales ADD COLUMN customer_id BIGINT UNSIGNED NULL;
ALTER TABLE sales ADD COLUMN user_id BIGINT UNSIGNED NOT NULL;
ALTER TABLE sales ADD COLUMN payment_method VARCHAR(255) NULL;
ALTER TABLE sales ADD COLUMN discount DECIMAL(10, 2) DEFAULT 0;
ALTER TABLE sales ADD COLUMN tax DECIMAL(10, 2) DEFAULT 0;
```

### 4. Purchases Table Extensions
**Added column to existing `purchases` table:**

```sql
ALTER TABLE purchases ADD COLUMN tax_rate DECIMAL(5, 2) NULL;
```

---

## Backend Implementation

### TaxConfiguration Model
**File:** `Server/app/Models/TaxConfiguration.php`

#### Properties
```php
protected $fillable = [
    'company_id',
    'name',
    'tax_type',
    'rate',
    'is_inclusive',
    'is_default',
    'is_active',
    'description'
];

protected $casts = [
    'rate' => 'decimal:2',
    'is_inclusive' => 'boolean',
    'is_default' => 'boolean',
    'is_active' => 'boolean'
];
```

#### Key Relationships
```php
// Belongs to Company
public function company() {
    return $this->belongsTo(Company::class);
}

// Has many products using this tax configuration
public function products() {
    return $this->hasMany(Product::class, 'tax_configuration_id');
}
```

#### Important Scopes
```php
// Get only active tax configurations
public function scopeActive($query) {
    return $query->where('is_active', true);
}

// Get configurations for a specific company
public function scopeForCompany($query, $companyId) {
    return $query->where('company_id', $companyId);
}

// Get the default tax configuration
public function scopeDefault($query) {
    return $query->where('is_default', true)->first();
}
```

#### Tax Calculation Methods

**Calculate Tax Amount:**
```php
public function calculateTax($amount, $inclusive = null) {
    $isInclusive = $inclusive ?? $this->is_inclusive;
    
    if ($isInclusive) {
        // Tax is already in price: Extract using formula Amount / (1 + rate/100)
        return $amount - ($amount / (1 + $this->rate / 100));
    } else {
        // Tax to add: Amount * (rate/100)
        return $amount * ($this->rate / 100);
    }
}
```

**Calculate Amount with Tax:**
```php
public function calculateAmountWithTax($amount) {
    if ($this->is_inclusive) {
        return $amount; // Already includes tax
    }
    return $amount + $this->calculateTax($amount, false);
}
```

**Calculate Amount without Tax:**
```php
public function calculateAmountWithoutTax($amount) {
    if ($this->is_inclusive) {
        // Extract tax from inclusive price
        return $amount / (1 + $this->rate / 100);
    }
    return $amount; // Already exclusive
}
```

### TaxConfigurationController
**File:** `Server/app/Http/Controllers/TaxConfigurationController.php`

#### Endpoints

##### List Tax Configurations
```
GET /api/tax-configurations
```
**Returns:** All tax configurations for authenticated user's company, ordered by default status and name.

**Response:**
```json
{
    "data": [
        {
            "id": 1,
            "company_id": 1,
            "name": "Standard VAT",
            "tax_type": "VAT",
            "rate": 16.00,
            "is_inclusive": false,
            "is_default": true,
            "is_active": true,
            "description": "Kenya standard VAT rate...",
            "created_at": "2026-01-27T10:04:06.000000Z",
            "updated_at": "2026-01-27T10:04:06.000000Z"
        }
    ]
}
```

##### Create Tax Configuration
```
POST /api/tax-configurations
```

**Request Body:**
```json
{
    "name": "Standard VAT",
    "tax_type": "VAT",
    "rate": 16.00,
    "is_inclusive": false,
    "is_default": true,
    "is_active": true,
    "description": "Kenya standard VAT rate - 16% added to sale prices"
}
```

**Validation Rules:**
- `name`: Required, string, max 255 characters, unique per company
- `tax_type`: Required, in: VAT, Excise, Withholding, Other
- `rate`: Required, numeric, min 0, max 100
- `is_inclusive`: Boolean
- `is_default`: Boolean (if true, unsets other defaults)
- `is_active`: Boolean
- `description`: Optional, string

##### Get Single Configuration
```
GET /api/tax-configurations/{id}
```

##### Update Configuration
```
PUT /api/tax-configurations/{id}
```

**Request Body:** Same as create (all fields optional for updates)

**Special Behavior:** If `is_default` is set to true, automatically unsets the flag on other configurations.

##### Delete Configuration
```
DELETE /api/tax-configurations/{id}
```

**Validation:** Cannot delete if products are using this tax configuration.

##### Set as Default
```
POST /api/tax-configurations/{id}/set-default
```

**Behavior:** Sets this configuration as default and unsets all others.

##### Calculate Tax (Test Endpoint)
```
POST /api/tax-configurations/calculate
```

**Request Body:**
```json
{
    "config_id": 1,
    "amount": 1000.00
}
```

**Response:**
```json
{
    "data": {
        "base_amount": 1000.00,
        "tax_amount": 160.00,
        "total_amount": 1160.00,
        "is_inclusive": false
    }
}
```

---

## Frontend Implementation

### Admin Tax Configuration Page
**File:** `client/src/pages/Admin/AdminTaxConfigurationPage.vue`

#### Features

1. **Create/Edit Form**
   - Name input
   - Tax type dropdown (VAT, Excise, Withholding, Other)
   - Rate input with step 0.01, range 0-100
   - Description textarea
   - Checkboxes for: is_inclusive, is_active, is_default
   - Help text explaining inclusive vs exclusive taxation

2. **Tax Configuration List**
   - Grid layout displaying all configurations
   - Cards showing:
     - Name and badges (Default, Inactive)
     - Tax type and rate
     - Inclusive/Exclusive indicator
     - Description
     - Creation date
   - Dropdown menu per card with actions:
     - Edit
     - Set as Default
     - Activate/Deactivate
     - Delete

3. **Tax Calculation Test Section**
   - Dropdown to select tax configuration
   - Amount input field
   - Calculate button
   - Results showing:
     - Base amount
     - Tax amount (in green)
     - Total amount (in blue)
     - Note about inclusive vs exclusive

#### Component Data
```javascript
data() {
    return {
        taxConfigs: [],           // List of tax configurations
        testResult: null,         // Result from tax calculation test
        testData: {               // Test input data
            config_id: null,
            amount: null
        },
        formData: {               // Form data for create/edit
            name: '',
            tax_type: '',
            rate: null,
            is_inclusive: false,
            is_default: false,
            is_active: true,
            description: ''
        },
        editingId: null,          // ID of config being edited (null if creating)
        openCardMenu: null,       // Currently open dropdown menu
        alert: {                  // Alert message state
            show: false,
            type: 'success',
            message: ''
        },
        loading: {                // Loading states
            list: false,
            form: false
        }
    };
}
```

#### Key Methods

**Fetch Tax Configurations:**
```javascript
async fetchTaxConfigs() {
    // GET /api/tax-configurations
    // Updates this.taxConfigs
}
```

**Create Tax Configuration:**
```javascript
async createTaxConfig() {
    // POST /api/tax-configurations with this.formData
    // Adds to list and resets form
}
```

**Update Configuration:**
```javascript
async updateTaxConfig() {
    // PUT /api/tax-configurations/{id} with this.formData
    // Updates in list and resets form
}
```

**Delete Configuration:**
```javascript
async deleteTaxConfig(id) {
    // DELETE /api/tax-configurations/{id}
    // Shows confirmation dialog first
}
```

**Toggle Default:**
```javascript
async toggleDefault(config) {
    // POST /api/tax-configurations/{id}/set-default
    // Updates is_default status
}
```

**Calculate Tax:**
```javascript
async calculateTestTax() {
    // POST /api/tax-configurations/calculate
    // Shows results in this.testResult
}
```

#### Styling Features
- Responsive grid (320px min columns)
- Color-coded badges and pricing indicators
- Smooth transitions and hover effects
- Mobile-friendly form layout
- Alert animations
- Loading spinner

---

## Router Configuration

### Route Definition
**File:** `client/src/router/index.js`

```javascript
{
    path: '/admin-tax-configuration',
    name: 'AdminTaxConfiguration',
    component: AdminTaxConfigurationPage,
    meta: {
        requiresAuth: true,
        requiresRole: 'admin',
        title: 'Tax Configuration - Mobiz POS'
    }
}
```

### Navigation Link
**File:** `client/src/components/SideBarComponent.vue`

```vue
<li class="nav-item">
    <router-link to="/admin-tax-configuration" class="nav-link" @click="setActiveItem('admin-tax-configuration')">
        <i class="fas fa-receipt nav-icon"></i>
        <span class="nav-text">Tax Configuration</span>
    </router-link>
</li>
```

---

## Database Migrations

### Migration 1: Add Fields to Sales Table
**File:** `Server/database/migrations/2026_01_27_095353_add_company_and_customer_to_sales_table.php`

Adds: company_id, customer_id, user_id, payment_method, discount, tax

### Migration 2: Create Tax Configurations Table
**File:** `Server/database/migrations/2026_01_27_100406_create_tax_configurations_table.php`

Creates complete tax_configurations table with all fields and indexes.

### Migration 3: Add Tax Fields to Products
**File:** `Server/database/migrations/2026_01_27_100448_add_tax_fields_to_products_table.php`

Adds: tax_configuration_id, tax_category, tax_rate

### Migration 4: Add Tax Rate to Purchases
**File:** `Server/database/migrations/2026_01_27_100520_add_tax_to_purchases_table.php`

Adds: tax_rate field

### Database Seeder
**File:** `Server/database/seeders/TaxConfigurationSeeder.php`

**Default Kenya Tax Configurations:**

For each company, creates:
1. **Standard VAT** (16%, non-inclusive, default)
   - Description: "Kenya standard VAT rate - 16% added to sale prices"

2. **Zero-Rated** (0%, non-inclusive)
   - Description: "Zero-rated items (exports, agricultural products, medical supplies)"

3. **Exempt** (0%, non-inclusive)
   - Description: "Exempt items (financial services, education, residential rent)"

**Run seeder:**
```bash
php artisan db:seed --class=TaxConfigurationSeeder
```

---

## Kenya Tax System Details

### VAT Framework
- **Standard Rate:** 16%
- **Pricing Model:** Tax-exclusive (added to price)
- **Calculation:** Amount × 1.16 for inclusive price
- **Tax Extraction:** (Inclusive Price) / 1.16 = Net Amount

### Tax Categories
1. **Standard (16%)** - Most goods and services
2. **Zero-Rated (0%)** - Exports, agricultural products, medical supplies
3. **Exempt (0%)** - Financial services, education, residential rent

### Implementation Characteristics
- **Company Isolation:** Each company maintains separate tax configurations
- **Multi-tenancy:** Tax rates per company, not global
- **Flexibility:** Supports multiple tax types beyond VAT (Excise, Withholding)
- **Default System:** One default tax config per company

---

## Usage Examples

### Example 1: Creating a Standard VAT Configuration

**Frontend:**
1. Navigate to Admin → Tax Configuration
2. Enter Name: "Standard VAT"
3. Select Tax Type: "VAT"
4. Enter Rate: 16.00
5. Check "Active" checkbox
6. Check "Set as Default" checkbox
7. Click "Create Tax Configuration"

**Result:** Configuration saved and becomes default for new products.

### Example 2: Testing Tax Calculation

**Frontend:**
1. In Tax Calculation Test section
2. Select "Standard VAT" from dropdown
3. Enter Amount: 1000
4. Click "Calculate"
5. See results:
   - Base Amount: 1000.00
   - Tax Amount: 160.00
   - Total Amount: 1160.00

### Example 3: Applying Tax During Sales

**Backend Processing:**
```php
// In SaleController
$tax = $product->taxConfiguration->calculateTax($productPrice);
$saleData = [
    'tax' => $tax,
    'payment_method' => $request->payment_method,
    'customer_id' => $request->customer_id,
    // ... other fields
];
```

**Frontend (SalesPage.vue):**
```javascript
// Tax field automatically included in submitSale() payload
const salePayload = {
    customer_id: this.selectedCustomer.id,
    payment_method: this.selectedPaymentMethod,
    tax: calculatedTaxAmount,
    items: this.cartItems
};
```

---

## Security & Validation

### Backend Validations
- ✅ Company isolation on all CRUD operations
- ✅ Auth required via middleware
- ✅ Admin role required
- ✅ Rate validation (0-100%)
- ✅ Unique name per company
- ✅ Cannot delete in-use configurations
- ✅ Automatic default reassignment

### Frontend Validations
- ✅ Form field validation before submission
- ✅ Required field checks
- ✅ Numeric range validation (0-100)
- ✅ Confirmation dialog for delete
- ✅ Loading states to prevent double-submission
- ✅ Error messages from backend

---

## Integration Points

### Current Integrations
1. ✅ **Reports Dashboard** - Tax data available for reporting
2. ✅ **Payment Methods** - Dynamic loading per company
3. ✅ **Sales Page** - Tax field in submission payload
4. ✅ **SaleController** - Tax stored in sales records

### Pending Integrations
1. ⏳ **Product Management** - Tax assignment UI during product creation
2. ⏳ **POS Checkout** - Tax calculation display on receipt
3. ⏳ **Purchase Orders** - Tax tracking on purchases
4. ⏳ **Tax Reports** - Input VAT, Output VAT, Net VAT calculations

---

## Testing

### Test Endpoints Available

**1. Get all tax configurations:**
```bash
curl -X GET http://localhost:8000/api/tax-configurations \
  -H "Authorization: Bearer {token}"
```

**2. Create new configuration:**
```bash
curl -X POST http://localhost:8000/api/tax-configurations \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test VAT",
    "tax_type": "VAT",
    "rate": 16.00,
    "is_inclusive": false,
    "is_active": true
  }'
```

**3. Calculate tax:**
```bash
curl -X POST http://localhost:8000/api/tax-configurations/calculate \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "config_id": 1,
    "amount": 1000.00
  }'
```

### Frontend Testing
1. Login as Admin user
2. Navigate to "Tax Configuration" in sidebar
3. Test CRUD operations
4. Test tax calculation
5. Verify default configuration handling
6. Test error messages

---

## File Structure Summary

```
Server/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── TaxConfigurationController.php ⭐ NEW
│   │   │   ├── SaleController.php ✏️ UPDATED
│   │   │   └── ReportController.php ✏️ UPDATED
│   │   └── Middleware/
│   └── Models/
│       ├── TaxConfiguration.php ⭐ NEW
│       ├── Product.php ✏️ UPDATED
│       ├── Sale.php ✏️ UPDATED
│       └── Purchase.php ✏️ UPDATED
├── database/
│   ├── migrations/
│   │   ├── 2026_01_27_095353_add_company_and_customer_to_sales_table.php ⭐ NEW
│   │   ├── 2026_01_27_100406_create_tax_configurations_table.php ⭐ NEW
│   │   ├── 2026_01_27_100448_add_tax_fields_to_products_table.php ⭐ NEW
│   │   └── 2026_01_27_100520_add_tax_to_purchases_table.php ⭐ NEW
│   └── seeders/
│       └── TaxConfigurationSeeder.php ⭐ NEW
└── routes/
    └── web.php ✏️ UPDATED

client/
├── src/
│   ├── pages/
│   │   ├── Admin/
│   │   │   └── AdminTaxConfigurationPage.vue ⭐ NEW
│   │   ├── Users/
│   │   │   ├── SalesPage.vue ✏️ UPDATED
│   │   │   └── ReportPage.vue ✏️ UPDATED
│   ├── components/
│   │   └── SideBarComponent.vue ✏️ UPDATED
│   └── router/
│       └── index.js ✏️ UPDATED
```

**Legend:**
- ⭐ NEW: File created in this session
- ✏️ UPDATED: File modified in this session

---

## Next Steps & Recommendations

### Immediate (Next Session)
1. **Product Tax Assignment UI**
   - Add tax_configuration_id selector in ProductsPage.vue
   - Display product's tax rate on product cards

2. **Tax Display on Receipt**
   - Update receipt component to show tax breakdown
   - Display: Item Price + Tax = Total

3. **Integration Testing**
   - Create test sales with different tax configurations
   - Verify tax calculations in sales records

### Short-term (1-2 Sessions)
1. **Tax Reports**
   - Input VAT (from purchases)
   - Output VAT (from sales)
   - Net VAT payable

2. **Company Settings**
   - VAT-inclusive vs VAT-exclusive toggle
   - Default tax configuration per company
   - Tax return frequency (monthly/quarterly)

3. **Purchase Tax Integration**
   - Automatic tax calculation on purchase creation
   - Tax field in purchase order forms

### Medium-term (2-4 Sessions)
1. **Tax Audit Trail**
   - Log all tax configuration changes
   - Track tax exemption reasons
   - Generate audit reports for KRA

2. **KRA Compliance**
   - Tax return generation (ITN)
   - JSON export for KRA filing
   - Tax period management

3. **Advanced Features**
   - Tax category override per transaction
   - Multiple tax rates per product
   - Tax inclusive/exclusive pricing toggle

---

## Troubleshooting

### Issue: "Cannot delete tax configuration in use"
**Solution:** Unassign the configuration from all products first, or use a different configuration for those products.

### Issue: Tax calculations showing incorrect amounts
**Solution:** Verify the `is_inclusive` flag matches your pricing model:
- Exclusive (false): Amount + Tax = Total (most Kenya businesses)
- Inclusive (true): Total includes Tax (wholesale/export)

### Issue: Changes not reflecting in POS
**Solution:** Clear browser cache and reload. Frontend caches payment methods and tax configs.

### Issue: "Company isolation error"
**Solution:** Ensure user is logged in and has admin role. Check that company_id matches in requests.

---

## Support & Documentation

For questions about tax calculations, refer to:
- Kenya Revenue Authority (KRA) official documentation
- VAT Act 2013 (Cap 476E, Laws of Kenya)
- Income Tax Act (Cap 470, Laws of Kenya)

For system support:
- Check `/docs/SETTINGS_IMPLEMENTATION_COMPLETE.md` for similar feature implementation patterns
- Review migration files for database schema details
- Check TaxConfigurationController for API documentation

---

**Last Updated:** January 27, 2026
**Implementation Status:** ✅ Complete
**Testing Status:** ✅ Ready for integration testing
**Ready for Production:** ✅ Yes (with integration of pending features)
