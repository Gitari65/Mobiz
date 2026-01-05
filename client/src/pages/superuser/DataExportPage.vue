<template>
  <div class="de-page">
    <!-- Header -->
    <header class="de-header">
      <div class="de-title">
        <i class="fas fa-file-export"></i>
        <div>
          <h1>Data Export</h1>
          <p class="muted">Export platform data in CSV, Excel, or PDF format</p>
        </div>
      </div>
    </header>

    <!-- Export Generator -->
    <section class="export-generator">
      <h2>Generate Export</h2>
      
      <div class="export-form">
        <div class="form-row">
          <div class="form-group">
            <label>Export Type *</label>
            <select v-model="exportForm.type" required>
              <option value="">Select data type</option>
              <option value="businesses">All Businesses</option>
              <option value="products">All Products</option>
              <option value="transactions">All Transactions</option>
              <option value="subscriptions">All Subscriptions</option>
              <option value="audit_logs">Audit Logs</option>
            </select>
          </div>

          <div class="form-group">
            <label>Format *</label>
            <select v-model="exportForm.format" required>
              <option value="">Select format</option>
              <option value="csv">CSV</option>
              <option value="xlsx">Excel (.xlsx)</option>
              <option value="pdf">PDF</option>
            </select>
          </div>

          <button @click="generateExport" :disabled="!exportForm.type || !exportForm.format || exporting" class="btn-generate">
            <i v-if="exporting" class="fas fa-spinner fa-spin"></i>
            <i v-else class="fas fa-download"></i>
            {{ exporting ? 'Generating...' : 'Generate Export' }}
          </button>
        </div>

        <!-- Filters (optional) -->
        <div v-if="showFilters" class="filters-section">
          <h3>Optional Filters</h3>
          <div class="filter-row">
            <div class="filter-group">
              <label>From Date</label>
              <input v-model="exportForm.filters.from_date" type="date" />
            </div>
            <div class="filter-group">
              <label>To Date</label>
              <input v-model="exportForm.filters.to_date" type="date" />
            </div>
            <div class="filter-group">
              <label>Status</label>
              <select v-model="exportForm.filters.status">
                <option value="">All</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Recent Exports -->
    <section class="recent-exports">
      <div class="section-header">
        <h2>Recent Exports</h2>
        <button class="btn-refresh" @click="fetchExports" :disabled="loading">
          <i class="fas fa-sync-alt" :class="{ 'spin': loading }"></i>
        </button>
      </div>

      <div v-if="loading" class="loading">
        <div class="spinner"></div>
        <p>Loading exports...</p>
      </div>

      <table v-else class="exports-table">
        <thead>
          <tr>
            <th>Type</th>
            <th>Format</th>
            <th>Status</th>
            <th>Requested</th>
            <th>Completed</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="exp in exports" :key="exp.export_id" :class="`status-${exp.status}`">
            <td>{{ formatType(exp.type) }}</td>
            <td>{{ exp.format.toUpperCase() }}</td>
            <td>
              <span class="status-badge" :class="`badge-${exp.status}`">
                {{ formatStatus(exp.status) }}
              </span>
            </td>
            <td>{{ formatDate(exp.created_at) }}</td>
            <td>{{ exp.completed_at ? formatDate(exp.completed_at) : '-' }}</td>
            <td class="actions">
              <button v-if="exp.status === 'completed'" @click="downloadExport(exp)" class="btn-download" title="Download">
                <i class="fas fa-download"></i>
              </button>
              <button v-if="exp.status === 'pending' || exp.status === 'processing'" @click="checkStatus(exp)" class="btn-check" title="Check Status">
                <i class="fas fa-refresh"></i>
              </button>
              <span v-if="exp.status === 'failed'" class="error-text">{{ exp.error }}</span>
            </td>
          </tr>
          <tr v-if="exports.length === 0">
            <td colspan="6" class="empty">No exports yet</td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="pagination">
        <button @click="currentPage--" :disabled="currentPage === 1" class="btn-page">
          <i class="fas fa-chevron-left"></i>
        </button>
        <span class="page-info">Page {{ currentPage }} of {{ totalPages }}</span>
        <button @click="currentPage++" :disabled="currentPage === totalPages" class="btn-page">
          <i class="fas fa-chevron-right"></i>
        </button>
      </div>
    </section>

    <!-- Alert Toast -->
    <div v-if="alert.show" class="alert-toast" :class="`alert-${alert.type}`">
      <i :class="getAlertIcon(alert.type)"></i>
      <span>{{ alert.message }}</span>
      <button @click="alert.show = false" class="alert-close">
        <i class="fas fa-times"></i>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import axios from 'axios'

// State
const loading = ref(false)
const exporting = ref(false)
const exports = ref([])
const currentPage = ref(1)
const totalPages = ref(1)
const showFilters = ref(false)

const exportForm = reactive({
  type: '',
  format: '',
  filters: { from_date: '', to_date: '', status: '' }
})

const alert = reactive({
  show: false,
  type: 'info',
  message: ''
})

// Methods
const showAlert = (type, message, duration = 3000) => {
  alert.show = true
  alert.type = type
  alert.message = message
  setTimeout(() => { alert.show = false }, duration)
}

const getAlertIcon = (type) => {
  const icons = { success: 'fas fa-check-circle', error: 'fas fa-exclamation-circle', warning: 'fas fa-exclamation-triangle', info: 'fas fa-info-circle' }
  return icons[type] || icons.info
}

