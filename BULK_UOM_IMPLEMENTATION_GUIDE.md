# Bulk UoM System - Implementation Checklist & Migration Guide

## ✅ Implementation Status

### Phase 1: Backend Setup (COMPLETE)
- [x] Database migrations created (2 files)
- [x] Product model updated with new fields & relationships
- [x] Product model updated with helper methods
- [x] SaleItem model updated for UoM tracking
- [x] SaleItem model updated for decimal quantities

### Phase 2: Frontend Setup (COMPLETE)
- [x] ProductsPage.vue form updated with bulk UoM fields
- [x] SalesPage.vue product display updated for sale units
- [x] SalesPage.vue cart operations updated
- [x] Helper functions implemented in both pages
- [x] CSS styling added

### Phase 3: Documentation (COMPLETE)
- [x] Comprehensive documentation (BULK_UOM_SYSTEM.md)
- [x] Quick reference guide (BULK_UOM_QUICK_REFERENCE.md)
- [x] This implementation checklist

---

## 🚀 How to Activate the Feature

### Step 1: Run Database Migrations

```bash
# Navigate to Server directory
cd Server

# Run the migrations
php artisan migrate
```

Expected output:
```
Migrating: 2026_04_21_000001_add_purchase_sale_uoms_to_products
Migrated:  2026_04_21_000001_add_purchase_sale_uoms_to_products (xxms)
Migrating: 2026_04_21_000002_enhance_sale_items_with_uom
Migrated:  2026_04_21_000002_enhance_sale_items_with_uom (xxms)
```

### Step 2: Verify Database Changes

**Check Products Table:**
```sql
SELECT 
    id, name, purchase_uom_id, sale_uom_id, conversion_ratio, 
    track_by_purchase_unit, stock_quantity
FROM products 
LIMIT 1;
```

Expected fields added:
- purchase_uom_id (nullable bigint)
- sale_uom_id (nullable bigint)
- conversion_ratio (decimal 10,4)
- track_by_purchase_unit (boolean)

**Check Sale Items Table:**
```sql
SELECT 
    id, sale_id, product_id, uom_id, quantity, unit_price, total_price
FROM sale_items 
LIMIT 1;
```

Expected changes:
- uom_id added (nullable bigint)
- quantity changed to decimal(12,4)

### Step 3: Ensure UoMs Are Available

Verify that UoMs exist in system:
```sql
SELECT id, name, abbreviation FROM u_o_m_s ORDER BY name;
```

Should include:
- Liters (1L, 500ml, 250ml, 50L, etc.)
- Kilograms (kg, g, 500g, 25kg, etc.)
- Pieces (pcs, dz, pack, etc.)

If needed, seed UOMs:
```bash
php artisan db:seed --class=UOMSeeder
```

### Step 4: Test Product Creation

1. Go to **Products** page
2. Click **Add Product**
3. Fill form:
   - Name: "Test Juice"
   - Stock: 2
   - Purchase UOM: 50L
   - Sale UOM: 1L
   - Conversion: 50
4. Save product
5. Verify it appears with "100 1L in stock" display

### Step 5: Test Sales Transaction

1. Go to **Sales** page
2. Click product to add to cart
3. Notice it shows "100 1L in stock"
4. Add 25 items to cart
5. Check out and complete sale
6. Verify stock updated to 75 (or 1.5 purchase units)

---

## 📋 Detailed File Changes

### New Migration Files

#### File: `Server/database/migrations/2026_04_21_000001_add_purchase_sale_uoms_to_products.php`
- Adds 4 new columns to products table
- Creates foreign keys to u_o_m_s table
- Adds comments for clarity
- Reversible (down method included)

#### File: `Server/database/migrations/2026_04_21_000002_enhance_sale_items_with_uom.php`
- Adds uom_id column to sale_items
- Changes quantity from integer to decimal(12,4)
- Creates foreign key to u_o_m_s table
- Reversible (reverts quantity back to integer in down)

### Modified Model Files

#### File: `Server/app/Models/Product.php`
**Changes:**
- Updated $fillable: Added 4 new fields
- Updated $casts: Added decimal and boolean casts
- Added purchaseUom() relationship
- Added saleUom() relationship
- Added 4 helper methods (52 lines total)

**New Methods:**
```php
getAvailableQuantityInSaleUnits()  // Returns: stock_qty × conversion_ratio
getStockInSaleUnits()               // Returns: array with qty and uom
deductStockBySaleUnit()             // Deduct based on sale units (auto-convert)
addStockByPurchaseUnit()            // Add based on purchase units
```

#### File: `Server/app/Models/SaleItem.php`
**Changes:**
- Updated $fillable: Added uom_id
- Added $casts: For proper type handling
- Added uom() relationship

### Modified Vue Files

#### File: `client/src/pages/Users/ProductsPage.vue`
**Changes:**
- Added 3 form fields to template (lines: 346-405)
- Updated singleProductForm data structure
- Updated bulkProducts data structure
- Added 3 helper methods
- Added CSS styles

