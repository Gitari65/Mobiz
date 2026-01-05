<template>
  <div class="imp-page">
    <!-- Impersonation Banner (if active) -->
    <div v-if="impersonating" class="impersonation-banner">
      <div class="banner-content">
        <i class="fas fa-user-secret"></i>
        <div class="banner-text">
          <h3>Impersonating {{ impersonationData.target_user?.name }}</h3>
          <p>{{ impersonationData.target_user?.email }} • Expires in {{ remainingTime }}</p>
        </div>
        <button @click="revertImpersonation" class="btn-revert">
          <i class="fas fa-sign-out-alt"></i> Revert
        </button>
      </div>
    </div>

    <!-- Main Content -->
    <section v-if="!impersonating" class="imp-section">
      <header class="imp-header">
        <div class="imp-title">
          <i class="fas fa-user-secret"></i>
          <div>
            <h1>Impersonate Business Admin</h1>
            <p class="muted">Log in as a business admin to troubleshoot issues</p>
          </div>
        </div>
      </header>

      <!-- Business Selection -->
      <div class="selection-card">
        <h2>Select Business & Admin</h2>

        <div v-if="loading" class="loading">
          <div class="spinner"></div>
          <p>Loading businesses...</p>
        </div>

        <div v-else-if="error" class="error-message">
          <i class="fas fa-exclamation-circle"></i>
          <span>{{ error }}</span>
          <button @click="loadBusinesses" class="btn-retry">Retry</button>
        </div>

        <div v-else class="business-grid">
          <div
            v-for="business in businesses"
            :key="business.id"
            class="business-card"
            :class="{ selected: selectedBusiness?.id === business.id }"
            @click="selectBusiness(business)"
          >
            <div class="business-header">
              <h3>{{ business.name }}</h3>
              <i v-if="selectedBusiness?.id === business.id" class="fas fa-check-circle check"></i>
            </div>
            <p class="business-email">{{ business.email }}</p>

            <!-- Users/Admins in this business -->
            <div v-if="business.users && business.users.length > 0" class="users-list">
              <h4>Admins:</h4>
              <div class="user-selector">
                <button
                  v-for="user in business.users"
                  :key="user.id"
                  @click.stop="selectUser(user)"
                  :class="{ active: selectedUser?.id === user.id }"
                  class="user-btn"
                  :title="user.email"
                >
                  {{ user.name }}
                </button>
              </div>
            </div>
            <div v-else class="no-users">
              <i class="fas fa-user-slash"></i> No verified admins
            </div>
          </div>

          <div v-if="businesses.length === 0" class="empty">
            <i class="fas fa-inbox"></i>
            <p>No active businesses</p>
          </div>
        </div>

        <!-- Action Button -->
        <div v-if="selectedBusiness && selectedUser" class="action-section">
          <div class="selected-info">
            <p><strong>Business:</strong> {{ selectedBusiness.name }}</p>
            <p><strong>User:</strong> {{ selectedUser.name }} ({{ selectedUser.email }})</p>
          </div>
          <button
            @click="startImpersonation"
            :disabled="impersonating || startingImpersonation"
            class="btn-impersonate"
          >
            <i v-if="startingImpersonation" class="fas fa-spinner fa-spin"></i>
            <i v-else class="fas fa-user-secret"></i>
            {{ startingImpersonation ? 'Starting...' : 'Start Impersonation' }}
          </button>
        </div>

        <div v-else class="hint">
          <i class="fas fa-info-circle"></i>
          <p>Select a business and admin user to begin impersonation</p>
        </div>
      </div>

      <!-- Security Info -->
      <div class="security-info">
        <h3>Security & Limits</h3>
        <ul>
          <li><i class="fas fa-check"></i> Impersonation sessions expire after 30 minutes</li>
          <li><i class="fas fa-check"></i> Cannot impersonate superusers for security</li>
          <li><i class="fas fa-check"></i> Cannot impersonate unverified users</li>
          <li><i class="fas fa-check"></i> All actions are logged in audit logs</li>
          <li><i class="fas fa-check"></i> Your IP address is recorded</li>
        </ul>
      </div>
    </section>

    <!-- Alert Toast -->
    <div v-if="alert.show" class="alert-toast" :class="`alert-${alert.type}`">
      <i :class="getAlertIcon(alert.type)"></i>
      <span>{{ alert.message }}</span>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import axios from 'axios'

