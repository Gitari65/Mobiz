<template>
  <div class="sidebar">
    <!-- Alert System -->
    <div v-if="alert.show" class="sidebar-alert" :class="alert.type">
      <div class="alert-content">
        <i :class="getAlertIcon(alert.type)"></i>
        <span>{{ alert.message }}</span>
      </div>
      <button @click="dismissAlert" class="alert-close">
        <i class="fas fa-times"></i>
      </button>
    </div>

    <!-- Brand Section -->
    <div class="brand-section">
      <div class="brand-logo">
        <i class="fas fa-shopping-cart"></i>
      </div>
      <h2 class="logo">Mobiz</h2>
      <p class="brand-subtitle">POS System</p>
    </div>

    <!-- Navigation Menu -->
    <nav class="navigation">
      <ul class="nav">
        <!-- ============= CASHIER MENUS ============= -->
        <template v-if="isCashier">
          <li class="nav-item">
            <router-link to="/" class="nav-link" @click="setActiveItem('dashboard')">
              <i class="fas fa-tachometer-alt nav-icon"></i>
              <span class="nav-text">Dashboard</span>
            </router-link>
          </li>
          <li class="nav-item">
            <router-link to="/sales" class="nav-link" @click="setActiveItem('sales')">
              <i class="fas fa-cash-register nav-icon"></i>
              <span class="nav-text">POS Sales</span>
            </router-link>
          </li>
          <li class="nav-item">
            <router-link to="/profile" class="nav-link" @click="setActiveItem('profile')">
              <i class="fas fa-user-circle nav-icon"></i>
              <span class="nav-text">My Profile</span>
            </router-link>
          </li>
          <li class="nav-item">
            <router-link to="/settings" class="nav-link" @click="setActiveItem('settings')">
              <i class="fas fa-cog nav-icon"></i>
              <span class="nav-text">Settings</span>
            </router-link>
          </li>
        </template>

        <!-- ============= ADMIN MENUS ============= -->
        <template v-else-if="isAdmin">
          <li class="nav-item">
            <router-link to="/" class="nav-link" @click="setActiveItem('dashboard')">
              <i class="fas fa-tachometer-alt nav-icon"></i>
              <span class="nav-text">Dashboard</span>
            </router-link>
          </li>
          <li class="nav-item">
            <router-link to="/sales" class="nav-link" @click="setActiveItem('sales')">
              <i class="fas fa-cash-register nav-icon"></i>
              <span class="nav-text">POS Sales</span>
            </router-link>
          </li>
          <li class="nav-item">
            <router-link to="/products" class="nav-link" @click="setActiveItem('products')">
              <i class="fas fa-box nav-icon"></i>
              <span class="nav-text">Products</span>
            </router-link>
          </li>
          <li class="nav-item">
            <router-link to="/inventory" class="nav-link" @click="setActiveItem('inventory')">
              <i class="fas fa-warehouse nav-icon"></i>
              <span class="nav-text">Inventory</span>
            </router-link>
          </li>
          <li class="nav-item">
            <router-link to="/reports" class="nav-link" @click="setActiveItem('reports')">
              <i class="fas fa-chart-bar nav-icon"></i>
              <span class="nav-text">Reports</span>
            </router-link>
          </li>
          <li class="nav-item">
            <router-link to="/expenses" class="nav-link" @click="setActiveItem('expenses')">
              <i class="fas fa-receipt nav-icon"></i>
              <span class="nav-text">Expenses</span>
            </router-link>
          </li>
          <li class="nav-item">
            <router-link to="/promotions" class="nav-link" @click="setActiveItem('promotions')">
              <i class="fas fa-tags nav-icon"></i>
              <span class="nav-text">Promotions</span>
            </router-link>
          </li>
          <li class="nav-item">
            <router-link to="/accounts" class="nav-link" @click="setActiveItem('accounts')">
              <i class="fas fa-money-bill-wave nav-icon"></i>
              <span class="nav-text">Accounts</span>
            </router-link>
          </li>
          <li class="nav-item">
            <router-link to="/admin-customization" class="nav-link" @click="setActiveItem('admin-customization')">
              <i class="fas fa-cogs nav-icon"></i>
              <span class="nav-text">Admin Customization</span>
            </router-link>
          </li>
          <li class="nav-item">
            <router-link to="/users" class="nav-link" @click="setActiveItem('users')">
              <i class="fas fa-users nav-icon"></i>
              <span class="nav-text">Manage Users</span>
            </router-link>
          </li>
          <li class="nav-item">
            <router-link to="/company-profile" class="nav-link" @click="setActiveItem('company-profile')">
              <i class="fas fa-building nav-icon"></i>
              <span class="nav-text">Company Profile</span>
            </router-link>
          </li>
          <li class="nav-item">
            <router-link to="/profile" class="nav-link" @click="setActiveItem('profile')">
              <i class="fas fa-user-circle nav-icon"></i>
              <span class="nav-text">My Profile</span>
            </router-link>
          </li>
          <li class="nav-item">
            <router-link to="/settings" class="nav-link" @click="setActiveItem('settings')">
              <i class="fas fa-cog nav-icon"></i>
              <span class="nav-text">Settings</span>
            </router-link>
          </li>
        </template>

        <!-- ============= SUPER USER MENUS ============= -->
        <template v-else-if="isSuperUser">
          <li class="nav-item nav-section-title">
            <span>Super User</span>
          </li>

          <li class="nav-item">
            <router-link to="/superuser" class="nav-link" @click="setActiveItem('superuser-dashboard')">
              <i class="fas fa-user-shield nav-icon"></i>
              <span class="nav-text">Dashboard</span>
            </router-link>
          </li>

          <li class="nav-item">
            <router-link to="/superuser/user-management" class="nav-link" @click="setActiveItem('superuser-users')">
              <i class="fas fa-users nav-icon"></i>
              <span class="nav-text">User Management</span>
            </router-link>
          </li>

          <li class="nav-item">
            <router-link to="/superuser/audit-logs" class="nav-link" @click="setActiveItem('superuser-audit')">
              <i class="fas fa-clipboard-list nav-icon"></i>
              <span class="nav-text">Audit Logs</span>
            </router-link>
          </li>

          <li class="nav-item">
            <router-link to="/superuser/global-settings" class="nav-link" @click="setActiveItem('superuser-settings')">
              <i class="fas fa-globe nav-icon"></i>
              <span class="nav-text">Global Settings</span>
            </router-link>
          </li>

          <li class="nav-item">
            <router-link to="/superuser/data-export" class="nav-link" @click="setActiveItem('superuser-export')">
              <i class="fas fa-file-export nav-icon"></i>
              <span class="nav-text">Data Export</span>
            </router-link>
          </li>

          <li class="nav-item">
            <router-link to="/superuser/support" class="nav-link" @click="setActiveItem('superuser-support')">
              <i class="fas fa-headset nav-icon"></i>
              <span class="nav-text">Support & Communication</span>
            </router-link>
          </li>

          <li class="nav-item">
            <router-link to="/superuser/subscriptions" class="nav-link" @click="setActiveItem('superuser-subscriptions')">
              <i class="fas fa-credit-card nav-icon"></i>
              <span class="nav-text">Subscription & Billing</span>
            </router-link>
          </li>

          <li class="nav-item">
            <router-link to="/superuser/impersonate" class="nav-link" @click="setActiveItem('superuser-impersonate')">
              <i class="fas fa-user-secret nav-icon"></i>
              <span class="nav-text">Impersonate Business Admin</span>
            </router-link>
          </li>

          <li class="nav-item">
            <router-link to="/superuser/profile" class="nav-link" @click="setActiveItem('superuser-profile')">
              <i class="fas fa-user-circle nav-icon"></i>
              <span class="nav-text">My Profile</span>
            </router-link>
          </li>

          <li class="nav-item">
            <router-link to="/superuser/settings" class="nav-link" @click="setActiveItem('superuser-settings')">
              <i class="fas fa-cog nav-icon"></i>
              <span class="nav-text">Settings</span>
            </router-link>
          </li>
        </template>
      </ul>
    </nav>

    <!-- Chat Modal -->
    <ChatListModal
      :isOpen="showChatModal"
      @close="closeChatModal"
      @message-sent="onChatMessageSent"
    />

    <!-- Loading Spinner -->
    <div v-if="isLoading" class="sidebar-spinner">
      <div class="spinner"></div>
      <p class="loading-text">Loading...</p>
    </div>

    <!-- Footer -->
    <div class="sidebar-footer">
      <div class="user-section">
        <div class="user-avatar"><i class="fas fa-user"></i></div>
        <div class="user-info">
          <p class="user-name">{{ userName }}</p>
          <p class="user-role">{{ userRoleLabel }}</p>
        </div>
      </div>

      <!-- Chat & Logout Buttons Row -->
      <div class="footer-actions">
        <button class="chat-btn" @click="openChatModal" :title="`${unreadCount} unread messages`">
          <i class="fas fa-comments"></i>
          <span v-if="unreadCount > 0" class="unread-badge">{{ unreadCount > 99 ? '99+' : unreadCount }}</span>
        </button>
        <button class="logout-btn" @click="handleLogout">
          <i class="fas fa-sign-out-alt"></i><span>Logout</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import ChatListModal from './ChatListModal.vue'

