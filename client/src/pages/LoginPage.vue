<template>
  <div class="login-container">
    <!-- Alert System -->
    <div v-if="alert.show" class="alert-container" :class="alert.type">
      <div class="alert-content">
        <i :class="getAlertIcon()"></i>
        <span>{{ alert.message }}</span>
      </div>
      <button class="alert-close" @click="hideAlert">
        <i class="fas fa-times"></i>
      </button>
    </div>

    <!-- Background Elements -->
    <div class="background-overlay">
      <div class="floating-shape shape-1"></div>
      <div class="floating-shape shape-2"></div>
      <div class="floating-shape shape-3"></div>
      <div class="floating-shape shape-4"></div>
    </div>

    <!-- Main Login Card -->
    <div class="login-card">
      <!-- Logo/Brand Section -->
      <div class="brand-section">
        <div class="brand-logo">
          <i class="fas fa-seedling"></i>
        </div>
        <h1 class="brand-title">AGROVET POS</h1>
        <p class="brand-subtitle">Your Agricultural Business Partner</p>
      </div>

      <!-- Login Form -->
      <form @submit.prevent="handleLogin" class="login-form">
        <div class="form-header">
          <h2 class="form-title">Welcome Back</h2>
          <p class="form-subtitle">Sign in to your account</p>
        </div>

        <!-- Username Field -->
        <div class="form-group">
          <label for="username" class="form-label">
            <i class="fas fa-user"></i>
            Username
          </label>
          <div class="input-wrapper">
            <input
              id="username"
              type="text"
              v-model="username"
              :disabled="isLoading"
              required
              class="form-input"
              placeholder="Enter your username"
            />
            <div class="input-focus-ring"></div>
          </div>
        </div>

        <!-- Password Field -->
        <div class="form-group">
          <label for="password" class="form-label">
            <i class="fas fa-lock"></i>
            Password
          </label>
          <div class="input-wrapper">
            <input
              id="password"
              :type="showPassword ? 'text' : 'password'"
              v-model="password"
              :disabled="isLoading"
              required
              class="form-input"
              placeholder="Enter your password"
            />
            <button
              type="button"
              class="password-toggle"
              @click="togglePasswordVisibility"
              :disabled="isLoading"
            >
              <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
            </button>
            <div class="input-focus-ring"></div>
          </div>
        </div>

        <!-- Remember Me -->
        <div class="form-options">
          <label class="checkbox-label">
            <input type="checkbox" v-model="rememberMe" :disabled="isLoading" />
            <span class="checkmark"></span>
            Remember me
          </label>
          <a href="#" class="forgot-link">Forgot password?</a>
        </div>

        <!-- Login Button -->
        <button
          type="submit"
          class="login-btn"
          :disabled="isLoading || !canSubmit"
          :class="{ 'loading': isLoading }"
        >
          <div v-if="isLoading" class="btn-loading">
            <div class="btn-spinner"></div>
            <span>Signing in...</span>
          </div>
          <div v-else class="btn-content">
            <i class="fas fa-sign-in-alt"></i>
            <span>Sign In</span>
          </div>
        </button>

        <!-- Demo Credentials -->
        <div class="demo-section">
          <p class="demo-title">Demo Credentials:</p>
          <div class="demo-credentials">
            <span><strong>Username:</strong> Admin</span>
            <span><strong>Password:</strong> Admin123</span>
          </div>
        </div>
      </form>

      <!-- Footer -->
      <div class="login-footer">
        <p>&copy; 2025 Agrovet POS. All rights reserved.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

// Form state
const username = ref('')
const password = ref('')
const showPassword = ref(false)
const rememberMe = ref(false)
const isLoading = ref(false)

// Alert system
const alert = ref({
  show: false,
  message: '',
  type: 'success' // success, error, warning, info
})

// Computed properties
const canSubmit = computed(() => {
  return username.value.trim().length > 0 && password.value.length > 0
})

// Alert functions
function showAlert(message, type = 'success') {
  alert.value = {
    show: true,
    message,
    type
  }
  
  // Auto-hide success alerts
  if (type === 'success') {
    setTimeout(() => {
      hideAlert()
    }, 3000)
  }
}

function hideAlert() {
  alert.value.show = false
}

function getAlertIcon() {
  switch (alert.value.type) {
    case 'success': return 'fas fa-check-circle'
    case 'error': return 'fas fa-exclamation-circle'
    case 'warning': return 'fas fa-exclamation-triangle'
    case 'info': return 'fas fa-info-circle'
    default: return 'fas fa-info-circle'
  }
}

// Form functions
function togglePasswordVisibility() {
  showPassword.value = !showPassword.value
}

