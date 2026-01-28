<template>
  <div class="admin-users-page">
    <header class="admin-header">
      <div>
        <h1>Manage Business Data</h1>
        <p class="muted">Manage users, suppliers, and customers for your business.</p>
      </div>
    </header>

    <!-- Tab Navigation -->
    <nav class="tab-navigation">
      <button 
        class="tab-btn" 
        :class="{ active: activeTab === 'users' }" 
        @click="activeTab = 'users'"
      >
        <i class="fas fa-users"></i> Users
      </button>
      <button 
        class="tab-btn" 
        :class="{ active: activeTab === 'suppliers' }" 
        @click="activeTab = 'suppliers'"
      >
        <i class="fas fa-truck"></i> Suppliers
      </button>
      <button 
        class="tab-btn" 
        :class="{ active: activeTab === 'customers' }" 
        @click="activeTab = 'customers'"
      >
        <i class="fas fa-user-tie"></i> Customers
      </button>
    </nav>

    <!-- Users Tab -->
    <div v-show="activeTab === 'users'" class="tab-content">
      <section class="admin-controls">
        <div class="search-bar">
          <i class="fas fa-search"></i>
          <input v-model="search" type="text" placeholder="Search by name or email..." />
        </div>
        <div class="control-buttons">
          <button class="btn-refresh" @click="fetchUsers">
            <i class="fas fa-sync-alt"></i> Refresh
          </button>
          <button class="btn-create" @click="showCreateModal = true">
            <i class="fas fa-user-plus"></i> Add User
          </button>
        </div>
      </section>

    <section class="admin-table-container">
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
            <th>Status</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in filteredUsers" :key="user.id">
            <td>{{ user.name }}</td>
            <td>{{ user.email }}</td>
            <td>
              <span class="badge" :class="`badge-${user.role?.name?.toLowerCase() || 'unknown'}`">
                {{ formatRole(user.role?.name) }}
              </span>
            </td>
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
              <button @click="deleteUser(user)" class="btn-action btn-delete" title="Delete">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
          <tr v-if="filteredUsers.length === 0">
            <td colspan="6" class="empty">No users found</td>
          </tr>
        </tbody>
      </table>
    </section>
    </div>

    <!-- Suppliers Tab -->
    <div v-show="activeTab === 'suppliers'" class="tab-content">
      <section class="admin-controls">
        <div class="search-bar">
          <i class="fas fa-search"></i>
          <input v-model="supplierSearch" type="text" placeholder="Search suppliers..." />
        </div>
        <div class="control-buttons">
          <button class="btn-refresh" @click="fetchSuppliers">
            <i class="fas fa-sync-alt"></i> Refresh
          </button>
          <button class="btn-create" @click="showBatchModal('supplier')">
            <i class="fas fa-upload"></i> Batch Import
          </button>
          <button class="btn-create" @click="showAddSupplier = true">
            <i class="fas fa-plus"></i> Add Supplier
          </button>
        </div>
      </section>

      <section class="admin-table-container">
        <div v-if="loadingSuppliers" class="loading">
          <div class="spinner"></div>
          <p>Loading suppliers...</p>
        </div>
        <table v-else class="users-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Contact Person</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Address</th>
              <th>Products</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="supplier in filteredSuppliers" :key="supplier.id">
              <td><strong>{{ supplier.name }}</strong></td>
              <td>{{ supplier.contact_person || '-' }}</td>
              <td>{{ supplier.email || '-' }}</td>
              <td>{{ supplier.phone || '-' }}</td>
              <td>{{ supplier.address || '-' }}</td>
              <td>{{ supplier.products_supplied || '-' }}</td>
              <td class="actions">
                <button @click="editSupplier(supplier)" class="btn-action btn-edit" title="Edit">
                  <i class="fas fa-edit"></i>
                </button>
                <button @click="deleteSupplier(supplier)" class="btn-action btn-delete" title="Delete">
                  <i class="fas fa-trash"></i>
                </button>
              </td>
            </tr>
            <tr v-if="filteredSuppliers.length === 0">
              <td colspan="7" class="empty">No suppliers found</td>
            </tr>
          </tbody>
        </table>
      </section>
    </div>

    <!-- Customers Tab -->
    <div v-show="activeTab === 'customers'" class="tab-content">
      <section class="admin-controls">
        <div class="search-bar">
          <i class="fas fa-search"></i>
          <input v-model="customerSearch" type="text" placeholder="Search customers..." />
        </div>
        <div class="control-buttons">
          <button class="btn-refresh" @click="fetchCustomers">
            <i class="fas fa-sync-alt"></i> Refresh
          </button>
          <button class="btn-create" @click="showBatchModal('customer')">
            <i class="fas fa-upload"></i> Batch Import
          </button>
          <button class="btn-create" @click="showAddCustomer = true">
            <i class="fas fa-plus"></i> Add Customer
          </button>
        </div>
      </section>

      <section class="admin-table-container">
        <div v-if="loadingCustomers" class="loading">
          <div class="spinner"></div>
          <p>Loading customers...</p>
        </div>
        <table v-else class="users-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Address</th>
              <th>Tax ID</th>
              <th>Price Group</th>
              <th>Total Orders</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="customer in filteredCustomers" :key="customer.id">
              <td><strong>{{ customer.name }}</strong></td>
              <td>{{ customer.email || '-' }}</td>
              <td>{{ customer.phone || '-' }}</td>
              <td>{{ customer.address || '-' }}</td>
              <td>{{ customer.tax_id || '-' }}</td>
              <td>
                <span v-if="customer.price_group" class="badge">{{ customer.price_group.name }}</span>
                <span v-else class="text-muted">-</span>
              </td>
              <td>{{ customer.total_orders || 0 }}</td>
              <td class="actions">
                <button @click="editCustomer(customer)" class="btn-action btn-edit" title="Edit">
                  <i class="fas fa-edit"></i>
                </button>
                <button @click="deleteCustomer(customer)" class="btn-action btn-delete" title="Delete">
                  <i class="fas fa-trash"></i>
                </button>
              </td>
            </tr>
            <tr v-if="filteredCustomers.length === 0">
              <td colspan="7" class="empty">No customers found</td>
            </tr>
          </tbody>
        </table>
      </section>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showCreateModal || showEditModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal">
        <div class="modal-header">
          <h2>{{ showEditModal ? 'Edit User' : 'Add New User' }}</h2>
          <button @click="closeModal" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
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
                <option v-for="role in roles" :key="role.id" :value="role.id">
                  {{ formatRole(role.name) }}
                </option>
              </select>
            </div>
          </form>
        </div>
        <div class="modal-actions">
          <button @click="saveUser" class="btn-primary">
            {{ showEditModal ? 'Update' : 'Create' }}
          </button>
          <button type="button" @click="closeModal" class="btn-secondary">Cancel</button>
        </div>
      </div>
    </div>

    <!-- Add/Edit Supplier Modal -->
    <div v-if="showAddSupplier || showEditSupplier" class="modal-overlay" @click.self="closeSupplierModal">
      <div class="modal modal-large">
        <div class="modal-header">
          <h2>{{ showEditSupplier ? 'Edit Supplier' : 'Add New Supplier' }}</h2>
          <button @click="closeSupplierModal" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="saveSupplier" class="modal-form">
            <div class="form-row">
              <div class="form-group">
                <label>Supplier Name *</label>
                <input v-model="supplierForm.name" type="text" required placeholder="Company Name" />
              </div>
              <div class="form-group">
                <label>Contact Person</label>
                <input v-model="supplierForm.contact_person" type="text" placeholder="John Doe" />
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Email</label>
                <input v-model="supplierForm.email" type="email" placeholder="supplier@example.com" />
              </div>
              <div class="form-group">
                <label>Phone</label>
                <input v-model="supplierForm.phone" type="tel" placeholder="+254712345678" />
              </div>
            </div>
            <div class="form-group">
              <label>Address</label>
              <textarea v-model="supplierForm.address" rows="2" placeholder="Street, City, Country"></textarea>
            </div>
            <div class="form-group">
              <label>Products Supplied</label>
              <input v-model="supplierForm.products_supplied" type="text" placeholder="Electronics, Furniture, etc." />
            </div>
            <div class="form-group">
              <label>Notes</label>
              <textarea v-model="supplierForm.notes" rows="3" placeholder="Additional information..."></textarea>
            </div>
          </form>
        </div>
        <div class="modal-actions">
          <button @click="saveSupplier" class="btn-primary">
            <i class="fas fa-save"></i> {{ showEditSupplier ? 'Update' : 'Create' }}
          </button>
          <button type="button" @click="closeSupplierModal" class="btn-secondary">Cancel</button>
        </div>
      </div>
    </div>

    <!-- Add/Edit Customer Modal -->
    <div v-if="showAddCustomer || showEditCustomer" class="modal-overlay" @click.self="closeCustomerModal">
      <div class="modal modal-large">
        <div class="modal-header">
          <h2>{{ showEditCustomer ? 'Edit Customer' : 'Add New Customer' }}</h2>
          <button @click="closeCustomerModal" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="saveCustomer" class="modal-form">
            <div class="form-row">
              <div class="form-group">
                <label>Customer Name *</label>
                <input v-model="customerForm.name" type="text" required placeholder="Full Name" />
              </div>
              <div class="form-group">
                <label>Phone</label>
                <input v-model="customerForm.phone" type="tel" placeholder="+254712345678" />
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Email</label>
                <input v-model="customerForm.email" type="email" placeholder="customer@example.com" />
              </div>
              <div class="form-group">
                <label>Tax ID / KRA PIN</label>
                <input v-model="customerForm.tax_id" type="text" placeholder="A123456789Z" />
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Price Group</label>
                <select v-model="customerForm.price_group_id">
                  <option :value="null">Default Pricing</option>
                  <option v-for="group in priceGroups" :key="group.id" :value="group.id">
                    {{ group.name }} ({{ group.discount_percentage }}% off)
                  </option>
                </select>
              </div>
            </div>
            <div class="form-group">
              <label>Address</label>
              <textarea v-model="customerForm.address" rows="2" placeholder="Street, City, Country"></textarea>
            </div>
            <div class="form-group">
              <label>Notes</label>
              <textarea v-model="customerForm.notes" rows="3" placeholder="Customer preferences, special instructions..."></textarea>
            </div>
          </form>
        </div>
        <div class="modal-actions">
          <button @click="saveCustomer" class="btn-primary">
            <i class="fas fa-save"></i> {{ showEditCustomer ? 'Update' : 'Create' }}
          </button>
          <button type="button" @click="closeCustomerModal" class="btn-secondary">Cancel</button>
        </div>
      </div>
    </div>

    <!-- Batch Import Modal -->
    <div v-if="showBatchImport" class="modal-overlay" @click.self="closeBatchModal">
      <div class="modal modal-large">
        <div class="modal-header">
          <h2>Batch Import {{ batchType === 'supplier' ? 'Suppliers' : 'Customers' }}</h2>
          <button @click="closeBatchModal" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="batch-import-container">
            <div class="import-instructions">
              <h3><i class="fas fa-info-circle"></i> Instructions</h3>
              <ol>
                <li>Download the Excel template with dropdown options</li>
                <li>Fill in the required fields for each {{ batchType }}</li>
                <li>Upload the completed file to import</li>
              </ol>
              <button @click="downloadTemplate(batchType)" class="btn-download">
                <i class="fas fa-download"></i> Download Excel Template
              </button>
            </div>

            <div class="upload-zone" :class="{ 'dragover': isDragOver }" 
                 @dragover.prevent="isDragOver = true"
                 @dragleave.prevent="isDragOver = false"
                 @drop.prevent="handleFileDrop">
              <i class="fas fa-cloud-upload-alt"></i>
              <h4>Drag & Drop Excel/CSV File</h4>
              <p>or click to browse</p>
              <input 
                type="file" 
                ref="batchFileInput"
                accept=".csv,.xlsx,.xls"
                @change="handleFileSelect"
                class="file-input"
              />
              <button type="button" class="browse-btn" @click="$refs.batchFileInput.click()">
                Browse Files
              </button>
            </div>

            <div v-if="batchFile" class="file-preview">
              <div class="file-info">
                <i class="fas fa-file-excel"></i>
                <span>{{ batchFile.name }}</span>
                <button type="button" @click="batchFile = null; batchData = []" class="remove-file-btn">
                  <i class="fas fa-times"></i>
                </button>
              </div>
              <div v-if="batchData.length > 0" class="preview-stats">
                <span class="stat-badge">{{ batchData.length }} records found</span>
              </div>
            </div>

            <div v-if="batchData.length > 0" class="preview-table-container">
              <h4>Preview (First 5 records)</h4>
              <table class="preview-table">
                <thead>
                  <tr>
                    <th v-for="(value, key) in batchData[0]" :key="key">{{ key }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, index) in batchData.slice(0, 5)" :key="index">
                    <td v-for="(value, key) in item" :key="key">{{ value || '-' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <div class="modal-actions">
          <button @click="importBatchData" class="btn-primary" :disabled="batchData.length === 0 || importing">
            <div v-if="importing" class="btn-loading">
              <div class="btn-spinner"></div>
              Importing...
            </div>
            <div v-else>
              <i class="fas fa-upload"></i> Import {{ batchData.length }} {{ batchType }}{{ batchData.length !== 1 ? 's' : '' }}
            </div>
          </button>
          <button type="button" @click="closeBatchModal" class="btn-secondary">Cancel</button>
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
import ExcelJS from 'exceljs'

const activeTab = ref('users')
const loading = ref(false)
const error = ref(null)
const users = ref([])
const roles = ref([])
const search = ref('')
const showCreateModal = ref(false)
const showEditModal = ref(false)

// Suppliers
const suppliers = ref([])
const loadingSuppliers = ref(false)
const supplierSearch = ref('')
const showAddSupplier = ref(false)
const showEditSupplier = ref(false)
const supplierForm = reactive({
  id: null,
  name: '',
  contact_person: '',
  email: '',
  phone: '',
  address: '',
  products_supplied: '',
  notes: ''
})

// Customers
const customers = ref([])
const loadingCustomers = ref(false)
const customerSearch = ref('')
const showAddCustomer = ref(false)
const showEditCustomer = ref(false)
const priceGroups = ref([])
const customerForm = reactive({
  id: null,
  name: '',
  email: '',
  phone: '',
  address: '',
  tax_id: '',
  notes: '',
  price_group_id: null
})

// Batch Import
const showBatchImport = ref(false)
const batchType = ref('supplier')
const batchFile = ref(null)
const batchData = ref([])
const isDragOver = ref(false)
const importing = ref(false)

const formData = reactive({
  name: '',
  email: '',
  password: '',
  role_id: '',
  id: null
})
const alert = reactive({
  show: false,
  type: 'info',
  message: '',
  timeout: null
})

const fetchUsers = async () => {
  loading.value = true
  error.value = null
  try {
    const res = await axios.get('/users')
    // Exclude superusers from the list
    const allUsers = res.data.data || res.data.users || []
    users.value = allUsers.filter(u => u.role?.name?.toLowerCase() !== 'superuser')
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to fetch users'
    users.value = []
  } finally {
    loading.value = false
  }
}

const fetchRoles = async () => {
  try {
    const res = await axios.get('/api/roles')
    const allRoles = res.data.roles || res.data.data || []
    // Only allow cashier role for admin
    roles.value = allRoles.filter(r => r.name.toLowerCase() === 'cashier')
  } catch (e) {
    roles.value = []
  }
}

const filteredUsers = computed(() => {
  if (!search.value) return users.value
  const q = search.value.toLowerCase()
  return users.value.filter(u =>
    u.name.toLowerCase().includes(q) ||
    u.email.toLowerCase().includes(q)
  )
})

const filteredSuppliers = computed(() => {
  if (!supplierSearch.value) return suppliers.value
  const q = supplierSearch.value.toLowerCase()
  return suppliers.value.filter(s =>
    s.name.toLowerCase().includes(q) ||
    (s.email && s.email.toLowerCase().includes(q)) ||
    (s.contact_person && s.contact_person.toLowerCase().includes(q))
  )
})

const filteredCustomers = computed(() => {
  if (!customerSearch.value) return customers.value
  const q = customerSearch.value.toLowerCase()
  return customers.value.filter(c =>
    c.name.toLowerCase().includes(q) ||
    (c.email && c.email.toLowerCase().includes(q)) ||
    (c.phone && c.phone.includes(q))
  )
})

const formatRole = (role) => {
  if (!role) return 'Unknown'
  return role.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

const showAlert = (type, message, duration = 3000) => {
  alert.show = true
  alert.type = type
  alert.message = message
  if (alert.timeout) clearTimeout(alert.timeout)
  alert.timeout = setTimeout(() => { alert.show = false }, duration)
}

const editUser = (user) => {
  showEditModal.value = true
  formData.name = user.name
  formData.email = user.email
  formData.role_id = user.role_id
  formData.id = user.id
  formData.password = ''
}

const closeModal = () => {
  showCreateModal.value = false
  showEditModal.value = false
  Object.assign(formData, { name: '', email: '', password: '', role_id: '', id: null })
}

const saveUser = async () => {
  try {
    if (showEditModal.value) {
      const payload = { name: formData.name, email: formData.email, role_id: formData.role_id }
      await axios.put(`/users/${formData.id}`, payload)
      showAlert('success', 'User updated successfully')
    } else {
      const payload = {
        name: formData.name,
        email: formData.email,
        password: formData.password,
        role_id: formData.role_id
      }
      await axios.post('/users', payload)
      showAlert('success', 'User created successfully')
    }
    closeModal()
    fetchUsers()
  } catch (e) {
    showAlert('error', e.response?.data?.message || 'Failed to save user')
  }
}

const deleteUser = async (user) => {
  if (!confirm(`Delete user ${user.name}?`)) return
  try {
    await axios.delete(`/users/${user.id}`)
    showAlert('success', 'User deleted')
    fetchUsers()
  } catch (e) {
    showAlert('error', e.response?.data?.message || 'Failed to delete user')
  }
}

const getAlertIcon = (type) => {
  const icons = { success: 'fas fa-check-circle', error: 'fas fa-exclamation-circle', warning: 'fas fa-exclamation-triangle', info: 'fas fa-info-circle' }
  return icons[type] || icons.info
}

// Supplier Methods
const fetchSuppliers = async () => {
  loadingSuppliers.value = true
  try {
    const res = await axios.get('/suppliers')
    suppliers.value = res.data.data || res.data || []
  } catch (e) {
    showAlert('error', 'Failed to fetch suppliers')
    suppliers.value = []
  } finally {
    loadingSuppliers.value = false
  }
}

const editSupplier = (supplier) => {
  showEditSupplier.value = true
  Object.assign(supplierForm, supplier)
}

const closeSupplierModal = () => {
  showAddSupplier.value = false
  showEditSupplier.value = false
  Object.assign(supplierForm, {
    id: null,
    name: '',
    contact_person: '',
    email: '',
    phone: '',
    address: '',
    products_supplied: '',
    notes: ''
  })
}

const saveSupplier = async () => {
  try {
    if (showEditSupplier.value) {
      await axios.put(`/suppliers/${supplierForm.id}`, supplierForm)
      showAlert('success', 'Supplier updated successfully')
    } else {
      await axios.post('/suppliers', supplierForm)
      showAlert('success', 'Supplier created successfully')
    }
    closeSupplierModal()
    fetchSuppliers()
  } catch (e) {
    showAlert('error', e.response?.data?.message || 'Failed to save supplier')
  }
}

const deleteSupplier = async (supplier) => {
  if (!confirm(`Delete supplier ${supplier.name}?`)) return
  try {
    await axios.delete(`/suppliers/${supplier.id}`)
    showAlert('success', 'Supplier deleted')
    fetchSuppliers()
  } catch (e) {
    showAlert('error', e.response?.data?.message || 'Failed to delete supplier')
  }
}

// Customer Methods
const fetchPriceGroups = async () => {
  try {
    const res = await axios.get('/price-groups')
    priceGroups.value = res.data || []
  } catch (e) {
    console.error('Failed to fetch price groups:', e)
    priceGroups.value = []
  }
}

const fetchCustomers = async () => {
  loadingCustomers.value = true
  try {
    const res = await axios.get('/customers')
    customers.value = res.data.data || res.data || []
  } catch (e) {
    showAlert('error', 'Failed to fetch customers')
    customers.value = []
  } finally {
    loadingCustomers.value = false
  }
}

const editCustomer = (customer) => {
  showEditCustomer.value = true
  Object.assign(customerForm, {
    ...customer,
    price_group_id: customer.price_group?.id || null
  })
}

const closeCustomerModal = () => {
  showAddCustomer.value = false
  showEditCustomer.value = false
  Object.assign(customerForm, {
    id: null,
    name: '',
    email: '',
    phone: '',
    address: '',
    tax_id: '',
    notes: '',
    price_group_id: null
  })
}

const saveCustomer = async () => {
  try {
    if (showEditCustomer.value) {
      await axios.put(`/customers/${customerForm.id}`, customerForm)
      showAlert('success', 'Customer updated successfully')
    } else {
      await axios.post('/customers', customerForm)
      showAlert('success', 'Customer created successfully')
    }
    closeCustomerModal()
    fetchCustomers()
  } catch (e) {
    showAlert('error', e.response?.data?.message || 'Failed to save customer')
  }
}

const deleteCustomer = async (customer) => {
  if (!confirm(`Delete customer ${customer.name}?`)) return
  try {
    await axios.delete(`/customers/${customer.id}`)
    showAlert('success', 'Customer deleted')
    fetchCustomers()
  } catch (e) {
    showAlert('error', e.response?.data?.message || 'Failed to delete customer')
  }
}

// Batch Import Methods
const showBatchModal = (type) => {
  batchType.value = type
  showBatchImport.value = true
}

const closeBatchModal = () => {
  showBatchImport.value = false
  batchFile.value = null
  batchData.value = []
}

const downloadTemplate = async (type) => {
  try {
    const workbook = new ExcelJS.Workbook()
    const worksheet = workbook.addWorksheet(type === 'supplier' ? 'Suppliers' : 'Customers')
    
    if (type === 'supplier') {
      worksheet.columns = [
        { header: 'name', key: 'name', width: 25 },
        { header: 'contact_person', key: 'contact_person', width: 20 },
        { header: 'email', key: 'email', width: 25 },
        { header: 'phone', key: 'phone', width: 15 },
        { header: 'address', key: 'address', width: 30 },
        { header: 'products_supplied', key: 'products_supplied', width: 25 },
        { header: 'notes', key: 'notes', width: 30 }
      ]
      
      worksheet.addRow({
        name: 'ABC Suppliers Ltd',
        contact_person: 'John Doe',
        email: 'john@abc.com',
        phone: '+254712345678',
        address: 'Nairobi, Kenya',
        products_supplied: 'Electronics, Furniture',
        notes: 'Preferred supplier'
      })
      worksheet.addRow({
        name: 'XYZ Trading Co',
        contact_person: 'Jane Smith',
        email: 'jane@xyz.com',
        phone: '+254723456789',
        address: 'Mombasa, Kenya',
        products_supplied: 'Office Supplies',
        notes: 'Fast delivery'
      })
    } else {
      worksheet.columns = [
        { header: 'name', key: 'name', width: 25 },
        { header: 'email', key: 'email', width: 25 },
        { header: 'phone', key: 'phone', width: 15 },
        { header: 'address', key: 'address', width: 30 },
        { header: 'tax_id', key: 'tax_id', width: 15 },
        { header: 'notes', key: 'notes', width: 30 }
      ]
      
      worksheet.addRow({
        name: 'Acme Corporation',
        email: 'contact@acme.com',
        phone: '+254712345678',
        address: 'Nairobi, Kenya',
        tax_id: 'A123456789Z',
        notes: 'VIP customer'
      })
      worksheet.addRow({
        name: 'Best Retail Ltd',
        email: 'info@bestretail.com',
        phone: '+254723456789',
        address: 'Kisumu, Kenya',
        tax_id: 'B987654321Y',
        notes: 'Monthly billing'
      })
    }
    
    worksheet.getRow(1).font = { bold: true, color: { argb: 'FFFFFFFF' } }
    worksheet.getRow(1).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF667eea' } }
    
    const buffer = await workbook.xlsx.writeBuffer()
    const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `${type}_import_template_${new Date().toISOString().split('T')[0]}.xlsx`
    link.click()
    window.URL.revokeObjectURL(url)
    
    showAlert('success', 'Template downloaded successfully!')
  } catch (error) {
    console.error('Error generating template:', error)
    showAlert('error', 'Failed to generate template')
  }
}

const handleFileDrop = (event) => {
  isDragOver.value = false
  const files = event.dataTransfer.files
  if (files.length > 0) {
    handleFile(files[0])
  }
}

const handleFileSelect = (event) => {
  const file = event.target.files[0]
  if (file) {
    handleFile(file)
  }
}

const handleFile = (file) => {
  const isCSV = file.type === 'text/csv' || file.name.endsWith('.csv')
  const isExcel = file.name.endsWith('.xlsx') || file.name.endsWith('.xls') || file.type.includes('spreadsheet')
  
  if (isCSV) {
    batchFile.value = file
    parseCSV(file)
  } else if (isExcel) {
    batchFile.value = file
    parseExcel(file)
  } else {
    showAlert('error', 'Please select a valid CSV or Excel file')
  }
}

const parseExcel = (file) => {
  const reader = new FileReader()
  reader.onload = async (e) => {
    try {
      const workbook = new ExcelJS.Workbook()
      await workbook.xlsx.load(e.target.result)
      const worksheet = workbook.worksheets[0]
      
      batchData.value = []
      let headers = []
      
      worksheet.eachRow((row, rowNumber) => {
        if (rowNumber === 1) {
          headers = row.values.slice(1)
          return
        }
        
        const item = {}
        row.values.slice(1).forEach((value, index) => {
          if (headers[index]) {
            item[headers[index]] = value || ''
          }
        })
        
        if (Object.keys(item).length > 0 && item.name) {
          batchData.value.push(item)
        }
      })
      
      showAlert('success', `Successfully loaded ${batchData.value.length} records from Excel file`)
    } catch (error) {
      console.error('Error parsing Excel file:', error)
      showAlert('error', 'Error parsing Excel file. Please ensure it has the correct format.')
    }
  }
  reader.readAsArrayBuffer(file)
}

const parseCSV = (file) => {
  const reader = new FileReader()
  reader.onload = (e) => {
    const csv = e.target.result
    const lines = csv.split('\n')
    const headers = lines[0].split(',').map(h => h.trim())
    
    batchData.value = []
    for (let i = 1; i < lines.length; i++) {
      if (lines[i].trim()) {
        const values = lines[i].split(',').map(v => v.trim())
        const item = {}
        headers.forEach((header, index) => {
          item[header] = values[index] || ''
        })
        if (item.name) {
          batchData.value.push(item)
        }
      }
    }
    
    if (batchData.value.length > 0) {
      showAlert('success', `Successfully parsed ${batchData.value.length} records from CSV`)
    }
  }
  reader.readAsText(file)
}

const importBatchData = async () => {
  if (batchData.value.length === 0) return
  
  importing.value = true
  try {
    const endpoint = batchType.value === 'supplier' ? '/suppliers/batch' : '/customers/batch'
    await axios.post(endpoint, { data: batchData.value })
    
    showAlert('success', `Successfully imported ${batchData.value.length} ${batchType.value}${batchData.value.length !== 1 ? 's' : ''}`)
    closeBatchModal()
    
    if (batchType.value === 'supplier') {
      fetchSuppliers()
    } else {
      fetchCustomers()
    }
  } catch (e) {
    showAlert('error', e.response?.data?.message || 'Failed to import data')
  } finally {
    importing.value = false
  }
}

onMounted(() => {
  fetchUsers()
  fetchRoles()
  fetchSuppliers()
  fetchCustomers()
  fetchPriceGroups()
})
</script>

<style scoped>
.admin-users-page { max-width: 1400px; margin: 2rem auto; padding: 1.5rem; font-family: 'Inter', sans-serif; }
.admin-header { display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 1.5rem; }
.admin-header h1 { margin: 0; font-size: 2rem; color: #1f2937; }
.muted { color: #6b7280; margin: 0.25rem 0 0 0; }

/* Tab Navigation */
.tab-navigation { 
  display: flex; 
  gap: 0.5rem; 
  margin-bottom: 2rem; 
  border-bottom: 2px solid #e5e7eb; 
  padding-bottom: 0;
}
.tab-btn { 
  background: transparent; 
  border: none; 
  padding: 1rem 1.5rem; 
  cursor: pointer; 
  font-weight: 600; 
  color: #6b7280; 
  transition: all 0.3s ease; 
  border-bottom: 3px solid transparent;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.tab-btn:hover { 
  color: #667eea; 
  background: rgba(102, 126, 234, 0.05);
}
.tab-btn.active { 
  color: #667eea; 
  border-bottom-color: #667eea;
}
.tab-content { animation: fadeIn 0.3s ease; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

.btn-create { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; border: none; padding: 0.75rem 1.5rem; border-radius: 10px; cursor: pointer; font-weight: 600; transition: all 0.3s ease; }
.btn-create:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(102,126,234,0.3); }
.btn-create i { margin-right: 0.5rem; }

.admin-controls { display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; align-items: center; }
.control-buttons { display: flex; gap: 0.75rem; margin-left: auto; }
.search-bar { flex: 1; min-width: 250px; max-width: 400px; display: flex; align-items: center; gap: 0.75rem; background: #fff; border: 2px solid #e5e7eb; border-radius: 10px; padding: 0.75rem 1rem; }
.search-bar i { color: #a0aec0; }
.search-bar input { flex: 1; border: none; outline: none; font-size: 1rem; }
.btn-refresh { background: #f3f4f6; color: #6b7280; border: none; padding: 0.75rem 1.5rem; border-radius: 10px; cursor: pointer; font-weight: 600; transition: all 0.3s ease; }
.btn-refresh:hover { background: #e5e7eb; }
.admin-table-container { background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: 0 6px 20px rgba(0,0,0,0.06); }
.loading, .error-message { text-align: center; padding: 2rem; }
.spinner { width: 40px; height: 40px; border: 3px solid #e5e7eb; border-left: 3px solid #667eea; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 1rem; }
@keyframes spin { to { transform: rotate(360deg); } }
.error-message { color: #991b1b; background: #fee2e2; border-radius: 8px; display: flex; align-items: center; gap: 0.75rem; }
.btn-retry { background: #dc2626; color: #fff; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; }
.users-table { width: 100%; border-collapse: collapse; }
.users-table th, .users-table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; }
.users-table th { background: #f9fafb; font-weight: 600; color: #374151; }
.users-table tr:hover { background: #f9fafb; }
.users-table td strong { color: #1f2937; }
.badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.875rem; font-weight: 500; }
.badge-superuser { background: #dbeafe; color: #1e40af; }
.badge-admin { background: #dcfce7; color: #166534; }
.badge-cashier { background: #fef3c7; color: #92400e; }
.status-badge { padding: 0.25rem 0.75rem; border-radius: 6px; font-size: 0.875rem; font-weight: 500; }
.status-badge.verified { background: #d1fae5; color: #065f46; }
.status-badge.unverified { background: #fee2e2; color: #991b1b; }
.stat-badge { background: #667eea; color: white; padding: 0.5rem 1rem; border-radius: 8px; font-weight: 600; }
.actions { display: flex; gap: 0.5rem; }
.btn-action { background: none; border: none; width: 32px; height: 32px; border-radius: 6px; cursor: pointer; transition: all 0.2s ease; display: flex; align-items: center; justify-content: center; }
.btn-edit { color: #667eea; background: rgba(102,126,234,0.1); }
.btn-edit:hover { background: rgba(102,126,234,0.2); }
.btn-delete { color: #dc2626; background: rgba(220,38,38,0.1); }
.btn-delete:hover { background: rgba(220,38,38,0.2); }
.empty { text-align: center; color: #6b7280; padding: 1rem; }
.modal-overlay { position: fixed; inset: 0; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.5); z-index: 2000; }
.modal { background: #fff; border-radius: 16px; width: 100%; max-width: 500px; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
.modal-large { max-width: 800px; }
.modal-header { padding: 1.25rem 1.5rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eef2f7; background: #fff; flex: 0 0 auto; }
.modal-header h2 { margin: 0; font-size: 1.5rem; color: #1f2937; }
.btn-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #6b7280; transition: color 0.2s; }
.btn-close:hover { color: #1f2937; }
.modal-body { padding: 1.25rem 1.5rem; overflow-y: auto; -webkit-overflow-scrolling: touch; flex: 1 1 auto; }
.modal-actions { padding: 0.75rem 1.5rem; border-top: 1px solid #eef2f7; display: flex; justify-content: flex-end; gap: 1rem; background: #fff; flex: 0 0 auto; }
.modal-form { display: flex; flex-direction: column; gap: 1rem; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.form-group { display: flex; flex-direction: column; gap: 0.5rem; }
.form-group label { font-weight: 600; color: #374151; }
.form-group input, .form-group select, .form-group textarea { padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 1rem; font-family: inherit; }
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.1); }
.btn-primary { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; border: none; padding: 0.75rem 1.5rem; border-radius: 10px; cursor: pointer; font-weight: 600; transition: all 0.3s ease; display: flex; align-items: center; gap: 0.5rem; }
.btn-primary:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(102,126,234,0.3); }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-secondary { background: #f3f4f6; color: #6b7280; border: none; padding: 0.75rem 1.5rem; border-radius: 10px; cursor: pointer; font-weight: 600; transition: all 0.3s ease; }
.btn-secondary:hover { background: #e5e7eb; }

/* Batch Import Styles */
.batch-import-container { display: flex; flex-direction: column; gap: 1.5rem; }
.import-instructions { background: #eff6ff; border: 2px dashed #3b82f6; border-radius: 12px; padding: 1.5rem; }
.import-instructions h3 { margin: 0 0 1rem 0; color: #1e40af; display: flex; align-items: center; gap: 0.5rem; }
.import-instructions ol { margin: 0.5rem 0 1rem 1.5rem; color: #1e40af; }
.btn-download { background: #3b82f6; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s ease; display: flex; align-items: center; gap: 0.5rem; }
.btn-download:hover { background: #2563eb; transform: translateY(-2px); }
.upload-zone { border: 3px dashed #d1d5db; border-radius: 12px; padding: 3rem 2rem; text-align: center; background: #f9fafb; transition: all 0.3s ease; position: relative; }
.upload-zone.dragover { border-color: #667eea; background: rgba(102,126,234,0.05); }
.upload-zone i { font-size: 3rem; color: #9ca3af; margin-bottom: 1rem; }
.upload-zone h4 { margin: 0 0 0.5rem 0; color: #4b5563; }
.upload-zone p { color: #6b7280; margin: 0 0 1rem 0; }
.file-input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
.browse-btn { background: #667eea; color: white; border: none; padding: 0.75rem 2rem; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s ease; }
.browse-btn:hover { background: #5568d3; transform: translateY(-2px); }
.file-preview { background: white; border: 2px solid #e5e7eb; border-radius: 12px; padding: 1rem; }
.file-info { display: flex; align-items: center; gap: 1rem; }
.file-info i { font-size: 2rem; color: #10b981; }
.file-info span { flex: 1; font-weight: 600; color: #1f2937; }
.remove-file-btn { background: rgba(220,38,38,0.1); color: #dc2626; border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; transition: all 0.2s; }
.remove-file-btn:hover { background: rgba(220,38,38,0.2); }
.preview-stats { margin-top: 1rem; display: flex; gap: 1rem; }
.preview-table-container { background: white; border: 2px solid #e5e7eb; border-radius: 12px; padding: 1rem; max-height: 300px; overflow: auto; }
.preview-table-container h4 { margin: 0 0 1rem 0; color: #1f2937; }
.preview-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
.preview-table th, .preview-table td { padding: 0.5rem; text-align: left; border-bottom: 1px solid #e5e7eb; white-space: nowrap; }
.preview-table th { background: #f9fafb; font-weight: 600; color: #374151; position: sticky; top: 0; }
.btn-loading { display: flex; align-items: center; gap: 0.5rem; }
.btn-spinner { width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.3); border-left: 2px solid white; border-radius: 50%; animation: spin 1s linear infinite; }

.alert-toast { position: fixed; bottom: 2rem; right: 2rem; display: flex; align-items: center; gap: 1rem; padding: 1rem 1.5rem; border-radius: 10px; background: #d1fae5; color: #065f46; box-shadow: 0 10px 30px rgba(0,0,0,0.1); z-index: 3000; animation: slideIn 0.3s ease; }
.alert-success { background: #d1fae5; color: #065f46; }
.alert-error { background: #fee2e2; color: #991b1b; }
.alert-warning { background: #fef3c7; color: #78350f; }
.alert-close { background: none; border: none; cursor: pointer; color: inherit; opacity: 0.7; transition: opacity 0.2s; }
.alert-close:hover { opacity: 1; }
@keyframes slideIn { from { transform: translateX(400px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }

.badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.875rem; font-weight: 600; background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
.text-muted { color: #9ca3af; }

@media (max-width: 768px) {
  .admin-header { flex-direction: column; align-items: flex-start; }
  .admin-controls { flex-direction: column; }
  .search-bar { max-width: 100%; }
  .control-buttons { width: 100%; justify-content: space-between; margin-left: 0; }
  .form-row { grid-template-columns: 1fr; }
  .tab-navigation { overflow-x: auto; }
  .modal-large { max-width: 95%; }
}
</style>
