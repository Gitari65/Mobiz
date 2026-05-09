# Comprehensive UOM Data Flow Analysis

## Executive Summary

The system has a **hybrid UOM architecture** supporting both:
1. **New Multi-UOM System**: Multiple sale UOMs per product (many-to-many via `product_sale_uoms` junction table)
2. **Legacy Single UOM System**: Single sale_uom_id field on products table (for backward compatibility)

The UOM data flows correctly from product definition → cart → database, but **gaps exist in visibility and validation**. UOM information is NOT displayed in receipts/invoices, and invoice records don't store which UOM was used for each line item.

---

## 1. How `saleUoms` is Populated in Product Data

### A. API Call Path

**Frontend Request** ([SalesPage.vue](client/src/pages/Users/SalesPage.vue) line 773):
```javascript
async function fetchProducts() {
  try {
    const res = await axios.get('/products')  // GET /products endpoint
    products.value = res.data
    console.log('✅ Products loaded with UoMs:', products.value.slice(0, 2))
  } catch (err) {
    console.error('❌ Failed to fetch products:', err.message)
  }
}
```

**Backend Controller** ([ProductController.php](Server/app/Http/Controllers/ProductController.php) lines 162-190):
```php
public function index()
{
    $products = Product::query()->get();
    
    // Loads all UOM relationships including NEW multi-UOM system
    $products->load([
        'warehouse', 
        'uom',              // Default UOM
        'saleUom',          // Legacy: single sale UOM (backward compat)
        'saleUoms',         // ← NEW: Multiple sale UOMs (many-to-many)
        'purchaseUom',      // Purchase UOM (bulk buying unit)
        'creator', 
        'editor', 
        'company'
    ]);
    
    return response()->json($products, 200);
}
```

### B. Backend Database Structure

**Products Table Columns** ([Product Model](Server/app/Models/Product.php)):
```php
protected $fillable = [
    'name', 'sku', 'price', 'cost_price', 'stock_quantity',
    'warehouse_id',
    'uom_id',           // Default/purchase unit
    'purchase_uom_id',  // How you buy (e.g., 50L drum)
    'sale_uom_id',      // Legacy: How you sell (backward compat)
    'conversion_ratio', // e.g., 1 purchase_uom = 4 sale_uoms
    'track_by_purchase_unit'
];
```

**Product_Sale_UOMs Junction Table** ([Migration 2026_04_21_000002](Server/database/migrations/2026_04_21_000002_create_product_sale_uoms_table.php)):
```php
Schema::create('product_sale_uoms', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
    $table->foreignId('uom_id')->constrained('u_o_m_s')->onDelete('cascade');
    $table->decimal('conversion_ratio', 10, 4)->default(1)
        ->comment('How many of this UOM make 1 purchase unit');
    $table->boolean('is_default')->default(false)
        ->comment('Default UOM to show in cart');
    $table->timestamps();
    
    $table->unique(['product_id', 'uom_id']);
});
```

### C. Example Data Flow

When you create a product in [ProductsPage.vue](client/src/pages/Users/ProductsPage.vue):

1. **User selects** "250ml, 500ml, 1L" as sale UOMs
2. **Frontend sends** to POST `/api/products`:
   ```javascript
   {
     name: "Juice",
     price: 150,
     sale_uom_ids: [1, 2, 3],      // IDs for 250ml, 500ml, 1L
     conversion_ratio: 4            // 1 purchase_uom (1L) = 4 × 250ml
   }
   ```

3. **Backend** ([ProductController line 241](Server/app/Http/Controllers/ProductController.php)):
   ```php
   $saleUomIds = $validated['sale_uom_ids'] ?? [];
   
   // Create the product
   $product = Product::create($validated);
   
   // Attach multiple sale UOMs if provided
   if (!empty($saleUomIds)) {
       $syncData = [];
       foreach ($saleUomIds as $index => $uomId) {
           $syncData[$uomId] = [
               'conversion_ratio' => $validated['conversion_ratio'] ?? 1,
               'is_default' => ($index === 0)  // First one is default
           ];
       }
       $product->saleUoms()->sync($syncData);
   }
   ```

4. **Data persisted** to:
   - `products` table: name, price, stock_quantity
   - `product_sale_uoms` table: product_id, uom_id, conversion_ratio, is_default