const router = useRouter()

// --- STATE ---
const isLoading = ref(false)
const isAdmin = ref(false)
const isSuperUser = ref(false)
const isCashier = ref(false)
const userName = ref('')
const userRole = ref('')
const activeItem = ref('dashboard')
const unreadCount = ref(0)
const showChatModal = ref(false)
const chatRefreshInterval = ref(null)

// --- ALERT SYSTEM ---
const alert = reactive({
  show: false,
  type: 'info',
  message: '',
  timeout: null
})

// --- COMPUTED ---
const userRoleLabel = computed(() => {
  if (userRole.value === 'superuser') return 'Super User'
  if (userRole.value === 'admin') return 'Administrator'
  if (userRole.value === 'user') return 'User'
  return userRole.value ? userRole.value.charAt(0).toUpperCase() + userRole.value.slice(1) : ''
})

// --- METHODS ---
const showAlert = (type, message, duration = 3000) => {
  alert.show = true
  alert.type = type
  alert.message = message
  if (alert.timeout) clearTimeout(alert.timeout)
  alert.timeout = setTimeout(() => (alert.show = false), duration)
}
const dismissAlert = () => (alert.show = false)

const getAlertIcon = (type) => {
  const icons = {
    success: 'fas fa-check-circle',
    error: 'fas fa-exclamation-circle',
    warning: 'fas fa-exclamation-triangle',
    info: 'fas fa-info-circle'
  }
  return icons[type] || icons.info
}