**New Form Fields:**
1. Purchase UOM dropdown
2. Sale UOM dropdown
3. Conversion Ratio input with live preview

**New Methods:**
```javascript
getPurchaseUomAbbrv()   // Returns abbreviation for purchase UoM
getSaleUomAbbrv()       // Returns abbreviation for sale UoM
```

#### File: `client/src/pages/Users/SalesPage.vue`
**Changes:**
- Updated product display template (lines: 88-105)
- Updated cart item template (lines: 160-163)
- Added 3 helper functions
- Updated addToCart() function
- Updated increaseQuantity() function
- Added CSS styles

**New Helper Functions:**
```javascript
getAvailableQuantity(product)    // Calc available in sale units
getProductSaleUom(product)       // Get sale UoM abbreviation
getUomLabel(product, type)       // Get formatted UoM label
```

**Updated Functions:**
- addToCart() - Now passes UoM info to cart items
- increaseQuantity() - Now checks available sale units

---

## 🔧 Troubleshooting

### Issue: "Column doesn't exist"
**Solution:**
```bash
php artisan migrate:refresh  # If development only
# OR
php artisan migrate  # Ensure migrations run
```

### Issue: UoM not appearing in dropdown
**Solution:**
```bash
php artisan db:seed --class=UOMSeeder
```

### Issue: Conversion ratio not showing
**Solution:**
- Set both Purchase UOM and Sale UOM
- Verify conversion_ratio field is populated
- Check browser console for errors

### Issue: Stock showing incorrect quantity
**Solutions:**
- Verify conversion_ratio is correct
- Check that it's tracking in **purchase units** internally
- Display shows **sale units** to user

---

## 📊 Testing Scenarios

### Test 1: Basic Bulk Purchase Setup
```
Setup:
- Product: Fresh Juice
- Purchase: 50L boxes (purchase_uom_id = 5)
- Retail: 1L bottles (sale_uom_id = 8)
- Conversion: 50
- Initial Stock: 3 (boxes)

Expected Result:
- Display: "150 1L in stock" (3 × 50)
- Can add up to 150 bottles to cart
```

### Test 2: Fractional Stock
```
Setup: Same as Test 1

Sell: 75 bottles
Expected After Sale:
- Internal: 1.5 purchase units
- Display: "75 1L in stock" (1.5 × 50)
```

### Test 3: Multiple Products with Different UoMs
```
Product 1: Juice (50L → 1L bottles, ratio: 50)
Product 2: Oil (5L → 250ml bottles, ratio: 20)

Expected:
- Each shows correct available quantity
- Cart shows correct UoM for each
- Stock calculated correctly
```

### Test 4: Cart Operations
```
Add to cart:
1. 25 × Juice (1L)
2. 40 × Oil (250ml)

Change quantities:
- Increase/decrease buttons work
- Stock limits enforced in sale units
- Cart totals calculated correctly
```

---

## 📈 Expected Behavior After Implementation

### For Users (Cashiers/Admins):

**Product Creation:**
- ✅ Can set purchase and retail UoMs separately
- ✅ Can set conversion ratio
- ✅ Initial stock in bulk units
- ✅ System converts to retail quantity

**Sales:**
- ✅ Product displays quantity in retail units
- ✅ Can add retail quantity to cart
- ✅ Cart shows what UoM is being sold
- ✅ Stock limits prevent overselling
- ✅ Checkout processes with auto-conversion

**Stock Management:**
- ✅ Purchase: Stock reduces in bulk units (with decimals)
- ✅ Reports: Show both bulk and retail quantities
- ✅ Alerts: Based on converted retail quantities

### For System:

**Database:**
- ✅ Products table: 4 new columns
- ✅ Sale Items: 1 new column, 1 modified column
- ✅ No breaking changes to existing data

**API:**
- ✅ Product endpoints accept new fields
- ✅ Sale endpoints handle decimal quantities
- ✅ Stock validation uses sale units

---

## 🚢 Rollback Instructions (If Needed)

To remove this feature:
```bash
cd Server

# Rollback the migrations
php artisan migrate:rollback --step=2

# This will:
# - Remove purchase_uom_id, sale_uom_id, conversion_ratio, track_by_purchase_unit from products
# - Remove uom_id from sale_items
# - Revert quantity to integer in sale_items
```

---

## ✅ Final Verification

After running migrations, verify everything:

```bash
# 1. Check migrations ran
php artisan migrate:status

# 2. Check products table structure
php artisan tinker
>>> \DB::select('PRAGMA table_info(products)') [for SQLite]
>>> or DESCRIBE products; [for MySQL]

# 3. Check sale_items table structure  
>>> \DB::select('PRAGMA table_info(sale_items)')

# 4. Test a product creation through API
```

---

## 📞 Support

Detailed documentation available in:
- **BULK_UOM_SYSTEM.md** - Comprehensive guide with all features
- **BULK_UOM_QUICK_REFERENCE.md** - Quick how-to guide with examples

Updated June 2026 for use with both admin and cashier roles.
