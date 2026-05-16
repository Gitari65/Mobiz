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
              <div v-if="product.category" class="product-category">
                <i class="fas fa-tag"></i>
                <span>{{ product.category }}</span>
              </div>
              <div class="product-price">
                <span class="currency">Ksh</span>
                <span class="amount">{{ formatPrice(getProductDefaultDisplayPrice(product)) }}</span>
              </div>
              <div v-if="getProductPricingWarning(product)" class="pricing-warning-badge">
                {{ getProductPricingWarning(product) }}
              </div>
              <div class="stock-info" :class="getStockClass(getAvailableQuantity(product))">
                <i class="fas fa-boxes"></i>
                <span>
                  {{ getAvailableQuantity(product) }}
                  {{ getProductDisplayUom(product) }} in stock
                </span>
              </div>
              <!-- Show conversion info if bulk purchase is configured -->
              <div v-if="product.sale_uom_id && product.purchase_uom_id" class="conversion-info">
                <small>
                  Buy: {{ product.conversion_ratio }} x {{ getUomLabel(product, 'sale') }} = 1 x {{ getUomLabel(product, 'purchase') }}
                </small>
              </div>
            </div>
            <div class="add-btn">
              <i class="fas fa-plus"></i>
            </div>
            <div class="stock-badge" :class="getStockClass(getAvailableQuantity(product))">
              {{ getStockStatus(getAvailableQuantity(product)) }}
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

          <!-- UoM Selection Modal -->
          <div v-if="showUoMSelector" class="modal-overlay">
            <div class="modal-content" @click.stop>
              <h3>Select Unit of Measure</h3>
              <p class="modal-subtitle">Choose how to sell {{ uoMSelectorProduct?.name }}</p>
              <div class="uom-options">
                <!-- Show all sale UOMs if available (new multi-UOM system) -->
                <button
                  v-for="uom in getProductSaleUoms(uoMSelectorProduct)"
                  :key="uom.id"
                  @click="selectUoM(uom)"
                  class="uom-option"
                >
                  <i class="fas fa-droplet"></i>
                  <strong>{{ uom.name }}</strong>
                  <small>{{ uom.abbreviation }} - Ksh {{ formatPrice(getEffectiveCartItemPrice(uoMSelectorProduct, uom.id, getUomSpecificPrice(uoMSelectorProduct, uom.id))) }}</small>
                </button>
                <!-- Fallback to legacy single sale UOM -->
                <button
                  v-if="!getProductSaleUoms(uoMSelectorProduct).length && getProductSaleUom(uoMSelectorProduct)"
                  @click="selectUoM(getProductSaleUom(uoMSelectorProduct))"
                  class="uom-option"
                >
                  <i class="fas fa-droplet"></i>
                  <strong>{{ getProductSaleUom(uoMSelectorProduct)?.name }}</strong>
                  <small>{{ getProductSaleUom(uoMSelectorProduct)?.abbreviation }} - Ksh {{ formatPrice(getEffectiveCartItemPrice(uoMSelectorProduct, getProductSaleUom(uoMSelectorProduct)?.id, getUomSpecificPrice(uoMSelectorProduct, getProductSaleUom(uoMSelectorProduct)?.id))) }}</small>
                </button>
                <button
                  v-if="!getProductSaleUoms(uoMSelectorProduct).length && !getProductSaleUom(uoMSelectorProduct) && getProductPurchaseUom(uoMSelectorProduct)"
                  @click="selectUoM(getProductPurchaseUom(uoMSelectorProduct))"
                  class="uom-option"
                >
                  <i class="fas fa-boxes"></i>
                  <strong>{{ getProductPurchaseUom(uoMSelectorProduct)?.name }}</strong>
                  <small>{{ getProductPurchaseUom(uoMSelectorProduct)?.abbreviation }} - Ksh {{ formatPrice(getEffectiveCartItemPrice(uoMSelectorProduct, getProductPurchaseUom(uoMSelectorProduct)?.id, getUomSpecificPrice(uoMSelectorProduct, getProductPurchaseUom(uoMSelectorProduct)?.id))) }}</small>
                </button>
                <button
                  v-if="!getProductSaleUoms(uoMSelectorProduct).length && !getProductSaleUom(uoMSelectorProduct) && !getProductPurchaseUom(uoMSelectorProduct) && getProductBaseUom(uoMSelectorProduct)"
                  @click="selectUoM(getProductBaseUom(uoMSelectorProduct))"
                  class="uom-option"
                >
                  <i class="fas fa-ruler"></i>
                  <strong>{{ getProductBaseUom(uoMSelectorProduct)?.name }}</strong>
                  <small>{{ getProductBaseUom(uoMSelectorProduct)?.abbreviation }} - Ksh {{ formatPrice(getEffectiveCartItemPrice(uoMSelectorProduct, getProductBaseUom(uoMSelectorProduct)?.id, getUomSpecificPrice(uoMSelectorProduct, getProductBaseUom(uoMSelectorProduct)?.id))) }}</small>
                </button>
              </div>
              <button @click="showUoMSelector = false" class="modal-close-btn">Cancel</button>
            </div>
          </div>

          <!-- Cart Items -->
          <div v-else class="cart-items">
            <div class="cart-actions-row">
              <span class="selection-count">
                {{ selectedCartItemIds.length }} selected
              </span>
              <div class="cart-actions-buttons">
                <button
                  type="button"
                  class="cart-action-btn"
                  @click="deleteSelectedCartItems"
                  :disabled="selectedCartItemIds.length === 0"
                >
                  <i class="fas fa-trash"></i>
                  Delete Selected
                </button>
                <button
                  type="button"
                  class="cart-action-btn danger"
                  @click="clearCart"
                  :disabled="cart.length === 0"
                >
                  <i class="fas fa-broom"></i>
                  Clear All
                </button>
              </div>
            </div>

            <div
              v-for="item in cart"
              :key="item.cart_uid"
              class="cart-item"
              :class="{ selected: selectedCartItemIds.includes(item.cart_uid) }"
            >
              <div class="item-select">
                <input
                  type="checkbox"
                  :checked="selectedCartItemIds.includes(item.cart_uid)"
                  @change="toggleCartItemSelection(item.cart_uid)"
                />
              </div>
              <div class="item-info">
                <h4>{{ item.name }}</h4>
                <p class="item-price">Ksh {{ formatPrice(item.price) }}</p>
                <p class="item-uom-label">Selling as: {{ getCartItemUomLabel(item) }}</p>
                <div v-if="hasMultipleCartItemUoms(item)" class="item-uom-selector">
                  <select v-model="item.uom_id" class="uom-select" @change="updateItemUoM(item)">
                    <option v-for="uom in getCartItemUomOptions(item)" :key="uom.id" :value="uom.id">
                      {{ getUomOptionLabel(getProduct(item.id), uom) }}
                    </option>
                  </select>
                </div>
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
              <div class="item-actions">
                <button @click="startEditCartItem(item)" class="edit-btn" title="Edit item">
                  <i class="fas fa-pen"></i>
                </button>
                <button @click="removeFromCart(item.cart_uid)" class="remove-btn" title="Delete item">
                  <i class="fas fa-trash"></i>
                </button>
              </div>

              <div v-if="editingCartItemId === item.cart_uid" class="item-edit-row">
                <div class="edit-field">
                  <label>Qty</label>
                  <input type="number" min="1" step="1" v-model.number="editCartForm.quantity" />
                </div>
                <div class="edit-actions">
                  <button type="button" class="save-edit-btn" @click="applyCartItemEdit(item)">Save</button>
                  <button type="button" class="cancel-edit-btn" @click="cancelCartItemEdit">Cancel</button>
                </div>
              </div>
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

              <div v-if="isMpesaPayment" class="mpesa-panel">
                <div class="form-group">
                  <label>M-Pesa Phone Number</label>
                  <input 
                    type="tel"
                    v-model="paymentForm.mpesaPhoneNumber"
                    placeholder="e.g. 0712345678 or 254712345678"
                  />
                </div>

                <div class="mpesa-actions">
                  <button
                    type="button"
                    class="mpesa-btn"
                    @click="initiateMpesaPayment"
                    :disabled="submitting || paymentForm.amountPaid <= 0 || !paymentForm.mpesaPhoneNumber || paymentForm.mpesaStatus === 'initiating'"
                  >
                    <i :class="paymentForm.mpesaStatus === 'initiating' ? 'fas fa-spinner fa-spin' : 'fas fa-mobile-alt'"></i>
                    {{ paymentForm.mpesaCheckoutRequestId ? 'Re-initiate STK Push' : 'Initiate STK Push' }}
                  </button>
                  <button
                    v-if="paymentForm.mpesaCheckoutRequestId"
                    type="button"
                    class="mpesa-btn secondary"
                    @click="checkMpesaPaymentStatus"
                    :disabled="submitting || paymentForm.mpesaStatus === 'checking'"
                  >
                    <i :class="paymentForm.mpesaStatus === 'checking' ? 'fas fa-spinner fa-spin' : 'fas fa-sync-alt'"></i>
                    Check Status
                  </button>
                </div>

                <div v-if="paymentForm.mpesaCheckoutRequestId" class="mpesa-status-card" :class="mpesaStatusClass">
                  <div class="mpesa-status-top">
                    <strong>M-Pesa Status:</strong>
                    <span class="mpesa-status-pill">{{ paymentForm.mpesaStatusLabel }}</span>
                  </div>
                  <p v-if="paymentForm.mpesaCustomerMessage" class="mpesa-status-text">{{ paymentForm.mpesaCustomerMessage }}</p>
                  <p v-if="paymentForm.mpesaReceiptNumber" class="mpesa-status-text">Receipt: <strong>{{ paymentForm.mpesaReceiptNumber }}</strong></p>
                  <p class="mpesa-status-text subtle">Checkout ID: {{ paymentForm.mpesaCheckoutRequestId }}</p>
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
              <img
                v-if="printerSettings.show_logo && printerSettings.receipt_logo_url"
                :src="printerSettings.receipt_logo_url"
                alt="Business Logo"
                class="receipt-company-logo"
              />
              <template v-if="headerLines.length">
                <h1 v-if="headerLines[0]" class="receipt-title">{{ headerLines[0] }}</h1>
                <p v-for="(line, idx) in headerLines.slice(1)" :key="idx" class="receipt-subline">{{ line }}</p>
              </template>
              <div class="receipt-divider">═══════════════════════════</div>
            </div>

            <!-- Sale Info -->
            <div class="receipt-sale-info">
              <p><strong>Receipt #:</strong> {{ receiptData.receiptNumber }}</p>
              <p v-if="receiptData.invoiceNumber"><strong>Invoice #:</strong> {{ receiptData.invoiceNumber }}</p>
              <p><strong>Date:</strong> {{ receiptData.date }}</p>
              <p><strong>Time:</strong> {{ receiptData.time }}</p>
              <p v-if="receiptData.customer"><strong>Customer:</strong> {{ receiptData.customer }}</p>
              <p><strong>Payment:</strong> {{ receiptData.paymentMethod || 'Cash' }}</p>
              <p v-if="receiptData.mpesaPhoneNumber"><strong>M-Pesa Phone:</strong> {{ receiptData.mpesaPhoneNumber }}</p>
              <p v-if="receiptData.mpesaReceiptNumber"><strong>M-Pesa Receipt:</strong> {{ receiptData.mpesaReceiptNumber }}</p>
              <p v-if="receiptData.mpesaCheckoutRequestId"><strong>Checkout ID:</strong> {{ receiptData.mpesaCheckoutRequestId }}</p>
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
                <span class="item-name">{{ item.name }} <small v-if="item.uom">({{ item.uom }})</small></span>
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

    <!-- Process Sale Modal -->
    <div v-if="showProcessSaleModal" class="modal-overlay process-sale-overlay" @click.self="showProcessSaleModal = false">
      <div class="process-sale-modal">
        <div class="modal-header">
          <h2>
            <i class="fas fa-check-circle"></i>
            Process Sale
          </h2>
          <button @click="showProcessSaleModal = false" class="modal-close-btn">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div class="modal-body">
          <!-- M-Pesa Status Alert -->
          <div v-if="isMpesaPayment" :class="['mpesa-status-alert', getMpesaAlertClass()]">
            <div class="mpesa-alert-header">
              <i :class="getMpesaAlertIcon()"></i>
              <span class="mpesa-status-label">{{ getMpesaStatusLabel() }}</span>
            </div>
            <div class="mpesa-alert-details">
              <div v-if="paymentForm.mpesaPhoneNumber" class="detail-row">
                <span class="label">Phone:</span>
                <span class="value">{{ paymentForm.mpesaPhoneNumber }}</span>
              </div>
              <div v-if="paymentForm.mpesaReceiptNumber" class="detail-row">
                <span class="label">Receipt:</span>
                <span class="value">{{ paymentForm.mpesaReceiptNumber }}</span>
              </div>
              <div class="detail-row">
                <span class="label">Status:</span>
                <span class="value" :class="['status-badge', paymentForm.mpesaStatus]">
                  {{ paymentForm.mpesaStatusLabel }}
                </span>
              </div>
            </div>
          </div>

          <!-- Order Summary -->
          <div class="order-summary-section">
            <h3>Order Summary</h3>
            <div class="summary-items">
              <div v-for="item in cart" :key="item.cart_uid" class="summary-item">
                <span class="item-name">{{ item.name }} × {{ item.quantity }}</span>
                <span class="item-total">Ksh {{ formatPrice(item.price * item.quantity) }}</span>
              </div>
            </div>
            <div class="summary-totals">
              <div class="total-row">
                <span>Subtotal:</span>
                <span>Ksh {{ formatPrice(subtotal) }}</span>
              </div>
              <div v-if="promoDiscount > 0" class="total-row discount">
                <span>Discount:</span>
                <span>-Ksh {{ formatPrice(promoDiscount) }}</span>
              </div>
              <div v-if="totalTax > 0" class="total-row tax">
                <span>Tax:</span>
                <span>Ksh {{ formatPrice(totalTax) }}</span>
              </div>
              <div class="total-row grand-total">
                <span>Total:</span>
                <span>Ksh {{ formatPrice(netTotal) }}</span>
              </div>
            </div>
          </div>

          <!-- Payment Details -->
          <div class="payment-section">
            <h3>Payment Details</h3>
            
            <div class="form-group">
              <label>Payment Method</label>
              <select v-model="paymentForm.paymentMethod" class="form-input">
                <option v-for="method in paymentMethods" :key="method" :value="method">
                  {{ method }}
                </option>
              </select>
            </div>

            <div v-if="isMpesaPayment" class="form-group">
              <label>M-Pesa Phone Number</label>
              <input 
                v-model="paymentForm.mpesaPhoneNumber" 
                type="tel" 
                placeholder="0748344757 or +254748344757"
                class="form-input"
                @keyup="normalizePhoneInput"
              />
            </div>

            <div class="form-group">
              <label>Amount Paid</label>
              <input 
                v-model.number="paymentForm.amountPaid" 
                type="number" 
                placeholder="0"
                class="form-input"
              />
            </div>

            <!-- M-Pesa Payment Initiation -->
            <div v-if="isMpesaPayment && !paymentForm.mpesaCheckoutRequestId" class="form-group">
              <button 
                @click="initiateMpesaPayment"
                class="mpesa-init-btn"
                :disabled="!paymentForm.mpesaPhoneNumber || !paymentForm.amountPaid"
              >
                <i class="fas fa-mobile-alt"></i>
                Send M-Pesa Prompt
              </button>
            </div>

            <!-- M-Pesa Status Check -->
            <div v-if="isMpesaPayment && paymentForm.mpesaCheckoutRequestId && paymentForm.mpesaStatus !== 'success'" class="form-group">
              <button 
                @click="checkMpesaPaymentStatus"
                class="mpesa-check-btn"
                :disabled="paymentForm.mpesaStatus === 'checking'"
              >
                <i :class="paymentForm.mpesaStatus === 'checking' ? 'fas fa-spinner fa-spin' : 'fas fa-search'"></i>
                {{ paymentForm.mpesaStatus === 'checking' ? 'Checking Status...' : 'Check Payment Status' }}
              </button>
            </div>
          </div>

          <!-- Customer Info (Optional) -->
          <div v-if="selectedCustomerId || paymentForm.amountPaid < netTotal" class="customer-section">
            <h3>Customer Information</h3>
            <div class="form-group">
              <label>Customer</label>
              <select v-model="selectedCustomerId" class="form-input">
                <option value="">Select Customer</option>
                <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                  {{ customer.name }} (Balance: Ksh {{ customer.credit_balance || 0 }})
                </option>
              </select>
            </div>
            <div class="form-group">
              <label>Notes</label>
              <textarea 
                v-model="saleForm.notes" 
                placeholder="Order notes..."
                class="form-textarea"
              ></textarea>
            </div>
          </div>

          <!-- Error/Warning Messages -->
          <div v-if="creditBlockReason" class="alert-message error">
            <i class="fas fa-ban"></i>
            <span>{{ creditBlockReason }}</span>
          </div>
          <div v-if="isMpesaPayment && paymentForm.mpesaStatus !== 'success' && paymentForm.mpesaCheckoutRequestId" class="alert-message warning">
            <i class="fas fa-exclamation-triangle"></i>
            <span>Complete M-Pesa payment before processing</span>
          </div>
        </div>

        <div class="modal-footer">
          <button 
            @click="showProcessSaleModal = false" 
            class="secondary-btn"
          >
            Cancel
          </button>
          <button 
            @click="submitSale"
            :disabled="!canProcessSale || submitting"
            :class="{ 'processing': submitting }"
            class="checkout-btn"
          >
            <div v-if="submitting" class="btn-loading">
              <div class="btn-spinner"></div>
              Processing...
            </div>
            <div v-else class="btn-content">
              <i class="fas fa-credit-card"></i>
              Process Sale
            </div>
          </button>
        </div>
      </div>
    </div>

    <!-- Process Sale Button (Floating) -->
    <button 
      v-if="cart.length > 0"
      @click="showProcessSaleModal = true"
      class="process-sale-btn"
      :class="{ 'has-issues': isMpesaPayment && paymentForm.mpesaStatus !== 'success' }"
      title="Process Sale"
    >
      <i class="fas fa-shopping-bag"></i>
      <span class="btn-label">Process</span>
      <span v-if="isMpesaPayment" class="mpesa-indicator" :class="paymentForm.mpesaStatus">
        <i :class="getMpesaIndicatorIcon()"></i>
      </span>
    </button>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import axios from 'axios'
