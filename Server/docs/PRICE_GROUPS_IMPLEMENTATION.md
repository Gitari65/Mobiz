# Price Groups Implementation

## Overview
Price groups allow businesses to define different pricing tiers for customers (retail, wholesale, etc.) with automatic discount percentages applied to base product prices.

## Database Structure

### Tables Created
1. **price_groups**
   - `id`: Primary key
   - `name`: Display name (e.g., "Retail", "Stockist")
   - `code`: Unique identifier (e.g., "RETAIL", "STOCKIST")
   - `description`: Description of the price group
   - `discount_percentage`: Percentage discount from base price (decimal 5,2)
   - `is_system`: Boolean flag for system-protected groups
   - `company_id`: Foreign key to companies (nullable for system groups)
   - `timestamps`

2. **customers** (modified)
   - Added `price_group_id`: Foreign key to price_groups (nullable, onDelete set null)

## System Price Groups
Three default price groups are seeded automatically:
- **Retail** (0% discount) - Standard retail pricing
- **Stockist** (10% discount) - Wholesale pricing for stockist partners
- **Superstockist** (15% discount) - Premium wholesale pricing

## Backend Implementation

### Models
- **PriceGroup** ([Server/app/Models/PriceGroup.php](../app/Models/PriceGroup.php))
  - Fillable: name, code, description, discount_percentage, is_system, company_id
  - Relationships: company(), customers()
  - Casts: discount_percentage (decimal:2), is_system (boolean)

- **Customer** ([Server/app/Models/Customer.php](../app/Models/Customer.php))
  - Added price_group_id to fillable
  - Added priceGroup() relationship

### Controllers
- **PriceGroupController** ([Server/app/Http/Controllers/PriceGroupController.php](../app/Http/Controllers/PriceGroupController.php))
  - `index()`: List all price groups (system + company-specific)
  - `store()`: Create custom price group
  - `show()`: Get price group details with customers
  - `update()`: Update custom price group (system groups protected)
  - `destroy()`: Delete custom price group (system groups protected, checks customer assignments)

- **CustomerController** ([Server/app/Http/Controllers/CustomerController.php](../app/Http/Controllers/CustomerController.php))
  - Updated validation to include `price_group_id`
  - All queries now eager load `priceGroup` relationship
  - Batch import supports price_group_id

### Routes
```php
GET    /price-groups          // List all price groups
POST   /price-groups          // Create new price group
GET    /price-groups/{id}     // Get price group details
PUT    /price-groups/{id}     // Update price group
DELETE /price-groups/{id}     // Delete price group
```

### Seeder
- **PriceGroupSeeder** ([Server/database/seeders/PriceGroupSeeder.php](../database/seeders/PriceGroupSeeder.php))
  - Creates 3 system price groups using `firstOrCreate` (idempotent)

## Frontend Implementation

### ManageUsersPage.vue Updates
- Added `priceGroups` ref array
- Added `fetchPriceGroups()` method to load price groups
- Updated customer table to display price group badge
- Added price group dropdown in customer form (create/edit)
- Updated `customerForm` to include `price_group_id`
- Updated `editCustomer()` to properly extract nested price_group.id
- Added badge CSS styling for price group display

## Features

### Protection Mechanisms
1. **System Price Groups**: Cannot be edited or deleted
2. **Customer Assignment Check**: Cannot delete price group with assigned customers
3. **Company Scoping**: Companies can only manage their own custom price groups
4. **Set Null on Delete**: If price group is deleted, customers revert to default pricing

### Pricing Logic
- Customers without a price group use default (base) pricing
- Customers with a price group receive the specified discount percentage
- Discount percentage is stored in the price group (e.g., 10.00 = 10% off)

## Usage Example

### Creating a Custom Price Group
```javascript
POST /price-groups
{
  "name": "VIP Customers",
  "code": "VIP",
  "description": "Premium customers with special pricing",
  "discount_percentage": 20.00
}
```

### Assigning Price Group to Customer
```javascript
POST /customers
{
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "+254712345678",
  "price_group_id": 2  // Stockist group
}
```

### Response Data Structure
```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john@example.com",
  "price_group": {
    "id": 2,
    "name": "Stockist",
    "code": "STOCKIST",
    "discount_percentage": "10.00"
  }
}
```

## Migration History
1. `2026_01_24_210327_create_price_groups_table.php` - Created price_groups table
2. `2026_01_24_210437_add_price_group_id_to_customers_table.php` - Added price_group_id to customers

## Future Enhancements
- Apply price group discounts in POS/cart calculations
- Price group-specific product pricing overrides
- Time-based price group assignments
- Bulk customer price group updates
- Price group usage analytics
