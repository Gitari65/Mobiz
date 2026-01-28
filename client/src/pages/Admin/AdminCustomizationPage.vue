<template>
  <div class="admin-customization">
    <!-- Alert Messages -->
    <div v-if="alert.show" :class="['alert', alert.type]">
      {{ alert.message }}
    </div>

    <div class="tabs">
      <button v-for="tab in tabs" :key="tab" :class="['tab-btn', { active: currentTab === tab }]" @click="currentTab = tab">
        {{ tab }}
      </button>
    </div>
    <div class="tab-content">
      <div v-if="currentTab === 'Product Categories'">
        <h2>Product Categories <span class="count">({{ categories.length }})</span></h2>
        <form class="custom-form" @submit.prevent="addCategory">
          <input v-model="newCategory" placeholder="New category name" required />
          <button type="submit" class="primary-btn" :disabled="loading.categories">
            <span v-if="loading.categories" class="spinner"></span>
            <span v-else>Add</span>
          </button>
        </form>
        <div class="csv-upload">
          <label>Bulk Upload (CSV):</label>
          <input class="file-input" type="file" @change="handleBulkUpload" accept=".csv" :disabled="loading.categories" />
          <a :href="sampleCsvUrl" download="sample_categories.csv" class="sample-link">Download Sample CSV</a>
        </div>
        <div v-if="loading.categories" class="loading-container">
          <div class="spinner large"></div>
          <p>Loading categories...</p>
        </div>
        <ul v-else class="item-list">
          <li v-for="cat in categories" :key="cat.id">
            <span>{{ cat.name }}</span>
            <button class="delete-btn" @click="deleteCategory(cat.id)"><i class="fas fa-trash"></i></button>
          </li>
        </ul>
      </div>
      <div v-else-if="currentTab === 'Warehouses'">
        <h2>Warehouses <span class="count">({{ warehouses.length }})</span></h2>
        <form class="custom-form" @submit.prevent="editingWarehouse ? updateWarehouse() : addWarehouse()">
          <input v-model="newWarehouse.name" placeholder="Warehouse name" required />
          <input v-model="newWarehouse.type" placeholder="Type (main, breakages, etc.)" />
          <button type="submit" class="primary-btn" :disabled="loading.warehouses">
            <span v-if="loading.warehouses" class="spinner"></span>
            <span v-else>{{ editingWarehouse ? 'Update' : 'Add' }}</span>
          </button>
          <button v-if="editingWarehouse" type="button" class="cancel-btn" @click="cancelEdit">Cancel</button>
        </form>
        <div v-if="loading.warehouses" class="loading-container">
          <div class="spinner large"></div>
          <p>Loading warehouses...</p>
        </div>
        <div v-else-if="warehouses.length === 0" class="empty-state">
          <i class="fas fa-warehouse"></i>
          <p>No warehouses found</p>
          <small>Add your first warehouse above</small>
        </div>
        <div v-else class="table-wrapper">
          <table class="warehouse-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Type</th>
                <th>Created</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="wh in warehouses" :key="wh.id">
                <td class="name-cell">{{ wh.name }}</td>
                <td><span class="type-badge">{{ wh.type || 'General' }}</span></td>
                <td class="date-cell">{{ formatDate(wh.created_at) }}</td>
                <td class="actions-cell">
                  <button class="edit-btn" @click="editWarehouse(wh)" title="Edit">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="delete-btn" @click="deleteWarehouse(wh.id)" title="Delete">
                    <i class="fas fa-trash"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div v-else-if="currentTab === 'Payment Methods'">
        <div class="tab-header">
          <div>
            <h2>Payment Methods <span class="count">({{ paymentMethods.filter(pm => pm.is_enabled).length }} / {{ paymentMethods.length }} enabled)</span></h2>
            <p class="info-text">Select which payment methods your business accepts</p>
          </div>
          <button 
            v-if="hasPaymentChanges" 
            @click="savePaymentChanges" 
            class="save-btn"
            :disabled="loading.paymentMethods"
          >
            <i class="fas fa-save"></i>
            Save Changes
          </button>
        </div>
        
        <div v-if="loading.paymentMethods" class="loading-container">
          <div class="spinner large"></div>
          <p>Loading payment methods...</p>
        </div>
        <div v-else class="payment-grid">
          <div 
            v-for="pm in paymentMethods" 
            :key="pm.id" 
            class="payment-card"
            :class="{ 
              enabled: pm.is_enabled,
              changed: hasChanged(pm.id)
            }"
            @click="togglePaymentMethodLocal(pm.id)"
          >
            <div v-if="hasChanged(pm.id)" class="change-indicator">
              <i class="fas fa-circle"></i>
            </div>
            <div class="payment-icon">
              <i :class="getPaymentIcon(pm.name)"></i>
            </div>
            <div class="payment-info">
              <h3 class="payment-name">{{ pm.name }}</h3>
              <p class="payment-desc">{{ pm.description }}</p>
            </div>
            <div class="payment-toggle">
              <div class="toggle-switch" :class="{ active: pm.is_enabled }">
                <div class="toggle-slider"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