5. **When fetched again**, API returns:
   ```json
   {
     "id": 123,
     "name": "Juice",
     "price": 150,
     "saleUoms": [
       {"id": 1, "name": "250 ml", "abbreviation": "250ml", "pivot": {"conversion_ratio": 4, "is_default": true}},
       {"id": 2, "name": "500 ml", "abbreviation": "500ml", "pivot": {"conversion_ratio": 2, "is_default": false}},
       {"id": 3, "name": "1 Liter", "abbreviation": "1L", "pivot": {"conversion_ratio": 1, "is_default": false}}
     ]
   }
   ```

---

## 2. Product Model UOM Relationships

### A. Relationship Definitions

**[Product.php](Server/app/Models/Product.php) lines 89-130**:

```php
// Default purchase unit
public function uom()
{
    return $this->belongsTo(UOM::class);
}

// Purchase and Sale UoM relationships
public function purchaseUom()
{
    return $this->belongsTo(UOM::class, 'purchase_uom_id');
}

// Legacy: Single sale UOM (backward compatibility)
public function saleUom()
{
    return $this->belongsTo(UOM::class, 'sale_uom_id');
}

// ← NEW: Multiple sale UOMs (many-to-many)
public function saleUoms()
{
    return $this->belongsToMany(UOM::class, 'product_sale_uoms', 'product_id', 'uom_id')
        ->withPivot('conversion_ratio', 'is_default')
        ->withTimestamps();
}
```

### B. Helper Methods

**[Product.php](Server/app/Models/Product.php) lines 228-260**:

```php
// Get available quantity in sale units
public function getAvailableQuantityInSaleUnits()
{
    if (!$this->sale_uom_id || !$this->conversion_ratio) {
        return $this->stock_quantity;
    }
    // Converts purchase units to sale units
    return $this->stock_quantity * $this->conversion_ratio;
}

// Get stock formatted for display in sale units
public function getStockInSaleUnits()
{
    if (!$this->sale_uom_id) {
        return [
            'quantity' => $this->stock_quantity,
            'uom' => $this->uom?->abbreviation ?? 'unit'
        ];
    }
    
    return [
        'quantity' => $this->getAvailableQuantityInSaleUnits(),
        'uom' => $this->saleUom?->abbreviation ?? 'unit'
    ];
}

// Deduct stock based on sale units sold
public function deductStockBySaleUnit($saleQuantity)
{
    if (!$this->sale_uom_id || !$this->conversion_ratio) {
        return $this->decrement('stock_quantity', $saleQuantity);
    }
    
    // Convert sale units back to purchase units for deduction
    $purchaseUnits = $saleQuantity / $this->conversion_ratio;
    return $this->decrement('stock_quantity', $purchaseUnits);
}
```

---

## 3. API Endpoints for Product-UOM Relationships

### A. Products Endpoint

**Endpoint**: `GET /products` or `GET /api/products`

**Controller**: [ProductController.index()](Server/app/Http/Controllers/ProductController.php) lines 162-190

**Response Structure**:
```json
[
  {
    "id": 1,
    "name": "Juice",
    "price": 150,
    "stock_quantity": 100,
    "uom_id": 4,
    "purchase_uom_id": 5,
    "sale_uom_id": null,
    "conversion_ratio": 4,
    
    "uom": {
      "id": 4,
      "name": "Piece",
      "abbreviation": "pcs"
    },
    
    "purchaseUom": {
      "id": 5,
      "name": "1 Liter",
      "abbreviation": "1L"
    },
    
    "saleUom": null,  // Legacy (null if using new multi-UOM)
    
    "saleUoms": [     // ← NEW multi-UOM system
      {
        "id": 1,
        "name": "250 ml",
        "abbreviation": "250ml",
        "pivot": {
          "conversion_ratio": 4,
          "is_default": true
        }
      },
      {
        "id": 2,
        "name": "500 ml",
        "abbreviation": "500ml",
        "pivot": {
          "conversion_ratio": 2,
          "is_default": false
        }
      },
      {
        "id": 3,
        "name": "1 Liter",
        "abbreviation": "1L",
        "pivot": {
          "conversion_ratio": 1,
          "is_default": false
        }
      }
    ]
  }
]
```

### B. UOMs Endpoint

**Endpoint**: `GET /uoms`

**Controller**: [UOMController.index()](Server/app/Http/Controllers/UOMController.php) lines 7-12

