<template>
  <div class="al-page">
    <!-- Header -->
    <header class="al-header">
      <div class="al-title">
        <i class="fas fa-history"></i>
        <div>
          <h1>Audit Logs</h1>
          <p class="muted">Track system-wide activity and changes</p>
        </div>
      </div>
      <button class="btn-refresh" @click="fetchLogs" :disabled="loading">
        <i class="fas fa-sync-alt" :class="{ 'spin': loading }"></i>
      </button>
    </header>

    <!-- Filters -->
    <section class="al-filters">
      <div class="filter-row">
        <div class="filter-group">
          <label>From Date</label>
          <input v-model="filters.from" type="date" class="filter-input" @change="fetchLogs" />
        </div>

        <div class="filter-group">
          <label>To Date</label>
          <input v-model="filters.to" type="date" class="filter-input" @change="fetchLogs" />
        </div>

        <div class="filter-group">
          <label>Action Type</label>
          <select v-model="filters.action" class="filter-select" @change="fetchLogs">
            <option value="">All Actions</option>
            <option value="created">Created</option>
            <option value="updated">Updated</option>
            <option value="deleted">Deleted</option>
            <option value="login">Login</option>
            <option value="impersonate">Impersonate</option>
          </select>
        </div>

        <div class="filter-group">
          <label>User</label>
          <input v-model="filters.user_id" type="text" placeholder="User name or ID..." class="filter-input" @keyup.enter="fetchLogs" />
        </div>

        <button class="btn-filter-reset" @click="resetFilters">
          <i class="fas fa-redo"></i> Reset
        </button>
      </div>

      <div class="filter-summary" v-if="hasActiveFilters">
        <span>Filters applied:</span>
        <span v-if="filters.from" class="tag">From: {{ filters.from }}</span>
        <span v-if="filters.to" class="tag">To: {{ filters.to }}</span>
        <span v-if="filters.action" class="tag">Action: {{ filters.action }}</span>
        <span v-if="filters.user_id" class="tag">User: {{ filters.user_id }}</span>
      </div>
    </section>

    <!-- Table -->
    <section class="al-table-container">
      <div v-if="loading" class="loading">
        <div class="spinner"></div>
        <p>Loading audit logs...</p>
      </div>

      <div v-else-if="error" class="error-message">
        <i class="fas fa-exclamation-circle"></i>
        <span>{{ error }}</span>
        <button @click="fetchLogs" class="btn-retry">Retry</button>
      </div>

      <table v-else class="audit-table">
        <thead>
          <tr>
            <th>Timestamp</th>
            <th>User</th>
            <th>Action</th>
            <th>Resource</th>
            <th>Record ID</th>
            <th>IP Address</th>
            <th>Notes</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="log in logs" :key="log.id" :class="`action-${log.action}`">
            <td class="timestamp">{{ formatDateTime(log.created_at) }}</td>
            <td class="user">{{ log.user_name || `User #${log.user_id}` || '-' }}</td>
            <td class="action">
              <span class="action-badge" :class="`badge-${log.action}`">
                {{ formatAction(log.action) }}
              </span>
            </td>
            <td class="resource">{{ formatResource(log.auditable_type) }}</td>
            <td class="record-id">{{ log.auditable_id || '-' }}</td>
            <td class="ip-address">{{ log.ip_address || '-' }}</td>
            <td class="notes">{{ log.notes || '-' }}</td>
          </tr>
          <tr v-if="logs.length === 0">
            <td colspan="7" class="empty">No audit logs found</td>
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
import { ref, reactive, computed, onMounted, watch } from 'vue'
import axios from 'axios'

// State
const loading = ref(false)
const error = ref(null)
const logs = ref([])
const currentPage = ref(1)
const totalPages = ref(1)

const filters = reactive({
  from: '',
  to: '',
  action: '',
  user_id: ''
})

const alert = reactive({
  show: false,
  type: 'info',
  message: ''
})

