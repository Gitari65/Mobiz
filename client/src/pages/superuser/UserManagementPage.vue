<template>
  <div class="um-page">
    <!-- Header -->
    <header class="um-header">
      <div class="um-title">
        <i class="fas fa-users"></i>
        <div>
          <h1>User Management</h1>
          <p class="muted">Manage all platform users, roles, and permissions</p>
        </div>
      </div>
      <button class="btn-create" @click="showCreateModal = true">
        <i class="fas fa-plus"></i> Create User
      </button>
    </header>

    <!-- Controls & Filters -->
    <section class="um-controls">
      <div class="search-bar">
        <i class="fas fa-search"></i>
        <input v-model="filters.q" type="text" placeholder="Search by name or email..." />
      </div>

      <div class="filter-group">
        <select v-model="filters.role" class="filter-select">
          <option value="">All Roles</option>
          <option value="superuser">Super User</option>
          <option value="admin">Admin</option>
          <option value="cashier">Cashier</option>
        </select>

        <select v-model="filters.status" class="filter-select">
          <option value="">All Status</option>
          <option value="verified">Verified</option>
          <option value="unverified">Unverified</option>
        </select>

        <button class="btn-reset" @click="resetFilters">
          <i class="fas fa-redo"></i> Reset
        </button>
      </div>
    </section>

    <!-- Users Table -->
    <section class="um-table-container">
      <div v-if="loading" class="loading">
        <div class="spinner"></div>
        <p>Loading users...</p>
      </div>

      <div v-else-if="error" class="error-message">
        <i class="fas fa-exclamation-circle"></i>
        <span>{{ error }}</span>
        <button @click="fetchUsers" class="btn-retry">Retry</button>
      </div>

      <table v-else class="users-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Business</th>
            <th>Status</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in displayedUsers" :key="user.id" :class="{ 'row-inactive': !user.verified }">
            <td>{{ user.name }}</td>
            <td>{{ user.email }}</td>
            <td>
              <span class="badge" :class="`badge-${user.role?.name?.toLowerCase() || 'unknown'}`">
                {{ formatRole(user.role?.name) }}
              </span>
            </td>
            <td>{{ user.company?.name || '-' }}</td>
            <td>
              <span class="status-badge" :class="user.verified ? 'verified' : 'unverified'">
                {{ user.verified ? 'Verified' : 'Unverified' }}
              </span>
            </td>
            <td>{{ formatDate(user.created_at) }}</td>
            <td class="actions">
              <button @click="editUser(user)" class="btn-action btn-edit" title="Edit">
                <i class="fas fa-edit"></i>
              </button>
              <button @click="toggleUserStatus(user)" class="btn-action" :class="user.verified ? 'btn-deactivate' : 'btn-activate'" :title="user.verified ? 'Deactivate' : 'Activate'">
                <i :class="user.verified ? 'fas fa-ban' : 'fas fa-check'"></i>
              </button>
              <button @click="resetUserPassword(user)" class="btn-action btn-reset-pwd" title="Reset Password">
                <i class="fas fa-key"></i>
              </button>
            </td>
          </tr>
          <tr v-if="displayedUsers.length === 0">
            <td colspan="7" class="empty">No users found</td>
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

    <!-- Create/Edit Modal -->
    <div v-if="showCreateModal || showEditModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal">
        <div class="modal-header">
          <h2>{{ showEditModal ? 'Edit User' : 'Create New User' }}</h2>
          <button @click="closeModal" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <!-- scrollable body -->
        <div class="modal-body">
          <form @submit.prevent="saveUser" class="modal-form">
            <div class="form-group">
              <label>Name *</label>
              <input v-model="formData.name" type="text" required placeholder="Full Name" />
            </div>

            <div class="form-group">
              <label>Email *</label>
              <input v-model="formData.email" type="email" required placeholder="user@example.com" />
            </div>

            <div v-if="!showEditModal" class="form-group">
              <label>Password *</label>
              <input v-model="formData.password" type="password" required placeholder="Min 6 characters" />
            </div>

            <div class="form-group">
              <label>Role *</label>
              <select v-model="formData.role_id" required>
                <option value="">Select Role</option>
                <option v-for="role in roles" :key="role.id" :value="role.id">
                  {{ formatRole(role.name) }}
                </option>
              </select>
            </div>

            <div class="form-group">
              <label>Business (Optional)</label>
              <select v-model="formData.company_id" :disabled="newCompany.create">
                <option value="">None</option>
                <option v-for="company in companies" :key="company.id" :value="company.id">
                  {{ company.name }}
                </option>
              </select>
            </div>

            <div class="form-group small">
              <label><input type="checkbox" v-model="newCompany.create" /> Create a new company for this user</label>
            </div>

            <div v-if="newCompany.create" class="company-fields">
              <div class="form-group">
                <label>Company Name *</label>
                <input v-model="newCompany.name" type="text" required placeholder="Business name" />
              </div>
              <div class="form-group">
                <label>Company Email</label>
                <input v-model="newCompany.email" type="email" placeholder="business@example.com" />
              </div>
              <div class="form-group">
                <label>Phone</label>
                <input v-model="newCompany.phone" type="text" placeholder="+1 555 5555" />
              </div>
              <div class="form-group">
                <label>Category</label>
                <select v-model="newCompany.category">
                  <option value="">Select Category</option>
                  <option v-for="cat in businessCategories" :key="cat.id" :value="cat.name">
                    {{ cat.name }}
                  </option>
                </select>
              </div>
              <div class="form-group">
                <label>Address</label>
                <input v-model="newCompany.address" type="text" placeholder="Street, City, State" />
              </div>
            </div>
            <!-- keep form content only here; actions moved outside so they remain visible -->
          </form>
        </div>

        <!-- actions always visible -->
        <div class="modal-actions">
          <button @click="saveUser" class="btn-primary">
            {{ showEditModal ? 'Update' : 'Create' }}
          </button>
          <button type="button" @click="closeModal" class="btn-secondary">Cancel</button>
        </div>
      </div>
    </div>

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
import { ref, reactive, computed, onMounted } from 'vue'
import axios from 'axios'

