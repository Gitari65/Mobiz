# Kenya Tax System - Architecture & Data Flow Diagram

## System Architecture

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         MOBIZ POS - TAX SYSTEM                          │
└─────────────────────────────────────────────────────────────────────────┘

┌──────────────────────┐
│   FRONTEND (Vue 3)   │
├──────────────────────┤
│ AdminTaxConfiguration│  ← Admin interface for tax management
│Page (980 lines)      │  
├──────────────────────┤
│ Features:            │
│ • CRUD Operations    │
│ • Tax Calculation    │
│ • Default Toggle     │
│ • Activate/Deactivate│
└──────────┬───────────┘
           │
           │ HTTP/Axios with Bearer Token
           │
           ▼
┌──────────────────────────────────────────────────────┐
│        BACKEND API (Laravel 11)                      │
├──────────────────────────────────────────────────────┤
│  TaxConfigurationController (7 Endpoints)            │
├──────────────────────────────────────────────────────┤
│ Route: GET    /api/tax-configurations          │
│        POST   /api/tax-configurations          │
│        GET    /api/tax-configurations/{id}     │
│        PUT    /api/tax-configurations/{id}     │
│        DELETE /api/tax-configurations/{id}     │
│        POST   /api/tax-configurations/{id}/... │
│        POST   /api/tax-configurations/calculate│
└──────────────────┬───────────────────────────────┘
                   │
                   │ Eloquent ORM
                   │
                   ▼
┌──────────────────────────────────────────────────────┐
│          DATA LAYER (MySQL Database)                 │
├──────────────────────────────────────────────────────┤
│ tax_configurations Table (Core)                      │
│ ├─ id (PK)                                          │
│ ├─ company_id (FK) ─────────────┐                   │
│ ├─ name                          │                   │
│ ├─ tax_type (VAT|Excise|...)    │                   │
│ ├─ rate (0.00 - 100.00)         │                   │
│ ├─ is_inclusive (boolean)       │                   │
│ ├─ is_default (boolean)         │                   │
│ ├─ is_active (boolean)          │                   │
│ ├─ description                  │                   │
│ └─ timestamps                   │                   │
│                                 │                   │
│ products Table (FK Relationship)│                   │
│ ├─ tax_configuration_id ────────┘                   │
│ ├─ tax_category (standard|zero-rated|exempt)        │
│ └─ tax_rate                                         │
│                                                     │
│ sales Table (Extended)                              │
│ ├─ company_id (FK)                                  │
│ ├─ customer_id (FK)                                 │
│ ├─ user_id (FK)                                     │
│ ├─ payment_method                                   │
│ ├─ discount                                         │
│ ├─ tax ◄──────────────── tax amount stored here     │
│ └─ timestamps                                       │
│                                                     │
│ purchases Table (Extended)                          │
│ ├─ tax_rate ◄────────────────tax rate tracked here  │
│ └─ ...                                              │
└──────────────────────────────────────────────────────┘
```

## Data Flow: Creating a Tax Configuration

```
┌─────────────────────────────────────┐
│  Admin User Interface               │
│  AdminTaxConfigurationPage.vue      │
├─────────────────────────────────────┤
│ 1. Fill Form:                       │
│    - Name: "Standard VAT"           │
│    - Type: "VAT"                    │
│    - Rate: 16.00                    │
│    - is_inclusive: false            │
│    - is_default: true               │
│    - is_active: true                │
└─────────────┬───────────────────────┘
              │
              │ POST /api/tax-configurations
              │ Headers: Authorization: Bearer {token}
              │
              ▼
┌─────────────────────────────────────┐
│  Backend API Validation             │
│  TaxConfigurationController::store()│
├─────────────────────────────────────┤
│ 2. Validate Request:                │
│    ✓ name: required, max 255        │
│    ✓ tax_type: in VAT,Excise,...    │
│    ✓ rate: numeric, 0-100           │
│    ✓ is_default: boolean            │
│    ✓ is_active: boolean             │
│    ✓ description: optional          │
└─────────────┬───────────────────────┘
              │
              │ if is_default = true
              │ then unset other defaults
              │
              ▼
┌─────────────────────────────────────┐
│  Model Processing                   │
│  TaxConfiguration::create()         │
├─────────────────────────────────────┤
│ 3. Auto-populate:                   │
│    - company_id: auth()->user()->... │
│    - created_at: now()              │
│    - updated_at: now()              │
└─────────────┬───────────────────────┘
              │
              │ INSERT INTO tax_configurations
              │
              ▼
