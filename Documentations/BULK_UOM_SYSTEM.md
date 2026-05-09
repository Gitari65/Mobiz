# Bulk Purchase & Retail Sale UoM System

## Overview

This system allows products to be purchased in bulk with one UoM (Unit of Measurement) and sold in smaller quantities with a different UoM. For example:
- **Purchase (Bulk)**: 50L containers
- **Sale (Retail)**: 1L or 250ml bottles
- **Conversion**: 1 bulk unit (50L) = 50 retail units (1L)

## Use Cases

### Beverage Business
- Buy: 50L bulk containers
- Sell: 1L bottles or 250ml cups
- Conversion: 1 (50L) = 50 (1L) or 200 (250ml)

### Food/Grain Business
- Buy: 25kg bags
- Sell: 500g or 1kg portions
- Conversion: 1 (25kg) = 50 (500g) or 25 (1kg)

### Cosmetics/Chemicals
- Buy: 5L drums
- Sell: 100ml bottles
- Conversion: 1 (5L) = 50 (100ml)

## Database Schema

### Products Table Additions

```sql
-- New columns added to products table:
- purchase_uom_id (nullable) - UoM for bulk purchasing
- sale_uom_id (nullable) - UoM for retail selling
- conversion_ratio (decimal 10,4) - How many sale units = 1 purchase unit
- track_by_purchase_unit (boolean) - Always true; stock is tracked in purchase units
```

### Sale Items Table Enhancements

```sql
-- New columns added to sale_items table:
- uom_id (nullable) - The UoM used for this specific sale
- quantity (changed to decimal 12,4) - Now supports fractional quantities
```

## Backend Models

### Product Model

**New Relationships:**
```php
$product->purchaseUom()  // BelongsTo UOM - bulk purchase unit
$product->saleUom()      // BelongsTo UOM - retail sale unit
```

**New Helper Methods:**
```php
// Get available quantity in sale units
$product->getAvailableQuantityInSaleUnits()

// Get stock display in sale units
$product->getStockInSaleUnits()

// Deduct stock based on sale units
$product->deductStockBySaleUnit($saleQuantity)

// Add stock in purchase units
$product->addStockByPurchaseUnit($purchaseQuantity)
```

### SaleItem Model

**Updated Fields:**
```php
protected $fillable = [
    'sale_id',
    'product_id',
    'uom_id',        // NEW - UoM used for this sale
    'quantity',      // NOW DECIMAL for fractional quantities
    'unit_price',
    'total_price'
];

protected $casts = [
    'quantity' => 'decimal:4',  // Supports up to 4 decimal places
    'unit_price' => 'decimal:2',
    'total_price' => 'decimal:2',
];
```

## Frontend Features

### Product Creation/Editing (ProductsPage.vue)

When creating or editing a product, three new fields appear:

1. **Purchase UOM** - The bulk unit (e.g., 50L)
2. **Sale UOM** - The retail unit (e.g., 1L or 250ml)
3. **Conversion Ratio** - How many sale units per 1 purchase unit
   - Example: If buying 50L containers and selling 1L bottles, ratio = 50

The form shows a helpful hint:
```
"1 50L = 50 1L"
```

### Product Display (SalesPage.vue)

Products show enhanced information:

```
Product Name
Ksh 150

100 L in stock          ← Shows stock in SALE units, not purchase units
Buy: 50 x 1L = 1 x 50L   ← Shows conversion info (if configured)
```

### Cart Operations

When adding products to cart:
- Available quantity is based on **sale units**, not purchase units
- Cart displays the sale UoM for clarity
- Stock limit enforcement uses sale units

Example:
- Product has 2 bulk units (50L each) in stock
- With conversion ratio 50, customer can buy up to 100 (1L) units
- System tracks this correctly throughout the sale process

## Implementation Flow

### Step 1: Configure Product

1. Go to Products page
2. Create or edit a product
3. Set:
   - Purchase UOM: 50L
   - Sale UOM: 1L
   - Conversion Ratio: 50
4. Save product with initial stock in purchase units
   - Example: Stock = 2 (means 2 × 50L = 100L total = 100 × 1L units available)

### Step 2: Make a Sale

1. Go to Sales page
2. Products display available quantity in sale units
3. Add products to cart (quantity in sale units)
4. At checkout, system converts sale units back to purchase units for storage

### Step 3: Stock Update

When sale is completed:
1. Cart items: 25 units of 1L
2. System calculates: 25 ÷ 50 = 0.5 purchase units
3. Stock reduced by 0.5 (from 2 to 1.5)
4. Remaining inventory: 1.5 × 50L = 75L = 75 × 1L units

## Example Scenarios

### Scenario 1: Juice Business

**Product Setup:**
- Name: Fresh Juice
- Purchase: 50L containers @ Ksh 7,500
- Retail: 1L bottles @ Ksh 200
- Conversion: 50

