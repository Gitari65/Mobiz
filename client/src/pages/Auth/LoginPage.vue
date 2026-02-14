<!-- Enhanced LoginPage.vue with Moving Bubbles Animation -->
<template>
  <div class="login-page">
    <!-- Animated Background with Moving Bubbles -->
    <div class="animated-background">
      <div class="bubbles">
        <div class="bubble" v-for="n in 15" :key="n"></div>
      </div>
      <div class="gradient-overlay"></div>
    </div>

    <div class="login-container">
      <div class="login-card">
        <!-- Floating Brand Logo -->
        <div class="brand-section">
          <div class="brand-logo-wrapper">
            <div class="brand-logo">
              <i class="fas fa-shopping-cart"></i>
              <div class="logo-pulse"></div>
            </div>
          </div>
          <h1 class="mobiz-title">
            <span class="mobiz-text">Mobiz</span>
          </h1>
          <h1 class="login-title">
            <span class="title-text">Mobiz</span>
            <span class="title-accent">POS</span>
          </h1>
          <p class="login-subtitle">Welcome back! Sign in to continue</p>
        </div>

        <!-- Login Form -->
        <form @submit.prevent="handleLogin" class="login-form">
          <div class="form-group">
            <div class="input-wrapper">
              <i class="fas fa-envelope input-icon"></i>
              <input
                v-model="loginForm.email"
                type="email"
                class="form-input"
                required
                :class="{ filled: loginForm.email }"
                placeholder=" "
                autocomplete="email"
              />
              <span class="floating-label" :class="{ floating: loginForm.email }">Email</span>
              <span class="input-line"></span>
            </div>
            <transition name="error-slide">
              <span v-if="errors.email" class="error-text">
                <i class="fas fa-exclamation-circle"></i>
                {{ errors.email }}
              </span>
            </transition>
          </div>

          <div class="form-group">
            <div class="input-wrapper">
              <i class="fas fa-lock input-icon"></i>
              <input
                v-model="loginForm.password"
                :type="showPassword ? 'text' : 'password'"
                class="form-input"
                required
                :class="{ filled: loginForm.password }"
                placeholder=" "
                autocomplete="current-password"
              />
              <span class="floating-label" :class="{ floating: loginForm.password }">Password</span>
              <span class="input-line"></span>
              <button
                type="button"
                class="password-toggle"
                @click="showPassword = !showPassword"
              >
                <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
              </button>
            </div>
            <transition name="error-slide">
              <span v-if="errors.password" class="error-text">
                <i class="fas fa-exclamation-circle"></i>
                {{ errors.password }}
              </span>
            </transition>
          </div>

          <div class="form-options">
            <label class="checkbox-wrapper">
              <input
                type="checkbox"
                v-model="loginForm.rememberMe"
                class="checkbox-input"
              />
              <div class="checkbox-custom">
                <i class="fas fa-check checkbox-icon"></i>
              </div>
              <span class="checkbox-text">Remember me</span>
            </label>
            <a href="#" @click.prevent="openForgotPasswordModal" class="forgot-link">Forgot Password?</a>
          </div>

          <button
            type="submit"
            class="login-button"
            :disabled="isLoading"
            :class="{ loading: isLoading }"
          >
            <div class="button-content">
              <div v-if="isLoading" class="loading-spinner">
                <div class="spinner-ring"></div>
              </div>
              <i v-else class="fas fa-sign-in-alt button-icon"></i>
              <span class="button-text">{{ isLoading ? 'Signing in...' : 'Sign In' }}</span>
            </div>
            <div class="button-ripple"></div>
          </button>
        </form>

        <!-- Social Login Options -->
        <div class="divider">
          <span class="divider-text">or continue with</span>
        </div>

        <div class="social-login">
          <button class="social-button google" type="button">
            <i class="fab fa-google"></i>
          </button>
          <button class="social-button microsoft" type="button">
            <i class="fab fa-microsoft"></i>
          </button>
          <button class="social-button apple" type="button">
            <i class="fab fa-apple"></i>
          </button>
        </div>

        <!-- Footer -->
        <div class="login-footer">
          <p class="footer-text">
            Don't have an account?
            <router-link to="/signup" class="signup-link">Create Account</router-link>
          </p>
        </div>

        <!-- Alert Messages -->
        <transition name="alert-slide">
          <div v-if="alert.show" class="login-alert" :class="alert.type">
            <div class="alert-content">
              <i :class="getAlertIcon(alert.type)" class="alert-icon"></i>
              <span class="alert-message">{{ alert.message }}</span>
            </div>
            <button @click="alert.show = false" class="alert-close">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </transition>
      </div>

      <!-- Version Info -->
      <div class="version-info">
        <p>Mobiz POS v2.1.0 | Secure Business Management</p>
      </div>
    </div>

    <!-- OTP Modal -->
    <transition name="fade">
      <div v-if="otpStep.active" class="otp-backdrop">
        <div class="otp-modal" :class="otpStep.isSignup ? 'signup-view' : 'login-view'">
          <!-- Signup View -->
          <div v-if="otpStep.isSignup" class="otp-content signup-content">
            <div class="otp-header signup-header">
              <div class="otp-icon">
                <i class="fas fa-envelope-open-text"></i>
              </div>
              <h2>Verify Your Email</h2>
              <p class="otp-subtitle">Activate your MOBIz account</p>
            </div>

            <div class="otp-body">
              <p class="otp-message">
                Welcome! We've sent a verification code to <strong>{{ otpStep.destination }}</strong> to activate your account.
              </p>

              <div class="otp-input-group">
                <label class="otp-label">Verification Code</label>
                <input
                  v-model="otpStep.code"
                  type="text"
                  inputmode="numeric"
                  maxlength="10"
                  class="otp-input signup-input"
                  placeholder="000000"
                />
              </div>

              <p v-if="otpStep.error" class="otp-error">
                <i class="fas fa-exclamation-circle"></i>
                {{ otpStep.error }}
              </p>

              <button 
                class="otp-button signup-button" 
                :disabled="otpStep.isVerifying || !otpStep.code.trim()" 
                @click="verifyOtp"
              >
                <div v-if="otpStep.isVerifying" class="otp-btn-spinner"></div>
                <i v-else class="fas fa-check"></i>
                <span>{{ otpStep.isVerifying ? 'Verifying...' : 'Verify & Activate' }}</span>
              </button>
            </div>

            <div class="otp-footer signup-footer">
              <div class="otp-help">
                <p class="otp-expiry">
                  <i class="fas fa-clock"></i>
                  Code expires in about {{ Math.ceil(otpStep.expiresIn / 60) }} minutes
                </p>
              </div>

              <div class="otp-resend-section">
                <p class="otp-resend-text">Didn't receive the code?</p>
                <button 
                  v-if="otpStep.remainingAttempts > 0"
                  @click="resendOtp" 
                  :disabled="otpStep.isResending || otpStep.remainingAttempts <= 0"
                  class="otp-resend-link"
                >
                  <i class="fas fa-redo"></i>
                  {{ otpStep.isResending ? 'Resending...' : 'Resend Code' }}
                </button>
                <p v-else class="otp-limit-reached">
                  <i class="fas fa-info-circle"></i>
                  Resend limit reached. Please contact support.
                </p>
                <p v-if="otpStep.remainingAttempts > 0 && !otpStep.isResending" class="otp-attempts">
                  {{ otpStep.remainingAttempts }} attempt{{ otpStep.remainingAttempts !== 1 ? 's' : '' }} remaining
                </p>
              </div>
            </div>
          </div>

          <!-- Login View -->
          <div v-else class="otp-content login-content">
            <div class="otp-header login-header">
              <div class="otp-icon login-icon">
                <i class="fas fa-shield-alt"></i>
              </div>
              <h2>Security Verification</h2>
              <p class="otp-subtitle">One-time password verification</p>
            </div>

            <div class="otp-body">
              <p class="otp-message">
                For your security, we've sent a verification code to <strong>{{ otpStep.destination }}</strong>.
              </p>

              <div class="otp-input-group">
                <label class="otp-label">Enter Code</label>
                <input
                  v-model="otpStep.code"
                  type="text"
                  inputmode="numeric"
                  maxlength="10"
                  class="otp-input login-input"
                  placeholder="000000"
                />
              </div>

              <p v-if="otpStep.error" class="otp-error">
                <i class="fas fa-exclamation-circle"></i>
                {{ otpStep.error }}
              </p>

              <button 
                class="otp-button login-button" 
                :disabled="otpStep.isVerifying || !otpStep.code.trim()" 
                @click="verifyOtp"
              >
                <div v-if="otpStep.isVerifying" class="otp-btn-spinner"></div>
                <i v-else class="fas fa-arrow-right"></i>
                <span>{{ otpStep.isVerifying ? 'Verifying...' : 'Verify & Continue' }}</span>
              </button>
            </div>

            <div class="otp-footer login-footer">
              <div class="otp-help">
                <p class="otp-expiry">
                  <i class="fas fa-hourglass-end"></i>
                  Code expires in {{ Math.ceil(otpStep.expiresIn / 60) }} minute{{ Math.ceil(otpStep.expiresIn / 60) !== 1 ? 's' : '' }}
                </p>
              </div>

              <div class="otp-resend-section">
                <p class="otp-resend-text">Code not received?</p>
                <button 
                  v-if="otpStep.remainingAttempts > 0"
                  @click="resendOtp" 
                  :disabled="otpStep.isResending"
                  class="otp-resend-link"
                >
                  <i class="fas fa-envelope"></i>
                  {{ otpStep.isResending ? 'Sending...' : 'Send New Code' }}
                </button>
                <p v-else class="otp-limit-reached">
                  <i class="fas fa-lock"></i>
                  Resend attempts exceeded. Contact support.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <!-- Forgot Password Modal -->
    <transition name="fade">
      <div v-if="forgotPasswordModal.active" class="forgot-backdrop" @click.self="closeForgotPasswordModal">
        <div class="forgot-modal">
          <button @click="closeForgotPasswordModal" class="forgot-close">
            <i class="fas fa-times"></i>
          </button>

          <!-- Step 1: Request Reset -->
          <div v-if="forgotPasswordModal.step === 'request'">
            <h2>Reset Your Password</h2>
            <p class="forgot-description">Enter your email address and we'll send you a reset code.</p>

            <div class="form-group">
              <label>Email Address</label>
              <input
                v-model="forgotPasswordModal.email"
                type="email"
                placeholder="your@email.com"
                class="forgot-input"
              />
            </div>

            <p v-if="forgotPasswordModal.error" class="error-text">{{ forgotPasswordModal.error }}</p>

            <button 
              @click="requestPasswordReset" 
              :disabled="forgotPasswordModal.loading || !forgotPasswordModal.email"
              class="forgot-button primary"
            >
              <span v-if="forgotPasswordModal.loading" class="btn-spinner"></span>
              {{ forgotPasswordModal.loading ? 'Sending...' : 'Send Reset Code' }}
            </button>

            <p class="forgot-footer">
              Remember your password? <a href="#" @click.prevent="closeForgotPasswordModal" class="back-link">Back to Login</a>
            </p>
          </div>

          <!-- Step 2: Verify & Reset -->
          <div v-if="forgotPasswordModal.step === 'verify'">
            <h2>Create New Password</h2>
            <p class="forgot-description">We sent a reset code to <strong>{{ forgotPasswordModal.destination }}</strong></p>

            <div class="form-group">
              <label>Reset Code</label>
              <input
                v-model="forgotPasswordModal.code"
                type="text"
                placeholder="Enter 6-digit code"
                maxlength="6"
                class="forgot-input"
              />
            </div>

            <div class="form-group">
              <label>New Password</label>
              <div class="password-wrapper">
                <input
                  v-model="forgotPasswordModal.password"
                  :type="forgotPasswordModal.showPassword ? 'text' : 'password'"
                  placeholder="At least 8 characters"
                  class="forgot-input"
                />
                <button
                  @click="forgotPasswordModal.showPassword = !forgotPasswordModal.showPassword"
                  type="button"
                  class="password-toggle"
                >
                  <i :class="forgotPasswordModal.showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                </button>
              </div>
            </div>

            <div class="form-group">
              <label>Confirm Password</label>
              <input
                v-model="forgotPasswordModal.passwordConfirm"
                type="password"
                placeholder="Confirm password"
                class="forgot-input"
              />
            </div>

            <p v-if="forgotPasswordModal.error" class="error-text">{{ forgotPasswordModal.error }}</p>

            <button 
              @click="resetPassword" 
              :disabled="forgotPasswordModal.loading || !forgotPasswordModal.code || !forgotPasswordModal.password"
              class="forgot-button primary"
            >
              <span v-if="forgotPasswordModal.loading" class="btn-spinner"></span>
              {{ forgotPasswordModal.loading ? 'Resetting...' : 'Reset Password' }}
            </button>

            <p class="forgot-footer">
              <a href="#" @click.prevent="resetForgotModal" class="back-link">Send another code?</a>
            </p>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { authAPI, handleAPIError } from '../../services/api.js'
