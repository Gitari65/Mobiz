# UOM-Specific Pricing Feature

## 🎯 Overview

You can now set **different selling prices for each Unit of Measure (UOM)** and automatically calculate profit margins based on your **purchase cost and conversion ratio**.

---

## 📊 How It Works

### **Example Scenario**

You buy Coca Cola in **cartons** (purchase unit):
- **Cost per carton**: 2,400 KSH
- **Contents**: 24 × 250ml units
- **You sell in 3 different UOMs**: 250ml, 500ml, 1L

### **Pricing Calculation**

```
Purchase: 1 carton = 2,400 KSH
Contains: 24 × 250ml units

Cost per unit = 2,400 ÷ 24 = 100 KSH per 250ml

Selling prices (you set):
  • 250ml: 200 KSH  → Margin: (200-100)/100 = 100%
  • 500ml: 350 KSH  → Margin: (350-100)/100 = 250%
  • 1L: 650 KSH     → Margin: (650-100)/100 = 550%
```

---

## 🔄 Step-by-Step: Add Product with UOM Pricing

### **Step 1: Basic Product Info**
1. Go to **Products Management**
2. Click **Edit Product** (or add new)
3. Fill in:
   - Product Name: `Coca Cola`
   - Category, Brand, Description

### **Step 2: Set Up Purchase & Sale UOMs**
```
Purchase UOM (How you buy)
  → Select: "Carton" (or your bulk unit)

Sale UOMs (How you sell)
  → Select: 250ml, 500ml, 1L
```

### **Step 3: Enter Pricing**
Go to **Pricing** section:
```
Cost Price (Ksh): 2400
  ↓
Selling Price (Ksh): 200  ← Base price (for reference)

Base Profit Margin: 91.7%   ← Shows base margin
```

### **Step 4: Set Purchase → Sale Conversion**
```
Conversion Ratio: 24
  ↓
Hint: "24 × 250ml = 1 carton"
```

### **Step 5: Set Individual UOM Prices**

New section appears: **"Prices by Unit of Measure"**

```
┌─────────────────────────────────────────┐
│ UOM               │ Selling Price │ Margin │
├─────────────────────────────────────────┤
│ 250ml (Small)     │ [200]         │ 100.0% │
│ 500ml (Medium)    │ [350]         │ 250.0% │
│ 1L (Large)        │ [650]         │ 550.0% │
└─────────────────────────────────────────┘
```

- Type selling price for each UOM
- **Margin % automatically updates** as you type
- Margin is color-coded:
  - 🟢 **Green** (50%+): Excellent profit
  - 🔵 **Blue** (20-50%): Good profit
  - 🟡 **Orange** (<20%): Low profit
  - 🔴 **Red** (negative): Loss

### **Step 6: Quick Price Setting (Optional)**

Below the table:
```
Quick Set: Apply Markup to All UOMs
  
Input: [50]%  [Apply] button

Calculates price = cost_per_uom × (1 + markup%)
```

Example: If you enter **50%** markup:
- Cost per 250ml: 100 KSH
- Price with 50% markup: 100 × 1.5 = **150 KSH**
- Applied to all selected UOMs

### **Step 7: Save**
Click **"Update Product"** to save all UOM prices

---

## 💡 Key Features

### **Automatic Margin Calculation**
- Considers **purchase cost** (not selling price)
- Accounts for **conversion ratio** automatically
- Shows margin for **each UOM individually**

### **Smart Pricing Helper**
- Quickly apply same markup % to all UOMs
- Saves time for bulk price updates
- Recalculates all margins instantly

### **Visual Feedback**
- Color-coded margin percentages
- Shows "-" if price not set yet
- Real-time calculations as you type

### **Flexible Pricing**
- Each UOM can have **completely different price**
- No lock-in to base selling price
- Easy to adjust individual prices later

---

## 🔢 Margin Calculation Logic

### **The Formula**

```
Cost per UOM = Purchase_Price ÷ Conversion_Ratio

Margin % = ((Selling_Price - Cost_per_UOM) ÷ Cost_per_UOM) × 100
```

### **Real Example**
```
Purchase price: 2,400 KSH
Conversion ratio: 24

Cost per unit (250ml) = 2,400 ÷ 24 = 100 KSH

If selling 250ml for 200 KSH:
Margin = ((200 - 100) ÷ 100) × 100 = 100%
```

---

## 📋 When You Edit a Product

