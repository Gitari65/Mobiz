# Default UOMs Inventory

## 📦 Complete List of Default System UOMs

Automatically seeded by `CreateDefaultUomsSeeder.php`

### 🌊 VOLUME (7 UOMs)

| # | Name | Abbreviation | Description |
|---|------|--------------|-------------|
| 1 | Millilitre | ml | Small liquid measurement |
| 2 | 250 Millilitre | 250ml | Quarter litre bottle |
| 3 | 500 Millilitre | 500ml | Half litre bottle |
| 4 | 750 Millilitre | 750ml | Three-quarter litre bottle |
| 5 | Decilitre | dl | One-tenth litre |
| 6 | Litre | L | Standard liquid measurement |
| 7 | Litre | litre | Alternative to L |

**Use For**: Beverages, oils, juices, syrups, milk, water

---

### ⚖️ WEIGHT (7 UOMs)

| # | Name | Abbreviation | Description |
|---|------|--------------|-------------|
| 1 | Milligram | mg | Small weight measurement |
| 2 | Gram | g | Basic weight unit |
| 3 | 250 Gram | 250g | Quarter kilogram |
| 4 | 500 Gram | 500g | Half kilogram |
| 5 | Kilogram | kg | Standard weight measurement |
| 6 | Ton | ton | Large weight measurement |

**Use For**: Rice, flour, sugar, spices, grains, dry goods, meat

---

### 📏 LENGTH (5 UOMs)

| # | Name | Abbreviation | Description |
|---|------|--------------|-------------|
| 1 | Millimetre | mm | Small length measurement |
| 2 | Centimetre | cm | Centimetre measurement |
| 3 | Metre | m | Standard length measurement |
| 4 | Metre | meter | Alternative to m |
| 5 | Kilometre | km | Large distance measurement |

**Use For**: Fabrics, rope, pipes, wood, fencing, wires

---

### 📐 AREA (2 UOMs)

| # | Name | Abbreviation | Description |
|---|------|--------------|-------------|
| 1 | Square Metre | m² | Area measurement |
| 2 | Square Centimetre | cm² | Small area measurement |

**Use For**: Floor space, land, tiles, sheets, surfaces

---

### 🎁 COUNT (13 UOMs)

| # | Name | Abbreviation | Description |
|---|------|--------------|-------------|
| 1 | Piece | pcs | Individual item |
| 2 | Piece | pc | Alternative to pcs |
| 3 | Dozen | dz | Set of 12 items |
| 4 | Box | box | Boxed items |
| 5 | Carton | ctn | Carton packaging |
| 6 | Pack | pack | Package of items |
| 7 | Packet | pkt | Small packet |
| 8 | Bottle | bottle | Bottled product |
| 9 | Can | can | Canned product |
| 10 | Jar | jar | Jar packaging |
| 11 | Bundle | bundle | Bundled items |
| 12 | Pair | pair | Set of 2 items |
| 13 | Set | set | Set of items |

**Use For**: Individual items, packaged goods, beverages, canned items

---

## 🎯 Type Mapping

```
VOLUME TYPE:  ml, 250ml, 500ml, 750ml, dl, L, litre
WEIGHT TYPE:  mg, g, 250g, 500g, kg, ton
LENGTH TYPE:  mm, cm, m, meter, km
AREA TYPE:    m², cm²
COUNT TYPE:   pcs, pc, dz, box, ctn, pack, pkt, bottle, can, jar, bundle, pair, set
```

---

## 📌 Characteristics

All default UOMs have:
- ✅ `is_system: true` - Protected from deletion
- ✅ `type` - Categorized for intelligent filtering
- ✅ `description` - Clear purpose
- ✅ `abbreviation` - Short form for quick reference

---

## 🔄 Purchase vs Sale Examples

### Example 1: Beverage (Volume)

**Purchase UOM**: Litre (L)
- Type: volume
- Bulk buying: 1000L per order

**Sale UOMs Available**:
- ml (millilitre)
- 250ml (small bottle)
- 500ml (medium bottle)
- 750ml (large bottle)
- L (full litre)

### Example 2: Fabric (Length)

**Purchase UOM**: Metre (m)
- Type: length
- Bulk buying: 100m per order

**Sale UOMs Available**:
- cm (centimetre)
- m (metre)
- mm (millimetre)

### Example 3: Packaging (Count)

**Purchase UOM**: Carton (ctn)
- Type: count
- Bulk buying: 50 cartons per order

**Sale UOMs Available**:
- pcs (single piece)
- box (box of items)
- ctn (carton)
- dz (dozen)
- pack (pack)

---

## 🛠️ How to Add More

Custom UOMs can be added through:

1. **Admin Panel**: Admin Settings → Customization → UOMs tab
2. **API**: POST /api/uoms with name, abbreviation, type
3. **Database**: Direct insertion into u_o_m_s table

**Important**: Keep system UOMs as-is, only modify custom ones.

---

## 💡 Best Practices

✅ **Do**:
- Use these default UOMs as-is
- Add custom business-specific UOMs
- Set correct type for each UOM
- Keep abbreviations short and clear

❌ **Don't**:
- Modify system UOMs
- Delete default UOMs
- Duplicate abbreviations
- Mix types (e.g., ml for weight)

---

## 📈 Scaling

System supports:
- Unlimited custom UOMs
- Multiple UOMs per purchase UOM type
- Type-based intelligent filtering
- API access for programmatic use

---

## 🎓 Learning Resources

See also:
- [Intelligent UOM Filtering Guide](INTELLIGENT_UOM_FILTERING.md)
- [UOM Setup Guide](DEFAULT_UOMS_SETUP.md)
- [Admin Customization](DEFAULT_UOMS_QUICK_START.md)

---

**40+ default UOMs ready to use!** 🚀