async function handleLogin() {
  if (!canSubmit.value) {
    showAlert('Please fill in all required fields.', 'warning')
    return
  }

  isLoading.value = true
  hideAlert()

  try {
    // Simulate API call delay
    await new Promise(resolve => setTimeout(resolve, 1500))

    // Hardcoded credentials for demo
    const correctUsername = 'Admin'
    const correctPassword = 'Admin123'

    if (username.value === correctUsername && password.value === correctPassword) {
      showAlert('Login successful! Redirecting to dashboard...', 'success')
      
      // Set authentication tokens
      localStorage.setItem('authToken', 'demo-auth-token-' + Date.now())
      localStorage.setItem('isLoggedIn', 'true')
      localStorage.setItem('userData', JSON.stringify({
        username: username.value,
        role: 'admin',
        loginTime: new Date().toISOString()
      }))
      
      // Store login state if remember me is checked
      if (rememberMe.value) {
        localStorage.setItem('rememberMe', 'true')
        localStorage.setItem('rememberedUser', username.value)
      } else {
        localStorage.removeItem('rememberMe')
        localStorage.removeItem('rememberedUser')
      }
      
      // Redirect using Vue Router after success message
      setTimeout(() => {
        router.push('/')
      }, 1500)
      
      console.log('Login successful')
    } else {
      showAlert('Invalid username or password. Please try again.', 'error')
      console.log('Login failed')
    }
  } catch (error) {
    showAlert('An error occurred during login. Please try again.', 'error')
    console.error('Login error:', error)
  } finally {
    isLoading.value = false
  }
}

// Auto-fill remembered user
onMounted(() => {
  const remembered = localStorage.getItem('rememberedUser')
  const rememberMeFlag = localStorage.getItem('rememberMe')
  
  if (remembered && rememberMeFlag === 'true') {
    username.value = remembered
    rememberMe.value = true
  }
})
</script>

<style scoped>
/* Modern Login Page Styles */
* {
  box-sizing: border-box;
}

.login-container {
  height: 100vh;
  max-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
  position: relative;
  overflow: hidden;
  box-sizing: border-box;
}

/* Alert System */
.alert-container {
  position: fixed;
  top: 2rem;
  left: 50%;
  transform: translateX(-50%);
  z-index: 1000;
  max-width: 400px;
  width: 90%;
  padding: 1rem 1.5rem;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
  backdrop-filter: blur(10px);
  animation: alertSlideIn 0.3s ease;
}

@keyframes alertSlideIn {
  from {
    opacity: 0;
    transform: translateX(-50%) translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
  }
}

.alert-container.success {
  background: linear-gradient(135deg, rgba(72, 187, 120, 0.95), rgba(56, 161, 105, 0.95));
  color: white;
  border: 1px solid rgba(72, 187, 120, 0.3);
}

.alert-container.error {
  background: linear-gradient(135deg, rgba(229, 62, 62, 0.95), rgba(197, 48, 48, 0.95));
  color: white;
  border: 1px solid rgba(229, 62, 62, 0.3);
}

.alert-container.warning {
  background: linear-gradient(135deg, rgba(237, 137, 54, 0.95), rgba(221, 107, 32, 0.95));
  color: white;
  border: 1px solid rgba(237, 137, 54, 0.3);
}

.alert-container.info {
  background: linear-gradient(135deg, rgba(66, 153, 225, 0.95), rgba(49, 130, 206, 0.95));
  color: white;
  border: 1px solid rgba(66, 153, 225, 0.3);
}

.alert-content {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex: 1;
}

.alert-content i {
  font-size: 1.1rem;
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

/* Background Animation */
.background-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  overflow: hidden;
  z-index: 0;
}

.floating-shape {
  position: absolute;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 50%;
  animation: float 20s infinite linear;
}

.shape-1 {
  width: 80px;
  height: 80px;
  top: 20%;
  left: 10%;
  animation-delay: 0s;
}

.shape-2 {
  width: 60px;
  height: 60px;
  top: 60%;
  right: 15%;
  animation-delay: 5s;
}

.shape-3 {
  width: 100px;
  height: 100px;
  bottom: 20%;
  left: 20%;
  animation-delay: 10s;
}

.shape-4 {
  width: 40px;
  height: 40px;
  top: 30%;
  right: 30%;
  animation-delay: 15s;
}

@keyframes float {
  0%, 100% {
    transform: translateY(0px) rotate(0deg);
    opacity: 0.7;
  }
  50% {
    transform: translateY(-20px) rotate(180deg);
    opacity: 1;
  }
}

/* Main Login Card */
.login-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 20px;
  padding: 2rem 2.5rem;
  width: 100%;
  max-width: 420px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
  position: relative;
  z-index: 1;
  animation: cardSlideIn 0.6s ease;
  box-sizing: border-box;
}

