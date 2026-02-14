<template>
  <div class="pos-system">
    <!-- Alert System -->
    <div v-if="alert.show" class="alert-container" :class="alert.type">
      <div class="alert-content">
        <i :class="getAlertIcon()"></i>
        <span>{{ alert.message }}</span>
      </div>
      <button class="alert-close" @click="hideAlert">
        <i class="fas fa-times"></i>
      </button>
    </div>

    <!-- Header -->
    <div class="pos-header">
      <div class="header-content">
        <div class="header-left">
          <h1 class="page-title">
            <i class="fas fa-cash-register"></i>
            Point of Sale
          </h1>
          <p class="page-subtitle">Select products to create a new sale</p>
        </div>
        <div class="header-right">
          <div class="search-container">
            <i class="fas fa-search search-icon"></i>
            <input 
              type="text" 
              class="search-input" 
              v-model="searchQuery"
              placeholder="Search products, SKU, category..." 
            />
          </div>
          <div class="barcode-scan">
            <i class="fas fa-barcode barcode-icon"></i>
            <input
              v-model="barcodeInput"
              @keyup.enter="handleBarcodeEnter"
              placeholder="Scan barcode..."
              class="barcode-input"
            />
          </div>
          <div class="stats-mini">
            <span class="stat-number">{{ filteredProducts.length }}</span>
            <span class="stat-label">Products</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filters-bar">
      <div class="filter-group">
        <label><i class="fas fa-tags"></i> Category</label>
        <select v-model="categoryFilter" class="filter-select">
          <option value="">All Categories</option>
          <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
        </select>
      </div>
      <div class="filter-group">
        <label><i class="fas fa-box"></i> Stock</label>
        <select v-model="stockFilter" class="filter-select">
          <option value="all">All Stock</option>
          <option value="in-stock">In Stock (>10)</option>
          <option value="low-stock">Low Stock (1-10)</option>
          <option value="out-of-stock">Out of Stock</option>
        </select>
      </div>
      <button v-if="hasActiveFilters" @click="clearFilters" class="clear-filters-btn">
        <i class="fas fa-times"></i> Clear Filters
      </button>
    </div>

    <!-- Main Content -->
    <div class="pos-main">
      <!-- Products Section -->
      <div class="products-section">
        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
          <div class="loading-spinner">
            <div class="spinner"></div>
            <p>Loading products...</p>
          </div>
        </div>

        <!-- Products Grid -->
        <div v-else class="products-grid">
          <div v-for="product in filteredProducts" :key="product.id" class="product-card" @click="addToCart(product)">
            <div class="product-image">
              <i class="fas fa-box"></i>
            </div>
            <div class="product-info">
              <h3 class="product-name">{{ product.name }}</h3>
              <div class="product-price">
                <span class="currency">Ksh</span>
                <span class="amount">{{ formatPrice(product.price) }}</span>
              </div>
              <div class="stock-info" :class="getStockClass(product.stock_quantity)">
                <i class="fas fa-boxes"></i>
                <span>{{ product.stock_quantity }} in stock</span>
              </div>
            </div>
            <div class="add-btn">
              <i class="fas fa-plus"></i>
            </div>
            <div class="stock-badge" :class="getStockClass(product.stock_quantity)">
              {{ getStockStatus(product.stock_quantity) }}
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="!loading && filteredProducts.length === 0 && !searchQuery" class="empty-state">
            <i class="fas fa-box-open"></i>
            <h3>No Products Available</h3>
            <p>Add products to your inventory to start making sales</p>
          </div>

          <!-- No Search Results -->
          <div v-if="!loading && filteredProducts.length === 0 && searchQuery" class="no-results">
            <i class="fas fa-search-minus"></i>
            <h3>No Results Found</h3>
            <p>No products match "{{ searchQuery }}"</p>
          </div>
        </div>
      </div>

      <!-- Cart Sidebar -->
      <div class="cart-sidebar" :class="{ 'cart-open': cartOpen }">
        <div class="cart-header">
          <h2>
            <i class="fas fa-shopping-cart"></i>
            Shopping Cart
          </h2>
          <button class="cart-close" @click="cartOpen = false">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div class="cart-content">
          <!-- Empty Cart -->
          <div v-if="cart.length === 0" class="empty-cart">
            <i class="fas fa-shopping-cart"></i>
            <p>Your cart is empty</p>
            <small>Add products to get started</small>
          </div>

          <!-- Cart Items -->
          <div v-else class="cart-items">
            <div v-for="item in cart" :key="item.id" class="cart-item">
              <div class="item-info">
                <h4>{{ item.name }}</h4>
                <p class="item-price">Ksh {{ formatPrice(item.price) }}</p>
              </div>
              <div class="item-controls">
                <button @click="decreaseQuantity(item)" class="qty-btn">
                  <i class="fas fa-minus"></i>
                </button>
                <span class="quantity">{{ item.quantity }}</span>
                <button @click="increaseQuantity(item)" class="qty-btn">
                  <i class="fas fa-plus"></i>
                </button>
              </div>
              <div class="item-total">
                Ksh {{ formatPrice(item.price * item.quantity) }}
              </div>
              <button @click="removeFromCart(item.id)" class="remove-btn">
                <i class="fas fa-trash"></i>
              </button>
            </div>

            <!-- Cart Summary -->
            <div class="cart-summary">
              <div class="summary-line">
                <span>Subtotal:</span>
                <span>Ksh {{ formatPrice(total) }}</span>
              </div>
              
              <!-- Applied Promotions Details -->
              <div v-if="appliedPromos.length > 0" class="applied-promos-section">
                <div class="promo-header">🎉 Active Promotions ({{ appliedPromos.length }}):</div>
                <div v-for="promo in appliedPromos" :key="promo.id" class="promo-item">
                  <span class="promo-name">{{ promo.name }}</span>
                  <span class="promo-discount">- Ksh {{ formatPrice(promo.discount) }}</span>
                </div>
                <div class="summary-line promo-total">
                  <span><strong>Total Discount:</strong></span>
                  <span><strong>- Ksh {{ formatPrice(promoDiscount) }}</strong></span>
                </div>
              </div>
              
              <div class="summary-line total-line">
                <span>Total:</span>
                <span>Ksh {{ formatPrice(netTotal) }}</span>
              </div>
            </div>

            <!-- Payment Section -->
            <div class="payment-section">
              <h3>Payment Details</h3>
              
              <div class="form-group">
                <label>Payment Method</label>
                <select v-model="paymentForm.paymentMethod" class="payment-method-select">
                  <option v-for="method in paymentMethods" :key="method.id" :value="method.name">
                    {{ getPaymentIcon(method.name) }} {{ method.name }}
                  </option>
                </select>
              </div>

              <div class="form-group">
                <label>Amount Paid</label>
                <div class="amount-input">
                  <span class="currency-symbol">Ksh</span>
                  <input 
                    type="number" 
                    v-model.number="paymentForm.amountPaid"
                    placeholder="0.00"
                    step="0.01"
                    min="0"
                  />
                </div>
              </div>

              <div class="form-group">
                <label>Customer</label>
                <select v-model="selectedCustomerId">
                  <option value="">Walk-in / Other</option>
                  <option v-for="cust in customers" :key="cust.id" :value="cust.id">
                    {{ cust.name }}
                  </option>
                </select>
              </div>

              <div v-if="selectedCustomerId" class="credit-status-info">
                <div v-if="selectedCustomerData" class="credit-details">
                  <div class="credit-item">
                    <span class="credit-label">Current Balance:</span>
                    <span class="credit-value" :class="selectedCustomerData.credit_balance > 0 ? 'warning' : 'success'">
                      Ksh {{ formatPrice(selectedCustomerData.credit_balance || 0) }}
                    </span>
                  </div>
                  <div class="credit-item">
                    <span class="credit-label">Credit Limit:</span>
                    <span class="credit-value info">Ksh {{ formatPrice(selectedCustomerData.credit_limit || 0) }}</span>
                  </div>
                  <div class="credit-item">
                    <span class="credit-label">Available Credit:</span>
                    <span class="credit-value" :class="availableCredit <= 0 ? 'danger' : 'success'">
                      Ksh {{ formatPrice(Math.max(0, (selectedCustomerData.credit_limit || 0) - (selectedCustomerData.credit_balance || 0))) }}
                    </span>
                  </div>
                  <div v-if="(selectedCustomerData.credit_balance || 0) > (selectedCustomerData.credit_limit || 0)" class="limit-warning">
                    ⚠️ Customer has exceeded credit limit!
                  </div>
                </div>
              </div>

              <div v-if="paymentForm.amountPaid > 0" class="change-display">
                <div class="change-info" :class="changeClass">
                  <i :class="changeIcon"></i>
                  <div class="change-text">
                    <span class="change-label">{{ changeLabel }}</span>
                    <span class="change-amount">Ksh {{ formatPrice(Math.abs(change)) }}</span>
                  </div>
                </div>
              </div>

              <div class="form-group">
                <label>Customer Name (Optional)</label>
                <input 
                  type="text" 
                  v-model="saleForm.customer_name"
                  placeholder="Enter customer name"
                />
              </div>

              <div class="form-group">
                <label>Notes (Optional)</label>
                <textarea 
                  v-model="saleForm.notes"
                  placeholder="Additional notes..."
                  rows="2"
                ></textarea>
              </div>

              <button 
                class="checkout-btn" 
                @click="submitSale"
                :disabled="!canProcessSale || submitting"
                :class="{ 'processing': submitting }"
              >
                <div v-if="submitting" class="btn-loading">
                  <div class="btn-spinner"></div>
                  Processing...
                </div>
                <div v-else class="btn-content">
                  <i class="fas fa-credit-card"></i>
                  {{ getSubmitButtonText() }}
                </div>
              </button>

              <div v-if="creditBlockReason" class="credit-block-msg">
                <i class="fas fa-ban"></i>
                <span>{{ creditBlockReason }}</span>
              </div>

              <div class="printer-controls">
                <button type="button" class="secondary-btn" @click="connectPrinter">
                  <i :class="printerConnected ? 'fas fa-print' : 'fas fa-plug'" aria-hidden="true"></i>
                  {{ printerConnected ? 'Printer Connected' : 'Connect Printer' }}
                </button>
                <button type="button" class="secondary-btn" @click="testPrint" :disabled="!printerConnected">
                  <i class="fas fa-file-alt" aria-hidden="true"></i>
                  Test Print
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Cart Toggle Button -->
    <button class="cart-toggle" @click="cartOpen = !cartOpen" :class="{ 'has-items': cart.length > 0 }">
      <i class="fas fa-shopping-cart"></i>
      <span class="cart-count" v-if="cart.length > 0">{{ cart.length }}</span>
    </button>

    <!-- Receipt Modal -->
    <div v-if="showReceipt" class="modal-overlay" @click="closeReceipt">
      <div class="receipt-modal" @click.stop>
        <div class="receipt-header">
          <h2>
            <i class="fas fa-receipt"></i>
            Receipt Generated
          </h2>
          <button @click="closeReceipt" class="close-btn">
            <i class="fas fa-times"></i>
          </button>
        </div>
        
        <div class="receipt-content" ref="receiptContent">
          <div class="receipt-paper">
            <!-- Receipt Header (uses printer settings; shows placeholders if not edited) -->
            <div class="receipt-business-header">
              <template v-if="headerLines.length">
                <h1 v-if="headerLines[0]" class="receipt-title">{{ headerLines[0] }}</h1>
                <p v-for="(line, idx) in headerLines.slice(1)" :key="idx" class="receipt-subline">{{ line }}</p>
              </template>
              <div class="receipt-divider">═══════════════════════════</div>
            </div>

            <!-- Sale Info -->
            <div class="receipt-sale-info">
              <p><strong>Receipt #:</strong> {{ receiptData.receiptNumber }}</p>
              <p><strong>Date:</strong> {{ receiptData.date }}</p>
              <p><strong>Time:</strong> {{ receiptData.time }}</p>
              <p v-if="receiptData.customer"><strong>Customer:</strong> {{ receiptData.customer }}</p>
              <div class="receipt-divider">═══════════════════════════</div>
            </div>

            <!-- Items -->
            <div class="receipt-items">
              <div class="receipt-items-header">
                <span>ITEM</span>
                <span>QTY</span>
                <span>PRICE</span>
                <span>TOTAL</span>
              </div>
              <div class="receipt-divider-thin">───────────────────────────</div>
              
              <div v-for="item in receiptData.items" :key="item.id" class="receipt-item">
                <span class="item-name">{{ item.name }}</span>
                <span class="item-qty">{{ item.quantity }}</span>
                <span class="item-price">{{ item.price }}</span>
                <span class="item-total">{{ (item.price * item.quantity).toFixed(2) }}</span>
              </div>
              
              <div class="receipt-divider-thin">───────────────────────────</div>
            </div>

            <!-- Totals -->
            <div class="receipt-totals">
              <div class="receipt-total-line">
                <span><strong>SUBTOTAL:</strong></span>
                <span><strong>Ksh {{ receiptData.subtotal }}</strong></span>
              </div>
              <div class="receipt-total-line" v-if="receiptData.showTaxes && Number(receiptData.taxAmount) > 0">
                <span>{{ receiptData.taxLabel }}:</span>
                <span>+ Ksh {{ receiptData.taxAmount }}</span>
              </div>
              <div class="receipt-total-line" v-if="receiptData.discount && Number(receiptData.discount) > 0">
                <span>Discounts:</span>
                <span>- Ksh {{ receiptData.discount }}</span>
              </div>
              <div class="receipt-total-line">
                <span><strong>TOTAL:</strong></span>
                <span><strong>Ksh {{ receiptData.total }}</strong></span>
              </div>
              <div class="receipt-total-line">
                <span>Amount Paid:</span>
                <span>Ksh {{ receiptData.amountPaid }}</span>
              </div>
              <div class="receipt-total-line" v-if="receiptData.balanceDue && Number(receiptData.balanceDue) > 0">
                <span><strong>Balance Due:</strong></span>
                <span><strong>Ksh {{ receiptData.balanceDue }}</strong></span>
              </div>
              <div class="receipt-total-line" v-else-if="receiptData.change > 0">
                <span>Change:</span>
                <span>Ksh {{ receiptData.change }}</span>
              </div>
              <div class="receipt-divider">═══════════════════════════</div>
              <div v-if="receiptData.balanceDue && Number(receiptData.balanceDue) > 0" class="receipt-credit-note">
                <p><em>ℹ️ Balance added to customer credit account</em></p>
              </div>
            </div>

            <div v-if="receiptData.promotions && receiptData.promotions.length" class="receipt-promos">
              <p><strong>Applied Promotions:</strong></p>
              <ul>
                <li v-for="promo in receiptData.promotions" :key="promo.id">
                  {{ promo.name }} ({{ promo.type }}): -Ksh {{ promo.discount.toFixed ? promo.discount.toFixed(2) : promo.discount }}
                </li>
              </ul>
              <div class="receipt-divider">═══════════════════════════</div>
            </div>

            <!-- Footer -->
            <div class="receipt-footer">
              <p v-if="receiptData.notes"><strong>Notes:</strong> {{ receiptData.notes }}</p>
              <template v-if="footerLines.length">
                <p v-for="(line, idx) in footerLines" :key="idx" class="thank-you">{{ line }}</p>
              </template>
              <p class="return-policy" v-if="showDiscountsFlag">* Discounts shown where applicable</p>
              <p class="service-notice">💚 Quality products, reliable service</p>
            </div>
          </div>
        </div>
        
        <div class="receipt-actions">
          <button @click="printReceipt" class="print-btn">
            <i class="fas fa-print"></i>
            Print Receipt
          </button>
          <button @click="closeReceipt" class="secondary-btn">
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

