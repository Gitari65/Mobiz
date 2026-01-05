<template>
  <div class="admin-users-page">
    <header class="admin-header">
      <div>
        <h1>Manage Business Users</h1>
        <p class="muted">Add, edit, or remove users for your business.</p>
      </div>
      <button class="btn-create" @click="showCreateModal = true">
        <i class="fas fa-user-plus"></i> Add User
      </button>
    </header>

    <section class="admin-controls">
      <div class="search-bar">
        <i class="fas fa-search"></i>
        <input v-model="search" type="text" placeholder="Search by name or email..." />
      </div>
      <button class="btn-refresh" @click="fetchUsers">
        <i class="fas fa-sync-alt"></i> Refresh
      </button>
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
                <option value="">Select Role</option>
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

const loading = ref(false)
const error = ref(null)
const users = ref([])
const roles = ref([])
const search = ref('')
const showCreateModal = ref(false)
const showEditModal = ref(false)
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
    users.value = res.data.data || res.data.users || []
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
    roles.value = res.data.roles || res.data.data || []
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

onMounted(() => {
  fetchUsers()
  fetchRoles()
})
</script>

<style scoped>
.admin-users-page { max-width: 1200px; margin: 2rem auto; padding: 1.5rem; font-family: 'Inter', sans-serif; }
.admin-header { display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 2rem; }
.muted { color: #6b7280; margin: 0; }
.btn-create { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; border: none; padding: 0.75rem 1.5rem; border-radius: 10px; cursor: pointer; font-weight: 600; transition: all 0.3s ease; }
.btn-create:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(102,126,234,0.3); }
.admin-controls { display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
.search-bar { flex: 1; min-width: 250px; display: flex; align-items: center; gap: 0.75rem; background: #fff; border: 2px solid #e5e7eb; border-radius: 10px; padding: 0.75rem 1rem; }
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
.btn-delete { color: #dc2626; background: rgba(220,38,38,0.1); }
.btn-delete:hover { background: rgba(220,38,38,0.2); }
.empty { text-align: center; color: #6b7280; padding: 1rem; }
.modal-overlay { position: fixed; inset: 0; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.5); z-index: 2000; }
.modal { background: #fff; border-radius: 16px; width: 100%; max-width: 500px; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.3); padding: 0; }
.modal-header { padding: 1.25rem 1.5rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eef2f7; background: #fff; flex: 0 0 auto; }
.modal-body { padding: 1.25rem 1.5rem; overflow-y: auto; -webkit-overflow-scrolling: touch; flex: 1 1 auto; }
.modal-actions { padding: 0.75rem 1.5rem; border-top: 1px solid #eef2f7; display: flex; justify-content: flex-end; gap: 1rem; background: #fff; flex: 0 0 auto; }
.modal-form { display: flex; flex-direction: column; gap: 1rem; }
.form-group { display: flex; flex-direction: column; gap: 0.5rem; }
.form-group label { font-weight: 600; color: #374151; }
.form-group input, .form-group select { padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 1rem; }
.alert-toast { position: fixed; bottom: 2rem; right: 2rem; display: flex; align-items: center; gap: 1rem; padding: 1rem 1.5rem; border-radius: 10px; background: #d1fae5; color: #065f46; box-shadow: 0 10px 30px rgba(0,0,0,0.1); z-index: 3000; animation: slideIn 0.3s ease; }
.alert-success { background: #d1fae5; color: #065f46; }
.alert-error { background: #fee2e2; color: #991b1b; }
.alert-warning { background: #fef3c7; color: #78350f; }
@keyframes slideIn { from { transform: translateX(400px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
</style>
