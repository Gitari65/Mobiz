<!-- SystemLogsPage.vue -->
<template>
  <div class="system-logs-page">
    <div class="page-header">
      <h1 class="page-title">System Logs</h1>
      <p class="page-description">Monitor system activities and audit trails</p>
    </div>
    
    <div class="content-wrapper">
      <div class="filters-bar">
        <select v-model="selectedLogType" class="filter-select">
          <option value="">All Log Types</option>
          <option value="auth">Authentication</option>
          <option value="sales">Sales</option>
          <option value="inventory">Inventory</option>
          <option value="error">Errors</option>
        </select>
        
        <input 
          type="date" 
          v-model="selectedDate" 
          class="filter-date"
        />
        
        <button class="btn btn-primary" @click="refreshLogs">
          <i class="fas fa-refresh"></i>
          Refresh
        </button>
      </div>
      
      <div class="logs-container">
        <div 
          v-for="log in filteredLogs" 
          :key="log.id"
          class="log-entry"
          :class="log.level.toLowerCase()"
        >
          <div class="log-timestamp">
            {{ formatTimestamp(log.timestamp) }}
          </div>
          <div class="log-type">
            <span class="type-badge" :class="log.type">
              {{ log.type.toUpperCase() }}
            </span>
          </div>
          <div class="log-message">
            {{ log.message }}
          </div>
          <div class="log-user" v-if="log.user">
            by {{ log.user }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

const selectedLogType = ref('')
const selectedDate = ref('')

const logs = ref([
  {
    id: 1,
    timestamp: new Date('2024-01-15T10:30:00'),
    type: 'auth',
    level: 'info',
    message: 'User logged in successfully',
    user: 'admin@example.com'
  },
  {
    id: 2,
    timestamp: new Date('2024-01-15T10:25:00'),
    type: 'sales',
    level: 'info',
    message: 'New sale recorded: $125.50',
    user: 'cashier@example.com'
  },
  {
    id: 3,
    timestamp: new Date('2024-01-15T09:15:00'),
    type: 'error',
    level: 'error',
    message: 'Failed to connect to inventory database',
    user: 'system'
  }
])

const filteredLogs = computed(() => {
  let filtered = logs.value
  
  if (selectedLogType.value) {
    filtered = filtered.filter(log => log.type === selectedLogType.value)
  }
  
  if (selectedDate.value) {
    const selected = new Date(selectedDate.value)
    filtered = filtered.filter(log => 
      log.timestamp.toDateString() === selected.toDateString()
    )
  }
  
  return filtered.sort((a, b) => b.timestamp - a.timestamp)
})

const formatTimestamp = (timestamp) => {
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  }).format(timestamp)
}

const refreshLogs = () => {
  console.log('Refreshing logs...')
}

onMounted(() => {
  console.log('System Logs page mounted')
})
</script>