// State
const products = ref([])
const cart = ref([])
const cartOpen = ref(false)
const loading = ref(false)
const submitting = ref(false)
const searchQuery = ref('')
const categoryFilter = ref('')
const stockFilter = ref('all')
const promoRefreshTimeout = ref(null)

const alert = ref({
  show: false,
  message: '',
  type: 'success' // success, error, warning, info
})

const customers = ref([])
const selectedCustomerId = ref('')
const barcodeInput = ref('')
const printerConnected = ref(false)
const paymentMethods = ref([])

// Printer and Tax settings
const printerSettings = ref({
  header_message: '',
  footer_message: '',
  show_logo: true,
  show_taxes: true,
  show_discounts: true,
  paper_size: '58mm',
  alignment: 'center'
})
const defaultTaxConfig = ref(null)
const taxConfigs = ref([])

const saleForm = ref({
  customer_name: '',
  notes: ''
})

const paymentForm = ref({
  amountPaid: 0,
  paymentMethod: 'Cash'
})

const promoDiscount = ref(0)
const appliedPromos = ref([])

const showReceipt = ref(false)
const receiptData = ref({})
const receiptContent = ref(null)

// Printer-friendly display helpers: show configured text (including placeholders) or sensible defaults when empty
const headerLines = computed(() => {
  const text = (printerSettings.value.header_message || '').trim()
  if (!text) {
    return ['🌾 AGROVET SUPPLIES', 'Your Trusted Agricultural Partner', '📍 Kerugoya, Kirinyaga County', '📞 Contact: +254-XXX-XXXX']
  }
  return text.split(/\n+/).map(line => line.trim()).filter(Boolean)
})

