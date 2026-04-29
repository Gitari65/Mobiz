<template>
  <div class="audit-page">
    <header class="page-header">
      <div>
        <h1>Company Audit Monitor</h1>
        <p>Track transaction activity and review audit entries for your business.</p>
      </div>
      <button class="refresh-btn" @click="fetchLogs" :disabled="loading">
        <i class="fas fa-sync-alt" :class="{ spin: loading }"></i>
        Refresh
      </button>
    </header>

    <section class="summary-grid">
      <article class="summary-card">
        <span>Total Logs</span>
        <strong>{{ summary.total_logs || 0 }}</strong>
      </article>
      <article class="summary-card warning">
        <span>Open Reviews</span>
        <strong>{{ summary.open_reviews || 0 }}</strong>
      </article>
      <article class="summary-card info">
        <span>In Progress</span>
        <strong>{{ summary.in_progress_reviews || 0 }}</strong>
      </article>
      <article class="summary-card success">
        <span>Resolved</span>
        <strong>{{ summary.resolved_reviews || 0 }}</strong>
      </article>
      <article class="summary-card">
        <span>Transaction Logs</span>
        <strong>{{ summary.transaction_logs || 0 }}</strong>
      </article>
    </section>

    <section class="filters">
      <input v-model="filters.search" type="text" placeholder="Search action, model, user, notes..." @keyup.enter="applyFilters" />
      <select v-model="filters.action" @change="applyFilters">
        <option value="">All actions</option>
        <option v-for="action in actionOptions" :key="action" :value="action">{{ formatAction(action) }}</option>
      </select>
      <select v-model="filters.review_status" @change="applyFilters">
        <option value="">All review states</option>
        <option value="open">Open</option>
        <option value="in_progress">In Progress</option>
        <option value="resolved">Resolved</option>
      </select>
      <input v-model="filters.model" type="text" placeholder="Model name (Sale, Invoice...)" @keyup.enter="applyFilters" />
      <input v-model="filters.from" type="date" @change="applyFilters" />
      <input v-model="filters.to" type="date" @change="applyFilters" />
      <button class="secondary-btn" @click="resetFilters">Reset</button>
    </section>

    <section class="table-wrap">
      <div class="table-actions">
        <span>{{ meta.total || 0 }} records</span>
        <button
          class="save-btn"
          :disabled="!hasUnsavedChanges || saving"
          @click="saveChanges"
        >
          {{ saving ? 'Saving...' : `Save Changes${unsavedCount ? ` (${unsavedCount})` : ''}` }}
        </button>
        <button
          class="danger-btn"
          :disabled="selectedIds.length === 0 || deleting"
          @click="deleteSelected"
        >
          Delete Selected ({{ selectedIds.length }})
        </button>
      </div>

      <div v-if="loading" class="status-box">Loading audit entries...</div>
      <div v-else-if="error" class="status-box error">{{ error }}</div>
      <table v-else class="audit-table">
        <thead>
          <tr>
            <th>
              <input
                type="checkbox"
                :checked="allSelected"
                @change="toggleSelectAll"
              />
            </th>
            <th>Time</th>
            <th>User</th>
            <th>Action</th>
            <th>Model</th>
            <th>Record</th>
            <th>Review</th>
            <th>Notes</th>
            <th>Ops</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in rows" :key="row.id">
            <td><input type="checkbox" :value="row.id" v-model="selectedIds" /></td>
            <td>{{ formatDateTime(row.created_at) }}</td>
            <td>{{ row.user_name || 'System' }}</td>
            <td><span class="badge">{{ formatAction(row.action) }}</span></td>
            <td>{{ modelName(row.auditable_type) }}</td>
            <td>#{{ row.auditable_id || '-' }}</td>
            <td>
              <select v-model="row.review_status" @change="markRowDirty(row)">
                <option value="open">Open</option>
                <option value="in_progress">In Progress</option>
                <option value="resolved">Resolved</option>
              </select>
            </td>
            <td>
              <input
                v-model="row.notes"
                type="text"
                maxlength="1000"
                placeholder="Add investigation note"
                @input="markRowDirty(row)"
              />
            </td>
            <td>
              <button class="danger-link" @click="deleteOne(row.id)">Delete</button>
            </td>
          </tr>
          <tr v-if="rows.length === 0">
            <td colspan="9" class="empty">No audit entries found.</td>
          </tr>
        </tbody>
      </table>

      <footer class="pagination" v-if="meta.last_page > 1">
        <button class="secondary-btn" :disabled="meta.current_page <= 1" @click="changePage(meta.current_page - 1)">Prev</button>
        <span>Page {{ meta.current_page }} / {{ meta.last_page }}</span>
        <button class="secondary-btn" :disabled="meta.current_page >= meta.last_page" @click="changePage(meta.current_page + 1)">Next</button>
      </footer>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import axios from 'axios'

