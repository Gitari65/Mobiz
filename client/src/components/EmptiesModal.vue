<template>
  <div v-if="isOpen" class="modal-overlay" @click.self="closeModal">
    <div class="empties-modal">
      <!-- Header -->
      <div class="modal-header">
        <div class="header-title">
          <i class="fas fa-recycle"></i>
          <div>
            <h3>Manage Returnables/Empties</h3>
            <p class="product-name">{{ product?.name }}</p>
          </div>
        </div>
        <button @click="closeModal" class="close-btn">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <!-- Body -->
      <div class="modal-body">
        <!-- Add New Empty Section -->
        <div class="add-empty-section">
          <h4><i class="fas fa-plus-circle"></i> Link New Empty/Returnable</h4>
          <div class="add-form">
            <div class="form-row">
              <div class="form-group flex-2">
                <label>Select Empty Product</label>
                <select v-model="newEmpty.empty_product_id" :disabled="isLoading">
                  <option value="">-- Choose a product --</option>
                  <option 
                    v-for="prod in availableEmpties" 
                    :key="prod.id" 
                    :value="prod.id"
                  >
                    {{ prod.name }} ({{ prod.sku }})
                  </option>
                </select>
              </div>
              <div class="form-group">
                <label>Quantity per Unit</label>
                <input 
                  type="number" 
                  v-model.number="newEmpty.quantity" 
                  min="1" 
                  placeholder="1"
                  :disabled="isLoading"
                />
              </div>
              <div class="form-group">
                <label>Deposit Amount (KES)</label>
                <input 
                  type="number" 
                  v-model.number="newEmpty.deposit_amount" 
                  min="0" 
                  step="0.01"
                  placeholder="0.00"
                  :disabled="isLoading"
                />
              </div>
              <div class="form-group-btn">
                <button 
                  @click="addEmpty" 
                  class="btn-add" 
                  :disabled="!canAddEmpty || isLoading"
                >
                  <i class="fas fa-link"></i>
                  Link
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Linked Empties List -->
        <div class="empties-list-section">
          <h4><i class="fas fa-list"></i> Linked Empties ({{ empties.length }})</h4>
          
          <!-- Loading State -->
          <div v-if="isLoading && empties.length === 0" class="loading-state">
            <div class="spinner"></div>
            <p>Loading empties...</p>
          </div>

          <!-- Empty State -->
          <div v-else-if="empties.length === 0" class="empty-state">
            <i class="fas fa-box-open"></i>
            <p>No empties linked to this product yet</p>
            <small>Link returnable containers or empties that come with this product</small>
          </div>

          <!-- Empties Table -->
          <div v-else class="empties-table">
            <table>
              <thead>
                <tr>
                  <th>Product</th>
                  <th>SKU</th>
                  <th>Qty/Unit</th>
                  <th>Deposit (KES)</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="empty in empties" :key="empty.id" :class="{ 'inactive': !empty.is_active }">
                  <td>
                    <div class="product-cell">
                      <strong>{{ empty.name }}</strong>
                    </div>
                  </td>
                  <td>
                    <span class="sku-badge">{{ empty.sku }}</span>
                  </td>
                  <td>
                    <div v-if="editingEmpty === empty.id" class="inline-edit">
                      <input 
                        type="number" 
                        v-model.number="editForm.quantity" 
                        min="1"
                        class="mini-input"
                      />
                    </div>
                    <div v-else class="quantity-display">
                      <span class="qty-number">{{ empty.quantity }}</span>
                      <span class="qty-label">{{ empty.quantity > 1 ? 'items' : 'item' }}</span>
                    </div>
                  </td>
                  <td>
                    <div v-if="editingEmpty === empty.id" class="inline-edit">
                      <input 
                        type="number" 
                        v-model.number="editForm.deposit_amount" 
                        min="0"
                        step="0.01"
                        class="mini-input"
                      />
                    </div>
                    <span v-else class="deposit-amount">
                      {{ formatCurrency(empty.deposit_amount) }}
                    </span>
                  </td>
                  <td>
                    <span class="status-badge" :class="empty.is_active ? 'active' : 'inactive'">
                      {{ empty.is_active ? 'Active' : 'Inactive' }}
                    </span>
                  </td>
                  <td>
                    <div class="action-buttons">
                      <template v-if="editingEmpty === empty.id">
                        <button 
                          @click="saveEdit(empty.id)" 
                          class="btn-icon success"
                          title="Save"
                        >
                          <i class="fas fa-check"></i>
                        </button>
                        <button 
                          @click="cancelEdit" 
                          class="btn-icon secondary"
                          title="Cancel"
                        >
                          <i class="fas fa-times"></i>
                        </button>
                      </template>
                      <template v-else>
                        <button 
                          @click="startEdit(empty)" 
                          class="btn-icon primary"
                          title="Edit"
                        >
                          <i class="fas fa-edit"></i>
                        </button>
                        <button 
                          @click="confirmDelete(empty)" 
                          class="btn-icon danger"
                          title="Unlink"
                        >
                          <i class="fas fa-unlink"></i>
                        </button>
                      </template>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Info Box -->
        <div class="info-box">
          <i class="fas fa-info-circle"></i>
          <div>
            <strong>How it works:</strong>
            <p>When you sell this product, the linked empties will be automatically included in the transaction. Customers pay a deposit for empties which is refunded when they return them.</p>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="modal-footer">
        <button @click="closeModal" class="btn-secondary">
          <i class="fas fa-times"></i>
          Close
        </button>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteConfirm" class="modal-overlay" @click.self="showDeleteConfirm = false">
      <div class="confirm-modal">
        <div class="confirm-header">
          <i class="fas fa-exclamation-triangle"></i>
          <h3>Confirm Unlink</h3>
        </div>
        <div class="confirm-body">
          <p>Are you sure you want to unlink <strong>{{ emptyToDelete?.name }}</strong>?</p>
          <p class="warning-text">This action cannot be undone.</p>
        </div>
        <div class="confirm-footer">
          <button @click="showDeleteConfirm = false" class="btn-secondary">Cancel</button>
          <button @click="deleteEmpty" class="btn-danger">
            <i class="fas fa-unlink"></i>
            Unlink Empty
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'EmptiesModal',
  props: {
    isOpen: {
      type: Boolean,
      default: false
    },
    product: {
      type: Object,
      default: null
    }
  },
  data() {
    return {
      empties: [],
      availableEmpties: [],
      isLoading: false,
      newEmpty: {
        empty_product_id: '',
        quantity: 1,
        deposit_amount: 0
      },
      editingEmpty: null,
      editForm: {
        quantity: 1,
        deposit_amount: 0,
        is_active: true
      },
      showDeleteConfirm: false,
      emptyToDelete: null
    }
  },
  computed: {
    canAddEmpty() {
      return this.newEmpty.empty_product_id && this.newEmpty.quantity > 0
    }
  },
  watch: {
    isOpen(val) {
      if (val && this.product) {
        this.loadEmpties()
        this.loadAvailableEmpties()
      }
    }
  },
  methods: {
    async loadEmpties() {
      if (!this.product?.id) return
      
      this.isLoading = true
      try {
        const response = await axios.get(`http://localhost:8000/api/products/${this.product.id}/empties`)
        this.empties = response.data.data || []
      } catch (error) {
        console.error('Error loading empties:', error)
        this.$emit('error', 'Failed to load empties')
      } finally {
        this.isLoading = false
      }
    },
    
    async loadAvailableEmpties() {
      if (!this.product?.id) return
      
      try {
        const response = await axios.get(`http://localhost:8000/api/products/${this.product.id}/available-empties`)
        this.availableEmpties = response.data.data || []
      } catch (error) {
        console.error('Error loading available empties:', error)
      }
    },
    
    async addEmpty() {
      if (!this.canAddEmpty) return
      
      this.isLoading = true
      try {
        await axios.post(`http://localhost:8000/api/products/${this.product.id}/empties`, this.newEmpty)
        await this.loadEmpties()
        this.resetNewEmptyForm()
        this.$emit('success', 'Empty linked successfully')
      } catch (error) {
        console.error('Error adding empty:', error)
        this.$emit('error', error.response?.data?.error || 'Failed to link empty')
      } finally {
        this.isLoading = false
      }
    },
    
    startEdit(empty) {
      this.editingEmpty = empty.id
      this.editForm = {
        quantity: empty.quantity,
        deposit_amount: empty.deposit_amount,
        is_active: empty.is_active
      }
    },
    
    async saveEdit(emptyId) {
      this.isLoading = true
      try {
        await axios.put(`http://localhost:8000/api/products/${this.product.id}/empties/${emptyId}`, this.editForm)
        await this.loadEmpties()
        this.cancelEdit()
        this.$emit('success', 'Empty updated successfully')
      } catch (error) {
        console.error('Error updating empty:', error)
        this.$emit('error', 'Failed to update empty')
      } finally {
        this.isLoading = false
      }
    },
    
    cancelEdit() {
      this.editingEmpty = null
      this.editForm = {
        quantity: 1,
        deposit_amount: 0,
        is_active: true
      }
    },
    
    confirmDelete(empty) {
      this.emptyToDelete = empty
      this.showDeleteConfirm = true
    },
    
    async deleteEmpty() {
      if (!this.emptyToDelete) return
      
      this.isLoading = true
      try {
        await axios.delete(`http://localhost:8000/api/products/${this.product.id}/empties/${this.emptyToDelete.id}`)
        await this.loadEmpties()
        this.showDeleteConfirm = false
        this.emptyToDelete = null
        this.$emit('success', 'Empty unlinked successfully')
      } catch (error) {
        console.error('Error deleting empty:', error)
        this.$emit('error', 'Failed to unlink empty')
      } finally {
        this.isLoading = false
      }
    },
    
    resetNewEmptyForm() {
      this.newEmpty = {
        empty_product_id: '',
        quantity: 1,
        deposit_amount: 0
      }
    },
    
    formatCurrency(amount) {
      return new Intl.NumberFormat('en-KE', {
        style: 'currency',
        currency: 'KES',
        minimumFractionDigits: 0
      }).format(amount || 0)
    },
    
    closeModal() {
      this.cancelEdit()
      this.resetNewEmptyForm()
      this.$emit('close')
    }
  }
}
</script>