import { cachedGet } from '../../services/api'

// State
const products = ref([])
const cart = ref([])
const cartOpen = ref(false)
const showProcessSaleModal = ref(false)
const loading = ref(false)
const submitting = ref(false)
const searchQuery = ref('')
const categoryFilter = ref('')
const stockFilter = ref('all')
const promoRefreshTimeout = ref(null)
const cartCacheSaveTimeout = ref(null)

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
const selectedCartItemIds = ref([])
const editingCartItemId = ref(null)
const editCartForm = ref({
  quantity: 1
})

// Printer and Tax settings
const printerSettings = ref({
  header_message: '',
  footer_message: '',
  show_logo: true,
  receipt_logo_url: null,
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
  paymentMethod: 'Cash',
  mpesaPhoneNumber: '',
  mpesaCheckoutRequestId: '',
  mpesaMerchantRequestId: '',
  mpesaReceiptNumber: '',
  mpesaCustomerMessage: '',
  mpesaStatus: 'idle',
  mpesaStatusLabel: 'Not Started'
})

const promoDiscount = ref(0)
const appliedPromos = ref([])

// UoM Selection Modal
const showUoMSelector = ref(false)
const uoMSelectorProduct = ref(null)
const pendingCartItem = ref(null)

const showReceipt = ref(false)
const receiptData = ref({})
const receiptContent = ref(null)

