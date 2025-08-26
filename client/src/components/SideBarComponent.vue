<template>
  <div class="sidebar">
    <!-- Alert System for Sidebar -->
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
        <li class="nav-item">
          <router-link to="/" class="nav-link" @click="setActiveItem('dashboard')">
            <div class="link-content">
              <i class="fas fa-tachometer-alt nav-icon"></i>
              <span class="nav-text">Dashboard</span>
              <div class="nav-indicator"></div>
            </div>
          </router-link>
        </li>
        
        <li class="nav-item">
          <router-link to="/sales" class="nav-link" @click="setActiveItem('sales')">
            <div class="link-content">
              <i class="fas fa-cash-register nav-icon"></i>
              <span class="nav-text">POS Sales</span>
              <div class="nav-indicator"></div>
            </div>
          </router-link>
        </li>
        
        <li class="nav-item">
          <router-link to="/products" class="nav-link" @click="setActiveItem('products')">
            <div class="link-content">
              <i class="fas fa-box nav-icon"></i>
              <span class="nav-text">Products</span>
              <div class="nav-indicator"></div>
            </div>
          </router-link>
        </li>
        
        <li class="nav-item">
          <router-link to="/inventory" class="nav-link" @click="setActiveItem('inventory')">
            <div class="link-content">
              <i class="fas fa-warehouse nav-icon"></i>
              <span class="nav-text">Inventory</span>
              <div class="nav-indicator"></div>
            </div>
          </router-link>
        </li>
        
        <li class="nav-item">
          <router-link to="/reports" class="nav-link" @click="setActiveItem('reports')">
            <div class="link-content">
              <i class="fas fa-chart-bar nav-icon"></i>
              <span class="nav-text">Reports</span>
              <div class="nav-indicator"></div>
            </div>
          </router-link>
        </li>
        
        <li class="nav-item">
          <router-link to="/expenses" class="nav-link" @click="setActiveItem('expenses')">
            <div class="link-content">
              <i class="fas fa-receipt nav-icon"></i>
              <span class="nav-text">Expenses</span>
              <div class="nav-indicator"></div>
            </div>
          </router-link>
        </li>
      </ul>
    </nav>

    <!-- Loading Spinner for Sidebar Operations -->
    <div v-if="isLoading" class="sidebar-spinner">
      <div class="spinner"></div>
      <p class="loading-text">Loading...</p>
    </div>

    <!-- Footer Section -->
    <div class="sidebar-footer">
      <div class="user-section">
        <div class="user-avatar">
          <i class="fas fa-user"></i>
        </div>
        <div class="user-info">
          <p class="user-name">Admin User</p>
          <p class="user-role">Administrator</p>
        </div>
      </div>
      
      <button class="logout-btn" @click="handleLogout">
        <i class="fas fa-sign-out-alt"></i>
        <span>Logout</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

// Reactive state
const isLoading = ref(false)
const activeItem = ref('dashboard')

// Alert system
const alert = reactive({
  show: false,
  type: 'info',
  message: '',
  timeout: null
})

// Methods
const setActiveItem = (item) => {
  activeItem.value = item
}

const showAlert = (type, message, duration = 3000) => {
  alert.show = true
  alert.type = type
  alert.message = message
  
  if (alert.timeout) {
    clearTimeout(alert.timeout)
  }
  
  alert.timeout = setTimeout(() => {
    dismissAlert()
  }, duration)
}

const dismissAlert = () => {
  alert.show = false
  alert.type = 'info'
  alert.message = ''
  
  if (alert.timeout) {
    clearTimeout(alert.timeout)
    alert.timeout = null
  }
}

const getAlertIcon = (type) => {
  const icons = {
    success: 'fas fa-check-circle',
    error: 'fas fa-exclamation-circle',
    warning: 'fas fa-exclamation-triangle',
    info: 'fas fa-info-circle'
  }
  return icons[type] || icons.info
}

const setLoading = (loading) => {
  isLoading.value = loading
}

const handleLogout = async () => {
  try {
    setLoading(true)
    showAlert('info', 'Logging out...', 1000)
    
    // Simulate logout process
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    // Clear any stored auth data
    localStorage.removeItem('authToken')
    localStorage.removeItem('userData')
    localStorage.removeItem('rememberMe')
    
    showAlert('success', 'Logged out successfully!', 1500)
    
    setTimeout(() => {
      router.push('/login')
    }, 1500)
  } catch (error) {
    showAlert('error', 'Error during logout')
  } finally {
    setLoading(false)
  }
}