<style scoped>
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
  z-index: 9999;
  backdrop-filter: blur(4px);
}

.empties-modal {
  background: white;
  border-radius: 16px;
  width: 90%;
  max-width: 1000px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
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
  padding: 1.5rem 2rem;
  border-bottom: 2px solid #f3f4f6;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 16px 16px 0 0;
}

.header-title {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.header-title i {
  font-size: 2rem;
}

.header-title h3 {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 700;
}

.product-name {
  margin: 0.25rem 0 0 0;
  font-size: 0.875rem;
  opacity: 0.9;
}

.close-btn {
  background: rgba(255, 255, 255, 0.2);
  border: none;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: white;
  transition: all 0.3s ease;
}

.close-btn:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: rotate(90deg);
}

.modal-body {
  flex: 1;
  overflow-y: auto;
  padding: 2rem;
}

.add-empty-section {
  background: #f8fafc;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 2rem;
}

.add-empty-section h4 {
  margin: 0 0 1rem 0;
  color: #1f2937;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.add-empty-section h4 i {
  color: #10b981;
}

.form-row {
  display: flex;
  gap: 1rem;
  align-items: flex-end;
}

.form-group {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group.flex-2 {
  flex: 2;
}

.form-group label {
  font-weight: 600;
  color: #374151;
  font-size: 0.875rem;
}

.form-group input,
.form-group select {
  padding: 0.75rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 1rem;
  transition: all 0.3s ease;
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-group input:disabled,
.form-group select:disabled {
  background: #f3f4f6;
  cursor: not-allowed;
}

.form-group-btn {
  display: flex;
  align-items: flex-end;
}

.btn-add {
  background: #10b981;
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
  white-space: nowrap;
}

.btn-add:hover:not(:disabled) {
  background: #059669;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.btn-add:disabled {
  background: #d1d5db;
  cursor: not-allowed;
  transform: none;
}

.empties-list-section {
  margin-bottom: 1.5rem;
}

.empties-list-section h4 {
  margin: 0 0 1rem 0;
  color: #1f2937;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.loading-state,
.empty-state {
  text-align: center;
  padding: 3rem 2rem;
  color: #6b7280;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #e5e7eb;
  border-top: 3px solid #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty-state i {
  font-size: 3rem;
  color: #d1d5db;
  margin-bottom: 1rem;
}

.empty-state small {
  display: block;
  margin-top: 0.5rem;
  color: #9ca3af;
}

.empties-table {
  overflow-x: auto;
}

.empties-table table {
  width: 100%;
  border-collapse: collapse;
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.empties-table thead {
  background: #f8fafc;
}

.empties-table th {
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  color: #374151;
  font-size: 0.875rem;
  border-bottom: 2px solid #e5e7eb;
}

.empties-table td {
  padding: 1rem;
  border-bottom: 1px solid #f3f4f6;
}

.empties-table tr:last-child td {
  border-bottom: none;
}

.empties-table tr:hover {
  background: #f8fafc;
}

.empties-table tr.inactive {
  opacity: 0.6;
}

.product-cell strong {
  color: #1f2937;
}

.sku-badge {
  background: #e0e7ff;
  color: #4338ca;
  padding: 0.25rem 0.75rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 600;
}

.quantity-display {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.qty-number {
  font-weight: 700;
  color: #059669;
  font-size: 1.125rem;
}

.qty-label {
  font-size: 0.75rem;
  color: #6b7280;
}

.deposit-amount {
  font-weight: 600;
  color: #1f2937;
}

.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
}

.status-badge.active {
  background: #dcfce7;
  color: #166534;
}

.status-badge.inactive {
  background: #fee2e2;
  color: #991b1b;
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
}

.btn-icon {
  border: none;
  border-radius: 6px;
  padding: 0.5rem;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
}

.btn-icon.primary {
  background: #dbeafe;
  color: #1e40af;
}

.btn-icon.primary:hover {
  background: #bfdbfe;
}

.btn-icon.success {
  background: #dcfce7;
  color: #166534;
}

.btn-icon.success:hover {
  background: #bbf7d0;
}

.btn-icon.secondary {
  background: #f3f4f6;
  color: #6b7280;
}

.btn-icon.secondary:hover {
  background: #e5e7eb;
}

.btn-icon.danger {
  background: #fee2e2;
  color: #dc2626;
}

.btn-icon.danger:hover {
  background: #fecaca;
}

.inline-edit .mini-input {
  width: 80px;
  padding: 0.5rem;
  border: 2px solid #667eea;
  border-radius: 6px;
  font-size: 0.875rem;
}

.info-box {
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 8px;
  padding: 1rem;
  display: flex;
  gap: 1rem;
  align-items: flex-start;
}

.info-box i {
  color: #3b82f6;
  font-size: 1.25rem;
  flex-shrink: 0;
}

.info-box strong {
  color: #1e40af;
  display: block;
  margin-bottom: 0.25rem;
}

.info-box p {
  margin: 0;
  color: #1e40af;
  font-size: 0.875rem;
  line-height: 1.5;
}

.modal-footer {
  padding: 1.5rem 2rem;
  border-top: 2px solid #f3f4f6;
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
}

.btn-secondary {
  background: #f3f4f6;
  color: #374151;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
}

.btn-secondary:hover {
  background: #e5e7eb;
}

.confirm-modal {
  background: white;
  border-radius: 16px;
  width: 90%;
  max-width: 450px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

.confirm-header {
  padding: 1.5rem;
  border-bottom: 1px solid #f3f4f6;
  display: flex;
  align-items: center;
  gap: 1rem;
  background: #fef2f2;
  border-radius: 16px 16px 0 0;
}

.confirm-header i {
  color: #ef4444;
  font-size: 1.5rem;
}

.confirm-header h3 {
  margin: 0;
  color: #991b1b;
}

.confirm-body {
  padding: 1.5rem;
}

.confirm-body p {
  margin: 0 0 0.5rem 0;
  color: #374151;
}

.warning-text {
  color: #dc2626;
  font-weight: 600;
}

.confirm-footer {
  padding: 1.5rem;
  border-top: 1px solid #f3f4f6;
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
}

.btn-danger {
  background: #ef4444;
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
}

.btn-danger:hover {
  background: #dc2626;
}

@media (max-width: 768px) {
  .empties-modal {
    width: 95%;
    max-height: 95vh;
  }

  .modal-header,
  .modal-body,
  .modal-footer {
    padding: 1rem;
  }

  .form-row {
    flex-direction: column;
    align-items: stretch;
  }

  .form-group-btn {
    align-items: stretch;
  }

  .btn-add {
    width: 100%;
    justify-content: center;
  }

  .empties-table {
    font-size: 0.875rem;
  }

  .empties-table th,
  .empties-table td {
    padding: 0.75rem 0.5rem;
  }
}
</style>
