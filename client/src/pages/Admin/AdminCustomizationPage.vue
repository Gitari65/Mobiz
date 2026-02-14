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
      <div v-else-if="currentTab === 'Tax Configuration'">
        <div class="tab-header">
          <div>
            <h2>Tax Configuration <span class="count">({{ taxConfigs.length }} tax types)</span></h2>
            <p class="info-text">Manage tax rates and settings for your business</p>
          </div>
        </div>

        <form class="custom-form tax-form" @submit.prevent="editingTaxConfig ? updateTaxConfig() : addTaxConfig()">
          <input v-model="newTaxConfig.name" placeholder="Tax name (e.g., VAT, Sales Tax)" required />
          <input v-model.number="newTaxConfig.rate" type="number" step="0.01" min="0" max="100" placeholder="Rate %" required />
          <select v-model="newTaxConfig.is_inclusive" required>
            <option :value="true">Inclusive</option>
            <option :value="false">Exclusive</option>
          </select>
          <button type="submit" class="primary-btn" :disabled="loading.taxConfigs">
            <span v-if="loading.taxConfigs" class="spinner"></span>
            <span v-else>{{ editingTaxConfig ? 'Update' : 'Add Tax' }}</span>
          </button>
          <button v-if="editingTaxConfig" type="button" class="cancel-btn" @click="cancelTaxEdit">Cancel</button>
        </form>

        <div v-if="loading.taxConfigs" class="loading-container">
          <div class="spinner large"></div>
          <p>Loading tax configurations...</p>
        </div>
        <div v-else-if="taxConfigs.length === 0" class="empty-state">
          <i class="fas fa-percentage"></i>
          <p>No tax configurations found</p>
          <small>Add your first tax configuration above</small>
        </div>
        <div v-else class="tax-cards">
          <div v-for="tax in taxConfigs" :key="tax.id" class="tax-card" :class="{ default: tax.is_default }">
            <div class="tax-card-header">
              <div class="tax-icon">
                <i class="fas fa-percentage"></i>
              </div>
              <div class="tax-info">
                <h3>{{ tax.name }}</h3>
                <p class="tax-rate">{{ tax.rate }}% {{ tax.is_inclusive ? 'Inclusive' : 'Exclusive' }}</p>
              </div>
              <div v-if="tax.is_default" class="default-badge">Default</div>
            </div>
            <div class="tax-card-actions">
              <button class="set-default-btn" @click="setDefaultTax(tax.id)" :disabled="tax.is_default">
                <i class="fas fa-star"></i>
                {{ tax.is_default ? 'Default' : 'Set as Default' }}
              </button>
              <button class="edit-btn" @click="editTaxConfig(tax)">
                <i class="fas fa-edit"></i>
              </button>
              <button class="delete-btn" @click="deleteTaxConfig(tax.id)" :disabled="tax.is_default">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      <div v-else-if="currentTab === 'Printer Settings'">
        <div class="tab-header">
          <div>
            <h2>Receipt Printer Settings</h2>
            <p class="info-text">Configure how receipts are printed</p>
          </div>
          <button 
            v-if="hasPrinterChanges" 
            @click="savePrinterSettings" 
            class="save-btn"
            :disabled="loading.printerSettings"
          >
            <i class="fas fa-save"></i>
            Save Changes
          </button>
        </div>

        <div v-if="loading.printerSettings" class="loading-container">
          <div class="spinner large"></div>
          <p>Loading printer settings...</p>
        </div>
        <div v-else class="printer-settings-form">
          <div class="form-section">
            <h3><i class="fas fa-align-left"></i> Receipt Header</h3>
            <p class="section-info">This text appears at the top of every receipt</p>
            <textarea 
              v-model="printerSettings.header_message" 
              placeholder="Business Name&#10;Address Line 1&#10;Address Line 2&#10;Phone Number"
              rows="4"
              @input="checkPrinterChanges"
            ></textarea>
          </div>

          <div class="form-section">
            <h3><i class="fas fa-align-right"></i> Receipt Footer</h3>
            <p class="section-info">This text appears at the bottom of every receipt</p>
            <textarea 
              v-model="printerSettings.footer_message" 
              placeholder="Thank you for your business!&#10;Return policy: 7 days with receipt&#10;Visit us again!"
              rows="4"
              @input="checkPrinterChanges"
            ></textarea>
          </div>

          <div class="form-section">
            <h3><i class="fas fa-cog"></i> Display Options</h3>
            <div class="checkbox-group">
              <label class="checkbox-label">
                <input type="checkbox" v-model="printerSettings.show_logo" @change="checkPrinterChanges" />
                <span>Show business logo on receipt</span>
              </label>
              <label class="checkbox-label">
                <input type="checkbox" v-model="printerSettings.show_taxes" @change="checkPrinterChanges" />
                <span>Show tax breakdown on receipt</span>
              </label>
              <label class="checkbox-label">
                <input type="checkbox" v-model="printerSettings.show_discounts" @change="checkPrinterChanges" />
                <span>Show discounts on receipt</span>
              </label>
            </div>
          </div>

          <div class="form-section">
            <h3><i class="fas fa-print"></i> Print Settings</h3>
            <div class="form-row">
              <div class="form-group">
                <label>Paper Size</label>
                <select v-model="printerSettings.paper_size" @change="checkPrinterChanges">
                  <option value="58mm">58mm (Thermal)</option>
                  <option value="80mm">80mm (Thermal)</option>
                  <option value="A4">A4 (Standard)</option>
                </select>
              </div>
              <div class="form-group">
                <label>Text Alignment</label>
                <select v-model="printerSettings.alignment" @change="checkPrinterChanges">
                  <option value="left">Left</option>
                  <option value="center">Center</option>
                  <option value="right">Right</option>
                </select>
              </div>
            </div>
          </div>

          <div class="preview-section">
            <h3><i class="fas fa-eye"></i> Receipt Preview</h3>
            <div class="receipt-preview">
              <div class="receipt-header">
                <div v-if="printerSettings.header_message" v-html="formatPreviewText(printerSettings.header_message)"></div>
                <div v-else class="preview-placeholder">Header will appear here</div>
              </div>
              <div class="receipt-divider">═══════════════════════</div>
              <div class="receipt-body">
                <p><strong>Receipt #:</strong> 12345</p>
                <p><strong>Date:</strong> {{ new Date().toLocaleDateString() }}</p>
                <div class="receipt-divider-thin">───────────────────────</div>
                <p>Sample Item 1 - Ksh 500.00</p>
                <p>Sample Item 2 - Ksh 300.00</p>
                <div class="receipt-divider-thin">───────────────────────</div>
                <p><strong>Subtotal:</strong> Ksh 800.00</p>
                <p v-if="printerSettings.show_discounts"><strong>Discount:</strong> - Ksh 50.00</p>
                <p v-if="printerSettings.show_taxes"><strong>VAT (16%):</strong> + Ksh 120.00</p>
                <p><strong>Total:</strong> Ksh 870.00</p>
              </div>
              <div class="receipt-divider">═══════════════════════</div>
              <div class="receipt-footer">
                <div v-if="printerSettings.footer_message" v-html="formatPreviewText(printerSettings.footer_message)"></div>
                <div v-else class="preview-placeholder">Footer will appear here</div>
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