┌─────────────────────────────────────┐
│  Database Storage                   │
│  tax_configurations Table           │
├─────────────────────────────────────┤
│ 4. Row Inserted:                    │
│ id: 1                               │
│ company_id: 1                       │
│ name: "Standard VAT"                │
│ tax_type: "VAT"                     │
│ rate: 16.00                         │
│ is_inclusive: 0 (false)             │
│ is_default: 1 (true)                │
│ is_active: 1 (true)                 │
│ ...timestamps...                    │
└─────────────┬───────────────────────┘
              │
              │ Response: 201 Created
              │ + Configuration object
              │
              ▼
┌─────────────────────────────────────┐
│  Frontend State Update              │
│  adminTaxConfigurationPage.vue      │
├─────────────────────────────────────┤
│ 5. Update State:                    │
│    - Add to taxConfigs[]            │
│    - Reset formData                 │
│    - Show success alert             │
│    - Re-render list                 │
└─────────────┬───────────────────────┘
              │
              ▼
┌─────────────────────────────────────┐
│  User Sees Result                   │
│  ✓ "Tax configuration created       │
│     successfully"                   │
│  ✓ New item appears in list         │
│  ✓ Form cleared for next entry      │
└─────────────────────────────────────┘
```

## Data Flow: Calculating Tax on a Sale

```
┌──────────────────────────────┐
│  Cashier POS (SalesPage.vue) │
├──────────────────────────────┤
│ 1. Scan Product              │
│    - Get product_id          │
│    - Load product details    │
│    - Get tax_configuration_id│
└──────────┬───────────────────┘
           │
           │ Query Product Model
           │
           ▼
┌──────────────────────────────┐
│  Product Model               │
│  Products Table              │
├──────────────────────────────┤
│ 2. Retrieve Tax Info:        │
│    - product.price: 1000     │
│    - tax_configuration_id: 1 │
│    - tax_rate: 16.00         │
└──────────┬───────────────────┘
           │
           │ load('taxConfiguration')
           │
           ▼
┌──────────────────────────────┐
│  Tax Configuration           │
│  from tax_configurations     │
├──────────────────────────────┤
│ 3. Load Tax Rules:           │
│    - rate: 16.00%            │
│    - is_inclusive: false     │
│    - tax_type: "VAT"         │
└──────────┬───────────────────┘
           │
           │ Call calculateTax()
           │ with price: 1000
           │
           ▼
┌──────────────────────────────┐
│  Tax Calculation             │
│  TaxConfiguration.php        │
├──────────────────────────────┤
│ 4. Calculate:                │
│    if is_inclusive = false:  │
│      tax = 1000 * 0.16       │
│      tax = 160.00            │
│    else:                     │
│      tax = 1000 / 1.16       │
│      tax = 862.07            │
│    return 160.00             │
└──────────┬───────────────────┘
           │
           │ Return tax_amount
           │
           ▼
┌──────────────────────────────┐
│  Frontend Calculation        │
│  SalesPage.vue               │
├──────────────────────────────┤
│ 5. Display & Submit:         │
│    - Item Price: 1000.00     │
│    - Tax: 160.00             │
│    - Total: 1160.00          │
│    - Quantity: 1             │
│    Cart Total: 1160.00       │
│    Subtotal: 1000.00         │
│    Total Tax: 160.00         │
└──────────┬───────────────────┘
           │
           │ POST /api/sales
           │ { 
           │   items: [...],
           │   tax: 160.00,
           │   payment_method: "Cash",
           │   customer_id: 1
           │ }
           │
           ▼
┌──────────────────────────────┐
│  Backend Sale Processing     │
│  SaleController.php          │
├──────────────────────────────┤
│ 6. Store Sale Record:        │
│    - Validate all fields     │
│    - Calculate totals        │
│    - Save to sales table     │
│    - Include: tax = 160.00   │
└──────────┬───────────────────┘
           │
           │ INSERT INTO sales
           │ (tax, payment_method,
           │  customer_id, user_id,
           │  company_id, ...)
           │
           ▼
