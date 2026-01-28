<template>
  <div class="um-page">
    <!-- Header -->
    <header class="um-header">
      <div class="um-title">
        <i class="fas fa-users-cog"></i>
        <div>
          <h1>System Management</h1>
          <p class="muted">Manage users, businesses, and platform settings</p>
        </div>
      </div>
      <button v-if="activeTab === 'users'" class="btn-create" @click="showCreateModal = true">
        <i class="fas fa-plus"></i> Create User
      </button>
      <button v-if="activeTab === 'companies'" class="btn-create" @click="showCreateCompanyModal = true">
        <i class="fas fa-plus"></i> Add Business
      </button>
    </header>

    <!-- Tabs Navigation -->
    <nav class="tabs-nav">
      <button 
        class="tab-btn" 
        :class="{ active: activeTab === 'users' }" 
        @click="activeTab = 'users'"
      >
        <i class="fas fa-users"></i>
        <span>Users</span>
        <span class="tab-count">{{ users.length }}</span>
      </button>
      <button 
        class="tab-btn" 
        :class="{ active: activeTab === 'companies' }" 
        @click="activeTab = 'companies'"
      >
        <i class="fas fa-building"></i>
        <span>Businesses</span>
        <span class="tab-count">{{ companies.length }}</span>
      </button>
    </nav>

    <!-- Users Tab Content -->
    <div v-if="activeTab === 'users'" class="tab-content">
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
      <div v-if="passwordResetProgress.active" class="inline-progress">
        <div class="progress-bar">
          <div class="progress-fill" :style="{ width: passwordResetProgress.percentage + '%' }"></div>
        </div>
        <p class="progress-text">
          <i class="fas fa-spinner fa-spin"></i>
          <span>{{ passwordResetProgress.message }}</span>
          <span v-if="passwordResetProgress.user" class="progress-user">for {{ passwordResetProgress.user.name }} ({{ passwordResetProgress.user.email }})</span>
        </p>
      </div>
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
              <button @click="openOtpModal(user)" class="btn-action btn-otp" title="Manage OTP">
                <i class="fas fa-shield-alt"></i>
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
    <div v-if="showCreateModal || showEditModal" class="modal-overlay">
      <div class="modal">
        <div class="modal-header">
          <h2>{{ showEditModal ? 'Edit User' : 'Create New User' }}</h2>
          <button @click="closeModal" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <!-- Progress Bar -->
        <div v-if="saving" class="progress-container">
          <div class="progress-bar">
            <div class="progress-fill" :style="{ width: saveProgress + '%' }"></div>
          </div>
          <p class="progress-text">{{ saveStatus }}</p>
        </div>

        <!-- scrollable body -->
        <div class="modal-body" :class="{ 'modal-body-disabled': saving }">
          <form @submit.prevent="saveUser" class="modal-form">
            <div class="form-group">
              <label>Name *</label>
              <input v-model="formData.name" type="text" required placeholder="Full Name" :disabled="saving" />
            </div>

            <div class="form-group">
              <label>Email *</label>
              <input v-model="formData.email" type="email" required placeholder="user@example.com" :disabled="saving" />
            </div>

            <div v-if="!showEditModal" class="form-group">
              <label>Password *</label>
              <input v-model="formData.password" type="password" required placeholder="Min 6 characters" :disabled="saving" />
            </div>

            <div class="form-group">
              <label>Role *</label>
              <select v-model="formData.role_id" required :disabled="saving">
                <option value="">Select Role</option>
                <option v-for="role in roles" :key="role.id" :value="role.id">
                  {{ formatRole(role.name) }}
                </option>
              </select>
            </div>

            <div class="form-group">
              <label>Business (Optional)</label>
              <select v-model="formData.company_id" :disabled="newCompany.create || saving">
                <option value="">None</option>
                <option v-for="company in companies" :key="company.id" :value="company.id">
                  {{ company.name }}
                </option>
              </select>
            </div>

            <div class="form-group small">
              <label><input type="checkbox" v-model="newCompany.create" :disabled="saving" /> Create a new company for this user</label>
            </div>

            <div v-if="newCompany.create" class="company-fields">
              <div class="form-group">
                <label>Company Name *</label>
                <input v-model="newCompany.name" type="text" required placeholder="Business name" :disabled="saving" />
              </div>
              <div class="form-group">
                <label>Company Email</label>
                <input v-model="newCompany.email" type="email" placeholder="business@example.com" :disabled="saving" />
              </div>
              <div class="form-group">
                <label>Phone</label>
                <input v-model="newCompany.phone" type="text" placeholder="+1 555 5555" :disabled="saving" />
              </div>
              <div class="form-group">
                <label>Category</label>
                <select v-model="newCompany.category" :disabled="saving">
                  <option value="">Select Category</option>
                  <option v-for="cat in businessCategories" :key="cat.id" :value="cat.name">
                    {{ cat.name }}
                  </option>
                </select>
              </div>
              <div class="form-group">
                <label>Address</label>
                <input v-model="newCompany.address" type="text" placeholder="Street, City, State" :disabled="saving" />
              </div>
            </div>
          </form>
        </div>

        <!-- actions always visible -->
        <div class="modal-actions">
          <button @click="saveUser" class="btn-primary" :disabled="saving">
            <span v-if="saving" class="btn-spinner"></span>
            {{ showEditModal ? 'Update' : 'Create' }}
          </button>
          <button type="button" @click="closeModal" class="btn-secondary" :disabled="saving">Cancel</button>
        </div>
      </div>
    </div>
    </div>

    <!-- Companies Tab Content -->
    <div v-if="activeTab === 'companies'" class="tab-content">
      <!-- Company Controls & Filters -->
      <section class="um-controls">
        <div class="search-bar">
          <i class="fas fa-search"></i>
          <input v-model="companyFilters.q" type="text" placeholder="Search businesses by name or email..." />
        </div>

        <div class="filter-group">
          <button class="btn-reset" @click="resetCompanyFilters">
            <i class="fas fa-redo"></i> Reset
          </button>
        </div>
      </section>

      <!-- Companies Table -->
      <section class="um-table-container">
        <div v-if="loadingCompanies" class="loading">
          <div class="spinner"></div>
          <p>Loading businesses...</p>
        </div>

        <div v-else-if="companyError" class="error-message">
          <i class="fas fa-exclamation-circle"></i>
          <span>{{ companyError }}</span>
          <button @click="fetchCompanies" class="btn-retry">Retry</button>
        </div>

        <table v-else class="users-table">
          <thead>
            <tr>
              <th>Business Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Category</th>
              <th>Users</th>
              <th>Created</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="company in displayedCompanies" :key="company.id">
              <td>
                <div class="company-name">
                  <i class="fas fa-building"></i>
                  {{ company.name }}
                </div>
              </td>
              <td>{{ company.email || '-' }}</td>
              <td>{{ company.phone || '-' }}</td>
              <td>{{ company.category || '-' }}</td>
              <td>
                <span class="badge badge-info">{{ company.users_count || 0 }} users</span>
              </td>
              <td>{{ formatDate(company.created_at) }}</td>
              <td class="actions">
                <button @click="editCompany(company)" class="btn-action btn-edit" title="Edit">
                  <i class="fas fa-edit"></i>
                </button>
                <button @click="confirmDeleteCompany(company)" class="btn-action btn-delete" title="Delete">
                  <i class="fas fa-trash"></i>
                </button>
              </td>
            </tr>
            <tr v-if="displayedCompanies.length === 0">
              <td colspan="7" class="empty">No businesses found</td>
            </tr>
          </tbody>
        </table>

        <!-- Company Pagination -->
        <div v-if="totalCompanyPages > 1" class="pagination">
          <button @click="currentCompanyPage--" :disabled="currentCompanyPage === 1" class="btn-page">
            <i class="fas fa-chevron-left"></i>
          </button>
          <span class="page-info">Page {{ currentCompanyPage }} of {{ totalCompanyPages }}</span>
          <button @click="currentCompanyPage++" :disabled="currentCompanyPage === totalCompanyPages" class="btn-page">
            <i class="fas fa-chevron-right"></i>
          </button>
        </div>
      </section>
    </div>

    <!-- Create/Edit Company Modal -->
    <div v-if="showCreateCompanyModal || showEditCompanyModal" class="modal-overlay">
      <div class="modal">
        <div class="modal-header">
          <h2>{{ showEditCompanyModal ? 'Edit Business' : 'Create New Business' }}</h2>
          <button @click="closeCompanyModal" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div v-if="savingCompany" class="progress-container">
          <div class="progress-bar">
            <div class="progress-fill" :style="{ width: saveCompanyProgress + '%' }"></div>
          </div>
          <p class="progress-text">{{ saveCompanyStatus }}</p>
        </div>

        <div class="modal-body" :class="{ 'modal-body-disabled': savingCompany }">
          <form @submit.prevent="saveCompany" class="modal-form">
            <div class="form-group">
              <label>Business Name *</label>
              <input v-model="companyFormData.name" type="text" required placeholder="Business Name" :disabled="savingCompany" />
            </div>

            <div class="form-group">
              <label>Email</label>
              <input v-model="companyFormData.email" type="email" placeholder="business@example.com" :disabled="savingCompany" />
            </div>

            <div class="form-group">
              <label>Phone</label>
              <input v-model="companyFormData.phone" type="text" placeholder="+1 555 5555" :disabled="savingCompany" />
            </div>

            <div class="form-group">
              <label>Category</label>
              <input v-model="companyFormData.category" type="text" placeholder="Retail, Wholesale, etc." :disabled="savingCompany" />
            </div>

            <div class="form-group">
              <label>Address</label>
              <textarea v-model="companyFormData.address" rows="3" placeholder="Business address" :disabled="savingCompany"></textarea>
            </div>
          </form>
        </div>

        <div class="modal-actions">
          <button @click="saveCompany" class="btn-primary" :disabled="savingCompany">
            <span v-if="savingCompany" class="btn-spinner"></span>
            {{ savingCompany ? 'Saving...' : (showEditCompanyModal ? 'Update' : 'Create') }}
          </button>
          <button type="button" @click="closeCompanyModal" class="btn-secondary" :disabled="savingCompany">
            Cancel
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Company Confirmation Modal -->
    <div v-if="showDeleteCompanyModal" class="modal-overlay">
      <div class="modal modal-sm">
        <div class="modal-header">
          <h2>Confirm Deletion</h2>
          <button @click="showDeleteCompanyModal = false" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div class="modal-body">
          <p>Are you sure you want to delete <strong>{{ deletingCompany?.name }}</strong>?</p>
          <p class="text-muted">This action cannot be undone.</p>
        </div>

        <div class="modal-actions">
          <button @click="deleteCompany" class="btn-danger" :disabled="deletingCompanyInProgress">
            <span v-if="deletingCompanyInProgress" class="btn-spinner"></span>
            {{ deletingCompanyInProgress ? 'Deleting...' : 'Delete' }}
          </button>
          <button type="button" @click="showDeleteCompanyModal = false" class="btn-secondary" :disabled="deletingCompanyInProgress">
            Cancel
          </button>
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

    <!-- OTP Management Modal -->
    <div v-if="showOtpModal.active" class="modal-overlay">
      <div class="modal">
        <div class="modal-header">
          <h2>OTP Management</h2>
          <button @click="closeOtpModal" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div class="modal-body">
          <div class="otp-info-card">
            <h3>{{ showOtpModal.user?.name }}</h3>
            <p class="otp-email">{{ showOtpModal.user?.email }}</p>

            <div class="otp-stats">
              <div class="stat-item">
                <label>OTP Resend Attempts:</label>
                <span class="stat-value" :class="{ 'limit-reached': showOtpModal.user?.otp_resend_count >= 3 }">
                  {{ showOtpModal.user?.otp_resend_count || 0 }} / 3
                </span>
              </div>

              <div class="stat-item">
                <label>Last OTP Request:</label>
                <span class="stat-value">
                  {{ showOtpModal.user?.last_otp_request_at ? formatDateTime(showOtpModal.user.last_otp_request_at) : 'Never' }}
                </span>
              </div>

              <div class="stat-item">
                <label>Last OTP Verified:</label>
                <span class="stat-value">
                  {{ showOtpModal.user?.last_otp_verified_at ? formatDateTime(showOtpModal.user.last_otp_verified_at) : 'Never' }}
                </span>
              </div>
            </div>

            <div v-if="showOtpModal.user?.otp_resend_count >= 3" class="otp-warning">
              <i class="fas fa-exclamation-triangle"></i>
              <p>This user has reached the OTP resend limit and cannot request new codes.</p>
            </div>
          </div>
        </div>

        <div class="modal-actions">
          <button 
            @click="resetOtpLimit" 
            class="btn-primary"
            :disabled="showOtpModal.isResetting || showOtpModal.user?.otp_resend_count < 3"
          >
            <span v-if="showOtpModal.isResetting" class="btn-spinner"></span>
            {{ showOtpModal.isResetting ? 'Resetting...' : 'Reset OTP Limit' }}
          </button>
          <button type="button" @click="closeOtpModal" class="btn-secondary" :disabled="showOtpModal.isResetting">
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import axios from 'axios'

