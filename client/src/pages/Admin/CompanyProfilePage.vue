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

          <!-- Pending upgrade request banner -->
          <div v-if="pendingRequest" class="upgrade-banner pending">
            <i class="fas fa-clock"></i>
            <div>
              <strong>Upgrade request pending approval</strong>
              <p>Requested plan: <strong>{{ pendingRequest.requested_plan }}</strong> · Submitted {{ formatDate(pendingRequest.created_at) }}</p>
              <p v-if="pendingRequest.admin_notes" class="req-notes">Your note: {{ pendingRequest.admin_notes }}</p>
              <p class="req-notes">Awaiting superuser review — you will be notified once approved or rejected.</p>
            </div>
          </div>

          <!-- Approved banner -->
          <div v-if="approvedRequest" class="upgrade-banner approved">
            <i class="fas fa-check-circle"></i>
            <div>
              <strong>Plan upgrade approved!</strong>
              <p>Upgraded to <strong>{{ approvedRequest.requested_plan }}</strong> on {{ formatDate(approvedRequest.reviewed_at) }}</p>
              <p v-if="approvedRequest.reviewer_notes" class="req-notes">Note: {{ approvedRequest.reviewer_notes }}</p>
            </div>
          </div>

          <!-- Rejected banner (shows above form so user can re-submit) -->
          <div v-if="rejectedRequest" class="upgrade-banner rejected">
            <i class="fas fa-times-circle"></i>
            <div>
              <strong>Last upgrade request was declined</strong>
              <p>Requested plan: <strong>{{ rejectedRequest.requested_plan }}</strong></p>
              <p v-if="rejectedRequest.reviewer_notes">Reason: {{ rejectedRequest.reviewer_notes }}</p>
              <p class="req-notes">You can submit a new request below.</p>
            </div>
          </div>

          <!-- Current subscription details -->
          <div v-if="!subscription">
            <p class="muted">No active subscription found for your company.</p>
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

          <!-- Features -->
          <div v-if="subscription && subscription.features && subscription.features.length" class="features-block">
            <h3>Enabled Functionalities</h3>
            <div class="feature-list">
              <span v-for="feature in subscription.features" :key="feature" class="feature-chip">
                {{ formatFeature(feature) }}
              </span>
            </div>
          </div>

          <!-- Request Upgrade form — hidden while a pending request is in flight -->
          <div v-if="!pendingRequest" class="upgrade-form">
            <h3>Request Plan Upgrade</h3>
            <div class="subscription-actions">
              <div class="form-group plan-selector">
                <label>New Plan</label>
                <select v-model="upgradeForm.plan_id">
                  <option value="">Choose a plan</option>
                  <option
                    v-for="plan in upgradablePlans"
                    :key="plan.id"
                    :value="String(plan.id)"
                  >
                    {{ plan.name }} — {{ formatMoney(plan.price) }}/{{ plan.billing_cycle || 'monthly' }}
                  </option>
                </select>
                <p v-if="upgradablePlans.length === 0" class="hint" style="color:#b45309">
                  No other plans available to upgrade to.
                </p>
              </div>
              <div class="form-group">
                <label>Note to superuser (optional)</label>
                <input v-model="upgradeForm.admin_notes" type="text" placeholder="e.g. We need promotions feature" />
              </div>
            </div>
            <div v-if="subError" class="alert error">{{ subError }}</div>
            <div v-if="subSuccess" class="alert success">{{ subSuccess }}</div>
            <button class="btn primary" :disabled="subSaving || !upgradeForm.plan_id" @click="submitUpgradeRequest">
              <i :class="subSaving ? 'fas fa-spinner fa-spin' : 'fas fa-paper-plane'"></i>
              {{ subSaving ? 'Submitting...' : 'Submit Upgrade Request' }}
            </button>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import axios from 'axios'

const activeTab = ref('profile')
const loading = ref(false)
const saving = ref(false)
const subLoading = ref(false)
const subSaving = ref(false)
const error = ref('')
const success = ref('')

const form = reactive({
  name: '', category: '', phone: '', email: '', kra_pin: '', address: '', city: '', county: '', zip_code: '', country: '', owner_name: '', owner_position: ''
})

const subscription = ref(null)
const upgradeRequest = ref(null)   // latest upgrade request (any status)
const availablePlans = ref([])
const upgradeForm = reactive({ plan_id: '', admin_notes: '' })
const subError = ref('')
const subSuccess = ref('')

// Derived states for banners
const pendingRequest = computed(() =>
  upgradeRequest.value?.status === 'pending' ? upgradeRequest.value : null
)
const approvedRequest = computed(() =>
  upgradeRequest.value?.status === 'approved' ? upgradeRequest.value : null
)
const rejectedRequest = computed(() =>
  upgradeRequest.value?.status === 'rejected' ? upgradeRequest.value : null
)