**Response**:
```json
[
  {"id": 1, "name": "250 ml", "abbreviation": "250ml", "is_system": true},
  {"id": 2, "name": "500 ml", "abbreviation": "500ml", "is_system": true},
  {"id": 3, "name": "1 Liter", "abbreviation": "1L", "is_system": true},
  {"id": 4, "name": "Piece", "abbreviation": "pcs", "is_system": true},
  {"id": 5, "name": "Box", "abbreviation": "box", "is_system": true}
]
```

### C. Sales Creation Endpoint

**Endpoint**: `POST /sales`

**Controller**: [SaleController.store()](Server/app/Http/Controllers/SaleController.php) lines 1-50

**Request Validation** (lines 16-26):
```php
$validated = $request->validate([
    'items'                  => 'required|array|min:1',
    'items.*.product_id'     => 'required|exists:products,id',
    'items.*.quantity'       => 'required|integer|min:1',
    'items.*.price'          => 'required|numeric|min:0',
    'items.*.uom_id'         => 'nullable|exists:u_o_m_s,id',  // ← UOM tracked
    'customer_id'            => 'nullable|integer|exists:customers,id',
    'payment_method'         => 'nullable|string|max:255',
    'tax'                    => 'nullable|numeric|min:0',
    'amount_paid'            => 'nullable|numeric|min:0',
]);
```

**Request Example**:
```json
{
  "items": [
    {
      "product_id": 1,
      "quantity": 5,
      "price": 150,
      "uom_id": 1
    },
    {
      "product_id": 2,
      "quantity": 2,
      "price": 200,
      "uom_id": 3
    }
  ],
  "customer_id": null,
  "payment_method": "Cash",
  "amount_paid": 950
}
```

---

## 4. Cart Item Structure & UOM Information

### A. Frontend Cart Item Structure

**[SalesPage.vue](client/src/pages/Users/SalesPage.vue) lines 920-945**:

```javascript
// When product added to cart
const cartItem = { 
  ...product,           // Spreads entire product object including saleUoms
  quantity: 1,
  uom_id: defaultUomId  // ← Selected UOM for this cart item
}
cart.value.push(cartItem)
```

**Full Cart Item Object**:
```javascript
{
  id: 123,                    // Product ID
  name: "Juice",
  price: 150,
  stock_quantity: 100,
  quantity: 5,                // Quantity user selected
  uom_id: 1,                  // ← Which UOM user chose (e.g., 250ml)
  
  // Entire product object is spread, so includes:
  saleUoms: [
    {id: 1, name: "250ml", abbreviation: "250ml", ...},
    {id: 2, name: "500ml", abbreviation: "500ml", ...},
    {id: 3, name: "1L", abbreviation: "1L", ...}
  ],
  saleUom: null,
  purchaseUom: {...},
  conversion_ratio: 4,
  // ... other product fields
}
```

### B. Cart Item UOM Change

**[SalesPage.vue](client/src/pages/Users/SalesPage.vue) lines 200-230**:

```html
<select v-model="item.uom_id" class="uom-select" @change="updateItemUoM(item)">
  <!-- Show all sale UOMs if available (new multi-UOM system) -->
  <optgroup v-if="getProduct(item.id)?.saleUoms && getProduct(item.id).saleUoms.length > 0" 
            label="Available Units">
    <option v-for="uom in getProduct(item.id).saleUoms" :key="uom.id" :value="uom.id">
      {{ uom.name }} ({{ uom.abbreviation }})
    </option>
  </optgroup>
  
  <!-- Fallback to legacy single sale UOM -->
  <option v-else-if="getProduct(item.id)?.saleUom" 
          :value="getProduct(item.id).saleUom.id">
    {{ getProduct(item.id).saleUom.name }}
  </option>
  
  <!-- Fallback to other UOMs -->
  <option v-if="getProduct(item.id)?.purchaseUom && !getProduct(item.id).saleUoms" 
          :value="getProduct(item.id).purchaseUom.id">
    {{ getProduct(item.id).purchaseUom.abbreviation }}
  </option>
  
  <option v-if="getProduct(item.id)?.uom && !getProduct(item.id).saleUoms" 
          :value="getProduct(item.id).uom.id">
    {{ getProduct(item.id).uom.abbreviation }}
  </option>
</select>
```

**Update Function**:
```javascript
function updateItemUoM(item) {
  // UoM has been changed, refresh promotions to recalculate
  refreshPromotions()
}
```

### C. Cart Submission to Backend

**[SalesPage.vue](client/src/pages/Users/SalesPage.vue) lines 1195-1210**:

