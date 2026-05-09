# Intelligent UOM Filtering - Quick Setup

## ⚡ 3-Step Setup (2 minutes)

### **Step 1: Run Migration**
```bash
cd Server
php artisan migrate
```

### **Step 2: Seed UOM Types**
```bash
php artisan db:seed --class=UpdateUomTypesSeeder
```

### **Step 3: Restart Frontend**
```bash
cd ../client
npm run dev
```

---

## ✅ That's It!

The system now intelligently filters UOMs by type.

---

## 🧪 Quick Test

1. Open browser → Products Management
2. Click **Edit** on any product
3. Select a **Purchase UOM** (e.g., "Litre")
4. Look at **Sale UOMs** dropdown
5. ✓ Should only show volume UOMs (ml, L, dl, etc.)
6. Select different purchase UOM → Sale UOMs update automatically ✓

---

## 📊 What Gets Categorized

| If you select... | You'll see... |
|------------------|---------------|
| Litre | ml, 250ml, 500ml, 750ml, L |
| Kilogram | g, 250g, 500g, kg |
| Metre | cm, m, mm, km |
| Carton | pcs, box, dz, ctn, bottle, can, pack |

---

## 💻 Commands Reference

```bash
# Check migration status
php artisan migrate:status

# Verify UOM types were set
php artisan tinker
>>> DB::table('units_of_measure')->pluck('type')->unique();

# Count UOMs by type
>>> DB::table('units_of_measure')->groupBy('type')->selectRaw('type, count(*) as count')->get();

# Exit tinker
>>> exit
```

---

## 🔧 If Something Goes Wrong

**Frontend not showing filtered UOMs?**
1. Hard refresh: `Ctrl+Shift+R`
2. Clear browser cache: `Ctrl+Shift+Delete`
3. Restart dev server: Stop (Ctrl+C), then `npm run dev`

**UOMs not in database?**
1. Check migration: `php artisan migrate:status` (should show 2026_04_22 as yes)
2. Re-run seeder: `php artisan db:seed --class=UpdateUomTypesSeeder`

**API not returning type field?**
1. Verify UOMController was updated (should have type filtering code)
2. Test endpoint: `curl http://127.0.0.1:8000/api/uoms | grep type`

---

## 📚 Full Documentation

See [INTELLIGENT_UOM_FILTERING.md](INTELLIGENT_UOM_FILTERING.md) for:
- Detailed explanations
- Real-world examples
- Troubleshooting guide
- UOM type reference
- API documentation

---

## 🎯 You're Done!

Enjoy intelligent UOM filtering! 🚀