const setActiveItem = (item) => (activeItem.value = item)

const setLoading = (loading) => (isLoading.value = loading)

const initializeUserRole = () => {
  try {
    const storedUser = JSON.parse(localStorage.getItem('userData'))
    if (!storedUser || !storedUser.role?.name) throw new Error('Invalid user data')

    const roleName = storedUser.role.name.toLowerCase()
    userRole.value = roleName
    userName.value = storedUser.name || ''

    isAdmin.value = ['admin', 'administrator'].includes(roleName)
    isSuperUser.value = ['superuser', 'super_user', 'super user'].includes(roleName)
    isCashier.value = roleName === 'cashier'
  } catch (err) {
    console.warn('User role init failed', err)
    isAdmin.value = false
    isSuperUser.value = false
    isCashier.value = true
  }
}

const handleLogout = async () => {
  try {
    setLoading(true)
    showAlert('info', 'Logging out...', 1000)
    await new Promise(resolve => setTimeout(resolve, 1000))
    localStorage.clear()
    showAlert('success', 'Logged out successfully!', 1500)
    setTimeout(() => router.push('/login'), 1500)
  } finally {
    setLoading(false)
  }
}

const loadUnreadCount = async () => {
  try {
    // don't call protected endpoint if no token present
    const token = localStorage.getItem('authToken')
    if (!token) {
      console.warn('Skipping unread count load: no auth token')
      unreadCount.value = 0
      return
    }

    try {
      // axios interceptor should already set Authorization header from token
      const res = await axios.get('/api/super/chats/unread-count')
      unreadCount.value = res.data.unread_count || 0
    } catch (err) {
      // Silently fail if endpoint doesn't exist yet
      console.debug('Chat unread count endpoint not available')
      unreadCount.value = 0
    }
  } catch (e) {
    console.warn('Failed to load unread count', e)
  }
}

