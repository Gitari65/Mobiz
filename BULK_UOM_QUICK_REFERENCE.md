# Bulk UoM System - Quick Reference

## Setup in 3 Steps

### 1️⃣ Create Product with Bulk UoM
- Go to **Products** page
- Click **Add Product**
- Fill basic details (Name, Price, Stock)
- **NEW FIELDS:**
  - Purchase UOM: Select bulk unit (e.g., 50L)
  - Sale UOM: Select retail unit (e.g., 1L or 250ml)
  - Conversion Ratio: Enter how many sale units per purchase unit
    - Example: 50 (meaning 1 × 50L = 50 × 1L)

### 2️⃣ Set Initial Stock in Purchase Units
- Stock Quantity = Number of bulk units
- Example: "2" means 2 × 50L containers = 100L total = 100 × 1L bottles

### 3️⃣ Sell with Automatic Conversion
- Go to **Sales** page
- Products show available quantity in **sale units**
- Add to cart in retail quantities
- System automatically handles conversion at checkout

---

## Common Scenarios

### Juice/Beverage Business
```
SETUP:
- Purchase UOM: 50L (bulk container)
- Sale UOM: 1L (retail bottle)
- Conversion: 50
- Initial Stock: 2 (meaning 2 containers = 100 bottles)

SELLING:
- Display: "100 1L in stock"
- Customer buys: 25 bottles
- Stock after: 75 bottles (1.5 containers remaining)
```

### Grain/Flour Business
```
SETUP:
- Purchase UOM: 25kg (bulk bag)
- Sale UOM: 500g (retail pack)
- Conversion: 50
- Initial Stock: 4 (meaning 4 bags = 200 packs)

SELLING:
- Display: "200 500g in stock"
- Customer buys: 10 packs
- Stock after: 190 packs (3.8 bags remaining)
```

### Medicine/Cosmetics
```
SETUP:
- Purchase UOM: 5L (bulk drum)
- Sale UOM: 100ml (retail bottle)
- Conversion: 50
- Initial Stock: 5 (meaning 5 drums = 250 bottles)

SELLING:
- Display: "250 100ml in stock"
- Customer buys: 30 bottles
- Stock after: 220 bottles (4.4 drums remaining)
```

---

## Key Features

| Feature | Benefit |
|---------|---------|
| **Dual UoM** | Buy bulk, sell retail without manual conversions |
| **Auto-Conversion** | Stock automatically calculated in sale units |
| **Fractional Tracking** | Handles 1.5 containers perfectly |
| **Stock Validation** | Prevents overselling in sale units |
| **Sale UoM Display** | Customers see what they're buying |
| **Conversion Visibility** | Shows "50 × 1L = 1 × 50L" hint |

---

## Database Fields

### In Products Table:
- `purchase_uom_id` - Bulk unit
- `sale_uom_id` - Retail unit  
- `conversion_ratio` - Sale units per purchase unit
- `track_by_purchase_unit` - Always true

### In Sale Items Table:
- `uom_id` - Which UoM was used for sale
- `quantity` - Now supports decimals (12,4 precision)

---

## Formulas

### Calculate Available Sale Units:
```
Available Retail = Stock Quantity × Conversion Ratio
Example: 2 × 50 = 100 bottles available
```

### Deduct Stock from Sale:
```
Purchase Units to Deduct = Sale Units Sold ÷ Conversion Ratio
Example: 25 bottles ÷ 50 = 0.5 containers deducted
New Stock: 2 - 0.5 = 1.5 containers remaining
```

### Display Stock:
```
For Customer: "100 1L in stock"     ← Sale units with sale UoM
Internal: 2 containers stored       ← Purchase units tracked
```

---

## Important Notes

✅ **Stock is always stored in PURCHASE units**
- This prevents floating-point issues
- Fractional purchase units are supported (1.5 containers)

✅ **Conversions happen transparently**
- Customer sees sale units
- System handles conversion internally
- No manual calculation needed

✅ **Works with existing features**
- Compatible with price groups
- Works with promotions
- Supports credit sales
- Integrates with reporting

---

## Migration Commands

Run once to activate the feature:

```bash
cd Server
php artisan migrate
```

This adds the columns to products and sale_items tables.

---

## Troubleshooting

### Products showing wrong stock quantity?
- Check purchase_uom_id and sale_uom_id are set
- Verify conversion_ratio > 0
- Ensure initial stock is in purchase units

### Can't select UOMs?
- Make sure UOMs are created in system
- Check that UOM seeder has run: `php artisan db:seed --class=UOMSeeder`

### Fractional stock appearing?
- This is normal! 1.5 containers = 0.5 containers remaining from last sale
- Tracks fractional purchases correctly

---

## API Examples

### Create Product:
```json
POST /products
{
  "name": "Juice",
  "stock_quantity": 2,
  "purchase_uom_id": 5,
  "sale_uom_id": 8,
  "conversion_ratio": 50
}
```

### Make Sale:
```json
POST /sales
{
  "items": [
    {"product_id": 123, "quantity": 25, "price": 200}
  ]
}
```
System stores: quantity=25 (sale units), converts internally to -0.5 purchase units

---

## Support Needed?

Check the detailed documentation:
📖 [BULK_UOM_SYSTEM.md](./BULK_UOM_SYSTEM.md)

Topics covered:
- Complete database schema
- Backend model methods
- Frontend implementation
- Stock validation logic
- Reporting examples
- Edge cases