const tabs = ['Product Categories', 'Warehouses', 'Payment Methods'];
const currentTab = ref(tabs[0]);

const categories = ref([]);
const newCategory = ref('');
const warehouses = ref([]);
const newWarehouse = ref({ name: '', type: '' });
const paymentMethods = ref([]);
const originalPaymentStates = ref({});
const hasPaymentChanges = ref(false);
const editingWarehouse = ref(null);
const currentUser = ref(null);

const loading = ref({
  categories: false,
  warehouses: false,
  paymentMethods: false
});

const alert = ref({
  show: false,
  type: 'success',
  message: ''
});

const sampleCsvUrl = '/sample_categories.csv';

const showAlert = (message, type = 'success') => {
  alert.value = { show: true, message, type };
  setTimeout(() => {
    alert.value.show = false;
  }, 3000);
};

const fetchCategories = async () => {
  loading.value.categories = true;
  try {
    const res = await axios.get('/business-categories');
    categories.value = res.data;
  } catch (error) {
    showAlert('Failed to load categories', 'error');
  } finally {
    loading.value.categories = false;
  }
};

const addCategory = async () => {
  loading.value.categories = true;
  try {
    await axios.post('/business-categories', { name: newCategory.value });
    newCategory.value = '';
    await fetchCategories();
    showAlert('Category added successfully');
  } catch (error) {
    showAlert('Failed to add category', 'error');
  } finally {
    loading.value.categories = false;
  }
};

const deleteCategory = async (id) => {
  if (!confirm('Are you sure you want to delete this category?')) return;
  loading.value.categories = true;
  try {
    await axios.delete(`/business-categories/${id}`);
    await fetchCategories();
    showAlert('Category deleted successfully');
  } catch (error) {
    showAlert('Failed to delete category', 'error');
  } finally {
    loading.value.categories = false;
  }
};

const handleBulkUpload = async (e) => {
  const file = e.target.files[0];
  if (!file) return;
  loading.value.categories = true;
  try {
    const formData = new FormData();
    formData.append('csv_file', file);
    await axios.post('/business-categories/import-csv', formData);
    await fetchCategories();
    showAlert('Categories imported successfully');
  } catch (error) {
    showAlert('Failed to import categories', 'error');
  } finally {
    loading.value.categories = false;
    e.target.value = '';
  }
};

const fetchWarehouses = async () => {
  loading.value.warehouses = true;
  try {
    const res = await axios.get('/warehouses');
    // Handle both direct array and wrapped response
    warehouses.value = res.data.warehouses || res.data;
  } catch (error) {
    showAlert('Failed to load warehouses', 'error');
  } finally {
    loading.value.warehouses = false;
  }
};

const formatDate = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};

const fetchCurrentUser = async () => {
  try {
    const res = await axios.get('/api/user');
    // Handle the user response - it could be wrapped or direct
    const userData = res.data.data || res.data;
    currentUser.value = userData;
    console.log('Current user:', currentUser.value);
  } catch (error) {
    console.error('Failed to fetch user:', error);
    // Fallback: try to get company_id from localStorage
    const storedUser = localStorage.getItem('user');
    if (storedUser) {
      try {
        currentUser.value = JSON.parse(storedUser);
      } catch (e) {
        console.error('Failed to parse stored user:', e);
      }
    }
  }
};

