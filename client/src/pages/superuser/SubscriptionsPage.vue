<template>
  <div class="sub-page">
    <!-- Tabs -->
    <div class="tabs">
      <button 
        v-for="tab in tabs" 
        :key="tab" 
        @click="activeTab = tab"
        :class="{ active: activeTab === tab }"
        class="tab-btn"
      >
        {{ formatTabName(tab) }}
      </button>
    </div>

    <!-- Subscriptions Tab -->
    <section v-if="activeTab === 'subscriptions'" class="section">
      <div class="section-header">
        <h2>Subscriptions</h2>
        <div class="controls">
          <input v-model="subFilters.q" type="search" placeholder="Search..." class="search-input" @input="fetchSubscriptions" />
          <select v-model="subFilters.status" @change="fetchSubscriptions" class="filter-select">
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="suspended">Suspended</option>
          </select>
        </div>
      </div>

      <div v-if="subLoading" class="loading">Loading subscriptions...</div>

      <table v-else class="data-table">
        <thead>
          <tr>
            <th>Company</th>
            <th>Plan</th>
            <th>Monthly Fee</th>
            <th>Status</th>
            <th>Trial</th>
            <th>Expires</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="sub in subscriptions" :key="sub.id">
            <td>{{ sub.company?.name }}</td>
            <td>{{ sub.plan?.name }}</td>
            <td>${{ sub.monthly_fee }}</td>
            <td><span class="badge" :class="`badge-${sub.status}`">{{ sub.status }}</span></td>
            <td>
              <span v-if="sub.on_trial" class="badge badge-warning">
                {{ formatDate(sub.trial_ends_at) }}
              </span>
              <span v-else class="text-muted">-</span>
            </td>
            <td>{{ sub.ends_at ? formatDate(sub.ends_at) : '-' }}</td>
            <td class="actions">
              <button @click="renewSubscription(sub)" class="btn-sm btn-primary" title="Renew">
                <i class="fas fa-sync"></i>
              </button>
              <button @click="assignTrial(sub)" class="btn-sm btn-secondary" title="Assign Trial">
                <i class="fas fa-gift"></i>
              </button>
              <button v-if="sub.status === 'active'" @click="deactivateSubscription(sub)" class="btn-sm btn-danger" title="Deactivate">
                <i class="fas fa-ban"></i>
              </button>
              <button v-else @click="activateSubscription(sub)" class="btn-sm btn-success" title="Activate">
                <i class="fas fa-check"></i>
              </button>
              <button @click="viewPaymentHistory(sub)" class="btn-sm btn-info" title="Payment History">
                <i class="fas fa-history"></i>
              </button>
            </td>
          </tr>
          <tr v-if="subscriptions.length === 0">
            <td colspan="7" class="empty">No subscriptions found</td>
          </tr>
        </tbody>
      </table>
    </section>

    <!-- Plans Tab -->
    <section v-if="activeTab === 'plans'" class="section">
      <div class="section-header">
        <h2>Billing Plans</h2>
        <button @click="showPlanForm = true" class="btn-create">
          <i class="fas fa-plus"></i> Create Plan
        </button>
      </div>

      <div v-if="planLoading" class="loading">Loading plans...</div>

      <div v-else class="plans-grid">
        <div v-for="plan in plans" :key="plan.id" class="plan-card">
          <div class="plan-header">
            <h3>{{ plan.name }}</h3>
            <span class="badge" :class="plan.is_active ? 'badge-success' : 'badge-danger'">
              {{ plan.is_active ? 'Active' : 'Inactive' }}
            </span>
          </div>
          <p class="plan-desc">{{ plan.description }}</p>
          <div class="plan-price">${{ plan.price }}/{{ plan.billing_cycle }}</div>
          <ul v-if="plan.features" class="plan-features">
            <li v-for="feature in plan.features.slice(0, 3)" :key="feature">
              <i class="fas fa-check"></i> {{ feature }}
            </li>
            <li v-if="plan.features.length > 3" class="more">+{{ plan.features.length - 3 }} more</li>
          </ul>
          <div class="plan-actions">
            <button @click="editPlan(plan)" class="btn-sm btn-secondary">Edit</button>
            <button @click="deletePlan(plan)" class="btn-sm btn-danger">Delete</button>
          </div>
        </div>
      </div>
    </section>

    <!-- Payment History Modal -->
    <div v-if="showPaymentHistory" class="modal-overlay" @click="showPaymentHistory = false">
      <div class="modal" @click.stop>
        <h2>Payment History: {{ selectedSub?.company?.name }}</h2>
        <div v-if="paymentLoading" class="loading">Loading transactions...</div>
        <table v-else class="data-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Amount</th>
              <th>Status</th>
              <th>Method</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="trans in paymentHistory" :key="trans.id">
              <td>{{ formatDate(trans.processed_at) }}</td>
              <td>${{ trans.amount }}</td>
              <td><span class="badge" :class="`badge-${trans.status}`">{{ trans.status }}</span></td>
              <td>{{ trans.payment_method }}</td>
            </tr>
          </tbody>
        </table>
        <button @click="showPaymentHistory = false" class="btn-close-modal">Close</button>
      </div>
    </div>

    <!-- Plan Form Modal -->
    <div v-if="showPlanForm" class="modal-overlay" @click="showPlanForm = false">
      <div class="modal" @click.stop>
        <h2>{{ editingPlanId ? 'Edit Plan' : 'Create Plan' }}</h2>
        <form @submit.prevent="savePlan" class="form">
          <div class="form-group">
            <label>Name *</label>
            <input v-model="planForm.name" type="text" required />
          </div>
          <div class="form-group">
            <label>Slug *</label>
            <input v-model="planForm.slug" type="text" required />
          </div>
          <div class="form-group">
            <label>Price *</label>
            <input v-model="planForm.price" type="number" required step="0.01" />
          </div>
          <div class="form-group">
            <label>Billing Cycle *</label>
            <select v-model="planForm.billing_cycle" required>
              <option value="monthly">Monthly</option>
              <option value="annual">Annual</option>
              <option value="custom">Custom</option>
            </select>
          </div>
          <div class="form-group">
            <label>Description</label>
            <textarea v-model="planForm.description" rows="3"></textarea>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn-primary">{{ editingPlanId ? 'Update' : 'Create' }}</button>
            <button type="button" @click="showPlanForm = false" class="btn-secondary">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Trial Assignment Modal -->
    <div v-if="showTrialForm" class="modal-overlay" @click="showTrialForm = false">
      <div class="modal" @click.stop>
        <h2>Assign Free Trial</h2>
        <div class="form-group">
          <label>Trial Days *</label>
          <input v-model.number="trialDays" type="number" required min="1" max="365" />
        </div>
        <div class="form-actions">
          <button @click="submitTrial" class="btn-primary">Assign Trial</button>
          <button @click="showTrialForm = false" class="btn-secondary">Cancel</button>
        </div>
      </div>
    </div>

    <!-- Alert Toast -->
    <div v-if="alert.show" class="alert-toast" :class="`alert-${alert.type}`">
      <i :class="getAlertIcon(alert.type)"></i>
      <span>{{ alert.message }}</span>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'