// State
const loading = ref(false)
const error = ref(null)
const users = ref([])
const roles = ref([])
const companies = ref([])
const businessCategories = ref([]) // <-- added

const currentPage = ref(1)
const perPage = ref(20)
const totalPages = ref(1)

const showCreateModal = ref(false)
const showEditModal = ref(false)

const filters = reactive({
  q: '',
  role: '',
  status: ''
})

const formData = reactive({
  name: '',
  email: '',
  password: '',
  role_id: '',
  company_id: ''
})

const newCompany = reactive({
  create: false,
  name: '',
  email: '',
  phone: '',
  category: '',
  address: ''
})

const alert = reactive({
  show: false,
  type: 'info',
  message: '',
  timeout: null
})

// Computed
const displayedUsers = computed(() => {
  return users.value.filter(u => {
    if (filters.q && !u.name.toLowerCase().includes(filters.q.toLowerCase()) && 
        !u.email.toLowerCase().includes(filters.q.toLowerCase())) return false
    if (filters.role && u.role?.name?.toLowerCase() !== filters.role) return false
    if (filters.status === 'verified' && !u.verified) return false
    if (filters.status === 'unverified' && u.verified) return false
    return true
  })
})

// Methods
const showAlert = (type, message, duration = 3000) => {
  alert.show = true
  alert.type = type
  alert.message = message
  if (alert.timeout) clearTimeout(alert.timeout)
  alert.timeout = setTimeout(() => { alert.show = false }, duration)
}

const getAlertIcon = (type) => {
  const icons = { success: 'fas fa-check-circle', error: 'fas fa-exclamation-circle', warning: 'fas fa-exclamation-triangle', info: 'fas fa-info-circle' }
  return icons[type] || icons.info
}