// Lifecycle
onMounted(() => {
  // Set initial active item based on current route
  const currentPath = router.currentRoute.value.path
  if (currentPath === '/') {
    activeItem.value = 'dashboard'
  } else if (currentPath.includes('/sales')) {
    activeItem.value = 'sales'
  } else if (currentPath.includes('/products')) {
    activeItem.value = 'products'
  } else if (currentPath.includes('/inventory')) {
    activeItem.value = 'inventory'
  } else if (currentPath.includes('/reports')) {
    activeItem.value = 'reports'
  }
})

// Expose methods for parent components
defineExpose({
  showAlert,
  setLoading,
  dismissAlert
})
</script>

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

.nav-link {
  color: #e2e8f0;
  text-decoration: none;
  display: block;
  position: relative;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border-radius: 0;
  overflow: hidden;
}

.nav-link:hover {
  color: white;
  background: linear-gradient(90deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.05));
}

.nav-link.router-link-exact-active {
  color: white;
  background: linear-gradient(90deg, rgba(102, 126, 234, 0.2), rgba(118, 75, 162, 0.1));
}

.nav-link.router-link-exact-active .nav-indicator {
  transform: scaleY(1);
  opacity: 1;
}

.link-content {
  display: flex;
  align-items: center;
  padding: 0.875rem 1.5rem;
  position: relative;
  transition: transform 0.2s ease;
}

.nav-link:hover .link-content {
  transform: translateX(8px);
}

.nav-icon {
  width: 20px;
  font-size: 1.125rem;
  margin-right: 1rem;
  transition: all 0.3s ease;
  color: #a0aec0;
}

.nav-link:hover .nav-icon,
.nav-link.router-link-exact-active .nav-icon {
  color: #667eea;
  transform: scale(1.1);
}

.nav-text {
  font-weight: 500;
  font-size: 0.95rem;
  flex: 1;
}

.nav-indicator {
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 4px;
  background: linear-gradient(180deg, #667eea, #764ba2);
  transform: scaleY(0);
  opacity: 0;
  transition: all 0.3s ease;
  border-radius: 0 2px 2px 0;
}

/* Loading Spinner */
.sidebar-spinner {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
  z-index: 100;
  background: rgba(45, 55, 72, 0.95);
  backdrop-filter: blur(10px);
  padding: 2rem;
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid rgba(102, 126, 234, 0.3);
  border-left: 3px solid #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.loading-text {
  color: #e2e8f0;
  font-size: 0.875rem;
  margin: 0;
  font-weight: 500;
}

/* Footer Section */
.sidebar-footer {
  padding: 1.5rem;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  position: relative;
  z-index: 1;
}

.user-section {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1rem;
  padding: 0.75rem;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 12px;
  transition: background 0.3s ease;
}

.user-section:hover {
  background: rgba(255, 255, 255, 0.08);
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
}

.user-info {
  flex: 1;
  min-width: 0;
}

.user-name {
  color: white;
  font-weight: 600;
  font-size: 0.875rem;
  margin: 0 0 0.125rem 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.user-role {
  color: #a0aec0;
  font-size: 0.75rem;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.logout-btn {
  width: 100%;
  background: linear-gradient(135deg, rgba(229, 62, 62, 0.8), rgba(197, 48, 48, 0.8));
  color: white;
  border: none;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
  font-family: inherit;
}

.logout-btn:hover {
  background: linear-gradient(135deg, rgba(229, 62, 62, 0.9), rgba(197, 48, 48, 0.9));
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(229, 62, 62, 0.3);
}

.logout-btn:active {
  transform: translateY(0);
}

/* Responsive Design */
@media (max-width: 1024px) {
  .sidebar {
    width: 260px;
  }
}

@media (max-width: 768px) {
  .sidebar {
    width: 240px;
  }
  
  .brand-section {
    padding: 1.5rem 1rem;
  }
  
  .logo {
    font-size: 1.5rem;
  }
  
  .link-content {
    padding: 0.75rem 1rem;
  }
  
  .nav-icon {
    margin-right: 0.75rem;
  }
}

/* Dark Mode Enhancements */
@media (prefers-color-scheme: dark) {
  .sidebar {
    background: linear-gradient(180deg, #1a202c 0%, #0d1117 100%);
  }
}

/* Smooth scrollbar for navigation */
.navigation::-webkit-scrollbar {
  width: 4px;
}

.navigation::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.05);
}

.navigation::-webkit-scrollbar-thumb {
  background: rgba(102, 126, 234, 0.5);
  border-radius: 2px;
}

.navigation::-webkit-scrollbar-thumb:hover {
  background: rgba(102, 126, 234, 0.7);
}

/* Font Smoothing */
* {
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}
</style>