const addWarehouse = async () => {
  if (!currentUser.value) {
    showAlert('User not loaded. Please refresh the page.', 'error');
    return;
  }

  if (!currentUser.value.company_id) {
    showAlert(`Company ID not found. User company_id: ${currentUser.value.company_id}`, 'error');
    return;
  }
  
  loading.value.warehouses = true;
  try {
    console.log('Creating warehouse with company_id:', currentUser.value.company_id);
    await axios.post('/warehouses', {
      name: newWarehouse.value.name,
      type: newWarehouse.value.type,
      company_id: currentUser.value.company_id
    });
    newWarehouse.value = { name: '', type: '' };
    await fetchWarehouses();
    showAlert('Warehouse added successfully');
  } catch (error) {
    console.error('Warehouse creation error:', error);
    showAlert(error.response?.data?.message || 'Failed to add warehouse', 'error');
  } finally {
    loading.value.warehouses = false;
  }
};

const editWarehouse = (warehouse) => {
  editingWarehouse.value = warehouse.id;
  newWarehouse.value = { name: warehouse.name, type: warehouse.type };
};

const updateWarehouse = async () => {
  if (!confirm('Are you sure you want to update this warehouse?')) return;
  loading.value.warehouses = true;
  try {
    await axios.put(`/warehouses/${editingWarehouse.value}`, newWarehouse.value);
    newWarehouse.value = { name: '', type: '' };
    editingWarehouse.value = null;
    await fetchWarehouses();
    showAlert('Warehouse updated successfully');
  } catch (error) {
    showAlert(error.response?.data?.message || 'Failed to update warehouse', 'error');
  } finally {
    loading.value.warehouses = false;
  }
};

const cancelEdit = () => {
  editingWarehouse.value = null;
  newWarehouse.value = { name: '', type: '' };
};

const deleteWarehouse = async (id) => {
  if (!confirm('Are you sure you want to delete this warehouse? This action cannot be undone.')) return;
  loading.value.warehouses = true;
  try {
    await axios.delete(`/warehouses/${id}`);
    await fetchWarehouses();
    showAlert('Warehouse deleted successfully');
  } catch (error) {
    showAlert(error.response?.data?.message || 'Failed to delete warehouse', 'error');
  } finally {
    loading.value.warehouses = false;
  }
};

const fetchPaymentMethods = async () => {
  loading.value.paymentMethods = true;
  try {
    const res = await axios.get('/payment-methods');
    paymentMethods.value = res.data;
    // Store original states
    originalPaymentStates.value = {};
    res.data.forEach(pm => {
      originalPaymentStates.value[pm.id] = pm.is_enabled;
    });
    hasPaymentChanges.value = false;
  } catch (error) {
    console.error('Payment methods error:', error);
    showAlert('Failed to load payment methods', 'error');
    paymentMethods.value = [];
  } finally {
    loading.value.paymentMethods = false;
  }
};

const togglePaymentMethodLocal = (id) => {
  const method = paymentMethods.value.find(pm => pm.id === id);
  if (method) {
    method.is_enabled = !method.is_enabled;
    checkForChanges();
  }
};

const hasChanged = (id) => {
  const method = paymentMethods.value.find(pm => pm.id === id);
  return method && originalPaymentStates.value[id] !== method.is_enabled;
};

const checkForChanges = () => {
  hasPaymentChanges.value = paymentMethods.value.some(pm => 
    originalPaymentStates.value[pm.id] !== pm.is_enabled
  );
};