┌──────────────────────────────┐
│  Database Storage            │
│  sales Table (Row)           │
├──────────────────────────────┤
│ 7. Sale Recorded:            │
│    id: 1001                  │
│    company_id: 1             │
│    customer_id: 5            │
│    user_id: 2                │
│    payment_method: "Cash"    │
│    discount: 0               │
│    tax: 160.00 ◄─────────────│ Tax stored!
│    ...                       │
└─────────────────────────────┘
```

## Data Flow: Accessing Tax Configuration Admin Page

```
┌─────────────────────────────────────┐
│  Browser Navigation                 │
│  Admin clicks "Tax Configuration"   │
└──────────────┬──────────────────────┘
               │
               │ router-link to /admin-tax-configuration
               │
               ▼
┌─────────────────────────────────────┐
│  Vue Router (index.js)              │
├─────────────────────────────────────┤
│ 1. Route Guard Checks:              │
│    ✓ requiresAuth: true             │
│    ✓ requiresRole: 'admin'          │
│    ✓ localStorage.getItem('token')  │
└──────────┬────────────────────────────┘
           │
           │ if authorized
           │
           ▼
┌─────────────────────────────────────┐
│  Component Lifecycle                │
│  AdminTaxConfigurationPage.mounted()│
├─────────────────────────────────────┤
│ 2. Component Initialization:        │
│    - fetchTaxConfigs() called       │
│    - loading.list = true            │
└──────────┬────────────────────────────┘
           │
           │ GET /api/tax-configurations
           │ Headers: 
           │   Authorization: Bearer {token}
           │
           ▼
┌─────────────────────────────────────┐
│  Backend Route Handler              │
│  TaxConfigurationController::index()│
├─────────────────────────────────────┤
│ 3. Request Processing:              │
│    - auth()->user() extracted       │
│    - company_id = user.company_id   │
│    - Query conditions:              │
│      where('company_id', $company)  │
│      orderBy('is_default', 'desc')  │
│      orderBy('name')                │
└──────────┬────────────────────────────┘
           │
           │ SELECT * FROM 
           │ tax_configurations
           │ WHERE company_id = ?
           │
           ▼
┌─────────────────────────────────────┐
│  Database Query                     │
│  tax_configurations (Company 1)     │
├─────────────────────────────────────┤
│ 4. Results Returned:                │
│   Row 1: Standard VAT (default)     │
│   Row 2: Zero-Rated                 │
│   Row 3: Exempt                     │
└──────────┬────────────────────────────┘
           │
           │ Return JSON response
           │ 200 OK
           │ { data: [...] }
           │
           ▼
┌─────────────────────────────────────┐
│  Frontend Data Update               │
│  Component.data.taxConfigs = [...]  │
├─────────────────────────────────────┤
│ 5. State Update:                    │
│    - loading.list = false           │
│    - taxConfigs = response.data      │
│    - Computed: activeTaxConfigs     │
│    - Trigger re-render              │
└──────────┬────────────────────────────┘
           │
           │ v-for loop on taxConfigs
           │ Render grid of cards
           │
           ▼
┌─────────────────────────────────────┐
│  User Sees Admin Dashboard          │
│  ✓ All tax configurations listed    │
│  ✓ Can edit, delete, set default    │
│  ✓ Can create new configurations    │
│  ✓ Can test calculations            │
└─────────────────────────────────────┘
```

## Tax Calculation Formula Reference

### Exclusive Tax (is_inclusive = false)
```
Net Amount = Base Price
Tax Amount = Base Price × Rate / 100
Total Amount = Base Price + Tax Amount

Example: Base 1000, Rate 16%
Tax = 1000 × 0.16 = 160
Total = 1000 + 160 = 1160
```

### Inclusive Tax (is_inclusive = true)
```
Total Amount = Price including tax (customer quoted price)
Net Amount = Total Amount / (1 + Rate / 100)
Tax Amount = Total Amount - Net Amount

Example: Total 1160, Rate 16%
Net = 1160 / 1.16 = 1000
Tax = 1160 - 1000 = 160
```

## Database Relationships Diagram

```
companies
├─ id (PK)
├─ name
└─ (other fields)
   │
   │ 1:N
   ▼
tax_configurations
├─ id (PK)
├─ company_id (FK) ──► companies.id
├─ name
├─ rate
├─ is_inclusive
├─ is_default
└─ is_active
   │
   │ 1:N
   ▼
products
├─ id (PK)
├─ tax_configuration_id (FK) ──► tax_configurations.id
├─ tax_category
├─ tax_rate
└─ (other fields)

sales
├─ id (PK)
├─ company_id (FK) ──► companies.id
├─ customer_id (FK)
├─ user_id (FK)
├─ tax ◄─── stored tax amount
├─ payment_method
└─ (other fields)

