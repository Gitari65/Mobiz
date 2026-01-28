# Empties/Returnables Management System

## Overview
The Empties/Returnables system allows you to link returnable containers (bottles, crates, cylinders, etc.) to products. When selling the main product, linked empties are automatically included in the transaction with their deposit amounts.

## Use Cases

### Common Scenarios
1. **Beverages**: Soda bottles, crates
   - 1 crate of soda = 1 crate (returnable) + 24 bottles (returnables)
   
2. **Gas/LPG**: Cylinders
   - 1 gas refill = 1 cylinder (returnable)
   
3. **Dairy Products**: Milk bottles, jerry cans
   - 1 milk product = 1 bottle/jerry can (returnable)

## Database Schema

### `product_empties` Table
```sql
- id: Primary key
- product_id: Foreign key to products table (the main product)
- empty_product_id: Foreign key to products table (the empty/returnable)
- quantity: How many empties per unit of main product (e.g., 24 bottles per crate)
- deposit_amount: Deposit charged for the empty (refundable)
- is_active: Whether this link is currently active
- timestamps: created_at, updated_at
```

## API Endpoints

### 1. Get Empties for a Product
```http
GET /api/products/{product_id}/empties
```

**Response:**
```json
{
  "data": [
    {
      "id": 5,
      "name": "Empty Soda Bottle",
      "sku": "EMP-BOTTLE-001",
      "price": 10.00,
      "quantity": 24,
      "deposit_amount": 10.00,
      "is_active": true
    }
  ]
}
```

### 2. Get Available Empties
```http
GET /api/products/{product_id}/available-empties
```
Returns all products from the same company that can be linked as empties (excludes the current product).

### 3. Link Empty to Product
```http
POST /api/products/{product_id}/empties
```

**Request Body:**
```json
{
  "empty_product_id": 5,
  "quantity": 24,
  "deposit_amount": 10.00
}
```

**Response:**
```json
{
  "message": "Empty linked successfully",
  "data": [...]
}
```

### 4. Update Empty Link
```http
PUT /api/products/{product_id}/empties/{empty_id}
```

**Request Body:**
```json
{
  "quantity": 12,
  "deposit_amount": 15.00,
  "is_active": true
}
```

### 5. Unlink Empty
```http
DELETE /api/products/{product_id}/empties/{empty_id}
```

## Frontend Integration

### EmptiesModal Component
Located at: `client/src/components/EmptiesModal.vue`

**Usage in ProductsPage:**
```vue
<EmptiesModal
  :isOpen="showEmptiesModalFlag"
  :product="selectedProductForEmpties"
  @close="closeEmptiesModal"
  @success="handleEmptiesSuccess"
  @error="handleEmptiesError"
/>
```

**Features:**
- Add new empty links
- Edit existing links (quantity, deposit)
- Delete/unlink empties
- View all linked empties
- Inline editing
- Real-time validation

### Opening the Modal
Click the recycle icon (🔄) on any product card in the Products page.

## Sales Integration

### Automatic Empties Inclusion
When a sale is created, the system automatically:

1. **Loads product empties** - Retrieves all active empties linked to each sold product
2. **Calculates quantities** - Multiplies empty quantity by product quantity
   - Example: Selling 2 crates with 24 bottles each = 2 crates + 48 bottles
3. **Adds to sale items** - Includes empties as separate line items with deposit amounts
4. **Updates totals** - Adds deposit amounts to the total sale value

### Example Transaction Flow

**Customer buys:**
- 2x Crate of Soda @ KES 1,200 each

**System automatically adds:**
- 2x Crate (empty) @ KES 100 deposit each = KES 200
- 48x Bottle (empty) @ KES 10 deposit each = KES 480

**Total:**
- Products: KES 2,400
- Deposits: KES 680
- **Grand Total: KES 3,080**

### Stock Management
- **Main products**: Stock is reduced
- **Empties with deposit only**: Stock is NOT reduced (they're tracked separately)
- **Empties with price > 0**: Stock is reduced

## Product Model

### Relationships Added

```php
// Get all empties linked to this product
public function empties()
{
    return $this->belongsToMany(Product::class, 'product_empties', 'product_id', 'empty_product_id')
        ->withPivot('quantity', 'deposit_amount', 'is_active')
        ->withTimestamps()
        ->where('product_empties.is_active', true);
}

// Get all products that use this as an empty
public function parentProducts()
{
    return $this->belongsToMany(Product::class, 'product_empties', 'empty_product_id', 'product_id')
        ->withPivot('quantity', 'deposit_amount', 'is_active')
        ->withTimestamps();
}
```

## Setup Instructions

### 1. Run Migration
```bash
php artisan migrate
```

This creates the `product_empties` table.

### 2. Create Empty Products
First, create products that will serve as empties:
- Empty Bottle
- Empty Crate
- Empty Cylinder
etc.

### 3. Link Empties to Main Products
1. Go to Products page
2. Click the recycle icon (🔄) on a product
3. Select an empty from the dropdown
4. Set quantity per unit
5. Set deposit amount
6. Click "Link"

### 4. Test Sales Flow
Create a sale with a product that has linked empties and verify:
- Empties appear in the sale items
- Deposits are added to the total
- Stock is properly managed

## Best Practices

### 1. Product Setup
- Create empty products with descriptive names (e.g., "Empty 500ml Bottle")
- Use clear SKUs for tracking (e.g., "EMP-BOTTLE-500ML")
- Set deposit amounts accurately

### 2. Quantity Configuration
- For cases/crates: Set quantity to number of units inside
- For single items: Set quantity to 1
- For bundles: Calculate total empties across all items

### 3. Deposit Amounts
- Set realistic deposit amounts that customers will pay
- Consider market rates for similar empties
- Update deposits when costs change

### 4. Stock Management
- Keep empty products in inventory
- Track returns separately
- Reconcile empties periodically

## Troubleshooting

### Empties not appearing in sales
**Check:**
- Is the empty link active? (`is_active = true`)
- Does the product have empties linked?
- Is the frontend calling the correct API endpoints?

### Incorrect quantities
**Verify:**
- Quantity multiplier in `product_empties` table
- Sale item calculation logic
- Frontend quantity inputs

### Stock issues
**Review:**
- Stock reduction logic in SaleController
- Whether empty should reduce stock or not
- Manual stock adjustments for empties

## Future Enhancements

### Potential Features
1. **Returns Management**
   - Track empty returns
   - Refund deposit amounts
   - Update empty stock

2. **Deposit Reconciliation**
   - Report on outstanding deposits
   - Track deposit liabilities
   - Automated refund processing

3. **Multi-level Empties**
   - Empties that have their own empties
   - Nested returnable structures

4. **Damaged Empties**
   - Track damaged/non-returnable empties
   - Charge customers for damaged items
   - Write-off damaged stock

## Support

For issues or questions:
1. Check this documentation
2. Review API endpoint responses
3. Check browser console for errors
4. Verify database records in `product_empties` table
5. Test with simple scenarios first

---

**Version:** 1.0  
**Last Updated:** January 26, 2026  
**Author:** Mobiz POS Development Team
