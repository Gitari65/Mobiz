# Default UOMs & Admin Customization Setup Guide

## 🎯 Overview

This guide sets up:
1. **Default System UOMs** - Pre-configured units of measure (L, Kg, G, M, Cm and related units)
2. **UOM Type Categorization** - Intelligent grouping (volume, weight, length, area, count)
3. **Admin Customization Panel** - New tab for managing UOMs

---

## 📦 What's Included

### Default System UOMs

The system comes with **40+ pre-configured UOMs** organized by type:

#### **Volume** (8 UOMs)
- ml, 250ml, 500ml, 750ml, dl, L, litre

#### **Weight** (7 UOMs)
- mg, g, 250g, 500g, kg, ton

#### **Length** (5 UOMs)
- mm, cm, m, meter, km

#### **Area** (2 UOMs)
- m², cm²

#### **Count** (13 UOMs)
- pcs, pc, dz, box, ctn, pack, pkt, bottle, can, jar, bundle, pair, set

---

## ⚡ Quick Setup (5 minutes)

### **Step 1: Run Database Migration**

Adds the `type` enum column to the `units_of_measure` table:

```bash
cd Server
php artisan migrate
```

Expected output:
```
Migrating: 2026_04_22_add_type_to_units_of_measure
Migrated: 2026_04_22_add_type_to_units_of_measure
```

### **Step 2: Seed Default UOMs**

Populates the database with default system UOMs:

```bash
php artisan db:seed --class=CreateDefaultUomsSeeder
```

Expected output:
```
Seeding: Database\Seeders\CreateDefaultUomsSeeder
Seeded: Database\Seeders\CreateDefaultUomsSeeder
```

### **Step 3: (Optional) Categorize Existing UOMs**

If you had existing UOMs, run this to add type information:

```bash
php artisan db:seed --class=UpdateUomTypesSeeder
```

### **Step 4: Restart Frontend**

```bash
cd ../client
npm run dev
```

---

## ✅ Verification

### Check Database

```bash
php artisan tinker

# Count UOMs by type
>>> DB::table('u_o_m_s')->groupBy('type')->selectRaw('type, count(*) as count')->get();

# Sample output:
=> Illuminate\Support\Collection {
     all: [
       {
         type: "volume",
         count: 7,
       },
       {
         type: "weight",
         count: 7,
       },
       ...
     ]
   }

# Check a specific UOM
>>> DB::table('u_o_m_s')->where('abbreviation', 'L')->first();

# Exit
>>> exit
```

### Test API Endpoint

```bash
# Get all UOMs
curl http://127.0.0.1:8000/api/uoms

# Get only volume UOMs
curl http://127.0.0.1:8000/api/uoms?type=volume

# Expected response: JSON array with type field
```

### Test Admin Panel

1. Open **Admin Settings** → **Customization**
2. Click the **UOMs** tab
3. Verify you see the list of UOMs
4. Try adding a new custom UOM
5. Try filtering by type (Volume, Weight, etc.)

---

## 🎛️ Admin UOM Management

### Features

✅ **View All UOMs** - See system and custom UOMs  
✅ **Filter by Type** - Quickly find UOMs by category  
✅ **Add Custom UOMs** - Create business-specific units  
✅ **Edit Custom UOMs** - Modify name, abbreviation, type, description  
✅ **Delete Custom UOMs** - Remove unused UOMs  
✅ **System UOM Protection** - Cannot edit/delete system UOMs  

### Admin Panel Workflow

**Adding a Custom UOM:**

1. Go to **Admin Settings** → **Customization**
2. Click **UOMs** tab
3. Fill in the form:
   - **UOM Name**: e.g., "Carton"
   - **Abbreviation**: e.g., "ctn"
   - **Type**: Select from dropdown (volume, weight, length, area, count, other)
   - **Description**: Optional notes
4. Click **Add UOM**
5. New UOM appears in the table

**Editing a Custom UOM:**

1. Click **Edit** button on any custom UOM row
2. Modify the details
3. Click **Update UOM**
4. Changes applied immediately

**Deleting a Custom UOM:**

1. Click **Delete** button on any custom UOM row
2. Confirm deletion
3. UOM removed from system

**Filtering by Type:**

1. Click any type button in the filter bar
2. Table shows only UOMs of that type
3. Click again to remove filter

---

## 📊 UOM Types Reference

