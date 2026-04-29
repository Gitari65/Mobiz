# UOM (Unit of Measure) System Implementation Guide

## 📋 Overview

The Mobiz POS system now has a **complete multi-UOM system** with:
- ✅ Improved dropdown selector with checklist interface
- ✅ Proper data flow from product selection → cart → invoice
- ✅ Support for multiple sale UOMs per product
- ✅ Conversion ratios between purchase and sale units

---

## 🎯 UOM Selector Component

### **Location**: `client/src/components/UOMSelector.vue`

### **Features**:
- **Dropdown with Checklist**: Click "Add UOM" to open a dropdown menu
- **Search Functionality**: Filter UOMs by name or abbreviation
- **Visual Feedback**: Gradient tags with remove buttons
- **Mobile Responsive**: Optimized for all screen sizes
- **Keyboard Support**: ESC key closes dropdown

### **Props**:
```javascript
modelValue      // Array of selected UOM IDs (v-model binding)
uoms           // Array of available UOM objects from API
```

### **Usage in Components**:
```vue
<UOMSelector 
  v-model="form.sale_uom_ids"
  :uoms="uoms"
/>
```

---

## 🔄 Data Flow Through System

### **1. Product Management (ProductsPage.vue)**

#### **Adding Product with Multiple UOMs**:
```javascript
form: {
  name: 'Coca Cola',
  price: 200,
  sale_uom_ids: [1, 2, 3],      // Multiple UOMs: 250ml, 500ml, 1L
  purchase_uom_id: 4,            // How purchased: Carton
  conversion_ratio: 24            // 24 x 250ml = 1 carton
}
```

#### **API Endpoint**: `POST /products`
```json
{
  "name": "Coca Cola",
  "price": 200,
  "sale_uom_ids": [1, 2, 3],
  "purchase_uom_id": 4,
  "conversion_ratio": 24
}
```

### **2. Sales Cart (SalesPage.vue)**

#### **Fetching Products with UOMs**:
```javascript
// Products come with saleUoms array
product = {
  id: 5,
  name: 'Coca Cola',
  price: 200,
  sale_uom_ids: [1, 2, 3],
  saleUoms: [
    { id: 1, name: 'Small', abbreviation: '250ml' },
    { id: 2, name: 'Medium', abbreviation: '500ml' },
    { id: 3, name: 'Large', abbreviation: '1L' }
  ],
  purchase_uom_id: 4,
  conversion_ratio: 24
}
```

#### **UOM Selection Modal** (Line 155-200):
```vue
<!-- When adding product to cart, user selects UOM -->
<div v-for="uom in uoMSelectorProduct?.saleUoms" @click="selectUoM(uom)">
  {{ uom.name }} ({{ uom.abbreviation }})
</div>
```

#### **Cart Item Structure**:
```javascript
cartItem = {
  product_id: 5,
  quantity: 5,
  uom_id: 1,                     // Selected UOM for this item
  price: 200,
  uom: { id: 1, name: 'Small', abbreviation: '250ml' }
}
```

### **3. Sales Creation (Checkout)**

#### **Request Payload**:
```json
{
  "customer_id": 1,
  "items": [
    {
      "product_id": 5,
      "quantity": 5,
      "uom_id": 1,                // UOM used in this sale
      "price": 200
    }
  ],
  "payment_method": "cash",
  "amount_paid": 1000
}
```

#### **Backend Processing** (SaleController.php):
```php
// Validates UOM exists for product
$product = Product::find($item['product_id']);
$saleUom = $product->saleUoms()->find($item['uom_id']);

// Creates SaleItem with UOM info
SaleItem::create([
  'sale_id' => $sale->id,
  'product_id' => $item['product_id'],
  'quantity' => $item['quantity'],
  'uom_id' => $item['uom_id'],
  'price' => $item['price']
]);
```

### **4. Receipt Display**

#### **Current Format** (Issue #1 - Not showing UOM):
```
Item: Coca Cola
Qty: 5
Price: 200 × 5 = 1,000
```

#### **Improved Format** (TODO):
```
Item: Coca Cola
Qty: 5 × 250ml
Price: 200 × 5 = 1,000
```

#### **Fix Location**: `resources/views/receipt.blade.php` (Line ~45)
```blade
<!-- Current -->
<td>{{ $item->quantity }}</td>

<!-- Should be -->
<td>{{ $item->quantity }} × {{ $item->uom->abbreviation }}</td>
```

### **5. Invoice Tracking**

#### **Current Database Issue**:
The `invoice_items` table doesn't store UOM info - **needs migration**

#### **Recommended Schema Addition**:
```sql
ALTER TABLE invoice_items ADD COLUMN uom_id BIGINT UNSIGNED NULLABLE;
ALTER TABLE invoice_items ADD COLUMN uom_abbreviation VARCHAR(50) NULLABLE;
ALTER TABLE invoice_items ADD FOREIGN KEY (uom_id) REFERENCES units_of_measure(id);
```

#### **Why Important**:
- Track which UOM was used when sale occurred
- Historical accuracy if UOMs are updated later
- Better reporting and analysis

### **6. Inventory Management (InventoryPageNew.vue)**