import axios from 'axios'



const router = useRouter()

// Reactive state
const isLoading = ref(false)
const showPassword = ref(false)

const forgotPasswordModal = reactive({
  active: false,
  step: 'request', // 'request' | 'verify'
  email: '',
  code: '',
  password: '',
  passwordConfirm: '',
  loading: false,
  error: '',
  destination: '',
  showPassword: false
})

const otpStep = reactive({
  active: false,
  email: '',
  destination: '',
  expiresIn: 600,
  code: '',
  error: '',
  isVerifying: false,
  resendCount: 0,
  remainingAttempts: 3,
  isResending: false,
  isSignup: false // New flag to distinguish signup from login
})

const pendingSession = reactive({
  userData: null,
  role: '',
  redirectPath: '/'
})

const loginForm = reactive({
  email: '',
  password: '',
  rememberMe: false
})

const errors = reactive({
  email: '',
  password: '',
  general: ''
})

const alert = reactive({
  show: false,
  type: 'info',
  message: '',
  details: ''
})

// Methods
const clearErrors = () => {
  errors.email = ''
  errors.password = ''
  errors.general = ''
}

const showAlert = (type, message, details = '') => {
  alert.show = true
  alert.type = type
  alert.message = message
  alert.details = details
  
  // Auto-hide alerts after 5 seconds for errors, 3 seconds for others
  const duration = type === 'error' ? 5000 : 3000
  setTimeout(() => {
    alert.show = false
  }, duration)
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

const validateForm = () => {
  clearErrors()
  let isValid = true

  if (!loginForm.email) {
    errors.email = 'Email is required'
    isValid = false
  } else if (!/\S+@\S+\.\S+/.test(loginForm.email)) {
    errors.email = 'Please enter a valid email address'
    isValid = false
  }

  if (!loginForm.password) {
    errors.password = 'Password is required'
    isValid = false
  } else if (loginForm.password.length < 6) {
    errors.password = 'Password must be at least 6 characters'
    isValid = false
  }

  return isValid
}

const setAuthenticationState = (user, token) => {
  localStorage.setItem('authToken', token)
  localStorage.setItem('isLoggedIn', 'true')
  localStorage.setItem('userData', JSON.stringify(user))
  
  if (loginForm.rememberMe) {
    localStorage.setItem('rememberMe', 'true')
  }
  
  const expiryTime = new Date().getTime() + (24 * 60 * 60 * 1000)
  localStorage.setItem('sessionExpiry', expiryTime.toString())
  
  console.log('Authentication state set:', {
    token,
    user: user.name,
    role: user.role.name,
    isLoggedIn: localStorage.getItem('isLoggedIn')
  })
}

const getRoleBasedRedirect = (role) => {
  const roleName = role.toLowerCase()
  
  const roleRedirects = {
    'superuser': '/super-user',
    'admin': '/',
    'cashier': '/'
  }
  
  return roleRedirects[roleName] || '/'
}

const authenticateUser = async (email, password) => {
  try {
    const result = await authAPI.login(email, password)
    
    if (result.success) {
      return result
    } else {
      return {
        success: false,
        message: result.message || 'Login failed',
        status: result.status
      }
    }
  } catch (error) {
    console.error('Authentication error:', error)
    return {
      success: false,
      message: 'Network error. Please check your connection and try again.'
    }
  }
}

const handleLogin = async () => {
  if (!validateForm()) {
    return
  }

  isLoading.value = true
  clearErrors()

  try {
    const res = await fetch('http://localhost:8000/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        email: loginForm.email,
        password: loginForm.password
      })
    })

    let data = null
    try {
      data = await res.json()
    } catch (parseErr) {
      console.error('Failed to parse login response as JSON', parseErr)
      showAlert('error', 'Unexpected server response.', 'Please check your connection and try again.')
      return
    }

    if (res.ok && data.user) {
      startOtpFlow(data)
    } else {
      // Handle specific error scenarios
      if (res.status === 401) {
        if (data.error === 'User not found.') {
          showAlert(
            'error',
            'User not found.',
            `No account exists for "${loginForm.email}". Please check the email or try demo credentials below.`
          )
        } else if (data.error === 'Password is incorrect.') {
          showAlert('error', 'Invalid password.', 'The password you entered is incorrect. Please try again.')
        } else {
          showAlert('error', 'Login failed.', data.error || 'Invalid credentials.')
        }
      } else if (res.status === 403) {
        showAlert(
          'warning',
          'Account not verified.',
          'Your account is pending verification. Please contact the administrator.'
        )
      } else if (res.status === 422) {
        showAlert('error', 'Validation error.', 'Please fill in all required fields.')
      } else {
        showAlert('error', 'Login failed.', data.error || data.message || 'An error occurred.')
      }
      console.warn('Login failed:', data)
    }
  } catch (error) {
    console.error('Login error:', error)
    showAlert(
      'error',
      'Connection error.',
      'Unable to reach the server. Please check your internet connection and try again.'
    )
  } finally {
    isLoading.value = false
  }
}