// State
const loading = ref(false)
const startingImpersonation = ref(false)
const error = ref(null)
const businesses = ref([])
const selectedBusiness = ref(null)
const selectedUser = ref(null)
const impersonating = ref(false)
const impersonationData = ref(null)
const impersonationToken = ref(null)
const remainingTime = ref('')

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

async function loadBusinesses() {
  loading.value = true
  error.value = null
  try {
    const res = await axios.get('/api/super/impersonate/businesses')
    businesses.value = res.data.businesses || []
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to load businesses'
    showAlert('error', error.value)
  } finally {
    loading.value = false
  }
}

function selectBusiness(business) {
  selectedBusiness.value = business
  selectedUser.value = null // Reset user selection
}

function selectUser(user) {
  selectedUser.value = user
}

async function startImpersonation() {
  if (!selectedUser.value) {
    showAlert('warning', 'Please select a user')
    return
  }

  startingImpersonation.value = true
  try {
    const res = await axios.post(`/api/super/impersonate/${selectedUser.value.id}`)

    impersonationToken.value = res.data.impersonate_token
    impersonationData.value = res.data

    // Store impersonation in localStorage for persistence
    localStorage.setItem('impersonation_token', impersonationToken.value)
    localStorage.setItem('impersonation_data', JSON.stringify(res.data))

    // Update main auth token if needed (optional, for API calls)
    if (res.data.api_token) {
      localStorage.setItem('authToken', res.data.api_token)
    }

    impersonating.value = true
    showAlert('success', `Now impersonating ${selectedUser.value.name}`)

    // Start timer to update remaining time
    startRemainingTimeTimer()
  } catch (e) {
    showAlert('error', e.response?.data?.error || 'Failed to start impersonation')
  } finally {
    startingImpersonation.value = false
  }
}

async function revertImpersonation() {
  try {
    await axios.post('/api/super/impersonate/revert', {
      impersonation_token: impersonationToken.value
    })

    // Clear impersonation from localStorage
    localStorage.removeItem('impersonation_token')
    localStorage.removeItem('impersonation_data')

    // Restore original token (if you saved it)
    // localStorage.setItem('authToken', originalToken)

    impersonating.value = false
    impersonationData.value = null
    impersonationToken.value = null
    selectedBusiness.value = null
    selectedUser.value = null

    showAlert('success', 'Impersonation ended. Returned to superuser context.')
  } catch (e) {
    showAlert('error', 'Failed to revert impersonation')
  }
}

function startRemainingTimeTimer() {
  const updateTimer = () => {
    if (!impersonationData.value?.expires_at) return

    const expiresAt = new Date(impersonationData.value.expires_at)
    const now = new Date()
    const minutes = Math.ceil((expiresAt - now) / 60000)

    if (minutes <= 0) {
      remainingTime.value = 'Expired'
      impersonating.value = false
    } else if (minutes === 1) {
      remainingTime.value = '1 minute'
    } else {
      remainingTime.value = `${minutes} minutes`
    }
  }

  updateTimer()
  setInterval(updateTimer, 10000) // Update every 10 seconds
}

function checkExistingImpersonation() {
  const token = localStorage.getItem('impersonation_token')
  const data = localStorage.getItem('impersonation_data')

  if (token && data) {
    impersonationToken.value = token
    impersonationData.value = JSON.parse(data)
    impersonating.value = true
    startRemainingTimeTimer()
  }
}

onMounted(() => {
  checkExistingImpersonation()
  loadBusinesses()
})
</script>

<style scoped>
.imp-page { max-width: 1200px; margin: 2rem auto; padding: 1.5rem; font-family: 'Inter', sans-serif; }

