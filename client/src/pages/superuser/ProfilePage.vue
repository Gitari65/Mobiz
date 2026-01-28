<template>
  <div class="profile-page">
    <!-- Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-title">
          <i class="fas fa-user-circle"></i>
          <div>
            <h1>My Profile</h1>
            <p class="subtitle">Manage your personal information and account settings</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Profile Content -->
    <div class="profile-content">
      <!-- Profile Picture Section -->
      <div class="profile-card profile-picture-card">
        <div class="card-header">
          <h2><i class="fas fa-camera"></i> Profile Picture</h2>
        </div>
        <div class="card-body">
          <!-- Alert for Profile Picture -->
          <div v-if="alerts.picture.show" class="alert" :class="`alert-${alerts.picture.type}`">
            <i :class="getAlertIcon(alerts.picture.type)"></i>
            <span>{{ alerts.picture.message }}</span>
            <button @click="dismissAlert('picture')" class="alert-close">
              <i class="fas fa-times"></i>
            </button>
          </div>

          <div class="profile-picture-container">
            <div class="profile-picture-preview">
              <img 
                v-if="profileData.profile_picture" 
                :src="getProfilePictureUrl(profileData.profile_picture)" 
                alt="Profile Picture"
                @error="handleImageError"
              />
              <div v-else class="profile-picture-placeholder">
                <i class="fas fa-user"></i>
              </div>
            </div>
            <div class="profile-picture-actions">
              <input 
                type="file" 
                ref="fileInput" 
                @change="handleFileSelect" 
                accept="image/*" 
                style="display: none;"
              />
              <button @click="$refs.fileInput.click()" class="btn btn-primary">
                <i class="fas fa-upload"></i> Choose Photo
              </button>
              <button 
                v-if="profileData.profile_picture" 
                @click="removeProfilePicture" 
                class="btn btn-danger"
                :disabled="isLoading"
              >
                <i class="fas fa-trash"></i> Remove
              </button>
              <button 
                v-if="selectedFile" 
                @click="uploadProfilePicture" 
                class="btn btn-success"
                :disabled="isLoading"
              >
                <i class="fas fa-check"></i> Upload
              </button>
            </div>
            <p class="help-text">JPG, PNG or GIF. Max size 2MB</p>
          </div>
        </div>
      </div>

      <!-- Personal Information Section -->
      <div class="profile-card">
        <div class="card-header">
          <h2><i class="fas fa-id-card"></i> Personal Information</h2>
          <button 
            v-if="!isEditingPersonal" 
            @click="startEditingPersonal" 
            class="btn btn-secondary btn-sm"
          >
            <i class="fas fa-edit"></i> Edit
          </button>
        </div>
        <div class="card-body">
          <!-- Alert for Personal Info Section -->
          <div v-if="alerts.personal.show" class="alert" :class="`alert-${alerts.personal.type}`">
            <i :class="getAlertIcon(alerts.personal.type)"></i>
            <span>{{ alerts.personal.message }}</span>
            <button @click="dismissAlert('personal')" class="alert-close">
              <i class="fas fa-times"></i>
            </button>
          </div>

          <div v-if="!isEditingPersonal" class="profile-view">
            <div class="info-row">
              <label>Name</label>
              <span>{{ profileData.name || 'Not set' }}</span>
            </div>
            <div class="info-row">
              <label>Email</label>
              <span>{{ profileData.email || 'Not set' }}</span>
            </div>
            <div class="info-row">
              <label>Phone</label>
              <span>{{ profileData.phone || 'Not set' }}</span>
            </div>
            <div class="info-row">
              <label>Role</label>
              <span class="role-badge">{{ getRoleLabel(profileData.role?.name) }}</span>
            </div>
            <div class="info-row">
              <label>Bio</label>
              <span class="bio-text">{{ profileData.bio || 'Not set' }}</span>
            </div>
          </div>

          <form v-else @submit.prevent="updatePersonalInfo" class="profile-form">
            <div class="form-group">
              <label for="name">Name *</label>
              <input 
                type="text" 
                id="name" 
                v-model="editForm.name" 
                required
                :disabled="isLoading"
              />
            </div>
            <div class="form-group">
              <label for="email">Email *</label>
              <input 
                type="email" 
                id="email" 
                v-model="editForm.email" 
                required
                :disabled="isLoading"
              />
            </div>
            <div class="form-group">
              <label for="phone">Phone</label>
              <input 
                type="tel" 
                id="phone" 
                v-model="editForm.phone"
                :disabled="isLoading"
              />
            </div>
            <div class="form-group">
              <label for="bio">Bio</label>
              <textarea 
                id="bio" 
                v-model="editForm.bio" 
                rows="4"
                :disabled="isLoading"
              ></textarea>
            </div>
            <div class="form-actions">
              <button type="submit" class="btn btn-primary" :disabled="isLoading">
                <i class="fas fa-save"></i> Save Changes
              </button>
              <button 
                type="button" 
                @click="cancelEditingPersonal" 
                class="btn btn-secondary"
                :disabled="isLoading"
              >
                <i class="fas fa-times"></i> Cancel
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Password Change Section -->
      <div class="profile-card">
        <div class="card-header">
          <h2><i class="fas fa-lock"></i> Change Password</h2>
        </div>
        <div class="card-body">
          <!-- Alert for Password Change Section -->
          <div v-if="alerts.password.show" class="alert" :class="`alert-${alerts.password.type}`">
            <i :class="getAlertIcon(alerts.password.type)"></i>
            <span>{{ alerts.password.message }}</span>
            <button @click="dismissAlert('password')" class="alert-close">
              <i class="fas fa-times"></i>
            </button>
          </div>

          <form @submit.prevent="changePassword" class="profile-form">
            <div class="form-group">
              <label for="current_password">Current Password *</label>
              <div class="password-input">
                <input 
                  :type="showCurrentPassword ? 'text' : 'password'" 
                  id="current_password" 
                  v-model="passwordForm.current_password" 
                  required
                  :disabled="isLoading || passwordProgress.active"
                />
                <button 
                  type="button" 
                  @click="showCurrentPassword = !showCurrentPassword"
                  class="password-toggle"
                >
                  <i :class="showCurrentPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                </button>
              </div>
            </div>
            <div class="form-group">
              <label for="new_password">New Password *</label>
              <div class="password-input">
                <input 
                  :type="showNewPassword ? 'text' : 'password'" 
                  id="new_password" 
                  v-model="passwordForm.new_password" 
                  required
                  minlength="8"
                  :disabled="isLoading || passwordProgress.active"
                />
                <button 
                  type="button" 
                  @click="showNewPassword = !showNewPassword"
                  class="password-toggle"
                >
                  <i :class="showNewPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                </button>
              </div>
              <p class="help-text">Minimum 8 characters</p>
            </div>
            <div class="form-group">
              <label for="confirm_password">Confirm New Password *</label>
              <div class="password-input">
                <input 
                  :type="showConfirmPassword ? 'text' : 'password'" 
                  id="confirm_password" 
                  v-model="passwordForm.confirm_password" 
                  required
                  :disabled="isLoading || passwordProgress.active"
                />
                <button 
                  type="button" 
                  @click="showConfirmPassword = !showConfirmPassword"
                  class="password-toggle"
                >
                  <i :class="showConfirmPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                </button>
              </div>
            </div>

            <!-- Progress Indicator -->
            <div v-if="passwordProgress.active" class="progress-container">
              <div class="progress-bar">
                <div class="progress-fill" :style="{ width: passwordProgress.percentage + '%' }"></div>
              </div>
              <p class="progress-text">
                <i class="fas fa-spinner fa-spin"></i> {{ passwordProgress.message }}
              </p>
            </div>

            <div class="form-actions">
              <button type="submit" class="btn btn-primary" :disabled="isLoading || passwordProgress.active">
                <i :class="passwordProgress.active ? 'fas fa-spinner fa-spin' : 'fas fa-key'"></i> 
                {{ passwordProgress.active ? 'Updating...' : 'Update Password' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Password Reset via Email Section -->
      <div class="profile-card">
        <div class="card-header">
          <h2><i class="fas fa-envelope"></i> Password Reset via Email</h2>
        </div>
        <div class="card-body">
          <!-- Alert for Password Reset Section -->
          <div v-if="alerts.reset.show" class="alert" :class="`alert-${alerts.reset.type}`">
            <i :class="getAlertIcon(alerts.reset.type)"></i>
            <span>{{ alerts.reset.message }}</span>
            <button @click="dismissAlert('reset')" class="alert-close">
              <i class="fas fa-times"></i>
            </button>
          </div>

          <!-- Progress Bar -->
          <div v-if="resetProgress.active" class="progress-container">
            <div class="progress-bar">
              <div class="progress-fill" :style="{ width: resetProgress.percentage + '%' }"></div>
            </div>
            <p class="progress-text">
              <i class="fas fa-spinner fa-spin"></i> {{ resetProgress.message }}
            </p>
          </div>

          <p class="info-text">
            Click the button below to receive a new temporary password via email. 
            You will need to change this temporary password on your next login for security.
          </p>
          <button 
            @click="requestPasswordReset" 
            class="btn btn-warning"
            :disabled="isLoading || resetProgress.active"
          >
            <i :class="resetProgress.active ? 'fas fa-spinner fa-spin' : 'fas fa-paper-plane'"></i> 
            {{ resetProgress.active ? 'Sending...' : 'Send New Password to Email' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Loading Overlay -->
    <div v-if="isLoading" class="loading-overlay">
      <div class="spinner"></div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'

// State
const isLoading = ref(false)
const isEditingPersonal = ref(false)
const selectedFile = ref(null)
const fileInput = ref(null)

// Password visibility toggles
const showCurrentPassword = ref(false)
const showNewPassword = ref(false)
const showConfirmPassword = ref(false)

// Profile data
const profileData = reactive({
  id: null,
  name: '',
  email: '',
  phone: '',
  bio: '',
  profile_picture: '',
  profile_picture_url: '',
  role: null
})

// Edit form
const editForm = reactive({
  name: '',
  email: '',
  phone: '',
  bio: ''
})

// Password form
const passwordForm = reactive({
  current_password: '',
  new_password: '',
  confirm_password: ''
})

// Progress tracking
const passwordProgress = reactive({
  active: false,
  percentage: 0,
  message: ''
})

const resetProgress = reactive({
  active: false,
  percentage: 0,
  message: ''
})

// Alert system - section specific
const alerts = reactive({
  picture: {
    show: false,
    type: 'info',
    message: '',
    timeout: null
  },
  personal: {
    show: false,
    type: 'info',
    message: '',
    timeout: null
  },
  password: {
    show: false,
    type: 'info',
    message: '',
    timeout: null
  },
  reset: {
    show: false,
    type: 'info',
    message: '',
    timeout: null
  }
})

// Methods
const showAlert = (section, type, message, duration = 3000) => {
  if (!alerts[section]) return
  
  alerts[section].show = true
  alerts[section].type = type
  alerts[section].message = message
  if (alerts[section].timeout) clearTimeout(alerts[section].timeout)
  alerts[section].timeout = setTimeout(() => (alerts[section].show = false), duration)
}

const dismissAlert = (section) => {
  if (!alerts[section]) return
  alerts[section].show = false
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

const getRoleLabel = (role) => {
  if (!role) return 'Unknown'
  if (role === 'superuser') return 'Super User'
  return role.charAt(0).toUpperCase() + role.slice(1)
}

const getProfilePictureUrl = (path) => {
  // Prefer server-provided full URL if available
  if (profileData.profile_picture_url) {
    let url = profileData.profile_picture_url
    
    // If the URL is relative (starts with /), prepend base URL
    if (url.startsWith('/')) {
      // Extract domain from baseURL
      const baseUrlObj = new URL(axios.defaults.baseURL || window.location.origin)
      url = baseUrlObj.origin + url
    }
    
    // Add cache buster parameter
    const separator = url.includes('?') ? '&' : '?'
    const finalUrl = `${url}${separator}t=${Date.now()}`
    console.log('Using profile_picture_url:', finalUrl)
    return finalUrl
  }

  if (!path) return ''
  
  // If path is already a full URL, return as is
  if (path.startsWith('http://') || path.startsWith('https://')) {
    const separator = path.includes('?') ? '&' : '?'
    const url = `${path}${separator}t=${Date.now()}`
    console.log('Using full URL path:', url)
    return url
  }
  
  // Handle both 'profile_pictures/filename' and just 'filename' formats
  let storagePath = path
  if (!path.includes('profile_pictures')) {
    storagePath = `profile_pictures/${path}`
  }
  
  // Construct URL to Laravel storage with cache buster
  const baseUrlObj = new URL(axios.defaults.baseURL || window.location.origin)
  const storageUrl = `${baseUrlObj.origin}/storage/${storagePath}?t=${Date.now()}`
  console.log('Using storage URL:', storageUrl)
  return storageUrl
}

const handleImageError = (e) => {
  // Only show error if there's actually an image path set
  if (!profileData.profile_picture && !profileData.profile_picture_url) return
  
  // Don't repeatedly show error for the same missing image
  if (e.target.dataset.errorShown) return
  e.target.dataset.errorShown = 'true'
  
  const failedUrl = e.target.src
  console.error('Image load error:', {
    failedUrl,
    profile_picture: profileData.profile_picture,
    profile_picture_url: profileData.profile_picture_url,
    baseURL: axios.defaults.baseURL,
    timestamp: new Date().toISOString()
  })
  
  // Show placeholder background instead of hiding
  e.target.style.backgroundColor = '#f0f0f0'
  e.target.style.display = 'block'
  e.target.style.opacity = '0.5'
  
  // Show less alarming message
  showAlert('picture', 'info', 'Image could not be loaded. Upload or refresh to reload.', 8000)
}

const loadProfile = async () => {
  try {
    isLoading.value = true
    const response = await axios.get('/api/profile')
    
    // Debug logging
    console.log('Loaded profile data:', response.data)
    console.log('Profile picture from DB:', response.data.user.profile_picture)
    console.log('Profile picture URL from API:', response.data.user.profile_picture_url)
    
    Object.assign(profileData, response.data.user)
    
    // Ensure profile_picture_url is set if profile_picture exists
    if (profileData.profile_picture && !profileData.profile_picture_url) {
      profileData.profile_picture_url = `${axios.defaults.baseURL}/storage/${profileData.profile_picture}`
      console.log('Generated URL from path:', profileData.profile_picture_url)
    }
  } catch (error) {
    console.error('Failed to load profile:', error)
    showAlert('personal', 'error', error.response?.data?.message || 'Failed to load profile')
  } finally {
    isLoading.value = false
  }
}

const startEditingPersonal = () => {
  editForm.name = profileData.name
  editForm.email = profileData.email
  editForm.phone = profileData.phone || ''
  editForm.bio = profileData.bio || ''
  isEditingPersonal.value = true
}

const cancelEditingPersonal = () => {
  isEditingPersonal.value = false
}

const updatePersonalInfo = async () => {
  try {
    isLoading.value = true
    const response = await axios.put('/api/profile/update', editForm)
    Object.assign(profileData, response.data.user)
    
    // Update localStorage
    const userData = JSON.parse(localStorage.getItem('userData') || '{}')
    userData.name = response.data.user.name
    userData.email = response.data.user.email
    localStorage.setItem('userData', JSON.stringify(userData))
    
    showAlert('personal', 'success', 'Profile updated successfully')
    isEditingPersonal.value = false
  } catch (error) {
    console.error('Failed to update profile:', error)
    showAlert('personal', 'error', error.response?.data?.message || 'Failed to update profile')
  } finally {
    isLoading.value = false
  }
}

const handleFileSelect = (event) => {
  const file = event.target.files[0]
  if (!file) return

  // Validate file type
  if (!file.type.startsWith('image/')) {
    showAlert('picture', 'error', 'Please select an image file')
    return
  }

  // Validate file size (2MB max)
  if (file.size > 2 * 1024 * 1024) {
    showAlert('picture', 'error', 'Image size must be less than 2MB')
    return
  }

  selectedFile.value = file
}

const uploadProfilePicture = async () => {
  if (!selectedFile.value) return

  try {
    isLoading.value = true
    const formData = new FormData()
    formData.append('profile_picture', selectedFile.value)

    const response = await axios.post('/api/profile/upload-picture', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    // Force image URL update with new path
    profileData.profile_picture = response.data.profile_picture
    if (response.data.profile_picture_url) {
      profileData.profile_picture_url = response.data.profile_picture_url
    }
    
    // Force re-render by clearing and reassigning
    selectedFile.value = null
    if (fileInput.value) fileInput.value.value = ''
    
    // Small delay to ensure image URL is updated before showing success message
    await new Promise(resolve => setTimeout(resolve, 100))
    
    showAlert('picture', 'success', 'Profile picture updated successfully')
  } catch (error) {
    console.error('Failed to upload profile picture:', error)
    showAlert('picture', 'error', error.response?.data?.message || 'Failed to upload profile picture')
  } finally {
    isLoading.value = false
  }
}

const removeProfilePicture = async () => {
  if (!confirm('Are you sure you want to remove your profile picture?')) return

  try {
    isLoading.value = true
    await axios.delete('/api/profile/remove-picture')
    profileData.profile_picture = ''
    profileData.profile_picture_url = ''
    showAlert('picture', 'success', 'Profile picture removed successfully')
  } catch (error) {
    console.error('Failed to remove profile picture:', error)
    showAlert('picture', 'error', error.response?.data?.message || 'Failed to remove profile picture')
  } finally {
    isLoading.value = false
  }
}

const changePassword = async () => {
  if (passwordForm.new_password !== passwordForm.confirm_password) {
    showAlert('password', 'error', 'New passwords do not match')
    return
  }

  if (passwordForm.new_password.length < 8) {
    showAlert('password', 'error', 'Password must be at least 8 characters long')
    return
  }

  try {
    isLoading.value = true
    passwordProgress.active = true
    
    // Step 1: Validating
    passwordProgress.percentage = 30
    passwordProgress.message = 'Validating current password...'
    await new Promise(resolve => setTimeout(resolve, 300))
    
    // Step 2: Updating
    passwordProgress.percentage = 60
    passwordProgress.message = 'Updating password...'
    
    await axios.put('/api/profile/change-password', {
      current_password: passwordForm.current_password,
      new_password: passwordForm.new_password,
      new_password_confirmation: passwordForm.confirm_password
    })
    
    // Step 3: Complete
    passwordProgress.percentage = 100
    passwordProgress.message = 'Password updated successfully!'
    await new Promise(resolve => setTimeout(resolve, 500))
    
    // Clear form
    passwordForm.current_password = ''
    passwordForm.new_password = ''
    passwordForm.confirm_password = ''
    
    showAlert('password', 'success', 'Password changed successfully! Please use your new password for future logins.', 5000)
  } catch (error) {
    console.error('Failed to change password:', error)
    const errorMsg = error.response?.data?.message || 'Failed to change password. Please check your current password and try again.'
    showAlert('password', 'error', errorMsg, 5000)
  } finally {
    isLoading.value = false
    passwordProgress.active = false
    passwordProgress.percentage = 0
    passwordProgress.message = ''
  }
}

const requestPasswordReset = async () => {
  if (!confirm(`A new temporary password will be sent to ${profileData.email}. You will need to login with it and change your password. Continue?`)) return

  try {
    isLoading.value = true
    resetProgress.active = true
    
    // Step 1: Preparing
    resetProgress.percentage = 25
    resetProgress.message = 'Generating new temporary password...'
    await new Promise(resolve => setTimeout(resolve, 400))
    
    // Step 2: Sending
    resetProgress.percentage = 50
    resetProgress.message = 'Sending email...'
    
    await axios.post('/api/profile/request-password-reset')
    
    // Step 3: Confirming
    resetProgress.percentage = 100
    resetProgress.message = 'Email sent successfully!'
    await new Promise(resolve => setTimeout(resolve, 500))
    
    showAlert('reset', 'success', `A new temporary password has been sent to ${profileData.email}. Please check your inbox and follow the instructions to login.`, 8000)
  } catch (error) {
    console.error('Failed to send password reset:', error)
    const errorMsg = error.response?.data?.message || 'Failed to send password reset email. Please try again or contact support.'
    showAlert('reset', 'error', errorMsg, 5000)
  } finally {
    isLoading.value = false
    resetProgress.active = false
    resetProgress.percentage = 0
    resetProgress.message = ''
  }
}

// Lifecycle
onMounted(() => {
  loadProfile()
})
</script>

<style scoped>
.profile-page {
  padding: 2rem;
  max-width: 1200px;
  margin: 0 auto;
  position: relative;
}

/* Page Header */
.page-header {
  margin-bottom: 2rem;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-title {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.header-title > i {
  font-size: 2.5rem;
  color: #667eea;
}

.header-title h1 {
  font-size: 2rem;
  font-weight: 700;
  color: #2d3748;
  margin: 0;
}

.subtitle {
  color: #718096;
  font-size: 0.95rem;
  margin: 0.25rem 0 0 0;
}

/* Alert System */
.alert {
  padding: 1rem 1.5rem;
  border-radius: 8px;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
  animation: slideDown 0.3s ease;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.alert-success {
  background: #c6f6d5;
  color: #22543d;
  border: 1px solid #9ae6b4;
}

.alert-error {
  background: #fed7d7;
  color: #742a2a;
  border: 1px solid #fc8181;
}

.alert-warning {
  background: #feebc8;
  color: #7c2d12;
  border: 1px solid #fbd38d;
}

.alert-info {
  background: #bee3f8;
  color: #2c5282;
  border: 1px solid #90cdf4;
}

.profile-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.card-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-header h2 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.card-body {
  padding: 2rem;
}

/* Profile Picture Section */
.profile-picture-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1.5rem;
}

.profile-picture-preview {
  width: 200px;
  height: 200px;
  border-radius: 50%;
  overflow: hidden;
  border: 4px solid #667eea;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
}

.profile-picture-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.profile-picture-placeholder {
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 5rem;
}

.profile-picture-actions {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  justify-content: center;
}

/* Profile View */
.profile-view {
  display: grid;
  gap: 1.5rem;
}

.info-row {
  display: grid;
  grid-template-columns: 150px 1fr;
  gap: 1rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #e2e8f0;
}

.info-row:last-child {
  border-bottom: none;
}

.info-row label {
  font-weight: 600;
  color: #4a5568;
}

.info-row span {
  color: #2d3748;
}

.role-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 500;
}

.bio-text {
  white-space: pre-wrap;
}

/* Profile Form */
.profile-form {
  display: grid;
  gap: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group label {
  font-weight: 600;
  color: #4a5568;
  font-size: 0.95rem;
}

.form-group input,
.form-group textarea {
  padding: 0.75rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.95rem;
  transition: border-color 0.2s;
  font-family: inherit;
}

.form-group input:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #667eea;
}

.form-group input:disabled,
.form-group textarea:disabled {
  background: #f7fafc;
  cursor: not-allowed;
}

.password-input {
  position: relative;
  display: flex;
}

.password-input input {
  flex: 1;
  padding-right: 3rem;
}

.password-toggle {
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: #718096;
  cursor: pointer;
  padding: 0.5rem;
  transition: color 0.2s;
}

.password-toggle:hover {
  color: #667eea;
}

.help-text {
  color: #718096;
  font-size: 0.85rem;
  margin: 0;
}

.info-text {
  color: #4a5568;
  margin-bottom: 1rem;
  line-height: 1.6;
}

/* Form Actions */
.form-actions {
  display: flex;
  gap: 1rem;
  margin-top: 0.5rem;
}

/* Buttons */
.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.2s;
  font-family: inherit;
  position: relative;
  overflow: hidden;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn i.fa-spinner {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Progress Container */
.progress-container {
  margin: 1.5rem 0;
  padding: 1.25rem;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-radius: 10px;
  border: 2px solid #dee2e6;
}

.progress-bar {
  width: 100%;
  height: 8px;
  background: #e9ecef;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
  border-radius: 10px;
  transition: width 0.4s ease;
  box-shadow: 0 0 10px rgba(102, 126, 234, 0.5);
  animation: progressShine 1.5s ease-in-out infinite;
}

@keyframes progressShine {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.8; }
}

.progress-text {
  margin: 0.75rem 0 0 0;
  color: #495057;
  font-size: 0.9rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.progress-text i {
  color: #667eea;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-secondary {
  background: #e2e8f0;
  color: #4a5568;
}

.btn-secondary:hover:not(:disabled) {
  background: #cbd5e0;
}

.btn-success {
  background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
  color: white;
}

.btn-success:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(72, 187, 120, 0.3);
}

.btn-danger {
  background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
  color: white;
}

.btn-danger:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(245, 101, 101, 0.3);
}

.btn-warning {
  background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
  color: white;
}

.btn-warning:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(237, 137, 54, 0.3);
}

.btn-sm {
  padding: 0.5rem 1rem;
  font-size: 0.85rem;
}

/* Loading Overlay */
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.spinner {
  width: 60px;
  height: 60px;
  border: 4px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

/* Responsive */
@media (max-width: 768px) {
  .profile-page {
    padding: 1rem;
  }

  .header-title h1 {
    font-size: 1.5rem;
  }

  .info-row {
    grid-template-columns: 1fr;
    gap: 0.5rem;
  }

  .form-actions {
    flex-direction: column;
  }

  .btn {
    width: 100%;
    justify-content: center;
  }

  .profile-picture-actions {
    flex-direction: column;
    width: 100%;
  }

  .profile-picture-actions .btn {
    width: 100%;
  }
}
</style>
