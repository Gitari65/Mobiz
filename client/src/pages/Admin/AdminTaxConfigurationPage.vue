<template>
  <div class="admin-tax-configuration">
    <!-- Alert Messages -->
    <div v-if="alert.show" :class="['alert', alert.type]">
      {{ alert.message }}
    </div>

    <!-- Page Header -->
    <div class="page-header">
      <h1>Tax Configuration</h1>
      <p class="subtitle">Manage tax rates, types, and configurations for your company</p>
    </div>

    <!-- Create Form -->
    <div class="create-form-section">
      <h2>{{ editingId ? 'Edit Tax Configuration' : 'Create New Tax Configuration' }}</h2>
      <form @submit.prevent="editingId ? updateTaxConfig() : createTaxConfig()" class="tax-form">
        <div class="form-row">
          <div class="form-group">
            <label for="name">Name *</label>
            <input 
              id="name"
              v-model="formData.name" 
              type="text" 
              placeholder="e.g., Standard VAT, Zero-Rated, Exempt" 
              required 
            />
          </div>
          <div class="form-group">
            <label for="tax_type">Tax Type *</label>
            <select id="tax_type" v-model="formData.tax_type" required>
              <option value="">Select tax type</option>
              <option value="VAT">VAT (Value Added Tax)</option>
              <option value="Excise">Excise Duty</option>
              <option value="Withholding">Withholding Tax</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="form-group">
            <label for="rate">Tax Rate (%) *</label>
            <input 
              id="rate"
              v-model.number="formData.rate" 
              type="number" 
              step="0.01" 
              min="0" 
              max="100" 
              placeholder="e.g., 16.00" 
              required 
            />
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="description">Description</label>
            <textarea 
              id="description"
              v-model="formData.description" 
              placeholder="Describe when this tax rate applies"
              rows="3"
            ></textarea>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group checkbox">
            <label for="is_inclusive">
              <input id="is_inclusive" v-model="formData.is_inclusive" type="checkbox" />
              <span>Tax Inclusive (Price includes tax)</span>
            </label>
            <small v-if="formData.is_inclusive" class="help-text">
              Tax is already included in the quoted price. Tax amount will be extracted from the price.
            </small>
            <small v-else class="help-text">
              Tax is added to the base price. Used for most Kenya transactions.
            </small>
          </div>
          <div class="form-group checkbox">
            <label for="is_active">
              <input id="is_active" v-model="formData.is_active" type="checkbox" />
              <span>Active</span>
            </label>
            <small class="help-text">Disable to hide from selection without deleting.</small>
          </div>
          <div class="form-group checkbox">
            <label for="is_default">
              <input id="is_default" v-model="formData.is_default" type="checkbox" />
              <span>Set as Default</span>
            </label>
            <small class="help-text">This tax rate will be auto-applied to new products.</small>
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" class="primary-btn" :disabled="loading.form">
            <span v-if="loading.form" class="spinner"></span>
            <span v-else>{{ editingId ? 'Update Tax Configuration' : 'Create Tax Configuration' }}</span>
          </button>
          <button v-if="editingId" type="button" class="cancel-btn" @click="cancelEdit" :disabled="loading.form">
            Cancel
          </button>
        </div>
      </form>
    </div>

    <!-- Tax Configurations List -->
    <div class="list-section">
      <h2>Tax Configurations <span class="count">({{ taxConfigs.length }})</span></h2>
      
      <div v-if="loading.list" class="loading-container">
        <div class="spinner large"></div>
        <p>Loading tax configurations...</p>
      </div>
      <div v-else-if="taxConfigs.length === 0" class="empty-state">
        <i class="fas fa-receipt"></i>
        <p>No tax configurations found</p>
        <small>Create your first tax configuration above</small>
      </div>
      <div v-else class="grid-container">
        <div v-for="config in taxConfigs" :key="config.id" class="tax-card" :class="{ default: config.is_default, inactive: !config.is_active }">
          <div class="card-header">
            <div class="card-title">
              <h3>{{ config.name }}</h3>
              <div class="badges">
                <span v-if="config.is_default" class="badge default">Default</span>
                <span v-if="!config.is_active" class="badge inactive">Inactive</span>
              </div>
            </div>
            <button class="card-menu-btn" @click="toggleCardMenu(config.id)">
              <i class="fas fa-ellipsis-v"></i>
            </button>
            <div v-if="openCardMenu === config.id" class="card-menu">
              <button @click="editTaxConfig(config)">
                <i class="fas fa-edit"></i> Edit
              </button>
              <button @click="toggleDefault(config)" v-if="!config.is_default">
                <i class="fas fa-star"></i> Set as Default
              </button>
              <button @click="toggleActive(config)" :class="{ danger: config.is_active }">
                <i :class="config.is_active ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                {{ config.is_active ? 'Deactivate' : 'Activate' }}
              </button>
              <button @click="deleteTaxConfig(config.id)" class="danger">
                <i class="fas fa-trash"></i> Delete
              </button>
            </div>
          </div>

          <div class="card-content">
            <div class="info-row">
              <span class="label">Tax Type:</span>
              <span class="value">{{ config.tax_type }}</span>
            </div>
            <div class="info-row">
              <span class="label">Rate:</span>
              <span class="value rate">{{ config.rate.toFixed(2) }}%</span>
            </div>
            <div class="info-row">
              <span class="label">Pricing:</span>
              <span class="value">
                <span v-if="config.is_inclusive" class="pricing-type inclusive">Inclusive</span>
                <span v-else class="pricing-type exclusive">Exclusive</span>
              </span>
            </div>
            <div v-if="config.description" class="info-row full-width">
              <span class="label">Description:</span>
              <p class="description">{{ config.description }}</p>
            </div>
          </div>

          <div class="card-footer">
            <div class="meta">
              <small class="text-muted">Created {{ formatDate(config.created_at) }}</small>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tax Calculation Test Section -->
    <div class="test-section">
      <h2>Tax Calculation Test</h2>
      <p class="subtitle">Test how taxes are calculated for different amounts</p>
      
      <div class="test-form">
        <div class="form-row">
          <div class="form-group">
            <label for="test_config">Select Tax Configuration:</label>
            <select id="test_config" v-model="testData.config_id">
              <option value="">-- Select a tax configuration --</option>
              <option v-for="config in activeTaxConfigs" :key="config.id" :value="config.id">
                {{ config.name }} ({{ config.rate.toFixed(2) }}%)
              </option>
            </select>
          </div>
          <div class="form-group">
            <label for="test_amount">Amount:</label>
            <input 
              id="test_amount"
              v-model.number="testData.amount" 
              type="number" 
              step="0.01" 
              min="0" 
              placeholder="Enter amount to calculate tax" 
            />
          </div>
          <div class="form-group">
            <button @click="calculateTestTax" class="primary-btn" :disabled="!testData.config_id || !testData.amount">
              Calculate
            </button>
          </div>
        </div>
      </div>

      <div v-if="testResult" class="test-result">
        <div class="result-item">
          <span class="label">Base Amount:</span>
          <span class="value">{{ formatCurrency(testResult.base_amount) }}</span>
        </div>
        <div class="result-item">
          <span class="label">Tax Amount:</span>
          <span class="value tax">{{ formatCurrency(testResult.tax_amount) }}</span>
        </div>
        <div class="result-item highlight">
          <span class="label">Total Amount:</span>
          <span class="value total">{{ formatCurrency(testResult.total_amount) }}</span>
        </div>
        <div class="result-note">
          <small v-if="testResult.is_inclusive" class="note-inclusive">
            <i class="fas fa-info-circle"></i>
            Tax was extracted from the total (inclusive pricing)
          </small>
          <small v-else class="note-exclusive">
            <i class="fas fa-info-circle"></i>
            Tax was added to the base amount (exclusive pricing)
          </small>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'AdminTaxConfigurationPage',
  data() {
    return {
      taxConfigs: [],
      testResult: null,
      testData: {
        config_id: null,
        amount: null,
      },
      formData: {
        name: '',
        tax_type: '',
        rate: null,
        is_inclusive: false,
        is_default: false,
        is_active: true,
        description: '',
      },
      editingId: null,
      openCardMenu: null,
      alert: {
        show: false,
        type: 'success',
        message: '',
      },
      loading: {
        list: false,
        form: false,
      },
    };
  },
  computed: {
    activeTaxConfigs() {
      return this.taxConfigs.filter(c => c.is_active);
    },
  },
  mounted() {
    this.fetchTaxConfigs();
  },
  methods: {
    async fetchTaxConfigs() {
      this.loading.list = true;
      try {
        const response = await axios.get('/api/tax-configurations', {
          headers: {
            Authorization: `Bearer ${localStorage.getItem('authToken')}`,
          },
        });
        this.taxConfigs = response.data.data || response.data;
      } catch (error) {
        this.showAlert('Failed to load tax configurations', 'error');
        console.error('Error fetching tax configurations:', error);
      } finally {
        this.loading.list = false;
      }
    },
    async createTaxConfig() {
      this.loading.form = true;
      try {
        const response = await axios.post('/api/tax-configurations', this.formData, {
          headers: {
            Authorization: `Bearer ${localStorage.getItem('authToken')}`,
          },
        });
        this.taxConfigs.push(response.data.data || response.data);
        this.resetForm();
        this.showAlert('Tax configuration created successfully', 'success');
      } catch (error) {
        this.showAlert(error.response?.data?.message || 'Failed to create tax configuration', 'error');
        console.error('Error creating tax configuration:', error);
      } finally {
        this.loading.form = false;
      }
    },
    async updateTaxConfig() {
      this.loading.form = true;
      try {
        const response = await axios.put(`/api/tax-configurations/${this.editingId}`, this.formData, {
          headers: {
            Authorization: `Bearer ${localStorage.getItem('authToken')}`,
          },
        });
        const idx = this.taxConfigs.findIndex(t => t.id === this.editingId);
        if (idx !== -1) {
          this.taxConfigs[idx] = response.data.data || response.data;
        }
        this.resetForm();
        this.showAlert('Tax configuration updated successfully', 'success');
      } catch (error) {
        this.showAlert(error.response?.data?.message || 'Failed to update tax configuration', 'error');
        console.error('Error updating tax configuration:', error);
      } finally {
        this.loading.form = false;
      }
    },
    async deleteTaxConfig(id) {
      if (!confirm('Are you sure you want to delete this tax configuration?')) return;

      this.loading.list = true;
      try {
        await axios.delete(`/api/tax-configurations/${id}`, {
          headers: {
            Authorization: `Bearer ${localStorage.getItem('authToken')}`,
          },
        });
        this.taxConfigs = this.taxConfigs.filter(t => t.id !== id);
        this.showAlert('Tax configuration deleted successfully', 'success');
      } catch (error) {
        this.showAlert(error.response?.data?.message || 'Failed to delete tax configuration', 'error');
        console.error('Error deleting tax configuration:', error);
      } finally {
        this.loading.list = false;
      }
    },
    async toggleDefault(config) {
      this.loading.list = true;
      try {
        const response = await axios.post(`/api/tax-configurations/${config.id}/set-default`, {}, {
          headers: {
            Authorization: `Bearer ${localStorage.getItem('authToken')}`,
          },
        });
        // Update all configs: unset previous default, set new default
        this.taxConfigs.forEach(t => {
          t.is_default = t.id === config.id;
        });
        this.showAlert('Default tax configuration updated', 'success');
      } catch (error) {
        this.showAlert(error.response?.data?.message || 'Failed to set default tax configuration', 'error');
        console.error('Error setting default:', error);
      } finally {
        this.loading.list = false;
      }
    },
    async toggleActive(config) {
      const newStatus = !config.is_active;
      this.loading.list = true;
      try {
        const response = await axios.put(`/api/tax-configurations/${config.id}`, 
          { is_active: newStatus }, 
          {
            headers: {
              Authorization: `Bearer ${localStorage.getItem('authToken')}`,
            },
          }
        );
        config.is_active = newStatus;
        this.showAlert(`Tax configuration ${newStatus ? 'activated' : 'deactivated'}`, 'success');
      } catch (error) {
        this.showAlert(error.response?.data?.message || 'Failed to update tax configuration status', 'error');
        console.error('Error toggling active status:', error);
      } finally {
        this.loading.list = false;
      }
    },
    editTaxConfig(config) {
      this.editingId = config.id;
      this.formData = {
        name: config.name,
        tax_type: config.tax_type,
        rate: config.rate,
        is_inclusive: config.is_inclusive,
        is_default: config.is_default,
        is_active: config.is_active,
        description: config.description || '',
      };
      this.openCardMenu = null;
      // Scroll to form
      setTimeout(() => {
        document.querySelector('.create-form-section')?.scrollIntoView({ behavior: 'smooth' });
      }, 100);
    },
    cancelEdit() {
      this.resetForm();
    },
    resetForm() {
      this.formData = {
        name: '',
        tax_type: '',
        rate: null,
        is_inclusive: false,
        is_default: false,
        is_active: true,
        description: '',
      };
      this.editingId = null;
    },
    toggleCardMenu(id) {
      this.openCardMenu = this.openCardMenu === id ? null : id;
    },
    async calculateTestTax() {
      try {
        const response = await axios.post('/api/tax-configurations/calculate', {
          config_id: this.testData.config_id,
          amount: this.testData.amount,
        }, {
          headers: {
            Authorization: `Bearer ${localStorage.getItem('authToken')}`,
          },
        });
        this.testResult = response.data.data || response.data;
      } catch (error) {
        this.showAlert('Failed to calculate tax', 'error');
        console.error('Error calculating tax:', error);
      }
    },
    showAlert(message, type = 'success') {
      this.alert = { show: true, type, message };
      setTimeout(() => {
        this.alert.show = false;
      }, 5000);
    },
    formatDate(dateString) {
      if (!dateString) return 'N/A';
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
      });
    },
    formatCurrency(value) {
      if (value === null || value === undefined) return 'KES 0.00';
      return `KES ${parseFloat(value).toFixed(2)}`;
    },
  },
};
</script>