const startOtpFlow = (data) => {
  const role = data.role || (data.user?.role?.name || '')
  pendingSession.userData = {
    id: data.user.id,
    name: data.user.name,
    email: data.user.email,
    company_id: data.user.company_id,
    role_id: data.user.role_id,
    verified: data.user.verified,
    must_change_password: data.user.must_change_password,
    role: data.user.role,
    company: data.user.company
  }
  pendingSession.role = role
  pendingSession.redirectPath = getRoleBasedRedirect(role)

  otpStep.active = true
  otpStep.email = data.user.email
  otpStep.destination = data?.otp?.destination || 'your email'
  otpStep.expiresIn = data?.otp?.expires_in || 600
  otpStep.code = ''
  otpStep.error = ''
  otpStep.resendCount = 0
  otpStep.remainingAttempts = 3
  otpStep.isSignup = data?.is_signup || false // Set signup flag from response

  // Different messages for signup vs login
  if (otpStep.isSignup) {
    const otpMessage = data?.otp?.sent
      ? `We sent a verification code to ${otpStep.destination}. Please check your email to activate your account.`
      : 'Enter the verification code to activate your account.'
    showAlert('success', 'Account Created! Verify Email', otpMessage)
  } else {
    const otpMessage = data?.otp?.sent
      ? `An OTP has been sent to ${otpStep.destination}.`
      : 'Enter the OTP to continue.'
    showAlert('success', 'Login successful!', otpMessage)
  }
}