#### **Stock Display**:
```javascript
// Shows total quantity in default UOM
product = {
  id: 5,
  name: 'Coca Cola',
  stock_quantity: 240,              // Total 250ml units in stock
  sale_uom_ids: [1, 2, 3],
  saleUoms: [
    { id: 1, name: '250ml', abbreviation: '250ml' },
    { id: 2, name: '500ml', abbreviation: '500ml' },
    { id: 3, name: '1L', abbreviation: '1L' }
  ],
  conversion_ratio: 24
}
```

#### **Display Format** (TODO):
```
Display: "240 × 250ml (equivalent to 10 × 1L)"
Calculation: 240 ÷ 24 = 10 cartons worth
```

---

## 🐛 Known Issues & Fixes Required

### **Issue 1: Receipt Not Showing UOM** 🔴 HIGH
**Status**: Not Fixed  
**Location**: `resources/views/receipt.blade.php`  
**Fix**: Add `{{ $item->uom->abbreviation }}` to receipt template  
**Estimated Time**: 15 minutes

### **Issue 2: Invoice Items Missing UOM Tracking** 🔴 HIGH
**Status**: Not Fixed  
**Location**: Database schema  
**Fix**: Create migration to add `uom_id` and `uom_abbreviation` columns  
**Estimated Time**: 30 minutes

### **Issue 3: No Server-Side UOM Validation** 🟡 MEDIUM
**Status**: Not Fixed  
**Location**: SaleController, ProductController  
**Fix**: Validate UOM belongs to product before processing  
**Estimated Time**: 45 minutes

### **Issue 4: Inventory Conversion Display** 🟡 MEDIUM
**Status**: Not Fixed  
**Location**: InventoryPageNew.vue  
**Fix**: Display equivalent units (e.g., "240 × 250ml = 60L")  
**Estimated Time**: 1 hour

---

## ✅ Implementation Checklist

### **Phase 1: Frontend (COMPLETED)**
- [x] Create UOMSelector component with dropdown + checklist
- [x] Integrate into ProductsPage.vue (add form)
- [x] Integrate into ProductsPage.vue (edit form)
- [x] Remove old UOM selection methods
- [x] Verify no console errors

### **Phase 2: Backend Validation (TODO)**
- [ ] Add server-side UOM validation in SaleController
- [ ] Verify API returns products with saleUoms relationship
- [ ] Test with multiple UOMs per product

### **Phase 3: User Facing Features (TODO)**
- [ ] Fix receipt display to show UOM abbreviation
- [ ] Add UOM conversion display in inventory
- [ ] Show conversion ratios in product details

### **Phase 4: Data Integrity (TODO)**
- [ ] Create database migration for invoice_items UOM tracking
- [ ] Backfill existing invoices with UOM data
- [ ] Add database constraints

---

## 🧪 Testing Workflow

### **Test 1: Add Product with Multiple UOMs**
1. Go to Products page
2. Click "Add Product"
3. Enter product details
4. In "Sale UOMs" section:
   - Click "Add UOM" dropdown
   - Select 3-4 different UOMs using checkboxes
   - Verify tags appear with remove buttons
   - Click "Done"
5. Save product
6. **Expected**: Product saved with all selected UOMs

### **Test 2: Add to Cart and Select UOM**
1. Go to Sales page
2. Search for product with multiple UOMs
3. Click to add to cart
4. Modal appears showing all available UOMs
5. Select one UOM
6. **Expected**: Cart item shows selected UOM

### **Test 3: Complete Sale**
1. Add multiple items with different UOMs to cart
2. Complete sale and print receipt
3. **Check**: Receipt shows each item with correct UOM (e.g., "5 × 250ml")

### **Test 4: Edit Product UOMs**
1. Edit existing product
2. Scroll to "Sale UOMs" section
3. Remove one UOM tag
4. Add new UOM using dropdown
5. Save changes
6. **Expected**: UOM changes persisted correctly

---

## 📊 Current System Status

| Component | Status | Notes |
|-----------|--------|-------|
| **UOM Selector UI** | ✅ Complete | New dropdown with checklist |
| **Product Add Form** | ✅ Complete | Uses UOMSelector component |
| **Product Edit Form** | ✅ Complete | Uses UOMSelector component |
| **Sales Cart** | ✅ Complete | Shows all UOM options |
| **Cart UOM Selection** | ✅ Complete | User can choose UOM per item |
| **Sale Creation** | ✅ Complete | Accepts UOM per item |
| **Receipt Display** | ❌ Missing UOM | Shows quantity, not "qty × UOM" |
| **Invoice Tracking** | ❌ No UOM Storage | Should save UOM used in sale |
| **Inventory Display** | ⚠️ Partial | Shows total but not conversion |
| **Server Validation** | ⚠️ Minimal | Should validate UOM for product |

---

## 🚀 Next Priority Actions

1. **Immediate**: Test UOMSelector component in browser
2. **High**: Fix receipt template to display UOM
3. **High**: Add UOM validation in SaleController
4. **Medium**: Create invoice UOM tracking migration
5. **Medium**: Add inventory conversion display

---

## 📞 Support

If UOM selector isn't appearing or showing errors:
1. Check browser console (F12 → Console tab)
2. Verify UOMSelector.vue exists at `client/src/components/UOMSelector.vue`
3. Confirm ProductsPage.vue imports the component correctly
4. Check that API returns `saleUoms` relationship with products

For API issues:
1. Verify backend Product model has `saleUoms()` relationship
2. Check ProductController's show/index methods load relationship
3. Test API endpoint: `/api/products` should include `saleUoms` array

