# Intelligent UOM Filtering by Type

## 🎯 What Changed?

The UOM system now intelligently filters sale UOM choices based on the **purchase UOM type** you select.

### **Before**
- All 24 UOMs shown together (ml, kg, cm, pcs, etc.)
- Confusing - why would you mix volume and weight UOMs?

### **After**
- Select purchase UOM (e.g., "Litre")
- Only volume UOMs appear as sale options (ml, L, dl, etc.)
- Smart filtering by type: length, volume, weight, count, area

---

## 📦 UOM Types

The system now categorizes all UOMs into 5 types:

| Type | Examples | When to Use |
|------|----------|------------|
| **volume** | ml, L, 250ml, 500ml, 750ml | Liquids, beverages, oils |
| **weight** | g, kg, 250g, 500g | Dry goods, solids |
| **length** | cm, m, mm | Fabrics, pipes, measurements |
| **area** | m², cm² | Floor space, land |
| **count** | pcs, box, ctn, bottle, can, pack, dz | Individual items, packaging |

---

## 🚀 Installation Steps

### **Step 1: Run Database Migration**

This adds a `type` column to the `units_of_measure` table:

```bash
cd Server
php artisan migrate
```

**Migration file**: `database/migrations/2026_04_22_add_type_to_units_of_measure.php`

**What it does:**
- Adds `type` enum column with values: volume, weight, length, area, count, other
- Creates index on `type` column for faster queries
- Default value: 'other'

### **Step 2: Run Seeder to Categorize UOMs**

This populates the `type` field for all existing 24 system UOMs:

```bash
php artisan db:seed --class=UpdateUomTypesSeeder
```

**Seeder file**: `database/seeders/UpdateUomTypesSeeder.php`

**What it does:**
- Maps 250ml → volume
- Maps kg → weight
- Maps cm → length
- Maps pcs, box, ctn → count
- Maps m² → area
- Sets remaining UOMs to 'other'

### **Step 3: Restart Frontend Dev Server**

```bash
cd client
npm run dev
```

The frontend now has access to UOM type information through the updated API.

---

## 💻 How It Works

### **Backend**

**Updated Endpoint:** `GET /uoms`

Now supports type filtering:

```bash
# Get all UOMs
GET /uoms

# Get only volume UOMs (ml, L, dl, etc.)
GET /uoms?type=volume

# Get only weight UOMs (g, kg, etc.)
GET /uoms?type=weight

# Get only count UOMs (pcs, box, ctn, etc.)
GET /uoms?type=count
```

**UOM Model** now returns:
```json
{
  "id": 1,
  "name": "250 ml",
  "abbreviation": "250ml",
  "type": "volume",        // NEW
  "description": "Small bottle",
  "is_system": true
}
```

### **Frontend**

**New Method:** `getFilteredSaleUoms(purchaseUomId)`

```javascript
// Get UOMs that match the purchase UOM type
const filteredUoms = this.getFilteredSaleUoms(form.purchase_uom_id)

// Example:
// If purchase_uom_id = 4 (Carton, type: 'count')
// Returns only count-type UOMs: pcs, box, dz, ctn, etc.
```

**How the UI works:**

1. User selects **Purchase UOM** (e.g., "Carton")
2. System detects type = "count"
3. Sale UOM options are filtered to only count-type UOMs
4. User sees: pcs, box, ctn, bottle, can, dz, pack, pkt, bundle, pair
5. No volume, weight, or length UOMs shown ✓

---

## 🧪 Example Workflows

### **Workflow 1: Beverages (Volume)**

```
Product: Coca Cola

Step 1: Select Purchase UOM
  → Choose: "Litre" (type: volume)

Step 2: Sale UOMs appear (auto-filtered)
  ✓ Available: ml, dl, L, 250ml, 500ml, 750ml
  ✓ NOT shown: kg, g, cm, m, pcs, box

Step 3: Select sale UOMs
  → Choose: 250ml, 500ml, 1L

Step 4: Set prices per UOM
  → 250ml: 200 KSH
  → 500ml: 350 KSH
  → 1L: 650 KSH
```

### **Workflow 2: Fabric (Length)**

```
Product: Cotton Fabric

Step 1: Select Purchase UOM
  → Choose: "Metre" (type: length)

Step 2: Sale UOMs appear (auto-filtered)
  ✓ Available: cm, m, mm, km
  ✓ NOT shown: ml, kg, pcs, m²

Step 3: Select sale UOMs
  → Choose: cm, m

Step 4: Set prices per UOM
  → 1cm: 50 KSH
  → 1m: 5000 KSH
```