// Computed
const hasActiveFilters = computed(() => {
  return filters.from || filters.to || filters.action || filters.user_id
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

const formatDateTime = (dt) => {
  if (!dt) return '-'
  const d = new Date(dt)
  return d.toLocaleString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' })
}

const formatAction = (action) => {
  if (!action) return '-'
  return action.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')
}

const formatResource = (type) => {
  if (!type) return '-'
  const parts = type.split('\\')
  return parts[parts.length - 1]
}

async function fetchLogs() {
  loading.value = true
  error.value = null
  try {
    const params = {
      page: currentPage.value,
      per_page: 25,
      ...filters
    }
    const res = await axios.get('/api/super/audit-logs', { params })
    const data = res.data
    logs.value = data.data || data.logs || []
    totalPages.value = data.last_page || 1
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to fetch audit logs'
    logs.value = []
    showAlert('error', error.value)
  } finally {
    loading.value = false
  }
}

function resetFilters() {
  filters.from = ''
  filters.to = ''
  filters.action = ''
  filters.user_id = ''
  currentPage.value = 1
  fetchLogs()
}

// Watchers
watch(() => currentPage.value, () => {
  fetchLogs()
})

// Lifecycle
onMounted(() => {
  fetchLogs()
})
</script>

<style scoped>
.al-page { max-width: 1400px; margin: 2rem auto; padding: 1.5rem; font-family: 'Inter', sans-serif; }

.al-header { display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 2rem; }
.al-title { display: flex; align-items: center; gap: 1rem; }
.al-title i { font-size: 1.75rem; color: #667eea; background: rgba(102,126,234,0.1); padding: 0.75rem; border-radius: 12px; }
.al-title h1 { margin: 0; font-size: 1.5rem; }
.muted { color: #6b7280; margin: 0; }

.btn-refresh { background: #f3f4f6; border: none; width: 44px; height: 44px; border-radius: 10px; cursor: pointer; transition: all 0.3s ease; }
.btn-refresh:hover { background: #e5e7eb; transform: scale(1.05); }
.btn-refresh:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-refresh.spin i { animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.al-filters { background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: 0 6px 20px rgba(0,0,0,0.06); margin-bottom: 1.5rem; }
.filter-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1rem; }
.filter-group { display: flex; flex-direction: column; gap: 0.5rem; }
.filter-group label { font-weight: 600; color: #374151; font-size: 0.875rem; }
.filter-input, .filter-select { padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 0.95rem; transition: all 0.3s ease; }
.filter-input:focus, .filter-select:focus { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.1); outline: none; }

.btn-filter-reset { background: #667eea; color: #fff; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s ease; align-self: flex-end; }
.btn-filter-reset:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(102,126,234,0.3); }

.filter-summary { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; }
.filter-summary span:first-child { font-size: 0.875rem; color: #6b7280; }
.tag { background: #e0e7ff; color: #4338ca; padding: 0.25rem 0.75rem; border-radius: 16px; font-size: 0.875rem; }

.al-table-container { background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: 0 6px 20px rgba(0,0,0,0.06); }

.loading, .error-message { text-align: center; padding: 2rem; }
.spinner { width: 40px; height: 40px; border: 3px solid #e5e7eb; border-left: 3px solid #667eea; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 1rem; }

.error-message { color: #991b1b; background: #fee2e2; border-radius: 8px; display: flex; align-items: center; justify-content: center; gap: 1rem; }
.btn-retry { background: #dc2626; color: #fff; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; }

.audit-table { width: 100%; border-collapse: collapse; }
.audit-table th, .audit-table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; font-size: 0.95rem; }
.audit-table th { background: #f9fafb; font-weight: 600; color: #374151; }
.audit-table tr:hover { background: #f9fafb; }

.timestamp { font-weight: 500; color: #667eea; }
.user { font-weight: 500; }
.action { text-align: center; }
.action-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 6px; font-size: 0.8rem; font-weight: 600; }
.badge-created { background: #d1fae5; color: #065f46; }
.badge-updated { background: #dbeafe; color: #1e40af; }
.badge-deleted { background: #fee2e2; color: #991b1b; }
.badge-login { background: #fef3c7; color: #78350f; }
.badge-impersonate { background: #f3e8ff; color: #5b21b6; }

.resource { font-weight: 500; color: #6b7280; }
.record-id { font-family: 'Courier New', monospace; color: #9ca3af; }
.ip-address { font-family: 'Courier New', monospace; color: #9ca3af; font-size: 0.85rem; }
.notes { color: #6b7280; font-size: 0.9rem; }
.empty { text-align: center; padding: 2rem; color: #94a3b8; }

.pagination { display: flex; align-items: center; justify-content: center; gap: 1rem; margin-top: 1.5rem; }
.btn-page { background: #f3f4f6; border: none; width: 36px; height: 36px; border-radius: 6px; cursor: pointer; transition: all 0.2s ease; }
.btn-page:hover:not(:disabled) { background: #e5e7eb; }
.btn-page:disabled { opacity: 0.5; cursor: not-allowed; }
.page-info { color: #6b7280; font-size: 0.95rem; }

.alert-toast { position: fixed; bottom: 2rem; right: 2rem; display: flex; align-items: center; gap: 1rem; padding: 1rem 1.5rem; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); z-index: 3000; animation: slideIn 0.3s ease; }
.alert-success { background: #d1fae5; color: #065f46; }
.alert-error { background: #fee2e2; color: #991b1b; }
.alert-warning { background: #fef3c7; color: #78350f; }
@keyframes slideIn { from { transform: translateX(400px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
</style>
