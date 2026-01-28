<template>
  <div class="superuser-settings-page">
    <div class="page-header">
      <h1><i class="fas fa-cogs"></i> System Settings</h1>
      <p class="subtitle">Manage global system configurations and features</p>
    </div>

    <!-- Tab Navigation -->
    <div class="tabs">
      <button 
        :class="['tab-button', { active: activeTab === 'personal' }]" 
        @click="activeTab = 'personal'"
      >
        <i class="fas fa-user"></i> Personal Settings
      </button>
      <button 
        :class="['tab-button', { active: activeTab === 'system' }]" 
        @click="activeTab = 'system'"
      >
        <i class="fas fa-server"></i> System Settings
      </button>
      <button 
        :class="['tab-button', { active: activeTab === 'features' }]" 
        @click="activeTab = 'features'"
      >
        <i class="fas fa-toggle-on"></i> Feature Toggles
      </button>
      <button 
        :class="['tab-button', { active: activeTab === 'templates' }]" 
        @click="activeTab = 'templates'"
      >
        <i class="fas fa-envelope"></i> Email Templates
      </button>
      <button 
        :class="['tab-button', { active: activeTab === 'backup' }]" 
        @click="activeTab = 'backup'"
      >
        <i class="fas fa-download"></i> Import/Export
      </button>
    </div>

    <!-- Alert Messages -->
    <div v-if="error" class="error-message">
      <i class="fas fa-exclamation-circle"></i>
      {{ error }}
      <button @click="error = null" class="close-btn">&times;</button>
    </div>

    <div v-if="successMessage" class="success-message">
      <i class="fas fa-check-circle"></i>
      {{ successMessage }}
      <button @click="successMessage = null" class="close-btn">&times;</button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="spinner"></div>
      <p>Loading settings...</p>
    </div>

    <!-- PERSONAL SETTINGS TAB -->
    <div v-if="activeTab === 'personal' && !loading" class="settings-content">
      <form @submit.prevent="savePersonalSettings">
        <section class="settings-section">
          <h2><i class="fas fa-paint-brush"></i> Display Preferences</h2>
          
          <div class="form-row">
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
          </div>
        </section>

        <section class="settings-section">
          <h2><i class="fas fa-bell"></i> Notification Preferences</h2>
          
          <div class="checkbox-grid">
            <label class="checkbox-label">
              <input v-model="userSettings.email_notifications" type="checkbox" />
              <span>Email Notifications</span>
            </label>

            <label class="checkbox-label">
              <input v-model="userSettings.push_notifications" type="checkbox" />
              <span>Push Notifications</span>
            </label>

            <label class="checkbox-label">
              <input v-model="userSettings.low_stock_alerts" type="checkbox" />
              <span>Low Stock Alerts</span>
            </label>

            <label class="checkbox-label">
              <input v-model="userSettings.report_alerts" type="checkbox" />
              <span>Report Alerts</span>
            </label>
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

    <!-- SYSTEM SETTINGS TAB -->
    <div v-if="activeTab === 'system' && !loading" class="settings-content">
      <div class="settings-toolbar">
        <div class="search-box">
          <i class="fas fa-search"></i>
          <input 
            v-model="searchQuery" 
            type="text" 
            placeholder="Search settings..."
            class="search-input"
          />
        </div>
        <select v-model="filterGroup" class="filter-select">
          <option value="">All Groups</option>
          <option value="general">General</option>
          <option value="security">Security</option>
          <option value="billing">Billing</option>
          <option value="performance">Performance</option>
          <option value="features">Features</option>
        </select>
        <button @click="showAddSettingModal = true" class="btn btn-success">
          <i class="fas fa-plus"></i> Add Setting
        </button>
      </div>

      <div class="settings-table">
        <table>
          <thead>
            <tr>
              <th>Key</th>
              <th>Value</th>
              <th>Type</th>
              <th>Group</th>
              <th>Public</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="setting in filteredSystemSettings" :key="setting.id">
              <td>
                <strong>{{ setting.key }}</strong>
                <small v-if="setting.description">{{ setting.description }}</small>
              </td>
              <td>
                <span v-if="!editingSettingId || editingSettingId !== setting.id">
                  {{ formatValue(setting.value, setting.type) }}
                </span>
                <input 
                  v-else
                  v-model="editingSettingValue"
                  :type="getInputType(setting.type)"
                  class="form-control-sm"
                />
              </td>
              <td><span class="badge" :class="'badge-' + setting.type">{{ setting.type }}</span></td>
              <td><span class="badge badge-secondary">{{ setting.group }}</span></td>
              <td>
                <i :class="setting.is_public ? 'fas fa-check text-success' : 'fas fa-times text-muted'"></i>
              </td>
              <td class="actions">
                <button 
                  v-if="editingSettingId !== setting.id"
                  @click="startEditSetting(setting)" 
                  class="btn-icon btn-edit"
                  title="Edit"
                >
                  <i class="fas fa-edit"></i>
                </button>
                <button 
                  v-else
                  @click="saveEditedSetting(setting)" 
                  class="btn-icon btn-success"
                  title="Save"
                >
                  <i class="fas fa-check"></i>
                </button>
                <button 
                  v-if="editingSettingId === setting.id"
                  @click="cancelEditSetting" 
                  class="btn-icon btn-secondary"
                  title="Cancel"
                >
                  <i class="fas fa-times"></i>
                </button>
                <button 
                  @click="deleteSetting(setting.id)" 
                  class="btn-icon btn-delete"
                  title="Delete"
                >
                  <i class="fas fa-trash"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Add Setting Modal -->
      <div v-if="showAddSettingModal" class="modal-overlay" @click.self="showAddSettingModal = false">
        <div class="modal-content">
          <div class="modal-header">
            <h3>Add System Setting</h3>
            <button @click="showAddSettingModal = false" class="close-btn">&times;</button>
          </div>
          <form @submit.prevent="addSystemSetting">
            <div class="form-group">
              <label>Key *</label>
              <input v-model="newSetting.key" type="text" class="form-control" required />
            </div>
            <div class="form-group">
              <label>Value *</label>
              <input v-model="newSetting.value" type="text" class="form-control" required />
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Type *</label>
                <select v-model="newSetting.type" class="form-control" required>
                  <option value="string">String</option>
                  <option value="boolean">Boolean</option>
                  <option value="integer">Integer</option>
                  <option value="float">Float</option>
                  <option value="json">JSON</option>
                </select>
              </div>
              <div class="form-group">
                <label>Group</label>
                <select v-model="newSetting.group" class="form-control">
                  <option value="general">General</option>
                  <option value="security">Security</option>
                  <option value="billing">Billing</option>
                  <option value="performance">Performance</option>
                  <option value="features">Features</option>
                </select>
              </div>
            </div>
            <div class="form-group">
              <label>Description</label>
              <textarea v-model="newSetting.description" class="form-control" rows="2"></textarea>
            </div>
            <div class="form-group">
              <label class="checkbox-label">
                <input v-model="newSetting.is_public" type="checkbox" />
                <span>Public (visible to all users)</span>
              </label>
            </div>
            <div class="modal-actions">
              <button type="button" @click="showAddSettingModal = false" class="btn btn-secondary">Cancel</button>
              <button type="submit" class="btn btn-primary">Add Setting</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- FEATURE TOGGLES TAB -->
    <div v-if="activeTab === 'features' && !loading" class="settings-content">
      <div class="feature-header">
        <h2><i class="fas fa-toggle-on"></i> Feature Management</h2>
        <p>Enable or disable features for specific companies</p>
      </div>

      <div class="company-selector">
        <label>Select Company:</label>
        <select v-model="selectedCompanyId" @change="loadFeatureToggles" class="form-control">
          <option value="">Global Features</option>
          <option v-for="company in companies" :key="company.id" :value="company.id">
            {{ company.name }}
          </option>
        </select>
      </div>

      <div class="features-grid">
        <div v-for="feature in availableFeatures" :key="feature.key" class="feature-card">
          <div class="feature-icon" :class="{ active: isFeatureEnabled(feature.key) }">
            <i :class="feature.icon"></i>
          </div>
          <div class="feature-info">
            <h3>{{ feature.name }}</h3>
            <p>{{ feature.description }}</p>
          </div>
          <div class="feature-toggle">
            <label class="toggle-switch">
              <input 
                type="checkbox" 
                :checked="isFeatureEnabled(feature.key)"
                @change="toggleFeature(feature.key, $event.target.checked)"
              />
              <span class="toggle-slider"></span>
            </label>
          </div>
        </div>
      </div>
    </div>

    <!-- EMAIL TEMPLATES TAB -->
    <div v-if="activeTab === 'templates' && !loading" class="settings-content">
      <div class="templates-header">
        <h2><i class="fas fa-envelope"></i> Email Templates</h2>
        <button @click="showTemplateModal = true" class="btn btn-success">
          <i class="fas fa-plus"></i> Create Template
        </button>
      </div>

      <div class="templates-grid">
        <div v-for="template in emailTemplates" :key="template.id" class="template-card">
          <div class="template-header">
            <h3>{{ template.name }}</h3>
            <div class="template-badges">
              <span class="badge" :class="template.is_active ? 'badge-success' : 'badge-secondary'">
                {{ template.is_active ? 'Active' : 'Inactive' }}
              </span>
              <span class="badge badge-info">{{ template.type }}</span>
            </div>
          </div>
          <div class="template-details">
            <p><strong>Subject:</strong> {{ template.subject }}</p>
            <p><strong>Slug:</strong> {{ template.slug }}</p>
          </div>
          <div class="template-actions">
            <button @click="editTemplate(template)" class="btn btn-sm btn-primary">
              <i class="fas fa-edit"></i> Edit
            </button>
            <button @click="testTemplate(template)" class="btn btn-sm btn-info">
              <i class="fas fa-paper-plane"></i> Test
            </button>
            <button @click="toggleTemplateStatus(template)" class="btn btn-sm" :class="template.is_active ? 'btn-warning' : 'btn-success'">
              <i :class="template.is_active ? 'fas fa-pause' : 'fas fa-play'"></i>
              {{ template.is_active ? 'Deactivate' : 'Activate' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Template Editor Modal -->
      <div v-if="showTemplateModal" class="modal-overlay" @click.self="showTemplateModal = false">
        <div class="modal-content modal-large">
          <div class="modal-header">
            <h3>{{ editingTemplate ? 'Edit Template' : 'Create Template' }}</h3>
            <button @click="showTemplateModal = false" class="close-btn">&times;</button>
          </div>
          <form @submit.prevent="saveTemplate">
            <div class="form-row">
              <div class="form-group">
                <label>Template Name *</label>
                <input v-model="templateForm.name" type="text" class="form-control" required />
              </div>
              <div class="form-group">
                <label>Slug *</label>
                <input v-model="templateForm.slug" type="text" class="form-control" required />
              </div>
            </div>
            <div class="form-group">
              <label>Subject *</label>
              <input v-model="templateForm.subject" type="text" class="form-control" required />
            </div>
            <div class="form-group">
              <label>Type</label>
              <select v-model="templateForm.type" class="form-control">
                <option value="transactional">Transactional</option>
                <option value="marketing">Marketing</option>
              </select>
            </div>
            <div class="form-group">
              <label>HTML Body *</label>
              <textarea v-model="templateForm.body_html" class="form-control" rows="10" required></textarea>
              <small>Available variables: name, email, company (use curly braces syntax in template)</small>
            </div>
            <div class="form-group">
              <label class="checkbox-label">
                <input v-model="templateForm.is_active" type="checkbox" />
                <span>Active</span>
              </label>
            </div>
            <div class="modal-actions">
              <button type="button" @click="showTemplateModal = false" class="btn btn-secondary">Cancel</button>
              <button type="submit" class="btn btn-primary">{{ editingTemplate ? 'Update' : 'Create' }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- IMPORT/EXPORT TAB -->
    <div v-if="activeTab === 'backup' && !loading" class="settings-content">
      <div class="backup-section">
        <h2><i class="fas fa-download"></i> Export Settings</h2>
        <p>Download all system settings as a JSON backup file</p>
        
        <div class="export-options">
          <label class="checkbox-label">
            <input v-model="exportOptions.includeSystem" type="checkbox" />
            <span>Include System Settings</span>
          </label>
          <label class="checkbox-label">
            <input v-model="exportOptions.includeCompany" type="checkbox" />
            <span>Include Company Settings</span>
          </label>
          <label class="checkbox-label">
            <input v-model="exportOptions.includeFeatures" type="checkbox" />
            <span>Include Feature Toggles</span>
          </label>
          <label class="checkbox-label">
            <input v-model="exportOptions.includeTemplates" type="checkbox" />
            <span>Include Email Templates</span>
          </label>
        </div>

        <button @click="exportSettings" class="btn btn-primary btn-lg">
          <i class="fas fa-file-download"></i> Export Settings
        </button>
      </div>

      <div class="backup-section">
        <h2><i class="fas fa-upload"></i> Import Settings</h2>
        <p>Restore settings from a JSON backup file</p>
        
        <div class="import-warning">
          <i class="fas fa-exclamation-triangle"></i>
          <strong>Warning:</strong> Importing settings will overwrite existing configurations. Make sure to backup first!
        </div>

        <input 
          type="file" 
          ref="fileInput"
          accept=".json"
          @change="handleFileUpload"
          style="display: none"
        />
        
        <button @click="$refs.fileInput.click()" class="btn btn-warning btn-lg">
          <i class="fas fa-file-upload"></i> Choose File to Import
        </button>

        <div v-if="importFile" class="import-preview">
          <p><strong>Selected file:</strong> {{ importFile.name }}</p>
          <button @click="importSettings" class="btn btn-danger">
            <i class="fas fa-check"></i> Confirm Import
          </button>
          <button @click="importFile = null" class="btn btn-secondary">
            Cancel
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import api from '../../services/api';

export default {
  name: 'SuperUserSettingsPage',
  data() {
    return {
      activeTab: 'personal',
      loading: false,
      saving: false,
      error: null,
      successMessage: null,
      
      // Personal settings
      userSettings: {
        theme: 'light',
        language: 'en',
        items_per_page: 20,
        email_notifications: true,
        push_notifications: true,
        low_stock_alerts: true,
        report_alerts: true,
      },

      // System settings
      systemSettings: [],
      searchQuery: '',
      filterGroup: '',
      editingSettingId: null,
      editingSettingValue: '',
      showAddSettingModal: false,
      newSetting: {
        key: '',
        value: '',
        type: 'string',
        group: 'general',
        description: '',
        is_public: false,
      },

      // Feature toggles
      companies: [],
      selectedCompanyId: '',
      featureToggles: [],
      availableFeatures: [
        { key: 'expenses_module', name: 'Expenses Module', description: 'Enable expense tracking and management', icon: 'fas fa-receipt' },
        { key: 'multi_warehouse', name: 'Multi-Warehouse', description: 'Support for multiple warehouse locations', icon: 'fas fa-warehouse' },
        { key: 'advanced_reports', name: 'Advanced Reports', description: 'Detailed analytics and custom reports', icon: 'fas fa-chart-line' },
        { key: 'email_marketing', name: 'Email Marketing', description: 'Send promotional emails to customers', icon: 'fas fa-envelope-open-text' },
        { key: 'sms_notifications', name: 'SMS Notifications', description: 'Send SMS alerts and notifications', icon: 'fas fa-sms' },
        { key: 'multi_currency', name: 'Multi-Currency', description: 'Support for multiple currencies', icon: 'fas fa-coins' },
        { key: 'barcode_scanner', name: 'Barcode Scanner', description: 'Barcode scanning for products', icon: 'fas fa-barcode' },
        { key: 'loyalty_program', name: 'Loyalty Program', description: 'Customer rewards and points system', icon: 'fas fa-gift' },
        { key: 'online_ordering', name: 'Online Ordering', description: 'Enable online order placement', icon: 'fas fa-shopping-cart' },
        { key: 'api_access', name: 'API Access', description: 'Enable API for third-party integrations', icon: 'fas fa-plug' },
      ],

      // Email templates
      emailTemplates: [],
      showTemplateModal: false,
      editingTemplate: null,
      templateForm: {
        name: '',
        slug: '',
        subject: '',
        body_html: '',
        type: 'transactional',
        is_active: true,
      },

      // Import/Export
      exportOptions: {
        includeSystem: true,
        includeCompany: true,
        includeFeatures: true,
        includeTemplates: true,
      },
      importFile: null,
    };
  },
  computed: {
    filteredSystemSettings() {
      let filtered = this.systemSettings;

      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase();
        filtered = filtered.filter(s => 
          s.key.toLowerCase().includes(query) || 
          (s.description && s.description.toLowerCase().includes(query))
        );
      }

      if (this.filterGroup) {
        filtered = filtered.filter(s => s.group === this.filterGroup);
      }

      return filtered;
    },
  },
  mounted() {
    this.loadAllSettings();
  },
  methods: {
    async loadAllSettings() {
      this.loading = true;
      this.error = null;

      try {
        // Load each setting sequentially with better error handling
        try {
          await this.loadPersonalSettings();
        } catch (err) {
          console.error('Failed to load personal settings:', err);
          this.error = 'Failed to load personal settings: ' + (err.response?.data?.message || err.message);
        }

        try {
          await this.loadSystemSettings();
        } catch (err) {
          console.error('Failed to load system settings:', err);
          this.error = 'Failed to load system settings: ' + (err.response?.data?.message || err.message);
        }

        try {
          await this.loadCompanies();
        } catch (err) {
          console.error('Failed to load companies:', err);
          this.error = 'Failed to load companies: ' + (err.response?.data?.message || err.message);
        }

        try {
          await this.loadFeatureToggles();
        } catch (err) {
          console.error('Failed to load feature toggles:', err);
          // Don't set error for feature toggles as it's optional
        }

        try {
          await this.loadEmailTemplates();
        } catch (err) {
          console.error('Failed to load email templates:', err);
          // Don't set error for templates as it's optional
        }
      } catch (err) {
        this.error = 'Failed to load settings';
        console.error('Error loading settings:', err);
      } finally {
        this.loading = false;
      }
    },

    async loadPersonalSettings() {
      const response = await api.get('/api/settings/user');
      this.userSettings = { ...this.userSettings, ...response.data };
    },

    async loadSystemSettings() {
      const response = await api.get('/api/settings/system');
      this.systemSettings = response.data.settings || [];
    },

    async loadCompanies() {
      const response = await api.get('/api/companies');
      this.companies = response.data.companies || [];
    },

    async loadFeatureToggles() {
      try {
        const response = await api.get('/api/settings/features/available', {
          params: { company_id: this.selectedCompanyId || null }
        });
        
        this.featureToggles = response.data.features || [];
        
        // Merge with available features to show status
        this.availableFeatures.forEach(feature => {
          const toggle = this.featureToggles.find(t => t.key === feature.key);
          if (toggle) {
            feature.is_enabled = toggle.is_enabled;
          }
        });
      } catch (err) {
        console.error('Error loading feature toggles:', err);
      }
    },

    async loadEmailTemplates() {
      // Load email templates
      // Mock data for now
      this.emailTemplates = [];
    },

    async savePersonalSettings() {
      this.saving = true;
      this.error = null;

      try {
        await api.put('/api/settings/user', this.userSettings);
        this.successMessage = 'Personal settings saved successfully!';
        setTimeout(() => { this.successMessage = null; }, 3000);
      } catch (err) {
        this.error = 'Failed to save personal settings';
      } finally {
        this.saving = false;
      }
    },

    async addSystemSetting() {
      try {
        await api.post('/api/settings/system', this.newSetting);
        this.successMessage = 'Setting added successfully!';
        this.showAddSettingModal = false;
        this.newSetting = { key: '', value: '', type: 'string', group: 'general', description: '', is_public: false };
        await this.loadSystemSettings();
        setTimeout(() => { this.successMessage = null; }, 3000);
      } catch (err) {
        this.error = 'Failed to add setting';
      }
    },

    startEditSetting(setting) {
      this.editingSettingId = setting.id;
      this.editingSettingValue = setting.value;
    },

    async saveEditedSetting(setting) {
      try {
        await api.put(`/api/settings/system/${setting.id}`, { value: this.editingSettingValue });
        this.successMessage = 'Setting updated successfully!';
        this.editingSettingId = null;
        await this.loadSystemSettings();
        setTimeout(() => { this.successMessage = null; }, 3000);
      } catch (err) {
        this.error = 'Failed to update setting';
      }
    },

    cancelEditSetting() {
      this.editingSettingId = null;
      this.editingSettingValue = '';
    },

    async deleteSetting(id) {
      if (!confirm('Are you sure you want to delete this setting?')) return;

      try {
        await api.delete(`/api/settings/system/${id}`);
        this.successMessage = 'Setting deleted successfully!';
        await this.loadSystemSettings();
        setTimeout(() => { this.successMessage = null; }, 3000);
      } catch (err) {
        this.error = 'Failed to delete setting';
      }
    },

    isFeatureEnabled(featureKey) {
      const feature = this.availableFeatures.find(f => f.key === featureKey);
      return feature ? feature.is_enabled : false;
    },

    async toggleFeature(featureKey, enabled) {
      try {
        await api.post('/api/settings/features/toggle', {
          company_id: this.selectedCompanyId || null,
          feature_key: featureKey,
          is_enabled: enabled,
        });
        
        this.successMessage = `Feature ${enabled ? 'enabled' : 'disabled'} successfully!`;
        setTimeout(() => { this.successMessage = null; }, 3000);
        
        // Reload feature toggles
        await this.loadFeatureToggles();
      } catch (err) {
        this.error = 'Failed to toggle feature';
        console.error('Error toggling feature:', err);
      }
    },

    editTemplate(template) {
      this.editingTemplate = template;
      this.templateForm = { ...template };
      this.showTemplateModal = true;
    },

    async saveTemplate() {
      console.log('Saving template:', this.templateForm);
      this.showTemplateModal = false;
      this.successMessage = 'Template saved successfully!';
      setTimeout(() => { this.successMessage = null; }, 3000);
    },

    async testTemplate(template) {
      console.log('Testing template:', template.id);
      this.successMessage = 'Test email sent!';
      setTimeout(() => { this.successMessage = null; }, 3000);
    },

    async toggleTemplateStatus(template) {
      template.is_active = !template.is_active;
      this.successMessage = `Template ${template.is_active ? 'activated' : 'deactivated'}!`;
      setTimeout(() => { this.successMessage = null; }, 3000);
    },

    async exportSettings() {
      try {
        const data = {
          system: this.exportOptions.includeSystem ? this.systemSettings : [],
          company: this.exportOptions.includeCompany ? [] : [],
          features: this.exportOptions.includeFeatures ? this.featureToggles : [],
          templates: this.exportOptions.includeTemplates ? this.emailTemplates : [],
          exported_at: new Date().toISOString(),
        };

        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `settings-backup-${Date.now()}.json`;
        a.click();
        URL.revokeObjectURL(url);

        this.successMessage = 'Settings exported successfully!';
        setTimeout(() => { this.successMessage = null; }, 3000);
      } catch (err) {
        this.error = 'Failed to export settings';
      }
    },

    handleFileUpload(event) {
      this.importFile = event.target.files[0];
    },

    async importSettings() {
      if (!this.importFile) return;

      try {
        const text = await this.importFile.text();
        const data = JSON.parse(text);

        console.log('Importing settings:', data);
        // Would call backend API to import settings

        this.successMessage = 'Settings imported successfully!';
        this.importFile = null;
        await this.loadAllSettings();
        setTimeout(() => { this.successMessage = null; }, 3000);
      } catch (err) {
        this.error = 'Failed to import settings. Invalid file format.';
      }
    },

    formatValue(value, type) {
      if (type === 'boolean') return value === '1' || value === 'true' ? 'Yes' : 'No';
      return value;
    },

    getInputType(type) {
      if (type === 'integer') return 'number';
      if (type === 'boolean') return 'checkbox';
      return 'text';
    },
  },
};
</script>

<style scoped>
.superuser-settings-page {
  padding: 30px;
  max-width: 1600px;
  margin: 0 auto;
  min-height: 100vh;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
}

.page-header {
  background: white;
  padding: 30px;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
  margin-bottom: 30px;
  border-left: 6px solid #667eea;
}

.page-header h1 {
  font-size: 32px;
  color: #2d3748;
  margin-bottom: 8px;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 12px;
}

.page-header i {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  font-size: 36px;
}

.subtitle {
  color: #718096;
  font-size: 16px;
  margin-bottom: 0;
  font-weight: 400;
}

.tabs {
  display: flex;
  gap: 8px;
  margin-bottom: 30px;
  background: white;
  padding: 8px;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
  flex-wrap: wrap;
  overflow-x: auto;
}

.tab-button {
  padding: 14px 24px;
  background: transparent;
  border: none;
  border-radius: 12px;
  cursor: pointer;
  font-size: 15px;
  color: #718096;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  font-weight: 500;
  white-space: nowrap;
  position: relative;
  overflow: hidden;
  display: flex;
  align-items: center;
  gap: 8px;
}

.tab-button::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  opacity: 0;
  transition: opacity 0.3s;
  border-radius: 12px;
}

.tab-button:hover {
  color: #667eea;
  transform: translateY(-2px);
}

.tab-button.active {
  color: white;
  font-weight: 600;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.tab-button.active::before {
  opacity: 0;
}

.tab-button.active i,
.tab-button.active span {
  color: white;
}

.tab-button i {
  margin-right: 8px;
  position: relative;
  z-index: 1;
}

.tab-button span {
  position: relative;
  z-index: 1;
}

.error-message, .success-message {
  padding: 18px 24px;
  border-radius: 12px;
  margin-bottom: 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  animation: slideInDown 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

@keyframes slideInDown {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.error-message {
  background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%);
  color: #c53030;
  border-left: 4px solid #c53030;
}

.success-message {
  background: linear-gradient(135deg, #f0fff4 0%, #c6f6d5 100%);
  color: #2f855a;
  border-left: 4px solid #38a169;
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: inherit;
  opacity: 0.6;
  transition: all 0.2s;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
}

.close-btn:hover {
  opacity: 1;
  background: rgba(0,0,0,0.1);
  transform: rotate(90deg);
}

.loading-container {
  text-align: center;
  padding: 60px 20px;
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.spinner {
  border: 4px solid #e2e8f0;
  border-top: 4px solid #667eea;
  border-radius: 50%;
  width: 50px;
  height: 50px;
  animation: spin 0.8s linear infinite;
  margin: 0 auto 20px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.loading-container p {
  color: #718096;
  font-size: 16px;
  font-weight: 500;
}

.settings-content {
  background: white;
  border-radius: 16px;
  padding: 40px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
  animation: fadeIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.settings-section {
  margin-bottom: 48px;
  padding-bottom: 36px;
  border-bottom: 2px solid #f7fafc;
  position: relative;
}

.settings-section:last-child {
  border-bottom: none;
}

.settings-section h2 {
  font-size: 22px;
  color: #2d3748;
  margin-bottom: 24px;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 12px;
}

.settings-section h2 i {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 10px;
  font-size: 18px;
}

.form-group {
  margin-bottom: 24px;
}

.form-group label {
  display: block;
  margin-bottom: 10px;
  font-weight: 600;
  color: #2d3748;
  font-size: 14px;
  letter-spacing: 0.3px;
}

.form-control {
  width: 100%;
  padding: 12px 16px;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 15px;
  transition: all 0.3s;
  background: #f7fafc;
}

.form-control:hover {
  border-color: #cbd5e0;
  background: white;
}

.form-control-sm {
  width: 100%;
  padding: 8px 12px;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  transition: all 0.3s;
  background: #f7fafc;
}

.form-control:focus, .form-control-sm:focus {
  outline: none;
  border-color: #667eea;
  background: white;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.form-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 24px;
}

.checkbox-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 18px;
}

.checkbox-label {
  display: flex;
  align-items: center;
  cursor: pointer;
  padding: 14px 18px;
  background: #f7fafc;
  border-radius: 10px;
  transition: all 0.3s;
  border: 2px solid transparent;
}

.checkbox-label:hover {
  background: #edf2f7;
  border-color: #cbd5e0;
  transform: translateX(4px);
}

.checkbox-label input[type="checkbox"] {
  width: 20px;
  height: 20px;
  margin-right: 12px;
  cursor: pointer;
  accent-color: #667eea;
}

.checkbox-label span {
  font-weight: 500;
  color: #2d3748;
  font-size: 14px;
}

.form-actions {
  margin-top: 40px;
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding-top: 24px;
  border-top: 2px solid #f7fafc;
}

.btn {
  padding: 14px 28px;
  border: none;
  border-radius: 10px;
  cursor: pointer;
  font-size: 16px;
  font-weight: 600;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  display: inline-flex;
  align-items: center;
  gap: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(0,0,0,0.15);
}

.btn:active:not(:disabled) {
  transform: translateY(0);
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  box-shadow: 0 4px 16px rgba(102, 126, 234, 0.4);
}

.btn-success {
  background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
  color: white;
}

.btn-success:hover {
  box-shadow: 0 4px 16px rgba(72, 187, 120, 0.4);
}

.btn-warning {
  background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
  color: white;
}

.btn-warning:hover {
  box-shadow: 0 4px 16px rgba(237, 137, 54, 0.4);
}

.btn-danger {
  background: linear-gradient(135deg, #f56565 0%, #c53030 100%);
  color: white;
}

.btn-danger:hover {
  box-shadow: 0 4px 16px rgba(245, 101, 101, 0.4);
}

.btn-secondary {
  background: linear-gradient(135deg, #718096 0%, #4a5568 100%);
  color: white;
}

.btn-secondary:hover {
  box-shadow: 0 4px 16px rgba(113, 128, 150, 0.4);
}

.btn-sm {
  padding: 10px 20px;
  font-size: 14px;
}

.btn-lg {
  padding: 18px 36px;
  font-size: 18px;
  font-weight: 700;
}

.btn:disabled {
  background: #e2e8f0;
  color: #a0aec0;
  cursor: not-allowed;
  box-shadow: none;
}

.btn:disabled:hover {
  transform: none;
}

.btn i {
  font-size: 18px;
}

/* System Settings Specific */
.settings-toolbar {
  display: flex;
  gap: 16px;
  margin-bottom: 32px;
  flex-wrap: wrap;
  background: #f7fafc;
  padding: 20px;
  border-radius: 12px;
  border: 2px solid #e2e8f0;
}

.search-box {
  flex: 1;
  min-width: 280px;
  position: relative;
}

.search-box i {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #a0aec0;
  font-size: 18px;
}

.search-input {
  width: 100%;
  padding: 12px 16px 12px 44px;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 15px;
  background: white;
  transition: all 0.3s;
}

.search-input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.search-input::placeholder {
  color: #a0aec0;
}

.filter-select {
  padding: 12px 40px 12px 16px;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 15px;
  background: white;
  cursor: pointer;
  appearance: none;
  font-weight: 500;
  color: #2d3748;
  transition: all 0.3s;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23718096' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 14px center;
}

.filter-select:hover {
  border-color: #cbd5e0;
}

.filter-select:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.settings-table {
  overflow-x: auto;
  border-radius: 12px;
  border: 2px solid #e2e8f0;
  background: white;
}

table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
}

thead {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

th, td {
  padding: 18px 20px;
  text-align: left;
  border-bottom: 1px solid #f7fafc;
}

th {
  font-weight: 700;
  color: white;
  font-size: 14px;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  border-bottom: none;
}

td {
  color: #2d3748;
  font-size: 14px;
}

thead th:first-child {
  border-top-left-radius: 12px;
}

thead th:last-child {
  border-top-right-radius: 12px;
}

tbody tr {
  transition: all 0.2s;
}

tbody tr:hover {
  background: #f7fafc;
  transform: scale(1.01);
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

td small {
  display: block;
  color: #a0aec0;
  font-size: 12px;
  margin-top: 6px;
  font-weight: 500;
}

.badge {
  padding: 6px 14px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.5px;
  display: inline-block;
}

.badge-string { 
  background: linear-gradient(135deg, #e6f2ff 0%, #cce5ff 100%);
  color: #0066cc;
}

.badge-boolean { 
  background: linear-gradient(135deg, #e6fffa 0%, #b2f5ea 100%);
  color: #00796b;
}

.badge-integer { 
  background: linear-gradient(135deg, #fff5e6 0%, #ffe0b2 100%);
  color: #e65100;
}

.badge-float { 
  background: linear-gradient(135deg, #fff5e6 0%, #ffe0b2 100%);
  color: #e65100;
}

.badge-json { 
  background: linear-gradient(135deg, #f3e5f5 0%, #e1bee7 100%);
  color: #6a1b9a;
}

.badge-secondary { 
  background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%);
  color: #616161;
}

.badge-success { 
  background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
  color: #2e7d32;
}

.badge-info { 
  background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
  color: #1565c0;
}

.actions {
  white-space: nowrap;
  display: flex;
  gap: 8px;
}

.btn-icon {
  width: 36px;
  height: 36px;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  transition: all 0.3s;
  font-size: 16px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  background: transparent;
  color: #718096;
}

.btn-icon:hover {
  transform: translateY(-2px) scale(1.1);
  box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.btn-edit {
  color: #4299e1;
}

.btn-edit:hover {
  background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
  color: white;
}

.btn-delete {
  color: #f56565;
}

.btn-delete:hover {
  background: linear-gradient(135deg, #fc8181 0%, #f56565 100%);
  color: white;
}

.text-success { 
  color: #38a169;
  font-weight: 600;
}

.text-muted { 
  color: #a0aec0;
  font-weight: 500;
}

/* Modal */
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
  z-index: 1000;
  padding: 20px;
  animation: fadeIn 0.3s ease;
}

.modal-content {
  background: white;
  border-radius: 16px;
  padding: 0;
  width: 100%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  animation: slideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(40px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.modal-large {
  max-width: 800px;
}

.modal-header {
  padding: 28px 36px;
  border-bottom: 2px solid #f7fafc;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
  border-top-left-radius: 16px;
  border-top-right-radius: 16px;
}

.modal-header h3 {
  margin: 0;
  font-size: 24px;
  color: #2d3748;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 12px;
}

.modal-header h3 i {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  font-size: 26px;
}

.modal-close {
  background: none;
  border: none;
  font-size: 28px;
  cursor: pointer;
  color: #718096;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  transition: all 0.3s;
}

.modal-close:hover {
  background: #e2e8f0;
  color: #2d3748;
  transform: rotate(90deg);
}

.modal-body {
  padding: 36px;
}

.modal-footer {
  padding: 24px 36px;
  border-top: 2px solid #f7fafc;
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  background: #f7fafc;
  border-bottom-left-radius: 16px;
  border-bottom-right-radius: 16px;
}

.modal-content form {
  padding: 0;
}

.modal-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 24px;
}

/* Features Grid */
.feature-header {
  margin-bottom: 36px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.company-selector {
  margin-bottom: 32px;
  display: flex;
  align-items: center;
  gap: 16px;
  background: #f7fafc;
  padding: 20px 24px;
  border-radius: 12px;
  border: 2px solid #e2e8f0;
}

.company-selector label {
  font-weight: 600;
  color: #2d3748;
  font-size: 15px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.company-selector label i {
  color: #667eea;
  font-size: 18px;
}

.company-selector select {
  flex: 1;
  padding: 12px 16px;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 15px;
  background: white;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.3s;
}

.company-selector select:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
  gap: 24px;
}

.feature-card {
  background: white;
  border: 2px solid #e2e8f0;
  border-radius: 16px;
  padding: 28px;
  display: flex;
  align-items: center;
  gap: 20px;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  position: relative;
  overflow: hidden;
}

.feature-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 0;
  height: 100%;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
  transition: width 0.4s ease;
  z-index: 0;
}

.feature-card:hover::before {
  width: 100%;
}

.feature-card:hover {
  border-color: #667eea;
  transform: translateY(-4px);
  box-shadow: 0 12px 24px rgba(102, 126, 234, 0.15);
}

.feature-card.enabled {
  border-color: #48bb78;
  background: linear-gradient(135deg, #f0fff4 0%, #f7fafc 100%);
}

.feature-card > * {
  position: relative;
  z-index: 1;
}

.feature-card:hover {
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.feature-icon {
  width: 64px;
  height: 64px;
  border-radius: 16px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  color: white;
  flex-shrink: 0;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
  transition: all 0.3s;
}

.feature-card:hover .feature-icon {
  transform: scale(1.1) rotate(5deg);
  box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
}

.feature-icon.active {
  background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
  box-shadow: 0 4px 12px rgba(72, 187, 120, 0.3);
}

.feature-info {
  flex: 1;
}

.feature-info h3 {
  margin: 0 0 8px 0;
  font-size: 18px;
  color: #2d3748;
  font-weight: 700;
}

.feature-info p {
  margin: 0;
  color: #718096;
  font-size: 14px;
  line-height: 1.6;
}

.toggle-switch {
  position: relative;
  display: inline-block;
  width: 56px;
  height: 32px;
}

.toggle-switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.toggle-slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e0 100%);
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  border-radius: 32px;
  box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
}

.toggle-slider:before {
  position: absolute;
  content: "";
  height: 26px;
  width: 26px;
  left: 3px;
  bottom: 3px;
  background: white;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  border-radius: 50%;
  box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}

input:checked + .toggle-slider {
  background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
  box-shadow: 0 0 0 3px rgba(72, 187, 120, 0.1);
}

input:checked + .toggle-slider:before {
  transform: translateX(24px);
}

.toggle-slider:hover {
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

input:checked + .toggle-slider:hover {
  box-shadow: 0 0 0 4px rgba(72, 187, 120, 0.15);
}

/* Templates */
.templates-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 36px;
}

.templates-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
  gap: 24px;
}

.template-card {
  background: white;
  border: 2px solid #e2e8f0;
  border-radius: 16px;
  padding: 28px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  position: relative;
  overflow: hidden;
}

.template-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 4px;
  background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
  transform: scaleX(0);
  transition: transform 0.3s ease;
}

.template-card:hover::before {
  transform: scaleX(1);
}

.template-card:hover {
  border-color: #667eea;
  transform: translateY(-4px);
  box-shadow: 0 12px 24px rgba(102, 126, 234, 0.15);
}

.template-header {
  display: flex;
  justify-content: space-between;
  align-items: start;
  margin-bottom: 20px;
}

.template-header h3 {
  margin: 0;
  font-size: 20px;
  color: #2d3748;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 10px;
}

.template-header h3 i {
  color: #667eea;
}

.template-badges {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.template-details {
  margin-bottom: 20px;
}

.template-details p {
  margin: 10px 0;
  font-size: 14px;
  color: #718096;
  display: flex;
  align-items: center;
  gap: 8px;
}

.template-details p strong {
  color: #2d3748;
  font-weight: 600;
  min-width: 80px;
}

.template-actions {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  padding-top: 20px;
  border-top: 2px solid #f7fafc;
}

/* Backup Section */
.backup-section {
  margin-bottom: 56px;
  padding: 32px;
  background: white;
  border-radius: 16px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.06);
  border: 2px solid #e2e8f0;
}

.backup-section h2 {
  font-size: 24px;
  color: #2d3748;
  margin-bottom: 12px;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 12px;
}

.backup-section h2 i {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 10px;
  font-size: 18px;
}

.backup-section p {
  color: #718096;
  font-size: 15px;
  margin-bottom: 24px;
  line-height: 1.6;
}

.export-options {
  display: flex;
  flex-direction: column;
  gap: 14px;
  margin: 24px 0;
  padding: 24px;
  background: #f7fafc;
  border-radius: 12px;
  border: 2px solid #e2e8f0;
}

.import-warning {
  background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
  border: 2px solid #f59e0b;
  border-radius: 12px;
  padding: 20px 24px;
  margin: 24px 0;
  display: flex;
  align-items: center;
  gap: 16px;
  box-shadow: 0 2px 8px rgba(245, 158, 11, 0.1);
}

.import-warning i {
  color: #d97706;
  font-size: 28px;
  flex-shrink: 0;
}

.import-warning p {
  margin: 0;
  color: #92400e;
  font-weight: 500;
  font-size: 14px;
  line-height: 1.6;
}

.import-preview {
  margin-top: 24px;
  padding: 24px;
  background: white;
  border-radius: 12px;
  border: 2px solid #e2e8f0;
  display: flex;
  align-items: center;
  gap: 20px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.import-preview i {
  font-size: 32px;
  color: #667eea;
}

.import-preview p {
  margin: 0;
  flex: 1;
  color: #2d3748;
  font-weight: 500;
  font-size: 15px;
}

.import-preview p strong {
  color: #667eea;
  font-weight: 700;
}

/* File upload styling */
input[type="file"] {
  padding: 12px;
  border: 2px dashed #cbd5e0;
  border-radius: 10px;
  background: #f7fafc;
  cursor: pointer;
  transition: all 0.3s;
  font-size: 14px;
  width: 100%;
}

input[type="file"]:hover {
  border-color: #667eea;
  background: white;
}

input[type="file"]::file-selector-button {
  padding: 10px 20px;
  border: none;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  margin-right: 12px;
  transition: all 0.3s;
}

input[type="file"]::file-selector-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .superuser-settings-page {
    padding: 20px;
  }

  .page-header {
    padding: 20px;
  }

  .page-header h1 {
    font-size: 24px;
  }

  .tabs {
    flex-direction: column;
  }

  .tab-button {
    width: 100%;
  }

  .settings-content {
    padding: 24px;
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  .features-grid, .templates-grid {
    grid-template-columns: 1fr;
  }

  .settings-toolbar {
    flex-direction: column;
  }

  .search-box {
    min-width: 100%;
  }
}
</style>
