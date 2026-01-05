<template>
  <div class="su-dashboard">
    <header class="su-header">
      <div class="su-title">
        <i class="fas fa-shield-alt"></i>
        <div>
          <h1>Super User Dashboard</h1>
          <p class="muted">Overview — aggregated platform metrics & recent activity</p>
        </div>
      </div>
      <div>
        <button class="btn-refresh" @click="loadOverview" :disabled="loading">
          <i class="fas fa-sync-alt"></i>
        </button>
      </div>
    </header>

    <section v-if="loading" class="loading">Loading dashboard...</section>
    <section v-else-if="error" class="error">Error loading dashboard: {{ error }}</section>

    <section v-else class="su-body">
      <div class="cards">
        <div class="card">
          <div class="card-title">Businesses</div>
          <div class="card-value">{{ overview.totals.total_businesses }}</div>
          <div class="card-sub">Active subs: {{ overview.totals.active_subscriptions }}</div>
        </div>

        <div class="card">
          <div class="card-title">Users</div>
          <div class="card-value">{{ overview.totals.total_users }}</div>
          <div class="card-sub">Daily revenue: {{ formatCurrency(overview.totals.daily_revenue) }}</div>
        </div>

        <div class="card">
          <div class="card-title">MRR</div>
          <div class="card-value">{{ formatCurrency(overview.totals.mrr) }}</div>
          <div class="card-sub">Queued jobs: {{ overview.system_health.queued_jobs }}</div>
        </div>

        <div class="card">
          <div class="card-title">System</div>
          <div class="card-value">Failed: {{ overview.system_health.failed_jobs }}</div>
          <div class="card-sub">Health: <span :class="{'good': overview.system_health.failed_jobs===0, 'warn': overview.system_health.failed_jobs>0}">{{ overview.system_health.failed_jobs===0 ? 'OK' : 'Issues' }}</span></div>
        </div>
      </div>

      <div class="charts-grid">
        <div class="chart-card">
          <h3>Monthly Signups</h3>
          <canvas ref="signupsChart" width="600" height="260"></canvas>
          <div v-if="!chartAvailable" class="chart-fallback">Install Chart.js to see interactive charts</div>
        </div>

        <div class="chart-card">
          <h3>Active Businesses (monthly)</h3>
          <canvas ref="businessChart" width="600" height="260"></canvas>
          <div v-if="!chartAvailable" class="chart-fallback">Install Chart.js to see interactive charts</div>
        </div>
      </div>

      <div class="lower-grid">
        <div class="activities-card">
          <h3>Latest Activities</h3>
          <table class="log-table">
            <thead>
              <tr><th>Time</th><th>User</th><th>Action</th><th>Resource</th><th>IP</th></tr>
            </thead>
            <tbody>
              <tr v-for="act in overview.latest_activities" :key="act.id">
                <td>{{ formatDate(act.created_at) }}</td>
                <td>{{ act.user_name || act.user_id || 'System' }}</td>
                <td>{{ act.action }}</td>
                <td>{{ shortResource(act.auditable_type) }}#{{ act.auditable_id }}</td>
                <td>{{ act.ip_address || '-' }}</td>
              </tr>
              <tr v-if="overview.latest_activities.length===0">
                <td colspan="5" class="empty">No recent activity</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="quick-actions-card">
          <h3>Quick Admin Actions</h3>
          <button class="action-btn" @click="goToUsers">Manage Users</button>
          <button class="action-btn" @click="goToCompanies">Manage Companies</button>
          <button class="action-btn" @click="goToExports">Start Export</button>
          <div class="note muted">All actions respect role & policy constraints.</div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'
import { useRouter } from 'vue-router'

const router = useRouter()
const overview = reactive({
  totals: { total_businesses: 0, active_subscriptions: 0, total_users: 0, daily_revenue: 0, mrr: 0 },
  system_health: { queued_jobs: 0, failed_jobs: 0 },
  charts: { monthly_signups: { labels: [], data: [] }, active_businesses: { labels: [], data: [] } },
  latest_activities: []
})
const loading = ref(false)
const error = ref(null)
const chartAvailable = ref(false)
let Chart = null
const signupsChartRef = ref(null)
const businessChartRef = ref(null)
let signupsChartInstance = null
let businessChartInstance = null

// set auth header if token exists
const token = localStorage.getItem('authToken')
if (token) {
  axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
}

function formatCurrency(v) {
  if (v == null) return '-'
  return new Intl.NumberFormat('en-US',{style:'currency',currency:'USD',maximumFractionDigits:2}).format(v)
}
function formatDate(dt) {
  if (!dt) return ''
  const d = new Date(dt)
  return d.toLocaleString()
}
function shortResource(type) {
  if (!type) return ''
  const parts = type.split('\\')
  return parts[parts.length-1]
}