```javascript
const submitData = {
  items: cart.value.map(item => {
    const price = Number(item.price) || 0
    const quantity = Number(item.quantity) || 0
    return {
      id: item.id,
      name: item.name,
      product_id: item.id,
      quantity: quantity,
      price: price,
      uom_id: item.uom_id,  // ← Sends selected UOM to backend
      total: (price * quantity)
    }
  }),
  customer_id: selectedCustomerId.value,
  payment_method: paymentForm.value.paymentMethod,
  amount_paid: amountPaid.value,
  notes: saleForm.value.notes
}

await axios.post('/sales', submitData)
```

---

## 5. Invoice/Receipt Generation & UOM Display

### A. Receipt Data Structure

**[SalesPage.vue](client/src/pages/Users/SalesPage.vue) lines 1195-1220**:

```javascript
receiptData.value = {
  receiptNumber: '12345',
  date: now.toLocaleDateString('en-GB'),
  time: now.toLocaleTimeString('en-GB'),
  customer: saleForm.value.customer_name || 'Walk-in Customer',
  
  items: cart.value.map(item => {
    const price = Number(item.price) || 0
    const quantity = Number(item.quantity) || 0
    return {
      id: item.id,
      name: item.name,
      quantity: quantity,           // ← Just the number
      price: price.toFixed(2),
      total: (price * quantity).toFixed(2)
      // ⚠️ MISSING: uom_id, uom_abbreviation
    }
  }),
  
  subtotal: subtotal.toFixed(2),
  discount: discountAmount.toFixed(2),
  taxAmount: taxAmount.toFixed(2),
  total: netTotal.toFixed(2),
  amountPaid: amountPaid.toFixed(2),
  change: Math.max(0, amountPaid - netTotal).toFixed(2),
  notes: saleForm.value.notes,
  saleId: saleResponse.id || 'N/A'
}
```

### B. Receipt Template Display

**[SalesPage.vue](client/src/pages/Users/SalesPage.vue) lines 430-475**:

```html
<div v-for="item in receiptData.items" :key="item.id" class="receipt-item">
  <span class="item-name">{{ item.name }}</span>
  <span class="item-qty">{{ item.quantity }}</span>          <!-- ← Just shows "5" -->
  <span class="item-price">{{ item.price }}</span>
  <span class="item-total">{{ (item.price * item.quantity).toFixed(2) }}</span>
</div>
```

**Result**: Receipt shows "5" instead of "5 × 250ml"

### C. Invoice Item Model

**[InvoiceItem.php](Server/app/Models/InvoiceItem.php)**:

```php
protected $fillable = [
    'invoice_id',
    'product_id',
    'description',
    'quantity',
    'unit_price',
    'total_price'
    // ⚠️ MISSING: uom_id, uom_abbreviation
];
```

**Issue**: Invoice records don't store which UOM was used for each line item!

---

## 6. Existing UOM Dropdown/Selector Components

### A. UOM Selection Modal (Add to Cart)

**[SalesPage.vue](client/src/pages/Users/SalesPage.vue) lines 155-200**:

```html
<div v-if="showUoMSelector" class="modal-overlay" @click="showUoMSelector = false">
  <div class="modal-content" @click.stop>
    <h3>Select Unit of Measure</h3>
    <p class="modal-subtitle">Choose how to sell {{ uoMSelectorProduct?.name }}</p>
    
    <div class="uom-options">
      <!-- Show all sale UOMs if available (new multi-UOM system) -->
      <button
        v-for="uom in uoMSelectorProduct?.saleUoms"
        :key="uom.id"
        @click="selectUoM(uom)"
        class="uom-option"
      >
        <i class="fas fa-droplet"></i>
        <strong>{{ uom.name }}</strong>
        <small>{{ uom.abbreviation }}</small>
      </button>
      
      <!-- Fallback to legacy single sale UOM -->
      <button
        v-if="!uoMSelectorProduct?.saleUoms && uoMSelectorProduct?.saleUom"
        @click="selectUoM(uoMSelectorProduct.saleUom)"
        class="uom-option"
      >
        <i class="fas fa-droplet"></i>
        <strong>{{ uoMSelectorProduct.saleUom.name }}</strong>
        <small>{{ uoMSelectorProduct.saleUom.abbreviation }}</small>
      </button>
      
      <!-- Fallback to purchase UOM -->
      <button
        v-if="!uoMSelectorProduct?.saleUoms && uoMSelectorProduct?.purchaseUom"
        @click="selectUoM(uoMSelectorProduct.purchaseUom)"
        class="uom-option"
      >
        <i class="fas fa-boxes"></i>
        <strong>{{ uoMSelectorProduct.purchaseUom.name }}</strong>
        <small>{{ uoMSelectorProduct.purchaseUom.abbreviation }}</small>
      </button>
    </div>
    
    <button @click="showUoMSelector = false" class="modal-close-btn">Cancel</button>
  </div>
</div>
```

