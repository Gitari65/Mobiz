<template>
  <div class="settings-page">
    <div class="page-header">
      <h1><i class="fas fa-cog"></i> Settings</h1>
      <p class="subtitle">Manage your personal and company preferences</p>
    </div>

    <!-- Tab Navigation (Admin sees both tabs, Cashier only sees Personal) -->
    <div class="tabs" v-if="isAdmin">
      <button 
        :class="['tab-button', { active: activeTab === 'personal' }]" 
        @click="activeTab = 'personal'"
      >
        <i class="fas fa-user"></i> Personal Settings
      </button>
      <button 
        :class="['tab-button', { active: activeTab === 'company' }]" 
        @click="activeTab = 'company'"
      >
        <i class="fas fa-building"></i> Company Settings
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="spinner"></div>
      <p>Loading settings...</p>
    </div>

    <!-- Error State -->
    <div v-if="error" class="error-message">
      <i class="fas fa-exclamation-circle"></i>
      {{ error }}
    </div>

    <!-- Success Message -->
    <div v-if="successMessage" class="success-message">
      <i class="fas fa-check-circle"></i>
      {{ successMessage }}
    </div>

    <!-- PERSONAL SETTINGS TAB -->
    <div v-if="activeTab === 'personal' && !loading" class="settings-content">
      <form @submit.prevent="savePersonalSettings">
        
        <!-- Display Preferences -->
        <section class="settings-section">
          <h2><i class="fas fa-paint-brush"></i> Display Preferences</h2>
          
          <div class="form-group">
            <label>Theme</label>
            <select v-model="userSettings.theme" class="form-control">
              <option value="light">Light</option>
              <option value="dark">Dark</option>
            </select>
          </div>

          <div class="form-group">
            <label>Language</label>
            <select v-model="userSettings.language" class="form-control">
              <option value="en">English</option>
              <option value="sw">Swahili</option>
            </select>
          </div>

          <div class="form-group">
            <label>Items Per Page</label>
            <input 
              v-model.number="userSettings.items_per_page" 
              type="number" 
              min="5" 
              max="100" 
              class="form-control"
            />
          </div>

          <div class="form-group">
            <label>Date Format</label>
            <select v-model="userSettings.date_format" class="form-control">
              <option value="Y-m-d">YYYY-MM-DD</option>
              <option value="d/m/Y">DD/MM/YYYY</option>
              <option value="m/d/Y">MM/DD/YYYY</option>
            </select>
          </div>

          <div class="form-group">
            <label>Time Format</label>
            <select v-model="userSettings.time_format" class="form-control">
              <option value="H:i">24-hour (HH:mm)</option>
              <option value="h:i A">12-hour (hh:mm AM/PM)</option>
            </select>
          </div>

          <div class="form-group">
            <label>Default Landing Page</label>
            <input 
              v-model="userSettings.default_page" 
              type="text" 
              class="form-control"
              placeholder="/"
            />
          </div>
        </section>

        <!-- Notification Preferences -->
        <section class="settings-section">
          <h2><i class="fas fa-bell"></i> Notification Preferences</h2>
          
          <div class="form-group checkbox-group">
            <label class="checkbox-label">
              <input v-model="userSettings.email_notifications" type="checkbox" />
              <span>Email Notifications</span>
            </label>
          </div>

          <div class="form-group checkbox-group">
            <label class="checkbox-label">
              <input v-model="userSettings.push_notifications" type="checkbox" />
              <span>Push Notifications</span>
            </label>
          </div>

          <div class="form-group checkbox-group">
            <label class="checkbox-label">
              <input v-model="userSettings.low_stock_alerts" type="checkbox" />
              <span>Low Stock Alerts</span>
            </label>
          </div>

          <div class="form-group checkbox-group">
            <label class="checkbox-label">
              <input v-model="userSettings.sale_alerts" type="checkbox" />
              <span>Sale Alerts</span>
            </label>
          </div>

          <div class="form-group checkbox-group">
            <label class="checkbox-label">
              <input v-model="userSettings.report_alerts" type="checkbox" />
              <span>Report Alerts</span>
            </label>
          </div>
        </section>

        <!-- Receipt Preferences -->
        <section class="settings-section">
          <h2><i class="fas fa-receipt"></i> Receipt Preferences</h2>
          
          <div class="form-group checkbox-group">
            <label class="checkbox-label">
              <input v-model="userSettings.auto_print_receipt" type="checkbox" />
              <span>Auto-print Receipt After Sale</span>
            </label>
          </div>

          <div class="form-group">
            <label>Printer Name</label>
            <input 
              v-model="userSettings.printer_name" 
              type="text" 
              class="form-control"
              placeholder="Default printer"
            />
          </div>
        </section>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary" :disabled="saving">
            <i class="fas fa-save"></i>
            {{ saving ? 'Saving...' : 'Save Personal Settings' }}
          </button>
        </div>
      </form>
    </div>

    <!-- COMPANY SETTINGS TAB (Admin Only) -->
    <div v-if="activeTab === 'company' && isAdmin && !loading" class="settings-content">
      <form @submit.prevent="saveCompanySettings">
        
        <!-- Business Configuration -->
        <section class="settings-section">
          <h2><i class="fas fa-briefcase"></i> Business Configuration</h2>
          
          <div class="form-row">
            <div class="form-group">
              <label>Business Hours Start</label>
              <input 
                v-model="companySettings.business_hours_start" 
                type="time" 
                class="form-control"
              />
            </div>

            <div class="form-group">
              <label>Business Hours End</label>
              <input 
                v-model="companySettings.business_hours_end" 
                type="time" 
                class="form-control"
              />
            </div>
          </div>

          <div class="form-group">
            <label>Timezone</label>
            <select v-model="companySettings.timezone" class="form-control">
              <option value="Africa/Nairobi">Africa/Nairobi (EAT)</option>
              <option value="UTC">UTC</option>
              <option value="America/New_York">America/New York</option>
              <option value="Europe/London">Europe/London</option>
            </select>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Currency</label>
              <input 
                v-model="companySettings.currency" 
                type="text" 
                class="form-control"
                maxlength="10"
              />
            </div>

            <div class="form-group">
              <label>Currency Symbol</label>
              <input 
                v-model="companySettings.currency_symbol" 
                type="text" 
                class="form-control"
                maxlength="10"
              />
            </div>

            <div class="form-group">
              <label>Decimal Places</label>
              <input 
                v-model.number="companySettings.decimal_places" 
                type="number" 
                min="0" 
                max="4" 
                class="form-control"
              />
            </div>
          </div>
        </section>

        <!-- Tax Settings -->
        <section class="settings-section">
          <h2><i class="fas fa-percent"></i> Tax Settings</h2>
          
          <div class="form-group checkbox-group">
            <label class="checkbox-label">
              <input v-model="companySettings.tax_enabled" type="checkbox" />
              <span>Enable Tax</span>
            </label>
          </div>

          <div v-if="companySettings.tax_enabled">
            <div class="form-group">
              <label>Tax Name (e.g., VAT, GST)</label>
              <input 
                v-model="companySettings.tax_name" 
                type="text" 
                class="form-control"
              />
            </div>

            <div class="form-group">
              <label>Tax Rate (%)</label>
              <input 
                v-model.number="companySettings.tax_rate" 
                type="number" 
                step="0.01" 
                min="0" 
                max="100" 
                class="form-control"
              />
            </div>

            <div class="form-group checkbox-group">
              <label class="checkbox-label">
                <input v-model="companySettings.tax_inclusive" type="checkbox" />
                <span>Tax Inclusive Pricing</span>
              </label>
            </div>
          </div>
        </section>

        <!-- Receipt & Invoice Settings -->
        <section class="settings-section">
          <h2><i class="fas fa-file-invoice"></i> Receipt & Invoice Settings</h2>
          
          <div class="form-group">
            <label>Receipt Header</label>
            <input 
              v-model="companySettings.receipt_header" 
              type="text" 
              class="form-control"
              placeholder="Thank you for shopping with us!"
            />
          </div>

          <div class="form-group">
            <label>Receipt Footer</label>
            <textarea 
              v-model="companySettings.receipt_footer" 
              class="form-control"
              rows="3"
              placeholder="Terms and conditions..."
            ></textarea>
          </div>

          <div class="form-group checkbox-group">
            <label class="checkbox-label">
              <input v-model="companySettings.auto_print_receipt" type="checkbox" />
              <span>Auto-print Receipt</span>
            </label>
          </div>

          <div class="form-group">
            <label>Receipt Logo</label>
            <div class="logo-upload">
              <img v-if="companySettings.receipt_logo_url" :src="companySettings.receipt_logo_url" alt="Receipt Logo" class="logo-preview" />
              <input type="file" @change="uploadReceiptLogo" accept="image/*" />
              <button v-if="companySettings.receipt_logo_path" @click.prevent="removeReceiptLogo" type="button" class="btn btn-danger btn-sm">
                Remove Logo
              </button>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Invoice Prefix</label>
              <input 
                v-model="companySettings.invoice_prefix" 
                type="text" 
                class="form-control"
              />
            </div>

            <div class="form-group">
              <label>Starting Invoice Number</label>
              <input 
                v-model.number="companySettings.invoice_number_start" 
                type="number" 
                min="1" 
                class="form-control"
              />
            </div>
          </div>
        </section>

        <!-- Inventory Settings -->
        <section class="settings-section">
          <h2><i class="fas fa-warehouse"></i> Inventory Settings</h2>
          
          <div class="form-group checkbox-group">
            <label class="checkbox-label">
              <input v-model="companySettings.low_stock_alerts" type="checkbox" />
              <span>Low Stock Alerts</span>
            </label>
          </div>

          <div v-if="companySettings.low_stock_alerts" class="form-group">
            <label>Low Stock Threshold</label>
            <input 
              v-model.number="companySettings.low_stock_threshold" 
              type="number" 
              min="0" 
              class="form-control"
            />
          </div>

          <div class="form-group checkbox-group">
            <label class="checkbox-label">
              <input v-model="companySettings.allow_negative_stock" type="checkbox" />
              <span>Allow Negative Stock</span>
            </label>
          </div>

          <div class="form-group checkbox-group">
            <label class="checkbox-label">
              <input v-model="companySettings.track_stock_expiry" type="checkbox" />
              <span>Track Stock Expiry Dates</span>
            </label>
          </div>
        </section>

        <!-- Sales Settings -->
        <section class="settings-section">
          <h2><i class="fas fa-shopping-cart"></i> Sales Settings</h2>
          
          <div class="form-group checkbox-group">
            <label class="checkbox-label">
              <input v-model="companySettings.require_customer_info" type="checkbox" />
              <span>Require Customer Information</span>
            </label>
          </div>

          <div class="form-group checkbox-group">
            <label class="checkbox-label">
              <input v-model="companySettings.allow_discount" type="checkbox" />
              <span>Allow Discounts</span>
            </label>
          </div>

          <div v-if="companySettings.allow_discount" class="form-group">
            <label>Maximum Discount (%)</label>
            <input 
              v-model.number="companySettings.max_discount_percent" 
              type="number" 
              step="0.01" 
              min="0" 
              max="100" 
              class="form-control"
            />
          </div>

          <div class="form-group checkbox-group">
            <label class="checkbox-label">
              <input v-model="companySettings.allow_credit_sales" type="checkbox" />
              <span>Allow Credit Sales</span>
            </label>
          </div>
        </section>

        <!-- Security Settings -->
        <section class="settings-section">
          <h2><i class="fas fa-shield-alt"></i> Security Settings</h2>
          
          <div class="form-group checkbox-group">
            <label class="checkbox-label">
              <input v-model="companySettings.require_receipt_approval" type="checkbox" />
              <span>Require Receipt Approval</span>
            </label>
          </div>

          <div class="form-group checkbox-group">
            <label class="checkbox-label">
              <input v-model="companySettings.enable_audit_log" type="checkbox" />
              <span>Enable Audit Logging</span>
            </label>
          </div>

          <div class="form-group">
            <label>Session Timeout (minutes)</label>
            <input 
              v-model.number="companySettings.session_timeout_minutes" 
              type="number" 
              min="5" 
              max="480" 
              class="form-control"
            />
          </div>

          <div class="form-group checkbox-group">
            <label class="checkbox-label">
              <input v-model="companySettings.two_factor_auth" type="checkbox" />
              <span>Two-Factor Authentication</span>
            </label>
          </div>
        </section>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary" :disabled="saving">
            <i class="fas fa-save"></i>
            {{ saving ? 'Saving...' : 'Save Company Settings' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import api from '../../services/api';

export default {
  name: 'SettingsPage',
  data() {
    return {
      activeTab: 'personal',
      loading: false,
      saving: false,
      error: null,
      successMessage: null,
      userSettings: {
        theme: 'light',
        language: 'en',
        items_per_page: 20,
        date_format: 'Y-m-d',
        time_format: 'H:i',
        email_notifications: true,
        push_notifications: true,
        low_stock_alerts: true,
        sale_alerts: false,
        report_alerts: true,
        auto_print_receipt: false,
        printer_name: '',
        default_page: '/',
      },
      companySettings: {
        business_hours_start: '',
        business_hours_end: '',
        timezone: 'Africa/Nairobi',
        currency: 'KES',
        currency_symbol: 'KSh',
        decimal_places: 2,
        tax_enabled: false,
        tax_name: '',
        tax_rate: 0.00,
        tax_inclusive: true,
        receipt_header: '',
        receipt_footer: '',
        auto_print_receipt: false,
        receipt_logo_path: null,
        receipt_logo_url: null,
        invoice_prefix: 'INV-',
        invoice_number_start: 1000,
        low_stock_alerts: true,
        low_stock_threshold: 10,
        allow_negative_stock: false,
        track_stock_expiry: false,
        require_customer_info: false,
        allow_discount: true,
        max_discount_percent: 20.00,
        allow_credit_sales: false,
        require_receipt_approval: false,
        enable_audit_log: true,
        session_timeout_minutes: 60,
        two_factor_auth: false,
      },
    };
  },
  computed: {
    isAdmin() {
      const userRole = localStorage.getItem('role');
      return userRole === 'Admin' || userRole === 'Administrator';
    },
  },
  mounted() {
    this.loadSettings();
  },
  methods: {
    async loadSettings() {
      this.loading = true;
      this.error = null;

      try {
        // Load personal settings
        const userResponse = await api.get('/api/settings/user');
        this.userSettings = { ...this.userSettings, ...userResponse.data };

        // Load company settings if admin
        if (this.isAdmin) {
          const companyResponse = await api.get('/api/settings/company');
          this.companySettings = { ...this.companySettings, ...companyResponse.data };
        }
      } catch (err) {
        this.error = err.response?.data?.error || 'Failed to load settings';
        console.error('Error loading settings:', err);
      } finally {
        this.loading = false;
      }
    },

    async savePersonalSettings() {
      this.saving = true;
      this.error = null;
      this.successMessage = null;

      try {
        await api.put('/api/settings/user', this.userSettings);
        this.successMessage = 'Personal settings saved successfully!';
        setTimeout(() => {
          this.successMessage = null;
        }, 3000);
      } catch (err) {
        this.error = err.response?.data?.error || 'Failed to save personal settings';
        console.error('Error saving personal settings:', err);
      } finally {
        this.saving = false;
      }
    },

    async saveCompanySettings() {
      this.saving = true;
      this.error = null;
      this.successMessage = null;

      try {
        await api.put('/api/settings/company', this.companySettings);
        this.successMessage = 'Company settings saved successfully!';
        setTimeout(() => {
          this.successMessage = null;
        }, 3000);
      } catch (err) {
        this.error = err.response?.data?.error || 'Failed to save company settings';
        console.error('Error saving company settings:', err);
      } finally {
        this.saving = false;
      }
    },

    async uploadReceiptLogo(event) {
      const file = event.target.files[0];
      if (!file) return;

      const formData = new FormData();
      formData.append('logo', file);

      try {
        const response = await api.post('/api/settings/company/upload-logo', formData);
        this.companySettings.receipt_logo_path = response.data.logo_path;
        this.companySettings.receipt_logo_url = response.data.logo_url;
        this.successMessage = 'Receipt logo uploaded successfully!';
        setTimeout(() => {
          this.successMessage = null;
        }, 3000);
      } catch (err) {
        this.error = err.response?.data?.error || 'Failed to upload logo';
        console.error('Error uploading logo:', err);
      }
    },

    async removeReceiptLogo() {
      try {
        await api.delete('/api/settings/company/remove-logo');
        this.companySettings.receipt_logo_path = null;
        this.companySettings.receipt_logo_url = null;
        this.successMessage = 'Receipt logo removed successfully!';
        setTimeout(() => {
          this.successMessage = null;
        }, 3000);
      } catch (err) {
        this.error = err.response?.data?.error || 'Failed to remove logo';
        console.error('Error removing logo:', err);
      }
    },
  },
};
</script>

<style scoped>
.settings-page {
  padding: 20px;
  max-width: 1200px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 30px;
}

.page-header h1 {
  font-size: 28px;
  color: #333;
  margin-bottom: 5px;
}

.page-header i {
  margin-right: 10px;
  color: #007bff;
}

.subtitle {
  color: #666;
  font-size: 14px;
}

.tabs {
  display: flex;
  gap: 10px;
  margin-bottom: 30px;
  border-bottom: 2px solid #e0e0e0;
}

.tab-button {
  padding: 12px 24px;
  background: transparent;
  border: none;
  border-bottom: 3px solid transparent;
  cursor: pointer;
  font-size: 16px;
  color: #666;
  transition: all 0.3s;
}

.tab-button:hover {
  color: #007bff;
}

.tab-button.active {
  color: #007bff;
  border-bottom-color: #007bff;
  font-weight: 600;
}

.tab-button i {
  margin-right: 8px;
}

.loading-container {
  text-align: center;
  padding: 40px;
}

.spinner {
  border: 4px solid #f3f3f3;
  border-top: 4px solid #007bff;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  animation: spin 1s linear infinite;
  margin: 0 auto 15px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.error-message, .success-message {
  padding: 15px;
  border-radius: 8px;
  margin-bottom: 20px;
}

.error-message {
  background: #fee;
  color: #c33;
  border-left: 4px solid #c33;
}

.success-message {
  background: #efe;
  color: #3c3;
  border-left: 4px solid #3c3;
}

.settings-content {
  background: white;
  border-radius: 8px;
  padding: 30px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.settings-section {
  margin-bottom: 40px;
  padding-bottom: 30px;
  border-bottom: 1px solid #e0e0e0;
}

.settings-section:last-child {
  border-bottom: none;
}

.settings-section h2 {
  font-size: 20px;
  color: #333;
  margin-bottom: 20px;
}

.settings-section h2 i {
  margin-right: 10px;
  color: #007bff;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: #555;
}

.form-control {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
}

.form-control:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
}

.form-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
}

.checkbox-group {
  display: flex;
  align-items: center;
}

.checkbox-label {
  display: flex;
  align-items: center;
  cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
  width: 18px;
  height: 18px;
  margin-right: 10px;
  cursor: pointer;
}

.checkbox-label span {
  font-weight: 500;
  color: #555;
}

.logo-upload {
  display: flex;
  align-items: center;
  gap: 15px;
}

.logo-preview {
  max-width: 150px;
  max-height: 100px;
  border: 1px solid #ddd;
  border-radius: 6px;
  padding: 5px;
}

.form-actions {
  margin-top: 30px;
  text-align: right;
}

.btn {
  padding: 12px 24px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 16px;
  transition: all 0.3s;
}

.btn-primary {
  background: #007bff;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #0056b3;
}

.btn-primary:disabled {
  background: #ccc;
  cursor: not-allowed;
}

.btn-danger {
  background: #dc3545;
  color: white;
}

.btn-danger:hover {
  background: #c82333;
}

.btn-sm {
  padding: 8px 16px;
  font-size: 14px;
}

.btn i {
  margin-right: 8px;
}
</style>
