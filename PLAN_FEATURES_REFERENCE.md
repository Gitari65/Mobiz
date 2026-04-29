# Plan Features Checklist - Complete Reference

## Overview
This document provides a comprehensive list of all available features/functionalities that can be assigned to subscription plans in the system. These features control what capabilities each company has access to.

---

## Sales & Transactions

### 1. **sales** - Sales Management
- **Description**: Process and record sales transactions
- **Key Functionality**: 
  - Point-of-sale (POS) operations
  - Create and finalize sales orders
  - Process customer purchases
  - Generate sales receipts
- **Route**: `/api/sales`, `/sales` endpoints

### 2. **mpesa** - M-Pesa Integration  
- **Description**: Accept M-Pesa mobile money payments
- **Key Functionality**:
  - Integrate with M-Pesa payment gateway
  - Process mobile money payments
  - Track M-Pesa transactions
  - Payment reconciliation for mobile payments
- **Route**: Payment gateway integration endpoints

### 3. **credit** - Credit System
- **Description**: Manage customer credit and debt
- **Key Functionality**:
  - Extend credit to customers
  - Track customer debt
  - Monitor credit limits
  - Generate credit statements
  - Credit collection management
- **Route**: `/api/credits`, credit tracking endpoints

### 4. **promotions** - Promotions
- **Description**: Create and manage sales promotions
- **Key Functionality**:
  - Create discount promotions
  - Apply discounts to products/orders
  - Set promotion rules (percentage, fixed amount, buy-one-get-one, etc.)
  - Time-based promotions
  - Track promotion effectiveness
- **Controller**: `PromotionController`

---

## Inventory & Stock

### 5. **inventory** - Inventory Management
- **Description**: Track stock levels and movements
- **Key Functionality**:
  - Monitor product inventory levels
  - Track stock movements (in/out)
  - Set reorder points and alerts
  - View inventory history
  - Stock valuation methods
- **Controller**: `ProductController`, inventory-related endpoints
- **Route**: `/products`, inventory endpoints

### 6. **purchases** - Purchase Orders
- **Description**: Create and manage purchase orders
- **Key Functionality**:
  - Create purchase orders from suppliers
  - Track PO status
  - Receive goods against POs
  - Manage supplier invoices
  - Purchase history and reporting
- **Controller**: `PurchaseController`
- **Route**: `/purchases` endpoints

### 7. **warehouse** - Warehouse Management
- **Description**: Manage multiple warehouses
- **Key Functionality**:
  - Create and manage multiple warehouse locations
  - Assign products to warehouses
  - Track inventory per warehouse
  - Set warehouse-specific pricing/rules
- **Controller**: `WarehouseController`
- **Route**: `/warehouses` endpoints

### 8. **stock_transfers** - Stock Transfers
- **Description**: Transfer stock between locations
- **Key Functionality**:
  - Transfer inventory between warehouses
  - Track transfer history
  - Manage inter-warehouse movements
  - Stock reconciliation across locations
- **Route**: Stock transfer endpoints

---

## Customers & Suppliers

### 9. **customer_management** - Customer Management
- **Description**: Manage customer profiles and history
- **Key Functionality**:
  - Create and maintain customer database
  - View customer purchase history
  - Manage customer contact information
  - Track customer segmentation
  - Customer loyalty tracking
- **Controller**: `CustomerController`
- **Route**: `/customers` endpoints

### 10. **suppliers** - Supplier Management
- **Description**: Manage supplier information
- **Key Functionality**:
  - Create and maintain supplier database
  - Track supplier contact details
  - Manage supplier payment terms
  - View purchase history with suppliers
  - Supplier performance metrics
- **Controller**: `SupplierController`
- **Route**: `/suppliers` endpoints

### 11. **sms** - SMS Notifications
- **Description**: Send SMS to customers and suppliers
- **Key Functionality**:
  - Send SMS order confirmations
  - Customer payment reminders
  - Stock alerts to suppliers
  - Promotional SMS
  - SMS delivery tracking
- **Route**: SMS service integration endpoints

---

## Financial & Tax

### 12. **tax_configuration** - Tax Configuration
- **Description**: Configure and manage tax settings
- **Key Functionality**:
  - Set up tax rates and categories
  - Configure tax application rules
  - Manage tax exemptions
  - Tax reporting and compliance
  - Multi-tax support (VAT, GST, etc.)
- **Controller**: `TaxConfigurationController`
- **Route**: `/tax-configuration` endpoints

### 13. **expenses** - Expense Tracking
- **Description**: Record and track business expenses
- **Key Functionality**:
  - Create expense records
  - Categorize expenses
  - Track expense approvals
  - Generate expense reports
  - Expense reconciliation with accounts
- **Controller**: `ExpenseController`
- **Route**: `/expenses` endpoints

### 14. **invoicing** - Invoicing
- **Description**: Create and send professional invoices
- **Key Functionality**:
  - Generate customer invoices
  - Invoice customization
  - Send invoices via email
  - Track invoice payment status
  - Invoice history and re-sending
- **Controller**: `InvoiceController`
- **Route**: `/invoices` endpoints

### 15. **price_groups** - Price Groups
- **Description**: Manage customer-specific pricing tiers
- **Key Functionality**:
  - Create customer price groups/tiers
  - Set tier-specific pricing
  - Automatic price application by customer group
  - Bulk pricing management
  - Volume-based discounts
- **Controller**: `PriceGroupController`
- **Route**: `/price-groups` endpoints

---

## Reporting & Analytics

### 16. **reports** - Reports & Analytics
- **Description**: Generate sales, inventory, and financial reports
- **Key Functionality**:
  - Sales reports (daily, weekly, monthly)
  - Inventory valuation reports
  - Financial summaries
  - Customer sales performance
  - Profitability analysis
