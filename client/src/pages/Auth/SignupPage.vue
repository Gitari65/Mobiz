<template>
  <div class="signup-container">
    <div class="signup-card">
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
        <h1 class="brand-title">MOBIZ POS</h1>
        <p class="brand-subtitle">Business Account Registration</p>
      </div>

      <form @submit.prevent="handleSignup" class="signup-form">
        <div v-if="step === 1">
          <h2 class="form-title">Step 1: Account Details</h2>
          <div class="form-group">
            <label for="email" :class="{'floating': form.email}">Email</label>
            <input id="email" v-model="form.email" type="email" required />
          </div>
          <div class="form-group">
            <label for="password" :class="{'floating': form.password}">Password</label>
            <input id="password" v-model="form.password" type="password" required />
          </div>
          <div class="form-group">
            <label for="confirmPassword" :class="{'floating': form.confirmPassword}">Confirm Password</label>
            <input id="confirmPassword" v-model="form.confirmPassword" type="password" required />
          </div>
        </div>
        <div v-else-if="step === 2">
          <h2 class="form-title">Step 2: Business Details</h2>
          <div class="form-group">
            <label for="businessName" :class="{'floating': form.businessName}">Business Name</label>
            <input id="businessName" v-model="form.businessName" type="text" required />
          </div>
          <div class="form-group">
            <label for="category" :class="{'floating': form.category}">Business Category</label>
            <select id="category" v-model="form.category" required class="form-select">
              <option value="">Select Business Category</option>
              <option value="Retail">Retail</option>
              <option value="Wholesale">Wholesale</option>
              <option value="Agrovet">Agrovet</option>
              <option value="Pharmacy">Pharmacy</option>
              <option value="Supermarket">Supermarket</option>
              <option value="Restaurant">Restaurant</option>
              <option value="Bakery">Bakery</option>
              <option value="Hardware">Hardware</option>
              <option value="Electronics">Electronics</option>
              <option value="Clothing">Clothing</option>
              <option value="Furniture">Furniture</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="form-group">
            <label for="kraPin" :class="{'floating': form.kraPin}">KRA PIN</label>
            <input id="kraPin" v-model="form.kraPin" type="text" required />
          </div>
          <div class="form-group">
            <label for="phone" :class="{'floating': form.phone}">Phone Number</label>
            <input id="phone" v-model="form.phone" type="text" required />
          </div>
          <div class="form-group">
            <label for="address" :class="{'floating': form.address}">Address</label>
            <input id="address" v-model="form.address" type="text" required />
          </div>
          <div class="form-group">
            <label for="city" :class="{'floating': form.city}">City/Town</label>
            <input id="city" v-model="form.city" type="text" required />
          </div>
          <div class="form-group">
            <label for="county" :class="{'floating': form.county}">County</label>
            <input id="county" v-model="form.county" type="text" required />
          </div>
          <div class="form-group">
            <label for="zipCode" :class="{'floating': form.zipCode}">Postal/Zip Code</label>
            <input id="zipCode" v-model="form.zipCode" type="text" />
          </div>
          <div class="form-group">
            <label for="country" :class="{'floating': form.country}">Country</label>
            <input id="country" v-model="form.country" type="text" required />
          </div>
        </div>
        <div v-else-if="step === 3">
          <h2 class="form-title">Step 3: Owner Details</h2>
          <div class="form-group">
            <label for="ownerName" :class="{'floating': form.ownerName}">Owner Name</label>
            <input id="ownerName" v-model="form.ownerName" type="text" required />
          </div>
          <div class="form-group">
            <label for="ownerPosition" :class="{'floating': form.ownerPosition}">Position</label>
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
  kraPin: '',
  phone: '',
  address: '',
  city: '',
  county: '',
  zipCode: '',
  country: '',
  ownerName: '',
  ownerPosition: ''
})
const alert = ref({ show: false, message: '', type: 'success' })