const footerLines = computed(() => {
  const text = (printerSettings.value.footer_message || '').trim()
  if (!text) {
    return ['🙏 Thank you for your business!', 'Return policy: 7 days with receipt', '💚 Quality products, reliable service']
  }
  return text.split(/\n+/).map(line => line.trim()).filter(Boolean)
})

const showDiscountsFlag = computed(() => printerSettings.value.show_discounts !== false)

// Computed properties
const categories = computed(() => {
  const cats = new Set()
  products.value.forEach(p => {
    if (p.category) cats.add(p.category)
  })
  return Array.from(cats).sort()
})

const filteredProducts = computed(() => {
  let filtered = products.value
  
  // Search filter
  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase().trim()
    filtered = filtered.filter(product => 
      product.name.toLowerCase().includes(query) ||
      (product.sku && product.sku.toLowerCase().includes(query)) ||
      (product.category && product.category.toLowerCase().includes(query)) ||
      product.price.toString().includes(query)
    )
  }
  
  // Category filter
  if (categoryFilter.value) {
    filtered = filtered.filter(p => p.category === categoryFilter.value)
  }
  
  // Stock filter
  if (stockFilter.value === 'in-stock') {
    filtered = filtered.filter(p => p.stock_quantity > 10)
  } else if (stockFilter.value === 'low-stock') {
    filtered = filtered.filter(p => p.stock_quantity > 0 && p.stock_quantity <= 10)
  } else if (stockFilter.value === 'out-of-stock') {
    filtered = filtered.filter(p => p.stock_quantity === 0)
  }
  
  return filtered
})

const total = computed(() =>
  cart.value.reduce((sum, item) => sum + item.price * item.quantity, 0)
)

const baseTotal = computed(() => Math.max(0, total.value - promoDiscount.value))

const taxDue = computed(() => {
  if (!defaultTaxConfig.value) return 0
  const rate = Number(defaultTaxConfig.value.rate || 0) / 100
  if (!rate) return 0
  if (defaultTaxConfig.value.is_inclusive) {
    return baseTotal.value - (baseTotal.value / (1 + rate))
  }
  return baseTotal.value * rate
})

const netTotal = computed(() => {
  if (!defaultTaxConfig.value) return baseTotal.value
  return defaultTaxConfig.value.is_inclusive
    ? baseTotal.value
    : baseTotal.value + taxDue.value
})

const change = computed(() => paymentForm.value.amountPaid - netTotal.value)
const balanceDue = computed(() => Math.max(0, netTotal.value - paymentForm.value.amountPaid))

const selectedCustomerData = computed(() => {
  if (!selectedCustomerId.value) return null
  return customers.value.find(c => c.id === parseInt(selectedCustomerId.value)) || null
})

const availableCredit = computed(() => {
  if (!selectedCustomerData.value) return 0
  const limit = selectedCustomerData.value.credit_limit || 0
  const balance = selectedCustomerData.value.credit_balance || 0
  return Math.max(0, limit - balance)
})

const isCreditPaymentEnabled = computed(() => {
  return paymentMethods.value.some(pm => 
    pm.name?.toLowerCase() === 'credit/invoice'
  )
})

const creditBlockReason = computed(() => {
  // Only show if cart has items and payment is less than total
  if (cart.value.length === 0 || balanceDue.value <= 0) return ''

  // Credit/Invoice payment not enabled - must pay full amount
  if (!isCreditPaymentEnabled.value) {
    return 'Credit/Invoice payments are not enabled. Please collect full payment.'
  }

  // Needs credit but no customer selected
  if (!selectedCustomerData.value) {
    return 'Payment is below total. Select a customer or collect full payment.'
  }

  const limit = selectedCustomerData.value.credit_limit || 0
  const balance = selectedCustomerData.value.credit_balance || 0
  const available = Math.max(0, limit - balance)

  if (limit <= 0) {
    return 'Customer is not credit worthy (credit limit is 0). Collect full payment or increase the limit.'
  }

  if (balanceDue.value > available) {
    return `Customer would exceed credit limit. Available credit: Ksh ${available.toFixed(2)}, needed: Ksh ${balanceDue.value.toFixed(2)}.`
  }

  return ''
})

const hasActiveFilters = computed(() => {
  return searchQuery.value.trim() !== '' || 
         categoryFilter.value !== '' || 
         stockFilter.value !== 'all'
})

const canProcessSale = computed(() => {
  if (cart.value.length === 0) return false
  if (paymentForm.value.amountPaid >= netTotal.value) return true
  if (!selectedCustomerId.value) return false
  return !creditBlockReason.value // block if credit rules fail
})

const getPaymentIcon = (methodName) => {
  const icons = {
    'Cash': '💵',
    'M-Pesa': '📱',
    'Card': '💳',
    'Bank Transfer': '🏦',
    'Cheque': '📄',
    'Mobile Money': '📱'
  }
  return icons[methodName] || '💰'
}

const changeClass = computed(() => {
  if (change.value < 0) return 'insufficient'
  if (change.value === 0) return 'exact'
  return 'overpaid'
})

const changeLabel = computed(() => {
  if (change.value < 0) return 'Balance Due'
  if (change.value === 0) return 'Exact Payment'
  return 'Change'
})

const changeIcon = computed(() => {
  if (change.value < 0) return 'fas fa-exclamation-triangle'
  if (change.value === 0) return 'fas fa-check-circle'
  return 'fas fa-money-bill-wave'
})

// Fetch products on mount
onMounted(() => {
  fetchProducts()
  fetchCustomers()
  fetchPaymentMethods()
  fetchPrinterSettings()
  fetchTaxConfigs()
})

async function fetchProducts() {
  loading.value = true
  try {
    const res = await axios.get('/products')
    products.value = res.data
    if (products.value.length === 0) {
      showAlert('No products available. Add products to start making sales.', 'info')
    }
  } catch (err) {
    console.error('❌ Failed to fetch products:', err.message)
    showAlert('Failed to load products. Please try again.', 'error')
  } finally {
    loading.value = false
  }
}

async function fetchCustomers() {
  try {
    const res = await axios.get('/customers')
    customers.value = Array.isArray(res.data?.data) ? res.data.data : (Array.isArray(res.data) ? res.data : [])
  } catch (err) {
    console.error('❌ Failed to fetch customers', err)
    console.error('Error details:', err.response?.data)
    customers.value = []
  }
}