purchases
├─ id (PK)
├─ tax_rate ◄─── stored tax rate
└─ (other fields)
```

## API Request/Response Cycle

```
╔═══════════════════════════════════════════════════════════════╗
║                    API REQUEST CYCLE                          ║
╠═══════════════════════════════════════════════════════════════╣
║                                                               ║
║  CLIENT                          SERVER                       ║
║  ──────                          ──────                       ║
║                                                               ║
║  Request                                                      ║
║  ────────────────────────────────────────────────────────►   ║
║  GET /api/tax-configurations                                 ║
║  Headers:                                                     ║
║  - Authorization: Bearer token                               ║
║  - Accept: application/json                                  ║
║                                                               ║
║                    Route Guard                               ║
║                    ◄─────────────────────────────────────    ║
║                    - Check token valid                       ║
║                    - Check admin role                        ║
║                    - Check company isolation                 ║
║                                                               ║
║                    Process Request                           ║
║                    ◄─────────────────────────────────────    ║
║                    - Extract company_id from user            ║
║                    - Query database                          ║
║                    - Format response                         ║
║                                                               ║
║  Response                                                     ║
║  ◄────────────────────────────────────────────────────────   ║
║  200 OK                                                       ║
║  Content-Type: application/json                              ║
║  {                                                            ║
║    "data": [                                                  ║
║      {                                                        ║
║        "id": 1,                                              ║
║        "name": "Standard VAT",                               ║
║        "rate": 16.00,                                        ║
║        ...                                                    ║
║      }                                                        ║
║    ]                                                          ║
║  }                                                            ║
║                                                               ║
║  Update Frontend                                              ║
║  ────────────────────────────────────────────────────────►   ║
║  - Parse response                                            ║
║  - Update component.data.taxConfigs                          ║
║  - Re-render Vue component                                   ║
║  - Display tax configurations list                           ║
║                                                               ║
╚═══════════════════════════════════════════════════════════════╝
```

## Security Flow

```
┌───────────────────────────────────────────────────────────┐
│                    SECURITY LAYERS                        │
├───────────────────────────────────────────────────────────┤
│                                                           │
│  Layer 1: Authentication (Laravel Sanctum)               │
│  ├─ User logs in with email/password                     │
│  ├─ Server generates Bearer token                        │
│  ├─ Token stored in localStorage                         │
│  └─ Sent in Authorization header on requests             │
│                                                           │
│  Layer 2: Authorization (Role Check)                     │
│  ├─ Check user.role_id = admin (ID 2)                    │
│  ├─ Enforced at Router level (Vue)                       │
│  ├─ Enforced at Route Middleware (Laravel)               │
│  └─ Enforced at Controller level                         │
│                                                           │
│  Layer 3: Multi-tenancy (Company Isolation)              │
│  ├─ Extract company_id from auth()->user()               │
│  ├─ Add WHERE company_id = $user->company_id to all      │
│    queries                                               │
│  ├─ Prevent cross-company data access                    │
│  └─ Validate company_id in request data                  │
│                                                           │
│  Layer 4: Validation                                     │
│  ├─ Frontend: Validate form inputs before sending        │
│  ├─ Backend: Re-validate all incoming data               │
│  ├─ Check: Data types, ranges, formats                   │
│  ├─ Check: Foreign key constraints                       │
│  └─ Return 422 if validation fails                       │
│                                                           │
│  Layer 5: CORS & CSRF Protection                         │
│  ├─ CORS headers configured in config/cors.php           │
│  ├─ Sanctum provides CSRF token for state-changing ops   │
│  ├─ Same-site cookies policy                             │
│  └─ Origin verification                                  │
│                                                           │
└───────────────────────────────────────────────────────────┘
```

## Environment & Dependencies

```
Backend Stack:
├─ Laravel 11
├─ PHP 8.2+
├─ MySQL 8.0+
├─ Eloquent ORM
├─ Laravel Sanctum (Authentication)
└─ Artisan CLI

Frontend Stack:
├─ Vue 3
├─ Vue Router 4
├─ Axios (HTTP Client)
├─ ES6+ JavaScript
├─ Scoped CSS/SCSS
└─ Font Awesome Icons

Database:
├─ MySQL Tables
├─ Migrations (Artisan)
├─ Seeders (TaxConfigurationSeeder)
└─ Relationships & Keys
```

---

**Last Updated:** January 27, 2026
**Diagram Version:** 1.0
**Accuracy:** ✅ Current