const openChatModal = () => {
  showChatModal.value = true
  loadUnreadCount()
}

const closeChatModal = () => {
  showChatModal.value = false
}

const onChatMessageSent = () => {
  loadUnreadCount()
}

// --- LIFECYCLE ---
onMounted(() => {
  initializeUserRole()
  // Load unread count for all authenticated users
  loadUnreadCount()
  // Poll unread count every 10 seconds
  chatRefreshInterval.value = setInterval(loadUnreadCount, 10000)
  
  const path = router.currentRoute.value.path
  if (path.includes('/sales')) activeItem.value = 'sales'
  else if (path.includes('/products')) activeItem.value = 'products'
  else if (path.includes('/inventory')) activeItem.value = 'inventory'
  else if (path.includes('/reports')) activeItem.value = 'reports'
  else activeItem.value = 'dashboard'
})

onUnmounted(() => {
  if (chatRefreshInterval.value) {
    clearInterval(chatRefreshInterval.value)
  }
})
</script>

<style scoped>
/* Add your existing styles here */
.sidebar {
  /* Your sidebar styles */
}

.sidebar-alert {
  /* Alert styles */
}

.brand-section {
  /* Brand styles */
}

.navigation {
  /* Navigation styles */
}

.nav-item {
  /* Nav item styles */
}

.nav-link {
  /* Nav link styles */
}

.sidebar-footer {
  /* Footer styles */
}

.user-section {
  /* User section styles */
}

.logout-btn {
  /* Logout button styles */
}

/* Add any additional styles you need */
</style>
<style scoped>
/* Modern Sidebar Component Styles */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

.sidebar {
  width: 280px;
  background: linear-gradient(180deg, #2d3748 0%, #1a202c 100%);
  color: white;
  height: 100vh;
  padding: 0;
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  position: relative;
  overflow: hidden;
  font-family: 'Inter', sans-serif;
  box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
}

.sidebar::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: radial-gradient(circle at 20% 50%, rgba(102, 126, 234, 0.1) 0%, transparent 50%),
              radial-gradient(circle at 80% 20%, rgba(118, 75, 162, 0.1) 0%, transparent 50%),
              radial-gradient(circle at 40% 80%, rgba(72, 187, 120, 0.05) 0%, transparent 50%);
  pointer-events: none;
  z-index: 0;
}

/* Alert System */
.sidebar-alert {
  position: absolute;
  top: 1rem;
  left: 1rem;
  right: 1rem;
  z-index: 1000;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  backdrop-filter: blur(10px);
  animation: alertSlideDown 0.3s ease;
  font-size: 0.875rem;
}