**When Triggered** ([SalesPage.vue](client/src/pages/Users/SalesPage.vue) lines 900-920):
```javascript
// Show UoM selector if product has multiple sale UOMs
if (hasSaleUoms && product.saleUoms.length > 1) {
  // Multiple UoMs - show modal
  uoMSelectorProduct.value = product
  pendingCartItem.value = {
    ...product,
    quantity: 1
  }
  showUoMSelector.value = true
  return
}
```

### B. Cart Item UOM Selector (Edit in Cart)

**[SalesPage.vue](client/src/pages/Users/SalesPage.vue) lines 200-230**:

```html
<select v-model="item.uom_id" class="uom-select" @change="updateItemUoM(item)">
  <!-- Multi-UOM system -->
  <optgroup v-if="getProduct(item.id)?.saleUoms" label="Available Units">
    <option v-for="uom in getProduct(item.id).saleUoms" :key="uom.id" :value="uom.id">
      {{ uom.name }} ({{ uom.abbreviation }})
    </option>
  </optgroup>
  
  <!-- Fallbacks for legacy system -->
  <option v-else-if="getProduct(item.id)?.saleUom" 
          :value="getProduct(item.id).saleUom.id">
    {{ getProduct(item.id).saleUom.name }}
  </option>
  <option v-if="getProduct(item.id)?.purchaseUom" 
          :value="getProduct(item.id).purchaseUom.id">
    {{ getProduct(item.id).purchaseUom.abbreviation }}
  </option>
  <option v-if="getProduct(item.id)?.uom" 
          :value="getProduct(item.id).uom.id">
    {{ getProduct(item.id).uom.abbreviation }}
  </option>
</select>
```

### C. Product Form UOM Selectors

**Multiple Sale UOM Selector** ([ProductsPage.vue](client/src/pages/Users/ProductsPage.vue) lines 388-410):

```html
<div class="form-group">
  <label class="form-label">Sale UOMs (Multiple Selection)</label>
  
  <div class="uom-selector-wrapper">
    <!-- Display selected UOMs as tags -->
    <div class="selected-uoms">
      <div v-if="singleProductForm.sale_uom_ids?.length" class="selected-tags">
        <span v-for="(uomId, index) in singleProductForm.sale_uom_ids" 
              :key="uomId" 
              class="uom-tag">
          {{ getUomName(uomId) }}
          <button type="button" @click="removeSaleUom(singleProductForm.sale_uom_ids, index)" 
                  class="remove-tag">
            <i class="fas fa-times"></i>
          </button>
        </span>
      </div>
      <div v-else class="empty-selection">No UOMs selected</div>
    </div>
    
    <!-- Add new UOM dropdown -->
    <select class="form-input uom-multiselect" 
            @change="addSaleUom(singleProductForm.sale_uom_ids, $event)">
      <option value="">+ Add Sale UOM</option>
      <option v-for="uom in uoms.filter(u => !singleProductForm.sale_uom_ids?.includes(u.id))" 
              :key="uom.id" 
              :value="uom.id">
        {{ uom.name }} ({{ uom.abbreviation }})
      </option>
    </select>
  </div>
  <small class="form-hint">Select multiple UOMs (e.g., 250ml, 500ml, 1L). First one will be default.</small>
</div>
```

**Helper Methods** ([ProductsPage.vue](client/src/pages/Users/ProductsPage.vue) lines 2225-2235):

```javascript
getUomName(uomId) {
  const uom = this.uoms.find(u => u.id === uomId)
  return uom ? `${uom.name} (${uom.abbreviation})` : 'Unknown UOM'
},

addSaleUom(saleUomIds, event) {
  const uomId = parseInt(event.target.value)
  if (uomId && !saleUomIds.includes(uomId)) {
    saleUomIds.push(uomId)
  }
  event.target.value = ''
},

removeSaleUom(saleUomIds, index) {
  saleUomIds.splice(index, 1)
}
```

---

## 7. Current Data Flow Diagram