Opening an existing product:
1. All previously saved UOM prices **load automatically**
2. Margins recalculate based on current cost_price
3. Can modify prices and see new margins instantly
4. Apply bulk markup to update all at once

---

## ⚠️ Important Notes

### **What if Conversion Ratio is Missing?**
- Margin calculation assumes 1:1 ratio if not set
- Set conversion_ratio for accurate margins
- Example: If you don't set it, system treats each unit as 1 unit cost

### **What if UOM Price Isn't Set?**
- Shows "-" in margin column
- Product can still be saved (not required)
- User will set price when adding to cart (fallback to base price)

### **Base Selling Price vs UOM Prices**
- **Base Selling Price**: Reference price, used for old products
- **UOM Prices**: Specific prices for each UOM (overrides base)
- If UOM price not set, falls back to base selling price

---

## 🎨 UI Components

### **Pricing Table**
- Header: UOM | Selling Price | Margin %
- Editable input for prices
- Auto-calculated margin display
- One row per selected UOM

### **Markup Helper**
- Input field: percentage
- Apply button: sets all UOMs at once
- Shows success alert on apply

### **Color Coding**
```
🟢 50%+        → Excellent  (rgba(72, 187, 120, 0.1))
🔵 20-50%      → Good       (rgba(102, 126, 234, 0.1))
🟡 <20%        → Low        (rgba(245, 158, 11, 0.1))
🔴 Negative    → Loss       (rgba(245, 101, 101, 0.1))
```

---

## 🚀 Example Workflows

### **Workflow 1: Quick Pricing Setup**

1. Edit product
2. Set conversion ratio: `24`
3. Set cost price: `2400`
4. In markup helper, enter: `50`
5. Click **Apply**
6. All UOMs now priced with 50% markup
7. Save

✅ Done in 1 minute!

### **Workflow 2: Custom Pricing Per Size**

1. Edit product
2. Set base prices:
   - Small (250ml): 180 KSH
   - Medium (500ml): 350 KSH  
   - Large (1L): 650 KSH
3. Watch margins update as you type
4. Adjust individual prices to fine-tune margins
5. Save

✅ Complete control over pricing!

### **Workflow 3: Markdown for Promotion**

1. Edit product
2. Current prices:
   - 250ml: 200 KSH (100% margin)
   - 500ml: 350 KSH (250% margin)
   - 1L: 650 KSH (550% margin)
3. For promotion, change 250ml to 150 KSH
4. Margin shows negative or low
5. Save (can temporarily accept lower margin)

✅ Promotional pricing support!

---

## 🔧 Technical Details

### **Data Stored**
```javascript
product: {
  id: 5,
  name: "Coca Cola",
  cost_price: 2400,
  price: 200,              // Base price
  sale_uom_ids: [1, 2, 3],
  conversion_ratio: 24,
  uom_prices: {            // NEW: Individual prices
    "1": 200,              // 250ml
    "2": 350,              // 500ml
    "3": 650               // 1L
  }
}
```

### **API Endpoint**
```
PUT /products/{id}
{
  "name": "Coca Cola",
  "cost_price": 2400,
  "price": 200,
  "sale_uom_ids": [1, 2, 3],
  "conversion_ratio": 24,
  "uom_prices": {"1": 200, "2": 350, "3": 650}
}
```

---

## ✅ Testing Checklist

- [ ] Edit a product with multiple UOMs
- [ ] See UOM pricing section with table
- [ ] Enter prices for each UOM
- [ ] Verify margins calculate correctly
- [ ] Use markup helper to set all at 50%
- [ ] Save and reload - prices persist
- [ ] Try negative margin scenario
- [ ] Change conversion ratio - margins update

---

## 📞 Troubleshooting

| Issue | Solution |
|-------|----------|
| UOM pricing section not showing | Select multiple UOMs first, then open edit modal |
| Margins showing "-" | Enter selling price for each UOM |
| Calculation seems wrong | Check cost_price and conversion_ratio are set |
| Can't edit prices | Ensure you're in edit mode (not add mode) |
| Markup not applying | Check all fields have values |

---

## 🎯 Next Steps

1. **Test the feature** with existing product
2. **Adjust prices** for different UOMs
3. **Use markup helper** for quick setup
4. **Save and verify** prices persist
5. **Add to cart** and confirm UOM prices are used

**Ready to sell smarter with UOM-specific pricing!** 💰