### **Workflow 3: Packaged Items (Count)**

```
Product: Pasta Boxes

Step 1: Select Purchase UOM
  → Choose: "Carton" (type: count)

Step 2: Sale UOMs appear (auto-filtered)
  ✓ Available: pcs, box, ctn, pack, dz, bundle
  ✓ NOT shown: ml, kg, cm, m, m²

Step 3: Select sale UOMs
  → Choose: pcs, box, dz

Step 4: Set prices per UOM
  → 1pcs: 150 KSH
  → 1box: 1500 KSH
  → 1dz: 18000 KSH
```

---

## 📊 UOM Type Mapping Reference

### **Volume UOMs**
```
ml, 250ml, 500ml, 750ml, dl, L, litre, liter, gallon
```

### **Weight UOMs**
```
mg, g, gram, 250g, 500g, kg, kilogram, ton
```

### **Length UOMs**
```
mm, cm, m, meter, metre, km, inch, ft, foot
```

### **Area UOMs**
```
m², sq m, cm², sq cm
```

### **Count UOMs**
```
pcs, piece, pc, dz, dozen, pack, pkt, packet, box, ctn, carton, 
crate, bottle, can, jar, bun, bundle, set, pair
```

---

## ✅ Verification Steps

### **1. Check Database**

```bash
php artisan tinker

# Check if type column exists
>>> DB::table('units_of_measure')->first();

# See a sample result
# Should show: "type" => "volume" (or other type)
```

### **2. Test API Endpoint**

```bash
# Get all UOMs
curl http://127.0.0.1:8000/api/uoms | jq

# Get only volume UOMs
curl http://127.0.0.1:8000/api/uoms?type=volume | jq

# Expected response includes "type" field for each UOM
```

### **3. Test Frontend**

1. Open Products page
2. Edit any product
3. Select a purchase UOM
4. Check that sale UOM options are filtered ✓
5. Switch purchase UOM
6. Sale UOM options should update immediately ✓

---

## 🔍 Troubleshooting

### **Problem: Type column not showing**
```bash
# Verify migration ran
php artisan migrate:status

# Check table structure
php artisan tinker
>>> Schema::getColumns('units_of_measure')
```

### **Problem: UOMs not categorized**
```bash
# Re-run seeder
php artisan db:seed --class=UpdateUomTypesSeeder

# Verify
php artisan tinker
>>> DB::table('units_of_measure')->count();  # Should show 24 + any custom
>>> DB::table('units_of_measure')->where('type', 'volume')->count();  # Should show volume UOMs
```

### **Problem: Frontend not filtering**
1. Hard refresh browser (Ctrl+Shift+R)
2. Check browser console (F12) for errors
3. Verify API returns type field:
   ```javascript
   // In browser console
   fetch('/api/uoms?type=volume')
     .then(r => r.json())
     .then(data => console.log(data))
   ```

---

## 🚀 Benefits

| Before | After |
|--------|-------|
| User sees 24 UOMs mixed together | User sees only relevant UOMs |
| Confusing to select sale UOM | Smart filtering by type |
| Easy to make mistakes (ml with kg) | Impossible to mix incompatible units |
| No context about unit relationships | Clear unit relationships shown |

---

## 📝 Files Modified/Created

### **Created:**
- `database/migrations/2026_04_22_add_type_to_units_of_measure.php`
- `database/seeders/UpdateUomTypesSeeder.php`

### **Modified:**
- `app/Http/Controllers/UOMController.php` - Added type filtering to index()
- `client/src/pages/Users/ProductsPage.vue` - Added getFilteredSaleUoms() method

### **No Changes Needed:**
- Product model (already supports multi-UOM)
- Database product table (UOMs linked via junction table)
- Frontend UOMSelector component (works with filtered list)

---

## 🎯 Next Steps

1. ✅ Run migration: `php artisan migrate`
2. ✅ Run seeder: `php artisan db:seed --class=UpdateUomTypesSeeder`
3. ✅ Restart frontend: `npm run dev`
4. ✅ Test in browser: Edit product, select purchase UOM, verify filtering
5. ✅ Add products with intelligent UOM pricing

---

## 💡 Future Enhancements

- [ ] Show unit conversion info (1L = 1000ml)
- [ ] Suggest related UOMs (if selecting 250ml, suggest 500ml, 1L)
- [ ] Auto-populate conversion ratios based on UOM type
- [ ] Show UOM type description on hover
- [ ] Add custom user UOM types

---

**Ready to use intelligent UOM filtering!** 🎯

