# Default UOMs - Quick Start (2 minutes)

## ⚡ Setup Steps

### 1️⃣ Database Migration
```bash
cd Server
php artisan migrate
```

### 2️⃣ Seed Default UOMs
```bash
php artisan db:seed --class=CreateDefaultUomsSeeder
```

### 3️⃣ Restart Frontend
```bash
cd ../client
npm run dev
```

---

## 📊 What You Get

**40+ Pre-configured UOMs:**
- Volume: ml, 250ml, 500ml, 750ml, dl, L, litre
- Weight: mg, g, 250g, 500g, kg, ton
- Length: mm, cm, m, meter, km
- Area: m², cm²
- Count: pcs, box, ctn, dz, bottle, can, jar, bundle, pair, set

---

## 🎛️ Admin Panel

**New UOM Management Tab** in Admin Settings → Customization

✅ View all UOMs by type  
✅ Add custom UOMs  
✅ Edit existing custom UOMs  
✅ Delete UOMs  
✅ Filter by type  
✅ System UOMs are protected  

---

## 🧪 Test It

1. **Admin Panel**: Settings → Admin Customization → UOMs tab
2. **See** the list of UOMs organized by type
3. **Filter** by clicking Volume, Weight, Length, Area, Count
4. **Add** a custom UOM using the form
5. **Edit** or **Delete** custom UOMs as needed

---

## 🔗 API Endpoints

```bash
# Get all UOMs
GET /api/uoms

# Get specific type
GET /api/uoms?type=volume
GET /api/uoms?type=weight
GET /api/uoms?type=length

# Create UOM
POST /api/uoms
{
  "name": "Unit Name",
  "abbreviation": "un",
  "type": "volume",
  "description": "Optional"
}

# Update UOM
PUT /api/uoms/{id}

# Delete UOM
DELETE /api/uoms/{id}
```

---

## 💾 Database

**New Column**: `type` enum field  
**Values**: volume | weight | length | area | count | other

**Table**: `u_o_m_s`

---

## ✨ You're Done!

Default UOMs are ready. Enjoy intelligent UOM filtering in products! 🎯