```
┌─────────────────────────────────────────────────────────────────────┐
│                    PRODUCT CREATION/EDITING                         │
│              (ProductsPage.vue + ProductController)                 │
└─────────────────────────────────────────────────────────────────────┘
                                 │
                                 ▼
                   User selects multiple UOMs
                   (e.g., 250ml, 500ml, 1L)
                                 │
                                 ▼
                   POST /products with sale_uom_ids
                                 │
                                 ▼
           ┌─────────────────────────────────────┐
           │      Product created in DB          │
           │  products table: uom_id, price      │
           │  product_sale_uoms: uom relationships│
           └─────────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────────┐
│                        POS SALES PAGE                               │
│           (SalesPage.vue + ProductController.index)                 │
└─────────────────────────────────────────────────────────────────────┘
                                 │
                                 ▼
                GET /products loads products
                with saleUoms relationships
                                 │
                                 ▼
              ┌──────────────────────────────┐
              │  Product displayed in list   │
              │  saleUoms[] included in data │
              └──────────────────────────────┘
                                 │
                                 ▼
                    User clicks "Add to Cart"
                                 │
                     ╔═══════════╩═══════════╗
                     │                       │
           ┌─────────▼─────────┐  ┌──────────▼────────┐
           │ Single UOM?       │  │ Multiple UOMs?   │
           └─────────┬─────────┘  └──────────┬────────┘
                     │ YES              │ YES
              Direct add to cart   Show modal selector
                     │              (buttons for each UOM)
                     │                      │
                     └──────────┬───────────┘
                                │
                                ▼
                  ┌──────────────────────────┐
                  │  Cart item created       │
                  │  {id, name, price,       │
                  │   quantity, uom_id,      │
                  │   saleUoms[]}            │
                  └──────────────────────────┘
                                │
                                ▼
                  ┌──────────────────────────┐
                  │ User can change UOM      │
                  │ in cart via dropdown     │
                  │ item.uom_id updated      │
                  └──────────────────────────┘
                                │
                                ▼
                  ┌──────────────────────────┐
                  │  User clicks "Checkout"  │
                  │  Submits cart            │
                  └──────────────────────────┘
                                │
                                ▼
                    POST /sales with items[]
                    including uom_id
                                │
                                ▼
           ┌─────────────────────────────────┐
           │    Sale created in DB           │
           │  Sale record: total, discount   │
           │  SaleItem records: product_id,  │
           │                   quantity,     │
           │                   uom_id,       │
           │                   unit_price    │
           └─────────────────────────────────┘
                                │
                                ▼
           ┌─────────────────────────────────┐
           │  Receipt generated              │
           │  ⚠️ UOM NOT included in display │
           │  Shows: "5" not "5 × 250ml"    │
           └─────────────────────────────────┘
```

---

## 8. Gaps and Inconsistencies

### Gap 1: Receipt Missing UOM Information
- **Issue**: Receipt shows quantity only (e.g., "5") without UOM unit
- **Location**: [SalesPage.vue](client/src/pages/Users/SalesPage.vue) lines 1195-1220 (receiptData), 430-475 (template)
- **Impact**: Users can't see what UOM they purchased (e.g., was it 5 × 250ml or 5 × 1L?)
- **Fix**: Include `uom_abbreviation` in receipt data

### Gap 2: Invoice Model Missing UOM Field
- **Issue**: `InvoiceItem` table has no `uom_id` or `uom_abbreviation` column
- **Location**: [InvoiceItem.php](Server/app/Models/InvoiceItem.php)
- **Migration**: [2026_01_27_130100_create_invoice_items_table.php](Server/database/migrations/2026_01_27_130100_create_invoice_items_table.php)
- **Impact**: Invoices don't store which UOM was used for purchases - historical data lost
- **Fix**: Add migration to add `uom_id` and `uom_abbreviation` columns

### Gap 3: No Server-Side UOM Validation
- **Issue**: API doesn't validate that selected `uom_id` is actually in product's `saleUoms`
- **Location**: [SaleController.php](Server/app/Http/Controllers/SaleController.php) lines 16-26
- **Impact**: Client could theoretically send invalid UOM IDs
- **Fix**: Add validation to check if uom_id exists in product_sale_uoms

### Gap 4: Conversion Ratios Not Displayed to Users
- **Issue**: Conversion ratio (e.g., "4 × 250ml = 1L") not shown in UI
- **Location**: Could be in cart, product details, receipt
- **Impact**: Users don't understand how bulk quantities convert
- **Fix**: Display conversion info in tooltips or receipt