const verifyOtp = async () => {
  if (!otpStep.code.trim()) {
    otpStep.error = 'Please enter the code sent to your email.'
    return
  }

  otpStep.isVerifying = true
  otpStep.error = ''

  try {
    const res = await fetch('http://localhost:8000/login/verify-otp', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        email: otpStep.email,
        code: otpStep.code.trim()
      })
    })

    let data = null
    try {
      data = await res.json()
    } catch (parseErr) {
      console.error('Failed to parse OTP verify response', parseErr)
      otpStep.error = 'Unexpected server response.'
      return
    }

    if (res.ok && data.user) {
      const token = data.token || data.plainTextToken || ''
      if (token) {
        localStorage.setItem('authToken', token)
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
      }

      localStorage.setItem('isLoggedIn', 'true')
      localStorage.setItem('userData', JSON.stringify(data.user))
      localStorage.setItem('user', JSON.stringify(data.user))

      if (loginForm.rememberMe) {
        localStorage.setItem('rememberMe', 'true')
        localStorage.setItem('rememberedUser', loginForm.email)
      } else {
        localStorage.removeItem('rememberMe')
        localStorage.removeItem('rememberedUser')
      }

      otpStep.active = false
      showAlert('success', 'OTP verified!', 'Redirecting to your dashboard...')

      setTimeout(() => {
        router.replace(pendingSession.redirectPath)
      }, 800)
    } else {
      otpStep.error = data?.error || 'Invalid or expired code. Please try again.'
    }
  } catch (error) {
    console.error('OTP verification error:', error)
    otpStep.error = 'Unable to verify code. Check your connection and try again.'
  } finally {
    otpStep.isVerifying = false
  }
}

const resendOtp = async () => {
  otpStep.isResending = true
  otpStep.error = ''

  try {
    const res = await fetch('http://localhost:8000/login/resend-otp', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        email: otpStep.email,
        is_signup: otpStep.isSignup // Include signup flag
      })
    })

    let data = null
    try {
      data = await res.json()
    } catch (parseErr) {
      console.error('Failed to parse resend response', parseErr)
      otpStep.error = 'Unexpected server response.'
      return
    }

    if (res.ok) {
      otpStep.resendCount = data.resend_count || 0
      otpStep.remainingAttempts = data.remaining_attempts || 0
      otpStep.code = ''
      
      const message = otpStep.isSignup 
        ? `Verification code resent to ${data.destination || 'your email'}.`
        : `New code sent to ${data.destination || 'your email'}.`
      
      showAlert('success', 'Code Resent!', message)
    } else {
      if (data?.limit_reached) {
        otpStep.error = 'Resend limit reached (3 attempts). Please contact support to reset your limit.'
      } else {
        otpStep.error = data?.error || 'Failed to resend code. Try again later.'
      }
    }
  } catch (error) {
    console.error('Resend OTP error:', error)
    otpStep.error = 'Unable to resend code. Check your connection and try again.'
  } finally {
    otpStep.isResending = false
  }
}

// Forgot Password Functions
const openForgotPasswordModal = () => {
  forgotPasswordModal.active = true
  forgotPasswordModal.step = 'request'
  forgotPasswordModal.email = ''
  forgotPasswordModal.code = ''
  forgotPasswordModal.password = ''
  forgotPasswordModal.passwordConfirm = ''
  forgotPasswordModal.error = ''
}

const closeForgotPasswordModal = () => {
  forgotPasswordModal.active = false
  forgotPasswordModal.error = ''
}

const resetForgotModal = () => {
  forgotPasswordModal.step = 'request'
  forgotPasswordModal.code = ''
  forgotPasswordModal.password = ''
  forgotPasswordModal.passwordConfirm = ''
  forgotPasswordModal.error = ''
}