const loading = ref(false)
const deleting = ref(false)
const saving = ref(false)
const error = ref('')
const rows = ref([])
const selectedIds = ref([])
const originalRowsById = ref({})
const dirtyRowIds = ref([])

const summary = reactive({
  total_logs: 0,
  open_reviews: 0,
  in_progress_reviews: 0,
  resolved_reviews: 0,
  transaction_logs: 0,
})

const meta = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0,
})

const filters = reactive({
  search: '',
  action: '',
  review_status: '',
  model: '',
  from: '',
  to: '',
})

const actionOptions = computed(() => {
  const set = new Set(rows.value.map((item) => item.action).filter(Boolean))
  return Array.from(set).sort()
})

const allSelected = computed(() => rows.value.length > 0 && selectedIds.value.length === rows.value.length)
const unsavedCount = computed(() => dirtyRowIds.value.length)
const hasUnsavedChanges = computed(() => unsavedCount.value > 0)

function formatAction(action) {
  return String(action || '').replace(/_/g, ' ').replace(/\b\w/g, (m) => m.toUpperCase()) || '-'
}

function modelName(type) {
  const value = String(type || '')
  const split = value.split('\\')
  return split[split.length - 1] || '-'
}

function formatDateTime(value) {
  if (!value) return '-'
  return new Date(value).toLocaleString()
}

function requestParams() {
  return {
    page: meta.current_page,
    per_page: meta.per_page,
    search: filters.search || undefined,
    action: filters.action || undefined,
    review_status: filters.review_status || undefined,
    model: filters.model || undefined,
    from: filters.from || undefined,
    to: filters.to || undefined,
  }
}

async function fetchLogs() {
  loading.value = true
  error.value = ''

  try {
    const res = await axios.get('/admin/audit-logs', { params: requestParams() })
    const data = res.data || {}

    rows.value = Array.isArray(data.data) ? data.data : []
    originalRowsById.value = rows.value.reduce((acc, row) => {
      acc[row.id] = {
        review_status: row.review_status || 'open',
        notes: row.notes || '',
      }
      return acc
    }, {})
    dirtyRowIds.value = []

    Object.assign(meta, data.meta || {})
    Object.assign(summary, data.summary || {})

    selectedIds.value = selectedIds.value.filter((id) => rows.value.some((row) => row.id === id))
  } catch (err) {
    rows.value = []
    error.value = err.response?.data?.message || 'Failed to load audit logs.'
  } finally {
    loading.value = false
  }
}

function markRowDirty(row) {
  const original = originalRowsById.value[row.id]
  if (!original) return

  const statusChanged = (row.review_status || 'open') !== (original.review_status || 'open')
  const notesChanged = (row.notes || '') !== (original.notes || '')
  const changed = statusChanged || notesChanged

  if (changed && !dirtyRowIds.value.includes(row.id)) {
    dirtyRowIds.value.push(row.id)
  }

  if (!changed && dirtyRowIds.value.includes(row.id)) {
    dirtyRowIds.value = dirtyRowIds.value.filter((id) => id !== row.id)
  }
}

function applyFilters() {
  meta.current_page = 1
  fetchLogs()
}

