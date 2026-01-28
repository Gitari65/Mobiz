# Product Pricing by Price Groups - Implementation

## Overview
Added the ability to set **custom prices for each price group** (retail, stockist, superstockist) when creating or editing products. This enables flexible, multi-tier pricing strategies.

## Database Changes

### New Table: `product_prices`
```sql
CREATE TABLE product_prices (
    id BIGINT PRIMARY KEY,
    product_id BIGINT NOT NULL,
    price_group_id BIGINT NOT NULL,
    price DECIMAL(12,2),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    UNIQUE(product_id, price_group_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (price_group_id) REFERENCES price_groups(id) ON DELETE CASCADE
)
```

## Backend Implementation

### Models
1. **ProductPrice** ([Server/app/Models/ProductPrice.php](../Server/app/Models/ProductPrice.php))
   - Fillable: product_id, price_group_id, price
   - Relationships: product(), priceGroup()
   - Casts: price (decimal:2)

2. **Product** - Updated
   - Added `prices()` hasMany relationship to ProductPrice

### Controller Updates
**ProductController** ([Server/app/Http/Controllers/ProductController.php](../Server/app/Http/Controllers/ProductController.php))

**store() method:**
- Added validation: `'prices' => 'nullable|array', 'prices.*' => 'numeric|min:0'`
- After creating product, saves prices for each price group
- Eager loads `prices.priceGroup` relationship in response

**update() method:**
- Same validation for prices
- Uses `updateOrCreate` to update or create price group prices
- Eager loads `prices.priceGroup` relationship in response

## Frontend Implementation

### ProductsPage.vue Updates

**Data Properties Added:**
- `priceGroups: []` - Array of all available price groups
- `singleProductForm.prices: {}` - Object to store prices keyed by price_group_id

**Methods Added:**
- `fetchPriceGroups()` - Fetches all price groups on component mount
- Updated `mounted()` to call `fetchPriceGroups()`

**UI Changes:**
- Added "Price Group Pricing" section in product form
- Shows a price input for each price group
- Each input displays:
  - Price group name
  - Discount percentage (e.g., "10% off base")
  - Currency prefix (Ksh)
  - Placeholder text
- Grid layout responsive (auto-fit on desktop, single column on mobile)

**Data Submission:**
- `saveAllProducts()` includes prices in API request
- Prices object: `{ priceGroupId: price, ... }`

## UI/UX Features

### Price Group Input Card
- Clean, modern design with hover effects
- Light blue background (#f7fafc)
- Border color changes on hover
- Shows discount information from price group
- Currency symbol (Ksh) displayed in input

### Pricing Section Header
- Icon: Tag icon (📍)
- Title: "Price Group Pricing"
- Subtitle: "Set custom prices for different customer tiers (optional)"
- Border separator from other form fields

### Responsive Design
- Desktop: Auto-fit grid with min 280px columns
- Mobile: Single column layout
- All inputs properly styled and validated

## How It Works

### Creating a Product with Group Pricing
1. User fills in base product info (name, base price, stock, etc.)
2. Scrolls to "Price Group Pricing" section
3. Enters optional custom prices for each group:
   - **Retail**: Base selling price (optional override)
   - **Stockist**: Wholesale price (optional override)
   - **Superstockist**: Premium wholesale price (optional override)
4. Submits form - API saves all prices in `product_prices` table

### Pricing Mechanics
**Option A: Use System Discounts (if no override set)**
- Base price: Ksh 1000
- Retail: Ksh 1000 (0% discount)
- Stockist: Ksh 900 (10% discount applied)
- Superstockist: Ksh 850 (15% discount applied)

**Option B: Custom Prices (if override provided)**
- Retail: Ksh 1200 (custom)
- Stockist: Ksh 800 (custom override)
- Superstockist: Ksh 700 (custom override)

### Retrieving Prices
Products are returned with nested structure:
```json
{
  "id": 1,
  "name": "Product Name",
  "price": 1000,
  "prices": [
    {
      "id": 1,
      "product_id": 1,
      "price_group_id": 1,
      "price": "1000.00",
      "priceGroup": {
        "id": 1,
        "name": "Retail",
        "discount_percentage": "0.00"
      }
    },
    ...
  ]
}
```

## Future Enhancements
1. **POS Integration**: Apply group prices at checkout based on customer's price group
2. **Price Calculation**: Auto-calculate prices based on discount percentage
3. **Bulk Price Updates**: Update prices for multiple products at once
4. **Price History**: Track price changes over time
5. **Price Rules**: Set markup/margin percentages per group
6. **Discount Validation**: Prevent pricing below cost price

## API Endpoints

### Create Product with Prices
```
POST /products
{
  "name": "Product Name",
  "price": 1000,
  "stock_quantity": 50,
  "warehouse_id": 1,
  "prices": {
    "1": 1000,    // Retail
    "2": 800,     // Stockist
    "3": 700      // Superstockist
  }
}
```

### Update Product Prices
```
PUT /products/{id}
{
  "price": 1000,
  "prices": {
    "1": 1200,
    "2": 900,
    "3": 800
  }
}
```

## Database Migration
**File**: `2026_01_24_212015_create_product_prices_table.php`
**Status**: ✅ Migrated successfully