const activeTab = ref('subscriptions')
const tabs = ['subscriptions', 'plans']

// Subscriptions State
const subscriptions = ref([])
const subLoading = ref(false)
const subFilters = reactive({ q: '', status: '' })

// Plans State
const plans = ref([])
const planLoading = ref(false)
const showPlanForm = ref(false)
const editingPlanId = ref(null)
const planForm = reactive({ name: '', slug: '', price: '', billing_cycle: 'monthly', description: '' })

// Payment History
const showPaymentHistory = ref(false)
const paymentLoading = ref(false)
const paymentHistory = ref([])
const selectedSub = ref(null)

// Trial Modal
const showTrialForm = ref(false)
const trialDays = ref(30)
const selectedSubForTrial = ref(null)

// Alert
const alert = reactive({ show: false, type: 'info', message: '' })

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

const formatTabName = (tab) => tab.charAt(0).toUpperCase() + tab.slice(1)
const formatDate = (date) => date ? new Date(date).toLocaleDateString() : '-'

async function fetchSubscriptions() {
  subLoading.value = true
  try {
    const res = await axios.get('/api/super/subscriptions', { params: subFilters })
    subscriptions.value = res.data.data || []
  } catch (e) {
    showAlert('error', 'Failed to load subscriptions')
  } finally {
    subLoading.value = false
  }
}

async function fetchPlans() {
  planLoading.value = true
  try {
    const res = await axios.get('/api/super/plans')
    plans.value = res.data.plans || []
  } catch (e) {
    showAlert('error', 'Failed to load plans')
  } finally {
    planLoading.value = false
  }
}

async function activateSubscription(sub) {
  try {
    await axios.patch(`/api/super/subscriptions/${sub.id}/activate`)
    sub.status = 'active'
    showAlert('success', 'Subscription activated')
  } catch (e) {
    showAlert('error', 'Failed to activate')
  }
}

async function deactivateSubscription(sub) {
  try {
    await axios.patch(`/api/super/subscriptions/${sub.id}/deactivate`)
    sub.status = 'inactive'
    showAlert('success', 'Subscription deactivated')
  } catch (e) {
    showAlert('error', 'Failed to deactivate')
  }
}

async function renewSubscription(sub) {
  try {
    await axios.post(`/api/super/subscriptions/${sub.id}/renew`)
    fetchSubscriptions()
    showAlert('success', 'Subscription renewed')
  } catch (e) {
    showAlert('error', 'Failed to renew')
  }
}

function assignTrial(sub) {
  selectedSubForTrial.value = sub
  showTrialForm.value = true
}

