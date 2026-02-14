<template>
  <div class="promotions-page">
    <!-- Alert Messages -->
    <div v-if="alert.show" :class="['alert', alert.type]">
      {{ alert.message }}
    </div>

    <!-- Header -->
    <div class="page-header">
      <div>
        <h1><i class="fas fa-tags"></i> Promotions & Discounts</h1>
        <p class="subtitle">Manage your promotional campaigns and discount strategies</p>
      </div>
      <button class="btn-primary" @click="showCreateModal = true">
        <i class="fas fa-plus"></i> Create Promotion
      </button>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
      <button 
        :class="['tab', { active: filterStatus === 'all' }]" 
        @click="filterStatus = 'all'"
      >
        All Promotions
      </button>
      <button 
        :class="['tab', { active: filterStatus === 'active' }]" 
        @click="filterStatus = 'active'"
      >
        Active
      </button>
      <button 
        :class="['tab', { active: filterStatus === 'inactive' }]" 
        @click="filterStatus = 'inactive'"
      >
        Inactive
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="spinner large"></div>
      <p>Loading promotions...</p>
    </div>

    <!-- Promotions List -->
    <div v-else class="promotions-grid">
      <div v-if="filteredPromotions.length === 0" class="empty-state">
        <i class="fas fa-tag"></i>
        <h3>No promotions found</h3>
        <p>Create your first promotion to start attracting customers</p>
      </div>

      <div 
        v-for="promo in filteredPromotions" 
        :key="promo.id" 
        class="promotion-card"
        :class="{ inactive: !promo.is_active }"
      >
        <div class="promo-header">
          <div class="promo-title-section">
            <h3>{{ promo.name }}</h3>
            <span :class="['type-badge', promo.type]">{{ formatType(promo.type) }}</span>
          </div>
          <div class="promo-actions">
            <button 
              class="toggle-btn" 
              @click="toggleStatus(promo.id)"
              :title="promo.is_active ? 'Deactivate' : 'Activate'"
            >
              <i :class="promo.is_active ? 'fas fa-toggle-on' : 'fas fa-toggle-off'"></i>
            </button>
            <button class="edit-btn" @click="editPromotion(promo)">
              <i class="fas fa-edit"></i>
            </button>
            <button class="delete-btn" @click="deletePromotion(promo.id)">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </div>

        <p v-if="promo.description" class="promo-description">{{ promo.description }}</p>

        <div class="promo-details">
          <div class="detail-item">
            <i class="fas fa-percentage"></i>
            <span>{{ getDiscountText(promo) }}</span>
          </div>
          <div class="detail-item" v-if="promo.scope !== 'all'">
            <i class="fas fa-bullseye"></i>
            <span>{{ formatScope(promo.scope) }}</span>
          </div>
          <div class="detail-item" v-if="promo.start_date || promo.end_date">
            <i class="fas fa-calendar"></i>
            <span>{{ formatDateRange(promo) }}</span>
          </div>
          <div class="detail-item" v-if="promo.usage_count > 0">
            <i class="fas fa-chart-line"></i>
            <span>Used {{ promo.usage_count }} times</span>
          </div>
        </div>

        <div class="promo-footer">
          <span :class="['status-badge', promo.is_active ? 'active' : 'inactive']">
            {{ promo.is_active ? 'Active' : 'Inactive' }}
          </span>
          <span class="priority-badge">Priority: {{ promo.priority }}</span>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showCreateModal || editingPromo" class="modal-overlay">
      <div class="modal-content large">
        <div class="modal-header">
          <h2>
            <i class="fas fa-tag"></i>
            {{ editingPromo ? 'Edit Promotion' : 'Create Promotion' }}
          </h2>
          <button class="close-btn" @click="closeModal">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <form @submit.prevent="savePromotion" class="promo-form">
          <!-- Basic Information -->
          <div class="form-section">
            <h3>Basic Information</h3>
            <div class="form-row">
              <div class="form-group full-width">
                <label>Promotion Name *</label>
                <input 
                  v-model="form.name" 
                  type="text" 
                  placeholder="e.g., Summer Sale 2026"
                  required
                />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group full-width">
                <label>Description</label>
                <textarea 
                  v-model="form.description" 
                  placeholder="Describe your promotion"
                  rows="3"
                ></textarea>
              </div>
            </div>
          </div>

          <!-- Discount Configuration -->
          <div class="form-section">
            <h3>Discount Configuration</h3>
            <div class="form-row">
              <div class="form-group">
                <label>Promotion Type *</label>
                <select v-model="form.type" required>
                  <option value="">Select Type</option>
                  <option value="percentage">Percentage Discount</option>
                  <option value="fixed_amount">Fixed Amount Off</option>
                  <option value="buy_x_get_y">Buy X Get Y</option>
                  <option value="spend_save">Spend & Save</option>
                  <option value="bulk_discount">Bulk Discount</option>
                </select>
              </div>

              <div class="form-group" v-if="['percentage', 'fixed_amount', 'spend_save', 'bulk_discount'].includes(form.type)">
                <label>{{ form.type === 'percentage' || form.type === 'spend_save' || form.type === 'bulk_discount' ? 'Discount %' : 'Amount (Ksh)' }} *</label>
                <input 
                  v-model="form.discount_value" 
                  type="number" 
                  step="0.01"
                  min="0"
                  required
                />
              </div>
            </div>

            <!-- Buy X Get Y specific fields -->
            <div v-if="form.type === 'buy_x_get_y'" class="buy-x-get-y-config">
              <div class="form-row">
                <div class="form-group">
                  <label>Buy Quantity *</label>
                  <input v-model.number="form.buy_quantity" type="number" min="1" required />
                </div>
                <div class="form-group">
                  <label>Buy Product(s) *</label>
                  <select multiple v-model="form.buy_products" required>
                    <option 
                      v-for="p in products" 
                      :key="p.id" 
                      :value="p.id">
                      {{ p.name }} ({{ p.sku || 'N/A' }})
                    </option>
                  </select>
                  <small>Select product(s) customer must buy</small>
                </div>
              </div>

              <div class="buy-get-divider">
                <span>THEN GET</span>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label>Get Quantity *</label>
                  <input v-model.number="form.get_quantity" type="number" min="1" required />
                </div>
                <div class="form-group">
                  <label>Get Product(s) *</label>
                  <select multiple v-model="form.get_products" required>
                    <option 
                      v-for="p in products" 
                      :key="p.id" 
                      :value="p.id">
                      {{ p.name }} ({{ p.sku || 'N/A' }})
                    </option>
                  </select>
                  <small>Select product(s) customer gets free/discounted</small>
                </div>
              </div>

              <div class="promo-example-box">
                <i class="fas fa-lightbulb"></i>
                <div>
                  <strong>Example:</strong> 
                  <span v-if="form.buy_quantity && form.get_quantity">
                    Buy {{ form.buy_quantity }} 
                    {{ form.buy_products.length > 0 ? getProductNames(form.buy_products) : 'product(s)' }}, 
                    Get {{ form.get_quantity }} 
                    {{ form.get_products.length > 0 ? getProductNames(form.get_products) : 'product(s)' }} 
                    free!
                  </span>
                  <span v-else>
                    Configure quantities and products above
                  </span>
                </div>
              </div>
            </div>

            <!-- Minimum requirements -->
            <div class="form-row">
              <div class="form-group">
                <label>Minimum Purchase (Ksh)</label>
                <input v-model="form.minimum_purchase" type="number" step="0.01" min="0" />
              </div>
              <div class="form-group">
                <label>Minimum Quantity</label>
                <input v-model="form.minimum_quantity" type="number" min="1" />
              </div>
            </div>
          </div>

          <!-- Application Scope -->
          <div class="form-section" v-if="form.type !== 'buy_x_get_y'">
            <h3>Application Scope</h3>
            <div class="form-row">
              <div class="form-group">
                <label>Apply To *</label>
                <select v-model="form.scope" required>
                  <option value="all">All Products</option>
                  <option value="category">Specific Categories</option>
                  <option value="product">Specific Products</option>
                  <option value="customer_group">Customer Groups</option>
                </select>
              </div>
            </div>

            <!-- Scope items selector -->
            <div class="form-row" v-if="form.scope === 'category'">
              <div class="form-group full-width">
                <label>Select Categories</label>
                <select multiple v-model="form.scope_items">
                  <option 
                    v-for="cat in categories" 
                    :key="cat.id" 
                    :value="cat.name">
                    {{ cat.name }}
                  </option>
                </select>
                <small>Hold Ctrl/Cmd to select multiple categories</small>
              </div>
            </div>

            <div class="form-row" v-if="form.scope === 'product'">
              <div class="form-group full-width">
                <label>Select Products</label>
                <select multiple v-model="form.scope_items">
                  <option 
                    v-for="p in products" 
                    :key="p.id" 
                    :value="p.id">
                    {{ p.name }} (SKU: {{ p.sku || 'N/A' }})
                  </option>
                </select>
                <small>Hold Ctrl/Cmd to select multiple products</small>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>
                  <input type="checkbox" v-model="form.first_time_only" />
                  First-time customers only
                </label>
              </div>
            </div>
          </div>

          <!-- Time Settings -->
          <div class="form-section">
            <h3>Time Settings</h3>
            <div class="form-row">
              <div class="form-group">
                <label>Start Date</label>
                <input v-model="form.start_date" type="datetime-local" />
              </div>
              <div class="form-group">
                <label>End Date</label>
                <input v-model="form.end_date" type="datetime-local" />
              </div>
            </div>
          </div>

          <!-- Usage Limits -->
          <div class="form-section">
            <h3>Usage Limits</h3>
            <div class="form-row">
              <div class="form-group">
                <label>Total Usage Limit</label>
                <input v-model="form.usage_limit_total" type="number" min="1" placeholder="Unlimited" />
              </div>
              <div class="form-group">
                <label>Per Customer Limit</label>
                <input v-model="form.usage_limit_per_customer" type="number" min="1" placeholder="Unlimited" />
              </div>
            </div>
          </div>

          <!-- Advanced Settings -->
          <div class="form-section">
            <h3>Advanced Settings</h3>
            <div class="form-row">
              <div class="form-group">
                <label>Priority (0-100)</label>
                <input v-model="form.priority" type="number" min="0" max="100" />
                <small>Higher priority promotions are applied first</small>
              </div>
              <div class="form-group">
                <label>
                  <input type="checkbox" v-model="form.is_stackable" />
                  Allow stacking with other promotions
                </label>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>
                  <input type="checkbox" v-model="form.is_active" />
                  Activate immediately
                </label>
              </div>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="form-actions">
            <button type="button" class="btn-secondary" @click="closeModal">Cancel</button>
            <button type="submit" class="btn-primary" :disabled="saving">
              <span v-if="saving" class="spinner"></span>
              <span v-else>{{ editingPromo ? 'Update' : 'Create' }} Promotion</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'PromotionsPage',
  data() {
    return {
      promotions: [],
      loading: false,
      saving: false,
      showCreateModal: false,
      editingPromo: null,
      filterStatus: 'all',
      alert: {
        show: false,
        message: '',
        type: 'success'
      },
      categories: [],
      products: [],
      form: {
        name: '',
        description: '',
        type: '',
        discount_value: null,
        buy_quantity: null,
        get_quantity: null,
        buy_products: [], // New: products customer must buy
        get_products: [], // New: products customer gets
        minimum_purchase: null,
        minimum_quantity: null,
        scope: 'all',
        scope_items: [],
        first_time_only: false,
        customer_groups: [],
        start_date: null,
        end_date: null,
        usage_limit_total: null,
        usage_limit_per_customer: null,
        is_active: true,
        priority: 0,
        is_stackable: false
      }
    }
  },
  computed: {
    filteredPromotions() {
      if (this.filterStatus === 'all') return this.promotions
      if (this.filterStatus === 'active') {
        return this.promotions.filter(p => p.is_active)
      }
      return this.promotions.filter(p => !p.is_active)
    }
  },
  mounted() {
    this.fetchPromotions()
    this.fetchCategories()
    this.fetchProducts()
  },
  methods: {
    async fetchPromotions() {
      this.loading = true
      try {
        const res = await axios.get('/promotions')
        this.promotions = res.data
      } catch (err) {
        this.showAlert('Failed to load promotions', 'error')
        console.error(err)
      } finally {
        this.loading = false
      }
    },

    async fetchCategories() {
      try {
        const res = await axios.get('/product-categories')
        this.categories = res.data || []
      } catch (err) {
        console.warn('Failed to load categories', err)
      }
    },

    async fetchProducts() {
      try {
        const res = await axios.get('/products')
        // Keep it light; optionally slice if too many
        this.products = Array.isArray(res.data) ? res.data : []
      } catch (err) {
        console.warn('Failed to load products', err)
      }
    },

    async savePromotion() {
      this.saving = true
      try {
        // For buy_x_get_y, merge buy and get products into scope_items
        const payload = { ...this.form }
        if (this.form.type === 'buy_x_get_y') {
          payload.scope = 'product'
          // Combine buy and get products (removing duplicates)
          const allProducts = [...new Set([...this.form.buy_products, ...this.form.get_products])]
          payload.scope_items = allProducts
          payload.buy_products = this.form.buy_products
          payload.get_products = this.form.get_products
        }

        if (this.editingPromo) {
          await axios.put(`/promotions/${this.editingPromo.id}`, payload)
          this.showAlert('Promotion updated successfully', 'success')
        } else {
          await axios.post('/promotions', payload)
          this.showAlert('Promotion created successfully', 'success')
        }
        await this.fetchPromotions()
        this.closeModal()
      } catch (err) {
        this.showAlert(err.response?.data?.message || 'Failed to save promotion', 'error')
        console.error(err)
      } finally {
        this.saving = false
      }
    },

    async toggleStatus(id) {
      try {
        await axios.post(`/promotions/${id}/toggle`)
        await this.fetchPromotions()
        this.showAlert('Promotion status updated', 'success')
      } catch (err) {
        this.showAlert('Failed to update status', 'error')
      }
    },

    async deletePromotion(id) {
      if (!confirm('Are you sure you want to delete this promotion?')) return

      try {
        await axios.delete(`/promotions/${id}`)
        await this.fetchPromotions()
        this.showAlert('Promotion deleted successfully', 'success')
      } catch (err) {
        this.showAlert(err.response?.data?.error || 'Failed to delete promotion', 'error')
      }
    },

    editPromotion(promo) {
      this.editingPromo = promo
      
      // Parse buy and get products if they exist
      let buyProducts = []
      let getProducts = []
      
      if (promo.type === 'buy_x_get_y') {
        // Try to extract from metadata or scope_items
        if (promo.buy_products) {
          buyProducts = Array.isArray(promo.buy_products) ? promo.buy_products : JSON.parse(promo.buy_products || '[]')
        }
        if (promo.get_products) {
          getProducts = Array.isArray(promo.get_products) ? promo.get_products : JSON.parse(promo.get_products || '[]')
        }
        
        // Fallback: if not found, use scope_items for both
        if (buyProducts.length === 0 && promo.scope_items) {
          const scopeItems = Array.isArray(promo.scope_items) ? promo.scope_items : JSON.parse(promo.scope_items || '[]')
          buyProducts = scopeItems
          getProducts = scopeItems
        }
      }

      this.form = {
        name: promo.name,
        description: promo.description,
        type: promo.type,
        discount_value: promo.discount_value,
        buy_quantity: promo.buy_quantity,
        get_quantity: promo.get_quantity,
        buy_products: buyProducts,
        get_products: getProducts,
        minimum_purchase: promo.minimum_purchase,
        minimum_quantity: promo.minimum_quantity,
        scope: promo.scope,
        scope_items: promo.scope_items || [],
        first_time_only: promo.first_time_only,
        customer_groups: promo.customer_groups || [],
        start_date: promo.start_date ? new Date(promo.start_date).toISOString().slice(0, 16) : null,
        end_date: promo.end_date ? new Date(promo.end_date).toISOString().slice(0, 16) : null,
        usage_limit_total: promo.usage_limit_total,
        usage_limit_per_customer: promo.usage_limit_per_customer,
        is_active: promo.is_active,
        priority: promo.priority,
        is_stackable: promo.is_stackable
      }
    },

    closeModal() {
      this.showCreateModal = false
      this.editingPromo = null
      this.resetForm()
    },

    resetForm() {
      this.form = {
        name: '',
        description: '',
        type: '',
        discount_value: null,
        buy_quantity: null,
        get_quantity: null,
        buy_products: [],
        get_products: [],
        minimum_purchase: null,
        minimum_quantity: null,
        scope: 'all',
        scope_items: [],
        first_time_only: false,
        customer_groups: [],
        start_date: null,
        end_date: null,
        usage_limit_total: null,
        usage_limit_per_customer: null,
        is_active: true,
        priority: 0,
        is_stackable: false
      }
    },

    getProductNames(productIds) {
      if (!productIds || productIds.length === 0) return ''
      const names = productIds
        .map(id => {
          const product = this.products.find(p => p.id === id)
          return product ? product.name : null
        })
        .filter(Boolean)
      
      if (names.length === 0) return 'selected product(s)'
      if (names.length === 1) return names[0]
      if (names.length === 2) return names.join(' or ')
      return `${names.slice(0, -1).join(', ')}, or ${names[names.length - 1]}`
    },

    getDiscountText(promo) {
      if (promo.type === 'percentage' || promo.type === 'spend_save' || promo.type === 'bulk_discount') {
        return `${promo.discount_value}% off`
      } else if (promo.type === 'fixed_amount') {
        return `Ksh ${promo.discount_value} off`
      } else if (promo.type === 'buy_x_get_y') {
        const buyProducts = this.getPromoProducts(promo, 'buy')
        const getProducts = this.getPromoProducts(promo, 'get')
        return `Buy ${promo.buy_quantity} ${buyProducts}, Get ${promo.get_quantity} ${getProducts}`
      }
      return 'Discount'
    },

    getPromoProducts(promo, type) {
      try {
        const productIds = type === 'buy' 
          ? (promo.buy_products || promo.scope_items)
          : (promo.get_products || promo.scope_items)
        
        if (!productIds) return 'product(s)'
        
        const ids = Array.isArray(productIds) ? productIds : JSON.parse(productIds || '[]')
        if (ids.length === 0) return 'product(s)'
        
        const names = ids
          .map(id => {
            const product = this.products.find(p => p.id === id)
            return product ? product.name : null
          })
          .filter(Boolean)
        
        if (names.length === 0) return 'product(s)'
        if (names.length === 1) return names[0]
        return `${names.length} products`
      } catch (e) {
        return 'product(s)'
      }
    },

    showAlert(message, type = 'success') {
      this.alert = { show: true, message, type }
      setTimeout(() => {
        this.alert.show = false
      }, 3000)
    }
  }
}
</script>

