# Empties/Returnables - Quick Start Guide

## Example: Soda Bottles and Crates

### Scenario
You sell soda in crates. Each crate contains 24 bottles. Both the crate and bottles are returnable with deposits.

### Step 1: Create the Products

#### Main Product
```
Name: Soda Crate (24x500ml)
SKU: SODA-CRATE-24
Price: KES 1,200
Stock: 50 crates
```

#### Empty Products
```
1. Empty Crate
   Name: Empty Soda Crate
   SKU: EMP-CRATE
   Price: KES 0 (deposit only)
   Stock: 100

2. Empty Bottle
   Name: Empty 500ml Bottle
   SKU: EMP-BOTTLE-500
   Price: KES 0 (deposit only)
   Stock: 1000
```

### Step 2: Link Empties to Main Product

Navigate to Products → Click recycle icon on "Soda Crate (24x500ml)"

**Link 1: Empty Crate**
- Select Product: Empty Soda Crate
- Quantity per Unit: 1 (one crate per crate sold)
- Deposit Amount: KES 100
- Click "Link"

**Link 2: Empty Bottles**
- Select Product: Empty 500ml Bottle
- Quantity per Unit: 24 (24 bottles per crate)
- Deposit Amount: KES 10
- Click "Link"

### Step 3: Test a Sale

**Customer Order:**
- 3x Soda Crate (24x500ml) @ KES 1,200 each

**System Automatically Calculates:**

| Item | Quantity | Unit Price | Total |
|------|----------|------------|-------|
| Soda Crate (24x500ml) | 3 | KES 1,200 | KES 3,600 |
| Empty Soda Crate (deposit) | 3 | KES 100 | KES 300 |
| Empty 500ml Bottle (deposit) | 72 | KES 10 | KES 720 |
| **TOTAL** | | | **KES 4,620** |

**Breakdown:**
- Product value: KES 3,600
- Crate deposits: KES 300 (3 crates × KES 100)
- Bottle deposits: KES 720 (72 bottles × KES 10)

### Step 4: Customer Returns Empties

When customer returns the empties, you:
1. Refund the deposit (KES 1,020)
2. Add empties back to inventory
3. Resell them with new products

## Example 2: Gas Cylinder

### Products Setup

**Main Product:**
```
Name: LPG Gas 13kg
SKU: GAS-13KG
Price: KES 2,500
Stock: 30
```

**Empty Product:**
```
Name: 13kg Gas Cylinder
SKU: EMP-CYL-13KG
Price: KES 0
Stock: 50
```

### Link Empty
- Product: LPG Gas 13kg
- Empty: 13kg Gas Cylinder
- Quantity: 1
- Deposit: KES 1,500

### Sale Transaction

**Customer Order:**
- 1x LPG Gas 13kg @ KES 2,500

**Auto-calculated:**
- 1x LPG Gas 13kg: KES 2,500
- 1x 13kg Gas Cylinder (deposit): KES 1,500
- **Total: KES 4,000**

## Example 3: Milk Jerry Can

### Products Setup

**Main Product:**
```
Name: Fresh Milk 20L
SKU: MILK-20L
Price: KES 1,800
Stock: 20
```

**Empty Product:**
```
Name: 20L Jerry Can
SKU: EMP-JERRY-20L
Price: KES 0
Stock: 30
```

### Link Empty
- Product: Fresh Milk 20L
- Empty: 20L Jerry Can
- Quantity: 1
- Deposit: KES 200

### Sale Transaction

**Customer Order:**
- 2x Fresh Milk 20L @ KES 1,800

**Auto-calculated:**
- 2x Fresh Milk 20L: KES 3,600
- 2x 20L Jerry Can (deposit): KES 400
- **Total: KES 4,000**

## Tips for Success

### 1. Organize Your Products
Create a category for "Empties" or "Returnables" to easily find them when linking.

### 2. Standard Naming
Use consistent naming:
- Main: "Product Name"
- Empty: "Empty Product Container"

### 3. Accurate Deposits
Research market rates:
- Bottle deposits: KES 5 - 20
- Crate deposits: KES 50 - 200
- Cylinder deposits: KES 1,000 - 3,000

### 4. Stock Tracking
Keep adequate empty stock:
- Empties should be >= products in stock
- Monitor return rates
- Adjust deposits if return rates are low

### 5. Customer Communication
Inform customers about:
- Deposit amounts
- Return conditions
- Refund process

## Common Mistakes to Avoid

❌ **Linking same product to itself**
- System prevents this automatically

❌ **Wrong quantity multiplier**
- Double-check: 1 crate = how many bottles?

❌ **Forgetting to set deposit**
- Always set deposit amount, even if small

❌ **Not creating empty products first**
- Create empties before linking

❌ **Setting price on deposit-only empties**
- Empties used only for deposits should have price = 0

## Quick Reference

### API Endpoints
```bash
# Get empties for product ID 5
GET /api/products/5/empties

# Link empty to product
POST /api/products/5/empties
{
  "empty_product_id": 12,
  "quantity": 24,
  "deposit_amount": 10
}

# Update empty link
PUT /api/products/5/empties/12
{
  "quantity": 12,
  "deposit_amount": 15,
  "is_active": true
}

# Unlink empty
DELETE /api/products/5/empties/12
```

### Database Query
```sql
-- View all empty links
SELECT 
  p1.name as product_name,
  p2.name as empty_name,
  pe.quantity,
  pe.deposit_amount,
  pe.is_active
FROM product_empties pe
JOIN products p1 ON pe.product_id = p1.id
JOIN products p2 ON pe.empty_product_id = p2.id
WHERE pe.is_active = true;
```

---

**Need Help?**
Check the full documentation: [EMPTIES_RETURNABLES_SYSTEM.md](./EMPTIES_RETURNABLES_SYSTEM.md)