### Gap 5: Legacy Fallback Logic Confusing
- **Issue**: Multiple fallback paths make code hard to follow:
  - `saleUoms` (new) → `saleUom` (legacy) → `purchaseUom` → `uom`
- **Location**: [SalesPage.vue](client/src/pages/Users/SalesPage.vue) lines 876-910, 217-230
- **Impact**: Code maintenance difficult, unclear when each path used
- **Fix**: Document or consolidate the logic

### Gap 6: Receipt Data Doesn't Include Product Details
- **Issue**: Receipt items lose reference to product, only store name/price
- **Location**: [SalesPage.vue](client/src/pages/Users/SalesPage.vue) line 1200
- **Impact**: Can't lookup original product to get UOM info
- **Fix**: Include product_id and uom_id in receipt items

### Gap 7: No UOM Availability Validation in Cart
- **Issue**: If a product's UOMs change after adding to cart, UI might show outdated options
- **Location**: Cart display logic assumes product.saleUoms is current
- **Impact**: Could select UOM that no longer exists on product
- **Fix**: Re-validate UOMs when displaying cart

---

## 9. Best Places to Implement Improved UOM Selector

### Location 1: Extract Reusable UOM Selector Component
**File**: Create `client/src/components/UOMSelector.vue`

**Current Code Location**: [ProductsPage.vue](client/src/pages/Users/ProductsPage.vue) lines 388-410

**Why**: Same pattern repeated in ProductsPage and SalesPage multiple times
- Multiple sale UOM selection with tags
- Add/remove functionality
- Display and validation

**Benefits**:
- Reduces code duplication
- Easier to maintain and enhance
- Can add features in one place (e.g., sort by size, filter)
- Better tested component

### Location 2: Enhance UOM Selection Modal
**File**: [SalesPage.vue](client/src/pages/Users/SalesPage.vue) lines 155-200 or extract to `components/UOMSelectionModal.vue`

**Current Issues**:
- No conversion ratio information displayed
- Basic button layout
- No visual indication of default UOM

**Enhancements**:
```html
<button @click="selectUoM(uom)" class="uom-option">
  <i class="fas fa-droplet"></i>
  <strong>{{ uom.name }}</strong>
  <small>{{ uom.abbreviation }}</small>
  
  <!-- NEW: Show conversion info -->
  <small v-if="uom.pivot?.conversion_ratio" class="conversion-hint">
    {{ uom.pivot.conversion_ratio }} units
  </small>
  
  <!-- NEW: Mark default -->
  <span v-if="uom.pivot?.is_default" class="badge default">Default</span>
</button>
```

### Location 3: Cart Item UOM Selector with Inline Display
**File**: [SalesPage.vue](client/src/pages/Users/SalesPage.vue) lines 200-230 or extract to `components/CartItemUOMSelector.vue`

**Current Issues**:
- Dropdown is narrow (width: 80px)
- No indication of current UOM value
- No visual feedback

**Enhancements**:
```html
<div class="item-uom-selector">
  <label>Unit:</label>
  <select v-model="item.uom_id" class="uom-select" @change="updateItemUoM(item)">
    <option v-for="uom in getProduct(item.id).saleUoms" :key="uom.id" :value="uom.id">
      {{ uom.abbreviation }}
    </option>
  </select>
  
  <!-- NEW: Show selected UOM full name as tooltip/text -->
  <span class="uom-display">
    {{ getUomName(item.uom_id) }}
  </span>
</div>
```

### Location 4: Receipt Display with UOM
**File**: [SalesPage.vue](client/src/pages/Users/SalesPage.vue) lines 430-475

**Current**: Just shows quantity (e.g., "5")

**Enhanced**:
```html
<div v-for="item in receiptData.items" :key="item.id" class="receipt-item">
  <span class="item-name">{{ item.name }}</span>
  
  <!-- NEW: Show quantity × UOM -->
  <span class="item-qty">
    {{ item.quantity }} 
    <span class="uom-abbr">{{ item.uom_abbreviation }}</span>
  </span>
  
  <span class="item-price">{{ item.price }}</span>
  <span class="item-total">{{ (item.price * item.quantity).toFixed(2) }}</span>
</div>
```