const formatRole = (role) => {
  if (!role) return 'Unknown'
  return role.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

async function fetchUsers() {
  loading.value = true
  error.value = null
  try {
    // ensure we have an auth token before calling protected superuser endpoint
    const token = localStorage.getItem('authToken')
    if (!token) {
      // not authenticated — redirect to login (or show message)
      console.warn('No auth token, redirecting to login')
      window.location.href = '/login'
      return
    }

    const res = await axios.get('/api/super/users', {
      params: { q: filters.q, role: filters.role, status: filters.status, page: currentPage.value, per_page: perPage.value }
    })
    const data = res.data
    users.value = data.data || data.users || []
    totalPages.value = data.last_page || 1
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to fetch users'
    users.value = []
  } finally {
    loading.value = false
  }
}

async function fetchRoles() {
  try {
    const res = await axios.get('/api/roles')
    roles.value = res.data.roles || res.data.data || []
  } catch (e) {
    console.error('Failed to fetch roles', e)
    roles.value = []
  }
}

async function fetchCompanies() {
  try {
    const res = await axios.get('/api/companies')
    companies.value = res.data.companies || res.data.data || []
     console.log('Fetched companies:', companies.value)
  } catch (e) {
    console.error('Failed to fetch companies', e)
    companies.value = []
  }
}

async function fetchBusinessCategories() {
  try {
    const res = await axios.get('/api/business-categories')
    businessCategories.value = res.data.categories || res.data.data || []
  } catch (e) {
    console.error('Failed to fetch business categories', e)
    businessCategories.value = []
  }
}

function resetFilters() {
  filters.q = ''
  filters.role = ''
  filters.status = ''
  currentPage.value = 1
  fetchUsers()
}

function editUser(user) {
  showEditModal.value = true
  formData.name = user.name
  formData.email = user.email
  formData.role_id = user.role_id
  formData.company_id = user.company_id
  formData.id = user.id
  formData.password = ''
}

function closeModal() {
  showCreateModal.value = false
  showEditModal.value = false
  Object.assign(formData, { name: '', email: '', password: '', role_id: '', company_id: '', id: null })
}

async function saveUser() {
  try {
    if (showEditModal.value) {
      const payload = { name: formData.name, email: formData.email, role_id: formData.role_id, company_id: formData.company_id }
      await axios.put(`/api/super/users/${formData.id}`, payload)
      showAlert('success', 'User updated successfully')
    } else {
      // Build payload for creation. If newCompany.create true include nested company object.
      const payload = {
        name: formData.name,
        email: formData.email,
        password: formData.password,
        role_id: formData.role_id,
        company_id: newCompany.create ? null : formData.company_id
      }

      if (newCompany.create) {
        payload.company = {
          name: newCompany.name,
          email: newCompany.email,
          phone: newCompany.phone,
          category: newCompany.category,
          address: newCompany.address
        }
      }

      await axios.post('/api/super/users', payload)
      showAlert('success', 'User created successfully')
    }
    closeModal()
    // reset newCompany
    Object.assign(newCompany, { create:false, name:'', email:'', phone:'', category:'', address:'' })
    fetchUsers()
  } catch (e) {
    showAlert('error', e.response?.data?.message || 'Failed to save user')
  }
}

async function toggleUserStatus(user) {
  try {
    const endpoint = user.verified ? 'deactivate' : 'activate'
    await axios.patch(`/api/super/users/${user.id}/${endpoint}`)
    showAlert('success', `User ${endpoint}d successfully`)
    fetchUsers()
  } catch (e) {
    showAlert('error', `Failed to ${user.verified ? 'deactivate' : 'activate'} user`)
  }
}

async function resetUserPassword(user) {
  try {
    const res = await axios.post(`/api/super/users/${user.id}/reset-password`)
    showAlert('success', `Password reset. New temp password: ${res.data.temp_password}`)
  } catch (e) {
    showAlert('error', 'Failed to reset password')
  }
}

onMounted(() => {
  const token = localStorage.getItem('authToken')
  fetchRoles()
  fetchCompanies()
  fetchBusinessCategories()
  if (token) {
    fetchUsers()
  } else {
    // not logged in – avoid calling protected API
    console.warn('UserManagement: no auth token present; skipping fetchUsers and redirecting to login')
    window.location.href = '/login'
  }
})
</script>

<style scoped>
.um-page { max-width: 1400px; margin: 2rem auto; padding: 1.5rem; font-family: 'Inter', sans-serif; }

.um-header { display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 2rem; }
.um-title { display: flex; align-items: center; gap: 1rem; }
.um-title i { font-size: 1.75rem; color: #667eea; background: rgba(102,126,234,0.1); padding: 0.75rem; border-radius: 12px; }
.um-title h1 { margin: 0; font-size: 1.5rem; }
.muted { color: #6b7280; margin: 0; }

.btn-create { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; border: none; padding: 0.75rem 1.5rem; border-radius: 10px; cursor: pointer; font-weight: 600; transition: all 0.3s ease; }
.btn-create:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(102,126,234,0.3); }

.um-controls { display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
.search-bar { flex: 1; min-width: 250px; display: flex; align-items: center; gap: 0.75rem; background: #fff; border: 2px solid #e5e7eb; border-radius: 10px; padding: 0.75rem 1rem; }
.search-bar i { color: #a0aec0; }
.search-bar input { flex: 1; border: none; outline: none; font-size: 1rem; }

.filter-group { display: flex; gap: 0.75rem; }
.filter-select { padding: 0.75rem 1rem; border: 2px solid #e5e7eb; border-radius: 10px; background: #fff; font-size: 1rem; cursor: pointer; }
.btn-reset { background: #f3f4f6; color: #6b7280; border: none; padding: 0.75rem 1.5rem; border-radius: 10px; cursor: pointer; font-weight: 600; transition: all 0.3s ease; }
.btn-reset:hover { background: #e5e7eb; }

.um-table-container { background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: 0 6px 20px rgba(0,0,0,0.06); }

.loading, .error-message { text-align: center; padding: 2rem; }
.spinner { width: 40px; height: 40px; border: 3px solid #e5e7eb; border-left: 3px solid #667eea; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 1rem; }
@keyframes spin { to { transform: rotate(360deg); } }

.error-message { color: #991b1b; background: #fee2e2; border-radius: 8px; display: flex; align-items: center; gap: 0.75rem; }
.btn-retry { background: #dc2626; color: #fff; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; }

.users-table { width: 100%; border-collapse: collapse; }
.users-table th, .users-table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; }
.users-table th { background: #f9fafb; font-weight: 600; color: #374151; }
.users-table tr:hover { background: #f9fafb; }
.row-inactive { opacity: 0.7; }

.badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.875rem; font-weight: 500; }
.badge-superuser { background: #dbeafe; color: #1e40af; }
.badge-admin { background: #dcfce7; color: #166534; }
.badge-cashier { background: #fef3c7; color: #92400e; }

.status-badge { padding: 0.25rem 0.75rem; border-radius: 6px; font-size: 0.875rem; font-weight: 500; }
.status-badge.verified { background: #d1fae5; color: #065f46; }
.status-badge.unverified { background: #fee2e2; color: #991b1b; }

.actions { display: flex; gap: 0.5rem; }
.btn-action { background: none; border: none; width: 32px; height: 32px; border-radius: 6px; cursor: pointer; transition: all 0.2s ease; display: flex; align-items: center; justify-content: center; }
.btn-edit { color: #667eea; background: rgba(102,126,234,0.1); }
.btn-edit:hover { background: rgba(102,126,234,0.2); }
.btn-activate { color: #48bb78; background: rgba(72,187,120,0.1); }
.btn-activate:hover { background: rgba(72,187,120,0.2); }
.btn-deactivate { color: #dc2626; background: rgba(220,38,38,0.1); }
.btn-deactivate:hover { background: rgba(220,38,38,0.2); }
.btn-reset-pwd { color: #f59e0b; background: rgba(245,158,11,0.1); }
.btn-reset-pwd:hover { background: rgba(245,158,11,0.2); }

.pagination { display: flex; align-items: center; justify-content: center; gap: 1rem; margin-top: 1.5rem; }
.btn-page { background: #f3f4f6; border: none; width: 36px; height: 36px; border-radius: 6px; cursor: pointer; }
.btn-page:disabled { opacity: 0.5; cursor: not-allowed; }
.page-info { color: #6b7280; font-size: 0.95rem; }

.modal-overlay { position: fixed; inset: 0; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.5); z-index: 2000; }
.modal { background: #fff; border-radius: 16px; width: 100%; max-width: 600px; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.3); padding: 0; }
.modal-header { padding: 1.25rem 1.5rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eef2f7; background: #fff; flex: 0 0 auto; }
.modal-body { padding: 1.25rem 1.5rem; overflow-y: auto; -webkit-overflow-scrolling: touch; flex: 1 1 auto; }
.modal-actions { padding: 0.75rem 1.5rem; border-top: 1px solid #eef2f7; display: flex; justify-content: flex-end; gap: 1rem; background: #fff; flex: 0 0 auto; }

.modal-form { display: flex; flex-direction: column; gap: 1rem; }
.form-group { display: flex; flex-direction: column; gap: 0.5rem; }
.form-group label { font-weight: 600; color: #374151; }
.form-group input, .form-group select { padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 1rem; }

.company-fields { display:flex; flex-direction:column; gap:0.75rem; margin-top:0.5rem; }

.alert-toast { position: fixed; bottom: 2rem; right: 2rem; display: flex; align-items: center; gap: 1rem; padding: 1rem 1.5rem; border-radius: 10px; background: #d1fae5; color: #065f46; box-shadow: 0 10px 30px rgba(0,0,0,0.1); z-index: 3000; animation: slideIn 0.3s ease; }
.alert-success { background: #d1fae5; color: #065f46; }
.alert-error { background: #fee2e2; color: #991b1b; }
.alert-warning { background: #fef3c7; color: #78350f; }
@keyframes slideIn { from { transform: translateX(400px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
</style>