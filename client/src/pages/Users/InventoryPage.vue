<template>
  <div class="modern-inventory">
    <!-- Navigation Bar -->
    <nav class="inventory-nav">
      <div class="nav-brand">
        <i class="fas fa-boxes nav-icon"></i>
        <h1>Inventory Manager</h1>
      </div>
      <div class="nav-actions">
        <button @click="fetchInventory" class="nav-btn refresh" :disabled="isLoading">
          <i class="fas fa-sync-alt" :class="{ 'spinning': isLoading }"></i>
        </button>
      </div>
    </nav>

    <!-- Main Container -->
    <div class="inventory-main">
      <!-- Loading State -->
      <div v-if="isLoading" class="loading-state">
        <div class="spinner"></div>
        <p>Loading inventory data...</p>
      </div>

      <!-- Content -->
      <div v-else class="inventory-content">
        <!-- Dashboard Cards -->
        <section class="dashboard-section">
          <div class="dashboard-grid">
            <div class="stat-card primary">
              <div class="stat-header">
                <i class="fas fa-cubes"></i>
                <span>Total Products</span>
              </div>
              <div class="stat-value">{{ totalProducts }}</div>
              <div class="stat-subtitle">items in stock</div>
            </div>

            <div class="stat-card success">
              <div class="stat-header">
                <i class="fas fa-dollar-sign"></i>
                <span>Total Value</span>
              </div>
              <div class="stat-value">{{ formatCurrency(totalInventoryValue) }}</div>
              <div class="stat-subtitle">inventory worth</div>
            </div>

            <div class="stat-card warning" :class="{ 'urgent': lowStock > 0 }">
              <div class="stat-header">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Low Stock</span>
              </div>
              <div class="stat-value">{{ lowStock }}</div>
              <div class="stat-subtitle">{{ lowStock > 0 ? 'needs attention' : 'all good' }}</div>
            </div>

            <div class="stat-card danger">
              <div class="stat-header">
                <i class="fas fa-times-circle"></i>
                <span>Out of Stock</span>
              </div>
              <div class="stat-value">{{ outOfStock }}</div>
              <div class="stat-subtitle">needs restocking</div>
            </div>
          </div>
        </section>

        <!-- Control Panel -->
        <section class="control-panel">
          <div class="panel-left">
            <div class="search-box">
              <i class="fas fa-search"></i>
              <input 
                v-model="searchQuery" 
                type="text" 
                placeholder="Search inventory..."
                @input="resetPagination"
              />
              <button v-if="searchQuery" @click="clearSearch" class="clear-btn">
                <i class="fas fa-times"></i>
              </button>
            </div>
          </div>
          
          <div class="panel-right">
            <button @click="openAddProductModal" class="control-btn primary">
              <i class="fas fa-plus"></i>
              Add Product
            </button>
            <button @click="openReplenishModal" class="control-btn secondary">
              <i class="fas fa-truck"></i>
              Restock
            </button>
            <button @click="openTransferModal" class="control-btn outline">
              <i class="fas fa-exchange-alt"></i>
              Transfer Stock
            </button>
            <button @click="viewTransferHistory" class="control-btn outline">
              <i class="fas fa-history"></i>
              Transfer History
            </button>
            <button @click="exportData" class="control-btn outline">
              <i class="fas fa-download"></i>
              Export
            </button>
          </div>
        </section>

        <!-- Results Info -->
        <section v-if="searchQuery" class="results-info">
          <div class="results-text">
            <i class="fas fa-filter"></i>
            <span>Showing {{ filteredProducts.length }} results for "<strong>{{ searchQuery }}</strong>"</span>
          </div>
          <button @click="clearSearch" class="clear-filter">
            <i class="fas fa-times"></i>
            Clear
          </button>
        </section>

        <!-- Product Table -->
        <section class="table-section">
          <div class="table-header">
            <div class="table-title">
              <i class="fas fa-table"></i>
              <span>Product Inventory</span>
            </div>
            <div class="table-options">
              <select v-model.number="pageSize" class="page-select">
                <option value="10">10 per page</option>
                <option value="25">25 per page</option>
                <option value="50">50 per page</option>
                <option value="100">100 per page</option>
              </select>
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="filteredProducts.length === 0" class="empty-state">
            <div class="empty-icon">
              <i class="fas fa-box-open"></i>
            </div>
            <h3>{{ searchQuery ? 'No products found' : 'No products in inventory' }}</h3>
            <p v-if="searchQuery">
              No results match your search. Try a different term.
            </p>
            <p v-else>
              Start building your inventory by adding products.
            </p>
            <button @click="searchQuery ? clearSearch() : openAddProductModal()" class="empty-btn">
              <i :class="searchQuery ? 'fas fa-search' : 'fas fa-plus'"></i>
              {{ searchQuery ? 'Clear Search' : 'Add First Product' }}
            </button>
          </div>

          <!-- Data Table -->
          <div v-else class="table-container">
            <table class="data-table">
              <thead>
                <tr>
                  <th @click="sortBy('name')" class="sortable">
                    <span>Product</span>
                    <i class="fas fa-sort" :class="getSortClass('name')"></i>
                  </th>
                  <th @click="sortBy('category')" class="sortable">
                    <span>Category</span>
                    <i class="fas fa-sort" :class="getSortClass('category')"></i>
                  </th>
                  <th @click="sortBy('stock')" class="sortable">
                    <span>Stock</span>
                    <i class="fas fa-sort" :class="getSortClass('stock')"></i>
                  </th>
                  <th @click="sortBy('unit')" class="sortable">
                    <span>Unit</span>
                    <i class="fas fa-sort" :class="getSortClass('unit')"></i>
                  </th>
                  <th @click="sortBy('price')" class="sortable">
                    <span>Price</span>
                    <i class="fas fa-sort" :class="getSortClass('price')"></i>
                  </th>
                  <th @click="sortBy('created_at')" class="sortable">
                    <span>Added</span>
                    <i class="fas fa-sort" :class="getSortClass('created_at')"></i>
                  </th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr 
                  v-for="product in paginatedProducts" 
                  :key="product.id" 
                  class="data-row"
                  :class="{
                    'low-stock': product.stock <= 10 && product.stock > 0,
                    'out-of-stock': product.stock === 0
                  }"
                >
                  <td>
                    <div class="product-info">
                      <div class="product-name">{{ product.name }}</div>
                      <div class="product-sku">{{ product.sku || 'N/A' }}</div>
                    </div>
                  </td>
                  <td>
                    <span class="category-tag" :class="getCategoryClass(product.category)">
                      {{ product.category }}
                    </span>
                  </td>
                  <td>
                    <div class="stock-info">
                      <span class="stock-number" :class="getStockStatusClass(product.stock)">
                        {{ product.stock }}
                      </span>
                      <span class="stock-badge" :class="getStockStatusClass(product.stock)">
                        {{ getStockStatus(product.stock) }}
                      </span>
                    </div>
                  </td>
                  <td>{{ product.unit || 'pcs' }}</td>
                  <td>
                    <span class="price">{{ formatCurrency(product.price) }}</span>
                  </td>
                  <td>
                    <span class="date">{{ formatDate(product.created_at) }}</span>
                  </td>
                  <td>
                    <div class="action-group">
                      <button @click="editProduct(product)" class="action-btn edit" title="Edit Product">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button @click="confirmDelete(product)" class="action-btn delete" title="Delete Product">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <!-- Pagination -->
        <section class="pagination-section" v-if="totalPages > 1">
          <div class="pagination-info">
            {{ paginationStart }}-{{ paginationEnd }} of {{ filteredProducts.length }} items
          </div>
          <div class="pagination-controls">
            <button 
              @click="goToPage(currentPage - 1)" 
              :disabled="currentPage === 1"
              class="page-btn"
            >
              <i class="fas fa-chevron-left"></i>
            </button>
            
            <div class="page-numbers">
              <button 
                v-for="page in visiblePages" 
                :key="page"
                @click="goToPage(page)"
                class="page-number"
                :class="{ 'active': page === currentPage, 'dots': page === '...' }"
              >
                {{ page }}
              </button>
            </div>
            
            <button 
              @click="goToPage(currentPage + 1)" 
              :disabled="currentPage === totalPages"
              class="page-btn"
            >
              <i class="fas fa-chevron-right"></i>
            </button>
          </div>
        </section>
      </div>
    </div>

    <!-- Modals -->
    <!-- Delete Modal -->
    <div v-if="showDeleteModal" class="modal-overlay">
      <div class="modal delete-modal">
        <div class="modal-header">
          <h3>
            <i class="fas fa-exclamation-triangle"></i>
            Confirm Delete
          </h3>
          <button @click="closeDeleteModal" class="close-btn">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="warning-box">
            <p><strong>{{ productToDelete?.name }}</strong> will be permanently deleted.</p>
            <p>This action cannot be undone.</p>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="closeDeleteModal" class="modal-btn secondary">Cancel</button>
          <button @click="deleteProduct" class="modal-btn danger" :disabled="isDeleting">
            {{ isDeleting ? 'Deleting...' : 'Delete Product' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Restock Modal -->
    <div v-if="showReplenishModal" class="modal-overlay">
      <div class="modal restock-modal">
        <div class="modal-header">
          <h3>
            <i class="fas fa-truck"></i>
            Restock Inventory
          </h3>
          <button @click="closeReplenishModal" class="close-btn">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div v-if="replenishStep === 1" class="restock-form">
            <h4>Restock Details</h4>
            
            <!-- Invoice & Supplier Information -->
            <div class="form-section">
              <h5><i class="fas fa-file-invoice"></i> Invoice Information</h5>
              <div class="form-grid">
                <div class="form-group">
                  <label>Invoice Number <span class="required">*</span></label>
                  <input v-model="restockDetails.invoice_number" type="text" placeholder="INV-2026-001" required />
                </div>
                <div class="form-group">
                  <label>Invoice Date <span class="required">*</span></label>
                  <input v-model="restockDetails.invoice_date" type="date" required />
                </div>
                <div class="form-group">
                  <label>Supplier <span class="required">*</span></label>
                  <select v-model="restockDetails.supplier_id" required>
                    <option value="">Select Supplier</option>
                    <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                      {{ supplier.name }}
                    </option>
                    <option value="others">Others (Custom)</option>
                  </select>
                </div>
                <div v-if="restockDetails.supplier_id === 'others'" class="form-group">
                  <label>Custom Supplier Name <span class="required">*</span></label>
                  <input v-model="restockDetails.custom_supplier" type="text" placeholder="Enter supplier name" required />
                </div>
                <div class="form-group">
                  <label>Warehouse</label>
                  <select v-model="restockDetails.warehouse_id">
                    <option value="">Select Warehouse</option>
                    <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
                      {{ warehouse.name }}
                    </option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Products to Restock -->
            <div class="form-section">
              <h5><i class="fas fa-boxes"></i> Add Products</h5>
              <div class="form-grid">
                <div class="form-group">
                  <label>Product</label>
                  <select v-model="replenishForm.product_id" required>
                    <option value="">Select Product</option>
                    <option v-for="product in products" :key="product.id" :value="product.id">
                      {{ product.name }} (Stock: {{ product.stock }})
                    </option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Quantity</label>
                  <input v-model.number="replenishForm.quantity" type="number" min="1" required />
                </div>
                <div class="form-group">
                  <label>Cost per Unit</label>
                  <input v-model.number="replenishForm.cost" type="number" step="0.01" min="0" required />
                </div>
                <div class="form-group full-width">
                  <label>Item Notes (Optional)</label>
                  <textarea v-model="replenishForm.notes" placeholder="Notes for this item..." rows="2"></textarea>
                </div>
              </div>
              <button @click="addReplenishItem" class="add-btn" :disabled="!canAddReplenishItem">
                <i class="fas fa-plus"></i>
                Add to List
              </button>
            </div>
          </div>

          <div v-if="replenishItems.length > 0" class="restock-list">
            <h4>Items to Restock ({{ replenishItems.length }})</h4>
            <div class="item-list">
              <div v-for="(item, index) in replenishItems" :key="index" class="restock-item">
                <div class="item-info">
                  <div class="item-name">{{ getProductName(item.product_id) }}</div>
                  <div class="item-details">{{ item.quantity }} × {{ formatCurrency(item.cost) }}</div>
                </div>
                <div class="item-total">{{ formatCurrency(item.quantity * item.cost) }}</div>
                <button @click="removeReplenishItem(index)" class="remove-btn">
                  <i class="fas fa-times"></i>
                </button>
              </div>
            </div>
            <div class="cost-breakdown">
              <div class="cost-row">
                <span>Subtotal:</span>
                <span>{{ formatCurrency(replenishTotal) }}</span>
              </div>
              <div class="cost-row">
                <label>Tax Amount:</label>
                <input v-model.number="restockDetails.tax_amount" type="number" step="0.01" min="0" class="inline-input" />
              </div>
              <div class="cost-row">
                <label>Shipping Cost:</label>
                <input v-model.number="restockDetails.shipping_cost" type="number" step="0.01" min="0" class="inline-input" />
              </div>
              <div class="cost-row">
                <label>Discount:</label>
                <input v-model.number="restockDetails.discount" type="number" step="0.01" min="0" class="inline-input" />
              </div>
              <div class="cost-row total">
                <strong>Grand Total:</strong>
                <strong>{{ formatCurrency(grandTotal) }}</strong>
              </div>
            </div>
          </div>

          <div v-if="replenishStep === 2" class="order-summary">
            <h4>Restock Summary</h4>
            
            <div class="summary-section">
              <h5>Invoice Details</h5>
              <div class="detail-row">
                <span>Invoice Number:</span>
                <strong>{{ restockDetails.invoice_number }}</strong>
              </div>
              <div class="detail-row">
                <span>Invoice Date:</span>
                <strong>{{ restockDetails.invoice_date }}</strong>
              </div>
              <div class="detail-row">
                <span>Supplier:</span>
                <strong>{{ getSupplierName() }}</strong>
              </div>
              <div class="detail-row" v-if="restockDetails.warehouse_id">
                <span>Warehouse:</span>
                <strong>{{ getWarehouseName(restockDetails.warehouse_id) }}</strong>
              </div>
            </div>

            <div class="summary-section">
              <h5>Items ({{ replenishItems.length }})</h5>
              <div class="summary-list">
                <div v-for="(item, index) in replenishItems" :key="index" class="summary-item">
                  <span>{{ getProductName(item.product_id) }}</span>
                  <span>{{ item.quantity }} units × {{ formatCurrency(item.cost) }}</span>
                  <span>{{ formatCurrency(item.quantity * item.cost) }}</span>
                </div>
              </div>
            </div>

            <div class="summary-section">
              <h5>Cost Breakdown</h5>
              <div class="detail-row">
                <span>Subtotal:</span>
                <span>{{ formatCurrency(replenishTotal) }}</span>
              </div>
              <div class="detail-row" v-if="restockDetails.tax_amount > 0">
                <span>Tax:</span>
                <span>{{ formatCurrency(restockDetails.tax_amount) }}</span>
              </div>
              <div class="detail-row" v-if="restockDetails.shipping_cost > 0">
                <span>Shipping:</span>
                <span>{{ formatCurrency(restockDetails.shipping_cost) }}</span>
              </div>
              <div class="detail-row" v-if="restockDetails.discount > 0">
                <span>Discount:</span>
                <span>-{{ formatCurrency(restockDetails.discount) }}</span>
              </div>
              <div class="detail-row total">
                <strong>Grand Total:</strong>
                <strong>{{ formatCurrency(grandTotal) }}</strong>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <div v-if="replenishStep === 1">
            <button @click="closeReplenishModal" class="modal-btn secondary">Cancel</button>
            <button @click="replenishStep = 2" class="modal-btn primary" :disabled="replenishItems.length === 0 || !restockDetails.invoice_number || !restockDetails.invoice_date || !restockDetails.supplier_id || (restockDetails.supplier_id === 'others' && !restockDetails.custom_supplier)">
              Review Order
            </button>
          </div>
          <div v-else>
            <button @click="replenishStep = 1" class="modal-btn secondary">Back</button>
            <button @click="saveReplenish" class="modal-btn primary">Confirm & Save</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Stock Transfer Modal -->
    <div v-if="showTransferModal" class="modal-overlay">
      <div class="modal transfer-modal">
        <div class="modal-header">
          <h3>
            <i class="fas fa-exchange-alt"></i>
            Transfer Stock
          </h3>
          <button @click="closeTransferModal" class="close-btn">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="executeTransfer" class="transfer-form">
            <!-- Transfer Items -->
            <div class="transfer-items">
              <div class="transfer-item" v-for="(item, index) in transferItems" :key="index">
                <div class="form-group">
                  <label>
                    <i class="fas fa-box"></i>
                    Product <span class="required">*</span>
                  </label>
                  <select v-model="item.product_id" @change="onTransferProductChange(index)" required class="form-select">
                    <option value="">Select Product</option>
                    <option v-for="product in products" :key="product.id" :value="product.id">
                      {{ product.name }} (Stock: {{ product.stock }}, {{ getWarehouseName(product.warehouse_id) }})
                    </option>
                  </select>
                </div>

                <div class="form-group">
                  <label>
                    <i class="fas fa-boxes"></i>
                    Quantity <span class="required">*</span>
                  </label>
                  <input
                    v-model.number="item.quantity"
                    type="number"
                    :max="getProductStock(item.product_id)"
                    min="1"
                    placeholder="Enter quantity"
                    required
                    class="form-input"
                  />
                  <small class="helper-text">
                    Available: <strong>{{ getProductStock(item.product_id) }}</strong> | From: {{ getProductWarehouseName(item.product_id) || 'N/A' }}
                  </small>
                </div>

                <div class="form-group inline-actions">
                  <button type="button" class="action-btn add" @click="addTransferItem" title="Add another product">
                    <i class="fas fa-plus"></i>
                  </button>
                  <button type="button" class="action-btn delete" @click="removeTransferItem(index)" :disabled="transferItems.length === 1" title="Remove this product">
                    <i class="fas fa-minus"></i>
                  </button>
                </div>
              </div>
            </div>

            <!-- Transfer Type -->
            <div class="form-group">
              <label>
                <i class="fas fa-arrows-alt-h"></i>
                Transfer Type <span class="required">*</span>
              </label>
              <select v-model="transferForm.destination_type" required class="form-select">
                <option value="">Select Transfer Type</option>
                <option value="warehouse">Transfer to Another Warehouse</option>
                <option value="supplier_return">Return to Supplier</option>
                <option value="write_off">Write Off (Damage/Expiry)</option>
                <option value="adjustment_out">Adjustment Out (Inventory Correction)</option>
              </select>
              <small class="helper-text">Choose where the stock will be transferred</small>
            </div>

            <!-- To Warehouse (conditional) -->
            <div v-if="transferForm.destination_type === 'warehouse'" class="form-group">
              <label>
                <i class="fas fa-warehouse"></i>
                To Warehouse <span class="required">*</span>
              </label>
              <select v-model="transferForm.to_warehouse_id" required class="form-select">
                <option value="">Select Destination Warehouse</option>
                <option 
                  v-for="warehouse in availableWarehouses" 
                  :key="warehouse.id" 
                  :value="warehouse.id"
                >
                  {{ warehouse.name }}
                  <span v-if="!warehouse.company_id"> (System Default)</span>
                </option>
              </select>
            </div>

            <!-- Supplier Name (conditional) -->
            <div v-if="transferForm.destination_type === 'supplier_return'" class="form-group">
              <label>
                <i class="fas fa-truck"></i>
                Supplier Name
              </label>
              <input 
                v-model="transferForm.external_target"
                type="text"
                placeholder="Enter supplier name"
                class="form-input"
              />
            </div>

            <!-- Reason -->
            <div class="form-group">
              <label>
                <i class="fas fa-info-circle"></i>
                Reason <span class="recommended">(Recommended for reports)</span>
              </label>
              <input 
                v-model="transferForm.reason"
                type="text"
                placeholder="e.g., Damaged items, Expired on 2026-01-15, Stock rebalancing"
                class="form-input"
              />
            </div>

            <!-- Reference Number -->
            <div class="form-group">
              <label>
                <i class="fas fa-hashtag"></i>
                Reference Number <span class="recommended">(Optional)</span>
              </label>
              <input 
                v-model="transferForm.reference"
                type="text"
                placeholder="e.g., RET-2026-001, WO-2026-045, Invoice #INV-123"
                class="form-input"
              />
              <small class="helper-text">For tracking and audit purposes</small>
            </div>

            <!-- Notes -->
            <div class="form-group">
              <label>
                <i class="fas fa-sticky-note"></i>
                Additional Notes
              </label>
              <textarea 
                v-model="transferForm.note"
                placeholder="Any additional information about this transfer..."
                rows="3"
                class="form-textarea"
              ></textarea>
            </div>

            <!-- Tracking Info -->
            <div class="tracking-info">
              <div class="tracking-row">
                <i class="fas fa-user"></i>
                <span>Transferred by: <strong>{{ currentUserName }}</strong></span>
              </div>
              <div class="tracking-row">
                <i class="fas fa-calendar"></i>
                <span>Date: <strong>{{ currentDate }}</strong></span>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button @click="closeTransferModal" class="modal-btn secondary">
            <i class="fas fa-times"></i>
            Cancel
          </button>
          <button @click="executeTransfer" class="modal-btn primary" :disabled="isTransferring || !isTransferValid">
            <i class="fas fa-exchange-alt"></i>
            {{ isTransferring ? 'Transferring...' : 'Transfer Stock' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Transfer History Modal -->
    <div v-if="showTransferHistory" class="modal-overlay">
      <div class="modal history-modal">
        <div class="modal-header">
          <h3>
            <i class="fas fa-history"></i>
            Stock Transfer History
          </h3>
          <button @click="closeTransferHistory" class="close-btn">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div v-if="loadingHistory" class="loading-state">
            <div class="spinner"></div>
            <p>Loading transfer history...</p>
          </div>
          <div v-else-if="transferHistory.length === 0" class="empty-state">
            <i class="fas fa-inbox"></i>
            <p>No transfer history found</p>
          </div>
          <div v-else class="history-table-container">
            <table class="history-table">
              <thead>
                <tr>
                  <th>Transfer #</th>
                  <th>Date</th>
                  <th>Product</th>
                  <th>Type</th>
                  <th>From</th>
                  <th>To</th>
                  <th>Qty</th>
                  <th>By</th>
                  <th>Reason</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="transfer in transferHistory" :key="transfer.id">
                  <td>
                    <span class="transfer-number">{{ transfer.transfer_number }}</span>
                  </td>
                  <td>{{ formatDateTime(transfer.created_at) }}</td>
                  <td>{{ transfer.product_name }}</td>
                  <td>
                    <span class="transfer-type-badge" :class="transfer.transfer_type">
                      {{ formatTransferType(transfer.transfer_type) }}
                    </span>
                  </td>
                  <td>{{ transfer.from_warehouse_name || 'N/A' }}</td>
                  <td>
                    <span v-if="transfer.transfer_type === 'warehouse'">
                      {{ transfer.to_warehouse_name || 'N/A' }}
                    </span>
                    <span v-else-if="transfer.transfer_type === 'supplier_return'" class="external-target">
                      {{ transfer.external_target || 'Supplier' }}
                    </span>
                    <span v-else class="removed-badge">Removed</span>
                  </td>
                  <td><strong>{{ transfer.quantity }}</strong></td>
                  <td>{{ transfer.user_name || 'N/A' }}</td>
                  <td>
                    <span class="reason-text" :title="transfer.reason">
                      {{ transfer.reason || '-' }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="closeTransferHistory" class="modal-btn secondary">Close</button>
          <button @click="exportTransferHistory" class="modal-btn outline">
            <i class="fas fa-download"></i>
            Export History
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  data() {
    return {
      products: [],
      searchQuery: '',
      sortKey: '',
      sortAsc: true,
      currentPage: 1,
      pageSize: 10,
      isLoading: true,
      showDeleteModal: false,
      showReplenishModal: false,
      productToDelete: null,
      isDeleting: false,
      replenishStep: 1,
      replenishForm: {
        product_id: '',
        quantity: 1,
        cost: 0,
        notes: ''
      },
      replenishItems: [],
      restockDetails: {
        invoice_number: '',
        invoice_date: new Date().toISOString().split('T')[0],
        supplier_id: '',
        custom_supplier: '',
        warehouse_id: '',
        tax_amount: 0,
        shipping_cost: 0,
        discount: 0,
        notes: ''
      },
      // Stock Transfer
      showTransferModal: false,
      isTransferring: false,
      warehouses: [],
      suppliers: [],
      transferItems: [
        { product_id: '', quantity: '' }
      ],
      transferForm: {
        destination_type: '',
        to_warehouse_id: '',
        reason: '',
        reference: '',
        external_target: '',
        note: ''
      },
      // Transfer History
      showTransferHistory: false,
      transferHistory: [],
      loadingHistory: false,
      currentUser: null
    }
  },
  computed: {
    filteredProducts() {
      if (!this.searchQuery) return this.products

      const query = this.searchQuery.toLowerCase()
      return this.products.filter(product =>
        product.name.toLowerCase().includes(query) ||
        product.category.toLowerCase().includes(query) ||
        (product.sku && product.sku.toLowerCase().includes(query))
      )
    },
    sortedProducts() {
      if (!this.sortKey) return this.filteredProducts

      return [...this.filteredProducts].sort((a, b) => {
        let aVal = a[this.sortKey]
        let bVal = b[this.sortKey]

        if (this.sortKey === 'price' || this.sortKey === 'stock') {
          aVal = parseFloat(aVal) || 0
          bVal = parseFloat(bVal) || 0
        }

        if (aVal < bVal) return this.sortAsc ? -1 : 1
        if (aVal > bVal) return this.sortAsc ? 1 : -1
        return 0
      })
    },
    paginatedProducts() {
      const start = (this.currentPage - 1) * this.pageSize
      const end = start + this.pageSize
      return this.sortedProducts.slice(start, end)
    },
    totalPages() {
      return Math.ceil(this.filteredProducts.length / this.pageSize)
    },
    paginationStart() {
      return (this.currentPage - 1) * this.pageSize + 1
    },
    paginationEnd() {
      return Math.min(this.currentPage * this.pageSize, this.filteredProducts.length)
    },
    visiblePages() {
      const total = this.totalPages
      const current = this.currentPage
      const pages = []

      if (total <= 7) {
        for (let i = 1; i <= total; i++) {
          pages.push(i)
        }
      } else {
        if (current <= 4) {
          for (let i = 1; i <= 5; i++) pages.push(i)
          pages.push('...')
          pages.push(total)
        } else if (current >= total - 3) {
          pages.push(1)
          pages.push('...')
          for (let i = total - 4; i <= total; i++) pages.push(i)
        } else {
          pages.push(1)
          pages.push('...')
          for (let i = current - 1; i <= current + 1; i++) pages.push(i)
          pages.push('...')
          pages.push(total)
        }
      }

      return pages
    },
    totalProducts() {
      return this.products.length
    },
    totalInventoryValue() {
      return this.products.reduce((sum, product) => {
        return sum + (parseFloat(product.price) * parseFloat(product.stock))
      }, 0)
    },
    lowStock() {
      return this.products.filter(product => product.stock <= 10 && product.stock > 0).length
    },
    outOfStock() {
      return this.products.filter(product => product.stock === 0).length
    },
    canAddReplenishItem() {
      return this.replenishForm.product_id &&
             this.replenishForm.quantity > 0 &&
             this.replenishForm.cost >= 0
    },
    replenishTotal() {
      return this.replenishItems.reduce((sum, item) => sum + (item.quantity * item.cost), 0)
    },
    grandTotal() {
      const subtotal = this.replenishTotal
      const tax = parseFloat(this.restockDetails.tax_amount) || 0
      const shipping = parseFloat(this.restockDetails.shipping_cost) || 0
      const discount = parseFloat(this.restockDetails.discount) || 0
      return subtotal + tax + shipping - discount
    },
    availableWarehouses() {
      return this.warehouses
    },
    isTransferValid() {
      if (!this.transferForm.destination_type) return false
      if (this.transferForm.destination_type === 'warehouse' && !this.transferForm.to_warehouse_id) return false
      if (!this.transferItems.length) return false
      return this.transferItems.every(item => {
        const qty = Number(item.quantity)
        const product = this.getProductById(item.product_id)
        if (!product) return false
        if (!qty || qty <= 0) return false
        if (qty > product.stock) return false
        return true
      })
    },
    currentUserName() {
      return this.currentUser?.name || this.currentUser?.email || 'Unknown User'
    },
    currentDate() {
      return new Date().toLocaleString()
    }
  },
  methods: {
    async fetchInventory() {
      this.isLoading = true
      try {
        const response = await axios.get('http://localhost:8000/products')
        this.products = response.data.data || response.data || []
      } catch (error) {
        console.error('Error fetching inventory:', error)
        this.products = []
      } finally {
        this.isLoading = false
      }
    },
    sortBy(key) {
      if (this.sortKey === key) {
        this.sortAsc = !this.sortAsc
      } else {
        this.sortKey = key
        this.sortAsc = true
      }
    },
    getSortClass(key) {
      if (this.sortKey !== key) return ''
      return this.sortAsc ? 'fa-sort-up' : 'fa-sort-down'
    },
    clearSearch() {
      this.searchQuery = ''
      this.resetPagination()
    },
    resetPagination() {
      this.currentPage = 1
    },
    goToPage(page) {
      if (page >= 1 && page <= this.totalPages && page !== '...') {
        this.currentPage = page
      }
    },
    formatCurrency(amount) {
      return new Intl.NumberFormat('en-KE', {
        style: 'currency',
        currency: 'KES',
        minimumFractionDigits: 0
      }).format(amount || 0)
    },
    formatDate(dateString) {
      return new Date(dateString).toLocaleDateString('en-KE', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    },
    getCategoryClass(category) {
      const categoryMap = {
        'fertilizers': 'fertilizers',
        'pesticides': 'pesticides',
        'seeds': 'seeds',
        'animal feed': 'animal-feed',
        'veterinary': 'veterinary',
        'tools': 'tools',
        'irrigation': 'irrigation',
        'dairy': 'dairy',
        'poultry': 'poultry',
        'livestock': 'livestock',
        'crop protection': 'crop',
        'soil management': 'soil',
        'greenhouse': 'greenhouse'
      }
      return categoryMap[category?.toLowerCase()] || 'default'
    },
    getStockStatusClass(stock) {
      if (stock === 0) return 'zero'
      if (stock <= 10) return 'low'
      return 'normal'
    },
    getStockStatus(stock) {
      if (stock === 0) return 'Out of Stock'
      if (stock <= 10) return 'Low Stock'
      return 'In Stock'
    },
    confirmDelete(product) {
      this.productToDelete = product
      this.showDeleteModal = true
    },
    closeDeleteModal() {
      this.showDeleteModal = false
      this.productToDelete = null
      this.isDeleting = false
    },
    async deleteProduct() {
      if (!this.productToDelete) return

      this.isDeleting = true
      try {
        await axios.delete(`/api/products/${this.productToDelete.id}`)
        this.products = this.products.filter(p => p.id !== this.productToDelete.id)
        this.closeDeleteModal()
      } catch (error) {
        console.error('Error deleting product:', error)
        alert('Failed to delete product. Please try again.')
      } finally {
        this.isDeleting = false
      }
    },
    openAddProductModal() {
      this.$emit('open-add-product')
    },
    editProduct(product) {
      this.$emit('edit-product', product)
    },
    openReplenishModal() {
      this.showReplenishModal = true
      this.replenishStep = 1
      this.resetReplenishForm()
    },
    closeReplenishModal() {
      this.showReplenishModal = false
      this.replenishStep = 1
      this.resetReplenishForm()
      this.replenishItems = []
      this.restockDetails = {
        invoice_number: '',
        invoice_date: new Date().toISOString().split('T')[0],
        supplier_id: '',
        custom_supplier: '',
        warehouse_id: '',
        tax_amount: 0,
        shipping_cost: 0,
        discount: 0,
        notes: ''
      }
    },
    resetReplenishForm() {
      this.replenishForm = {
        product_id: '',
        quantity: 1,
        cost: 0,
        notes: ''
      }
    },
    addReplenishItem() {
      if (!this.canAddReplenishItem) return

      this.replenishItems.push({
        product_id: this.replenishForm.product_id,
        quantity: this.replenishForm.quantity,
        cost: this.replenishForm.cost,
        notes: this.replenishForm.notes
      })

      this.resetReplenishForm()
    },
    removeReplenishItem(index) {
      this.replenishItems.splice(index, 1)
    },
    getProductName(productId) {
      const product = this.products.find(p => p.id === productId)
      return product ? product.name : 'Unknown Product'
    },
    async saveReplenish() {
      try {
        // Determine supplier info
        let supplierId = null
        let supplierName = ''
        
        if (this.restockDetails.supplier_id === 'others') {
          supplierName = this.restockDetails.custom_supplier
        } else {
          supplierId = parseInt(this.restockDetails.supplier_id)
          const supplier = this.suppliers.find(s => s.id === supplierId)
          supplierName = supplier ? supplier.name : ''
        }

        const payload = {
          supplier_id: supplierId,
          supplier_name: supplierName,
          invoice_number: this.restockDetails.invoice_number,
          invoice_date: this.restockDetails.invoice_date,
          warehouse_id: this.restockDetails.warehouse_id ? parseInt(this.restockDetails.warehouse_id) : null,
          tax_amount: parseFloat(this.restockDetails.tax_amount) || 0,
          shipping_cost: parseFloat(this.restockDetails.shipping_cost) || 0,
          discount: parseFloat(this.restockDetails.discount) || 0,
          notes: this.restockDetails.notes || '',
          items: this.replenishItems
        }
        
        await axios.post('http://localhost:8000/purchases', payload)
        alert('Restock completed successfully!')
        await this.fetchInventory()
        this.closeReplenishModal()
      } catch (error) {
        console.error('Error saving restock:', error)
        const errorMessage = error.response?.data?.message || error.response?.data?.error || 'Failed to save restock. Please try again.'
        alert(errorMessage)
      }
    },
    exportData() {
      console.log('Export data')
    },
    // Stock Transfer
    async fetchWarehouses() {
      try {
        const response = await axios.get('http://localhost:8000/warehouses')
        this.warehouses = response.data
      } catch (error) {
        console.error('Error fetching warehouses:', error)
      }
    },
    async fetchSuppliers() {
      try {
        const response = await axios.get('http://localhost:8000/suppliers')
        this.suppliers = response.data.data || response.data || []
      } catch (error) {
        console.error('Error fetching suppliers:', error)
      }
    },
    async fetchCurrentUser() {
      try {
        const response = await axios.get('http://localhost:8000/user')
        this.currentUser = response.data
      } catch (error) {
        console.error('Error fetching current user:', error)
      }
    },
    openTransferModal() {
      this.transferForm = {
        destination_type: '',
        to_warehouse_id: '',
        reason: '',
        reference: '',
        external_target: '',
        note: ''
      }
      this.transferItems = [{ product_id: '', quantity: '' }]
      this.showTransferModal = true
    },
    closeTransferModal() {
      this.showTransferModal = false
      this.transferForm = {
        destination_type: '',
        to_warehouse_id: '',
        reason: '',
        reference: '',
        external_target: '',
        note: ''
      }
      this.transferItems = [{ product_id: '', quantity: '' }]
    },
    async executeTransfer() {
      if (!this.isTransferValid) return

      this.isTransferring = true
      try {
        const payloads = this.transferItems.map(item => {
          const product = this.getProductById(item.product_id)
          
          // Check if product has warehouse_id
          if (!product.warehouse_id) {
            throw new Error(`Product "${product.name}" is not assigned to any warehouse`)
          }
          
          const payload = {
            product_id: product.id,
            from_warehouse_id: product.warehouse_id,
            quantity: parseInt(item.quantity),
            destination_type: this.transferForm.destination_type,
            reason: this.transferForm.reason || '',
            reference: this.transferForm.reference || '',
            note: this.transferForm.note || ''
          }
          
          // Add to_warehouse_id for warehouse transfers
          if (this.transferForm.destination_type === 'warehouse') {
            payload.to_warehouse_id = parseInt(this.transferForm.to_warehouse_id)
          }
          
          // Add external_target for supplier returns
          if (this.transferForm.destination_type === 'supplier_return') {
            payload.external_target = this.transferForm.external_target || ''
          }
          
          console.log('Transfer payload:', payload)
          return payload
        })

        await Promise.all(payloads.map(payload => axios.post('http://localhost:8000/products/transfer', payload)))

        alert('Stock transferred successfully!')
        await this.fetchInventory()
        this.closeTransferModal()
      } catch (error) {
        console.error('Error transferring stock:', error)
        console.error('Error response:', error.response?.data)
        const errorMessage = error.response?.data?.error || error.response?.data?.message || error.message
        alert('Failed to transfer stock: ' + errorMessage)
      } finally {
        this.isTransferring = false
      }
    },
    async viewTransferHistory() {
      this.showTransferHistory = true
      this.loadingHistory = true
      try {
        const response = await axios.get('http://localhost:8000/warehouse-transfers')
        this.transferHistory = response.data
      } catch (error) {
        console.error('Error fetching transfer history:', error)
        alert('Failed to load transfer history')
      } finally {
        this.loadingHistory = false
      }
    },
    closeTransferHistory() {
      this.showTransferHistory = false
    },
    exportTransferHistory() {
      if (this.transferHistory.length === 0) return

      const headers = ['Transfer #', 'Date', 'Product', 'Type', 'From', 'To', 'Quantity', 'By', 'Reason', 'Reference', 'Notes']
      const csvData = this.transferHistory.map(t => [
        t.transfer_number,
        this.formatDateTime(t.created_at),
        t.product_name,
        this.formatTransferType(t.transfer_type),
        t.from_warehouse_name || 'N/A',
        t.transfer_type === 'warehouse' ? (t.to_warehouse_name || 'N/A') : (t.external_target || 'Removed'),
        t.quantity,
        t.user_name || 'N/A',
        t.reason || '-',
        t.reference || '-',
        t.note || '-'
      ])

      const csv = [
        headers.join(','),
        ...csvData.map(row => row.map(cell => `"${cell}"`).join(','))
      ].join('\n')

      const blob = new Blob([csv], { type: 'text/csv' })
      const url = window.URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `transfer-history-${new Date().toISOString().split('T')[0]}.csv`
      a.click()
      window.URL.revokeObjectURL(url)
    },
    addTransferItem() {
      this.transferItems.push({ product_id: '', quantity: '' })
    },
    removeTransferItem(index) {
      if (this.transferItems.length === 1) return
      this.transferItems.splice(index, 1)
    },
    onTransferProductChange(index) {
      const item = this.transferItems[index]
      const product = this.getProductById(item.product_id)
      if (product) {
        item.quantity = 1
      }
    },
    getProductById(id) {
      return this.products.find(p => p.id === id)
    },
    getProductStock(productId) {
      const product = this.getProductById(productId)
      return product ? product.stock : 0
    },
    getProductWarehouseName(productId) {
      const product = this.getProductById(productId)
      if (!product) return ''
      return this.getWarehouseName(product.warehouse_id)
    },
    getWarehouseName(warehouseId) {
      if (!warehouseId) return 'Not assigned'
      const warehouse = this.warehouses.find(w => w.id === warehouseId)
      return warehouse ? warehouse.name : 'Unknown Warehouse'
    },
    getSupplierName() {
      if (this.restockDetails.supplier_id === 'others') {
        return this.restockDetails.custom_supplier || 'Custom Supplier'
      }
      const supplier = this.suppliers.find(s => s.id === this.restockDetails.supplier_id)
      return supplier ? supplier.name : 'Unknown Supplier'
    },
    formatTransferType(type) {
      const types = {
        'warehouse': 'Warehouse Transfer',
        'supplier_return': 'Supplier Return',
        'write_off': 'Write Off',
        'adjustment_out': 'Adjustment Out'
      }
      return types[type] || type
    },
    formatDateTime(dateString) {
      if (!dateString) return 'N/A'
      const date = new Date(dateString)
      return date.toLocaleString()
    }
  },
  mounted() {
    this.fetchInventory()
    this.fetchWarehouses()
    this.fetchSuppliers()
    this.fetchCurrentUser()
  }
}
</script>

<style scoped>
/* Modern Inventory Styling */
.modern-inventory {
  min-height: 100vh;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

/* Navigation */
.inventory-nav {
  background: white;
  padding: 1rem 2rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  display: flex;
  justify-content: space-between;
  align-items: center;
  position: sticky;
  top: 0;
  z-index: 50;
}

.nav-brand {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.nav-icon {
  font-size: 1.5rem;
  color: #3b82f6;
}

.nav-brand h1 {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
}

.nav-btn {
  background: #f3f4f6;
  border: none;
  border-radius: 8px;
  padding: 0.75rem;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
}

.nav-btn:hover:not(:disabled) {
  background: #e5e7eb;
}

.nav-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.spinning {
  animation: spin 1s linear infinite;
}

/* Main Container */
.inventory-main {
  max-width: 1400px;
  margin: 0 auto;
  padding: 2rem;
}

/* Loading State */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem;
  color: #6b7280;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #e5e7eb;
  border-top: 3px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

/* Dashboard Section */
.dashboard-section {
  margin-bottom: 2rem;
}

.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
}

.stat-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
}

.stat-card.primary::before { background: #3b82f6; }
.stat-card.success::before { background: #10b981; }
.stat-card.warning::before { background: #f59e0b; }
.stat-card.danger::before { background: #ef4444; }

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.stat-card.urgent {
  animation: pulse 2s infinite;
}

.stat-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
  font-weight: 600;
  color: #6b7280;
  font-size: 0.875rem;
}

.stat-header i {
  font-size: 1.25rem;
}

.stat-card.primary .stat-header i { color: #3b82f6; }
.stat-card.success .stat-header i { color: #10b981; }
.stat-card.warning .stat-header i { color: #f59e0b; }
.stat-card.danger .stat-header i { color: #ef4444; }

.stat-value {
  font-size: 2rem;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 0.5rem;
}

.stat-subtitle {
  font-size: 0.875rem;
  color: #6b7280;
}

/* Control Panel */
.control-panel {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1.5rem;
  flex-wrap: wrap;
}

.panel-left {
  flex: 1;
  min-width: 300px;
}

.search-box {
  position: relative;
  display: flex;
  align-items: center;
  max-width: 400px;
}

.search-box i {
  position: absolute;
  left: 1rem;
  color: #9ca3af;
  z-index: 1;
}

.search-box input {
  width: 100%;
  padding: 0.75rem 1rem 0.75rem 2.5rem;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  font-size: 1rem;
  transition: all 0.3s ease;
  background: #f9fafb;
}

.search-box input:focus {
  outline: none;
  border-color: #3b82f6;
  background: white;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.clear-btn {
  position: absolute;
  right: 0.5rem;
  background: #e5e7eb;
  border: none;
  border-radius: 50%;
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #6b7280;
  transition: all 0.3s ease;
}

.clear-btn:hover {
  background: #d1d5db;
  color: #374151;
}

.panel-right {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.control-btn {
  padding: 0.75rem 1.25rem;
  border: none;
  border-radius: 12px;
  font-weight: 600;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  white-space: nowrap;
}

.control-btn.primary {
  background: #3b82f6;
  color: white;
}

.control-btn.secondary {
  background: #10b981;
  color: white;
}

.control-btn.outline {
  background: white;
  color: #6b7280;
  border: 2px solid #e5e7eb;
}

.control-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.control-btn.primary:hover { background: #2563eb; }
.control-btn.secondary:hover { background: #059669; }
.control-btn.outline:hover {
  border-color: #3b82f6;
  color: #3b82f6;
}

/* Results Info */
.results-info {
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 12px;
  padding: 1rem 1.5rem;
  margin-bottom: 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.results-text {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #1e40af;
  font-weight: 500;
}

.clear-filter {
  background: #1e40af;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
}

.clear-filter:hover {
  background: #1d4ed8;
}

/* Table Section */
.table-section {
  background: white;
  border-radius: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  margin-bottom: 1.5rem;
}

.table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #f3f4f6;
  background: #f8fafc;
}

.table-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
}

.page-select {
  padding: 0.5rem 0.75rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 0.875rem;
  background: white;
  cursor: pointer;
}

.page-select:focus {
  outline: none;
  border-color: #3b82f6;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 4rem 2rem;
}

.empty-icon {
  font-size: 4rem;
  margin-bottom: 1.5rem;
  opacity: 0.5;
}

.empty-state h3 {
  margin: 0 0 1rem 0;
  font-size: 1.5rem;
  color: #374151;
  font-weight: 600;
}

.empty-state p {
  color: #6b7280;
  margin-bottom: 2rem;
  line-height: 1.6;
}

.empty-btn {
  background: #3b82f6;
  color: white;
  border: none;
  padding: 1rem 2rem;
  border-radius: 12px;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
}

.empty-btn:hover {
  background: #2563eb;
  transform: translateY(-1px);
}

/* Data Table */
.table-container {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th {
  background: #f8fafc;
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  color: #374151;
  font-size: 0.875rem;
  border-bottom: 2px solid #e5e7eb;
  position: sticky;
  top: 0;
  z-index: 10;
}

.sortable {
  cursor: pointer;
  user-select: none;
  transition: background-color 0.3s ease;
  position: relative;
}

.sortable:hover {
  background: #f1f5f9;
}

.sortable span {
  margin-right: 0.5rem;
}

.sortable i {
  position: absolute;
  right: 1rem;
  top: 50%;
  transform: translateY(-50%);
  font-size: 0.75rem;
  color: #9ca3af;
}

.fa-sort-up,
.fa-sort-down {
  color: #3b82f6 !important;
}

.data-table td {
  padding: 1rem;
  border-bottom: 1px solid #f3f4f6;
  vertical-align: middle;
}

.data-row {
  transition: background-color 0.3s ease;
}

.data-row:hover {
  background: #f8fafc;
}

.data-row.low-stock {
  background: linear-gradient(90deg, #fef3c7 0%, #ffffff 100%);
}

.data-row.out-of-stock {
  background: linear-gradient(90deg, #fee2e2 0%, #ffffff 100%);
}

/* Table Cell Content */
.product-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.product-name {
  font-weight: 600;
  color: #1f2937;
}

.product-sku {
  font-size: 0.75rem;
  color: #6b7280;
  background: #f3f4f6;
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
  width: fit-content;
}

.category-tag {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: capitalize;
}

/* Category Colors */
.category-tag.fertilizers { background: #dcfce7; color: #166534; }
.category-tag.pesticides { background: #fed7d7; color: #c53030; }
.category-tag.seeds { background: #fef3c7; color: #92400e; }
.category-tag.animal-feed { background: #ddd6fe; color: #6b46c1; }
.category-tag.veterinary { background: #dbeafe; color: #1e40af; }
.category-tag.tools { background: #e0e7ff; color: #3730a3; }
.category-tag.irrigation { background: #cffafe; color: #0f766e; }
.category-tag.dairy { background: #fce7f3; color: #be185d; }
.category-tag.poultry { background: #fef2f2; color: #991b1b; }
.category-tag.livestock { background: #f0fdf4; color: #15803d; }
.category-tag.crop { background: #fff7ed; color: #c2410c; }
.category-tag.soil { background: #f5f3ff; color: #7c3aed; }
.category-tag.greenhouse { background: #ecfdf5; color: #047857; }
.category-tag.default { background: #f3f4f6; color: #374151; }

.stock-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.stock-number {
  font-weight: 700;
  font-size: 1.125rem;
}

.stock-number.normal { color: #059669; }
.stock-number.low { color: #d97706; }
.stock-number.zero { color: #dc2626; }

.stock-badge {
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
  text-align: center;
  width: fit-content;
}

.stock-badge.normal { background: #dcfce7; color: #166534; }
.stock-badge.low { background: #fef3c7; color: #92400e; }
.stock-badge.zero { background: #fee2e2; color: #991b1b; }

.price {
  font-weight: 600;
  color: #1f2937;
  font-size: 1.125rem;
}

.date {
  color: #6b7280;
  font-size: 0.875rem;
}

.action-group {
  display: flex;
  gap: 0.5rem;
}

.action-btn {
  background: white;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  padding: 0.5rem;
  cursor: pointer;
  transition: all 0.3s ease;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6b7280;
}

.action-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.action-btn.edit:hover {
  border-color: #3b82f6;
  background: #eff6ff;
  color: #3b82f6;
}

.action-btn.delete:hover {
  border-color: #ef4444;
  background: #fef2f2;
  color: #ef4444;
}

/* Pagination */
.pagination-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: white;
  padding: 1.5rem;
  border-radius: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  flex-wrap: wrap;
  gap: 1rem;
}

.pagination-info {
  color: #6b7280;
  font-size: 0.875rem;
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.page-btn {
  padding: 0.5rem 1rem;
  border: 2px solid #e5e7eb;
  background: white;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #374151;
  font-weight: 500;
}

.page-btn:hover:not(:disabled) {
  border-color: #3b82f6;
  color: #3b82f6;
}

.page-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-numbers {
  display: flex;
  gap: 0.25rem;
}

.page-number {
  width: 36px;
  height: 36px;
  border: 2px solid #e5e7eb;
  background: white;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 500;
  color: #374151;
}

.page-number:hover:not(.dots) {
  border-color: #3b82f6;
  color: #3b82f6;
}

.page-number.active {
  background: #3b82f6;
  border-color: #3b82f6;
  color: white;
}

.page-number.dots {
  border: none;
  cursor: default;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  backdrop-filter: blur(4px);
}

.modal {
  background: white;
  border-radius: 16px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  max-width: 500px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
  animation: modalSlideIn 0.3s ease-out;
}

.restock-modal {
  max-width: 700px;
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: translateY(-20px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #f3f4f6;
}

.modal-header h3 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.close-btn {
  background: #f3f4f6;
  border: none;
  border-radius: 50%;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #6b7280;
  transition: all 0.3s ease;
}

.close-btn:hover {
  background: #e5e7eb;
  color: #374151;
}

.modal-body {
  padding: 1.5rem;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1.5rem;
  border-top: 1px solid #f3f4f6;
  background: #f8fafc;
}

/* Form Styles */
.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group.full-width {
  grid-column: 1 / -1;
}

.form-group label {
  font-weight: 600;
  color: #374151;
  font-size: 0.875rem;
}

.form-group input,
.form-group select,
.form-group textarea {
  padding: 0.75rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 1rem;
  transition: all 0.3s ease;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-group textarea {
  min-height: 80px;
  resize: vertical;
}

.modal-btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.modal-btn.primary {
  background: #3b82f6;
  color: white;
}

.modal-btn.secondary {
  background: #f3f4f6;
  color: #374151;
}

.modal-btn.danger {
  background: #ef4444;
  color: white;
}

.modal-btn:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.modal-btn.primary:hover { background: #2563eb; }
.modal-btn.danger:hover { background: #dc2626; }

.modal-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

/* Restock Modal Specific */
.warning-box {
  background: #fef2f2;
  padding: 1.5rem;
  border-radius: 8px;
  border-left: 4px solid #ef4444;
}

.warning-box p {
  margin: 0 0 0.5rem 0;
  color: #374151;
}

.add-btn {
  background: #10b981;
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  width: 100%;
}

.add-btn:hover:not(:disabled) {
  background: #059669;
}

.add-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.restock-list {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e5e7eb;
}

.restock-list h4 {
  margin: 0 0 1rem 0;
  color: #374151;
  font-weight: 600;
}

.item-list {
  background: #f8fafc;
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 1rem;
}

.restock-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem;
  background: white;
  border-radius: 8px;
  margin-bottom: 0.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.restock-item:last-child {
  margin-bottom: 0;
}

.item-info {
  flex: 1;
}

.item-name {
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.item-details {
  font-size: 0.875rem;
  color: #6b7280;
}

.item-total {
  font-weight: 600;
  color: #059669;
  margin-right: 1rem;
}

.remove-btn {
  background: #fee2e2;
  border: none;
  border-radius: 50%;
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #dc2626;
  transition: all 0.3s ease;
}

.remove-btn:hover {
  background: #fecaca;
}

.total-cost {
  text-align: right;
  padding: 1rem;
  border-top: 2px solid #e5e7eb;
  font-size: 1.125rem;
  color: #1f2937;
  font-weight: 600;
}

.order-summary {
  background: #f0f9ff;
  padding: 1.5rem;
  border-radius: 8px;
  border: 1px solid #bfdbfe;
}

.order-summary h4 {
  margin: 0 0 1rem 0;
  color: #0c4a6e;
  font-weight: 600;
}

.summary-list {
  margin-bottom: 1rem;
}

.summary-item {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr;
  gap: 1rem;
  padding: 0.5rem 0;
  border-bottom: 1px solid #bfdbfe;
}

.summary-item:last-child {
  border-bottom: none;
}

.summary-total {
  text-align: right;
  font-size: 1.125rem;
  color: #0c4a6e;
  font-weight: 600;
  padding-top: 1rem;
  border-top: 2px solid #93c5fd;
}

/* Animations */
@keyframes spin {
  to { transform: rotate(360deg); }
}

@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.02); }
}

/* Responsive Design */
@media (max-width: 768px) {
  .inventory-main {
    padding: 1rem;
  }

  .nav-brand h1 {
    display: none;
  }

  .dashboard-grid {
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  }

  .control-panel {
    flex-direction: column;
    align-items: stretch;
  }

  .panel-left {
    min-width: auto;
  }

  .panel-right {
    justify-content: center;
  }

  .search-box {
    max-width: none;
  }

  .table-header {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }

  .pagination-section {
    flex-direction: column;
    text-align: center;
  }

  .modal {
    margin: 1rem;
    width: calc(100% - 2rem);
  }
}

@media (max-width: 640px) {
  .dashboard-grid {
    grid-template-columns: 1fr;
  }

  .stat-card {
    text-align: center;
  }

  .control-btn {
    flex: 1;
    justify-content: center;
  }

  .data-table {
    font-size: 0.875rem;
  }

  .data-table th,
  .data-table td {
    padding: 0.75rem 0.5rem;
  }
}

/* Stock Transfer Styles */
.action-btn.transfer {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.action-btn.transfer:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.control-btn.info {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
  color: white;
}

.control-btn.info:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(79, 172, 254, 0.4);
}

.transfer-modal,
.history-modal {
  max-width: 700px;
}

.transfer-form .info-banner {
  background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
  border-left: 4px solid #667eea;
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.transfer-form .info-banner i {
  color: #667eea;
  font-size: 1.25rem;
}

.transfer-form .warehouse-info {
  color: #718096;
  font-weight: normal;
  margin-left: 0.5rem;
}

.transfer-form .form-group {
  margin-bottom: 1.25rem;
}

.transfer-form label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
  color: #2d3748;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
}

.transfer-form label i {
  color: #667eea;
  font-size: 0.95rem;
}

.transfer-form .required {
  color: #e53e3e;
  font-weight: 700;
}

.transfer-form .recommended {
  color: #805ad5;
  font-weight: 400;
  font-size: 0.85rem;
}

.transfer-form .form-input,
.transfer-form .form-select,
.transfer-form .form-textarea,
.transfer-form .disabled-input {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.95rem;
  transition: all 0.3s ease;
}

.transfer-form .form-input:focus,
.transfer-form .form-select:focus,
.transfer-form .form-textarea:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.transfer-form .disabled-input {
  background: #f7fafc;
  color: #718096;
  cursor: not-allowed;
}

.transfer-form .helper-text {
  display: block;
  margin-top: 0.25rem;
  font-size: 0.85rem;
  color: #718096;
}

.transfer-form .helper-text strong {
  color: #667eea;
  font-weight: 600;
}

.tracking-info {
  background: #f7fafc;
  border: 2px dashed #cbd5e0;
  border-radius: 8px;
  padding: 1rem;
  margin-top: 1.5rem;
}

.tracking-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
  color: #4a5568;
}

.tracking-row:last-child {
  margin-bottom: 0;
}

.tracking-row i {
  color: #667eea;
  width: 16px;
}

.tracking-row strong {
  color: #2d3748;
}

/* Transfer History Styles */
.history-table-container {
  max-height: 500px;
  overflow-y: auto;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.history-table {
  width: 100%;
  border-collapse: collapse;
}

.history-table thead {
  position: sticky;
  top: 0;
  background: #f7fafc;
  z-index: 10;
}

.history-table th {
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  color: #2d3748;
  border-bottom: 2px solid #e2e8f0;
  font-size: 0.85rem;
  text-transform: uppercase;
}

.history-table td {
  padding: 0.875rem 1rem;
  border-bottom: 1px solid #e2e8f0;
  font-size: 0.9rem;
  color: #4a5568;
}

.history-table tbody tr:hover {
  background: #f7fafc;
}

.transfer-type-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
}

.transfer-type-badge.warehouse {
  background: #bee3f8;
  color: #2c5282;
}

.transfer-type-badge.supplier_return {
  background: #feebc8;
  color: #7c2d12;
}

.transfer-type-badge.write_off {
  background: #fed7d7;
  color: #742a2a;
}

.transfer-type-badge.adjustment_out {
  background: #e9d8fd;
  color: #553c9a;
}

.external-target {
  color: #d69e2e;
  font-weight: 500;
}

.removed-badge {
  color: #e53e3e;
  font-weight: 500;
}

.reason-text {
  display: inline-block;
  max-width: 200px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Restock Modal Enhancements */
.restock-modal {
  max-width: 900px;
}

.form-section {
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid #e2e8f0;
}

.form-section:last-child {
  border-bottom: none;
}

.form-section h5 {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #2d3748;
  font-size: 1rem;
  margin-bottom: 1rem;
  font-weight: 600;
}

.form-section h5 i {
  color: #667eea;
}

.required {
  color: #e53e3e;
  font-weight: bold;
}

.cost-breakdown {
  background: #f7fafc;
  border-radius: 8px;
  padding: 1rem;
  margin-top: 1rem;
}

.cost-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
  font-size: 0.95rem;
}

.cost-row.total {
  border-top: 2px solid #cbd5e0;
  margin-top: 0.5rem;
  padding-top: 0.75rem;
  font-size: 1.1rem;
  color: #2d3748;
}

.cost-row label {
  font-size: 0.9rem;
  color: #4a5568;
}

.cost-row .inline-input {
  width: 120px;
  padding: 0.375rem 0.75rem;
  border: 1px solid #cbd5e0;
  border-radius: 6px;
  text-align: right;
  font-size: 0.9rem;
}

.cost-row .inline-input:focus {
  outline: none;
  border-color: #667eea;
}

.summary-section {
  margin-bottom: 1.5rem;
  padding: 1rem;
  background: #f7fafc;
  border-radius: 8px;
}

.summary-section h5 {
  color: #2d3748;
  font-size: 0.95rem;
  margin-bottom: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  padding: 0.5rem 0;
  border-bottom: 1px solid #e2e8f0;
  font-size: 0.9rem;
}

.detail-row:last-child {
  border-bottom: none;
}

.detail-row.total {
  border-top: 2px solid #cbd5e0;
  margin-top: 0.5rem;
  padding-top: 0.75rem;
  font-size: 1.05rem;
}

.transfer-number {
  display: inline-block;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 0.25rem 0.75rem;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.85rem;
  font-family: 'Courier New', monospace;
  letter-spacing: 0.5px;
}
</style>
