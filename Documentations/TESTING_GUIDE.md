# Testing Guide - Products & Warehouse Management

## New Features Added

### 1. Warehouse Selector on Product Forms
**Location:** Product Create/Edit Forms

**What to Test:**
- Open the "Add Product" modal
- Verify the "Warehouse *" dropdown appears after the Brand field
- Dropdown should show:
  - All system default warehouses (e.g., "Main Warehouse (System Default)", "Breakages Warehouse (System Default)")
  - Company-specific warehouses
- Create a product with a selected warehouse
- Verify `warehouse_id` is saved in the database

### 2. Stock Transfer Functionality
**Location:** Product Card Actions

**Features:**
- New purple "Transfer Stock" button (exchange icon) next to Edit/Delete buttons

**Transfer Types:**
1. **Warehouse to Warehouse**
   - Select "Transfer to Another Warehouse" as transfer type
   - Choose destination warehouse (excludes source warehouse)
   - Enter quantity (max: current stock)
   - Optional: reason, reference, notes
   - Result: Stock deducted from source, added to destination

2. **Return to Supplier**
   - Select "Return to Supplier" as transfer type
   - Enter supplier name in "Supplier Name" field
   - Enter quantity
   - Optional: reason, reference, notes
   - Result: Stock deducted from source warehouse

3. **Write Off (Damage/Expiry)**
   - Select "Write Off (Damage/Expiry)" as transfer type
   - Enter quantity
   - Optional: reason (e.g., "Expired on 2026-01-20"), reference, notes
   - Result: Stock deducted from inventory

4. **Adjustment Out**
   - Select "Adjustment Out" as transfer type
   - Enter quantity
   - Optional: reason (e.g., "Inventory correction"), reference, notes
   - Result: Stock deducted from inventory

## Testing Steps

### Test 1: Create Product with Warehouse
1. Click "Add Product" button
2. Fill in product details:
   - Name: "Test Product A"
   - Category: Select any
   - Brand: "Test Brand"
   - **Warehouse: Select "Main Warehouse (System Default)"**
   - Cost Price: 100
   - Selling Price: 150
   - Initial Stock: 50
3. Save product
4. Verify product appears in list
5. Edit the product and verify warehouse is selected

### Test 2: Warehouse-to-Warehouse Transfer
1. Find a product with stock in "Main Warehouse"
2. Click the purple transfer button (exchange icon)
3. Transfer modal opens showing:
   - From Warehouse: Main Warehouse (disabled/read-only)
   - Transfer Type: Select "Transfer to Another Warehouse"
   - To Warehouse: Select "Breakages Warehouse (System Default)"
   - Quantity: Enter 10
   - Reason: "Damaged items"
4. Click "Transfer Stock"
5. Verify:
   - Success message appears
   - Stock deducted from source product
   - Check database `warehouse_transfers` table for the record
   - Check if product exists in destination warehouse with correct quantity

### Test 3: Return to Supplier
1. Click transfer button on a product
2. Select transfer type: "Return to Supplier"
3. Fill in:
   - Supplier Name: "ABC Suppliers Ltd"
   - Quantity: 5
   - Reason: "Defective items"
   - Reference: "RET-2026-001"
4. Transfer
5. Verify:
   - Stock deducted from product
   - `warehouse_transfers` record created with:
     - `transfer_type` = 'supplier_return'
     - `to_warehouse_id` = NULL
     - `external_target` = 'ABC Suppliers Ltd'

### Test 4: Write Off (Expiry)
1. Click transfer button
2. Select: "Write Off (Damage/Expiry)"
3. Fill in:
   - Quantity: 3
   - Reason: "Expired on 2026-01-15"
   - Reference: "EXP-2026-001"
4. Transfer
5. Verify stock deducted and transfer logged with `transfer_type` = 'write_off'

### Test 5: Adjustment Out
1. Click transfer button
2. Select: "Adjustment Out"
3. Fill in:
   - Quantity: 2
   - Reason: "Inventory count correction"
4. Transfer
5. Verify stock deducted and transfer logged with `transfer_type` = 'adjustment_out'

## Database Verification

### Check warehouse_transfers table:
```sql
SELECT 
  wt.*,
  p.name as product_name,
  fw.name as from_warehouse,
  tw.name as to_warehouse
FROM warehouse_transfers wt
JOIN products p ON wt.product_id = p.id
LEFT JOIN warehouses fw ON wt.from_warehouse_id = fw.id
LEFT JOIN warehouses tw ON wt.to_warehouse_id = tw.id
ORDER BY wt.created_at DESC
LIMIT 10;
```

### Check products table:
```sql
SELECT 
  p.name,
  p.stock_quantity,
  w.name as warehouse_name,
  p.created_by,
  p.updated_by
FROM products p
LEFT JOIN warehouses w ON p.warehouse_id = w.id
ORDER BY p.updated_at DESC
LIMIT 10;
```

## Expected Behavior

### Authorization
- Only **admin** and **superuser** roles can transfer stock
- Cashiers can view products but cannot transfer
- Frontend should show transfer button to all users (backend enforces authorization)

### Validation
- Quantity cannot exceed available stock
- Warehouse transfers require `to_warehouse_id`
- External transfers (supplier_return, write_off, adjustment_out) have `to_warehouse_id` = NULL
- Transfer type is required

### UI/UX
- Transfer modal closes on success
- Error messages displayed for validation failures
- Product list refreshes after successful transfer
- Loading state during transfer ("Transferring...")

## Troubleshooting

### Issue: Warehouse dropdown is empty
**Solution:** 
- Check if warehouses are seeded: `php artisan db:seed --class=WarehouseSeeder`
- Verify `/warehouses` endpoint returns data
- Check browser console for fetch errors

### Issue: Transfer fails with 403 Forbidden
**Solution:**
- Verify user role is admin or superuser
- Check middleware in routes/web.php
- Verify user is authenticated

### Issue: Stock not updating after transfer
**Solution:**
- Check backend ProductController::transferStock method
- Verify transaction is committing
- Check for errors in storage/logs/laravel.log
- Confirm product exists in destination warehouse

### Issue: "Warehouse *" field not showing in product form
**Solution:**
- Clear browser cache
- Check ProductsPage.vue line 339-350
- Verify Vue component is recompiled
- Run `npm run dev` in client directory

## API Endpoints

### GET /warehouses
Returns system default warehouses + company-specific warehouses

### POST /products/transfer
Payload:
```json
{
  "product_id": 1,
  "from_warehouse_id": 7,
  "to_warehouse_id": 8,  // nullable for external transfers
  "quantity": 10,
  "destination_type": "warehouse|supplier_return|write_off|adjustment_out",
  "reason": "Optional reason",
  "reference": "Optional reference",
  "external_target": "Supplier name (for supplier_return)",
  "note": "Optional notes"
}
```

Response:
```json
{
  "message": "Stock transferred successfully"
}
```