// Plans the admin can actually request (not the one they're already on)
const upgradablePlans = computed(() =>
  availablePlans.value.filter(p =>
    !subscription.value || (p.slug !== subscription.value.plan_slug && String(p.id) !== String(subscription.value.plan_id))
  )
)

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
    const [subRes, reqRes] = await Promise.all([
      axios.get('/api/company/subscription'),
      axios.get('/api/company/subscription/upgrade-request'),
    ])
    subscription.value = subRes.data?.subscription || null
    upgradeRequest.value = reqRes.data?.upgrade_request || null
  } catch (e) {
    // silently ignore
  } finally {
    subLoading.value = false
  }
}

const loadAvailablePlans = async () => {
  try {
    const res = await axios.get('/api/company/subscription/plans')
    availablePlans.value = res.data?.plans || []
  } catch (e) {
    availablePlans.value = []
  }
}

const submitUpgradeRequest = async () => {
  try {
    subError.value = ''
    subSuccess.value = ''
    subSaving.value = true
    const res = await axios.post('/api/company/subscription/request-upgrade', {
      plan_id: Number(upgradeForm.plan_id),
      admin_notes: upgradeForm.admin_notes || null,
    })
    subSuccess.value = res.data?.message || 'Upgrade request submitted. Awaiting superuser approval.'
    upgradeForm.plan_id = ''
    upgradeForm.admin_notes = ''
    await loadSubscription()
  } catch (e) {
    subError.value = e.response?.data?.error || 'Failed to submit upgrade request'
  } finally {
    subSaving.value = false
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

const formatFeature = (feature) => {
  if (!feature) return '—'
  return String(feature)
    .replace(/[_-]+/g, ' ')
    .replace(/\b\w/g, c => c.toUpperCase())
}

onMounted(() => {
  loadCompany()
  loadAvailablePlans().then(loadSubscription)
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
.btn { margin-top: 12px; padding: 10px 14px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
.btn.primary { background: #2563eb; color: #fff; }
.btn.secondary { background: #1f2937; color: #fff; }
.btn.success { background: #16a34a; color: #fff; }
.btn.danger { background: #dc2626; color: #fff; }
.muted { color: #6b7280; }
.sub-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 10px; }
.sub-item { background: #f8fafc; padding: 12px; border-radius: 8px; display: flex; justify-content: space-between; }
.sub-item .label { color: #6b7280; }
.sub-item .value.status.active { color: #16a34a; font-weight: 600; }
.sub-item .value.status.inactive { color: #dc2626; font-weight: 600; }
.sub-item .value.status.canceled { color: #b45309; font-weight: 600; }

.upgrade-banner {
  display: flex;
  gap: 12px;
  align-items: flex-start;
  padding: 12px 16px;
  border-radius: 10px;
  margin-bottom: 16px;
  font-size: 0.92rem;
}
.upgrade-banner i { font-size: 1.25rem; margin-top: 2px; }
.upgrade-banner p { margin: 4px 0 0; color: #374151; font-size: 0.85rem; }
.upgrade-banner strong { display: block; font-size: 0.95rem; }
.upgrade-banner.pending  { background: #fef9c3; border: 1px solid #fde047; color: #713f12; }
.upgrade-banner.rejected { background: #fee2e2; border: 1px solid #fca5a5; color: #7f1d1d; }
.upgrade-banner.approved { background: #dcfce7; border: 1px solid #86efac; color: #14532d; }
.req-notes { font-style: italic; }

.upgrade-form {
  margin-top: 20px;
  padding-top: 16px;
  border-top: 1px solid #e5e7eb;
}
.upgrade-form h3 { margin: 0 0 12px; font-size: 0.95rem; color: #374151; }
.upgrade-form .form-group input {
  padding: 10px 12px;
  border: 1.5px solid #e5e7eb;
  border-radius: 8px;
  width: 100%;
  box-sizing: border-box;
}

.subscription-actions {
  display: grid;
  grid-template-columns: minmax(260px, 1fr) 2fr;
  gap: 12px;
  margin-bottom: 16px;
  align-items: end;
}

.plan-selector select {
  padding: 10px 12px;
  border: 1.5px solid #e5e7eb;
  border-radius: 8px;
}

.action-buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.action-buttons .btn {
  margin-top: 0;
}

.features-block {
  margin-top: 16px;
  padding: 12px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
}

.features-block h3 {
  margin: 0 0 10px;
  font-size: 0.95rem;
  color: #374151;
}

.feature-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.feature-chip {
  background: #eff6ff;
  color: #1e40af;
  border-radius: 999px;
  font-size: 0.8rem;
  font-weight: 600;
  padding: 6px 10px;
}

@media (max-width: 860px) {
  .subscription-actions {
    grid-template-columns: 1fr;
  }
}
</style>