const requestPasswordReset = async () => {
  if (!forgotPasswordModal.email) {
    forgotPasswordModal.error = 'Please enter your email address'
    return
  }

  forgotPasswordModal.loading = true
  forgotPasswordModal.error = ''

  try {
    const res = await fetch('http://localhost:8000/forgot-password', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        email: forgotPasswordModal.email
      })
    })

    let data = null
    try {
      data = await res.json()
    } catch (parseErr) {
      console.error('Failed to parse forgot password response', parseErr)
      forgotPasswordModal.error = 'Unexpected server response.'
      return
    }

    if (res.ok && data.sent) {
      forgotPasswordModal.step = 'verify'
      forgotPasswordModal.destination = data.destination || 'your email'
      showAlert('success', 'Reset Code Sent', `Check your email for the reset code.`)
    } else {
      forgotPasswordModal.error = data?.error || 'Failed to send reset code. Try again later.'
    }
  } catch (error) {
    console.error('Forgot password error:', error)
    forgotPasswordModal.error = 'Unable to send reset code. Check your connection and try again.'
  } finally {
    forgotPasswordModal.loading = false
  }
}

const resetPassword = async () => {
  if (!forgotPasswordModal.code) {
    forgotPasswordModal.error = 'Please enter the reset code'
    return
  }

  if (!forgotPasswordModal.password) {
    forgotPasswordModal.error = 'Please enter a new password'
    return
  }

  if (forgotPasswordModal.password.length < 8) {
    forgotPasswordModal.error = 'Password must be at least 8 characters'
    return
  }

  if (forgotPasswordModal.password !== forgotPasswordModal.passwordConfirm) {
    forgotPasswordModal.error = 'Passwords do not match'
    return
  }

  forgotPasswordModal.loading = true
  forgotPasswordModal.error = ''

  try {
    const res = await fetch('http://localhost:8000/forgot-password/verify-otp', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        email: forgotPasswordModal.email,
        code: forgotPasswordModal.code.trim(),
        password: forgotPasswordModal.password,
        password_confirmation: forgotPasswordModal.passwordConfirm
      })
    })

    let data = null
    try {
      data = await res.json()
    } catch (parseErr) {
      console.error('Failed to parse reset password response', parseErr)
      forgotPasswordModal.error = 'Unexpected server response.'
      return
    }

    if (res.ok && data.success) {
      showAlert('success', 'Password Reset', 'Your password has been reset. Please login with your new password.')
      closeForgotPasswordModal()
      // Optionally pre-fill the login form
      loginForm.email = forgotPasswordModal.email
      loginForm.password = ''
    } else {
      if (res.status === 410) {
        forgotPasswordModal.error = 'Reset code expired. Please request a new one.'
      } else if (res.status === 422) {
        forgotPasswordModal.error = 'Invalid reset code. Please try again.'
      } else {
        forgotPasswordModal.error = data?.error || 'Failed to reset password. Try again later.'
      }
    }
  } catch (error) {
    console.error('Reset password error:', error)
    forgotPasswordModal.error = 'Unable to reset password. Check your connection and try again.'
  } finally {
    forgotPasswordModal.loading = false
  }
}

// Check if user is already logged in
onMounted(() => {
  const isLoggedIn = localStorage.getItem('isLoggedIn') === 'true'
  const userData = localStorage.getItem('userData')
  
  if (isLoggedIn && userData) {
    try {
      const user = JSON.parse(userData)
      const redirectPath = getRoleBasedRedirect(user.role.name)
      
      console.log('User already logged in, redirecting to:', redirectPath)
      router.push(redirectPath)
    } catch (error) {
      console.error('Error parsing stored user data:', error)
      localStorage.removeItem('userData')
      localStorage.removeItem('isLoggedIn')
      localStorage.removeItem('authToken')
    }
  }
})
</script>

<style scoped>
/* Global Styles */
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow: hidden;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}

/* Animated Background */
.animated-background {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, 
    #667eea 0%, 
    #764ba2 25%, 
    #6a5acd 50%, 
    #8a2387 75%, 
    #e94057 100%);
  background-size: 400% 400%;
  animation: gradientShift 15s ease infinite;
  z-index: 1;
}

@keyframes gradientShift {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

.gradient-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
              radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.3) 0%, transparent 50%),
              radial-gradient(circle at 40% 40%, rgba(120, 219, 255, 0.2) 0%, transparent 50%);
  animation: overlayPulse 8s ease-in-out infinite;
}

@keyframes overlayPulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.8; }
}

/* Floating Bubbles */
.bubbles {
  position: absolute;
  width: 100%;
  height: 100%;
  overflow: hidden;
}

.bubble {
  position: absolute;
  bottom: -100px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 50%;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  animation: bubbleFloat 15s infinite linear;
}

.bubble:nth-child(1) { left: 10%; width: 60px; height: 60px; animation-delay: 0s; }
.bubble:nth-child(2) { left: 20%; width: 20px; height: 20px; animation-delay: 2s; }
.bubble:nth-child(3) { left: 25%; width: 40px; height: 40px; animation-delay: 4s; }
.bubble:nth-child(4) { left: 40%; width: 80px; height: 80px; animation-delay: 0s; }
.bubble:nth-child(5) { left: 70%; width: 30px; height: 30px; animation-delay: 1s; }
.bubble:nth-child(6) { left: 80%; width: 50px; height: 50px; animation-delay: 3s; }
.bubble:nth-child(7) { left: 32%; width: 25px; height: 25px; animation-delay: 7s; }
.bubble:nth-child(8) { left: 55%; width: 35px; height: 35px; animation-delay: 15s; }
.bubble:nth-child(9) { left: 25%; width: 45px; height: 45px; animation-delay: 2s; }
.bubble:nth-child(10) { left: 90%; width: 25px; height: 25px; animation-delay: 11s; }
.bubble:nth-child(11) { left: 15%; width: 55px; height: 55px; animation-delay: 13s; }
.bubble:nth-child(12) { left: 60%; width: 30px; height: 30px; animation-delay: 6s; }
.bubble:nth-child(13) { left: 75%; width: 40px; height: 40px; animation-delay: 9s; }
.bubble:nth-child(14) { left: 5%; width: 70px; height: 70px; animation-delay: 12s; }
.bubble:nth-child(15) { left: 85%; width: 35px; height: 35px; animation-delay: 5s; }