async function fetchPaymentMethods() {
  try {
    const res = await axios.get('/api/payment-methods/enabled')
    paymentMethods.value = Array.isArray(res.data) ? res.data : []
    
    // Set default payment method to first enabled method
    if (paymentMethods.value.length > 0) {
      paymentForm.value.paymentMethod = paymentMethods.value[0].name
    }
  } catch (err) {
    console.warn('❌ Failed to fetch payment methods, using defaults', err)
    // Fallback to defaults if API fails
    paymentMethods.value = [
      { id: 1, name: 'Cash', description: 'Cash payment' },
      { id: 2, name: 'M-Pesa', description: 'Mobile money' }
    ]
  }
}

async function fetchPrinterSettings() {
  try {
    const res = await axios.get('/api/printer-settings')
    printerSettings.value = res.data
  } catch (err) {
    console.warn('⚠️ Failed to load printer settings', err)
  }
}

async function fetchTaxConfigs() {
  try {
    const res = await axios.get('/api/tax-configurations')
    taxConfigs.value = Array.isArray(res.data) ? res.data : []
    // Set default tax config (marked as is_default)
    const defaultConfig = taxConfigs.value.find(t => t.is_default)
    if (defaultConfig) {
      defaultTaxConfig.value = defaultConfig
    }
  } catch (err) {
    console.warn('⚠️ Failed to load tax configurations', err)
  }
}

// Alert system
function showAlert(message, type = 'success') {
  alert.value = {
    show: true,
    message,
    type
  }
  
  // Auto-hide after 5 seconds for success/info alerts
  if (type === 'success' || type === 'info') {
    setTimeout(() => {
      hideAlert()
    }, 5000)
  }
}

function hideAlert() {
  alert.value.show = false
}

function getAlertIcon() {
  switch (alert.value.type) {
    case 'success': return 'fas fa-check-circle'
    case 'error': return 'fas fa-exclamation-circle'
    case 'warning': return 'fas fa-exclamation-triangle'
    case 'info': return 'fas fa-info-circle'
    default: return 'fas fa-info-circle'
  }
}

// Cart functions with await
async function addToCart(product) {
  if (product.stock_quantity === 0) {
    showAlert('This product is out of stock!', 'warning')
    return
  }

  const existing = cart.value.find(item => item.id === product.id)
  if (existing) {
    if (existing.quantity >= product.stock_quantity) {
      showAlert('Cannot add more items. Stock limit reached!', 'warning')
      return
    }
    existing.quantity++
    showAlert(`Added another ${product.name} to cart`, 'success')
  } else {
    cart.value.push({ ...product, quantity: 1 })
    showAlert(`${product.name} added to cart`, 'success')
  }
  
  await refreshPromotions()
  
  // Auto-open cart on first item
  if (cart.value.length === 1) {
    cartOpen.value = true
  }
}

function handleBarcodeEnter() {
  const code = barcodeInput.value.trim()
  if (!code) return
  const match = products.value.find(p =>
    (p.sku && p.sku.toString() === code) ||
    p.id?.toString() === code
  )
  if (match) {
    addToCart(match)
  } else {
    showAlert('No product found for that barcode/SKU', 'warning')
  }
  barcodeInput.value = ''
}

function clearFilters() {
  searchQuery.value = ''
  categoryFilter.value = ''
  stockFilter.value = 'all'
}

async function removeFromCart(id) {
  const item = cart.value.find(item => item.id === id)
  if (item) {
    cart.value = cart.value.filter(item => item.id !== id)
    showAlert(`${item.name} removed from cart`, 'info')
    await refreshPromotions()
  }
}

async function increaseQuantity(item) {
  const product = products.value.find(p => p.id === item.id)
  if (item.quantity >= product.stock_quantity) {
    showAlert('Stock limit reached!', 'warning')
    return
  }
  item.quantity++
  await refreshPromotions()
}

async function decreaseQuantity(item) {
  if (item.quantity > 1) {
    item.quantity--
    await refreshPromotions()
  } else {
    await removeFromCart(item.id)
  }
}

// Utility functions
function formatPrice(price) {
  return new Intl.NumberFormat().format(price || 0)
}

function getStockStatus(quantity) {
  if (quantity === 0) return 'Out of Stock'
  if (quantity < 10) return 'Low Stock'
  return 'In Stock'
}

function getStockClass(quantity) {
  if (quantity === 0) return 'out-of-stock'
  if (quantity < 10) return 'low-stock'
  return 'in-stock'
}

function getSubmitButtonText() {
  if (cart.value.length === 0) return 'Add items to cart'
  if (paymentForm.value.amountPaid === 0) return 'Enter payment amount'
  if (paymentForm.value.amountPaid < netTotal.value && !selectedCustomerId.value) return 'Select customer or pay full'
  if (paymentForm.value.amountPaid < netTotal.value) return 'Process with credit'
  return 'Process Sale'
}

async function refreshPromotions() {
  // Clear any pending refresh
  if (promoRefreshTimeout.value) {
    clearTimeout(promoRefreshTimeout.value)
  }

  if (!cart.value.length) {
    promoDiscount.value = 0
    appliedPromos.value = []
    return
  }

  // Debounce promo calculation to avoid rapid API calls
  return new Promise((resolve) => {
    promoRefreshTimeout.value = setTimeout(async () => {
      try {
        const payload = {
          cart_total: total.value,
          cart_items: cart.value.map(item => ({
            product_id: item.id,
            quantity: item.quantity,
            price: item.price
          })),
          customer_id: selectedCustomerId.value || null
        }
        
        console.log('🛒 Requesting promo calculation for cart:', payload)
        
        const res = await axios.post('/promotions/calculate-discount', payload)
        
        // Ensure we're getting the correct data structure
        const promoData = res.data || {}
        const newDiscount = Number(promoData.total_discount || 0)
        
        // Get all applicable promotions - handle various response formats
        let newPromos = []
        
        if (Array.isArray(promoData.applicable_promotions)) {
          // Direct array format
          newPromos = promoData.applicable_promotions
        } else if (promoData.applicable_promotions && typeof promoData.applicable_promotions === 'object') {
          // Object format - convert to array
          const promoValues = Object.values(promoData.applicable_promotions)
          newPromos = Array.isArray(promoValues) ? promoValues.flat() : [promoData.applicable_promotions]
        }
        
        // Normalize all promos to ensure consistent data structure
        newPromos = newPromos.map(promo => {
          // Handle different possible field names and data structures
          const discountValue = Number(
            promo.discount || 
            promo.discount_value || 
            promo.total_discount ||
            promo.amount ||
            0
          )
          
          return {
            id: promo.id || `promo-${Math.random()}`,
            name: promo.name || promo.promo_name || 'Unknown Promotion',
            type: promo.type || promo.promo_type || 'unknown',
            discount: Math.max(0, discountValue), // Ensure non-negative
            buy_quantity: promo.buy_quantity,
            get_quantity: promo.get_quantity,
            discount_value: promo.discount_value,
            ...promo // Keep all original fields for reference
          }
        })
        
        // Filter out any promos with zero discount
        newPromos = newPromos.filter(p => p.discount > 0)
        
        // Only update if values changed to prevent unnecessary re-renders
        if (promoDiscount.value !== newDiscount || 
            JSON.stringify(appliedPromos.value) !== JSON.stringify(newPromos)) {
          promoDiscount.value = newDiscount
          appliedPromos.value = newPromos
          
          console.log('📊 Cart Promo Updated:', {
            count: appliedPromos.value.length,
            promos: appliedPromos.value.map(p => ({
              name: p.name,
              type: p.type,
              discount: p.discount,
              buy_qty: p.buy_quantity,
              get_qty: p.get_quantity
            })),
            totalDiscount: promoDiscount.value
          })
        }
        
        resolve()
      } catch (err) {
        console.error('❌ Promo calculation failed:', err)
        console.error('Error details:', err.response?.data)
        promoDiscount.value = 0
        appliedPromos.value = []
        resolve()
      }
    }, 500) // 500ms debounce delay
  })
}