<style scoped>
.admin-tax-configuration {
  padding: 20px;
  max-width: 1400px;
  margin: 0 auto;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Page Header */
.page-header {
  margin-bottom: 40px;
  border-bottom: 2px solid #e0e7ff;
  padding-bottom: 20px;
}

.page-header h1 {
  margin: 0;
  font-size: 28px;
  color: #1f2937;
  margin-bottom: 8px;
}

.page-header .subtitle {
  margin: 0;
  color: #6b7280;
  font-size: 14px;
}

/* Alert */
.alert {
  padding: 12px 16px;
  margin-bottom: 20px;
  border-radius: 6px;
  font-size: 14px;
  animation: slideIn 0.3s ease-out;
}

.alert.success {
  background-color: #ecfdf5;
  color: #065f46;
  border: 1px solid #d1fae5;
}

.alert.error {
  background-color: #fef2f2;
  color: #7f1d1d;
  border: 1px solid #fee2e2;
}

@keyframes slideIn {
  from {
    transform: translateY(-20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

/* Form Sections */
.create-form-section,
.list-section,
.test-section {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 24px;
  margin-bottom: 30px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.create-form-section h2,
.list-section h2,
.test-section h2 {
  margin: 0 0 20px 0;
  font-size: 18px;
  color: #1f2937;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.subtitle {
  color: #6b7280;
  font-size: 13px;
  margin: 0 0 20px 0;
}

/* Forms */
.tax-form,
.test-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 16px;
}

.form-row.full-width {
  grid-template-columns: 1fr;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-group label {
  font-weight: 500;
  color: #374151;
  font-size: 13px;
}

.form-group input,
.form-group textarea,
.form-group select {
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 13px;
  transition: all 0.2s ease;
  font-family: inherit;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.form-group textarea {
  resize: vertical;
  min-height: 80px;
}

.form-group.checkbox {
  flex-direction: row;
  align-items: flex-start;
  gap: 8px;
}

.form-group.checkbox label {
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 0;
  font-weight: 400;
}

.form-group.checkbox input[type="checkbox"] {
  width: 18px;
  height: 18px;
  margin: 0;
  cursor: pointer;
}

.help-text {
  color: #9ca3af;
  font-size: 12px;
  margin-top: 4px;
}

.form-actions {
  display: flex;
  gap: 12px;
  margin-top: 12px;
}

/* Buttons */
.primary-btn,
.cancel-btn,
.secondary-btn {
  padding: 10px 20px;
  border: none;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-height: 40px;
}

.primary-btn {
  background-color: #6366f1;
  color: white;
}

.primary-btn:hover:not(:disabled) {
  background-color: #4f46e5;
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.primary-btn:disabled {
  background-color: #d1d5db;
  cursor: not-allowed;
  opacity: 0.6;
}

.cancel-btn {
  background-color: #e5e7eb;
  color: #374151;
}

.cancel-btn:hover:not(:disabled) {
  background-color: #d1d5db;
}

.secondary-btn {
  background-color: #f3f4f6;
  color: #374151;
  border: 1px solid #d1d5db;
}

.secondary-btn:hover:not(:disabled) {
  background-color: #e5e7eb;
}

/* Grid Container */
.grid-container {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 20px;
  margin-top: 20px;
}

/* Tax Card */
.tax-card {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
  background: white;
  transition: all 0.2s ease;
  display: flex;
  flex-direction: column;
  position: relative;
}

.tax-card:hover {
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
  border-color: #d1d5db;
}

.tax-card.default {
  border-left: 4px solid #6366f1;
}

.tax-card.inactive {
  opacity: 0.6;
  background-color: #f9fafb;
}

.card-header {
  padding: 16px;
  border-bottom: 1px solid #f3f4f6;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  position: relative;
}

.card-title h3 {
  margin: 0 0 8px 0;
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
}

.badges {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}

.badge {
  display: inline-block;
  padding: 3px 10px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.badge.default {
  background-color: #e0e7ff;
  color: #4338ca;
}

.badge.inactive {
  background-color: #fee2e2;
  color: #991b1b;
}

.card-menu-btn {
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px 8px;
  color: #6b7280;
  font-size: 16px;
  transition: color 0.2s ease;
}

.card-menu-btn:hover {
  color: #1f2937;
}

.card-menu {
  position: absolute;
  top: 100%;
  right: 16px;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
  z-index: 10;
  min-width: 180px;
  padding: 4px;
}

.card-menu button {
  width: 100%;
  padding: 10px 12px;
  border: none;
  background: none;
  text-align: left;
  cursor: pointer;
  font-size: 13px;
  color: #374151;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s ease;
}

.card-menu button:hover {
  background-color: #f3f4f6;
}

.card-menu button.danger:hover {
  background-color: #fee2e2;
  color: #991b1b;
}

.card-content {
  padding: 16px;
  flex: 1;
}

.info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
  font-size: 13px;
}

.info-row.full-width {
  flex-direction: column;
  align-items: flex-start;
}

.info-row .label {
  font-weight: 500;
  color: #6b7280;
}

.info-row .value {
  color: #1f2937;
  font-weight: 600;
}

.info-row .value.rate {
  font-size: 18px;
  color: #6366f1;
}

.pricing-type {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 600;
}

.pricing-type.inclusive {
  background-color: #dbeafe;
  color: #0c4a6e;
}

.pricing-type.exclusive {
  background-color: #fef3c7;
  color: #78350f;
}

.description {
  margin: 8px 0 0 0;
  color: #6b7280;
  font-size: 12px;
  line-height: 1.4;
}

.card-footer {
  padding: 12px 16px;
  border-top: 1px solid #f3f4f6;
  background-color: #fafafa;
}

.meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 12px;
}

.text-muted {
  color: #9ca3af;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 40px 20px;
  color: #6b7280;
}

.empty-state i {
  font-size: 48px;
  color: #d1d5db;
  display: block;
  margin-bottom: 12px;
}

.empty-state p {
  margin: 12px 0 8px 0;
  font-size: 16px;
  font-weight: 600;
}

.empty-state small {
  color: #9ca3af;
  font-size: 13px;
}

/* Loading */
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  gap: 16px;
}

.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid #e5e7eb;
  border-top: 3px solid #6366f1;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.spinner.large {
  width: 48px;
  height: 48px;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Test Section */
.test-result {
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  padding: 20px;
  margin-top: 20px;
}

.result-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  border-bottom: 1px solid #e5e7eb;
  font-size: 14px;
}

.result-item:last-of-type {
  border-bottom: none;
}

.result-item .label {
  font-weight: 500;
  color: #374151;
}

.result-item .value {
  font-weight: 600;
  color: #1f2937;
  font-size: 16px;
}

.result-item .value.tax {
  color: #10b981;
}

.result-item .value.total {
  color: #6366f1;
  font-size: 18px;
}

.result-item.highlight {
  background-color: #f0f4ff;
  border-radius: 4px;
  margin-top: 8px;
}

.result-note {
  margin-top: 12px;
  padding: 12px;
  border-radius: 4px;
  font-size: 12px;
}

.note-inclusive {
  background-color: #dbeafe;
  color: #0c4a6e;
  display: flex;
  align-items: center;
  gap: 8px;
}

.note-exclusive {
  background-color: #fef3c7;
  color: #78350f;
  display: flex;
  align-items: center;
  gap: 8px;
}

/* Count Badge */
.count {
  font-size: 14px;
  color: #9ca3af;
  font-weight: 400;
  margin-left: 8px;
}

/* Responsive */
@media (max-width: 768px) {
  .admin-tax-configuration {
    padding: 12px;
  }

  .grid-container {
    grid-template-columns: 1fr;
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  .page-header h1 {
    font-size: 22px;
  }

  .create-form-section,
  .list-section,
  .test-section {
    padding: 16px;
  }

  .card-menu {
    right: auto;
    left: 16px;
  }
}
</style>