.impersonation-banner {
  position: sticky;
  top: 0;
  background: linear-gradient(135deg, #f59e0b, #d97706);
  color: white;
  padding: 1.25rem 1.5rem;
  border-radius: 12px;
  margin-bottom: 2rem;
  box-shadow: 0 10px 30px rgba(245, 158, 11, 0.3);
  z-index: 100;
  animation: bannerSlideDown 0.3s ease;
}

@keyframes bannerSlideDown {
  from { transform: translateY(-20px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

.banner-content {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  justify-content: space-between;
}

.banner-content i { font-size: 1.75rem; }
.banner-text h3 { margin: 0; font-size: 1.1rem; }
.banner-text p { margin: 0.25rem 0 0 0; opacity: 0.95; font-size: 0.9rem; }

.btn-revert { background: rgba(255,255,255,0.2); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s ease; }
.btn-revert:hover { background: rgba(255,255,255,0.3); transform: translateY(-2px); }

.imp-section { background: #fff; border-radius: 12px; padding: 2rem; box-shadow: 0 6px 20px rgba(0,0,0,0.06); }

.imp-header { display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem; }
.imp-title { display: flex; align-items: center; gap: 1rem; }
.imp-title i { font-size: 2rem; color: #667eea; background: rgba(102,126,234,0.1); padding: 0.75rem; border-radius: 12px; }
.imp-title h1 { margin: 0; font-size: 1.5rem; }
.muted { color: #6b7280; margin: 0; }

.selection-card { background: #f9fafb; border-radius: 12px; padding: 2rem; margin-bottom: 2rem; }
.selection-card h2 { margin-top: 0; font-size: 1.25rem; }

.loading, .error-message { text-align: center; padding: 2rem; }
.spinner { width: 40px; height: 40px; border: 3px solid #e5e7eb; border-left: 3px solid #667eea; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 1rem; }
@keyframes spin { to { transform: rotate(360deg); } }

.error-message { color: #991b1b; background: #fee2e2; border-radius: 8px; display: flex; align-items: center; justify-content: center; gap: 1rem; }
.btn-retry { background: #dc2626; color: #fff; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; }

.business-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
.business-card { border: 2px solid #e5e7eb; border-radius: 12px; padding: 1.5rem; cursor: pointer; transition: all 0.3s ease; }
.business-card:hover { border-color: #667eea; box-shadow: 0 4px 12px rgba(102,126,234,0.15); }
.business-card.selected { border-color: #667eea; background: rgba(102,126,234,0.05); }

.business-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.75rem; }
.business-header h3 { margin: 0; font-size: 1.1rem; }
.check { font-size: 1.5rem; color: #48bb78; }

.business-email { color: #6b7280; margin: 0.5rem 0 1rem 0; font-size: 0.9rem; }

.users-list h4 { margin: 1rem 0 0.5rem 0; color: #374151; font-size: 0.9rem; }
.user-selector { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.user-btn { background: #e5e7eb; border: 1px solid #d1d5db; color: #374151; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; font-size: 0.85rem; transition: all 0.2s ease; }
.user-btn:hover { background: #d1d5db; }
.user-btn.active { background: #667eea; color: #fff; border-color: #667eea; }

.no-users { color: #9ca3af; font-size: 0.9rem; padding: 1rem; text-align: center; }
.empty { text-align: center; padding: 3rem 1rem; color: #a0aec0; }

.action-section { background: white; border-radius: 12px; padding: 1.5rem; margin-top: 1.5rem; }
.selected-info { background: #f0f9ff; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
.selected-info p { margin: 0.5rem 0; color: #1e40af; font-size: 0.95rem; }

.btn-impersonate { width: 100%; background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; border: none; padding: 1rem; border-radius: 8px; font-weight: 600; font-size: 1rem; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 0.75rem; }
.btn-impersonate:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(102,126,234,0.3); }
.btn-impersonate:disabled { opacity: 0.5; cursor: not-allowed; }

.hint { background: #ecfdf5; border-left: 4px solid #48bb78; padding: 1.5rem; border-radius: 8px; color: #065f46; text-align: center; }
.hint i { font-size: 1.5rem; margin-bottom: 0.5rem; }
.hint p { margin: 0; }

.security-info { background: #f0f9ff; border-radius: 12px; padding: 1.5rem; margin-top: 2rem; }
.security-info h3 { margin-top: 0; color: #1e40af; }
.security-info ul { list-style: none; padding: 0; margin: 0; }
.security-info li { padding: 0.75rem 0; color: #1e40af; display: flex; align-items: center; gap: 0.75rem; }
.security-info i { color: #48bb78; }

.alert-toast { position: fixed; bottom: 2rem; right: 2rem; display: flex; align-items: center; gap: 1rem; padding: 1rem 1.5rem; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); z-index: 3000; animation: slideIn 0.3s ease; }
.alert-success { background: #d1fae5; color: #065f46; }
.alert-error { background: #fee2e2; color: #991b1b; }
.alert-warning { background: #fef3c7; color: #78350f; }
@keyframes slideIn { from { transform: translateX(400px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
</style>