// Receipt functions
function generateReceipt(saleResponse) {
  const now = new Date()
  const receiptNumber = `RCP-${Date.now().toString().slice(-8)}`
  const discountAmount = Number(saleResponse.discount || 0)
  const sale = saleResponse.sale ?? saleResponse
  const taxAmount = Number(sale.tax || 0)
  const taxConfig = sale.tax_configuration || sale.taxConfiguration || defaultTaxConfig.value || {}
  const taxInclusive = Boolean(taxConfig.is_inclusive)
  const netTotal = Number(sale.total || 0)
  const amountPaid = Number(sale.amount_paid || paymentForm.value.amountPaid || 0)
  const balanceDue = Math.max(0, netTotal - amountPaid)
  
  // Calculate subtotal from cart items (gross before discount and tax)
  const grossTotal = cart.value.reduce((sum, item) => sum + (item.price * item.quantity), 0)
  const subtotal = grossTotal
  const showTaxes = printerSettings.value.show_taxes !== false
  const taxLabel = taxConfig.name ? `${taxConfig.name} (${taxConfig.rate ?? 0}%${taxInclusive ? ' incl.' : ''})` : 'Tax'
  
  receiptData.value = {
    receiptNumber,
    date: now.toLocaleDateString('en-GB'),
    time: now.toLocaleTimeString('en-GB'),
    customer: saleForm.value.customer_name || 'Walk-in Customer',
    items: cart.value.map(item => {
      const price = Number(item.price) || 0
      const quantity = Number(item.quantity) || 0
      return {
        id: item.id,
        name: item.name,
        quantity: quantity,
        price: price.toFixed(2),
        total: (price * quantity).toFixed(2)
      }
    }),
    subtotal: subtotal.toFixed(2),
    discount: discountAmount.toFixed(2),
    taxAmount: taxAmount.toFixed(2),
    total: netTotal.toFixed(2),
    amountPaid: amountPaid.toFixed(2),
    change: Math.max(0, amountPaid - netTotal).toFixed(2),
    balanceDue: balanceDue.toFixed(2),
    notes: saleForm.value.notes,
    saleId: saleResponse.id || 'N/A',
    promotions: saleResponse.applied_promotions || [],
    taxLabel,
    showTaxes
  }
  
  showReceipt.value = true
}

function closeReceipt() {
  showReceipt.value = false
  receiptData.value = {}
}

function printReceipt() {
  const printContent = receiptContent.value.innerHTML
  const originalContent = document.body.innerHTML
  
  // Create print-friendly CSS
  const printStyles = `
    <style>
      @media print {
        body * { visibility: hidden; }
        .print-area, .print-area * { visibility: visible; }
        .print-area { 
          position: absolute; 
          left: 0; 
          top: 0; 
          width: 80mm;
          font-family: 'Courier New', monospace;
          font-size: 12px;
          line-height: 1.4;
        }
        .receipt-paper {
          padding: 5mm;
          margin: 0;
        }
        .receipt-business-header h1 {
          font-size: 16px;
          text-align: center;
          margin: 5px 0;
        }
        .receipt-business-header p {
          font-size: 10px;
          text-align: center;
          margin: 2px 0;
        }
        .receipt-items-header {
          display: grid;
          grid-template-columns: 3fr 1fr 1fr 1fr;
          font-weight: bold;
          font-size: 10px;
        }
        .receipt-item {
          display: grid;
          grid-template-columns: 3fr 1fr 1fr 1fr;
          font-size: 10px;
          margin: 1px 0;
        }
        .receipt-total-line {
          display: flex;
          justify-content: space-between;
          font-size: 11px;
          margin: 2px 0;
        }
        .receipt-divider, .receipt-divider-thin {
          text-align: center;
          margin: 3px 0;
          font-size: 10px;
        }
        .thank-you {
          text-align: center;
          font-weight: bold;
          margin: 5px 0;
        }
        .return-policy, .service-notice {
          text-align: center;
          font-size: 9px;
          margin: 2px 0;
        }
      }
    </style>
  `
  
  document.body.innerHTML = `
    ${printStyles}
    <div class="print-area">
      ${printContent}
    </div>
  `
  
  window.print()
  
  // Restore original content after printing
  setTimeout(() => {
    document.body.innerHTML = originalContent
    location.reload() // Reload to restore Vue functionality
  }, 1000)
}

function connectPrinter() {
  // Placeholder: in a real setup, you would probe available printers
  printerConnected.value = true
  showAlert('Printer connected (simulated).', 'info')
}

function testPrint() {
  if (!printerConnected.value) {
    showAlert('Connect a printer first.', 'warning')
    return
  }
  if (!showReceipt.value) {
    showAlert('Generate a receipt first, then test print.', 'info')
    return
  }
  printReceipt()
}

// Submit sale
async function submitSale() {
  if (!canProcessSale.value) {
    if (cart.value.length === 0) {
      showAlert('Add products to the cart before submitting a sale.', 'warning')
    } else if (paymentForm.value.amountPaid < netTotal.value && !selectedCustomerId.value) {
      showAlert('Payment is below total. Select a customer to record the balance as credit or pay full amount.', 'warning')
    } else if (creditBlockReason.value) {
      showAlert(creditBlockReason.value, 'error')
    }
    return
  }

  submitting.value = true
  try {
    await refreshPromotions()

    // Credit handling prompt when payment is less than total
    const balance = Math.max(0, netTotal.value - paymentForm.value.amountPaid)
    let applyCredit = false
    if (balance > 0) {
      if (!selectedCustomerId.value) {
        showAlert('Payment is below total. Select a customer or pay full amount.', 'warning')
        submitting.value = false
        return
      }
      
      // Check customer credit limit BEFORE prompting
      const selectedCustomer = customers.value.find(c => c.id === parseInt(selectedCustomerId.value))
      if (selectedCustomer) {
        const currentCredit = selectedCustomer.credit_balance || 0
        const creditLimit = selectedCustomer.credit_limit || 0
        const proposedCredit = currentCredit + balance
        
        // Block sale if credit limit would be exceeded
        if (creditLimit > 0 && proposedCredit > creditLimit) {
          const exceedsBy = (proposedCredit - creditLimit).toFixed(2)
          const availableCredit = Math.max(0, creditLimit - currentCredit)
          const message = `🚫 SALE BLOCKED - CREDIT LIMIT EXCEEDED!\n\nCustomer: ${selectedCustomer.name}\nCurrent Balance: Ksh ${currentCredit.toFixed(2)}\nCredit Limit: Ksh ${creditLimit.toFixed(2)}\nAvailable Credit: Ksh ${availableCredit.toFixed(2)}\n\nThis sale requires: Ksh ${balance.toFixed(2)} credit\nWould exceed limit by: Ksh ${exceedsBy}\n\n✅ Solutions:\n1. Increase credit limit in Accounts Management\n2. Customer pays at least Ksh ${(paymentForm.value.amountPaid + parseFloat(exceedsBy)).toFixed(2)}\n3. Reduce cart items`
          showAlert(message, 'error')
          submitting.value = false
          return // Block the sale completely
        }
      }
      
      // Only prompt if credit limit check passed
      const confirmCredit = window.confirm(`Payment is short by Ksh ${balance.toFixed(2)}. Add this balance to the customer account as credit?`)
      if (!confirmCredit) {
        submitting.value = false
        return
      }
      applyCredit = true
    }

    const payload = {
      customer_id: selectedCustomerId.value || null,
      payment_method: paymentForm.value.paymentMethod,
      tax_configuration_id: defaultTaxConfig.value?.id || null,
      discount: promoDiscount.value,
      tax: defaultTaxConfig.value?.rate || 0,
      amount_paid: paymentForm.value.amountPaid,
      apply_credit: applyCredit,
      items: cart.value.map(item => ({
        product_id: item.id,
        quantity: item.quantity,
        price: item.price
      }))
    }

    console.log('📤 Submitting sale with tax config:', payload)

  const res = await axios.post('/api/sales', payload)
    // Sync cart-side promo view with server-calculated promos/discounts (so cart matches receipt)
    if (res.data) {
      const serverDiscount = Number(res.data.discount ?? res.data.sale?.discount ?? promoDiscount.value ?? 0)
      const serverPromos = res.data.applied_promotions || res.data.sale?.applied_promotions
      promoDiscount.value = serverDiscount
      appliedPromos.value = Array.isArray(serverPromos) ? serverPromos : appliedPromos.value
    }
    console.log('✅ Sale recorded:', res.data)

    // Show success message with credit info if applicable
    if (applyCredit && balance > 0) {
      showAlert(`Sale processed! Ksh ${balance.toFixed(2)} added to customer credit balance.`, 'success')
    } else {
      showAlert('Sale processed successfully!', 'success')
    }

    // Generate and show receipt
    generateReceipt(res.data)

    // Reset cart and forms
    cart.value = []
    cartOpen.value = false
    selectedCustomerId.value = ''
    saleForm.value.customer_name = ''
    saleForm.value.notes = ''
    paymentForm.value.amountPaid = 0
    paymentForm.value.paymentMethod = 'Cash'
    await fetchProducts()
  } catch (err) {
    const apiMsg = err.response?.data?.message || err.response?.data?.error
    const friendly = apiMsg && typeof apiMsg === 'string'
      ? apiMsg
      : 'Sale could not be processed right now. Please try again.'

    // Log full error for debugging while keeping the user alert friendly
    console.error('❌ Sale submission failed:', err.response?.data || err.message)

    showAlert(friendly, 'error')
  } finally {
    submitting.value = false
  }
}
</script>

