# Product Pricing Mechanics - Examples

## Example 1: Product with No Custom Group Prices

### Create Request
```json
POST /products
{
  "name": "Milk 1L",
  "category": "Dairy",
  "warehouse_id": 1,
  "cost_price": 60,
  "price": 100,
  "stock_quantity": 500,
  "description": "Fresh milk 1 liter bottle"
}
```

### Response
```json
{
  "message": "Product created successfully",
  "product": {
    "id": 5,
    "name": "Milk 1L",
    "price": 100,
    "stock_quantity": 500,
    "warehouse_id": 1,
    "prices": []
  }
}
```

### Pricing Behavior (at POS)
- **Retail** (0% discount): Ksh 100
- **Stockist** (10% discount): Ksh 90
- **Superstockist** (15% discount): Ksh 85

---

## Example 2: Product with Custom Group Prices

### Create Request
```json
POST /products
{
  "name": "Premium Butter 500g",
  "category": "Dairy",
  "warehouse_id": 1,
  "cost_price": 250,
  "price": 450,
  "stock_quantity": 200,
  "description": "Imported butter 500g",
  "prices": {
    "1": 450,      // Retail custom price
    "2": 350,      // Stockist custom override (not using 10% discount)
    "3": 280       // Superstockist custom override
  }
}
```

### Response
```json
{
  "message": "Product created successfully",
  "product": {
    "id": 6,
    "name": "Premium Butter 500g",
    "price": 450,
    "stock_quantity": 200,
    "prices": [
      {
        "id": 1,
        "product_id": 6,
        "price_group_id": 1,
        "price": "450.00",
        "priceGroup": {
          "id": 1,
          "name": "Retail",
          "code": "RETAIL",
          "discount_percentage": "0.00"
        }
      },
      {
        "id": 2,
        "product_id": 6,
        "price_group_id": 2,
        "price": "350.00",
        "priceGroup": {
          "id": 2,
          "name": "Stockist",
          "code": "STOCKIST",
          "discount_percentage": "10.00"
        }
      },
      {
        "id": 3,
        "product_id": 6,
        "price_group_id": 3,
        "price": "280.00",
        "priceGroup": {
          "id": 3,
          "name": "Superstockist",
          "code": "SUPERSTOCKIST",
          "discount_percentage": "15.00"
        }
      }
    ]
  }
}
```

### Pricing Behavior (at POS)
- **Retail**: Ksh 450 (custom price overrides 0% discount)
- **Stockist**: Ksh 350 (custom price - 22% off base)
- **Superstockist**: Ksh 280 (custom price - 38% off base)

---

## Example 3: Update Product with Different Prices

### Update Request
```json
PUT /products/6
{
  "prices": {
    "1": 480,      // Retail increased due to demand
    "2": 360,      // Stockist adjusted
    "3": 300       // Superstockist adjusted
  }
}
```

### Response
```json
{
  "message": "Product updated successfully",
  "product": {
    "id": 6,
    "name": "Premium Butter 500g",
    "price": 450,
    "prices": [
      {
        "id": 1,
        "price_group_id": 1,
        "price": "480.00",
        "priceGroup": { "id": 1, "name": "Retail" }
      },
      {
        "id": 2,
        "price_group_id": 2,
        "price": "360.00",
        "priceGroup": { "id": 2, "name": "Stockist" }
      },
      {
        "id": 3,
        "price_group_id": 3,
        "price": "300.00",
        "priceGroup": { "id": 3, "name": "Superstockist" }
      }
    ]
  }
}
```

---

## Frontend Form Example

### UI Display for Example 2 Product

```
Price Group Pricing
Set custom prices for different customer tiers (optional)

┌─────────────────────────┐
│ Retail (0% off base)    │
│ Ksh │ 450               │
└─────────────────────────┘

┌─────────────────────────┐
│ Stockist (10% off base) │
│ Ksh │ 350               │
└─────────────────────────┘

┌──────────────────────────────┐
│ Superstockist (15% off base) │
│ Ksh │ 280                     │
└──────────────────────────────┘
```

---

## Database Structure

### product_prices Table
```sql
SELECT * FROM product_prices WHERE product_id = 6;

+----+-------------+----------------+-------+---------------------+---------------------+
| id | product_id  | price_group_id | price | created_at          | updated_at          |
+----+-------------+----------------+-------+---------------------+---------------------+
| 1  | 6           | 1              | 450   | 2026-01-24 21:30:00 | 2026-01-24 21:35:00 |
| 2  | 6           | 2              | 350   | 2026-01-24 21:30:01 | 2026-01-24 21:35:00 |
| 3  | 6           | 3              | 280   | 2026-01-24 21:30:02 | 2026-01-24 21:35:00 |
+----+-------------+----------------+-------+---------------------+---------------------+
```

---

## Price Retrieval in POS

### Get Product with All Prices
```php
$product = Product::with('prices.priceGroup')->find(6);

// Access price for specific group
$retailPrice = $product->prices->where('price_group_id', 1)->first()?->price;
// Result: 450.00

$stockistPrice = $product->prices->where('price_group_id', 2)->first()?->price;
// Result: 350.00
```

### At Checkout (Future Implementation)
```javascript
// Get customer's price group
const customer = await getCustomer(customerId); // { price_group_id: 2 }

// Get product with prices
const product = await getProduct(productId);

// Find applicable price
const applicablePrice = product.prices
  .find(p => p.price_group_id === customer.price_group_id)
  ?.price || product.price;
  
// Result for Stockist: 350.00
```

---

## Business Logic Rules

### Price Precedence (Recommended)
1. **Use custom group price if set** → highest priority
2. **Fall back to base price with discount calculation** → if no custom price
3. **Use base price** → for default/retail customers

### Markup Strategy
- **Retail**: 100% markup (cost: 225 → price: 450)
- **Stockist**: 40% markup (cost: 225 → price: 350) 
- **Superstockist**: 24% markup (cost: 225 → price: 280)

### Bulk Updates (Future)
```json
PATCH /products/batch-prices
{
  "product_ids": [1, 2, 3],
  "price_adjustments": {
    "1": { "operation": "increase", "value": 10 },     // +10%
    "2": { "operation": "increase", "value": 5 },      // +5%
    "3": { "operation": "set", "value": 500 }          // set to 500
  }
}
```