const savePaymentChanges = async () => {
  loading.value.paymentMethods = true;
  try {
    const changedMethods = paymentMethods.value.filter(pm => 
      originalPaymentStates.value[pm.id] !== pm.is_enabled
    );

    let savedCount = 0;
    for (const method of changedMethods) {
      try {
        await axios.post(`/payment-methods/${method.id}/toggle`);
        originalPaymentStates.value[method.id] = method.is_enabled;
        savedCount++;
      } catch (error) {
        console.error(`Failed to toggle ${method.name}:`, error);
        // Revert this specific method on error
        method.is_enabled = originalPaymentStates.value[method.id];
      }
    }

    hasPaymentChanges.value = false;
    
    if (savedCount === changedMethods.length) {
      showAlert(`Successfully updated ${savedCount} payment method${savedCount !== 1 ? 's' : ''}`);
    } else {
      showAlert(`Updated ${savedCount} of ${changedMethods.length} payment methods`, 'warning');
    }
  } catch (error) {
    showAlert('Failed to save changes', 'error');
  } finally {
    loading.value.paymentMethods = false;
  }
};

const getPaymentIcon = (name) => {
  const icons = {
    'Cash': 'fas fa-money-bill-wave',
    'M-Pesa': 'fas fa-mobile-alt',
    'Bank Transfer': 'fas fa-university',
    'Credit Card': 'fas fa-credit-card',
    'Debit Card': 'far fa-credit-card',
    'Cheque': 'fas fa-money-check',
    'Credit/Invoice': 'fas fa-file-invoice-dollar',
    'PayPal': 'fab fa-paypal'
  }
  return icons[name] || 'fas fa-money-bill'
}

onMounted(() => {
  fetchCurrentUser();
  fetchCategories();
  fetchWarehouses();
  fetchPaymentMethods();
});
</script>

<style scoped>
.admin-customization {
  padding: 2rem 0;
}

.alert {
  padding: 1rem 1.5rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
  font-weight: 500;
  animation: slideDown 0.3s ease;
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

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
.tabs {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
}
.tab-btn {
  background: #f3f4f6;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px 8px 0 0;
  font-weight: 600;
  cursor: pointer;
  color: #374151;
  transition: background 0.2s, color 0.2s;
}
.tab-btn.active {
  background: #fff;
  color: #3b82f6;
  border-bottom: 2px solid #3b82f6;
}
.tab-content {
  background: #fff;
  border-radius: 0 0 16px 16px;
  box-shadow: 0 2px 16px rgba(0,0,0,0.07);
  padding: 2rem 1.5rem 1.5rem 1.5rem;
}
.custom-form {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1rem;
}
.custom-form input,
.custom-form select {
  flex: 1;
  padding: 0.5rem 0.75rem;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  font-size: 1rem;
  background: #f9fafb;
  transition: border 0.2s;
}
.custom-form input:focus,
.custom-form select:focus {
  border-color: #3b82f6;
  outline: none;
}
.primary-btn {
  background: #3b82f6;
  color: #fff;
  border: none;
  border-radius: 6px;
  padding: 0.5rem 1.25rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}
.primary-btn:disabled {
  background: #9ca3af;
  cursor: not-allowed;
}
.primary-btn:hover {
  background: #2563eb;
}
.file-input {
  margin-bottom: 1rem;
}
.sample-link {
  display: inline-block;
  margin-left: 1rem;
  color: #3b82f6;
  text-decoration: underline;
  font-size: 0.95rem;
}
.item-list {
  list-style: none;
  padding: 0;
  margin: 0;
}
.item-list li {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.5rem 0;
  border-bottom: 1px solid #f1f1f1;
}
.item-list li:last-child {
  border-bottom: none;
}
.delete-btn {
  background: none;
  border: none;
  color: #ef4444;
  font-size: 1.1rem;
  cursor: pointer;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  transition: background 0.15s;
}
.delete-btn:hover {
  background: #fee2e2;
}
.csv-upload {
  margin-bottom: 1rem;
}
.count {
  color: #6b7280;
  font-size: 0.9rem;
  font-weight: 400;
}
.spinner {
  display: inline-block;
  width: 14px;
  height: 14px;
  border: 2px solid #ffffff;
  border-top-color: transparent;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}
.spinner.large {
  width: 40px;
  height: 40px;
  border-width: 4px;
  border-color: #3b82f6;
  border-top-color: transparent;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  color: #6b7280;
}
.loading-container p {
  margin-top: 1rem;
  font-size: 0.95rem;
}
.type-badge {
  display: inline-block;
  background: #e0e7ff;
  color: #4f46e5;
  padding: 0.2rem 0.6rem;
  border-radius: 12px;
  font-size: 0.8rem;
  font-weight: 600;
  margin-left: 0.5rem;
}
.empty-state {
  text-align: center;
  padding: 3rem 1rem;
  color: #9ca3af;
}

.empty-state i {
  font-size: 3rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.empty-state p {
  font-size: 1.1rem;
  font-weight: 500;
  margin-bottom: 0.5rem;
  color: #6b7280;
}

.empty-state small {
  font-size: 0.9rem;
  color: #9ca3af;
}

.warehouse-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.warehouse-name {
  font-weight: 500;
  color: #1f2937;
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
}

.edit-btn {
  background: #dbeafe;
  border: none;
  color: #2563eb;
  font-size: 0.9rem;
  cursor: pointer;
  padding: 0.4rem 0.6rem;
  border-radius: 4px;
  transition: background 0.15s;
}

.edit-btn:hover {
  background: #bfdbfe;
}

.cancel-btn {
  background: #e5e7eb;
  color: #374151;
  border: none;
  border-radius: 6px;
  padding: 0.5rem 1.25rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.cancel-btn:hover {
  background: #d1d5db;
}

.table-wrapper {
  overflow-x: auto;
  margin-top: 1.5rem;
}

.warehouse-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.95rem;
}

.warehouse-table thead {
  background: #f3f4f6;
}

.warehouse-table th {
  padding: 0.75rem 1rem;
  text-align: left;
  font-weight: 600;
  color: #374151;
  border-bottom: 2px solid #e5e7eb;
}

.warehouse-table td {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #e5e7eb;
  color: #1f2937;
}

.warehouse-table tbody tr:hover {
  background: #f9fafb;
}

.warehouse-table tbody tr:last-child td {
  border-bottom: none;
}

.name-cell {
  font-weight: 500;
}

.date-cell {
  font-size: 0.9rem;
  color: #6b7280;
}

.actions-cell {
  display: flex;
  gap: 0.5rem;
}

.info-text {
  color: #6b7280;
  font-size: 0.95rem;
  margin-bottom: 2rem;
}

.payment-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1.5rem;
  margin-top: 1.5rem;
}

.payment-card {
  background: #f9fafb;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  padding: 1.5rem;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  position: relative;
  overflow: hidden;
}

.payment-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
}