| Type | Examples | Use Case |
|------|----------|----------|
| **volume** | ml, L, 250ml, 500ml, 750ml, dl | Beverages, oils, liquids |
| **weight** | g, kg, 250g, 500g, mg, ton | Dry goods, solids |
| **length** | cm, m, mm, km | Fabrics, pipes, wood |
| **area** | m², cm² | Floor space, land, sheets |
| **count** | pcs, box, ctn, dz, bottle, can, jar, pack | Items, packaging, sets |
| **other** | Custom units | Not in standard categories |

---

## 🔌 API Endpoints

### Get All UOMs
```
GET /api/uoms
Response: Array of all UOMs with type field
```

### Get UOMs by Type
```
GET /api/uoms?type=volume
Response: Only UOMs matching type=volume
```

### Create UOM
```
POST /api/uoms
{
  "name": "Custom Unit",
  "abbreviation": "cu",
  "type": "other",
  "description": "Optional description"
}
Response: 201 Created
```

### Update UOM
```
PUT /api/uoms/{id}
{
  "name": "Updated Name",
  "abbreviation": "un",
  "type": "volume",
  "description": "Updated description"
}
Response: 200 OK (only custom UOMs)
```

### Delete UOM
```
DELETE /api/uoms/{id}
Response: 200 OK (only custom UOMs, cannot delete system UOMs)
```

---

## 🛡️ System UOM Protection

**System UOMs** (marked with 🔒):
- Are read-only
- Show lock icon in admin panel
- Cannot be edited or deleted
- Have `is_system: true` in database

**Custom UOMs**:
- Are fully editable
- Can be deleted if not in use
- Should not be marked as `is_system`

---

## 🚨 Troubleshooting

### Problem: Type column not showing
```bash
# Verify migration ran
php artisan migrate:status

# If not showing, run migration again
php artisan migrate

# Check table structure
php artisan tinker
>>> Schema::getColumns('u_o_m_s')
```

### Problem: Default UOMs not created
```bash
# Check seeder status
php artisan db:seed --class=CreateDefaultUomsSeeder

# Verify count
php artisan tinker
>>> DB::table('u_o_m_s')->count();
```

### Problem: Admin panel not showing UOM tab
1. Hard refresh browser (Ctrl+Shift+R)
2. Clear browser cache
3. Check browser console (F12) for errors
4. Verify API endpoint works:
   ```javascript
   fetch('/api/uoms')
     .then(r => r.json())
     .then(data => console.log(data))
   ```

### Problem: Cannot add custom UOM
- Ensure name and abbreviation are unique
- Check for duplicate abbreviations in database:
  ```bash
  php artisan tinker
  >>> DB::table('u_o_m_s')->pluck('abbreviation')->duplicates();
  ```

---

## 📝 Files Created/Modified

### Created Files:
- `database/migrations/2026_04_22_add_type_to_units_of_measure.php` - Type field migration
- `database/seeders/CreateDefaultUomsSeeder.php` - Default UOMs seeder
- `database/seeders/UpdateUomTypesSeeder.php` - Type categorization seeder

### Modified Files:
- `app/Models/UOM.php` - Added 'type' to fillable array
- `app/Http/Controllers/UOMController.php` - Full CRUD + type validation
- `routes/api.php` - Added UOM API routes
- `client/src/pages/Admin/AdminCustomizationPage.vue` - Added UOM management tab

---

## 🎯 Next Steps

1. ✅ Run migration and seeders
2. ✅ Restart frontend dev server
3. ✅ Test admin UOM management panel
4. ✅ Create custom UOMs as needed
5. ✅ Update product pricing to use filtered UOMs by type

---

## 📚 Related Documentation

- [Intelligent UOM Filtering](INTELLIGENT_UOM_FILTERING.md) - Auto-filter sale UOMs based on purchase UOM type
- [Quick Reference](UOM_SELECTOR_QUICK_REFERENCE.md) - UOM selector component guide
- [UOM System Implementation](UOM_SYSTEM_IMPLEMENTATION.md) - Complete UOM system details

---

## 💡 Tips

**Best Practices:**
- Keep abbreviations short and memorable (ml, not mL)
- Use consistent capitalization (kg, not Kg)
- Set appropriate types for intelligent filtering to work
- Test custom UOMs before using in products

**Performance:**
- UOM lists are cached in browser after first load
- Type filtering happens on frontend for instant response
- System UOMs marked as read-only for data integrity

---

## ✨ Features Unlocked

With default UOMs configured:

✅ Products can use volume/weight/length/count/area units  
✅ Admin panel provides easy UOM management  
✅ Custom UOMs can be added by admin  
✅ Intelligent filtering by type in product forms  
✅ API supports type-based filtering  
✅ System UOMs protected from accidental deletion  
✅ Database properly categorized with type field  

---

**You're all set!** Default UOMs are ready to use. 🚀