<style scoped>
.promotions-page {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

.alert {
  padding: 1rem 1.5rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
  font-weight: 500;
}

.alert.success {
  background: #d1fae5;
  color: #065f46;
  border: 1px solid #6ee7b7;
}

.alert.error {
  background: #fee2e2;
  color: #991b1b;
  border: 1px solid #fca5a5;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
}

.page-header h1 {
  font-size: 2rem;
  margin: 0 0 0.5rem 0;
  color: #1f2937;
}

.page-header h1 i {
  color: #3b82f6;
  margin-right: 0.5rem;
}

.subtitle {
  color: #6b7280;
  margin: 0;
}

.filter-tabs {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
  border-bottom: 2px solid #e5e7eb;
}

.tab {
  background: none;
  border: none;
  padding: 0.75rem 1.5rem;
  cursor: pointer;
  font-weight: 600;
  color: #6b7280;
  border-bottom: 3px solid transparent;
  transition: all 0.3s;
}

.tab.active {
  color: #3b82f6;
  border-bottom-color: #3b82f6;
}

.promotions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1.5rem;
}

.promotion-card {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 1.5rem;
  transition: all 0.3s;
}

.promotion-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}

.promotion-card.inactive {
  opacity: 0.6;
  background: #f9fafb;
}

.promo-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.promo-title-section h3 {
  margin: 0 0 0.5rem 0;
  color: #1f2937;
  font-size: 1.25rem;
}