**Stock Management:**
- Buy: 2 containers = 2 purchase units
- Available for sale: 100 bottles = 100 sale units

**Customer Sale:**
- Customer buys: 10 bottles (10 sale units)
- Stock after: 90 bottles (1.9 purchase units)

### Scenario 2: Grain/Flour Business

**Product Setup:**
- Name: Wheat Flour
- Purchase: 25kg bags @ Ksh 1,200
- Retail: 500g packs @ Ksh 30
- Conversion: 50 (25kg = 50 × 500g)

**Stock Management:**
- Buy: 4 bags = 4 purchase units
- Available: 200 packs = 200 sale units

**Customer Sale:**
- Customer buys: 3 packs (3 sale units)
- Stock after: 197 packs (3.94 purchase units)

## API Integration

### Creating/Updating Product with UoMs

```bash
POST /products
{
  "name": "Fresh Juice",
  "sku": "JUL-001",
  "category": "Beverages",
  "price": 200,
  "cost_price": 150,
  "stock_quantity": 2,           # Purchase units (50L containers)
  "warehouse_id": 1,
  "purchase_uom_id": 5,          # 50L UoM
  "sale_uom_id": 8,              # 1L UoM
  "conversion_ratio": 50         # 1 container = 50 bottles
}
```

### Selling with UoM Tracking

```bash
POST /sales
{
  "items": [
    {
      "product_id": 123,
      "quantity": 25,             # 25 sale units (25 × 1L = 0.5 purchase units)
      "price": 200
    }
  ],
  "customer_id": 456,
  "payment_method": "cash"
}
```

The system automatically:
1. Validates: 25 sale units ≤ available quantity
2. Tracks: In SaleItem as 25 units of 1L (uom_id = 8)
3. Stores: Stock adjustment as -0.5 purchase units (quantity reduction: 2 → 1.5)

## Database Migrations

Two migrations added:

1. **2026_04_21_000001_add_purchase_sale_uoms_to_products.php**
   - Adds purchase_uom_id, sale_uom_id, conversion_ratio, track_by_purchase_unit

2. **2026_04_21_000002_enhance_sale_items_with_uom.php**
   - Adds uom_id to sale_items
   - Changes quantity from integer to decimal(12,4)

## Running Migrations

```bash
cd Server
php artisan migrate
```

## Stock Validation

### During Purchase (Adding to Cart)

```vue
// JavaScript
const availableQty = product.stock_quantity * product.conversion_ratio
// Example: 2 * 50 = 100 sale units available

// Validation: Can't add more than available
if (item.quantity >= availableQty) {
  // Show error: Stock limit reached
}
```

### During Sale Checkout

```php
// PHP (Backend)
foreach ($items as $item) {
    $product = Product::find($item['product_id']);
    $availableInSaleUnits = $product->getAvailableQuantityInSaleUnits();
    
    // Validate
    if ($item['quantity'] > $availableInSaleUnits) {
        throw new Exception("Insufficient stock");
    }
    
    // Deduct stock
    $product->deductStockBySaleUnit($item['quantity']);
}
```

## Reporting & Analytics

### Stock Reporting

Reports now show:
- **Purchase Unit Stock**: Physical inventory (e.g., "2 containers of 50L")
- **Sale Unit Stock**: Available for sales (e.g., "100 bottles of 1L")
- **Value**: Based on cost_price × purchase units

### Sales Reports

Sales transactions show:
- Product name
- Quantity sold (in sale units, e.g., "25 × 1L")
- UoM used for that transaction
- Unit price
- Total amount

## Edge Cases & Considerations

### Fractional Purchase Units
If customer buys: 75 × 1L and stock tracks in 50L containers:
- 75 ÷ 50 = 1.5 purchase units deducted
- Stock becomes: 2 → 0.5 purchase units
- Available units: 0.5 × 50 = 25 bottles remaining

### Multiple Conversions
For future: Support multiple sale UoMs for same product:
- Buy: 50L container
- Sell Options: 1L bottles OR 250ml cups OR 500ml cans
- Each with different conversion ratios

### No Conversion Setup
If sale_uom_id is not set:
- System defaults to standard behavior
- Stock tracked as-is
- Single UoM used throughout

## Benefits

✅ **Flexibility**: Handle bulk and retail separately
✅ **Accuracy**: No rounding errors in stock tracking
✅ **Scalability**: Works for any product/UoM combination
✅ **Transparency**: Clear visibility of bulk vs retail quantities
✅ **User-Friendly**: Automatic conversions, intuitive UI
✅ **Data Integrity**: Consistent stock tracking across operations

## Future Enhancements

1. **Bulk Purchase Suggestions**: Alert when stock falls below X retail units
2. **Reorder Points**: Set minimum in purchase units automatically
3. **Dynamic Pricing**: Different prices for different quantities
4. **UoM Conversion History**: Track all conversions in audit logs
5. **Forecasting**: Predict when to reorder based on sales velocity