// State
const activeTab = ref('users')
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

// Companies Tab State
const loadingCompanies = ref(false)
const companyError = ref(null)
const currentCompanyPage = ref(1)
const perCompanyPage = ref(20)
const totalCompanyPages = ref(1)
const showCreateCompanyModal = ref(false)
const showEditCompanyModal = ref(false)
const showDeleteCompanyModal = ref(false)
const deletingCompany = ref(null)
const deletingCompanyInProgress = ref(false)
const savingCompany = ref(false)
const saveCompanyProgress = ref(0)
const saveCompanyStatus = ref('')

const companyFilters = reactive({
  q: ''
})

const companyFormData = reactive({
  id: null,
  name: '',
  email: '',
  phone: '',
  category: '',
  address: ''
})

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

const passwordResetProgress = reactive({
  active: false,
  percentage: 0,
  message: '',
  user: null
})

const showOtpModal = reactive({
  active: false,
  user: null,
  isResetting: false
})

const saving = ref(false)
const saveProgress = ref(0)
const saveStatus = ref('')

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

const displayedCompanies = computed(() => {
  if (!Array.isArray(companies.value)) {
    return []
  }
  return companies.value.filter(c => {
    if (companyFilters.q && !c.name.toLowerCase().includes(companyFilters.q.toLowerCase()) && 
        !c.email?.toLowerCase().includes(companyFilters.q.toLowerCase())) return false
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

const formatDateTime = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleString('en-US', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
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

// Companies Tab Methods
function resetCompanyFilters() {
  companyFilters.q = ''
  currentCompanyPage.value = 1
}

async function fetchCompanies() {
  loadingCompanies.value = true
  companyError.value = null
  try {
    const token = localStorage.getItem('authToken')
    if (!token) {
      window.location.href = '/login'
      return
    }

    const res = await axios.get('http://127.0.0.1:8000/api/superuser/companies', {
      headers: { Authorization: `Bearer ${token}` }
    })
    
    console.log('Companies API response:', res.data)
    // Handle multiple response formats
    let data = []
    if (Array.isArray(res.data)) {
      data = res.data
    } else if (res.data.data && Array.isArray(res.data.data)) {
      data = res.data.data
    } else if (res.data.companies && Array.isArray(res.data.companies)) {
      data = res.data.companies
    }
    
    console.log('Extracted data:', data)
    console.log('Is array?', Array.isArray(data))
    companies.value = data
    console.log('Companies set to:', companies.value)
    totalCompanyPages.value = Math.ceil(companies.value.length / perCompanyPage.value)
    
  } catch (err) {
    console.error('Error fetching companies:', err)
    companyError.value = err.response?.data?.error || 'Failed to load businesses'
    companies.value = []
  } finally {
    loadingCompanies.value = false
  }
}

function editCompany(company) {
  showEditCompanyModal.value = true
  companyFormData.id = company.id
  companyFormData.name = company.name
  companyFormData.email = company.email || ''
  companyFormData.phone = company.phone || ''
  companyFormData.category = company.category || ''
  companyFormData.address = company.address || ''
}

function confirmDeleteCompany(company) {
  deletingCompany.value = company
  showDeleteCompanyModal.value = true
}

function closeCompanyModal() {
  showCreateCompanyModal.value = false
  showEditCompanyModal.value = false
  Object.assign(companyFormData, { id: null, name: '', email: '', phone: '', category: '', address: '' })
  saveCompanyProgress.value = 0
  saveCompanyStatus.value = ''
}

async function saveCompany() {
  try {
    if (!companyFormData.name) {
      showAlert('error', 'Business name is required')
      return
    }

    savingCompany.value = true
    saveCompanyProgress.value = 0
    saveCompanyStatus.value = 'Initializing...'

    const token = localStorage.getItem('authToken')
    if (!token) {
      window.location.href = '/login'
      return
    }

    const payload = {
      name: companyFormData.name,
      email: companyFormData.email || null,
      phone: companyFormData.phone || null,
      category: companyFormData.category || null,
      address: companyFormData.address || null
    }

    if (showEditCompanyModal.value) {
      saveCompanyStatus.value = 'Updating business details...'
      saveCompanyProgress.value = 30
      
      await axios.put(`http://127.0.0.1:8000/api/superuser/companies/${companyFormData.id}`, payload, {
        headers: { Authorization: `Bearer ${token}` }
      })
      
      saveCompanyStatus.value = 'Business updated successfully!'
      showAlert('success', 'Business updated successfully!')
    } else {
      saveCompanyStatus.value = 'Creating new business...'
      saveCompanyProgress.value = 30
      
      await axios.post('http://127.0.0.1:8000/api/superuser/companies', payload, {
        headers: { Authorization: `Bearer ${token}` }
      })
      
      saveCompanyStatus.value = 'Business created successfully!'
      showAlert('success', 'Business created successfully!')
    }

    saveCompanyProgress.value = 100
    
    await fetchCompanies()
    
    setTimeout(() => {
      closeCompanyModal()
    }, 500)
    
  } catch (err) {
    console.error('Error saving company:', err)
    const errorMsg = err.response?.data?.error || err.response?.data?.message || 'Failed to save business'
    showAlert('error', errorMsg)
    saveCompanyStatus.value = `Error: ${errorMsg}`
  } finally {
    savingCompany.value = false
  }
}

async function deleteCompany() {
  try {
    deletingCompanyInProgress.value = true
    
    const token = localStorage.getItem('authToken')
    if (!token) {
      window.location.href = '/login'
      return
    }

    await axios.delete(`http://127.0.0.1:8000/api/superuser/companies/${deletingCompany.value.id}`, {
      headers: { Authorization: `Bearer ${token}` }
    })

    showAlert('success', 'Business deleted successfully!')
    showDeleteCompanyModal.value = false
    deletingCompany.value = null
    
    await fetchCompanies()
    
  } catch (err) {
    console.error('Error deleting company:', err)
    const errorMsg = err.response?.data?.error || err.response?.data?.message || 'Failed to delete business'
    showAlert('error', errorMsg)
  } finally {
    deletingCompanyInProgress.value = false
  }
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
    // Validate password for new users
    if (!showEditModal.value && !formData.password) {
      showAlert('error', 'Password is required')
      return
    }

    if (!showEditModal.value && formData.password.length < 6) {
      showAlert('error', 'Password must be at least 6 characters')
      return
    }

    saving.value = true
    saveProgress.value = 0
    saveStatus.value = 'Initializing...'


    if (showEditModal.value) {
      saveStatus.value = 'Updating user details...'
      saveProgress.value = 30
      const payload = { 
        name: formData.name, 
        email: formData.email, 
        role_id: formData.role_id, 
        company_id: formData.company_id 
      }
      await axios.put(`/api/super/users/${formData.id}`, payload)
      saveProgress.value = 100
      saveStatus.value = 'User updated successfully'
      showAlert('success', 'User updated successfully')
    } else {
      let companyId = formData.company_id
      let companyPayload = null

      // Prepare company data if checkbox is checked
      if (newCompany.create) {
        companyPayload = {
          name: newCompany.name,
          email: newCompany.email,
          phone: newCompany.phone,
          category: newCompany.category,
          address: newCompany.address
        }
        companyId = null // Will be set by backend when creating nested company
      }

      // Now create user with the company_id and password
      saveProgress.value = 70
      saveStatus.value = 'Creating user account...'
      const payload = {
        name: formData.name,
        email: formData.email,
        password: formData.password,
        role_id: formData.role_id,
        company_id: companyId
      }
      if (companyPayload) {
        payload.company = companyPayload
      }

      await axios.post('/api/super/users', payload)
      saveProgress.value = 100
      saveStatus.value = 'User created successfully'
      showAlert('success', 'User created successfully')
    }

    setTimeout(() => {
      closeModal()
      // reset newCompany
      Object.assign(newCompany, { create: false, name: '', email: '', phone: '', category: '', address: '' })
      fetchUsers()
      fetchCompanies() // Refresh companies list
      saving.value = false
      saveProgress.value = 0
      saveStatus.value = ''
    }, 1000)
  } catch (e) {
    // Remove progress bar on error
    saveProgress.value = 0
    saveStatus.value = ''
    saving.value = false
    const errorMessage = e.response?.data?.error?.password?.[0] || e.response?.data?.message || 'Failed to save user'
    showAlert('error', errorMessage)
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
  const confirmed = confirm(`Send a new temporary password to ${user.email}? This will email them a reset password and require a change on next login.`)
  if (!confirmed) return

  try {
    passwordResetProgress.active = true
    passwordResetProgress.user = { name: user.name, email: user.email }
    passwordResetProgress.percentage = 25
    passwordResetProgress.message = 'Preparing reset...'
    await new Promise(resolve => setTimeout(resolve, 300))

    passwordResetProgress.percentage = 55
    passwordResetProgress.message = 'Generating new temporary password...'
    await axios.post(`/api/super/users/${user.id}/reset-password`)

    passwordResetProgress.percentage = 85
    passwordResetProgress.message = 'Sending email to user...'
    await new Promise(resolve => setTimeout(resolve, 300))

    passwordResetProgress.percentage = 100
    passwordResetProgress.message = 'Email sent successfully'
    showAlert('success', `Reset email sent to ${user.email}. User will need to change password on next login.`)
  } catch (e) {
    const msg = e.response?.data?.message || 'Failed to reset password'
    showAlert('error', msg)
  } finally {
    setTimeout(() => {
      passwordResetProgress.active = false
      passwordResetProgress.percentage = 0
      passwordResetProgress.message = ''
      passwordResetProgress.user = null
    }, 800)
  }
}
function openOtpModal(user) {
  showOtpModal.active = true
  showOtpModal.user = { ...user }
}

function closeOtpModal() {
  showOtpModal.active = false
  showOtpModal.user = null
  showOtpModal.isResetting = false
}

async function resetOtpLimit() {
  if (!showOtpModal.user?.id) return

  try {
    showOtpModal.isResetting = true
    const token = localStorage.getItem('authToken')
    
    const res = await axios.post(
      `/reset-otp-limit/${showOtpModal.user.id}`,
      {},
      {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      }
    )

    showAlert('success', `OTP limit reset for ${showOtpModal.user.name}`)
    
    // Update the modal user data
    showOtpModal.user.otp_resend_count = 0
    showOtpModal.user.last_otp_request_at = null
    
    // Refresh users list
    fetchUsers()
    
    setTimeout(() => {
      closeOtpModal()
    }, 1500)
  } catch (e) {
    showAlert('error', e.response?.data?.error || 'Failed to reset OTP limit')
  } finally {
    showOtpModal.isResetting = false
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

// Watch for tab changes and fetch companies when switching to companies tab
watch(activeTab, (newTab) => {
  if (newTab === 'companies' && companies.value.length === 0) {
    fetchCompanies()
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

/* Tabs Navigation */
.tabs-nav { 
  display: flex; 
  gap: 0.75rem; 
  margin-bottom: 2rem; 
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); 
  padding: 0.625rem; 
  border-radius: 16px; 
  box-shadow: 0 4px 20px rgba(0,0,0,0.08), inset 0 1px 0 rgba(255,255,255,0.5); 
  border: 1px solid rgba(102,126,234,0.1);
}

.tab-btn { 
  flex: 1; 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  gap: 0.875rem; 
  padding: 1rem 1.75rem; 
  background: transparent; 
  border: 2px solid transparent; 
  border-radius: 12px; 
  cursor: pointer; 
  font-weight: 600; 
  font-size: 1rem; 
  color: #6b7280; 
  transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1); 
  position: relative;
  overflow: hidden;
}

.tab-btn::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, rgba(102,126,234,0.05), rgba(118,75,162,0.05));
  opacity: 0;
  transition: opacity 0.3s ease;
  border-radius: 10px;
}

.tab-btn:hover::before { 
  opacity: 1; 
}

.tab-btn:hover { 
  color: #667eea; 
  transform: translateY(-2px);
  border-color: rgba(102,126,234,0.2);
}

.tab-btn.active { 
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
  color: #fff; 
  box-shadow: 0 8px 24px rgba(102,126,234,0.4), 0 2px 8px rgba(118,75,162,0.3); 
  border-color: rgba(255,255,255,0.3);
  transform: translateY(-3px);
}

.tab-btn.active::before {
  opacity: 0;
}

.tab-btn i { 
  font-size: 1.25rem; 
  transition: transform 0.3s ease;
}

.tab-btn:hover i,
.tab-btn.active i { 
  transform: scale(1.1);
}

.tab-count { 
  padding: 0.375rem 0.625rem; 
  background: rgba(102,126,234,0.15); 
  border-radius: 16px; 
  font-size: 0.8125rem; 
  font-weight: 700; 
  min-width: 28px;
  text-align: center;
  transition: all 0.3s ease;
  border: 1px solid rgba(102,126,234,0.2);
}

.tab-btn:hover .tab-count {
  background: rgba(102,126,234,0.25);
  transform: scale(1.05);
}

.tab-btn.active .tab-count { 
  background: rgba(255,255,255,0.25); 
  color: #fff;
  border-color: rgba(255,255,255,0.3);
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.tab-content { 
  animation: fadeIn 0.4s cubic-bezier(0.4, 0, 0.2, 1); 
}

@keyframes fadeIn { 
  from { 
    opacity: 0; 
    transform: translateY(20px) scale(0.98); 
  } 
  to { 
    opacity: 1; 
    transform: translateY(0) scale(1); 
  } 
}

@media (max-width: 768px) {
  .tabs-nav {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .tab-btn {
    padding: 0.875rem 1.25rem;
  }
}

.company-name { display: flex; align-items: center; gap: 0.5rem; font-weight: 600; }
.company-name i { color: #667eea; }
.modal-sm { max-width: 500px; }
.text-muted { color: #6b7280; font-size: 0.9rem; margin-top: 0.5rem; }
.btn-delete { color: #dc2626; background: rgba(220,38,38,0.1); }
.btn-delete:hover { background: rgba(220,38,38,0.2); }
.badge-info { background: #dbeafe; color: #1e40af; }

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
.btn-otp { color: #8b5cf6; background: rgba(139,92,246,0.1); }
.btn-otp:hover { background: rgba(139,92,246,0.2); }

.inline-progress {
  margin-bottom: 1rem;
  padding: 1rem 1.25rem;
  background: linear-gradient(135deg, #f8fafc 0%, #edf2f7 100%);
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.inline-progress .progress-bar {
  width: 100%;
  height: 8px;
  background: #e2e8f0;
  border-radius: 999px;
  overflow: hidden;
  margin-bottom: 0.5rem;
}

.inline-progress .progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
  transition: width 0.35s ease;
}

.inline-progress .progress-text {
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #2d3748;
  font-weight: 600;
}

.inline-progress .progress-text i {
  color: #667eea;
}

.progress-user {
  color: #4a5568;
  font-weight: 500;
}

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

.progress-container {
  padding: 1rem 1.5rem;
  background: #f0f9ff;
  border-bottom: 1px solid #bfdbfe;
}

.progress-bar {
  width: 100%;
  height: 6px;
  background: #e0e7ff;
  border-radius: 3px;
  overflow: hidden;
  margin-bottom: 0.75rem;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #667eea, #764ba2);
  transition: width 0.3s ease;
  border-radius: 3px;
}

.progress-text {
  margin: 0;
  font-size: 0.875rem;
  color: #0369a1;
  font-weight: 500;
}

.modal-body-disabled {
  opacity: 0.6;
  pointer-events: none;
}

.modal-body-disabled input,
.modal-body-disabled select {
  cursor: not-allowed;
  background: #f3f4f6;
}

.btn-primary:disabled,
.btn-secondary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-spinner {
  display: inline-block;
  width: 12px;
  height: 12px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: #fff;
  animation: spin 0.6s linear infinite;
  margin-right: 0.5rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* OTP Management Styles */
.otp-info-card {
  background: #f0f9ff;
  border: 2px solid #bfdbfe;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
}

.otp-info-card h3 {
  margin: 0 0 0.25rem;
  font-size: 1.125rem;
  color: #1e293b;
}

.otp-email {
  margin: 0 0 1rem;
  color: #6b7280;
  font-size: 0.95rem;
}

.otp-stats {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.stat-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  background: #fff;
  border-radius: 8px;
  border-left: 3px solid #667eea;
}

.stat-item label {
  font-weight: 500;
  color: #4b5563;
  margin: 0;
}

.stat-value {
  font-weight: 700;
  color: #667eea;
  font-size: 0.95rem;
}

.stat-value.limit-reached {
  color: #dc2626;
  background: #fee2e2;
  padding: 0.25rem 0.75rem;
  border-radius: 4px;
}

.otp-warning {
  background: #fef3c7;
  border: 1px solid #fcd34d;
  border-radius: 8px;
  padding: 1rem;
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  margin-top: 1rem;
  color: #78350f;
}

.otp-warning i {
  font-size: 1.1rem;
  margin-top: 0.1rem;
  flex-shrink: 0;
}

.otp-warning p {
  margin: 0;
  font-size: 0.95rem;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: #fff;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.3s ease;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
}

.btn-secondary {
  background: #f3f4f6;
  color: #6b7280;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.3s ease;
}

.btn-secondary:hover:not(:disabled) {
  background: #e5e7eb;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: #6b7280;
  cursor: pointer;
  padding: 0;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  transition: all 0.2s ease;
}

.btn-close:hover {
  background: #f3f4f6;
  color: #1f2937;
}
</style>