<style scoped>
/* Modern POS System Styles */
* {
  box-sizing: border-box;
}

.pos-system {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
  position: relative;
}

/* Alert System */
.alert-container {
  position: fixed;
  top: 2rem;
  left: 50%;
  transform: translateX(-50%);
  z-index: 2000;
  max-width: 500px;
  width: 90%;
  padding: 1rem 1.5rem;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
  backdrop-filter: blur(10px);
  animation: alertSlideIn 0.3s ease;
}

@keyframes alertSlideIn {
  from {
    opacity: 0;
    transform: translateX(-50%) translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
  }
}

.alert-container.success {
  background: linear-gradient(135deg, rgba(72, 187, 120, 0.95), rgba(56, 161, 105, 0.95));
  color: white;
  border: 1px solid rgba(72, 187, 120, 0.3);
}

.alert-container.error {
  background: linear-gradient(135deg, rgba(229, 62, 62, 0.95), rgba(197, 48, 48, 0.95));
  color: white;
  border: 1px solid rgba(229, 62, 62, 0.3);
}

.alert-container.warning {
  background: linear-gradient(135deg, rgba(237, 137, 54, 0.95), rgba(221, 107, 32, 0.95));
  color: white;
  border: 1px solid rgba(237, 137, 54, 0.3);
}

.alert-container.info {
  background: linear-gradient(135deg, rgba(66, 153, 225, 0.95), rgba(49, 130, 206, 0.95));
  color: white;
  border: 1px solid rgba(66, 153, 225, 0.3);
}

.alert-content {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex: 1;
}

.alert-content i {
  font-size: 1.1rem;
}

.alert-close {
  background: none;
  border: none;
  color: inherit;
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 4px;
  transition: background 0.2s ease;
}

.alert-close:hover {
  background: rgba(255, 255, 255, 0.2);
}

/* Header */
.pos-header {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  padding: 2rem;
  margin: 0 2rem 2rem 2rem;
  border-radius: 20px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 2rem;
}

.header-left {
  flex: 1;
}

.page-title {
  font-size: 2.5rem;
  font-weight: 700;
  color: #2d3748;
  margin: 0 0 0.5rem 0;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.page-title i {
  color: #667eea;
}

.page-subtitle {
  color: #718096;
  font-size: 1.1rem;
  margin: 0;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

/* Search */
.search-container {
  position: relative;
  display: flex;
  align-items: center;
}

.search-input {
  width: 300px;
  padding: 0.875rem 1rem 0.875rem 2.5rem;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 1rem;
  background: white;
  transition: all 0.3s ease;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.search-input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1), 0 4px 12px rgba(0, 0, 0, 0.1);
  transform: translateY(-1px);
}

.search-icon {
  position: absolute;
  left: 1rem;
  color: #a0aec0;
  z-index: 1;
  pointer-events: none;
}

.stats-mini {
  background: linear-gradient(135deg, #667eea, #764ba2);
  border-radius: 12px;
  padding: 1rem 1.5rem;
  color: white;
  text-align: center;
  min-width: 80px;
}

.stat-number {
  display: block;
  font-size: 1.5rem;
  font-weight: 700;
}

.stat-label {
  font-size: 0.85rem;
  opacity: 0.9;
}

/* Barcode Scan */
.barcode-scan {
  position: relative;
  display: flex;
  align-items: center;
}

.barcode-icon {
  position: absolute;
  left: 1rem;
  color: #667eea;
  font-size: 1.2rem;
  z-index: 1;
  pointer-events: none;
}

.barcode-input {
  width: 250px;
  padding: 0.875rem 1rem 0.875rem 2.8rem;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 1rem;
  background: white;
  transition: all 0.3s ease;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.barcode-input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1), 0 4px 12px rgba(0, 0, 0, 0.1);
  transform: translateY(-1px);
}

/* Filters Bar */
.filters-bar {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  margin: 0 2rem 1.5rem 2rem;
  padding: 1.5rem 2rem;
  border-radius: 16px;
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
  display: flex;
  align-items: center;
  gap: 1.5rem;
  flex-wrap: wrap;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  min-width: 200px;
}

.filter-group label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #4a5568;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.filter-group label i {
  color: #667eea;
}

.filter-select {
  padding: 0.75rem 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 1rem;
  background: white;
  color: #2d3748;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.filter-select:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.filter-select:hover {
  border-color: #cbd5e0;
}

.clear-filters-btn {
  margin-left: auto;
  background: linear-gradient(135deg, #f56565, #e53e3e);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  box-shadow: 0 4px 10px rgba(245, 101, 101, 0.3);
}

.clear-filters-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 15px rgba(245, 101, 101, 0.4);
}

/* Main Content */
.pos-main {
  display: grid;
  grid-template-columns: 1fr 400px;
  gap: 2rem;
  padding: 0 2rem;
  height: calc(100vh - 200px);
}

/* Products Section */
.products-section {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 20px;
  padding: 2rem;
  overflow-y: auto;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

/* Loading State */
.loading-state {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 400px;
  flex-direction: column;
}

.loading-spinner {
  text-align: center;
  color: #667eea;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid rgba(102, 126, 234, 0.2);
  border-left: 4px solid #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Products Grid */
.products-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1.5rem;
}

.product-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease;
  position: relative;
  cursor: pointer;
  overflow: hidden;
}

.product-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
}

.product-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

.product-image {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  background: linear-gradient(135deg, #667eea, #764ba2);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.5rem;
  margin-bottom: 1rem;
}

.product-info {
  margin-bottom: 1rem;
}

.product-name {
  font-size: 1.25rem;
  font-weight: 600;
  color: #2d3748;
  margin: 0 0 1rem 0;
}

.product-price {
  display: flex;
  align-items: baseline;
  gap: 0.25rem;
  margin-bottom: 0.75rem;
}

.currency {
  font-size: 0.9rem;
  color: #718096;
  font-weight: 500;
}

.amount {
  font-size: 1.5rem;
  font-weight: 700;
  color: #2d3748;
}

.stock-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
  font-weight: 500;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  width: fit-content;
}

.stock-info.in-stock {
  background: rgba(72, 187, 120, 0.1);
  color: #38a169;
}

.stock-info.low-stock {
  background: rgba(237, 137, 54, 0.1);
  color: #dd6b20;
}

.stock-info.out-of-stock {
  background: rgba(229, 62, 62, 0.1);
  color: #e53e3e;
}