// Cart cache (persists cart across menu/page navigation for a short period)
const CART_CACHE_TTL_MS = 1000 * 60 * 60 * 12 // 12 hours

function getCartCacheKey() {
  try {
    const raw = localStorage.getItem('userData')
    if (!raw) return 'pos-cart-cache-anonymous'
    const user = JSON.parse(raw)
    const companyId = user?.company_id || 'no-company'
    const userId = user?.id || 'no-user'
    return `pos-cart-cache-${companyId}-${userId}`
  } catch (_e) {
    return 'pos-cart-cache-anonymous'
  }
}

function clearCartCache() {
  localStorage.removeItem(getCartCacheKey())
}

function generateCartItemUid(productId, uomId) {
  return `${productId}-${uomId || 'base'}-${Date.now()}-${Math.random().toString(36).slice(2, 9)}`
}

function normalizeCartItems(items) {
  if (!Array.isArray(items)) return []
  return items.map((item) => ({
    ...item,
    quantity: Math.max(1, Number(item.quantity) || 1),
    price: Math.max(0, Number(item.price) || 0),
    cart_uid: item.cart_uid || generateCartItemUid(item.id, item.uom_id)
  }))
}

function saveCartCache() {
  try {
    const payload = {
      savedAt: Date.now(),
      expiresAt: Date.now() + CART_CACHE_TTL_MS,
      cart: normalizeCartItems(cart.value),
      selectedCustomerId: selectedCustomerId.value,
      saleForm: saleForm.value,
      paymentForm: paymentForm.value,
      cartOpen: cartOpen.value
    }
    localStorage.setItem(getCartCacheKey(), JSON.stringify(payload))
  } catch (_e) {
    // Ignore cache write failures (e.g., private mode/quota)
  }
}

function scheduleCartCacheSave() {
  if (cartCacheSaveTimeout.value) {
    clearTimeout(cartCacheSaveTimeout.value)
  }
  cartCacheSaveTimeout.value = setTimeout(() => {
    saveCartCache()
  }, 250)
}

function restoreCartCache() {
  try {
    const raw = localStorage.getItem(getCartCacheKey())
    if (!raw) return

    const payload = JSON.parse(raw)
    if (!payload || !Array.isArray(payload.cart)) {
      clearCartCache()
      return
    }

    if (payload.expiresAt && Date.now() > payload.expiresAt) {
      clearCartCache()
      return
    }

    cart.value = normalizeCartItems(payload.cart)
    selectedCustomerId.value = payload.selectedCustomerId || ''
    saleForm.value = {
      customer_name: payload.saleForm?.customer_name || '',
      notes: payload.saleForm?.notes || ''
    }
    paymentForm.value = {
      amountPaid: Number(payload.paymentForm?.amountPaid || 0),
      paymentMethod: payload.paymentForm?.paymentMethod || 'Cash',
      mpesaPhoneNumber: payload.paymentForm?.mpesaPhoneNumber || '',
      mpesaCheckoutRequestId: payload.paymentForm?.mpesaCheckoutRequestId || '',
      mpesaMerchantRequestId: payload.paymentForm?.mpesaMerchantRequestId || '',
      mpesaReceiptNumber: payload.paymentForm?.mpesaReceiptNumber || '',
      mpesaCustomerMessage: payload.paymentForm?.mpesaCustomerMessage || '',
      mpesaStatus: payload.paymentForm?.mpesaStatus || 'idle',
      mpesaStatusLabel: payload.paymentForm?.mpesaStatusLabel || 'Not Started'
    }
    cartOpen.value = Boolean(payload.cartOpen || payload.cart?.length)
  } catch (_e) {
    clearCartCache()
  }
}

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

const isMpesaPayment = computed(() => {
  const normalized = String(paymentForm.value.paymentMethod || '').toLowerCase().trim()
  return ['m-pesa', 'mpesa', 'm pesa'].includes(normalized)
})