@keyframes alertSlideDown {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.sidebar-alert.success {
  background: linear-gradient(135deg, rgba(72, 187, 120, 0.9), rgba(56, 161, 105, 0.9));
  border: 1px solid rgba(72, 187, 120, 0.3);
}

.sidebar-alert.error {
  background: linear-gradient(135deg, rgba(229, 62, 62, 0.9), rgba(197, 48, 48, 0.9));
  border: 1px solid rgba(229, 62, 62, 0.3);
}

.sidebar-alert.warning {
  background: linear-gradient(135deg, rgba(237, 137, 54, 0.9), rgba(221, 107, 32, 0.9));
  border: 1px solid rgba(237, 137, 54, 0.3);
}

.sidebar-alert.info {
  background: linear-gradient(135deg, rgba(66, 153, 225, 0.9), rgba(49, 130, 206, 0.9));
  border: 1px solid rgba(66, 153, 225, 0.3);
}

.alert-content {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex: 1;
}

.alert-close {
  background: none;
  border: none;
  color: inherit;
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 4px;
  transition: background 0.2s ease;
}

.alert-close:hover {
  background: rgba(255, 255, 255, 0.2);
}

/* Brand Section */
.brand-section {
  padding: 2rem 1.5rem;
  text-align: center;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  position: relative;
  z-index: 1;
}

.brand-logo {
  width: 60px;
  height: 60px;
  background: linear-gradient(135deg, #667eea, #764ba2);
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1rem;
  font-size: 1.5rem;
  color: white;
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
  transition: transform 0.3s ease;
}

.brand-logo:hover {
  transform: scale(1.05);
}

.logo {
  font-size: 1.75rem;
  margin: 0 0 0.25rem 0;
  font-weight: 700;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.brand-subtitle {
  color: #a0aec0;
  font-size: 0.875rem;
  margin: 0;
  font-weight: 500;
}

/* Navigation */
.navigation {
  flex: 1;
  padding: 1rem 0;
  position: relative;
  z-index: 1;
  overflow-y: auto;
}

.nav {
  list-style: none;
  padding: 0;
  margin: 0;
}

.nav-item {
  margin: 0.25rem 0;
  position: relative;
}

.nav-section-title {
  padding: 0.75rem 1.5rem 0.5rem;
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: #a0aec0;
  margin: 0.75rem 0 0.25rem 0;
  display: block !important;
}

.nav-link {
  color: #e2e8f0;
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.7rem 1.25rem;
  position: relative;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border-radius: 10px;
  overflow: hidden;
  margin: 0 0.5rem;
}

/* Background gradient effect on hover */
.nav-link::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, rgba(102, 126, 234, 0.15) 0%, rgba(118, 75, 162, 0.1) 100%);
  transition: left 0.3s ease;
  z-index: 0;
}

.nav-link:hover::before {
  left: 0;
}

/* Active state gradient background */
.nav-link.router-link-exact-active::before {
  left: 0;
  background: linear-gradient(90deg, rgba(102, 126, 234, 0.25) 0%, rgba(118, 75, 162, 0.15) 100%);
}

/* Icon styling */
.nav-icon {
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.125rem;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  color: #a0aec0;
  flex-shrink: 0;
  position: relative;
  z-index: 1;
}

.nav-link:hover .nav-icon {
  color: #667eea;
  transform: scale(1.15) translateX(2px);
  filter: drop-shadow(0 0 8px rgba(102, 126, 234, 0.4));
}

.nav-link.router-link-exact-active .nav-icon {
  color: #667eea;
  transform: scale(1.2);
  filter: drop-shadow(0 0 10px rgba(102, 126, 234, 0.5));
}

/* Text styling */
.nav-text {
  font-weight: 500;
  font-size: 0.95rem;
  flex: 1;
  position: relative;
  z-index: 1;
  transition: all 0.3s ease;
}

.nav-link:hover .nav-text {
  color: #fff;
  transform: translateX(4px);
}

.nav-link.router-link-exact-active .nav-text {
  color: #fff;
  font-weight: 600;
}

/* Right accent line for active state */
.nav-indicator {
  position: absolute;
  right: 0;
  top: 0;
  bottom: 0;
  width: 3px;
  background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
  transform: scaleY(0);
  opacity: 0;
  transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
  border-radius: 3px 0 0 3px;
}

.nav-link.router-link-exact-active .nav-indicator {
  transform: scaleY(1);
  opacity: 1;
}

/* Left accent for active state */
.nav-accent {
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  width: 3px;
  height: 0;
  background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
  border-radius: 0 3px 3px 0;
  transition: height 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.nav-link.router-link-exact-active .nav-accent {
  height: 60%;
}

/* Ripple effect on click */
.nav-link::after {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 0;
  height: 0;
  background: radial-gradient(circle, rgba(102, 126, 234, 0.3) 0%, transparent 70%);
  border-radius: 50%;
  transform: translate(-50%, -50%);
  pointer-events: none;
}

.nav-link:active::after {
  animation: ripple 0.6s ease-out;
}

@keyframes ripple {
  0% {
    width: 0;
    height: 0;
    opacity: 1;
  }
  100% {
    width: 300px;
    height: 300px;
    opacity: 0;
  }
}

/* Smooth transition for all link states */
.nav-link {
  outline: none;
}

.nav-link:focus-visible {
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.3);
}

/* Footer Section - Organized Layout */
.sidebar-footer {
  padding: 1.25rem;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.user-section {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  background: rgba(255, 255, 255, 0.04);
  border-radius: 10px;
  transition: all 0.3s ease;
  border: 1px solid rgba(255, 255, 255, 0.06);
}

.user-section:hover {
  background: rgba(255, 255, 255, 0.08);
  border-color: rgba(255, 255, 255, 0.12);
}

.user-avatar {
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, #48bb78, #38a169);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1rem;
  flex-shrink: 0;
  box-shadow: 0 4px 12px rgba(72, 187, 120, 0.2);
}

.user-info {
  flex: 1;
  min-width: 0;
}

.user-name {
  color: white;
  font-weight: 600;
  font-size: 0.85rem;
  margin: 0 0 0.1rem 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.user-role {
  color: #a0aec0;
  font-size: 0.7rem;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  font-weight: 500;
}

/* Chat Button - Improved */
.chat-btn {
  flex: 1;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.85), rgba(118, 75, 162, 0.85));
  color: white;
  border: 1px solid rgba(102, 126, 234, 0.3);
  padding: 0.7rem 1rem;
  border-radius: 10px;
  font-size: 0.95rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  font-family: inherit;
  font-weight: 500;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
  white-space: nowrap;
}

.chat-btn:hover {
  background: linear-gradient(135deg, rgba(102, 126, 234, 1), rgba(118, 75, 162, 0.95));
  border-color: rgba(102, 126, 234, 0.6);
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.25);
}

