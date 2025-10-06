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
              <select v-model="pageSize" class="page-select">
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
                      <button @click="editProduct(product)" class="action-btn edit">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button @click="confirmDelete(product)" class="action-btn delete">
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
    <div v-if="showDeleteModal" class="modal-overlay" @click.self="closeDeleteModal">
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
    <div v-if="showReplenishModal" class="modal-overlay" @click.self="closeReplenishModal">
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
            <h4>Add Items to Restock</h4>
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
                <label>Notes (Optional)</label>
                <textarea v-model="replenishForm.notes" placeholder="Additional notes..."></textarea>
              </div>
            </div>
            <button @click="addReplenishItem" class="add-btn" :disabled="!canAddReplenishItem">
              <i class="fas fa-plus"></i>
              Add to List
            </button>
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
            <div class="total-cost">
              Total: {{ formatCurrency(replenishTotal) }}
            </div>
          </div>

          <div v-if="replenishStep === 2" class="order-summary">
            <h4>Order Summary</h4>
            <div class="summary-list">
              <div v-for="(item, index) in replenishItems" :key="index" class="summary-item">
                <span>{{ getProductName(item.product_id) }}</span>
                <span>{{ item.quantity }} units</span>
                <span>{{ formatCurrency(item.quantity * item.cost) }}</span>
              </div>
            </div>
            <div class="summary-total">
              Total: {{ formatCurrency(replenishTotal) }}
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <div v-if="replenishStep === 1">
            <button @click="closeReplenishModal" class="modal-btn secondary">Cancel</button>
            <button @click="replenishStep = 2" class="modal-btn primary" :disabled="replenishItems.length === 0">
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
      replenishItems: []
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
    }
  },
  methods: {
    async fetchInventory() {
      this.isLoading = true
      try {
        const response = await axios.get('/api/products')
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
      // This should be handled by parent component or router
      this.$emit('open-add-product')
    },
    editProduct(product) {
      // This should be handled by parent component or router
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
        const payload = {
          items: this.replenishItems
        }
        await axios.post('/api/inventory/restock', payload)
        await this.fetchInventory() // Refresh data
        this.closeReplenishModal()
      } catch (error) {
        console.error('Error saving restock:', error)
        alert('Failed to save restock. Please try again.')
      }
    },
    exportData() {
      // Implement export functionality
      console.log('Export data')
    }
  },
  mounted() {
    this.fetchInventory()
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
</style>