@keyframes cardSlideIn {
  from {
    opacity: 0;
    transform: translateY(30px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

/* Brand Section */
.brand-section {
  text-align: center;
  margin-bottom: 1.5rem;
}

.brand-logo {
  width: 60px;
  height: 60px;
  background: linear-gradient(135deg, #48bb78, #38a169);
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 0.75rem;
  font-size: 1.5rem;
  color: white;
  box-shadow: 0 8px 20px rgba(72, 187, 120, 0.3);
}

.brand-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: #2d3748;
  margin: 0 0 0.25rem 0;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.brand-subtitle {
  color: #718096;
  margin: 0;
  font-size: 0.875rem;
}

/* Form Header */
.form-header {
  text-align: center;
  margin-bottom: 1.5rem;
}

.form-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: #2d3748;
  margin: 0 0 0.25rem 0;
}

.form-subtitle {
  color: #718096;
  margin: 0;
  font-size: 0.875rem;
}

/* Form Styling */
.login-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
  color: #4a5568;
  font-size: 0.95rem;
}

.form-label i {
  color: #667eea;
  width: 16px;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.form-input {
  width: 100%;
  padding: 1rem 1.25rem;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 1rem;
  background: white;
  color: #2d3748;
  transition: all 0.3s ease;
  position: relative;
  z-index: 1;
}

.form-input:focus {
  outline: none;
  border-color: #667eea;
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
}

.form-input:disabled {
  background: #f7fafc;
  color: #a0aec0;
  cursor: not-allowed;
}

.form-input::placeholder {
  color: #a0aec0;
}

.input-focus-ring {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  border-radius: 12px;
  background: linear-gradient(135deg, #667eea, #764ba2);
  opacity: 0;
  z-index: 0;
  transition: opacity 0.3s ease;
  padding: 2px;
}

.form-input:focus + .input-focus-ring,
.form-input:focus + .password-toggle + .input-focus-ring {
  opacity: 0.1;
}

.password-toggle {
  position: absolute;
  right: 1rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: #a0aec0;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 6px;
  transition: color 0.3s ease;
  z-index: 2;
}

.password-toggle:hover {
  color: #667eea;
}

.password-toggle:disabled {
  cursor: not-allowed;
  opacity: 0.5;
}

/* Form Options */
.form-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin: 0.5rem 0;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  color: #4a5568;
  font-size: 0.9rem;
  position: relative;
}

.checkbox-label input[type="checkbox"] {
  opacity: 0;
  position: absolute;
}

.checkmark {
  width: 18px;
  height: 18px;
  border: 2px solid #e2e8f0;
  border-radius: 4px;
  position: relative;
  transition: all 0.3s ease;
}

.checkbox-label input[type="checkbox"]:checked + .checkmark {
  background: linear-gradient(135deg, #667eea, #764ba2);
  border-color: #667eea;
}

.checkbox-label input[type="checkbox"]:checked + .checkmark::after {
  content: '✓';
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  color: white;
  font-size: 12px;
  font-weight: bold;
}

.forgot-link {
  color: #667eea;
  text-decoration: none;
  font-size: 0.9rem;
  font-weight: 500;
  transition: color 0.3s ease;
}

.forgot-link:hover {
  color: #764ba2;
  text-decoration: underline;
}

/* Login Button */
.login-btn {
  width: 100%;
  padding: 1rem;
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  margin-top: 1rem;
  position: relative;
  overflow: hidden;
}

.login-btn::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
  transition: left 0.5s;
}

.login-btn:hover::before {
  left: 100%;
}

.login-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
}

.login-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
}

.login-btn.loading {
  background: linear-gradient(135deg, #a0aec0, #718096);
}

.btn-loading {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.btn-spinner {
  width: 20px;
  height: 20px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-left: 2px solid white;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.btn-content {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

/* Demo Section */
.demo-section {
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
  border: 1px solid rgba(102, 126, 234, 0.2);
  border-radius: 12px;
  padding: 1rem;
  margin-top: 1rem;
  text-align: center;
}

.demo-title {
  font-size: 0.9rem;
  font-weight: 600;
  color: #4a5568;
  margin: 0 0 0.5rem 0;
}

.demo-credentials {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  font-size: 0.85rem;
  color: #667eea;
}

/* Footer */
.login-footer {
  text-align: center;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #e2e8f0;
}

.login-footer p {
  color: #a0aec0;
  font-size: 0.85rem;
  margin: 0;
}

/* Responsive Design */
@media (max-width: 640px) {
  .login-container {
    padding: 0.5rem;
  }
  
  .login-card {
    padding: 1.5rem;
    border-radius: 16px;
    max-height: 95vh;
  }
  
  .brand-title {
    font-size: 1.5rem;
  }
  
  .form-title {
    font-size: 1.25rem;
  }
  
  .form-options {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
}

@media (max-width: 480px) {
  .login-card {
    padding: 1.25rem;
    max-height: 98vh;
  }
  
  .brand-logo {
    width: 50px;
    height: 50px;
    font-size: 1.25rem;
  }
  
  .brand-title {
    font-size: 1.25rem;
  }
  
  .form-input {
    padding: 0.75rem 1rem;
  }
}

@media (max-height: 700px) {
  .login-card {
    max-height: 95vh;
    padding: 1.5rem 2rem;
  }
  
  .brand-section {
    margin-bottom: 1rem;
  }
  
  .form-header {
    margin-bottom: 1rem;
  }
  
  .login-form {
    gap: 0.75rem;
  }
}

/* Font Import */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

/* Global Font Smoothing */
body {
  font-family: 'Inter', sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}
</style>