**Update receiptData** (lines 1200):
```javascript
items: cart.value.map(item => {
  const uom = getProduct(item.id)?.saleUoms?.find(u => u.id === item.uom_id)
  return {
    id: item.id,
    name: item.name,
    quantity: quantity,
    uom_abbreviation: uom?.abbreviation || '',  // ← NEW
    price: price.toFixed(2),
    total: (price * quantity).toFixed(2)
  }
})
```

### Location 5: Create Database Migration for Invoice UOM Tracking
**File**: Create `Server/database/migrations/2026_XX_XX_XXXXXX_add_uom_to_invoice_items.php`

**Purpose**: Store which UOM was used for each invoice line item

**Migration**:
```php
Schema::table('invoice_items', function (Blueprint $table) {
    $table->unsignedBigInteger('uom_id')->nullable()->after('product_id');
    $table->string('uom_abbreviation', 20)->nullable()->after('uom_id');
    $table->foreign('uom_id')->references('id')->on('u_o_m_s')->onDelete('set null');
});
```

**Update InvoiceItem Model**:
```php
protected $fillable = [
    'invoice_id', 'product_id', 'uom_id', 'uom_abbreviation',
    'description', 'quantity', 'unit_price', 'total_price'
];

public function uom()
{
    return $this->belongsTo(UOM::class);
}
```

### Location 6: Add Conversion Ratio Display Component
**File**: Create `client/src/components/ConversionRatioDisplay.vue`

**Purpose**: Show how units convert (e.g., "1L = 4 × 250ml")

**Usage**:
```html
<ConversionRatioDisplay 
  :product="product" 
  :selectedUomId="item.uom_id"
  :conversionRatio="product.conversion_ratio"
/>
```

**Output**: "1 Liter = 4 × 250ml"

---

## 10. Recommended Implementation Order

1. **Quick Win**: Add UOM display to receipt
   - Modify `receiptData` to include `uom_abbreviation`
   - Update receipt template
   - ~30 minutes

2. **Component Extraction**: Create `UOMSelector.vue`
   - Extract from ProductsPage
   - Reuse in both product form and sales
   - ~1 hour

3. **Enhance Cart Display**: Improve UOM selector styling
   - Better visual feedback
   - Show full UOM name
   - ~45 minutes

4. **Database**: Add UOM to invoices
   - Create migration
   - Update InvoiceItem model
   - ~30 minutes

5. **Advanced**: Create ConversionRatioDisplay component
   - Show conversion information
   - Add to cart/receipt/product
   - ~1 hour

6. **Server Validation**: Add UOM availability check
   - Validate in SaleController
   - Return clear error if invalid
   - ~30 minutes

---

## 11. Summary Table: UOM Handling Across Modules

| Module | File | Line | UOM Field | Notes |
|--------|------|------|-----------|-------|
| **Product Definition** | ProductsPage.vue | 388-410 | sale_uom_ids[] | Multiple selection UI |
| **Product Creation** | ProductController.php | 241 | sync(product_sale_uoms) | Stores many-to-many |
| **Product Fetching** | ProductController.php | 162 | saleUoms loaded | Relationship preloaded |
| **Product Adding** | SalesPage.vue | 876 | uoMSelectorProduct | Store pending item |
| **UOM Selection** | SalesPage.vue | 155 | selectUoM() | Modal button handler |
| **Cart Display** | SalesPage.vue | 200 | item.uom_id | Dropdown selector |
| **Cart Submit** | SalesPage.vue | 1195 | uom_id field | Sent to backend |
| **Sale Creation** | SaleController.php | 16 | items.*.uom_id | Validated & stored |
| **SaleItem Storage** | SaleItem.php | fillable | uom_id | Stored in DB |
| **Receipt Display** | SalesPage.vue | 430 | ⚠️ MISSING | Not shown to user |
| **Invoice Storage** | InvoiceItem.php | fillable | ⚠️ MISSING | Not tracked |

---

## Conclusion

The UOM system is **well-designed at the data level** with proper many-to-many relationships and API support. However, **visibility gaps** exist:

1. ✅ Data structure is solid (product_sale_uoms junction table)
2. ✅ API correctly loads all UOM relationships
3. ✅ Cart properly tracks selected UOM
4. ✅ Backend stores UOM in SaleItem
5. ❌ **Receipt doesn't display UOM** - user sees "5" instead of "5 × 250ml"
6. ❌ **Invoices don't store UOM** - historical data lost
7. ⚠️ No server-side validation of UOM availability

**Priority fixes**: Receipts → Invoices → Validation → Components refactoring
