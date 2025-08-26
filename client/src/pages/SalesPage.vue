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
              placeholder="Search products..." 
            />
          </div>
          <div class="stats-mini">
            <span class="stat-number">{{ filteredProducts.length }}</span>
            <span class="stat-label">Products</span>
          </div>
        </div>
      </div>
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
              <div class="summary-line total-line">
                <span>Total:</span>
                <span>Ksh {{ formatPrice(total) }}</span>
              </div>
            </div>

            <!-- Payment Section -->
            <div class="payment-section">
              <h3>Payment Details</h3>
              
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
            <!-- Receipt Header -->
            <div class="receipt-business-header">
              <h1>🌾 AGROVET SUPPLIES</h1>
              <p>Your Trusted Agricultural Partner</p>
              <p>📍 Kerugoya, Kirinyaga County</p>
              <p>📞 Contact: +254-XXX-XXXX</p>
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
              <div class="receipt-total-line">
                <span><strong>TOTAL:</strong></span>
                <span><strong>Ksh {{ receiptData.total }}</strong></span>
              </div>
              <div class="receipt-total-line">
                <span>Amount Paid:</span>
                <span>Ksh {{ receiptData.amountPaid }}</span>
              </div>
              <div class="receipt-total-line" v-if="receiptData.change > 0">
                <span>Change:</span>
                <span>Ksh {{ receiptData.change }}</span>
              </div>
              <div class="receipt-divider">═══════════════════════════</div>
            </div>

            <!-- Footer -->
            <div class="receipt-footer">
              <p v-if="receiptData.notes"><strong>Notes:</strong> {{ receiptData.notes }}</p>
              <p class="thank-you">🙏 Thank you for your business!</p>
              <p class="return-policy">* Return policy: 7 days with receipt</p>
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

const alert = ref({
  show: false,
  message: '',
  type: 'success' // success, error, warning, info
})

const saleForm = ref({
  customer_name: '',
  notes: ''
})

const paymentForm = ref({
  amountPaid: 0
})

const showReceipt = ref(false)
const receiptData = ref({})
const receiptContent = ref(null)

// Computed properties
const filteredProducts = computed(() => {
  if (!searchQuery.value.trim()) {
    return products.value
  }
  
  const query = searchQuery.value.toLowerCase().trim()
  return products.value.filter(product => 
    product.name.toLowerCase().includes(query) ||
    product.price.toString().includes(query)
  )
})

const total = computed(() =>
  cart.value.reduce((sum, item) => sum + item.price * item.quantity, 0)
)

const change = computed(() => {
  return paymentForm.value.amountPaid - total.value
})

const canProcessSale = computed(() => {
  return cart.value.length > 0 && paymentForm.value.amountPaid >= total.value
})

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
onMounted(fetchProducts)

async function fetchProducts() {
  loading.value = true
  try {
    const res = await axios.get('http://localhost:8000/products')
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

// Cart functions
function addToCart(product) {
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
  
  // Auto-open cart on first item
  if (cart.value.length === 1) {
    cartOpen.value = true
  }
}

function removeFromCart(id) {
  const item = cart.value.find(item => item.id === id)
  if (item) {
    cart.value = cart.value.filter(item => item.id !== id)
    showAlert(`${item.name} removed from cart`, 'info')
  }
}

function increaseQuantity(item) {
  const product = products.value.find(p => p.id === item.id)
  if (item.quantity >= product.stock_quantity) {
    showAlert('Stock limit reached!', 'warning')
    return
  }
  item.quantity++
}

function decreaseQuantity(item) {
  if (item.quantity > 1) {
    item.quantity--
  } else {
    removeFromCart(item.id)
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
  if (paymentForm.value.amountPaid < total.value) return 'Insufficient payment'
  return 'Process Sale'
}

// Receipt functions
function generateReceipt(saleResponse) {
  const now = new Date()
  const receiptNumber = `RCP-${Date.now().toString().slice(-8)}`
  
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
    subtotal: Number(total.value).toFixed(2),
    total: Number(total.value).toFixed(2),
    amountPaid: Number(paymentForm.value.amountPaid).toFixed(2),
    change: Number(change.value).toFixed(2),
    notes: saleForm.value.notes,
    saleId: saleResponse.id || 'N/A'
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

// Submit sale
async function submitSale() {
  if (!canProcessSale.value) {
    if (cart.value.length === 0) {
      showAlert('Add products to the cart before submitting a sale.', 'warning')
    } else if (paymentForm.value.amountPaid < total.value) {
      showAlert('Payment amount is insufficient.', 'warning')
    }
    return
  }

  submitting.value = true
  try {
    const payload = {
      customer_name: saleForm.value.customer_name || null,
      notes: saleForm.value.notes || null,
      amount_paid: paymentForm.value.amountPaid,
      change_given: change.value,
      items: cart.value.map(item => ({
        product_id: item.id,
        quantity: item.quantity,
        price: item.price
      }))
    }

    console.log('📤 Submitting sale payload:', payload)

    const res = await axios.post('http://localhost:8000/sales', payload)
    console.log('✅ Sale recorded:', res.data)

    showAlert('Sale processed successfully!', 'success')

    // Generate and show receipt
    generateReceipt(res.data)

    // Reset cart and forms
    cart.value = []
    cartOpen.value = false
    saleForm.value.customer_name = ''
    saleForm.value.notes = ''
    paymentForm.value.amountPaid = 0
    await fetchProducts()
  } catch (err) {
    console.error('❌ Sale submission failed:', err.response?.data || err.message)
    showAlert('Sale failed: ' + (err.response?.data?.message || err.message), 'error')
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

.qty-btn {
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

.qty-btn:hover {
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
.form-group textarea {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.95rem;
  transition: all 0.3s ease;
}

.amount-input input {
  padding-left: 2.5rem;
}

.form-group input:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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
  }
  
  .search-input {
    width: 200px;
  }
  
  .pos-main {
    padding: 0 1rem;
  }
  
  .products-grid {
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
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
  .products-grid {
    grid-template-columns: 1fr;
  }
  
  .search-input {
    width: 150px;
  }
  
  .cart-item {
    grid-template-columns: 1fr;
    gap: 0.5rem;
    text-align: center;
  }
  
  .item-controls {
    justify-content: center;
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