async function submitTrial() {
  try {
    await axios.post(`/api/super/subscriptions/${selectedSubForTrial.value.id}/trial`, { trial_days: trialDays.value })
    showTrialForm.value = false
    fetchSubscriptions()
    showAlert('success', 'Trial assigned')
  } catch (e) {
    showAlert('error', 'Failed to assign trial')
  }
}

async function viewPaymentHistory(sub) {
  selectedSub.value = sub
  paymentLoading.value = true
  try {
    const res = await axios.get(`/api/super/subscriptions/${sub.id}/transactions`)
    paymentHistory.value = res.data.data || []
    showPaymentHistory.value = true
  } catch (e) {
    showAlert('error', 'Failed to load payment history')
  } finally {
    paymentLoading.value = false
  }
}

function editPlan(plan) {
  editingPlanId.value = plan.id
  Object.assign(planForm, plan)
  showPlanForm.value = true
}

async function savePlan() {
  try {
    if (editingPlanId.value) {
      await axios.put(`/api/super/plans/${editingPlanId.value}`, planForm)
      showAlert('success', 'Plan updated')
    } else {
      await axios.post('/api/super/plans', planForm)
      showAlert('success', 'Plan created')
    }
    showPlanForm.value = false
    editingPlanId.value = null
    Object.assign(planForm, { name: '', slug: '', price: '', billing_cycle: 'monthly', description: '' })
    fetchPlans()
  } catch (e) {
    showAlert('error', e.response?.data?.message || 'Failed to save plan')
  }
}

async function deletePlan(plan) {
  if (!confirm('Delete this plan?')) return
  try {
    await axios.delete(`/api/super/plans/${plan.id}`)
    fetchPlans()
    showAlert('success', 'Plan deleted')
  } catch (e) {
    showAlert('error', 'Failed to delete')
  }
}

onMounted(() => {
  fetchSubscriptions()
  fetchPlans()
})
</script>

<style scoped>
.sub-page { max-width: 1200px; margin: 2rem auto; padding: 1.5rem; font-family: 'Inter', sans-serif; }

.tabs { display: flex; gap: 1rem; margin-bottom: 2rem; border-bottom: 2px solid #e5e7eb; }
.tab-btn { background: none; border: none; padding: 1rem; cursor: pointer; font-weight: 600; color: #6b7280; transition: all 0.3s; border-bottom: 3px solid transparent; }
.tab-btn.active { color: #667eea; border-bottom-color: #667eea; }

.section { background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: 0 6px 20px rgba(0,0,0,0.06); }

.section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; gap: 1rem; }
.section-header h2 { margin: 0; }

.controls { display: flex; gap: 0.75rem; }
.search-input, .filter-select { padding: 0.75rem 1rem; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 0.95rem; }

.data-table { width: 100%; border-collapse: collapse; }
.data-table th, .data-table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; }
.data-table th { background: #f9fafb; font-weight: 600; }

.badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 6px; font-size: 0.8rem; font-weight: 600; }
.badge-active { background: #d1fae5; color: #065f46; }
.badge-inactive { background: #fee2e2; color: #991b1b; }
.badge-success { background: #d1fae5; color: #065f46; }
.badge-warning { background: #fef3c7; color: #78350f; }

.actions { display: flex; gap: 0.5rem; }
.btn-sm { padding: 0.5rem; border: none; border-radius: 6px; cursor: pointer; font-size: 0.85rem; }
.btn-primary { background: #667eea; color: #fff; }
.btn-secondary { background: #e5e7eb; color: #374151; }
.btn-danger { background: #dc2626; color: #fff; }
.btn-success { background: #48bb78; color: #fff; }
.btn-info { background: #3b82f6; color: #fff; }

.plans-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; }
.plan-card { border: 2px solid #e5e7eb; border-radius: 12px; padding: 1.5rem; }
.plan-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem; }
.plan-header h3 { margin: 0; }
.plan-price { font-size: 1.5rem; font-weight: 700; color: #667eea; margin: 1rem 0; }
.plan-features { list-style: none; padding: 0; margin: 1rem 0; }
.plan-features li { padding: 0.5rem 0; color: #6b7280; }
.plan-actions { display: flex; gap: 0.5rem; }

.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 2000; }
.modal { background: #fff; padding: 2rem; border-radius: 12px; width: 100%; max-width: 500px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }

.form { display: flex; flex-direction: column; gap: 1rem; }
.form-group { display: flex; flex-direction: column; gap: 0.5rem; }
.form-group label { font-weight: 600; }
.form-group input, .form-group select, .form-group textarea { padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 8px; font-family: inherit; }

.form-actions { display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1rem; }
.btn-create { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600; }

.alert-toast { position: fixed; bottom: 2rem; right: 2rem; display: flex; align-items: center; gap: 1rem; padding: 1rem 1.5rem; border-radius: 10px; z-index: 3000; }
.alert-success { background: #d1fae5; color: #065f46; }
.alert-error { background: #fee2e2; color: #991b1b; }

.loading { text-align: center; padding: 2rem; color: #6b7280; }
.empty { text-align: center; padding: 1rem; color: #a0aec0; }
</style>