.payment-card.enabled {
  background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
  border-color: #10b981;
}

.payment-card.enabled::before {
  content: '';
  position: absolute;
  top: 0;
  right: 0;
  width: 0;
  height: 0;
  border-style: solid;
  border-width: 0 60px 60px 0;
  border-color: transparent #10b981 transparent transparent;
}

.payment-card.enabled::after {
  content: '✓';
  position: absolute;
  top: 8px;
  right: 8px;
  color: white;
  font-size: 18px;
  font-weight: bold;
  z-index: 1;
}

.payment-icon {
  width: 56px;
  height: 56px;
  border-radius: 12px;
  background: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  color: #3b82f6;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease;
}

.payment-card.enabled .payment-icon {
  background: #10b981;
  color: white;
}

.payment-card:hover .payment-icon {
  transform: scale(1.1);
}

.payment-info {
  flex: 1;
}

.payment-name {
  font-size: 1.1rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0 0 0.5rem 0;
}

.payment-desc {
  font-size: 0.875rem;
  color: #6b7280;
  margin: 0;
  line-height: 1.4;
}

.payment-toggle {
  display: flex;
  justify-content: flex-end;
}

.toggle-switch {
  width: 52px;
  height: 28px;
  background: #d1d5db;
  border-radius: 14px;
  position: relative;
  transition: background 0.3s ease;
}

.toggle-switch.active {
  background: #10b981;
}

.toggle-slider {
  position: absolute;
  top: 3px;
  left: 3px;
  width: 22px;
  height: 22px;
  background: white;
  border-radius: 50%;
  transition: transform 0.3s ease;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.toggle-switch.active .toggle-slider {
  transform: translateX(24px);
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .tab-header {
    flex-direction: column;
    align-items: stretch;
  }

  .save-btn {
    width: 100%;
    justify-content: center;
  }
}
</style>
