<template>
  <div class="messaging-page">
    <div class="page-header">
      <div class="header-content">
        <h1>📧 Messaging Center</h1>
        <p>Send SMS & Email campaigns to your customers, suppliers, and staff</p>
      </div>
      <button class="btn btn-primary" @click="showTemplateModal = true">
        ➕ New Template
      </button>
    </div>

    <!-- Tab Navigation -->
    <div class="tabs-container">
      <div class="tabs">
        <button 
          v-for="tab in tabs" 
          :key="tab.id"
          class="tab-button"
          :class="{ active: activeTab === tab.id }"
          @click="activeTab = tab.id"
        >
          {{ tab.icon }} {{ tab.label }}
          <span class="badge" v-if="tab.badge">{{ tab.badge }}</span>
        </button>
      </div>
    </div>

    <!-- TAB: Send Message -->
    <div v-if="activeTab === 'send'" class="tab-content">
      <div class="send-message-form">
        <div class="form-section">
          <h3>📨 Send Message</h3>
          
          <!-- Message Type Selection -->
          <div class="form-group">
            <label>Message Type</label>
            <div class="radio-group">
              <label class="radio-label">
                <input v-model="sendForm.messageType" type="radio" value="single">
                Send to Single Recipient
              </label>
              <label class="radio-label">
                <input v-model="sendForm.messageType" type="radio" value="bulk">
                Send to Multiple Recipients
              </label>
            </div>
          </div>

          <!-- Channel Selection -->
          <div class="form-group">
            <label>Channel</label>
            <select v-model="sendForm.channel">
              <option value="email">📧 Email</option>
              <option value="sms">📱 SMS</option>
              <option value="both">📧 📱 Both</option>
            </select>
          </div>

          <!-- Content Type -->
          <div class="form-group">
            <label>Content Type</label>
            <div class="radio-group">
              <label class="radio-label">
                <input v-model="sendForm.contentType" type="radio" value="template">
                Use Template
              </label>
              <label class="radio-label">
                <input v-model="sendForm.contentType" type="radio" value="custom">
                Custom Message
              </label>
            </div>
          </div>

          <!-- Template Selection -->
          <div v-if="sendForm.contentType === 'template'" class="form-group">
            <label>Select Template</label>
            <select v-model="sendForm.selectedTemplateId">
              <option value="">-- Choose a template --</option>
              <optgroup v-for="cat in templatesByCategory" :key="cat.category" :label="cat.category">
                <option v-for="t in cat.templates" :key="t.id" :value="t.id">
                  {{ t.name }}
                </option>
              </optgroup>
            </select>
            
            <!-- Template Preview -->
            <div v-if="selectedTemplate && sendForm.contentType === 'template'" class="template-preview">
              <h4>Template Preview</h4>
              <div class="preview-item" v-if="selectedTemplate.email_body">
                <strong>Email Subject:</strong> {{ selectedTemplate.email_subject }}
              </div>
              <div class="preview-item" v-if="selectedTemplate.sms_body">
                <strong>SMS Body:</strong> {{ selectedTemplate.sms_body }}
              </div>
            </div>

            <!-- Variables Input -->
            <div v-if="selectedTemplate && selectedTemplate.variables.length > 0" class="form-group">
              <label>Template Variables</label>
              <div v-for="variable in selectedTemplate.variables" :key="variable" class="variable-input">
                <input 
                  v-model="sendForm.variables[variable]"
                  type="text"
                  :placeholder="variable"
                  class="input-field"
                >
              </div>
            </div>
          </div>

          <!-- Custom Message -->
          <div v-else class="form-group">
            <label>Message Subject (for Email)</label>
            <input v-model="sendForm.customSubject" type="text" class="input-field" placeholder="Email subject">
            
            <label>Message Body</label>
            <textarea v-model="sendForm.customBody" class="textarea-field" placeholder="Your message..." rows="6"></textarea>
          </div>

          <!-- Single Recipient -->
          <div v-if="sendForm.messageType === 'single'" class="form-group">
            <label>Recipient Name</label>
            <input v-model="sendForm.recipientName" type="text" class="input-field" placeholder="Customer name">
            
            <label v-if="sendForm.channel !== 'sms'">Email Address</label>
            <input v-if="sendForm.channel !== 'sms'" v-model="sendForm.recipientEmail" type="email" class="input-field" placeholder="customer@example.com">
            
            <label v-if="sendForm.channel !== 'email'">Phone Number</label>
            <input v-if="sendForm.channel !== 'email'" v-model="sendForm.recipientPhone" type="tel" class="input-field" placeholder="+254700000000">
          </div>

          <!-- Bulk Recipients -->
          <div v-else class="form-group bulk-recipients-section">
            <!-- System Customers Checklist -->
            <div class="recipient-panel">
              <div class="panel-header">
                <label>System Customers <span class="badge-count" v-if="selectedCustomerIds.size > 0">{{ selectedCustomerIds.size }} selected</span></label>
                <div class="panel-controls">
                  <button type="button" class="btn-mini" @click="selectAllFiltered" :disabled="filteredCustomers.length === 0">All</button>
                  <button type="button" class="btn-mini btn-clear" @click="clearCustomerSelection" :disabled="selectedCustomerIds.size === 0">Clear</button>
                </div>
              </div>
              <input
                v-model="customerSearch"
                type="search"
                class="input-field customer-search"
                placeholder="🔍 Search by name, email or phone..."
              />
              <div class="customer-checklist" v-if="!customersLoading">
                <div v-if="filteredCustomers.length === 0" class="checklist-empty">
                  {{ customers.length === 0 ? 'No customers found in system' : 'No matches for your search' }}
                </div>
                <label
                  v-for="c in filteredCustomers"
                  :key="c.id"
                  class="customer-row"
                  :class="{ selected: selectedCustomerIds.has(c.id) }"
                >
                  <input type="checkbox" :checked="selectedCustomerIds.has(c.id)" @change="toggleCustomer(c.id)" />
                  <span class="c-name">{{ c.name }}</span>
                  <span class="c-meta">
                    <span v-if="c.email">{{ c.email }}</span>
                    <span v-if="c.phone">{{ c.phone }}</span>
                  </span>
                </label>
              </div>
              <div v-else class="checklist-empty"><i class="fas fa-spinner fa-spin"></i> Loading customers...</div>
            </div>

            <!-- Custom/Extra Recipients -->
            <div class="recipient-panel" style="margin-top:1rem">
              <label class="panel-header">
                <span>Extra Custom Recipients <small>(one email or phone per line)</small></span>
              </label>
              <textarea
                v-model="sendForm.bulkRecipients"
                class="textarea-field"
                rows="5"
                placeholder="+254700000000&#10;another@example.com&#10;+254711111111"
              ></textarea>
            </div>

            <!-- Selected Summary -->
            <div v-if="selectedCustomerIds.size > 0 || sendForm.bulkRecipients.trim()" class="recipient-summary">
              <strong>Recipients:</strong>
              {{ selectedCustomerIds.size }} system customer(s)
              <span v-if="customRecipientCount > 0">+ {{ customRecipientCount }} custom</span>
              = <strong>{{ totalRecipientCount }}</strong> total
            </div>
          </div>

          <!-- Campaign Name -->
          <div class="form-group">
            <label>Campaign Name (optional)</label>
            <input v-model="sendForm.campaignName" type="text" class="input-field" placeholder="e.g., Summer Sale 2024">
          </div>

          <!-- Send Button -->
          <div class="form-actions">
            <button @click="testTemplate" v-if="sendForm.contentType === 'template'" class="btn btn-secondary">
              👁️ Preview Template
            </button>
            <button @click="sendMessage" :disabled="!canSendMessage" class="btn btn-success">
              ✉️ Send Message
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB: Templates -->
    <div v-if="activeTab === 'templates'" class="tab-content">
      <div class="templates-grid">
        <div v-if="templates.length === 0" class="empty-state">
          <p>No templates created yet</p>
          <button @click="showTemplateModal = true" class="btn btn-primary">Create First Template</button>
        </div>

        <div v-for="template in templates" :key="template.id" class="template-card">
          <div class="template-header">
            <h4>{{ template.name }}</h4>
            <span class="badge" :class="`badge-${template.type}`">{{ template.type }}</span>
          </div>
          <p class="template-category">{{ template.category }}</p>
          <div class="template-preview-mini">
            <small v-if="template.email_subject">📧 {{ template.email_subject.substring(0, 50) }}...</small>
            <small v-if="template.sms_body">📱 {{ template.sms_body.substring(0, 50) }}...</small>
          </div>
          <div class="template-actions">
            <button @click="editTemplate(template)" class="btn btn-small btn-secondary">Edit</button>
            <button @click="deleteTemplate(template)" class="btn btn-small btn-danger">Delete</button>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB: Message History -->
    <div v-if="activeTab === 'history'" class="tab-content">
      <div class="filters">
        <input v-model="historyFilters.search" type="text" placeholder="Search messages..." class="input-field">
        <select v-model="historyFilters.type" class="input-field">
          <option value="">All Types</option>
          <option value="email">Email</option>
          <option value="sms">SMS</option>
        </select>
        <select v-model="historyFilters.status" class="input-field">
          <option value="">All Status</option>
          <option value="sent">Sent</option>
          <option value="failed">Failed</option>
          <option value="pending">Pending</option>
        </select>
      </div>

      <div class="table-container">
        <table v-if="messageLogs.length > 0" class="table">
          <thead>
            <tr>
              <th>Type</th>
              <th>Recipient</th>
              <th>Status</th>
              <th>Campaign</th>
              <th>Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="log in messageLogs" :key="log.id" class="log-row">
              <td>
                <span class="badge">{{ log.type === 'email' ? '📧' : '📱' }} {{ log.type }}</span>
              </td>
              <td>
                {{ log.recipient_name || log.recipient_contact }}
              </td>
              <td>
                <span class="status-badge" :class="`status-${log.status}`">
                  {{ log.status }}
                </span>
              </td>
              <td>{{ log.campaign_type || '—' }}</td>
              <td>{{ formatDate(log.created_at) }}</td>
              <td>
                <button @click="viewMessageDetails(log)" class="btn btn-small">View</button>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-else class="empty-state">
          <p>No messages yet</p>
        </div>
      </div>
    </div>

    <!-- TAB: Statistics -->
    <div v-if="activeTab === 'stats'" class="tab-content">
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-value">{{ stats.total_sent || 0 }}</div>
          <div class="stat-label">Messages Sent</div>
          <div class="stat-icon">✉️</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ stats.total_failed || 0 }}</div>
          <div class="stat-label">Failed</div>
          <div class="stat-icon">❌</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ stats.total_pending || 0 }}</div>
          <div class="stat-label">Pending</div>
          <div class="stat-icon">⏳</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ calculateSuccessRate }}%</div>
          <div class="stat-label">Success Rate</div>
          <div class="stat-icon">📊</div>
        </div>
      </div>

      <div class="stats-by-type">
        <h3>Breakdown by Type</h3>
        <div class="type-breakdown">
          <div v-for="item in stats.by_type" :key="item.type" class="breakdown-item">
            <span class="type-name">{{ item.type === 'email' ? '📧 Email' : '📱 SMS' }}</span>
            <span class="type-count">{{ item.count }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Template Modal -->
    <div v-if="showTemplateModal" class="modal-overlay" @click="closeTemplateModal">
      <div class="modal" @click.stop>
        <button class="close-button" @click="closeTemplateModal">✕</button>
        <h2>{{ editingTemplateId ? 'Edit Template' : 'Create New Template' }}</h2>

        <div class="form-group">
          <label>Template Name</label>
          <input v-model="templateForm.name" type="text" class="input-field" placeholder="e.g., Welcome Email">
        </div>

        <div class="form-group">
          <label>Category</label>
          <select v-model="templateForm.category">
            <option value="promotional">Promotional</option>
            <option value="transactional">Transactional</option>
            <option value="notification">Notification</option>
            <option value="reminder">Reminder</option>
          </select>
        </div>

        <div class="form-group">
          <label>Type</label>
          <select v-model="templateForm.type">
            <option value="email">Email Only</option>
            <option value="sms">SMS Only</option>
            <option value="both">Both Email & SMS</option>
          </select>
        </div>

        <div v-if="templateForm.type !== 'sms'" class="form-group">
          <label>Email Subject</label>
          <input v-model="templateForm.email_subject" type="text" class="input-field" placeholder="Email subject with {{variables}}">
          <label>Email Body</label>
          <textarea v-model="templateForm.email_body" class="textarea-field" rows="6" placeholder="Email content with {{variables}}"></textarea>
        </div>

        <div v-if="templateForm.type !== 'email'" class="form-group">
          <label>SMS Body</label>
          <textarea v-model="templateForm.sms_body" class="textarea-field" rows="4" placeholder="SMS content with {{variables}}"></textarea>
          <small>Recommended: under 160 characters</small>
        </div>

        <div class="form-actions">
          <button @click="closeTemplateModal" class="btn btn-secondary">Cancel</button>
          <button @click="saveTemplate" class="btn btn-primary">Save Template</button>
        </div>
      </div>
    </div>

    <!-- Message Details Modal -->
    <div v-if="showDetailsModal && selectedMessage" class="modal-overlay" @click="showDetailsModal = false">
      <div class="modal" @click.stop>
        <button class="close-button" @click="showDetailsModal = false">✕</button>
        <h2>Message Details</h2>

        <div class="details-content">
          <p><strong>Type:</strong> {{ selectedMessage.type }}</p>
          <p><strong>Recipient:</strong> {{ selectedMessage.recipient_contact }}</p>
          <p><strong>Status:</strong> <span class="status-badge" :class="`status-${selectedMessage.status}`">{{ selectedMessage.status }}</span></p>
          <p><strong>Sent:</strong> {{ formatDate(selectedMessage.sent_at) }}</p>
          <p><strong>Campaign:</strong> {{ selectedMessage.campaign_type }}</p>
          
          <div v-if="selectedMessage.type === 'email'">
            <h4>Subject</h4>
            <p>{{ selectedMessage.subject }}</p>
            <h4>Body</h4>
            <p>{{ selectedMessage.body }}</p>
          </div>
          <div v-else>
            <h4>Message</h4>
            <p>{{ selectedMessage.body }}</p>
          </div>

          <div v-if="selectedMessage.error_message" class="error-box">
            <strong>Error:</strong> {{ selectedMessage.error_message }}
          </div>
        </div>
      </div>
    </div>

    <!-- Loading Indicator -->
    <div v-if="isLoading" class="loading-overlay">
      <div class="spinner"></div>
    </div>

    <!-- Toast Notification -->
    <div v-if="notification" :class="['toast', `toast-${notification.type}`]">
      {{ notification.message }}
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';

export default {
  name: 'MessagingPage',
  setup() {
    const getToken = () => localStorage.getItem('token');
    const activeTab = ref('send');
    const isLoading = ref(false);
    const notification = ref(null);

    const tabs = [
      { id: 'send', label: 'Send Message', icon: '✉️', badge: null },
      { id: 'templates', label: 'Templates', icon: '📋', badge: null },
      { id: 'history', label: 'Message History', icon: '📜', badge: null },
      { id: 'stats', label: 'Statistics', icon: '📊', badge: null },
    ];

    const sendForm = ref({
      messageType: 'single',
      channel: 'email',
      contentType: 'template',
      selectedTemplateId: '',
      customSubject: '',
      customBody: '',
      recipientName: '',
      recipientEmail: '',
      recipientPhone: '',
      bulkRecipients: '',
      campaignName: '',
      variables: {},
    });

    const templateForm = ref({
      name: '',
      category: 'transactional',
      type: 'both',
      email_subject: '',
      email_body: '',
      sms_body: '',
    });

    const templates = ref([]);
    const messageLogs = ref([]);
    const stats = ref({});
    const selectedMessage = ref(null);
    const editingTemplateId = ref(null);
    const showTemplateModal = ref(false);
    const showDetailsModal = ref(false);

    // --- Customer checklist state ---
    const customers = ref([]);
    const customersLoading = ref(false);
    const customerSearch = ref('');
    const selectedCustomerIds = ref(new Set());

    const filteredCustomers = computed(() => {
      const q = customerSearch.value.trim().toLowerCase();
      if (!q) return customers.value;
      return customers.value.filter(c =>
        (c.name || '').toLowerCase().includes(q) ||
        (c.email || '').toLowerCase().includes(q) ||
        (c.phone || '').toLowerCase().includes(q)
      );
    });

    const customRecipientCount = computed(() => {
      return sendForm.value.bulkRecipients.trim()
        ? sendForm.value.bulkRecipients.trim().split('\n').filter(l => l.trim()).length
        : 0;
    });

    const totalRecipientCount = computed(() =>
      selectedCustomerIds.value.size + customRecipientCount.value
    );

    const toggleCustomer = (id) => {
      const s = new Set(selectedCustomerIds.value);
      s.has(id) ? s.delete(id) : s.add(id);
      selectedCustomerIds.value = s;
    };

    const selectAllFiltered = () => {
      const s = new Set(selectedCustomerIds.value);
      filteredCustomers.value.forEach(c => s.add(c.id));
      selectedCustomerIds.value = s;
    };

    const clearCustomerSelection = () => {
      selectedCustomerIds.value = new Set();
    };

    const fetchCustomers = async () => {
      customersLoading.value = true;
      try {
        const res = await fetch('/customers', {
          headers: { 'Authorization': `Bearer ${getToken()}` }
        });
        const data = await res.json();
        customers.value = Array.isArray(data) ? data : (data.data || []);
      } catch (e) {
        console.error('Failed to fetch customers', e);
      } finally {
        customersLoading.value = false;
      }
    };

    const historyFilters = ref({
      search: '',
      type: '',
      status: '',
    });

    const selectedTemplate = computed(() => {
      if (!sendForm.value.selectedTemplateId) return null;
      return templates.value.find(t => t.id == sendForm.value.selectedTemplateId);
    });

    const templatesByCategory = computed(() => {
      const grouped = {};
      templates.value.forEach(t => {
        if (!grouped[t.category]) {
          grouped[t.category] = { category: t.category, templates: [] };
        }
        grouped[t.category].templates.push(t);
      });
      return Object.values(grouped);
    });

    const canSendMessage = computed(() => {
      const hasContent = sendForm.value.contentType === 'template'
        ? !!sendForm.value.selectedTemplateId
        : !!sendForm.value.customBody;

      if (sendForm.value.messageType === 'single') {
        return (sendForm.value.recipientEmail || sendForm.value.recipientPhone) && hasContent;
      } else {
        const hasRecipients = selectedCustomerIds.value.size > 0 || sendForm.value.bulkRecipients.trim().length > 0;
        return hasRecipients && hasContent;
      }
    });

    const calculateSuccessRate = computed(() => {
      if (!stats.value.total_sent) return 0;
      return Math.round((stats.value.total_sent / (stats.value.total_sent + stats.value.total_failed)) * 100);
    });

    const fetchTemplates = async () => {
      try {
        const response = await fetch('/api/messaging/templates', {
          headers: { 'Authorization': `Bearer ${getToken()}` }
        });
        templates.value = await response.json();
      } catch (error) {
        console.error('Error fetching templates:', error);
      }
    };

    const fetchMessageLogs = async () => {
      try {
        const response = await fetch('/api/messaging/logs', {
          headers: { 'Authorization': `Bearer ${getToken()}` }
        });
        const data = await response.json();
        messageLogs.value = data.data || [];
      } catch (error) {
        console.error('Error fetching logs:', error);
      }
    };

    const fetchStats = async () => {
      try {
        const response = await fetch('/api/messaging/stats', {
          headers: { 'Authorization': `Bearer ${getToken()}` }
        });
        stats.value = await response.json();
      } catch (error) {
        console.error('Error fetching stats:', error);
      }
    };

    const sendMessage = async () => {
      isLoading.value = true;
      try {
        if (sendForm.value.messageType === 'bulk') {
          // Build recipients array from system customers + custom lines
          const recipients = [];

          // Add selected system customers
          for (const id of selectedCustomerIds.value) {
            const c = customers.value.find(x => x.id === id);
            if (c) recipients.push({ name: c.name || '', email: c.email || null, phone: c.phone || null });
          }

          // Add custom lines (each line is an email or phone)
          const customLines = sendForm.value.bulkRecipients.trim().split('\n').map(l => l.trim()).filter(Boolean);
          for (const line of customLines) {
            const isEmail = line.includes('@');
            recipients.push({ name: '', email: isEmail ? line : null, phone: !isEmail ? line : null });
          }

          if (recipients.length === 0) {
            showNotification('Please select at least one recipient', 'error');
            return;
          }

          const payload = {
            type: sendForm.value.channel,
            recipient_type: 'custom',
            recipients,
            campaign_name: sendForm.value.campaignName || undefined,
            use_custom_content: sendForm.value.contentType === 'custom',
            ...(sendForm.value.contentType === 'custom' ? {
              custom_subject: sendForm.value.customSubject,
              custom_body: sendForm.value.customBody,
            } : {
              template_id: sendForm.value.selectedTemplateId,
              variables_template: sendForm.value.variables,
            }),
          };

          const response = await fetch('/api/messaging/send-bulk', {
            method: 'POST',
            headers: { 'Authorization': `Bearer ${getToken()}`, 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
          });

          if (response.ok) {
            const data = await response.json();
            const r = data.results || {};
            showNotification(`Sent to ${r.sent || 0} / ${r.total || recipients.length} recipients`, 'success');
            clearCustomerSelection();
            resetSendForm();
            fetchMessageLogs();
          } else {
            const err = await response.json().catch(() => ({}));
            showNotification(err.error || err.message || 'Failed to send bulk messages', 'error');
          }
        } else {
          // Single send
          const payload = {
            type: sendForm.value.channel,
            use_custom_content: sendForm.value.contentType === 'custom',
            ...(sendForm.value.contentType === 'custom' ? {
              custom_subject: sendForm.value.customSubject,
              custom_body: sendForm.value.customBody,
            } : {
              template_id: sendForm.value.selectedTemplateId,
              variables: sendForm.value.variables,
            }),
            recipient_email: sendForm.value.recipientEmail,
            recipient_phone: sendForm.value.recipientPhone,
            recipient_name: sendForm.value.recipientName,
            campaign_name: sendForm.value.campaignName,
          };

          const response = await fetch('/api/messaging/send', {
            method: 'POST',
            headers: { 'Authorization': `Bearer ${getToken()}`, 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
          });

          if (response.ok) {
            showNotification('Message sent successfully!', 'success');
            resetSendForm();
            fetchMessageLogs();
          } else {
            const err = await response.json().catch(() => ({}));
            showNotification(err.error || err.message || 'Failed to send message', 'error');
          }
        }
      } catch (error) {
        showNotification(error.message || 'An error occurred', 'error');
      } finally {
        isLoading.value = false;
      }
    };

    const testTemplate = async () => {
      if (!selectedTemplate.value) return;

      try {
        const response = await fetch('/api/messaging/test-template', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${getToken()}`,
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            template_id: sendForm.value.selectedTemplateId,
            variables: sendForm.value.variables,
          }),
        });

        const data = await response.json();
        alert('Template Preview:\n\n' + JSON.stringify(data.preview, null, 2));
      } catch (error) {
        showNotification('Failed to preview template', 'error');
      }
    };

    const saveTemplate = async () => {
      isLoading.value = true;
      try {
        const url = editingTemplateId.value 
          ? `/api/messaging/templates/${editingTemplateId.value}`
          : '/api/messaging/templates';
        
        const method = editingTemplateId.value ? 'PUT' : 'POST';

        const response = await fetch(url, {
          method,
          headers: {
            'Authorization': `Bearer ${getToken()}`,
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(templateForm.value),
        });

        if (response.ok) {
          showNotification(editingTemplateId.value ? 'Template updated' : 'Template created', 'success');
          resetTemplateForm();
          closeTemplateModal();
          fetchTemplates();
        }
      } catch (error) {
        showNotification(error.message, 'error');
      } finally {
        isLoading.value = false;
      }
    };

    const editTemplate = (template) => {
      editingTemplateId.value = template.id;
      templateForm.value = { ...template };
      showTemplateModal.value = true;
    };

    const deleteTemplate = async (template) => {
      if (!confirm('Delete this template?')) return;

      try {
        const response = await fetch(`/api/messaging/templates/${template.id}`, {
          method: 'DELETE',
          headers: { 'Authorization': `Bearer ${getToken()}` }
        });

        if (response.ok) {
          showNotification('Template deleted', 'success');
          fetchTemplates();
        }
      } catch (error) {
        showNotification(error.message, 'error');
      }
    };

    const closeTemplateModal = () => {
      showTemplateModal.value = false;
      resetTemplateForm();
      editingTemplateId.value = null;
    };

    const resetSendForm = () => {
      sendForm.value = {
        messageType: 'single',
        channel: 'email',
        contentType: 'template',
        selectedTemplateId: '',
        customSubject: '',
        customBody: '',
        recipientName: '',
        recipientEmail: '',
        recipientPhone: '',
        bulkRecipients: '',
        campaignName: '',
        variables: {},
      };
      clearCustomerSelection();
    };

    const resetTemplateForm = () => {
      templateForm.value = {
        name: '',
        category: 'transactional',
        type: 'both',
        email_subject: '',
        email_body: '',
        sms_body: '',
      };
    };

    const viewMessageDetails = (log) => {
      selectedMessage.value = log;
      showDetailsModal.value = true;
    };

    const formatDate = (date) => {
      return new Date(date).toLocaleString();
    };

    const showNotification = (message, type = 'info') => {
      notification.value = { message, type };
      setTimeout(() => {
        notification.value = null;
      }, 3000);
    };

    onMounted(() => {
      fetchTemplates();
      fetchMessageLogs();
      fetchStats();
      fetchCustomers();
    });

    return {
      activeTab,
      tabs,
      templates,
      messageLogs,
      stats,
      sendForm,
      templateForm,
      selectedTemplate,
      templatesByCategory,
      canSendMessage,
      calculateSuccessRate,
      isLoading,
      notification,
      showTemplateModal,
      showDetailsModal,
      selectedMessage,
      editingTemplateId,
      historyFilters,
      // Customer checklist
      customers,
      customersLoading,
      customerSearch,
      selectedCustomerIds,
      filteredCustomers,
      customRecipientCount,
      totalRecipientCount,
      toggleCustomer,
      selectAllFiltered,
      clearCustomerSelection,
      sendMessage,
      testTemplate,
      saveTemplate,
      editTemplate,
      deleteTemplate,
      closeTemplateModal,
      viewMessageDetails,
      formatDate,
      showNotification,
    };
  },
};
</script>

<style scoped>
/* ── Customer checklist ──────────────────────────────────────────── */
.recipient-panel {
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 0.875rem 1rem;
  background: #fafafa;
}

.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #374151;
}

.badge-count {
  background: #6366f1;
  color: #fff;
  border-radius: 999px;
  padding: 1px 8px;
  font-size: 0.72rem;
  font-weight: 600;
  margin-left: 6px;
}

.panel-controls { display: flex; gap: 0.4rem; }

.btn-mini {
  padding: 2px 10px;
  font-size: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  background: #fff;
  cursor: pointer;
  color: #374151;
  transition: background 0.15s;
}
.btn-mini:hover { background: #f3f4f6; }
.btn-mini.btn-clear { border-color: #fca5a5; color: #dc2626; }
.btn-mini.btn-clear:hover { background: #fff1f2; }
.btn-mini:disabled { opacity: 0.4; cursor: default; }

.customer-search {
  margin-bottom: 0.5rem;
}

.customer-checklist {
  max-height: 220px;
  overflow-y: auto;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #fff;
}

.customer-row {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.45rem 0.75rem;
  cursor: pointer;
  border-bottom: 1px solid #f3f4f6;
  transition: background 0.1s;
}
.customer-row:last-child { border-bottom: none; }
.customer-row:hover { background: #f5f3ff; }
.customer-row.selected { background: #eef2ff; }
.customer-row input[type="checkbox"] { accent-color: #6366f1; width: 15px; height: 15px; flex-shrink: 0; }
.c-name { font-weight: 500; color: #111827; min-width: 140px; }
.c-meta { margin-left: auto; font-size: 0.78rem; color: #6b7280; display: flex; gap: 0.75rem; flex-wrap: wrap; justify-content: flex-end; }

.checklist-empty {
  padding: 1rem;
  text-align: center;
  color: #9ca3af;
  font-size: 0.875rem;
}

.recipient-summary {
  margin-top: 0.75rem;
  padding: 0.6rem 0.875rem;
  background: #ecfdf5;
  border: 1px solid #a7f3d0;
  border-radius: 8px;
  font-size: 0.875rem;
  color: #065f46;
}

.messaging-page {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 2rem;
  border-radius: 12px;
  color: white;
}

.header-content h1 {
  margin: 0;
  font-size: 2rem;
}

.header-content p {
  margin: 0.5rem 0 0 0;
  opacity: 0.9;
}

.tabs-container {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
  border-bottom: 2px solid #e0e0e0;
  overflow-x: auto;
}

.tabs {
  display: flex;
  gap: 0.5rem;
}

.tab-button {
  padding: 0.75rem 1.5rem;
  border: none;
  background: transparent;
  cursor: pointer;
  font-size: 1rem;
  position: relative;
  border-bottom: 3px solid transparent;
  transition: all 0.3s ease;
}

.tab-button:hover {
  color: #667eea;
}

.tab-button.active {
  color: #667eea;
  border-bottom-color: #667eea;
}

.badge {
  display: inline-block;
  background: #667eea;
  color: white;
  padding: 0.25rem 0.5rem;
  border-radius: 12px;
  font-size: 0.75rem;
  margin-left: 0.5rem;
}

.tab-content {
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.send-message-form {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.form-section {
  margin-bottom: 2rem;
}

.form-section h3 {
  margin-top: 0;
  color: #333;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #333;
}

.input-field,
.textarea-field,
select {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 1rem;
  font-family: inherit;
}

.textarea-field {
  resize: vertical;
}

.radio-group {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.radio-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
}

.radio-label input[type="radio"] {
  cursor: pointer;
}

.template-preview {
  background: #f5f5f5;
  padding: 1rem;
  border-radius: 6px;
  margin-top: 1rem;
}

.template-preview h4 {
  margin-top: 0;
}

.preview-item {
  margin: 0.5rem 0;
  font-size: 0.9rem;
}

.variable-input {
  margin-bottom: 0.5rem;
}

.form-actions {
  display: flex;
  gap: 1rem;
  margin-top: 2rem;
  justify-content: flex-end;
}

.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 6px;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-primary {
  background: #667eea;
  color: white;
}

.btn-primary:hover {
  background: #5568d3;
}

.btn-secondary {
  background: #e0e0e0;
  color: #333;
}

.btn-secondary:hover {
  background: #d0d0d0;
}

.btn-success {
  background: #52c41a;
  color: white;
}

.btn-success:hover {
  background: #389e0d;
}

.btn-danger {
  background: #ff7d7d;
  color: white;
}

.btn-danger:hover {
  background: #ff5757;
}

.btn-small {
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.templates-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 1.5rem;
}

.template-card {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.template-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.template-header {
  display: flex;
  justify-content: space-between;
  align-items: start;
  margin-bottom: 0.5rem;
}

.template-header h4 {
  margin: 0;
  flex: 1;
}

.badge-email {
  background: #1890ff;
}

.badge-sms {
  background: #ff7a45;
}

.badge-both {
  background: #722ed1;
}

.template-category {
  color: #999;
  font-size: 0.875rem;
  margin: 0.5rem 0;
}

.template-preview-mini {
  background: #f5f5f5;
  padding: 0.75rem;
  border-radius: 4px;
  margin: 1rem 0;
}

.template-preview-mini small {
  display: block;
  margin: 0.25rem 0;
  color: #666;
  word-break: break-word;
}

.template-actions {
  display: flex;
  gap: 0.5rem;
  margin-top: 1rem;
}

.table-container {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.table {
  width: 100%;
  border-collapse: collapse;
}

.table th {
  background: #f5f5f5;
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  color: #333;
}

.table td {
  padding: 1rem;
  border-bottom: 1px solid #e0e0e0;
}

.table tbody tr:hover {
  background: #f9f9f9;
}

.status-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.875rem;
  font-weight: 500;
}

.status-sent {
  background: #d4edda;
  color: #155724;
}

.status-failed {
  background: #f8d7da;
  color: #721c24;
}

.status-pending {
  background: #fff3cd;
  color: #856404;
}

.status-delivered {
  background: #d1ecf1;
  color: #0c5460;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  text-align: center;
  position: relative;
}

.stat-value {
  font-size: 2.5rem;
  font-weight: bold;
  color: #667eea;
  margin-bottom: 0.5rem;
}

.stat-label {
  color: #666;
  font-size: 0.9rem;
}

.stat-icon {
  font-size: 2rem;
  position: absolute;
  top: 1rem;
  right: 1rem;
  opacity: 0.5;
}

.stats-by-type {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.type-breakdown {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.breakdown-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: #f5f5f5;
  padding: 0.75rem 1rem;
  border-radius: 6px;
}

.type-name {
  font-weight: 500;
}

.type-count {
  background: #667eea;
  color: white;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  position: relative;
}

.close-button {
  position: absolute;
  top: 1rem;
  right: 1rem;
  background: transparent;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #999;
}

.close-button:hover {
  color: #333;
}

.modal h2 {
  margin-top: 0;
  margin-bottom: 1.5rem;
}

.details-content {
  line-height: 1.8;
}

.details-content p {
  margin: 0.5rem 0;
}

.error-box {
  background: #f8d7da;
  color: #721c24;
  padding: 1rem;
  border-radius: 6px;
  margin-top: 1rem;
}

.empty-state {
  text-align: center;
  padding: 3rem;
  color: #999;
}

.filters {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}

.filters .input-field {
  flex: 1;
  min-width: 200px;
}

.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 999;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.toast {
  position: fixed;
  bottom: 2rem;
  right: 2rem;
  padding: 1rem 1.5rem;
  border-radius: 6px;
  z-index: 1001;
  animation: slideIn 0.3s ease;
}

@keyframes slideIn {
  from {
    transform: translateX(400px);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

.toast-success {
  background: #52c41a;
  color: white;
}

.toast-error {
  background: #ff7d7d;
  color: white;
}

.toast-info {
  background: #1890ff;
  color: white;
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }

  .templates-grid {
    grid-template-columns: 1fr;
  }

  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .modal {
    width: 95%;
  }
}
</style>