.type-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
}

.type-badge.percentage,
.type-badge.spend_save,
.type-badge.bulk_discount {
  background: #dbeafe;
  color: #1e40af;
}

.type-badge.fixed_amount {
  background: #dcfce7;
  color: #166534;
}

.type-badge.buy_x_get_y {
  background: #fef3c7;
  color: #92400e;
}

.promo-actions {
  display: flex;
  gap: 0.5rem;
}

.toggle-btn,
.edit-btn,
.delete-btn {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1.1rem;
  padding: 0.5rem;
  transition: all 0.3s;
}

.toggle-btn {
  color: #3b82f6;
}

.edit-btn {
  color: #6b7280;
}

.edit-btn:hover {
  color: #3b82f6;
}

.delete-btn {
  color: #6b7280;
}

.delete-btn:hover {
  color: #dc2626;
}

.promo-description {
  color: #6b7280;
  margin: 0 0 1rem 0;
  font-size: 0.9rem;
}

.promo-details {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.detail-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
  color: #4b5563;
}

.detail-item i {
  color: #3b82f6;
  width: 16px;
}

.promo-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 1rem;
  border-top: 1px solid #e5e7eb;
}

.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
}

.status-badge.active {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.inactive {
  background: #fee2e2;
  color: #991b1b;
}

.priority-badge {
  font-size: 0.75rem;
  color: #6b7280;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 12px;
  width: 90%;
  max-width: 800px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h2 {
  margin: 0;
  font-size: 1.5rem;
  color: #1f2937;
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #6b7280;
}

.promo-form {
  padding: 1.5rem;
}

.form-section {
  margin-bottom: 2rem;
}

.form-section h3 {
  margin: 0 0 1rem 0;
  color: #1f2937;
  font-size: 1.1rem;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid #e5e7eb;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-bottom: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group.full-width {
  grid-column: 1 / -1;
}

.form-group label {
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #374151;
  font-size: 0.9rem;
}

.form-group input[type="text"],
.form-group input[type="number"],
.form-group input[type="datetime-local"],
.form-group select,
.form-group textarea {
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 1rem;
}

.form-group small {
  color: #6b7280;
  font-size: 0.8rem;
  margin-top: 0.25rem;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e5e7eb;
}

.btn-primary,
.btn-secondary {
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  border: none;
}

.btn-primary {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
}

.btn-primary:hover {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
}

.btn-secondary {
  background: #f3f4f6;
  color: #374151;
}

.btn-secondary:hover {
  background: #e5e7eb;
}

.loading-container,
.empty-state {
  text-align: center;
  padding: 4rem 2rem;
}

.spinner {
  display: inline-block;
  width: 20px;
  height: 20px;
  border: 3px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: white;
  animation: spin 1s linear infinite;
}

.spinner.large {
  width: 50px;
  height: 50px;
  border-color: rgba(59, 130, 246, 0.3);
  border-top-color: #3b82f6;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty-state i {
  font-size: 4rem;
  color: #d1d5db;
  margin-bottom: 1rem;
}

.empty-state h3 {
  color: #6b7280;
  margin-bottom: 0.5rem;
}

.empty-state p {
  color: #9ca3af;
}

.buy-x-get-y-config {
  background: #f0f9ff;
  border: 2px solid #bae6fd;
  border-radius: 12px;
  padding: 1.5rem;
  margin-top: 1rem;
}

.buy-get-divider {
  text-align: center;
  margin: 1.5rem 0;
  position: relative;
}

.buy-get-divider::before,
.buy-get-divider::after {
  content: '';
  position: absolute;
  top: 50%;
  width: 35%;
  height: 2px;
  background: linear-gradient(to right, transparent, #3b82f6, transparent);
}

.buy-get-divider::before {
  left: 0;
}

.buy-get-divider::after {
  right: 0;
}

.buy-get-divider span {
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  color: white;
  padding: 0.5rem 2rem;
  border-radius: 20px;
  font-weight: 700;
  font-size: 0.9rem;
  letter-spacing: 1px;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
  display: inline-block;
}

.promo-example-box {
  background: white;
  border: 2px dashed #3b82f6;
  border-radius: 8px;
  padding: 1rem;
  margin-top: 1rem;
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
}

.promo-example-box i {
  color: #f59e0b;
  font-size: 1.5rem;
  margin-top: 0.25rem;
}

.promo-example-box strong {
  color: #1f2937;
  display: block;
  margin-bottom: 0.25rem;
}

.promo-example-box span {
  color: #4b5563;
  font-size: 0.95rem;
}

.form-group select[multiple] {
  min-height: 120px;
  padding: 0.5rem;
}

.form-group select[multiple] option {
  padding: 0.5rem;
  margin: 2px 0;
  border-radius: 4px;
}

.form-group select[multiple] option:checked {
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  color: white;
}
</style>