const tabs = ['Product Categories', 'Warehouses', 'Payment Methods', 'Tax Configuration', 'Printer Settings'];
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

// Tax Configuration state
const taxConfigs = ref([]);
const newTaxConfig = ref({ name: '', rate: 0, is_inclusive: true });
const editingTaxConfig = ref(null);

// Printer Settings state
const printerSettings = ref({
  header_message: '',
  footer_message: '',
  show_logo: true,
  show_taxes: true,
  show_discounts: true,
  paper_size: '58mm',
  alignment: 'center'
});
const originalPrinterSettings = ref({});
const hasPrinterChanges = ref(false);

const loading = ref({
  categories: false,
  warehouses: false,
  paymentMethods: false,
  taxConfigs: false,
  printerSettings: false
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

const fetchTaxConfigs = async () => {
  loading.value.taxConfigs = true;
  try {
    const res = await axios.get('/api/tax-configurations');
    taxConfigs.value = Array.isArray(res.data) ? res.data : [];
  } catch (error) {
    console.error('Tax configs error:', error);
    showAlert('Failed to load tax configurations', 'error');
    taxConfigs.value = [];
  } finally {
    loading.value.taxConfigs = false;
  }
};

const addTaxConfig = async () => {
  loading.value.taxConfigs = true;
  try {
    await axios.post('/api/tax-configurations', newTaxConfig.value);
    newTaxConfig.value = { name: '', rate: 0, is_inclusive: true };
    await fetchTaxConfigs();
    showAlert('Tax configuration added successfully');
  } catch (error) {
    showAlert(error.response?.data?.message || 'Failed to add tax configuration', 'error');
  } finally {
    loading.value.taxConfigs = false;
  }
};

const editTaxConfig = (tax) => {
  editingTaxConfig.value = tax.id;
  newTaxConfig.value = { 
    name: tax.name, 
    rate: tax.rate, 
    is_inclusive: tax.is_inclusive 
  };
};

const updateTaxConfig = async () => {
  if (!confirm('Are you sure you want to update this tax configuration?')) return;
  loading.value.taxConfigs = true;
  try {
    await axios.put(`/api/tax-configurations/${editingTaxConfig.value}`, newTaxConfig.value);
    newTaxConfig.value = { name: '', rate: 0, is_inclusive: true };
    editingTaxConfig.value = null;
    await fetchTaxConfigs();
    showAlert('Tax configuration updated successfully');
  } catch (error) {
    showAlert(error.response?.data?.message || 'Failed to update tax configuration', 'error');
  } finally {
    loading.value.taxConfigs = false;
  }
};

const cancelTaxEdit = () => {
  editingTaxConfig.value = null;
  newTaxConfig.value = { name: '', rate: 0, is_inclusive: true };
};

const deleteTaxConfig = async (id) => {
  if (!confirm('Are you sure you want to delete this tax configuration?')) return;
  loading.value.taxConfigs = true;
  try {
    await axios.delete(`/api/tax-configurations/${id}`);
    await fetchTaxConfigs();
    showAlert('Tax configuration deleted successfully');
  } catch (error) {
    showAlert(error.response?.data?.message || 'Failed to delete tax configuration', 'error');
  } finally {
    loading.value.taxConfigs = false;
  }
};

const setDefaultTax = async (id) => {
  loading.value.taxConfigs = true;
  try {
    await axios.post(`/api/tax-configurations/${id}/set-default`);
    await fetchTaxConfigs();
    showAlert('Default tax configuration updated');
  } catch (error) {
    showAlert(error.response?.data?.message || 'Failed to set default tax', 'error');
  } finally {
    loading.value.taxConfigs = false;
  }
};

const fetchPrinterSettings = async () => {
  loading.value.printerSettings = true;
  try {
    const res = await axios.get('/api/printer-settings');
    // Initialize with defaults if no settings exist
    printerSettings.value = {
      header_message: res.data.header_message || '',
      footer_message: res.data.footer_message || '',
      show_logo: res.data.show_logo !== undefined ? res.data.show_logo : true,
      show_taxes: res.data.show_taxes !== undefined ? res.data.show_taxes : true,
      show_discounts: res.data.show_discounts !== undefined ? res.data.show_discounts : true,
      paper_size: res.data.paper_size || '58mm',
      alignment: res.data.alignment || 'center'
    };
    originalPrinterSettings.value = JSON.parse(JSON.stringify(printerSettings.value));
    hasPrinterChanges.value = false;
  } catch (error) {
    console.error('Printer settings error:', error);
    // If settings don't exist, keep defaults
    if (error.response?.status === 404) {
      showAlert('No printer settings found. Using defaults. Save to create configuration.', 'info');
    } else {
      showAlert('Failed to load printer settings', 'error');
    }
  } finally {
    loading.value.printerSettings = false;
  }
};

const checkPrinterChanges = () => {
  hasPrinterChanges.value = JSON.stringify(printerSettings.value) !== JSON.stringify(originalPrinterSettings.value);
};

const savePrinterSettings = async () => {
  loading.value.printerSettings = true;
  try {
    // Use PUT instead of POST to update existing settings
    await axios.put('/api/printer-settings', printerSettings.value);
    originalPrinterSettings.value = JSON.parse(JSON.stringify(printerSettings.value));
    hasPrinterChanges.value = false;
    showAlert('Printer settings saved successfully');
  } catch (error) {
    showAlert(error.response?.data?.message || 'Failed to save printer settings', 'error');
  } finally {
    loading.value.printerSettings = false;
  }
};

const formatPreviewText = (text) => {
  return text.split('\n').map(line => `<p>${line || '&nbsp;'}</p>`).join('');
};

onMounted(() => {
  fetchCurrentUser();
  fetchCategories();
  fetchWarehouses();
  fetchPaymentMethods();
  fetchTaxConfigs();
  fetchPrinterSettings();
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

/* Tax & Printer Settings Styles */
.tab-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
  gap: 2rem;
}

.tab-header h2 {
  margin: 0 0 0.5rem 0;
  color: #1f2937;
  font-size: 1.75rem;
  font-weight: 700;
}

.tab-header > div:first-child {
  flex: 1;
}

.save-btn {
  background: linear-gradient(135deg, #10b981, #059669);
  color: white;
  border: none;
  border-radius: 10px;
  padding: 0.875rem 1.75rem;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
  white-space: nowrap;
  height: fit-content;
}

.save-btn:hover:not(:disabled) {
  background: linear-gradient(135deg, #059669, #047857);
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
}

.save-btn:disabled {
  background: #9ca3af;
  cursor: not-allowed;
  box-shadow: none;
  transform: none;
  opacity: 0.6;
}

.save-btn i {
  font-size: 1rem;
}

/* Tax Form Styles */
.tax-form {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr auto auto;
  gap: 0.75rem;
  background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
  padding: 1.5rem;
  border-radius: 12px;
  border: 2px solid #bae6fd;
  margin-bottom: 2rem;
}

.tax-form input,
.tax-form select {
  padding: 0.75rem 1rem;
  border: 1px solid #e0e7ff;
  border-radius: 8px;
  font-size: 0.95rem;
  background: white;
  transition: all 0.3s ease;
}

.tax-form input:focus,
.tax-form select:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Tax Cards Grid */
.tax-cards {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
  gap: 1.5rem;
  margin-top: 1.5rem;
}

.tax-card {
  background: white;
  border: 2px solid #e5e7eb;
  border-radius: 16px;
  padding: 1.75rem;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.tax-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #3b82f6, #8b5cf6);
}

.tax-card.default::before {
  background: linear-gradient(90deg, #f59e0b, #d97706);
}

.tax-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 28px rgba(0, 0, 0, 0.12);
  border-color: #3b82f6;
}

.tax-card.default {
  background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
  border-color: #fbbf24;
}

.tax-card.default:hover {
  border-color: #f59e0b;
}

.tax-card-header {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  margin-bottom: 1.5rem;
  padding-bottom: 1.25rem;
  border-bottom: 2px solid #f3f4f6;
}

.tax-card.default .tax-card-header {
  border-bottom-color: #fde68a;
}

.tax-icon {
  width: 56px;
  height: 56px;
  border-radius: 14px;
  background: linear-gradient(135deg, #dbeafe, #bfdbfe);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  color: #2563eb;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
  transition: all 0.3s ease;
}

.tax-card:hover .tax-icon {
  transform: scale(1.1) rotate(5deg);
}

.tax-card.default .tax-icon {
  background: linear-gradient(135deg, #fbbf24, #f59e0b);
  color: white;
  box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}

.tax-info {
  flex: 1;
}

.tax-info h3 {
  margin: 0 0 0.5rem 0;
  font-size: 1.25rem;
  font-weight: 700;
  color: #1f2937;
  letter-spacing: -0.01em;
}

.tax-rate {
  margin: 0;
  font-size: 1rem;
  color: #6b7280;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.tax-rate::before {
  content: '📊';
  font-size: 1.1rem;
}

.default-badge {
  background: linear-gradient(135deg, #fbbf24, #f59e0b);
  color: white;
  padding: 0.375rem 1rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.default-badge::before {
  content: '⭐';
}

.tax-card-actions {
  display: flex;
  gap: 0.75rem;
  padding-top: 1.25rem;
}

.set-default-btn {
  flex: 1;
  background: white;
  border: 2px solid #e5e7eb;
  color: #374151;
  padding: 0.625rem 1.25rem;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
  font-size: 0.9rem;
}

.set-default-btn i {
  color: #9ca3af;
  transition: color 0.3s ease;
}

.set-default-btn:not(:disabled):hover {
  background: #fffbeb;
  border-color: #fbbf24;
  color: #f59e0b;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(251, 191, 36, 0.2);
}

.set-default-btn:not(:disabled):hover i {
  color: #f59e0b;
}

.set-default-btn:disabled {
  background: #f9fafb;
  color: #9ca3af;
  cursor: not-allowed;
  border-color: #e5e7eb;
}

.set-default-btn:disabled i {
  color: #d1d5db;
}

.tax-card-actions .edit-btn,
.tax-card-actions .delete-btn {
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  border: 2px solid;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 1rem;
}

.tax-card-actions .edit-btn {
  background: #dbeafe;
  border-color: #93c5fd;
  color: #2563eb;
}

.tax-card-actions .edit-btn:hover {
  background: #2563eb;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.tax-card-actions .delete-btn {
  background: #fee2e2;
  border-color: #fca5a5;
  color: #dc2626;
}

.tax-card-actions .delete-btn:hover:not(:disabled) {
  background: #dc2626;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
}

.tax-card-actions .delete-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
  background: #f3f4f6;
  border-color: #e5e7eb;
  color: #9ca3af;
}

/* Printer Settings Styles */
.printer-settings-form {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.form-section {
  background: white;
  padding: 2rem;
  border-radius: 16px;
  border: 2px solid #e5e7eb;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.form-section::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: linear-gradient(90deg, #3b82f6, #8b5cf6, #ec4899);
}

.form-section:hover {
  border-color: #3b82f6;
  box-shadow: 0 8px 24px rgba(59, 130, 246, 0.1);
}

.form-section h3 {
  margin: 0 0 0.75rem 0;
  color: #1f2937;
  font-size: 1.25rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.form-section h3 i {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #dbeafe, #bfdbfe);
  border-radius: 10px;
  color: #2563eb;
  font-size: 1.1rem;
}

.section-info {
  color: #6b7280;
  font-size: 0.95rem;
  margin: 0 0 1.25rem 0;
  line-height: 1.6;
}

.form-section textarea {
  width: 100%;
  padding: 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  font-size: 0.95rem;
  font-family: 'Courier New', monospace;
  resize: vertical;
  background: #f9fafb;
  transition: all 0.3s ease;
  line-height: 1.6;
}

.form-section textarea:focus {
  outline: none;
  border-color: #3b82f6;
  background: white;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.checkbox-group {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 1rem;
  cursor: pointer;
  font-size: 1rem;
  color: #374151;
  font-weight: 500;
  padding: 1rem;
  border-radius: 10px;
  background: #f9fafb;
  border: 2px solid #e5e7eb;
  transition: all 0.3s ease;
}

.checkbox-label:hover {
  background: white;
  border-color: #3b82f6;
  transform: translateX(4px);
  box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);
}

.checkbox-label input[type="checkbox"] {
  width: 22px;
  height: 22px;
  cursor: pointer;
  accent-color: #3b82f6;
}

.checkbox-label input[type="checkbox"]:checked + span {
  color: #2563eb;
  font-weight: 600;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.form-group label {
  font-weight: 600;
  color: #374151;
  font-size: 0.95rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.form-group label::before {
  content: '•';
  color: #3b82f6;
  font-size: 1.5rem;
}

.form-group select {
  padding: 0.875rem 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 10px;
  font-size: 0.95rem;
  background: #f9fafb;
  cursor: pointer;
  transition: all 0.3s ease;
  font-weight: 500;
  color: #374151;
}

.form-group select:hover {
  border-color: #cbd5e0;
}

.form-group select:focus {
  outline: none;
  border-color: #3b82f6;
  background: white;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.preview-section {
  background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
  padding: 2rem;
  border-radius: 16px;
  border: 2px dashed #3b82f6;
  position: relative;
  margin-top: 1rem;
}

.preview-section::before {
  content: '👁️ LIVE PREVIEW';
  position: absolute;
  top: -12px;
  left: 50%;
  transform: translateX(-50%);
  background: linear-gradient(135deg, #3b82f6, #8b5cf6);
  color: white;
  padding: 0.25rem 1rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.5px;
  box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
}

.preview-section h3 {
  margin: 1rem 0 1.5rem 0;
  color: #1f2937;
  font-size: 1.25rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
}

.preview-section h3 i {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #dbeafe, #bfdbfe);
  border-radius: 10px;
  color: #2563eb;
}

.receipt-preview {
  background: white;
  max-width: 420px;
  margin: 0 auto;
  padding: 2rem;
  border-radius: 12px;
  font-family: 'Courier New', monospace;
  font-size: 13px;
  line-height: 1.7;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
  border: 1px solid #e5e7eb;
  position: relative;
}

.receipt-preview::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 8px;
  background: repeating-linear-gradient(
    45deg,
    #3b82f6,
    #3b82f6 10px,
    #8b5cf6 10px,
    #8b5cf6 20px
  );
  border-radius: 12px 12px 0 0;
}

.receipt-header,
.receipt-footer {
  text-align: center;
  padding: 0.5rem 0;
}

.receipt-header p,
.receipt-footer p {
  margin: 3px 0;
  color: #374151;
}

.preview-placeholder {
  color: #9ca3af;
  font-style: italic;
  padding: 1.5rem 0;
  text-align: center;
  background: #f9fafb;
  border-radius: 8px;
  border: 2px dashed #d1d5db;
}

.receipt-divider {
  text-align: center;
  margin: 1rem 0;
  color: #4b5563;
  font-size: 11px;
  letter-spacing: 1px;
}

.receipt-divider-thin {
  text-align: center;
  margin: 0.75rem 0;
  color: #9ca3af;
  font-size: 11px;
}

.receipt-body {
  margin: 1rem 0;
}

.receipt-body p {
  margin: 8px 0;
  color: #1f2937;
}

.receipt-body p strong {
  color: #111827;
}

/* Responsive Design */
@media (max-width: 1024px) {
  .tax-form {
    grid-template-columns: 1fr 1fr;
  }

  .tax-form .cancel-btn {
    grid-column: 1 / -1;
  }

  .form-row {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .tab-header {
    flex-direction: column;
    align-items: stretch;
    gap: 1rem;
  }

  .save-btn {
    width: 100%;
    justify-content: center;
  }

  .tax-form {
    grid-template-columns: 1fr;
  }

  .tax-cards {
    grid-template-columns: 1fr;
  }

  .tax-card-actions {
    flex-wrap: wrap;
  }

  .set-default-btn {
    width: 100%;
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  .receipt-preview {
    max-width: 100%;
    font-size: 12px;
    padding: 1.5rem;
  }
}

@media (max-width: 480px) {
  .tab-header h2 {
    font-size: 1.5rem;
  }

  .tax-card-actions {
    gap: 0.5rem;
  }

  .tax-card-actions .edit-btn,
  .tax-card-actions .delete-btn {
    width: 40px;
    height: 40px;
    font-size: 0.9rem;
  }

  .form-section {
    padding: 1.5rem 1rem;
  }

  .checkbox-label {
    padding: 0.75rem;
    font-size: 0.95rem;
  }
}
</style>