const mpesaStatusClass = computed(() => {
  const status = paymentForm.value.mpesaStatus
  if (status === 'success') return 'success'
  if (status === 'failed') return 'failed'
  if (status === 'pending' || status === 'initiating' || status === 'checking') return 'pending'
  return 'idle'
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
  if (isMpesaPayment.value) {
    if (paymentForm.value.amountPaid <= 0) return false
    if (!paymentForm.value.mpesaPhoneNumber) return false
    if (paymentForm.value.mpesaStatus !== 'success') return false
  }
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

watch(() => paymentForm.value.paymentMethod, (newValue, oldValue) => {
  if (newValue !== oldValue) {
    resetMpesaState()
  }
})

watch(() => paymentForm.value.amountPaid, (newValue, oldValue) => {
  if (isMpesaPayment.value && Number(newValue) !== Number(oldValue) && paymentForm.value.mpesaCheckoutRequestId) {
    resetMpesaState({ keepPhone: true })
  }
})

watch(() => paymentForm.value.mpesaPhoneNumber, (newValue, oldValue) => {
  if (isMpesaPayment.value && newValue !== oldValue && paymentForm.value.mpesaCheckoutRequestId) {
    resetMpesaState({ keepPhone: true })
    paymentForm.value.mpesaPhoneNumber = newValue
  }
})

// Fetch products on mount
onMounted(() => {
  restoreCartCache()
  fetchProducts()
  fetchCustomers()
  fetchPaymentMethods()
  fetchPrinterSettings()
  fetchTaxConfigs()
})

onBeforeUnmount(() => {
  if (promoRefreshTimeout.value) {
    clearTimeout(promoRefreshTimeout.value)
  }
  if (cartCacheSaveTimeout.value) {
    clearTimeout(cartCacheSaveTimeout.value)
  }
})

watch(
  [cart, selectedCustomerId, saleForm, paymentForm, cartOpen],
  () => {
    // Keep empty cart state when user has intentionally cleared everything.
    scheduleCartCacheSave()
  },
  { deep: true }
)

watch(selectedCustomerId, async () => {
  if (!applyCustomerPricingToCart()) {
    selectedCustomerId.value = ''
    return
  }
  await refreshPromotions()
})

async function fetchProducts() {
  loading.value = true
  try {
    const data = await cachedGet('/products', {
      params: { with: 'saleUom,purchaseUom,uom' },
      ttlMs: 60 * 1000
    })
    products.value = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : [])
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
    const data = await cachedGet('/customers', {
      ttlMs: 60 * 1000
    })
    customers.value = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : [])
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
    
    // Keep the current selection if still available; otherwise pick first enabled.
    const hasCurrent = paymentMethods.value.some(pm => pm.name === paymentForm.value.paymentMethod)
    if (!hasCurrent && paymentMethods.value.length > 0) {
      paymentForm.value.paymentMethod = paymentMethods.value[0].name
    } else if (paymentMethods.value.length === 0) {
      paymentForm.value.paymentMethod = 'Cash'
    }
  } catch (err) {
    console.warn('❌ Failed to fetch payment methods, using cash-only fallback', err)
    // Never reintroduce subscription-gated methods on the client fallback.
    paymentMethods.value = [
      { id: 1, name: 'Cash', description: 'Cash payment' }
    ]
    paymentForm.value.paymentMethod = 'Cash'
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
  // Determine which UoM to use - prioritize saleUoms relationship
  const saleUoms = getProductSaleUoms(product)
  const saleUom = getProductSaleUom(product)
  const purchaseUom = getProductPurchaseUom(product)
  const baseUom = getProductBaseUom(product)
  const hasSaleUoms = saleUoms.length > 0
  const hasSaleUom = saleUom && saleUom.id
  const hasPurchaseUom = purchaseUom && purchaseUom.id
  const hasDefaultUom = baseUom && baseUom.id
  const defaultUomId = hasSaleUoms 
    ? getDefaultProductUomId(product)
    : (hasSaleUom 
        ? saleUom.id 
        : (hasPurchaseUom ? purchaseUom.id : (hasDefaultUom ? baseUom.id : null)))
  const availableQty = getRemainingAvailableQuantity(product, defaultUomId)

  if (!canSellProductToSelectedCustomer(product)) {
    return
  }

  if (availableQty === 0) {
    showAlert('This product is out of stock!', 'warning')
    return
  }

  if (hasSaleUoms && saleUoms.length > 1) {
    uoMSelectorProduct.value = product
    pendingCartItem.value = {
      ...product,
      quantity: 1,
      price: getUomSpecificPrice(product, saleUoms[0].id, product.price),
      uom_id: saleUoms[0].id,
      cart_uid: generateCartItemUid(product.id, saleUoms[0].id)
    }
    showUoMSelector.value = true
    return
  }

  const selectedUnitPrice = getEffectiveCartItemPrice(product, defaultUomId)

  // Check if product already in cart with same UoM
  const existing = cart.value.find(item => item.id === product.id && item.uom_id === defaultUomId)
  
  if (existing) {
    existing.price = selectedUnitPrice
    if (getRemainingAvailableQuantity(product, defaultUomId, existing.cart_uid) < 1) {
      showAlert('Cannot add more items. Stock limit reached!', 'warning')
      return
    }
    existing.quantity++
    showAlert(`Added another ${product.name} to cart`, 'success')
  } else {
    const cartItem = { 
      ...product, 
      quantity: 1,
      price: selectedUnitPrice,
      uom_id: defaultUomId,
      cart_uid: generateCartItemUid(product.id, defaultUomId)
    }
    cart.value.push(cartItem)
    showAlert(`${product.name} added to cart`, 'success')
  }
  
  await refreshPromotions()
  
  // Auto-open cart on first item
  if (cart.value.length === 1) {
    cartOpen.value = true
  }
}

function selectUoM(uom) {
  if (pendingCartItem.value) {
    const product = uoMSelectorProduct.value || getProduct(pendingCartItem.value.id)
    if (!canSellProductToSelectedCustomer(product)) {
      pendingCartItem.value = null
      showUoMSelector.value = false
      return
    }
    const existing = cart.value.find(item => Number(item.id) === Number(pendingCartItem.value.id) && Number(item.uom_id) === Number(uom.id))
    const remainingQty = getRemainingAvailableQuantity(product, uom.id, existing?.cart_uid || null)

    if (remainingQty < 1) {
      showAlert('Cannot add more items. Stock limit reached!', 'warning')
      pendingCartItem.value = null
      showUoMSelector.value = false
      return
    }

    const selectedPrice = getEffectiveCartItemPrice(uoMSelectorProduct.value, uom.id, pendingCartItem.value.price)
    if (existing) {
      existing.uom_abbr = uom.abbreviation
      existing.price = selectedPrice
      existing.quantity += 1
    } else {
      pendingCartItem.value.uom_id = uom.id
      pendingCartItem.value.uom_abbr = uom.abbreviation
      pendingCartItem.value.price = selectedPrice
      pendingCartItem.value.cart_uid = generateCartItemUid(pendingCartItem.value.id, uom.id)
      cart.value.push(pendingCartItem.value)
    }
    pendingCartItem.value = null
  }
  showUoMSelector.value = false
}

function getProduct(productId) {
  const targetId = Number(productId)
  return products.value.find(p => Number(p.id) === targetId)
}

function getProductSaleUoms(product) {
  if (!product) return []
  if (Array.isArray(product.saleUoms)) return product.saleUoms
  if (Array.isArray(product.sale_uoms)) return product.sale_uoms
  return []
}

function getProductSaleUom(product) {
  if (!product) return null
  return product.saleUom || product.sale_uom || null
}

function getProductPurchaseUom(product) {
  if (!product) return null
  return product.purchaseUom || product.purchase_uom || null
}

function getProductBaseUom(product) {
  if (!product) return null
  return product.uom || product.base_uom || null
}

function getCartItemUomOptions(item) {
  const product = getProduct(item.id)
  if (!product) return []

  const options = []
  const saleUoms = getProductSaleUoms(product)

  if (saleUoms.length > 0) {
    options.push(...saleUoms)
  } else if (getProductSaleUom(product)?.id) {
    options.push(getProductSaleUom(product))
  }

  if (options.length === 0 && getProductPurchaseUom(product)?.id) {
    options.push(getProductPurchaseUom(product))
  }

  if (options.length === 0 && getProductBaseUom(product)?.id) {
    options.push(getProductBaseUom(product))
  }

  const seen = new Set()
  return options.filter((uom) => {
    const id = Number(uom?.id)
    if (!id || seen.has(id)) {
      return false
    }
    seen.add(id)
    return true
  })
}

function hasMultipleCartItemUoms(item) {
  const product = getProduct(item.id)
  return getProductSaleUoms(product).length > 1
}

function updateItemUoM(item) {
  const product = getProduct(item.id)
  item.price = getEffectiveCartItemPrice(product, item.uom_id, item.price)
  const availableQty = getRemainingAvailableQuantity(product, item.uom_id, item.cart_uid)
  if (item.quantity > availableQty) {
    item.quantity = Math.max(1, Math.floor(availableQty))
    showAlert(`Available stock in selected unit is ${formatPrice(availableQty)}. Quantity adjusted.`, 'warning')
  }

  // UoM has been changed, refresh promotions to recalculate
  refreshPromotions()
}

function getUomSpecificPrice(product, uomId, fallbackPrice = null) {
  if (!product) return Number(fallbackPrice || 0)

  const fallback = Number(fallbackPrice ?? product.price ?? 0)
  const targetUomId = Number(uomId || 0)
  if (!targetUomId) return fallback

  const fromObjectMap = product.uom_prices
  if (fromObjectMap && typeof fromObjectMap === 'object' && !Array.isArray(fromObjectMap)) {
    const direct = fromObjectMap[targetUomId]
    const stringKey = fromObjectMap[String(targetUomId)]
    const resolved = direct ?? stringKey
    if (resolved !== undefined && resolved !== null && !Number.isNaN(Number(resolved))) {
      return Number(resolved)
    }
  }

  const fromArrayMap = Array.isArray(product.uom_prices) ? product.uom_prices : []
  const foundInUomPrices = fromArrayMap.find((entry) => Number(entry?.uom_id) === targetUomId)
  if (foundInUomPrices && foundInUomPrices.price !== undefined && foundInUomPrices.price !== null) {
    return Number(foundInUomPrices.price)
  }

  const prices = Array.isArray(product.prices) ? product.prices : []
  const foundInPricesRelation = prices.find((entry) => Number(entry?.uom_id) === targetUomId)
  if (foundInPricesRelation && foundInPricesRelation.price !== undefined && foundInPricesRelation.price !== null) {
    return Number(foundInPricesRelation.price)
  }

  return fallback
}

function getSelectedCustomerPriceGroup(customer = selectedCustomerData.value) {
  if (!customer) return null
  return customer.priceGroup || customer.price_group || null
}

function getSelectedCustomerPriceGroupId(customer = selectedCustomerData.value) {
  const priceGroup = getSelectedCustomerPriceGroup(customer)
  return Number(customer?.price_group_id || priceGroup?.id || 0)
}

function normalizePricingGroupIdentifier(value) {
  return String(value || '')
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '_')
    .replace(/^_+|_+$/g, '')
}