.chat-btn:active {
  transform: translateY(0);
}

/* Unread Badge - Enhanced */
.unread-badge {
  position: absolute;
  top: -6px;
  right: -6px;
  background: linear-gradient(135deg, #ef4444, #dc2626);
  color: white;
  border-radius: 50%;
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.65rem;
  font-weight: 700;
  border: 2px solid #2d3748;
  animation: badgePulse 2s infinite;
  box-shadow: 0 2px 8px rgba(220, 38, 38, 0.3);
}

@keyframes badgePulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.1); }
}

/* Logout Button - Improved */
.logout-btn {
  flex: 1;
  background: linear-gradient(135deg, rgba(229, 62, 62, 0.85), rgba(197, 48, 48, 0.85));
  color: white;
  border: 1px solid rgba(229, 62, 62, 0.3);
  padding: 0.7rem 1rem;
  border-radius: 10px;
  font-size: 0.95rem;
  font-weight: 500;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  font-family: inherit;
  box-shadow: 0 4px 12px rgba(229, 62, 62, 0.15);
  white-space: nowrap;
}

.logout-btn:hover {
  background: linear-gradient(135deg, rgba(229, 62, 62, 1), rgba(197, 48, 48, 0.95));
  border-color: rgba(229, 62, 62, 0.6);
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(229, 62, 62, 0.25);
}

.logout-btn:active {
  transform: translateY(0);
}

.logout-btn i {
  transition: all 0.3s ease;
}

.logout-btn:hover i {
  transform: scale(1.1);
}

/* Responsive Adjustments */
@media (max-width: 768px) {
  .sidebar-footer {
    padding: 1rem;
    gap: 0.5rem;
  }

  .footer-actions {
    gap: 0.5rem;
  }

  .chat-btn,
  .logout-btn {
    padding: 0.65rem 0.75rem;
    font-size: 0.85rem;
  }

  .chat-btn span,
  .logout-btn span {
    display: none;
  }

  .user-section {
    padding: 0.6rem;
  }

  .user-avatar {
    width: 36px;
    height: 36px;
    font-size: 0.9rem;
  }

  .user-name {
    font-size: 0.8rem;
  }

  .user-role {
    font-size: 0.65rem;
  }
}
</style>