.otp-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.55);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 999;
  backdrop-filter: blur(3px);
}

.otp-modal {
  background: #fff;
  padding: 24px;
  border-radius: 16px;
  width: min(420px, 90vw);
  box-shadow: 0 20px 50px rgba(0,0,0,0.2);
  text-align: center;
}

.otp-modal h2 {
  margin: 0 0 8px;
  font-size: 22px;
}

.otp-hint {
  margin: 0 0 16px;
  color: #4b5563;
}

.otp-input {
  width: 100%;
  padding: 14px;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  font-size: 18px;
  letter-spacing: 2px;
  text-align: center;
}

.otp-input:focus {
  border-color: #667eea;
  outline: none;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
}

.otp-error {
  color: #dc2626;
  margin: 10px 0 0;
}

.otp-actions {
  margin-top: 16px;
}

.otp-button {
  width: 100%;
  padding: 12px;
  border: none;
  border-radius: 12px;
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: #fff;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.otp-button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.otp-button:not(:disabled):hover {
  transform: translateY(-1px);
  box-shadow: 0 10px 25px rgba(102, 126, 234, 0.25);
}

.otp-expiry {
  margin-top: 12px;
  color: #6b7280;
  font-size: 14px;
}

.otp-footer {
  margin-top: 12px;
  border-top: 1px solid #e5e7eb;
  padding-top: 12px;
}

.otp-resend-info {
  margin: 8px 0 0;
  color: #6b7280;
  font-size: 14px;
}

.otp-resend-btn {
  background: none;
  border: none;
  color: #667eea;
  text-decoration: underline;
  cursor: pointer;
  font-weight: 600;
  padding: 0 4px;
}

.otp-resend-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.otp-limit-reached {
  margin: 8px 0 0;
  color: #dc2626;
  font-size: 13px;
}


@keyframes bubbleFloat {
  0% {
    bottom: -100px;
    transform: translateX(0px) rotate(0deg);
    opacity: 0;
  }
  10% {
    opacity: 0.6;
  }
  90% {
    opacity: 0.6;
  }
  100% {
    bottom: 110vh;
    transform: translateX(-100px) rotate(600deg);
    opacity: 0;
  }
}

/* Login Container */
.login-container {
  position: relative;
  z-index: 10;
  width: 100%;
  max-width: 440px;
  padding: 20px;
}

.login-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 24px;
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15),
              0 0 0 1px rgba(255, 255, 255, 0.2);
  padding: 48px;
  position: relative;
  overflow: hidden;
  animation: cardSlideIn 0.8s ease-out;
}