function nextStep() {
  if (step.value === 1 && (!form.value.email || !form.value.password || form.value.password !== form.value.confirmPassword)) {
    showAlert('Please fill all fields and ensure passwords match.', 'error')
    return
  }
  if (step.value === 2 && (!form.value.businessName || !form.value.category || !form.value.kraPin || !form.value.phone || !form.value.address || !form.value.city || !form.value.county || !form.value.country)) {
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
        kra_pin: form.value.kraPin,
        phone: form.value.phone,
        address: form.value.address,
        city: form.value.city,
        county: form.value.county,
        zip_code: form.value.zipCode,
        country: form.value.country,
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
  padding: 12px;
}

.signup-card {
  background: #fff;
  border-radius: 16px;
  padding: 24px 20px;
  width: 100%;
  max-width: 420px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
  position: relative;
  overflow-y: auto;
  max-height: 90vh;
}

.brand-section {
  text-align: center;
  margin-bottom: 18px;
}

.brand-logo-wrapper {
  position: relative;
  display: inline-block;
  margin-bottom: 10px;
}

.brand-logo {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 56px;
  height: 56px;
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  border-radius: 50%;
  font-size: 22px;
  box-shadow: 0 6px 24px rgba(102, 126, 234, 0.3);
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

.brand-title {
  font-size: 18px;
  font-weight: 700;
  color: #2d3748;
  margin: 0 0 4px 0;
  letter-spacing: 0.3px;
}

.brand-subtitle {
  color: #718096;
  margin: 0;
  font-size: 12px;
  font-weight: 500;
}

.signup-form {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-title {
  font-size: 15px;
  font-weight: 600;
  color: #2d3748;
  margin: 12px 0 8px 0;
}

.form-group {
  position: relative;
  margin-bottom: 10px;
}

.form-group label {
  font-weight: 500;
  color: #4a5568;
  position: absolute;
  left: 12px;
  top: 10px;
  background: #fff;
  padding: 0 4px;
  pointer-events: none;
  transition: 0.2s;
  font-size: 13px;
}

.form-group input:focus + label,
.form-group input:not(:placeholder-shown) + label,
.form-group label.floating {
  top: -8px;
  left: 10px;
  font-size: 11px;
  color: #667eea;
  background: #fff;
  z-index: 2;
}

.form-group input {
  padding: 10px 12px 10px 12px;
  border: 2px solid #e2e8f0;
  border-radius: 9px;
  font-size: 13px;
  background: #f7fafc;
  color: #2d3748;
  width: 100%;
  transition: all 0.2s;
}

.form-group input:focus {
  border-color: #667eea;
  outline: none;
}

.form-select {
  padding: 10px 12px;
  border: 2px solid #e2e8f0;
  border-radius: 9px;
  font-size: 13px;
  background: #f7fafc;
  color: #2d3748;
  width: 100%;
  transition: all 0.2s;
  cursor: pointer;
  font-family: inherit;
}

.form-select:focus {
  border-color: #667eea;
  outline: none;
  background: #fff;
}

.form-select option {
  color: #2d3748;
  background: #fff;
}

.form-actions {
  display: flex;
  justify-content: space-between;
  gap: 8px;
  margin-top: 12px;
}

.form-actions button {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: #fff;
  border: none;
  border-radius: 9px;
  padding: 10px 16px;
  font-weight: 600;
  cursor: pointer;
  font-size: 12px;
  transition: all 0.2s;
  flex: 1;
}

.form-actions button:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.form-actions button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.alert {
  margin-top: 12px;
  padding: 10px 12px;
  border-radius: 8px;
  text-align: center;
  font-size: 12px;
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
  margin-top: 12px;
  text-align: center;
  color: #667eea;
  font-size: 12px;
}

.login-link a {
  color: #764ba2;
  text-decoration: underline;
  font-weight: 500;
}

.mobiz-title {
  font-size: 20px;
  font-weight: 900;
  margin: 0;
  letter-spacing: -0.5px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #8a2387 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  display: none;
}

.mobiz-text {
  display: inline-block;
}

@keyframes titleGlow {
  0%, 100% {
    filter: drop-shadow(0 0 8px rgba(102, 126, 234, 0.3));
  }
  50% {
    filter: drop-shadow(0 0 16px rgba(102, 126, 234, 0.5));
  }
}

@keyframes titleSlide {
  0% { opacity: 0; transform: translateX(-20px); }
  100% { opacity: 1; transform: translateX(0); }
}

/* Responsive Styles */
@media (max-width: 768px) {
  .signup-container {
    padding: 10px;
    min-height: auto;
    justify-content: flex-start;
    padding-top: 40px;
  }

  .signup-card {
    padding: 20px 16px;
    border-radius: 14px;
    max-height: none;
  }

  .brand-section {
    margin-bottom: 14px;
  }

  .brand-logo {
    width: 48px;
    height: 48px;
    font-size: 18px;
    margin: 0 auto 6px;
  }

  .brand-title {
    font-size: 16px;
    margin-bottom: 2px;
  }

  .brand-subtitle {
    font-size: 11px;
  }

  .form-title {
    font-size: 14px;
    margin: 10px 0 6px 0;
  }

  .form-group {
    margin-bottom: 8px;
  }

  .form-group label {
    font-size: 12px;
    left: 10px;
    top: 9px;
  }

  .form-group input {
    padding: 9px 10px;
    font-size: 12px;
    border-radius: 8px;
  }

  .form-actions {
    gap: 6px;
    margin-top: 10px;
  }

  .form-actions button {
    padding: 9px 14px;
    font-size: 11px;
    border-radius: 8px;
  }

  .alert {
    margin-top: 10px;
    padding: 8px 10px;
    font-size: 11px;
  }

  .login-link {
    margin-top: 10px;
    font-size: 11px;
  }
}

@media (max-width: 480px) {
  .signup-container {
    padding: 8px;
    padding-top: 20px;
  }

  .signup-card {
    padding: 18px 14px;
    border-radius: 12px;
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
  }

  .brand-section {
    margin-bottom: 12px;
  }

  .brand-logo {
    width: 44px;
    height: 44px;
    font-size: 16px;
    margin: 0 auto 4px;
    border-radius: 10px;
  }

  .brand-title {
    font-size: 14px;
    margin-bottom: 2px;
  }

  .brand-subtitle {
    font-size: 10px;
  }

  .form-title {
    font-size: 13px;
    margin: 8px 0 4px 0;
  }

  .form-group {
    margin-bottom: 6px;
  }

  .form-group label {
    font-size: 11px;
    left: 10px;
    top: 8px;
  }

  .form-group input:focus + label,
  .form-group input:not(:placeholder-shown) + label,
  .form-group label.floating {
    top: -7px;
    left: 9px;
    font-size: 10px;
  }

  .form-group input {
    padding: 8px 10px;
    font-size: 11px;
    border-radius: 8px;
  }

  .form-actions {
    gap: 6px;
    margin-top: 8px;
  }

  .form-actions button {
    padding: 8px 12px;
    font-size: 10px;
    border-radius: 8px;
  }

  .alert {
    margin-top: 8px;
    padding: 8px 10px;
    font-size: 10px;
  }

  .login-link {
    margin-top: 8px;
    font-size: 10px;
  }
}

@media (max-height: 600px) and (orientation: landscape) {
  .signup-container {
    min-height: auto;
    padding-top: 16px;
    padding-bottom: 16px;
  }

  .signup-card {
    max-height: 90vh;
    padding: 14px 12px;
  }

  .brand-logo {
    width: 40px;
    height: 40px;
    font-size: 14px;
  }

  .form-title {
    margin: 6px 0 4px 0;
    font-size: 12px;
  }

  .form-group {
    margin-bottom: 4px;
  }

  .form-actions button {
    padding: 6px 10px;
    font-size: 10px;
  }
}
</style>