const formatType = (type) => {
  const map = { businesses: 'Businesses', products: 'Products', transactions: 'Transactions', subscriptions: 'Subscriptions', audit_logs: 'Audit Logs' }
  return map[type] || type
}

const formatStatus = (status) => {
  const map = { pending: 'Pending', processing: 'Processing', completed: 'Completed', failed: 'Failed' }
  return map[status] || status
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

async function generateExport() {
  exporting.value = true
  try {
    const res = await axios.post('/api/super/exports', {
      type: exportForm.type,
      format: exportForm.format,
      filters: exportForm.filters
    })
    showAlert('success', 'Export queued. Check back soon!')
    exportForm.type = ''
    exportForm.format = ''
    fetchExports()
  } catch (e) {
    showAlert('error', e.response?.data?.message || 'Failed to generate export')
  } finally {
    exporting.value = false
  }
}

async function fetchExports() {
  loading.value = true
  try {
    const res = await axios.get('/api/super/exports', { params: { page: currentPage.value, per_page: 20 } })
    const data = res.data
    exports.value = data.data || []
    totalPages.value = data.last_page || 1
  } catch (e) {
    showAlert('error', 'Failed to fetch exports')
    exports.value = []
  } finally {
    loading.value = false
  }
}

async function checkStatus(exp) {
  try {
    const res = await axios.get(`/api/super/exports/${exp.export_id}`)
    const idx = exports.value.findIndex(e => e.export_id === exp.export_id)
    if (idx >= 0) {
      Object.assign(exports.value[idx], res.data)
    }
    showAlert('info', `Status: ${res.data.status}`)
  } catch (e) {
    showAlert('error', 'Failed to check status')
  }
}

function downloadExport(exp) {
  if (exp.download_url) {
    window.location.href = exp.download_url
  }
}

// Watchers
watch(() => currentPage.value, () => { fetchExports() })

// Lifecycle
onMounted(() => { fetchExports() })
</script>

<style scoped>
.de-page { max-width: 1200px; margin: 2rem auto; padding: 1.5rem; font-family: 'Inter', sans-serif; }

.de-header { display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem; }
.de-title { display: flex; align-items: center; gap: 1rem; }
.de-title i { font-size: 1.75rem; color: #667eea; background: rgba(102,126,234,0.1); padding: 0.75rem; border-radius: 12px; }
.de-title h1 { margin: 0; font-size: 1.5rem; }
.muted { color: #6b7280; margin: 0; }

.export-generator, .recent-exports { background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: 0 6px 20px rgba(0,0,0,0.06); margin-bottom: 1.5rem; }

.export-generator h2, .recent-exports h2 { margin-top: 0; font-size: 1.25rem; }

.export-form { display: flex; flex-direction: column; gap: 1rem; }
.form-row { display: grid; grid-template-columns: 1fr 1fr auto; gap: 1rem; align-items: flex-end; }
.form-group { display: flex; flex-direction: column; gap: 0.5rem; }
.form-group label { font-weight: 600; color: #374151; }
.form-group select { padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 0.95rem; }

.btn-generate { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s ease; }
.btn-generate:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(102,126,234,0.3); }
.btn-generate:disabled { opacity: 0.5; cursor: not-allowed; }

.filters-section { padding-top: 1rem; border-top: 1px solid #e5e7eb; }
.filter-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
.filter-group { display: flex; flex-direction: column; gap: 0.5rem; }
.filter-group label { font-weight: 600; color: #374151; font-size: 0.9rem; }
.filter-group input, .filter-group select { padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px; }

.section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
.btn-refresh { background: #f3f4f6; border: none; width: 44px; height: 44px; border-radius: 10px; cursor: pointer; }
.btn-refresh.spin i { animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.loading { text-align: center; padding: 2rem; }
.spinner { width: 40px; height: 40px; border: 3px solid #e5e7eb; border-left: 3px solid #667eea; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 1rem; }

.exports-table { width: 100%; border-collapse: collapse; }
.exports-table th, .exports-table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; }
.exports-table th { background: #f9fafb; font-weight: 600; color: #374151; }
.exports-table tr:hover { background: #f9fafb; }

.status-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 6px; font-size: 0.8rem; font-weight: 600; }
.badge-pending { background: #fef3c7; color: #78350f; }
.badge-processing { background: #dbeafe; color: #1e40af; }
.badge-completed { background: #d1fae5; color: #065f46; }
.badge-failed { background: #fee2e2; color: #991b1b; }

.actions { display: flex; gap: 0.5rem; align-items: center; }
.btn-download, .btn-check { background: none; border: none; color: #667eea; cursor: pointer; padding: 0.5rem; border-radius: 6px; transition: all 0.2s ease; }
.btn-download:hover, .btn-check:hover { background: rgba(102,126,234,0.1); }
.error-text { color: #991b1b; font-size: 0.85rem; }
.empty { text-align: center; padding: 2rem; color: #94a3b8; }

.pagination { display: flex; align-items: center; justify-content: center; gap: 1rem; margin-top: 1.5rem; }
.btn-page { background: #f3f4f6; border: none; width: 36px; height: 36px; border-radius: 6px; cursor: pointer; }
.btn-page:disabled { opacity: 0.5; cursor: not-allowed; }

.alert-toast { position: fixed; bottom: 2rem; right: 2rem; display: flex; align-items: center; gap: 1rem; padding: 1rem 1.5rem; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); z-index: 3000; animation: slideIn 0.3s ease; }
.alert-success { background: #d1fae5; color: #065f46; }
.alert-error { background: #fee2e2; color: #991b1b; }
@keyframes slideIn { from { transform: translateX(400px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
</style>