- **Controller**: `ReportController`, `DashboardController`
- **Route**: `/reports`, `/dashboard` endpoints

### 17. **data_export** - Data Export
- **Description**: Export data to CSV/Excel
- **Key Functionality**:
  - Export sales data
  - Export inventory lists
  - Export customer records
  - Export financial statements
  - Scheduled export jobs
- **Controller**: `DataExportController`
- **Route**: `/exports` endpoints

### 18. **audit_logs** - Audit Logs
- **Description**: View transaction and user activity logs
- **Key Functionality**:
  - Track all system activities
  - User action history
  - Transaction logs
  - Data modification audit trail
  - Compliance reporting
- **Controller**: `AuditLogController`
- **Route**: `/audit-logs` endpoints

---

## System & Administration

### 19. **user_management** - User Management
- **Description**: Manage users and assign roles
- **Key Functionality**:
  - Create and manage user accounts
  - Assign roles (Cashier, Admin, etc.)
  - Manage user permissions
  - Track user activity
  - User onboarding and offboarding
- **Controller**: `UserManagementController`
- **Route**: `/users` endpoints

### 20. **printer_config** - Printer Configuration
- **Description**: Configure receipt printer settings
- **Key Functionality**:
  - Configure receipt printer connection
  - Set paper size and format
  - Customize receipt layout
  - Test print functionality
  - Printer device management
- **Controller**: `PrinterSettingsController`
- **Route**: `/printer-settings` endpoints

### 21. **returns** - Product Returns
- **Description**: Process and manage product returns
- **Key Functionality**:
  - Create return orders
  - Process customer refunds
  - Track return reasons
  - Manage restocking procedures
  - Return authorization workflow
- **Controller**: `ReturnController`
- **Route**: `/returns` endpoints

### 22. **advanced_settings** - Advanced Settings
- **Description**: Access advanced system configuration
- **Key Functionality**:
  - Configure business rules
  - System-wide settings
  - Integration configurations
  - Advanced reporting options
  - Custom workflows

---

## Feature Categories Summary

| Category | Count | Features |
|----------|-------|----------|
| Sales & Transactions | 4 | sales, mpesa, credit, promotions |
| Inventory & Stock | 4 | inventory, purchases, warehouse, stock_transfers |
| Customers & Suppliers | 3 | customer_management, suppliers, sms |
| Financial & Tax | 4 | tax_configuration, expenses, invoicing, price_groups |
| Reporting & Analytics | 3 | reports, data_export, audit_logs |
| System & Administration | 4 | user_management, printer_config, returns, advanced_settings |
| **TOTAL** | **22** | **All features** |

---

## Feature Slugs (for API/Database)

Use these exact slugs when creating/editing plans:

```
sales
mpesa
credit
promotions
inventory
purchases
warehouse
stock_transfers
customer_management
suppliers
sms
tax_configuration
expenses
invoicing
price_groups
reports
data_export
audit_logs
user_management
printer_config
returns
advanced_settings
```

---

## Plan Assignment Rules

### Creating a New Plan
1. Go to Superuser → Subscriptions → Plans tab
2. Click "Create Plan"
3. Fill in:
   - **Name**: e.g., "Professional Plus"
   - **Slug**: e.g., "professional_plus" (auto-generated from name)
   - **Price**: Monthly or annual fee in Ksh
   - **Billing Cycle**: monthly, annual, or custom
   - **Description**: Brief description of the plan
4. Select features using the comprehensive checklist
5. Mark as "Active" to make visible to admin customers
6. Click "Create"

### Editing a Plan
1. Click "Edit" on the desired plan card
2. Update any fields
3. Modify features using the checklist (check/uncheck as needed)
4. Click "Update"
5. Changes apply to new subscriptions; existing subscriptions retain their current features

### Plan Best Practices

**Starter Plan** - Basic features for small businesses
- Sales
- Inventory
- Customer Management
- Reports

**Professional Plan** - Intermediate features
- All Starter features +
- M-Pesa Integration
- Promotions
- Purchases
- Warehouse
- Price Groups
- SMS

**Enterprise Plan** - Full feature set
- All Professional features +
- Credit System
- Advanced Reporting
- Data Export
- Audit Logs
- User Management
- Advanced Settings
- Printer Configuration
- Returns

---

## Feature Availability Status

All 22 features are currently **available** and can be assigned to plans. The system is designed to be modular, allowing fine-grained control over which capabilities each subscription tier receives.

### Feature Dependencies
Some features may depend on others:
- **Purchases** relies on **Inventory** for stock management
- **Price Groups** works best with **Customer Management**
- **M-Pesa** requires prior setup (payment gateway configuration)
- **Tax Configuration** affects **Sales** and **Invoicing**

---

## Technical Notes

### How Features Are Stored
- Features are stored as an array in the Plan model
- Example: `['sales', 'inventory', 'customer_management', 'reports']`
- Features are stored as strings (slugs) for consistency

### How Features Are Used
1. When a company subscribes to a plan, they get all features in that plan's array
2. Frontend components check for feature availability using the subscription's feature array
3. Admins see feature restrictions based on their company's subscription
4. Sidebar menu items can be filtered based on available features

### Feature Validation
- Features must match the predefined list above
- Invalid feature names will be silently ignored
- Duplicate features are automatically prevented
- Feature names are case-sensitive (always lowercase with underscores)

---

## Support & Updates

To add new features to the system:
1. Ensure the feature backend functionality is implemented
2. Add the feature slug to this documentation
3. Update the checklist in SubscriptionsPage.vue
4. Add feature display name to `formatFeatureName()` function
5. Test feature assignment and functionality