function isRetailPricingCustomer(customer) {
  if (!customer) return true

  const priceGroup = getSelectedCustomerPriceGroup(customer)
  const priceGroupId = getSelectedCustomerPriceGroupId(customer)

  if (!priceGroupId) {
    return true
  }

  const normalizedCode = normalizePricingGroupIdentifier(priceGroup?.code)
  const normalizedName = normalizePricingGroupIdentifier(priceGroup?.name)

  return normalizedCode === 'retail_default'
    || normalizedName === 'retail_default'
    || normalizedName === 'retail'
}

function isCustomerPriceGroupEnabled(customer = selectedCustomerData.value) {
  const priceGroup = getSelectedCustomerPriceGroup(customer)
  if (!priceGroup) return true
  if (isRetailPricingCustomer(customer)) return true
  return Boolean(priceGroup.is_enabled)
}

function getProductGroupBasePrice(product, customer) {
  if (!product || !customer || isRetailPricingCustomer(customer)) {
    return Number(product?.price || 0)
  }

  const priceGroup = getSelectedCustomerPriceGroup(customer)
  const priceGroupId = getSelectedCustomerPriceGroupId(customer)
  const retailBasePrice = Number(product.price || 0)

  if (!priceGroupId) {
    return retailBasePrice
  }

  const prices = Array.isArray(product.prices) ? product.prices : []
  const matchedGroupPrice = prices.find((entry) => Number(entry?.price_group_id) === priceGroupId && (entry?.uom_id === null || entry?.uom_id === undefined))
  if (matchedGroupPrice && matchedGroupPrice.price !== undefined && matchedGroupPrice.price !== null) {
    return Number(matchedGroupPrice.price)
  }

  const discountPercentage = Number(priceGroup?.discount_percentage || 0)
  if (discountPercentage > 0) {
    return retailBasePrice * (1 - discountPercentage / 100)
  }

  return retailBasePrice
}

function hasExplicitPriceForCustomerGroup(product, customer = selectedCustomerData.value) {
  if (!product || !customer || isRetailPricingCustomer(customer)) {
    return true
  }

  if (!isCustomerPriceGroupEnabled(customer)) {
    return false
  }

  const priceGroupId = getSelectedCustomerPriceGroupId(customer)
  if (!priceGroupId) {
    return true
  }

  const prices = Array.isArray(product.prices) ? product.prices : []
  return prices.some((entry) => Number(entry?.price_group_id) === priceGroupId && (entry?.uom_id === null || entry?.uom_id === undefined))
}

function getCustomerPricingBlockMessage(product, customer = selectedCustomerData.value) {
  const priceGroup = getSelectedCustomerPriceGroup(customer)
  const groupName = priceGroup?.name || 'selected pricing group'
  const productName = product?.name || 'This product'

  if (priceGroup && !isCustomerPriceGroupEnabled(customer)) {
    return `${groupName} pricing is turned off. Enable ${groupName} or reassign the customer before selling ${productName}.`
  }

  return `${productName} has no price set for ${groupName}. Set a ${groupName} price before selling to this customer.`
}

function getProductPricingWarning(product, customer = selectedCustomerData.value) {
  const priceGroup = getSelectedCustomerPriceGroup(customer)
  if (!product || !customer || !priceGroup || isRetailPricingCustomer(customer)) {
    return ''
  }

  if (!isCustomerPriceGroupEnabled(customer)) {
    return `${priceGroup.name} disabled`
  }

  if (!hasExplicitPriceForCustomerGroup(product, customer)) {
    return `${priceGroup.name} price missing`
  }

  return ''
}

function canSellProductToSelectedCustomer(product, customer = selectedCustomerData.value, { showError = true } = {}) {
  const allowed = hasExplicitPriceForCustomerGroup(product, customer)
  if (!allowed && showError) {
    showAlert(getCustomerPricingBlockMessage(product, customer), 'error')
  }
  return allowed
}

function getEffectiveCartItemPrice(product, uomId, fallbackPrice = null) {
  const retailUomPrice = getUomSpecificPrice(product, uomId, fallbackPrice)
  const customer = selectedCustomerData.value

  if (!product || !customer || isRetailPricingCustomer(customer)) {
    return retailUomPrice
  }

  const retailBasePrice = Number(product.price || 0)
  const groupedBasePrice = getProductGroupBasePrice(product, customer)

  if (!retailBasePrice || retailBasePrice <= 0) {
    return groupedBasePrice || retailUomPrice
  }

  const ratio = groupedBasePrice / retailBasePrice
  return Number((retailUomPrice * ratio).toFixed(2))
}

function applyCustomerPricingToCart() {
  const invalidItem = cart.value.find((item) => {
    const product = getProduct(item.id)
    return !canSellProductToSelectedCustomer(product, selectedCustomerData.value, { showError: false })
  })

  if (invalidItem) {
    const invalidProduct = getProduct(invalidItem.id)
    showAlert(getCustomerPricingBlockMessage(invalidProduct, selectedCustomerData.value), 'error')
    return false
  }

  cart.value.forEach((item) => {
    const product = getProduct(item.id)
    item.price = getEffectiveCartItemPrice(product, item.uom_id, item.price)
  })

  return true
}

function validateCustomerPricingForCart() {
  const invalidItem = cart.value.find((item) => {
    const product = getProduct(item.id)
    return !canSellProductToSelectedCustomer(product, selectedCustomerData.value, { showError: false })
  })

  if (!invalidItem) {
    return true
  }

  const invalidProduct = getProduct(invalidItem.id)
  showAlert(getCustomerPricingBlockMessage(invalidProduct, selectedCustomerData.value), 'error')
  return false
}

function getUomOptionLabel(product, uom) {
  if (!uom) return 'UOM'
  const price = getEffectiveCartItemPrice(product, uom.id, getUomSpecificPrice(product, uom.id, product?.price))
  const name = uom.name || 'UOM'
  const abbr = uom.abbreviation || name
  return `${name} (${abbr}) - Ksh ${formatPrice(price)}`
}