function resetFilters() {
  filters.search = ''
  filters.action = ''
  filters.review_status = ''
  filters.model = ''
  filters.from = ''
  filters.to = ''
  applyFilters()
}

function changePage(page) {
  meta.current_page = page
  fetchLogs()
}

function toggleSelectAll(event) {
  if (event.target.checked) {
    selectedIds.value = rows.value.map((row) => row.id)
    return
  }

  selectedIds.value = []
}

async function saveChanges() {
  if (!dirtyRowIds.value.length) return

  saving.value = true
  error.value = ''

  try {
    const payloads = dirtyRowIds.value
      .map((id) => rows.value.find((row) => row.id === id))
      .filter(Boolean)
      .map((row) => axios.patch(`/admin/audit-logs/${row.id}`, {
        review_status: row.review_status,
        notes: row.notes || null,
      }))

    await Promise.all(payloads)
    await fetchLogs()
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to update audit entry.'
  } finally {
    saving.value = false
  }
}

async function deleteOne(id) {
  if (!window.confirm('Delete this audit entry?')) return

  try {
    await axios.delete(`/admin/audit-logs/${id}`)
    await fetchLogs()
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to delete audit entry.'
  }
}

async function deleteSelected() {
  if (!selectedIds.value.length) return
  if (!window.confirm(`Delete ${selectedIds.value.length} selected audit entries?`)) return

  deleting.value = true
  try {
    await axios.delete('/admin/audit-logs', { data: { ids: selectedIds.value } })
    selectedIds.value = []
    await fetchLogs()
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to delete selected entries.'
  } finally {
    deleting.value = false
  }
}

onMounted(fetchLogs)
</script>

<style scoped>
.audit-page {
  padding: 1.5rem;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1rem;
}

.page-header h1 {
  margin: 0;
}

.page-header p {
  margin: 0.25rem 0 0;
  color: #64748b;
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.summary-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 0.8rem;
}

.summary-card span {
  display: block;
  color: #475569;
  font-size: 0.85rem;
}

.summary-card strong {
  font-size: 1.3rem;
}

.summary-card.warning {
  border-color: #f59e0b;
}

.summary-card.info {
  border-color: #0ea5e9;
}

.summary-card.success {
  border-color: #22c55e;
}

.filters {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
  gap: 0.6rem;
  margin-bottom: 1rem;
}

.filters input,
.filters select {
  width: 100%;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  padding: 0.55rem 0.65rem;
}

.table-wrap {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 0.75rem;
}

.table-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.audit-table {
  width: 100%;
  border-collapse: collapse;
}

.audit-table th,
.audit-table td {
  border-bottom: 1px solid #e2e8f0;
  padding: 0.55rem;
  text-align: left;
  vertical-align: middle;
}

.audit-table td input,
.audit-table td select {
  width: 100%;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  padding: 0.35rem 0.45rem;
}

.badge {
  border-radius: 999px;
  background: #e2e8f0;
  padding: 0.15rem 0.45rem;
  font-size: 0.78rem;
}

.empty {
  text-align: center;
  color: #64748b;
}

.status-box {
  padding: 1rem;
  border-radius: 8px;
  background: #f8fafc;
}

.status-box.error {
  background: #fef2f2;
  color: #b91c1c;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 0.75rem;
  margin-top: 0.9rem;
}

.refresh-btn,
.secondary-btn,
.danger-btn,
.danger-link,
.save-btn {
  border: none;
  border-radius: 8px;
  padding: 0.5rem 0.75rem;
  cursor: pointer;
}

.save-btn {
  background: #16a34a;
  color: #fff;
}

.save-btn:disabled {
  background: #94a3b8;
  cursor: not-allowed;
}

.refresh-btn {
  background: #0f172a;
  color: #fff;
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
}

.secondary-btn {
  background: #e2e8f0;
  color: #0f172a;
}

.danger-btn,
.danger-link {
  background: #ef4444;
  color: white;
}

.danger-link {
  padding: 0.35rem 0.6rem;
}

.spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