.add-btn {
  position: absolute;
  bottom: 1rem;
  right: 1rem;
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, #48bb78, #38a169);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transform: scale(0.8);
  transition: all 0.3s ease;
}

.product-card:hover .add-btn {
  opacity: 1;
  transform: scale(1);
}

.stock-badge {
  position: absolute;
  top: 1rem;
  right: 1rem;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stock-badge.in-stock {
  background: #48bb78;
  color: white;
}

.stock-badge.low-stock {
  background: #ed8936;
  color: white;
}

.stock-badge.out-of-stock {
  background: #e53e3e;
  color: white;
}

/* Empty States */
.empty-state, .no-results {
  grid-column: 1 / -1;
  text-align: center;
  padding: 4rem 2rem;
  color: #718096;
}

.empty-state i, .no-results i {
  font-size: 4rem;
  margin-bottom: 1rem;
  opacity: 0.3;
}

.empty-state h3, .no-results h3 {
  font-size: 1.5rem;
  margin: 0 0 1rem 0;
  color: #4a5568;
}

.empty-state p, .no-results p {
  margin: 0;
}

/* Cart Sidebar */
.cart-sidebar {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 20px;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  transition: all 0.3s ease;
}

.cart-header {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  padding: 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.cart-header h2 {
  margin: 0;
  font-size: 1.25rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.cart-close {
  background: rgba(255, 255, 255, 0.2);
  border: none;
  color: white;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  cursor: pointer;
  display: none;
}

.cart-content {
  flex: 1;
  padding: 1.5rem;
  overflow-y: auto;
}

/* Empty Cart */
.empty-cart {
  text-align: center;
  padding: 3rem 1rem;
  color: #718096;
}

.empty-cart i {
  font-size: 3rem;
  margin-bottom: 1rem;
  opacity: 0.3;
}

.empty-cart p {
  font-size: 1.1rem;
  margin: 0 0 0.5rem 0;
  color: #4a5568;
}

/* Cart Items */
.cart-items {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.cart-item {
  background: #f8fafc;
  border-radius: 12px;
  padding: 1rem;
  display: grid;
  grid-template-columns: 1fr auto auto auto;
  gap: 1rem;
  align-items: center;
  border: 1px solid #e2e8f0;
}

.item-info h4 {
  margin: 0 0 0.25rem 0;
  font-size: 0.95rem;
  color: #2d3748;
}

.item-price {
  margin: 0;
  font-size: 0.85rem;
  color: #718096;
}

.item-controls {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

 qty-btn {
  width: 28px;
  height: 28px;
  border: none;
  background: #e2e8f0;
  color: #4a5568;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
}

 qty-btn:hover {
  background: #cbd5e0;
}

.quantity {
  font-weight: 600;
  color: #2d3748;
  min-width: 20px;
  text-align: center;
}

.item-total {
  font-weight: 600;
  color: #2d3748;
}

.remove-btn {
  width: 28px;
  height: 28px;
  border: none;
  background: rgba(229, 62, 62, 0.1);
  color: #e53e3e;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
}

.remove-btn:hover {
  background: #e53e3e;
  color: white;
}

/* Cart Summary */
.cart-summary {
  border-top: 2px solid #e2e8f0;
  padding-top: 1rem;
  margin-top: 1rem;
}

.summary-line {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
  font-size: 0.95rem;
}

.total-line {
  font-size: 1.1rem;
  font-weight: 700;
  color: #2d3748;
  border-top: 1px solid #e2e8f0;
  padding-top: 0.5rem;
  margin-top: 0.5rem;
}

/* Applied Promotions Section */
.applied-promos-section {
  background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
  border: 2px solid #38bdf8;
  border-radius: 8px;
  padding: 0.75rem;
  margin: 0.75rem 0;
}

.promo-header {
  font-weight: 600;
  color: #0369a1;
  font-size: 0.9rem;
  margin-bottom: 0.5rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.promo-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.4rem 0.5rem;
  background: rgba(255, 255, 255, 0.6);
  border-radius: 4px;
  margin-bottom: 0.4rem;
  font-size: 0.9rem;
}

.promo-name {
  color: #0c4a6e;
  font-weight: 500;
  flex: 1;
}

.promo-discount {
  color: #16a34a;
  font-weight: 700;
}

.promo-item.muted {
  color: #0f172a;
  font-style: italic;
  opacity: 0.85;
}

.promo-total {
  margin-top: 0.5rem;
  padding-top: 0.5rem;
  border-top: 1px dashed #38bdf8;
  color: #0369a1;
}

/* Payment Section */
.payment-section {
  border-top: 2px solid #e2e8f0;
  padding-top: 1.5rem;
  margin-top: 1.5rem;
}

.payment-section h3 {
  margin: 0 0 1rem 0;
  color: #2d3748;
  font-size: 1.1rem;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #4a5568;
  font-size: 0.9rem;
}

.amount-input {
  position: relative;
  display: flex;
  align-items: center;
}

.currency-symbol {
  position: absolute;
  left: 1rem;
  color: #718096;
  font-weight: 500;
  z-index: 1;
}

.form-group input,
.form-group textarea,
.form-group select {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.95rem;
  transition: all 0.3s ease;
  background: white;
  color: #2d3748;
}

.amount-input input {
  padding-left: 2.5rem;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.payment-method-select {
  cursor: pointer;
  font-weight: 500;
}

/* Change Display */
.change-display {
  margin: 1rem 0;
}

.change-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem;
  border-radius: 10px;
  font-weight: 500;
}

.change-info.insufficient {
  background: rgba(229, 62, 62, 0.1);
  color: #e53e3e;
  border: 1px solid rgba(229, 62, 62, 0.2);
}

.change-info.exact {
  background: rgba(72, 187, 120, 0.1);
  color: #38a169;
  border: 1px solid rgba(72, 187, 120, 0.2);
}

.change-info.overpaid {
  background: rgba(66, 153, 225, 0.1);
  color: #3182ce;
  border: 1px solid rgba(66, 153, 225, 0.2);
}

.change-text {
  display: flex;
  flex-direction: column;
}

.change-label {
  font-size: 0.85rem;
  opacity: 0.8;
}

.change-amount {
  font-size: 1.1rem;
  font-weight: 700;
}

/* Credit Status Info */
.credit-status-info {
  margin: 1rem 0;
  padding: 1rem;
  background: #f0f9ff;
  border-radius: 10px;
  border: 1px solid #bfdbfe;
}

.credit-details {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.credit-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.95rem;
}

.credit-label {
  color: #4a5568;
  font-weight: 500;
}

.credit-value {
  font-weight: 700;
  padding: 0.25rem 0.75rem;
  border-radius: 6px;
  font-size: 0.9rem;
}

.credit-value.success {
  background: #dcfce7;
  color: #166534;
}

.credit-value.warning {
  background: #fef3c7;
  color: #92400e;
}

.credit-value.danger {
  background: #fee2e2;
  color: #991b1b;
}

.credit-value.info {
  background: #dbeafe;
  color: #1e40af;
}

.limit-warning {
  margin-top: 0.75rem;
  padding: 0.75rem;
  background: #fee2e2;
  border: 1px solid #fecaca;
  border-radius: 6px;
  color: #991b1b;
  font-weight: 600;
  text-align: center;
}

/* Checkout Button */
.checkout-btn {
  width: 100%;
  padding: 1rem;
  background: linear-gradient(135deg, #48bb78, #38a169);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  margin-top: 1rem;
}

.checkout-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(72, 187, 120, 0.3);
}

.checkout-btn:disabled {
  background: #cbd5e0;
  color: #a0aec0;
  cursor: not-allowed;
  transform: none;
}

.credit-block-msg {
  margin-top: 0.75rem;
  display: flex;
  gap: 0.5rem;
  align-items: flex-start;
  background: #fef2f2;
  color: #991b1b;
  border: 1px solid #fecaca;
  border-radius: 8px;
  padding: 0.75rem;
  font-size: 0.95rem;
}

.credit-block-msg i {
  margin-top: 2px;
}

.checkout-btn.processing {
  background: #667eea;
}

.btn-loading {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-left: 2px solid white;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.btn-content {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

/* Cart Toggle Button */
.cart-toggle {
  position: fixed;
  bottom: 2rem;
  right: 2rem;
  width: 60px;
  height: 60px;
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  border: none;
  border-radius: 50%;
  cursor: pointer;
  font-size: 1.25rem;
  display: none;
  align-items: center;
  justify-content: center;
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
  transition: all 0.3s ease;
  z-index: 1000;
}

.cart-toggle:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
}

.cart-toggle.has-items {
  background: linear-gradient(135deg, #48bb78, #38a169);
}

.cart-count {
  position: absolute;
  top: -8px;
  right: -8px;
  background: #e53e3e;
  color: white;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 700;
}

/* Receipt Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  animation: modalFadeIn 0.3s ease;
}

@keyframes modalFadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.receipt-modal {
  background: white;
  border-radius: 20px;
  max-width: 500px;
  width: 90%;
  max-height: 90vh;
  overflow: hidden;
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
  animation: modalSlideIn 0.3s ease;
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: translateY(20px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.receipt-header {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  padding: 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.receipt-header h2 {
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.close-btn {
  background: rgba(255, 255, 255, 0.2);
  border: none;
  color: white;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  cursor: pointer;
  transition: background 0.2s ease;
}

.close-btn:hover {
  background: rgba(255, 255, 255, 0.3);
}

.receipt-content {
  max-height: 60vh;
  overflow-y: auto;
  padding: 1.5rem;
}

.receipt-paper {
  background: white;
  border: 1px dashed #ccc;
  padding: 1.5rem;
  font-family: 'Courier New', monospace;
  font-size: 14px;
  line-height: 1.5;
  max-width: 400px;
  margin: 0 auto;
}

.receipt-business-header {
  text-align: center;
  margin-bottom: 1.5rem;
}

.receipt-business-header h1 {
  margin: 0 0 0.5rem 0;
  font-size: 18px;
  font-weight: bold;
  color: #2c5530;
}

.receipt-business-header p {
  margin: 2px 0;
  font-size: 12px;
  color: #555;
}

.receipt-sale-info {
  margin: 1rem 0;
}

.receipt-sale-info p {
  margin: 5px 0;
  display: flex;
  justify-content: space-between;
}

.receipt-items-header {
  display: grid;
  grid-template-columns: 3fr 1fr 1fr 1fr;
  font-weight: bold;
  margin-bottom: 5px;
  text-align: center;
}

.receipt-items-header span:first-child {
  text-align: left;
}

.receipt-item {
  display: grid;
  grid-template-columns: 3fr 1fr 1fr 1fr;
  margin: 3px 0;
  text-align: center;
}

.receipt-item .item-name {
  text-align: left;
}

.receipt-totals {
  margin: 1rem 0;
}

.receipt-total-line {
  display: flex;
  justify-content: space-between;
  margin: 5px 0;
  padding: 2px 0;
}

.receipt-total-line:nth-child(2) {
  border-top: 1px solid #333;
  border-bottom: 1px solid #333;
  font-weight: bold;
  font-size: 16px;
}

.receipt-footer {
  text-align: center;
  margin-top: 1.5rem;
}

.receipt-credit-note {
  text-align: center;
  margin: 1rem 0;
  padding: 0.75rem;
  background: #fef3c7;
  border-radius: 6px;
  border: 1px dashed #f59e0b;
}

.receipt-credit-note p {
  margin: 0;
  font-size: 13px;
  color: #92400e;
  font-weight: 500;
}

.thank-you {
  font-weight: bold;
  font-size: 16px;
  margin: 10px 0;
  color: #2c5530;
}

.return-policy, .service-notice {
  font-size: 11px;
  color: #666;
  margin: 5px 0;
}

.receipt-divider {
  text-align: center;
  margin: 10px 0;
  color: #666;
  font-size: 12px;
}

.receipt-divider-thin {
  text-align: center;
  margin: 5px 0;
  color: #999;
  font-size: 12px;
}

.receipt-actions {
  padding: 1.5rem;
  border-top: 1px solid #e2e8f0;
  background: #f8fafc;
  display: flex;
  gap: 1rem;
  justify-content: center;
}

.print-btn {
  background: linear-gradient(135deg, #48bb78, #38a169);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 10px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.print-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(72, 187, 120, 0.3);
}

.secondary-btn {
  background: #e2e8f0;
  color: #4a5568;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 10px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.3s ease;
}

.secondary-btn:hover {
  background: #cbd5e0;
}

/* Responsive Design */
@media (max-width: 1200px) {
  .pos-main {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .cart-sidebar {
    position: fixed;
    top: 0;
    right: -450px;
    width: 400px;
    height: 100vh;
    z-index: 1500;
    border-radius: 0;
    transition: right 0.3s ease;
  }
  
  .cart-sidebar.cart-open {
    right: 0;
  }
  
  .cart-close {
    display: flex;
  }
  
  .cart-toggle {
    display: flex;
  }
}

@media (max-width: 768px) {
  .pos-header {
    margin: 0 1rem 1rem 1rem;
    padding: 1.5rem;
  }
  
  .header-content {
    flex-direction: column;
    gap: 1rem;
  }
  
  .header-right {
    width: 100%;
    justify-content: space-between;
    flex-wrap: wrap;
  }
  
  .search-input {
    width: 100%;
    max-width: 250px;
  }

  .barcode-input {
    width: 100%;
    max-width: 200px;
  }
  
  .filters-bar {
    margin: 0 1rem 1rem 1rem;
    padding: 1rem;
    flex-direction: column;
    align-items: stretch;
  }

  .filter-group {
    min-width: 100%;
  }

  .clear-filters-btn {
    margin-left: 0;
    width: 100%;
    justify-content: center;
  }
  
  .pos-main {
    padding: 0 1rem;
    height: auto;
  }
  
  .products-grid {
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
  }
  
  .cart-sidebar {
    width: 100%;
    right: -100%;
  }
  
  .page-title {
    font-size: 2rem;
  }
}

@media (max-width: 480px) {
  .pos-header {
    margin: 0.5rem;
    padding: 1rem;
  }

  .page-title {
    font-size: 1.5rem;
  }

  .page-subtitle {
    font-size: 0.9rem;
  }

  .header-right {
    gap: 0.75rem;
  }

  .filters-bar {
    margin: 0.5rem;
    padding: 0.75rem;
  }

  .filter-group label {
    font-size: 0.8rem;
  }

  .filter-select {
    padding: 0.6rem 0.75rem;
    font-size: 0.9rem;
  }

  .products-section {
    padding: 1rem;
  }

  .products-grid {
    grid-template-columns: 1fr;
    gap: 0.75rem;
  }
  
  .product-card {
    padding: 1rem;
  }
  
  .product-name {
    font-size: 1rem;
  }

  .search-input,
  .barcode-input {
    width: 100%;
    max-width: none;
    font-size: 0.9rem;
    padding: 0.75rem 1rem 0.75rem 2.5rem;
  }

  .stats-mini {
    padding: 0.75rem 1rem;
    min-width: 60px;
  }

  .stat-number {
    font-size: 1.25rem;
  }

  .stat-label {
    font-size: 0.75rem;
  }
  
  .cart-item {
    grid-template-columns: 1fr;
    gap: 0.5rem;
    text-align: center;
  }
  
  .item-controls {
    justify-content: center;
  }

  .checkout-btn {
    padding: 0.875rem;
    font-size: 1rem;
  }
}

/* Print Styles */
@media print {
  .receipt-modal {
    box-shadow: none;
    border: none;
    max-width: none;
    width: 80mm;
  }
  
  .receipt-header, .receipt-actions {
    display: none !important;
  }
  
  .receipt-content {
    padding: 0;
  }
  
  .receipt-paper {
    border: none;
    padding: 5mm;
    font-size: 11px;
  }
}
</style>