function getProductDefaultDisplayPrice(product) {
  const defaultUomId = getDefaultProductUomId(product)
  return getEffectiveCartItemPrice(product, defaultUomId, getUomSpecificPrice(product, defaultUomId, product?.price))
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

async function removeFromCart(cartUid) {
  const item = cart.value.find(entry => entry.cart_uid === cartUid)
  if (item) {
    cart.value = cart.value.filter(entry => entry.cart_uid !== cartUid)
    selectedCartItemIds.value = selectedCartItemIds.value.filter(id => id !== cartUid)
    if (editingCartItemId.value === cartUid) {
      editingCartItemId.value = null
    }
    showAlert(`${item.name} removed from cart`, 'info')
    await refreshPromotions()
  }
}

function toggleCartItemSelection(cartUid) {
  if (selectedCartItemIds.value.includes(cartUid)) {
    selectedCartItemIds.value = selectedCartItemIds.value.filter(id => id !== cartUid)
    if (editingCartItemId.value === cartUid) {
      editingCartItemId.value = null
    }
    return
  }
  selectedCartItemIds.value.push(cartUid)
}

async function deleteSelectedCartItems() {
  if (selectedCartItemIds.value.length === 0) {
    showAlert('Select cart items first.', 'warning')
    return
  }

  const selectedSet = new Set(selectedCartItemIds.value)
  const removedCount = cart.value.filter(item => selectedSet.has(item.cart_uid)).length
  cart.value = cart.value.filter(item => !selectedSet.has(item.cart_uid))
  selectedCartItemIds.value = []
  editingCartItemId.value = null
  showAlert(`${removedCount} item(s) removed from cart`, 'info')
  await refreshPromotions()
}

async function clearCart() {
  if (cart.value.length === 0) return
  const clearedCount = cart.value.length
  cart.value = []
  selectedCartItemIds.value = []
  editingCartItemId.value = null
  showAlert(`Cleared ${clearedCount} item(s) from cart`, 'info')
  await refreshPromotions()
}

function startEditCartItem(item) {
  if (!selectedCartItemIds.value.includes(item.cart_uid)) {
    selectedCartItemIds.value.push(item.cart_uid)
  }

  editingCartItemId.value = item.cart_uid
  editCartForm.value = {
    quantity: Number(item.quantity) || 1
  }
}

function cancelCartItemEdit() {
  editingCartItemId.value = null
}

async function applyCartItemEdit(item) {
  const nextQty = Math.max(1, parseInt(editCartForm.value.quantity, 10) || 1)

  const product = products.value.find(p => p.id === item.id)
  const availableQty = getRemainingAvailableQuantity(product, item.uom_id, item.cart_uid)
  if (nextQty > availableQty) {
    showAlert(`Only ${availableQty} item(s) available in stock.`, 'warning')
    return
  }

  item.quantity = nextQty
  editingCartItemId.value = null
  showAlert(`${item.name} updated`, 'success')
  await refreshPromotions()
}

async function increaseQuantity(item) {
  const product = products.value.find(p => p.id === item.id)
  const availableQty = getRemainingAvailableQuantity(product, item.uom_id, item.cart_uid)
  if (item.quantity >= availableQty) {
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
    await removeFromCart(item.cart_uid)
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

// UoM Helper Functions
function getDefaultProductUomId(product) {
  if (!product) return null

  const saleUoms = getProductSaleUoms(product)
  const defaultSaleUom = saleUoms.find(uom => Boolean(uom.pivot?.is_default))
  return Number(
    defaultSaleUom?.id ||
    product.sale_uom_id ||
    saleUoms?.[0]?.id ||
    product.uom_id ||
    product.purchase_uom_id ||
    getProductBaseUom(product)?.id ||
    getProductPurchaseUom(product)?.id ||
    getProductSaleUom(product)?.id ||
    0
  ) || null
}

function getAvailableQuantity(product, uomId = null) {
  if (!product) return 0

  const targetUomId = Number(uomId || getDefaultProductUomId(product) || product.uom_id || 0)
  const stockByUom = product.available_stock_by_uom || {}
  const available = targetUomId ? stockByUom[targetUomId] ?? stockByUom[String(targetUomId)] : null

  if (available !== null && available !== undefined) {
    return Number(available)
  }

  return Number(product.base_stock_quantity ?? product.stock_quantity ?? 0)
}

function getBaseStockQuantity(product) {
  if (!product) return 0
  return Number(product.base_stock_quantity ?? product.stock_quantity ?? 0)
}

function getBaseUnitsPerUom(product, uomId = null) {
  if (!product) return 0

  const baseStockQuantity = getBaseStockQuantity(product)
  const targetUomId = Number(uomId || getDefaultProductUomId(product) || 0)
  const baseUomId = Number(product.base_uom_id || product.uom_id || getProductBaseUom(product)?.id || 0)

  if (!targetUomId || targetUomId === baseUomId) {
    return 1
  }

  const availableQty = getAvailableQuantity(product, targetUomId)
  if (!availableQty || !baseStockQuantity) {
    return 0
  }

  return baseStockQuantity / availableQty
}

function getCartReservedBaseQuantity(product, excludeCartUid = null) {
  const productId = Number(product?.id)
  if (!productId) return 0

  return cart.value.reduce((sum, entry) => {
    if (Number(entry.id) !== productId) {
      return sum
    }

    if (excludeCartUid && entry.cart_uid === excludeCartUid) {
      return sum
    }

    const multiplier = getBaseUnitsPerUom(product, entry.uom_id)
    return sum + ((Number(entry.quantity) || 0) * multiplier)
  }, 0)
}

function getRemainingAvailableQuantity(product, uomId = null, excludeCartUid = null) {
  if (!product) return 0

  const baseStockQuantity = getBaseStockQuantity(product)
  const reservedBaseQuantity = getCartReservedBaseQuantity(product, excludeCartUid)
  const remainingBaseQuantity = Math.max(0, baseStockQuantity - reservedBaseQuantity)
  const multiplier = getBaseUnitsPerUom(product, uomId)

  if (!multiplier) {
    return 0
  }

  return Number((remainingBaseQuantity / multiplier).toFixed(4))
}

function getProductDisplayUom(product) {
  const targetUomId = getDefaultProductUomId(product)
  const saleUoms = getProductSaleUoms(product)

  if (saleUoms.length) {
    const saleUom = saleUoms.find(uom => Number(uom.id) === Number(targetUomId)) || saleUoms[0]
    if (saleUom) return saleUom.abbreviation || saleUom.name || ''
  }

  if (getProductSaleUom(product) && Number(getProductSaleUom(product).id) === Number(targetUomId)) {
    return getProductSaleUom(product).abbreviation || getProductSaleUom(product).name || ''
  }
  if (getProductBaseUom(product) && Number(getProductBaseUom(product).id) === Number(targetUomId)) {
    return getProductBaseUom(product).abbreviation || getProductBaseUom(product).name || ''
  }
  if (getProductPurchaseUom(product) && Number(getProductPurchaseUom(product).id) === Number(targetUomId)) {
    return getProductPurchaseUom(product).abbreviation || getProductPurchaseUom(product).name || ''
  }

  return ''
}

function getCartItemUomLabel(item) {
  const product = getProduct(item.id)
  if (!product) return item.uom_abbr || 'Base'

  const allUoms = [
    ...getProductSaleUoms(product),
    ...(getProductSaleUom(product) ? [getProductSaleUom(product)] : []),
    ...(getProductPurchaseUom(product) ? [getProductPurchaseUom(product)] : []),
    ...(getProductBaseUom(product) ? [getProductBaseUom(product)] : [])
  ]

  const matched = allUoms.find(uom => Number(uom.id) === Number(item.uom_id))
  return matched?.abbreviation || matched?.name || item.uom_abbr || 'Base'
}

function getUomLabel(product, type = 'sale') {
  if (type === 'sale' && getProductSaleUom(product)) {
    return getProductSaleUom(product).abbreviation
  }
  if (type === 'purchase' && getProductPurchaseUom(product)) {
    return getProductPurchaseUom(product).abbreviation
  }
  if (getProductBaseUom(product)) {
    return getProductBaseUom(product).abbreviation
  }
  return 'unit'
}

function getSubmitButtonText() {
  if (cart.value.length === 0) return 'Add items to cart'
  if (paymentForm.value.amountPaid === 0) return 'Enter payment amount'
  if (isMpesaPayment.value && !paymentForm.value.mpesaPhoneNumber) return 'Enter M-Pesa phone number'
  if (isMpesaPayment.value && paymentForm.value.mpesaStatus !== 'success') return 'Complete M-Pesa payment first'
  if (paymentForm.value.amountPaid < netTotal.value && !selectedCustomerId.value) return 'Select customer or pay full'
  if (paymentForm.value.amountPaid < netTotal.value) return 'Process with credit'
  return 'Process Sale'
}

function resetMpesaState(options = {}) {
  const keepPhone = Boolean(options.keepPhone)
  const currentPhone = paymentForm.value.mpesaPhoneNumber
  paymentForm.value.mpesaCheckoutRequestId = ''
  paymentForm.value.mpesaMerchantRequestId = ''
  paymentForm.value.mpesaReceiptNumber = ''
  paymentForm.value.mpesaCustomerMessage = ''
  paymentForm.value.mpesaStatus = 'idle'
  paymentForm.value.mpesaStatusLabel = 'Not Started'
  paymentForm.value.mpesaPhoneNumber = keepPhone ? currentPhone : ''
}

function syncMpesaTransactionState(transaction, fallbackMessage = '') {
  paymentForm.value.mpesaCheckoutRequestId = transaction?.checkout_request_id || paymentForm.value.mpesaCheckoutRequestId
  paymentForm.value.mpesaMerchantRequestId = transaction?.merchant_request_id || paymentForm.value.mpesaMerchantRequestId
  paymentForm.value.mpesaReceiptNumber = transaction?.mpesa_receipt_number || ''
  paymentForm.value.mpesaCustomerMessage = transaction?.result_desc || fallbackMessage || paymentForm.value.mpesaCustomerMessage

  if (transaction?.status === 'success') {
    paymentForm.value.mpesaStatus = 'success'
    paymentForm.value.mpesaStatusLabel = 'Paid'
  } else if (transaction?.status === 'failed') {
    paymentForm.value.mpesaStatus = 'failed'
    paymentForm.value.mpesaStatusLabel = 'Failed'
  } else if (transaction?.status === 'pending') {
    paymentForm.value.mpesaStatus = 'pending'
    paymentForm.value.mpesaStatusLabel = 'Awaiting Approval'
  }
}

async function initiateMpesaPayment() {
  if (!paymentForm.value.mpesaPhoneNumber) {
    showAlert('Enter the M-Pesa phone number first.', 'warning')
    return
  }

  if (Number(paymentForm.value.amountPaid) <= 0) {
    showAlert('Enter the amount to charge via M-Pesa.', 'warning')
    return
  }

  try {
    paymentForm.value.mpesaStatus = 'initiating'
    paymentForm.value.mpesaStatusLabel = 'Initiating...'

    const res = await axios.post('/api/mpesa/stk-push', {
      phone_number: paymentForm.value.mpesaPhoneNumber,
      amount: Number(paymentForm.value.amountPaid),
      account_reference: `SALE-${Date.now()}`,
      transaction_desc: 'POS Payment'
    })

    syncMpesaTransactionState(res.data?.transaction, res.data?.message)
    paymentForm.value.mpesaStatus = 'pending'
    paymentForm.value.mpesaStatusLabel = 'Awaiting Approval'
    showAlert(res.data?.message || 'STK Push sent. Ask the customer to approve the prompt.', 'info')
  } catch (err) {
    resetMpesaState({ keepPhone: true })
    showAlert(err.response?.data?.error || err.response?.data?.message || 'Failed to initiate M-Pesa payment.', 'error')
  }
}

async function checkMpesaPaymentStatus() {
  if (!paymentForm.value.mpesaCheckoutRequestId) {
    showAlert('Initiate the M-Pesa payment first.', 'warning')
    return
  }

  try {
    paymentForm.value.mpesaStatus = 'checking'
    paymentForm.value.mpesaStatusLabel = 'Checking...'

    const res = await axios.post('/api/mpesa/stk-query', {
      checkout_request_id: paymentForm.value.mpesaCheckoutRequestId,
    })

    syncMpesaTransactionState(res.data?.transaction, res.data?.provider_response?.ResultDesc)

    if (res.data?.transaction?.status === 'success') {
      showAlert('M-Pesa payment confirmed successfully.', 'success')
    } else if (res.data?.transaction?.status === 'failed') {
      showAlert(res.data?.transaction?.result_desc || 'M-Pesa payment failed.', 'error')
    } else {
      showAlert('Payment is still pending. Ask the customer to complete the prompt, then check again.', 'info')
    }
  } catch (err) {
    paymentForm.value.mpesaStatus = 'pending'
    paymentForm.value.mpesaStatusLabel = 'Awaiting Approval'
    showAlert(err.response?.data?.error || err.response?.data?.message || 'Failed to check M-Pesa status.', 'error')
  }
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
  
  // Get invoice data if available
  const invoice = saleResponse.invoice
  
  receiptData.value = {
    receiptNumber,
    invoiceNumber: invoice?.invoice_number || 'N/A',
    date: now.toLocaleDateString('en-GB'),
    time: now.toLocaleTimeString('en-GB'),
    customer: saleForm.value.customer_name || 'Walk-in Customer',
    items: cart.value.map(item => {
      const price = Number(item.price) || 0
      const quantity = Number(item.quantity) || 0
      return {
        id: item.id,
        name: item.name,
        uom: getCartItemUomLabel(item),
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
    paymentMethod: sale.payment_method || paymentForm.value.paymentMethod || 'Cash',
    mpesaPhoneNumber: sale.mpesa_phone_number || paymentForm.value.mpesaPhoneNumber || null,
    mpesaReceiptNumber: sale.mpesa_receipt_number || paymentForm.value.mpesaReceiptNumber || null,
    mpesaCheckoutRequestId: sale.mpesa_checkout_request_id || paymentForm.value.mpesaCheckoutRequestId || null,
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
    } else if (isMpesaPayment.value && paymentForm.value.mpesaStatus !== 'success') {
      showAlert('Complete the M-Pesa payment and confirm its status before processing the sale.', 'warning')
    } else if (paymentForm.value.amountPaid < netTotal.value && !selectedCustomerId.value) {
      showAlert('Payment is below total. Select a customer to record the balance as credit or pay full amount.', 'warning')
    } else if (creditBlockReason.value) {
      showAlert(creditBlockReason.value, 'error')
    }
    return
  }

  if (!validateCustomerPricingForCart()) {
    return
  }

  submitting.value = true
  try {
    if (isMpesaPayment.value) {
      if (!paymentForm.value.mpesaPhoneNumber) {
        showAlert('Enter the M-Pesa phone number first.', 'warning')
        submitting.value = false
        return
      }

      if (paymentForm.value.mpesaStatus !== 'success') {
        showAlert('Complete the M-Pesa payment and confirm its status before processing the sale.', 'warning')
        submitting.value = false
        return
      }
    }

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
      customer_id: selectedCustomerId.value ? parseInt(selectedCustomerId.value) : null,
      payment_method: paymentForm.value.paymentMethod,
      tax_configuration_id: defaultTaxConfig.value?.id ? parseInt(defaultTaxConfig.value.id) : null,
      discount: parseFloat(promoDiscount.value) || 0,
      tax: parseFloat(defaultTaxConfig.value?.rate) || 0,
      amount_paid: parseFloat(paymentForm.value.amountPaid) || 0,
      apply_credit: Boolean(applyCredit),
      mpesa_phone_number: isMpesaPayment.value ? paymentForm.value.mpesaPhoneNumber : null,
      mpesa_checkout_request_id: isMpesaPayment.value ? paymentForm.value.mpesaCheckoutRequestId : null,
      mpesa_receipt_number: isMpesaPayment.value ? paymentForm.value.mpesaReceiptNumber : null,
      items: cart.value.map(item => ({
        product_id: item.id,
        quantity: parseInt(item.quantity) || 1,
        price: parseFloat(item.price) || 0,
        uom_id: item.uom_id ? parseInt(item.uom_id) : null
      }))
    }

    const res = await axios.post('/sales', payload)
    // Sync cart-side promo view with server-calculated promos/discounts (so cart matches receipt)
    if (res.data) {
      const serverDiscount = Number(res.data.discount ?? res.data.sale?.discount ?? promoDiscount.value ?? 0)
      const serverPromos = res.data.applied_promotions || res.data.sale?.applied_promotions
      promoDiscount.value = serverDiscount
      appliedPromos.value = Array.isArray(serverPromos) ? serverPromos : appliedPromos.value
    }
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
    selectedCartItemIds.value = []
    editingCartItemId.value = null
    selectedCustomerId.value = ''
    saleForm.value.customer_name = ''
    saleForm.value.notes = ''
    paymentForm.value.amountPaid = 0
    paymentForm.value.paymentMethod = 'Cash'
    resetMpesaState()
    clearCartCache()
    await fetchProducts()
  } catch (err) {
    const apiMsg = err.response?.data?.message || err.response?.data?.error
    const validationErrors = err.response?.data?.details || err.response?.data?.errors
    
    let friendly = apiMsg && typeof apiMsg === 'string'
      ? apiMsg
      : 'Sale could not be processed right now. Please try again.'
    
    // Add validation details if available
    if (validationErrors && typeof validationErrors === 'object') {
      const errorList = Object.entries(validationErrors)
        .map(([field, errors]) => `${field}: ${Array.isArray(errors) ? errors.join(', ') : errors}`)
        .join('\n')
      if (errorList) {
        friendly += `\n\nValidation errors:\n${errorList}`
      }
    }

    // Log full error for debugging
    console.error('❌ Sale submission failed:', err.response?.data || err.message)
    console.error('📦 Payload sent:', payload)
    console.error('🔍 Full error:', err)

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

.pricing-warning-badge {
  display: inline-flex;
  align-items: center;
  margin: 0.5rem 0;
  padding: 0.3rem 0.55rem;
  border-radius: 999px;
  background: rgba(245, 101, 101, 0.14);
  color: #c53030;
  font-size: 0.72rem;
  font-weight: 700;
  line-height: 1.2;
}
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

.cart-actions-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.75rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 0.75rem;
}

.selection-count {
  font-size: 0.85rem;
  font-weight: 600;
  color: #475569;
}

.cart-actions-buttons {
  display: flex;
  gap: 0.5rem;
}

.cart-action-btn {
  border: none;
  border-radius: 8px;
  padding: 0.45rem 0.7rem;
  font-size: 0.8rem;
  font-weight: 600;
  color: #1e3a8a;
  background: #dbeafe;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
}

.cart-action-btn.danger {
  color: #991b1b;
  background: #fee2e2;
}

.cart-action-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.cart-item {
  background: #f8fafc;
  border-radius: 12px;
  padding: 1rem;
  display: grid;
  grid-template-columns: auto 1fr auto auto auto;
  gap: 1rem;
  align-items: center;
  border: 1px solid #e2e8f0;
}

.cart-item.selected {
  border-color: #60a5fa;
  background: #eff6ff;
}

.item-select input {
  width: 16px;
  height: 16px;
  cursor: pointer;
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

.item-uom-label {
  margin: 0.25rem 0 0;
  font-size: 0.78rem;
  color: #475569;
  font-weight: 600;
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

.item-actions {
  display: flex;
  gap: 0.4rem;
}

.edit-btn,
.remove-btn {
  width: 28px;
  height: 28px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
}

.edit-btn {
  background: rgba(30, 64, 175, 0.12);
  color: #1e40af;
}

.edit-btn:hover {
  background: #1e40af;
  color: white;
}

.remove-btn {
  background: rgba(229, 62, 62, 0.1);
  color: #e53e3e;
}

.remove-btn:hover {
  background: #e53e3e;
  color: white;
}

.item-edit-row {
  grid-column: 2 / -1;
  display: flex;
  align-items: flex-end;
  gap: 0.75rem;
  border-top: 1px dashed #bfdbfe;
  padding-top: 0.75rem;
}

.edit-field {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.edit-field label {
  font-size: 0.75rem;
  font-weight: 600;
  color: #475569;
}

.edit-field input {
  width: 96px;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  padding: 0.45rem 0.55rem;
  font-size: 0.85rem;
}

.edit-actions {
  display: flex;
  gap: 0.4rem;
}

.save-edit-btn,
.cancel-edit-btn {
  border: none;
  border-radius: 6px;
  padding: 0.45rem 0.65rem;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
}

.save-edit-btn {
  background: #16a34a;
  color: white;
}

.cancel-edit-btn {
  background: #e2e8f0;
  color: #334155;
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

.mpesa-panel {
  margin-bottom: 1rem;
  padding: 1rem;
  border: 1px solid #c7d2fe;
  border-radius: 12px;
  background: #eef2ff;
}

.mpesa-actions {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
  margin-top: 0.5rem;
}

.mpesa-btn {
  border: none;
  border-radius: 8px;
  padding: 0.75rem 1rem;
  background: #059669;
  color: white;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.mpesa-btn.secondary {
  background: #475569;
}

.mpesa-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.mpesa-status-card {
  margin-top: 0.85rem;
  padding: 0.85rem 1rem;
  border-radius: 10px;
  background: white;
  border: 1px solid #cbd5e1;
}

.mpesa-status-card.success {
  border-color: #10b981;
}

.mpesa-status-card.pending {
  border-color: #f59e0b;
}

.mpesa-status-card.failed {
  border-color: #ef4444;
}

.mpesa-status-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
}

.mpesa-status-pill {
  padding: 0.25rem 0.6rem;
  border-radius: 999px;
  background: #e2e8f0;
  font-size: 0.75rem;
  font-weight: 700;
}

.mpesa-status-text {
  margin-top: 0.45rem;
  color: #334155;
  font-size: 0.9rem;
}

.mpesa-status-text.subtle {
  word-break: break-all;
  color: #64748b;
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

.receipt-company-logo {
  max-width: 90px;
  max-height: 70px;
  object-fit: contain;
  margin: 0 auto 0.5rem auto;
  display: block;
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

/* UoM Selection Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  backdrop-filter: blur(4px);
}

.modal-content {
  background: white;
  border-radius: 16px;
  padding: 2rem;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  max-width: 400px;
  width: 90%;
  animation: slideUp 0.3s ease;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.modal-content h3 {
  margin: 0 0 0.5rem 0;
  font-size: 1.3rem;
  color: #2d3748;
}

.modal-subtitle {
  margin: 0 0 1.5rem 0;
  color: #718096;
  font-size: 0.9rem;
}

.uom-options {
  display: grid;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
}

.uom-option {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  background: #f8fafc;
  cursor: pointer;
  transition: all 0.2s ease;
  gap: 0.5rem;
}

.uom-option:hover {
  border-color: #667eea;
  background: #edf2f7;
  transform: translateY(-2px);
}

.uom-option i {
  font-size: 1.5rem;
  color: #667eea;
}

.uom-option strong {
  color: #2d3748;
  font-size: 0.95rem;
}

.uom-option small {
  color: #718096;
  font-size: 0.8rem;
}

.modal-close-btn {
  width: 100%;
  padding: 0.75rem;
  border: none;
  border-radius: 8px;
  background: #e2e8f0;
  color: #2d3748;
  cursor: pointer;
  font-weight: 500;
  transition: background 0.2s ease;
}

.modal-close-btn:hover {
  background: #cbd5e0;
}

/* Item UoM Selector */
.item-uom-selector {
  margin-top: 0.5rem;
}

.uom-select {
  width: 80px;
  padding: 0.35rem 0.5rem;
  border: 1px solid #cbd5e0;
  border-radius: 4px;
  font-size: 0.8rem;
  background: white;
  color: #2d3748;
  cursor: pointer;
}

.uom-select:hover {
  border-color: #667eea;
}

.uom-select:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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

/* UoM and Conversion Styles */
.conversion-info {
  margin-top: 0.5rem;
  font-size: 0.75rem;
  color: #666;
  background: rgba(102, 126, 234, 0.1);
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
}

.item-uom {
  display: block;
  margin-top: 0.25rem;
  color: #667eea;
  font-weight: 500;
}

.form-hint {
  display: block;
  margin-top: 0.25rem;
  color: #666;
  font-size: 0.85rem;
}
</style>
