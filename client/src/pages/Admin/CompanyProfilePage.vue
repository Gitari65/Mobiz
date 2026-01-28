<template>
  <div class="company-profile-page">
    <div class="page-header">
      <h1><i class="fas fa-building"></i> Company Profile</h1>
      <p class="subtitle">Manage your business details and subscription</p>
    </div>

    <div class="tabs">
      <button :class="['tab', activeTab==='profile' && 'active']" @click="activeTab='profile'">
        <i class="fas fa-id-card"></i> Profile
      </button>
      <button :class="['tab', activeTab==='subscription' && 'active']" @click="activeTab='subscription'">
        <i class="fas fa-credit-card"></i> Subscription
      </button>
    </div>

    <div v-if="activeTab==='profile'" class="card">
      <div class="card-header">
        <h2>Business Information</h2>
      </div>
      <div class="card-body">
        <form @submit.prevent="saveCompany">
          <div class="grid">
            <div class="form-group">
              <label>Business Name *</label>
              <input v-model="form.name" required />
            </div>
            <div class="form-group">
              <label>Category</label>
              <input v-model="form.category" />
            </div>
            <div class="form-group">
              <label>Phone</label>
              <input v-model="form.phone" />
            </div>
            <div class="form-group">
              <label>Email</label>
              <input type="email" v-model="form.email" />
            </div>
            <div class="form-group">
              <label>KRA PIN</label>
              <input v-model="form.kra_pin" />
            </div>
            <div class="form-group">
              <label>Address</label>
              <input v-model="form.address" />
            </div>
            <div class="form-group">
              <label>City</label>
              <input v-model="form.city" />
            </div>
            <div class="form-group">
              <label>County</label>
              <input v-model="form.county" />
            </div>
            <div class="form-group">
              <label>ZIP Code</label>
              <input v-model="form.zip_code" />
            </div>
            <div class="form-group">
              <label>Country</label>
              <input v-model="form.country" />
            </div>
            <div class="form-group">
              <label>Owner Name</label>
              <input v-model="form.owner_name" />
            </div>
            <div class="form-group">
              <label>Owner Position</label>
              <input v-model="form.owner_position" />
            </div>
          </div>

          <div v-if="error" class="alert error">{{ error }}</div>
          <div v-if="success" class="alert success">{{ success }}</div>

          <button class="btn primary" :disabled="saving">
            <i :class="saving ? 'fas fa-spinner fa-spin' : 'fas fa-save'"></i>
            {{ saving ? 'Saving...' : 'Save Changes' }}
          </button>
        </form>
      </div>
    </div>

    <div v-else class="card">
      <div class="card-header">
        <h2>Subscription</h2>
      </div>
      <div class="card-body">
        <div v-if="subLoading" class="muted"><i class="fas fa-spinner fa-spin"></i> Loading subscription...</div>
        <div v-else>
          <div v-if="!subscription">
            <p class="muted">No subscription found for your company.</p>
          </div>
          <div v-else class="sub-grid">
            <div class="sub-item"><span class="label">Plan</span><span class="value">{{ subscription.plan || '—' }}</span></div>
            <div class="sub-item"><span class="label">Status</span><span class="value status" :class="subscription.status">{{ subscription.status }}</span></div>
            <div class="sub-item"><span class="label">Starts</span><span class="value">{{ formatDate(subscription.starts_at) }}</span></div>
            <div class="sub-item"><span class="label">Ends</span><span class="value">{{ formatDate(subscription.ends_at) }}</span></div>
            <div class="sub-item"><span class="label">Trial ends</span><span class="value">{{ formatDate(subscription.trial_ends_at) }}</span></div>
            <div class="sub-item"><span class="label">On trial</span><span class="value">{{ subscription.on_trial ? 'Yes' : 'No' }}</span></div>
            <div class="sub-item"><span class="label">Monthly fee</span><span class="value">{{ formatMoney(subscription.monthly_fee) }}</span></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'

const activeTab = ref('profile')
const loading = ref(false)
const saving = ref(false)
const subLoading = ref(false)
const error = ref('')
const success = ref('')

const form = reactive({
  name: '', category: '', phone: '', email: '', kra_pin: '', address: '', city: '', county: '', zip_code: '', country: '', owner_name: '', owner_position: ''
})

const subscription = ref(null)

const loadCompany = async () => {
  try {
    loading.value = true
    const res = await axios.get('/api/company/me')
    if (res.data?.company) Object.assign(form, res.data.company)
  } catch (e) {
    error.value = e.response?.data?.error || 'Failed to load company profile'
  } finally {
    loading.value = false
  }
}

const saveCompany = async () => {
  try {
    error.value = ''
    success.value = ''
    saving.value = true
    const res = await axios.put('/api/company/me', form)
    success.value = res.data?.message || 'Saved'
  } catch (e) {
    error.value = e.response?.data?.error || 'Failed to save company profile'
  } finally {
    saving.value = false
  }
}

const loadSubscription = async () => {
  try {
    subLoading.value = true
    const res = await axios.get('/api/company/subscription')
    subscription.value = res.data?.subscription || null
  } catch (e) {
    // silently ignore
  } finally {
    subLoading.value = false
  }
}

const formatDate = (d) => {
  if (!d) return '—'
  try { return new Date(d).toLocaleDateString() } catch { return '—' }
}
const formatMoney = (n) => {
  if (n == null) return '—'
  return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' }).format(n)
}

onMounted(() => {
  loadCompany()
  loadSubscription()
})
</script>

<style scoped>
.company-profile-page { padding: 20px; }
.page-header { margin-bottom: 16px; }
.page-header h1 { display: flex; gap: 10px; align-items: center; margin: 0; }
.subtitle { color: #6b7280; margin: 4px 0 0; }
.tabs { display: flex; gap: 8px; margin: 16px 0; }
.tab { padding: 10px 14px; border: 1px solid #e5e7eb; border-radius: 8px; background: #fff; cursor: pointer; }
.tab.active { background: #2563eb; color: #fff; border-color: #2563eb; }
.card { background: #fff; border-radius: 12px; box-shadow: 0 4px 16px rgba(0,0,0,0.06); overflow: hidden; }
.card-header { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; }
.card-body { padding: 16px; }
.grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group input { padding: 10px 12px; border: 1.5px solid #e5e7eb; border-radius: 8px; }
.alert { margin-top: 12px; padding: 10px 12px; border-radius: 8px; }
.alert.error { background: #fee2e2; color: #991b1b; }
.alert.success { background: #dcfce7; color: #14532d; }
.btn.primary { margin-top: 12px; padding: 10px 14px; border: none; border-radius: 8px; background: #2563eb; color: #fff; font-weight: 600; cursor: pointer; }
.muted { color: #6b7280; }
.sub-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 10px; }
.sub-item { background: #f8fafc; padding: 12px; border-radius: 8px; display: flex; justify-content: space-between; }
.sub-item .label { color: #6b7280; }
.sub-item .value.status.active { color: #16a34a; font-weight: 600; }
.sub-item .value.status.inactive { color: #dc2626; font-weight: 600; }
</style>
