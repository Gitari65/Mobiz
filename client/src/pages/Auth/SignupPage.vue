<template>
  <div class="signup-container">
    <div class="signup-card">
      <div class="brand-section">
        <div class="brand-logo">
          <i class="fas fa-seedling"></i>
        </div>
        <h1 class="brand-title">AGROVET POS</h1>
        <p class="brand-subtitle">Business Account Registration</p>
      </div>

      <form @submit.prevent="handleSignup" class="signup-form">
        <div v-if="step === 1">
          <h2 class="form-title">Step 1: Account Details</h2>
          <div class="form-group">
            <label for="email">Email</label>
            <input id="email" v-model="form.email" type="email" required />
          </div>
          <div class="form-group">
            <label for="password">Password</label>
            <input id="password" v-model="form.password" type="password" required />
          </div>
          <div class="form-group">
            <label for="confirmPassword">Confirm Password</label>
            <input id="confirmPassword" v-model="form.confirmPassword" type="password" required />
          </div>
        </div>
        <div v-else-if="step === 2">
          <h2 class="form-title">Step 2: Business Details</h2>
          <div class="form-group">
            <label for="businessName">Business Name</label>
            <input id="businessName" v-model="form.businessName" type="text" required />
          </div>
          <div class="form-group">
            <label for="category">Business Category</label>
            <input id="category" v-model="form.category" type="text" required />
          </div>
          <div class="form-group">
            <label for="phone">Phone Number</label>
            <input id="phone" v-model="form.phone" type="text" required />
          </div>
        </div>
        <div v-else-if="step === 3">
          <h2 class="form-title">Step 3: Owner Details</h2>
          <div class="form-group">
            <label for="ownerName">Owner Name</label>
            <input id="ownerName" v-model="form.ownerName" type="text" required />
          </div>
          <div class="form-group">
            <label for="ownerPosition">Position</label>
            <input id="ownerPosition" v-model="form.ownerPosition" type="text" required />
          </div>
        </div>
        <div class="form-actions">
          <button v-if="step > 1" type="button" @click="prevStep">Back</button>
          <button v-if="step < 3" type="button" @click="nextStep">Next</button>
          <button v-if="step === 3" type="submit">Submit</button>
        </div>
      </form>
      <div v-if="alert.show" :class="['alert', alert.type]">{{ alert.message }}</div>
      <div class="login-link">
        Already have an account? <router-link to="/login">Sign In</router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const step = ref(1)
const form = ref({
  email: '',
  password: '',
  confirmPassword: '',
  businessName: '',
  category: '',
  phone: '',
  ownerName: '',
  ownerPosition: ''
})
const alert = ref({ show: false, message: '', type: 'success' })

function nextStep() {
  if (step.value === 1 && (!form.value.email || !form.value.password || form.value.password !== form.value.confirmPassword)) {
    showAlert('Please fill all fields and ensure passwords match.', 'error')
    return
  }
  if (step.value === 2 && (!form.value.businessName || !form.value.category || !form.value.phone)) {
    showAlert('Please fill all business details.', 'error')
    return
  }
  step.value++
}
function prevStep() {
  step.value--
}
function showAlert(message, type = 'success') {
  alert.value = { show: true, message, type }
  setTimeout(() => (alert.value.show = false), 3000)
}
async function handleSignup() {
  if (form.value.password !== form.value.confirmPassword) {
    showAlert('Passwords do not match.', 'error')
    return
  }
  try {
    const response = await fetch('http://localhost:8000/register-company', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        email: form.value.email,
        password: form.value.password,
        business_name: form.value.businessName,
        category: form.value.category,
        phone: form.value.phone,
        owner_name: form.value.ownerName,
        owner_position: form.value.ownerPosition
      })
    })
    const data = await response.json()
    if (response.ok) {
      showAlert('Registration successful! Awaiting approval.', 'success')
      setTimeout(() => router.push('/login'), 3000)
    } else {
      showAlert(data.message || 'Registration failed. Please try again.', 'error')
    }
  } catch (error) {
    showAlert('An error occurred. Please try again.', 'error')
  }
}
</script>

<style scoped>
.signup-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
.signup-card {
  background: #fff;
  border-radius: 20px;
  padding: 2rem 2.5rem;
  width: 100%;
  max-width: 420px;
  box-shadow: 0 25px 50px rgba(0,0,0,0.15);
  position: relative;
}
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
.signup-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.form-title {
  font-size: 1.2rem;
  font-weight: 600;
  color: #2d3748;
  margin-bottom: 1rem;
}
.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.form-group label {
  font-weight: 500;
  color: #4a5568;
}
.form-group input {
  padding: 0.75rem 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 1rem;
  background: #f7fafc;
  color: #2d3748;
}
.form-actions {
  display: flex;
  justify-content: space-between;
  margin-top: 1rem;
}
.form-actions button {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: #fff;
  border: none;
  border-radius: 10px;
  padding: 0.75rem 1.5rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}
.form-actions button:hover {
  background: linear-gradient(135deg, #764ba2, #667eea);
}
.alert {
  margin-top: 1rem;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  text-align: center;
}
.alert.success {
  background: #e6fffa;
  color: #2c7a7b;
}
.alert.error {
  background: #fff5f5;
  color: #c53030;
}
.login-link {
  margin-top: 1.5rem;
  text-align: center;
  color: #667eea;
}
.login-link a {
  color: #764ba2;
  text-decoration: underline;
  font-weight: 500;
}
</style>