@keyframes cardSlideIn {
  0% {
    opacity: 0;
    transform: translateY(30px) scale(0.95);
  }
  100% {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.login-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
}

/* Brand Section */
.brand-section {
  text-align: center;
  margin-bottom: 40px;
}

.brand-logo-wrapper {
  position: relative;
  display: inline-block;
  margin-bottom: 24px;
}

.brand-logo {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  border-radius: 50%;
  font-size: 32px;
  box-shadow: 0 8px 32px rgba(102, 126, 234, 0.4);
  animation: logoFloat 3s ease-in-out infinite;
}

@keyframes logoFloat {
  0%, 100% { transform: translateY(0px) rotate(0deg); }
  50% { transform: translateY(-10px) rotate(180deg); }
}

.logo-pulse {
  position: absolute;
  top: -10px;
  left: -10px;
  right: -10px;
  bottom: -10px;
  border-radius: 50%;
  border: 2px solid #667eea;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0% { transform: scale(1); opacity: 0.8; }
  50% { transform: scale(1.1); opacity: 0.4; }
  100% { transform: scale(1.2); opacity: 0; }
}

.login-title {
  font-size: 36px;
  font-weight: 800;
  margin: 0 0 8px 0;
  background: linear-gradient(135deg, #667eea, #764ba2);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.title-text {
  display: inline-block;
  animation: titleSlide 0.8s ease-out 0.2s both;
}

.title-accent {
  display: inline-block;
  animation: titleSlide 0.8s ease-out 0.4s both;
}

@keyframes titleSlide {
  0% { opacity: 0; transform: translateX(-20px); }
  100% { opacity: 1; transform: translateX(0); }
}

.login-subtitle {
  color: #6b7280;
  font-size: 16px;
  font-weight: 500;
  margin: 0;
  animation: subtitleFade 0.8s ease-out 0.6s both;
}

@keyframes subtitleFade {
  0% { opacity: 0; transform: translateY(10px); }
  100% { opacity: 1; transform: translateY(0); }
}

/* Form Styles */
.login-form {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon {
  position: absolute;
  left: 16px;
  color: #9ca3af;
  font-size: 16px;
  z-index: 3;
  transition: all 0.3s ease;
}

.form-input {
  width: 100%;
  padding: 16px 48px 16px 48px;
  border: 2px solid #e5e7eb;
  border-radius: 16px;
  font-size: 16px;
  background: rgba(255, 255, 255, 0.8);
  transition: all 0.3s ease;
  outline: none;
  position: relative;
  z-index: 2;
}

.form-input::placeholder {
  color: transparent;
}

.floating-label {
  position: absolute;
  left: 48px;
  top: 50%;
  transform: translateY(-50%);
  color: #9ca3af;
  font-size: 16px;
  pointer-events: none;
  transition: all 0.3s ease;
  z-index: 1;
  background: rgba(255, 255, 255, 0.9);
  padding: 0 8px;
  max-width: calc(100% - 96px);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  transform-origin: left center;
  opacity: 1;
}

.form-input:focus + .floating-label,
.form-input.filled + .floating-label,
.floating-label.floating {
  top: -12px;
  transform: translateY(-50%) scale(0.75);
  color: #667eea;
  font-weight: 600;
  background: #fff;
  z-index: 4;
}

.form-input:focus,
.form-input.filled,
.form-input.active {
  border-color: #667eea;
  background: rgba(255, 255, 255, 0.95);
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
}

.form-input:focus + .floating-label,
.form-input.filled + .floating-label,
.form-input.active + .floating-label {
  transform: translateY(-12px) scale(0.75);
  color: #667eea;
  font-weight: 600;
}

.form-input:focus ~ .input-icon {
  color: #667eea;
  transform: scale(1.1);
}

.input-line {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: linear-gradient(135deg, #667eea, #764ba2);
  transform: scaleX(0);
  transition: transform 0.3s ease;
  z-index: 1;
}

.form-input:focus ~ .input-line {
  transform: scaleX(1);
}

.password-toggle {
  position: absolute;
  right: 16px;
  background: none;
  border: none;
  color: #9ca3af;
  cursor: pointer;
  padding: 8px;
  border-radius: 8px;
  transition: all 0.3s ease;
  z-index: 3;
}

.password-toggle:hover {
  color: #667eea;
  background: rgba(102, 126, 234, 0.1);
}

/* Error Styles */
.form-input.error {
  border-color: #ef4444;
  background: rgba(239, 68, 68, 0.05);
}

.error-text {
  color: #ef4444;
  font-size: 14px;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 6px;
  margin-left: 4px;
}

.error-slide-enter-active,
.error-slide-leave-active {
  transition: all 0.3s ease;
}

.error-slide-enter-from {
  opacity: 0;
  transform: translateY(-10px);
}

.error-slide-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

/* Form Options */
.form-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin: 8px 0;
}

.checkbox-wrapper {
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  user-select: none;
}

.checkbox-input {
  display: none;
}

.checkbox-custom {
  position: relative;
  width: 20px;
  height: 20px;
  border: 2px solid #d1d5db;
  border-radius: 6px;
  background: white;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.checkbox-input:checked + .checkbox-custom {
  background: linear-gradient(135deg, #667eea, #764ba2);
  border-color: #667eea;
  transform: scale(1.1);
}

.checkbox-icon {
  color: white;
  font-size: 12px;
  opacity: 0;
  transform: scale(0);
  transition: all 0.2s ease;
}

.checkbox-input:checked + .checkbox-custom .checkbox-icon {
  opacity: 1;
  transform: scale(1);
}

.checkbox-text {
  color: #6b7280;
  font-size: 14px;
  font-weight: 500;
}

.forgot-link {
  color: #667eea;
  text-decoration: none;
  font-size: 14px;
  font-weight: 600;
  transition: all 0.3s ease;
}

.forgot-link:hover {
  color: #5a67d8;
  transform: translateX(2px);
}

/* Login Button */
.login-button {
  position: relative;
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  border: none;
  padding: 16px 32px;
  border-radius: 16px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  margin-top: 8px;
  overflow: hidden;
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.login-button:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
}

.login-button:active {
  transform: translateY(0);
}

.login-button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none !important;
}

.button-content {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  position: relative;
  z-index: 2;
}

.loading-spinner {
  width: 20px;
  height: 20px;
}

.spinner-ring {
  width: 20px;
  height: 20px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top: 2px solid white;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.button-ripple {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.3) 0%, transparent 70%);
  opacity: 0;
  transform: scale(0);
  transition: all 0.6s ease;
}

.login-button:active .button-ripple {
  opacity: 1;
  transform: scale(1);
}

/* Divider */
.divider {
  margin: 32px 0;
  position: relative;
  text-align: center;
}

.divider::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 0;
  right: 0;
  height: 1px;
  background: linear-gradient(90deg, transparent, #e5e7eb, transparent);
}

.divider-text {
  background: rgba(255, 255, 255, 0.95);
  color: #9ca3af;
  padding: 0 20px;
  font-size: 14px;
  font-weight: 500;
}

/* Social Login */
.social-login {
  display: flex;
  justify-content: center;
  gap: 16px;
  margin-bottom: 32px;
}

.social-button {
  width: 48px;
  height: 48px;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.8);
  color: #6b7280;
  cursor: pointer;
  font-size: 18px;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.social-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.social-button.google:hover {
  border-color: #ea4335;
  color: #ea4335;
}

.social-button.microsoft:hover {
  border-color: #00a1f1;
  color: #00a1f1;
}

.social-button.apple:hover {
  border-color: #000;
  color: #000;
}

/* Footer */
.login-footer {
  text-align: center;
}

.footer-text {
  color: #6b7280;
  font-size: 14px;
  margin: 0;
}

.signup-link {
  color: #667eea;
  text-decoration: none;
  font-weight: 600;
  transition: all 0.3s ease;
}

.signup-link:hover {
  color: #5a67d8;
  text-decoration: underline;
}

/* Alert Styles */
.alert {
  position: fixed;
  top: 20px;
  left: 50%;
  transform: translateX(-50%);
  min-width: 300px;
  max-width: 90%;
  padding: 16px 24px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
  z-index: 100;
}
.alert.success {
  background: #d1fae5;
  border: 1px solid #10b981;
  color: #065f46;
}
.alert.error {
  background: #fee2e2;
  border: 1px solid #ef4444;
  color: #991b1b;
}

.alert.warning {
  background: #fef3c7;
  border: 1px solid #f59e0b;
  color: #78350f;
}
.alert.info {
  background: #dbeafe;
  border: 1px solid #3b82f6;
  color: #1e40af;
}
.alert-content {
  display: flex;
  align-items: center;
  gap: 12px;
}
.alert-icon {
  font-size: 20px;
}
.alert-message {
  font-size: 14px;
  font-weight: 500;
}
.alert-close {
  background: none;
  border: none;
  color: inherit;
  cursor: pointer;
  font-size: 16px;
  padding: 8px;
  border-radius: 8px;
  transition: all 0.3s ease;
}

/* Enhanced Alert Styles */
.login-alert {
  position: fixed;
  top: 20px;
  left: 50%;
  transform: translateX(-50%);
  min-width: 320px;
  max-width: calc(100% - 40px);
  padding: 16px 20px;
  border-radius: 12px;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
  z-index: 1000;
  animation: alertSlide 0.3s ease-out;
}

@keyframes alertSlide {
  from {
    opacity: 0;
    transform: translateX(-50%) translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
  }
}

.login-alert.success {
  background: #d1fae5;
  border: 1px solid #10b981;
  color: #065f46;
}

.login-alert.error {
  background: #fee2e2;
  border: 1px solid #ef4444;
  color: #991b1b;
}

.login-alert.warning {
  background: #fef3c7;
  border: 1px solid #f59e0b;
  color: #78350f;
}

.login-alert.info {
  background: #dbeafe;
  border: 1px solid #3b82f6;
  color: #1e40af;
}

.alert-content {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  flex: 1;
}

.alert-icon {
  font-size: 20px;
  flex-shrink: 0;
  margin-top: 2px;
}

.alert-message-wrapper {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.alert-message {
  font-size: 15px;
  font-weight: 600;
}

.alert-details {
  font-size: 13px;
  font-weight: 400;
  opacity: 0.9;
  line-height: 1.4;
}

.alert-close {
  background: none;
  border: none;
  color: inherit;
  cursor: pointer;
  font-size: 18px;
  padding: 4px 8px;
  border-radius: 6px;
  transition: all 0.2s ease;
  flex-shrink: 0;
  opacity: 0.7;
}

.alert-close:hover {
  opacity: 1;
  background: rgba(0, 0, 0, 0.1);
}

.alert-slide-enter-active,
.alert-slide-leave-active {
  transition: all 0.3s ease;
}

.alert-slide-enter-from {
  opacity: 0;
  transform: translateX(-50%) translateY(-20px);
}

.alert-slide-leave-to {
  opacity: 0;
  transform: translateX(-50%) translateY(-20px);
}

/* Responsive Design */
@media (max-width: 767px) {
  .login-card {
    padding: 24px;
  }

  .brand-logo {
    width: 60px;
    height: 60px;
    font-size: 24px;
  }

  .login-title {
    font-size: 24px;
  }

  .login-subtitle {
    font-size: 14px;
  }

  .form-input {
    padding: 12px 40px 12px 40px;
  }

  .input-icon {
    left: 12px;
  }

  .floating-label {
    left: 40px;
    max-width: calc(100% - 80px);
  }

  .password-toggle {
    right: 12px;
  }

  .login-button {
    padding: 12px 24px;
  }

  .social-login {
    gap: 12px;
  }

  .divider {
    margin: 24px 0;
  }

  .version-info {
    font-size: 12px;
  }
}

/* Forgot Password Modal Styles */
.forgot-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.forgot-modal {
  background: white;
  border-radius: 20px;
  padding: 40px;
  max-width: 420px;
  width: 100%;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  position: relative;
  animation: slideUp 0.3s ease;
}

.forgot-modal h2 {
  font-size: 24px;
  font-weight: 700;
  margin: 0 0 12px 0;
  color: #1f2937;
}

.forgot-description {
  color: #6b7280;
  font-size: 14px;
  margin-bottom: 24px;
  line-height: 1.5;
}

.forgot-close {
  position: absolute;
  top: 20px;
  right: 20px;
  background: none;
  border: none;
  font-size: 20px;
  color: #9ca3af;
  cursor: pointer;
  transition: color 0.2s;
}

.forgot-close:hover {
  color: #1f2937;
}

.forgot-input {
  width: 100%;
  padding: 12px 16px;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  font-size: 14px;
  background: #f9fafb;
  transition: all 0.2s;
}

.forgot-input:focus {
  outline: none;
  border-color: #3b82f6;
  background: white;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-group {
  margin-bottom: 16px;
}

.form-group label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: #374151;
  margin-bottom: 6px;
}

.password-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.password-wrapper input {
  width: 100%;
  padding-right: 40px;
}

.password-toggle {
  position: absolute;
  right: 12px;
  background: none;
  border: none;
  color: #9ca3af;
  cursor: pointer;
  font-size: 16px;
  transition: color 0.2s;
}

.password-toggle:hover {
  color: #3b82f6;
}

.error-text {
  color: #dc2626;
  font-size: 13px;
  margin-bottom: 16px;
  padding: 10px 12px;
  background: #fee2e2;
  border-radius: 8px;
}

.forgot-button {
  width: 100%;
  padding: 14px 16px;
  border: none;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.forgot-button.primary {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
  margin-bottom: 16px;
}

.forgot-button.primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
}

.forgot-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-spinner {
  display: inline-block;
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

.forgot-footer {
  text-align: center;
  font-size: 13px;
  color: #6b7280;
  margin-top: 16px;
}

.back-link {
  color: #3b82f6;
  text-decoration: none;
  font-weight: 600;
  transition: color 0.2s;
}

.back-link:hover {
  color: #2563eb;
}

@keyframes slideUp {
  from {
    transform: translateY(20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 640px) {
  .forgot-modal {
    padding: 24px;
    max-width: calc(100% - 32px);
  }

  .forgot-modal h2 {
    font-size: 20px;
  }
}
</style>

