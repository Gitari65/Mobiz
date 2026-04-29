# UOM Selector Quick Start

## 🎯 What Changed?

**Old Interface**: Simple select dropdown with "+ Add UOM" text  
**New Interface**: Beautiful dropdown with checkboxes, search, and gradient tags

---

## 💻 For Developers

### Quick Integration

If you need UOM selection in any other component:

```vue
<template>
  <UOMSelector 
    v-model="selectedUoms"
    :uoms="availableUoms"
  />
</template>

<script>
import UOMSelector from '@/components/UOMSelector.vue'

export default {
  components: { UOMSelector },
  data() {
    return {
      selectedUoms: [1, 3],        // Already selected UOM IDs
      availableUoms: []            // Fetch from API
    }
  }
}
</script>
```

### Events & Methods

The component uses v-model, so no event listening needed:

```javascript
// Parent component automatically updates when user selects/deselects UOMs
this.selectedUoms  // Always contains array of selected UOM IDs
```

---

## 👤 For Users (Mobiz POS Staff)

### How to Add Product with Multiple UOMs

**Step 1**: Click "Add Product"  
**Step 2**: Fill in basic info (name, price, etc.)  
**Step 3**: Scroll down to "Sale UOMs (How you sell...)"  
**Step 4**: Click the blue "Add UOM" button with dropdown arrow  
**Step 5**: A dropdown menu appears with:
- Search box at top
- Checkbox list of all available UOMs (250ml, 500ml, 1L, etc.)
- "Done" button at bottom

**Step 6**: Check the boxes for UOMs this product sells in:
```
✓ 250ml (Small)
✓ 500ml (Medium)  
✓ 1L (Large)
```

**Step 7**: Click "Done" button  
**Step 8**: Selected UOMs appear as purple/pink gradient tags  
**Step 9**: To remove a UOM, click the "X" on the tag  
**Step 10**: Click "Save" to create product

### Visual Example

```
Sale UOMs (How you sell - Multiple Selection)
┌─────────────────────────────────────┐
│ ┌──────────────────┐ ┌────────────┐ │
│ │ 250ml (Small)  │ │ 500ml (Med)│ │  ← Selected UOM tags with X buttons
│ └──────────────────┘ └────────────┘ │
│ ┌──────────────────────────────────┐ │
│ │ ➕ Add UOM         ⬇️              │ │  ← Click to open dropdown
│ └──────────────────────────────────┘ │
└─────────────────────────────────────┘

Select multiple UOMs (e.g., 250ml, 500ml, 1L). First is default.
```

### When Adding to Cart at Sales

1. Click a product
2. If it has multiple UOMs, a "Select UOM" dialog appears
3. Choose which size/unit you're selling
4. Click to select - item added to cart with chosen UOM

---

## 🔧 Component Features

### Dropdown Menu
- **Search box**: Type to filter UOMs (searches name & abbreviation)
- **Checkboxes**: Click to select/deselect multiple
- **Visual feedback**: Checked boxes show with checkmark
- **Auto-close**: Click "Done" to close dropdown
- **ESC key**: Press ESC to close without changing

### Selected Tags
- **Colored badges**: Purple/pink gradient for visual appeal
- **Abbreviation display**: Shows "250ml" not "Small"
- **Remove button**: Small X on each tag to deselect
- **Drag friendly**: Tags wrap responsively on mobile

### Mobile Optimizations
- Dropdown doesn't extend beyond screen
- Scrollable UOM list for long lists
- Touch-friendly checkbox size (20px)
- Readable font sizes on small screens

---

## 📋 System Flow

```
Product Management (ProductsPage.vue)
    ↓
    Select multiple Sale UOMs using new dropdown
    ↓
Save product with sale_uom_ids array
    ↓
SalesPage.vue fetches product with saleUoms relationship
    ↓
When adding to cart, user selects one UOM from dropdown
    ↓
Cart item stored with selected uom_id
    ↓
Sale created with each item's chosen UOM
    ↓
Receipt prints (currently shows qty, should show qty × UOM)
```

---

## 📊 Data Stored

When you create a product:

```
Product:
  name: "Coca Cola"
  sale_uom_ids: [1, 2, 3]    ← Array of UOM IDs stored

UOMs:
  [
    { id: 1, name: "Small", abbreviation: "250ml" },
    { id: 2, name: "Medium", abbreviation: "500ml" },
    { id: 3, name: "Large", abbreviation: "1L" }
  ]

Product-UOM Relationship (Many-to-Many):
  product_id: 5
  uom_id: 1     → 250ml
  uom_id: 2     → 500ml
  uom_id: 3     → 1L
```

---

## ⚡ Performance Notes

- **Search**: Real-time filtering (no API call)
- **Animations**: Smooth CSS transitions (60fps)
- **Mobile**: Optimized scrolling with momentum
- **Load time**: Component loads instantly (no external dependencies)

---

## 🐛 If Something Goes Wrong

| Problem | Solution |
|---------|----------|
| Dropdown doesn't open | Refresh page (F5). Check browser console (F12) for errors |
| Selected UOMs not showing as tags | Clear browser cache (Ctrl+Shift+Delete) and refresh |
| Can't find UOM to select | Search box: type part of UOM name (e.g., "ml" or "L") |
| Checkboxes not working on mobile | Update to latest browser version |

---

## 🎨 Visual Design

The component uses:
- **Primary Color**: #667eea (Indigo)
- **Accent Color**: #764ba2 (Purple)
- **Gradient**: Linear gradient from Indigo → Purple
- **Text**: Dark gray (#2d3748) for readability
- **Borders**: Light gray (#e2e8f0) for subtlety

This matches the existing Mobiz design system.

---

## 📞 Questions?

The UOM selector component is fully self-contained and handles:
- ✅ Showing available UOMs
- ✅ Filtering by search
- ✅ Managing selections with checkboxes
- ✅ Displaying selected items as tags
- ✅ Removing selections
- ✅ Mobile responsiveness
- ✅ Keyboard support (ESC key)

No additional setup needed - it just works!

