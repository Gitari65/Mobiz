<!-- UserManagementPage.vue -->
<template>
  <div class="user-management-page">
    <div class="page-header">
      <h1 class="page-title">User Management</h1>
      <p class="page-description">Manage system users, roles, and permissions</p>
    </div>
    
    <div class="content-wrapper">
      <div class="actions-bar">
        <button class="btn btn-primary" @click="showAddUserModal = true">
          <i class="fas fa-plus"></i>
          Add New User
        </button>
        <button class="btn btn-secondary" @click="exportUsers">
          <i class="fas fa-download"></i>
          Export Users
        </button>
      </div>
      
      <div class="users-table-wrapper">
        <table class="users-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Role</th>
              <th>Status</th>
              <th>Last Login</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in users" :key="user.id">
              <td>{{ user.name }}</td>
              <td>{{ user.email }}</td>
              <td>
                <span class="role-badge" :class="user.role.toLowerCase()">
                  {{ user.role }}
                </span>
              </td>
              <td>
                <span class="status-badge" :class="user.status.toLowerCase()">
                  {{ user.status }}
                </span>
              </td>
              <td>{{ formatDate(user.lastLogin) }}</td>
              <td>
                <div class="action-buttons">
                  <button class="btn btn-sm btn-outline" @click="editUser(user)">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="btn btn-sm btn-danger" @click="deleteUser(user)">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const showAddUserModal = ref(false)
const users = ref([
  {
    id: 1,
    name: 'John Admin',
    email: 'admin@example.com',
    role: 'Admin',
    status: 'Active',
    lastLogin: new Date('2024-01-15T10:30:00')
  },
  {
    id: 2,
    name: 'Jane Cashier',
    email: 'cashier@example.com',
    role: 'Cashier',
    status: 'Active',
    lastLogin: new Date('2024-01-14T14:20:00')
  }
])

const formatDate = (date) => {
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(date)
}

const editUser = (user) => {
  console.log('Edit user:', user)
}

const deleteUser = (user) => {
  console.log('Delete user:', user)
}

const exportUsers = () => {
  console.log('Export users')
}

onMounted(() => {
  console.log('User Management page mounted')
})
</script>