async function loadOverview() {
  loading.value = true
  error.value = null
  try {
    const res = await axios.get('/dashboard/overview')
    const data = res.data
    // merge safely
    Object.assign(overview.totals, data.totals || {})
    Object.assign(overview.system_health, data.system_health || {})
    overview.charts.monthly_signups = data.charts?.monthly_signups || { labels: [], data: [] }
    overview.charts.active_businesses = data.charts?.active_businesses || { labels: [], data: [] }
    overview.latest_activities = data.latest_activities || []
    await ensureCharts()
    renderCharts()
  } catch (e) {
    console.error(e)
    error.value = (e?.response?.data?.message) || e.message || 'Failed to load overview'
  } finally {
    loading.value = false
  }
}

async function ensureCharts() {
  if (chartAvailable.value) return
  try {
    const mod = await import('chart.js/auto')
    Chart = mod.default || mod
    chartAvailable.value = true
  } catch (err) {
    // Chart.js not installed; keep chartAvailable false and show fallback
    console.warn('Chart.js not available:', err)
    chartAvailable.value = false
  }
}

function renderCharts() {
  if (!chartAvailable.value) return
  // destroy existing
  if (signupsChartInstance) { signupsChartInstance.destroy(); signupsChartInstance = null }
  if (businessChartInstance) { businessChartInstance.destroy(); businessChartInstance = null }

  const signupsCtx = signupsChartRef.value && signupsChartRef.value.getContext ? signupsChartRef.value.getContext('2d') : null
  const businessCtx = businessChartRef.value && businessChartRef.value.getContext ? businessChartRef.value.getContext('2d') : null

  if (signupsCtx) {
    signupsChartInstance = new Chart(signupsCtx, {
      type: 'line',
      data: {
        labels: overview.charts.monthly_signups.labels,
        datasets: [{
          label: 'Signups',
          data: overview.charts.monthly_signups.data,
          backgroundColor: 'rgba(102,126,234,0.15)',
          borderColor: 'rgba(102,126,234,0.9)',
          fill: true,
          tension: 0.3
        }]
      },
      options: { responsive: true, maintainAspectRatio: false }
    })
  }

  if (businessCtx) {
    businessChartInstance = new Chart(businessCtx, {
      type: 'bar',
      data: {
        labels: overview.charts.active_businesses.labels,
        datasets: [{
          label: 'New Businesses',
          data: overview.charts.active_businesses.data,
          backgroundColor: 'rgba(72,187,120,0.9)'
        }]
      },
      options: { responsive: true, maintainAspectRatio: false }
    })
  }
}

// quick nav
function goToUsers() { router.push('/user-management') }
function goToCompanies() { router.push('/admin-customization') }
function goToExports() { router.push('/data-export') }

onMounted(() => {
  loadOverview()
})
</script>

<style scoped>
.su-dashboard { max-width: 1200px; margin: 2rem auto; padding: 1.5rem; font-family: Inter, system-ui, -apple-system; }
.su-header { display:flex; justify-content:space-between; align-items:center; gap:1rem; margin-bottom:1rem; }
.su-title { display:flex; align-items:center; gap:1rem; }
.su-title i { font-size:2rem; color:#667eea; background:rgba(102,126,234,0.08); padding:0.5rem; border-radius:10px; }
.su-title h1 { margin:0; font-size:1.5rem; }
.muted { color:#6b7280; margin:0; }

.cards { display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; margin-bottom:1.25rem; }
.card { background:#fff; border-radius:12px; padding:1rem; box-shadow:0 6px 18px rgba(15,23,42,0.06); }
.card-title { font-size:0.9rem; color:#718096; }
.card-value { font-size:1.6rem; font-weight:700; margin-top:0.2rem; }
.card-sub { font-size:0.85rem; color:#94a3b8; margin-top:0.5rem; }

.charts-grid { display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1.25rem; }
.chart-card { background:#fff; border-radius:12px; padding:1rem; height:320px; position:relative; box-shadow:0 6px 18px rgba(15,23,42,0.06); }
.chart-card h3 { margin:0 0 0.5rem 0; font-size:1rem; }
.chart-fallback { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; color:#94a3b8; font-size:0.95rem; }

.lower-grid { display:grid; grid-template-columns:2fr 1fr; gap:1rem; }
.activities-card, .quick-actions-card { background:#fff; border-radius:12px; padding:1rem; box-shadow:0 6px 18px rgba(15,23,42,0.06); }
.log-table { width:100%; border-collapse:collapse; }
.log-table th, .log-table td { padding:0.6rem; border-bottom:1px solid #edf2f7; text-align:left; font-size:0.9rem; }
.log-table th { background:#f7fafc; font-weight:600; color:#475569; }
.empty { text-align:center; padding:1rem; color:#94a3b8; }

.btn-refresh { background:#fff; border:1px solid #e6e6f0; padding:0.5rem; border-radius:8px; cursor:pointer; }
.action-btn { display:block; width:100%; margin-bottom:0.5rem; padding:0.6rem; background:linear-gradient(135deg,#667eea,#764ba2); color:#fff; border:none; border-radius:8px; cursor:pointer; }

.loading, .error { padding:1rem; text-align:center; color:#94a3b8; background:#fff; border-radius:8px; }
</style>
