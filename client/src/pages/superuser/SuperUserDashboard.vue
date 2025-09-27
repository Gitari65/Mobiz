<template>
  <div class="superuser-dashboard-container">
    <div class="dashboard-header">
      <div class="brand-logo">
        <i class="fas fa-user-shield"></i>
      </div>
      <h1 class="dashboard-title">Super User Dashboard</h1>
      <p class="dashboard-subtitle">Manage all businesses and approvals</p>
    </div>

    <div class="dashboard-section">
      <h2>Pending Business Approvals</h2>
      <div v-if="loading" class="loading">Loading...</div>
      <div v-else-if="pendingCompanies.length === 0" class="empty">No pending business registrations.</div>
      <table v-else class="company-table">
        <thead>
          <tr>
            <th>Business Name</th>
            <th>Category</th>
            <th>Owner</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="company in pendingCompanies" :key="company.id">
            <td>{{ company.name }}</td>
            <td>{{ company.category }}</td>
            <td>{{ company.owner_name }}</td>
            <td>{{ company.email }}</td>
            <td>{{ company.phone }}</td>
            <td><span class="status pending">Pending</span></td>
            <td>
              <button @click="approveCompany(company.id)" class="approve-btn">Approve</button>
              <button @click="rejectCompany(company.id)" class="reject-btn">Reject</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="dashboard-section">
      <h2>All Businesses</h2>
      <div v-if="loading" class="loading">Loading...</div>
      <div v-else-if="allCompanies.length === 0" class="empty">No businesses found.</div>
      <table v-else class="company-table">
        <thead>
          <tr>
            <th>Business Name</th>
            <th>Category</th>
            <th>Owner</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="company in allCompanies" :key="company.id">
            <td>{{ company.name }}</td>
            <td>{{ company.category }}</td>
            <td>{{ company.owner_name }}</td>
            <td>{{ company.email }}</td>
            <td>{{ company.phone }}</td>
            <td>
              <span :class="['status', company.status]">{{ company.status }}</span>
            </td>
            <td>
              <button v-if="company.status === 'active'" @click="deactivateCompany(company.id)" class="deactivate-btn">Deactivate</button>
              <button v-else-if="company.status === 'inactive'" @click="activateCompany(company.id)" class="activate-btn">Activate</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- User Management Section -->
    <div class="dashboard-section">
      <h2>User Management</h2>
      <div class="user-management-controls">
        <input v-model="userSearch" placeholder="Search users by name, email, or business..." class="user-search" />
      </div>
      <div v-if="loadingUsers" class="loading">Loading users...</div>
      <div v-else-if="filteredUsers.length === 0" class="empty">No users found.</div>
      <table v-else class="user-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Business</th>
            <th>Role</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in filteredUsers" :key="user.id">
            <td>{{ user.name }}</td>
            <td>{{ user.email }}</td>
            <td>{{ user.business_name }}</td>
            <td>{{ user.role }}</td>
            <td>
              <span :class="['status', user.status]">{{ user.status }}</span>
            </td>
            <td>
              <button v-if="user.status === 'active'" @click="deactivateUser(user.id)" class="deactivate-btn">Deactivate</button>
              <button v-else-if="user.status === 'inactive'" @click="activateUser(user.id)" class="activate-btn">Activate</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Audit Logs Section -->
    <div class="dashboard-section">
      <h2>Audit Logs & Activity Tracking</h2>
      <div class="audit-log-controls">
        <input v-model="logSearch" placeholder="Search logs by business, user, or action..." class="log-search" />
      </div>
      <div v-if="loadingLogs" class="loading">Loading logs...</div>
      <div v-else-if="filteredLogs.length === 0" class="empty">No logs found.</div>
      <table v-else class="log-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Business</th>
            <th>User</th>
            <th>Action</th>
            <th>Details</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="log in filteredLogs" :key="log.id">
            <td>{{ log.created_at }}</td>
            <td>{{ log.business_name }}</td>
            <td>{{ log.user_name }}</td>
            <td>{{ log.action }}</td>
            <td>{{ log.details }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Global Settings Section -->
    <div class="dashboard-section">
      <h2>Global Settings</h2>
      <div class="global-settings-controls">
        <input v-model="newCategory" placeholder="Add new business category..." class="category-input" />
        <button @click="addCategory" class="add-category-btn">Add</button>
      </div>
      <div class="category-list">
        <span v-for="cat in categories" :key="cat" class="category-chip">
          {{ cat }}
          <button @click="removeCategory(cat)" class="remove-category-btn">&times;</button>
        </span>
      </div>
      <div class="feature-toggles">
        <h3>Feature Toggles</h3>
        <div v-for="feature in features" :key="feature.key" class="feature-toggle-row">
          <label>
            <input type="checkbox" v-model="feature.enabled" @change="toggleFeature(feature)" />
            {{ feature.name }}
          </label>
        </div>
      </div>
      <div class="announcement-section">
        <h3>Platform-wide Announcement</h3>
        <textarea v-model="announcement" class="announcement-input" placeholder="Write an announcement..."></textarea>
        <button @click="saveAnnouncement" class="save-announcement-btn">Save Announcement</button>
      </div>
    </div>

    <!-- Data Export Section -->
    <div class="dashboard-section">
      <h2>Data Export</h2>
      <div class="export-controls">
        <button @click="exportBusinesses('csv')" class="export-btn">Export Businesses (CSV)</button>
        <button @click="exportBusinesses('xlsx')" class="export-btn">Export Businesses (Excel)</button>
        <button @click="exportUsers('csv')" class="export-btn">Export Users (CSV)</button>
        <button @click="exportUsers('xlsx')" class="export-btn">Export Users (Excel)</button>
      </div>
      <div v-if="exporting" class="loading">Exporting...</div>
    </div>

    <!-- Support & Communication Section -->
    <div class="dashboard-section">
      <h2>Support & Communication</h2>
      <div class="support-controls">
        <input v-model="ticketSearch" placeholder="Search tickets by business, user, or subject..." class="ticket-search" />
      </div>
      <div v-if="loadingTickets" class="loading">Loading tickets...</div>
      <div v-else-if="filteredTickets.length === 0" class="empty">No support tickets found.</div>
      <table v-else class="ticket-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Business</th>
            <th>User</th>
            <th>Subject</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="ticket in filteredTickets" :key="ticket.id">
            <td>{{ ticket.created_at }}</td>
            <td>{{ ticket.business_name }}</td>
            <td>{{ ticket.user_name }}</td>
            <td>{{ ticket.subject }}</td>
            <td><span :class="['status', ticket.status]">{{ ticket.status }}</span></td>
            <td>
              <button @click="viewTicket(ticket)" class="view-btn">View</button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Ticket Modal -->
      <div v-if="showTicketModal" class="ticket-modal-overlay">
        <div class="ticket-modal">
          <h3>Ticket: {{ selectedTicket.subject }}</h3>
          <p><strong>From:</strong> {{ selectedTicket.user_name }} ({{ selectedTicket.business_name }})</p>
          <p><strong>Date:</strong> {{ selectedTicket.created_at }}</p>
          <p><strong>Status:</strong> <span :class="['status', selectedTicket.status]">{{ selectedTicket.status }}</span></p>
          <div class="ticket-message">{{ selectedTicket.message }}</div>
          <textarea v-model="ticketReply" class="ticket-reply-input" placeholder="Write a reply..."></textarea>
          <div class="ticket-modal-actions">
            <button @click="sendReply" class="send-reply-btn">Send Reply</button>
            <button @click="closeTicketModal" class="close-modal-btn">Close</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Subscription & Billing Section -->
    <div class="dashboard-section">
      <h2>Subscription & Billing</h2>
      <div class="subscription-controls">
        <input v-model="subscriptionSearch" placeholder="Search by business or plan..." class="subscription-search" />
      </div>
      <div v-if="loadingSubscriptions" class="loading">Loading subscriptions...</div>
      <div v-else-if="filteredSubscriptions.length === 0" class="empty">No subscriptions found.</div>
      <table v-else class="subscription-table">
        <thead>
          <tr>
            <th>Business</th>
            <th>Plan</th>
            <th>Status</th>
            <th>Renewal Date</th>
            <th>Features</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="sub in filteredSubscriptions" :key="sub.id">
            <td>{{ sub.business_name }}</td>
            <td>{{ sub.plan }}</td>
            <td><span :class="['status', sub.status]">{{ sub.status }}</span></td>
            <td>{{ sub.renewal_date }}</td>
            <td>{{ sub.features.join(', ') }}</td>
            <td>
              <button @click="changePlan(sub)" class="change-plan-btn">Change Plan</button>
              <button v-if="sub.status === 'active'" @click="deactivateSubscription(sub.id)" class="deactivate-btn">Deactivate</button>
              <button v-else-if="sub.status === 'inactive'" @click="activateSubscription(sub.id)" class="activate-btn">Activate</button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Change Plan Modal -->
      <div v-if="showPlanModal" class="plan-modal-overlay">
        <div class="plan-modal">
          <h3>Change Plan for {{ selectedSubscription.business_name }}</h3>
          <select v-model="newPlan" class="plan-select">
            <option v-for="plan in availablePlans" :key="plan" :value="plan">{{ plan }}</option>
          </select>
          <div class="plan-modal-actions">
            <button @click="savePlanChange" class="save-plan-btn">Save</button>
            <button @click="closePlanModal" class="close-modal-btn">Cancel</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Impersonate Business Admin Section -->
    <div class="dashboard-section">
      <h2>Impersonate Business Admin</h2>
      <div class="impersonate-controls">
        <input v-model="impersonateSearch" placeholder="Search business by name..." class="impersonate-search" />
      </div>
      <div v-if="loadingBusinesses" class="loading">Loading businesses...</div>
      <div v-else-if="filteredBusinesses.length === 0" class="empty">No businesses found.</div>
      <table v-else class="impersonate-table">
        <thead>
          <tr>
            <th>Business Name</th>
            <th>Admin Name</th>
            <th>Email</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="biz in filteredBusinesses" :key="biz.id">
            <td>{{ biz.name }}</td>
            <td>{{ biz.admin_name }}</td>
            <td>{{ biz.admin_email }}</td>
            <td>
              <button @click="impersonateAdmin(biz.id)" class="impersonate-btn">Impersonate</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { computed } from 'vue'
// Impersonate Business Admin State
const businesses = ref([])
const loadingBusinesses = ref(true)
const impersonateSearch = ref('')

const filteredBusinesses = computed(() => {
  if (!impersonateSearch.value.trim()) return businesses.value
  const q = impersonateSearch.value.trim().toLowerCase()
  return businesses.value.filter(b =>
    (b.name && b.name.toLowerCase().includes(q))
  )
})

async function fetchBusinesses() {
  loadingBusinesses.value = true
  try {
    const res = await axios.get('/api/superuser/businesses')
    businesses.value = res.data
  } catch (e) {
    businesses.value = []
  } finally {
    loadingBusinesses.value = false
  }
}

async function impersonateAdmin(businessId) {
  // This should call the backend to set impersonation session/token
  await axios.post(`/api/superuser/impersonate/${businessId}`)
  // Redirect to dashboard as that business admin
  window.location.href = '/'
}
// Subscription & Billing State
const subscriptions = ref([])
const loadingSubscriptions = ref(true)
const subscriptionSearch = ref('')
const showPlanModal = ref(false)
const selectedSubscription = ref({})
const newPlan = ref('')
const availablePlans = ref(['Basic', 'Standard', 'Premium'])

const filteredSubscriptions = computed(() => {
  if (!subscriptionSearch.value.trim()) return subscriptions.value
  const q = subscriptionSearch.value.trim().toLowerCase()
  return subscriptions.value.filter(s =>
    (s.business_name && s.business_name.toLowerCase().includes(q)) ||
    (s.plan && s.plan.toLowerCase().includes(q))
  )
})

async function fetchSubscriptions() {
  loadingSubscriptions.value = true
  try {
    const res = await axios.get('/api/superuser/subscriptions')
    subscriptions.value = res.data
  } catch (e) {
    subscriptions.value = []
  } finally {
    loadingSubscriptions.value = false
  }
}

function changePlan(sub) {
  selectedSubscription.value = sub
  newPlan.value = sub.plan
  showPlanModal.value = true
}
function closePlanModal() {
  showPlanModal.value = false
}
async function savePlanChange() {
  await axios.post(`/api/superuser/subscriptions/${selectedSubscription.value.id}/change-plan`, { plan: newPlan.value })
  showPlanModal.value = false
  fetchSubscriptions()
}
async function deactivateSubscription(id) {
  await axios.post(`/api/superuser/subscriptions/${id}/deactivate`)
  fetchSubscriptions()
}
async function activateSubscription(id) {
  await axios.post(`/api/superuser/subscriptions/${id}/activate`)
  fetchSubscriptions()
}
// Support & Communication State
const tickets = ref([])
const loadingTickets = ref(true)
const ticketSearch = ref('')
const showTicketModal = ref(false)
const selectedTicket = ref({})
const ticketReply = ref('')

const filteredTickets = computed(() => {
  if (!ticketSearch.value.trim()) return tickets.value
  const q = ticketSearch.value.trim().toLowerCase()
  return tickets.value.filter(t =>
    (t.business_name && t.business_name.toLowerCase().includes(q)) ||
    (t.user_name && t.user_name.toLowerCase().includes(q)) ||
    (t.subject && t.subject.toLowerCase().includes(q))
  )
})

async function fetchTickets() {
  loadingTickets.value = true
  try {
    const res = await axios.get('/api/superuser/support-tickets')
    tickets.value = res.data
  } catch (e) {
    tickets.value = []
  } finally {
    loadingTickets.value = false
  }
}

function viewTicket(ticket) {
  selectedTicket.value = ticket
  ticketReply.value = ''
  showTicketModal.value = true
}
function closeTicketModal() {
  showTicketModal.value = false
}
async function sendReply() {
  if (!ticketReply.value.trim()) return
  await axios.post(`/api/superuser/support-tickets/${selectedTicket.value.id}/reply`, { message: ticketReply.value })
  ticketReply.value = ''
  showTicketModal.value = false
  fetchTickets()
}
// Data Export State
const exporting = ref(false)

async function exportBusinesses(format) {
  exporting.value = true
  try {
    const res = await axios.get(`/api/superuser/export/businesses?format=${format}`, { responseType: 'blob' })
    downloadFile(res.data, `businesses.${format}`)
  } finally {
    exporting.value = false
  }
}
async function exportUsers(format) {
  exporting.value = true
  try {
    const res = await axios.get(`/api/superuser/export/users?format=${format}`, { responseType: 'blob' })
    downloadFile(res.data, `users.${format}`)
  } finally {
    exporting.value = false
  }
}
function downloadFile(blob, filename) {
  const url = window.URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = filename
  document.body.appendChild(a)
  a.click()
  a.remove()
  window.URL.revokeObjectURL(url)
}
// Global Settings State
const categories = ref([])
const newCategory = ref('')
const features = ref([
  { key: 'multi_currency', name: 'Multi-Currency Support', enabled: false },
  { key: 'advanced_reports', name: 'Advanced Reports', enabled: false },
  { key: 'api_access', name: 'API Access', enabled: false }
])
const announcement = ref('')

async function fetchCategories() {
  try {
    const res = await axios.get('/api/superuser/business-categories')
    categories.value = res.data
  } catch (e) {
    categories.value = []
  }
}
async function addCategory() {
  if (!newCategory.value.trim()) return
  await axios.post('/api/superuser/business-categories', { name: newCategory.value })
  newCategory.value = ''
  fetchCategories()
}
async function removeCategory(cat) {
  await axios.delete(`/api/superuser/business-categories/${encodeURIComponent(cat)}`)
  fetchCategories()
}
async function toggleFeature(feature) {
  await axios.post('/api/superuser/feature-toggles', { key: feature.key, enabled: feature.enabled })
}
async function fetchFeatures() {
  try {
    const res = await axios.get('/api/superuser/feature-toggles')
    features.value = res.data
  } catch (e) {}
}
async function fetchAnnouncement() {
  try {
    const res = await axios.get('/api/superuser/announcement')
    announcement.value = res.data
  } catch (e) {}
}
async function saveAnnouncement() {
  await axios.post('/api/superuser/announcement', { text: announcement.value })
}
// Audit Logs State
const logs = ref([])
const loadingLogs = ref(true)
const logSearch = ref('')

const filteredLogs = computed(() => {
  if (!logSearch.value.trim()) return logs.value
  const q = logSearch.value.trim().toLowerCase()
  return logs.value.filter(l =>
    (l.business_name && l.business_name.toLowerCase().includes(q)) ||
    (l.user_name && l.user_name.toLowerCase().includes(q)) ||
    (l.action && l.action.toLowerCase().includes(q))
  )
})

async function fetchLogs() {
  loadingLogs.value = true
  try {
    const res = await axios.get('/api/superuser/audit-logs')
    logs.value = res.data
  } catch (e) {
    logs.value = []
  } finally {
    loadingLogs.value = false
  }
}

// User Management State
const users = ref([])
const loadingUsers = ref(true)
const userSearch = ref('')

const filteredUsers = computed(() => {
  if (!userSearch.value.trim()) return users.value
  const q = userSearch.value.trim().toLowerCase()
  return users.value.filter(u =>
    (u.name && u.name.toLowerCase().includes(q)) ||
    (u.email && u.email.toLowerCase().includes(q)) ||
    (u.business_name && u.business_name.toLowerCase().includes(q))
  )
})

async function fetchUsers() {
  loadingUsers.value = true
  try {
    const res = await axios.get('/api/superuser/users')
    users.value = res.data
  } catch (e) {
    users.value = []
  } finally {
    loadingUsers.value = false
  }
}

async function deactivateUser(id) {
  await axios.post(`/api/superuser/users/${id}/deactivate`)
  fetchUsers()
}
async function activateUser(id) {
  await axios.post(`/api/superuser/users/${id}/activate`)
  fetchUsers()
}

const loading = ref(true)
const pendingCompanies = ref([])
const allCompanies = ref([])

async function fetchCompanies() {
  loading.value = true
  try {
    // Replace with your backend endpoints
    const [pendingRes, allRes] = await Promise.all([
      axios.get('/api/superuser/companies?status=pending'),
      axios.get('/api/superuser/companies')
    ])
    pendingCompanies.value = pendingRes.data
    allCompanies.value = allRes.data
  } catch (e) {
    // Handle error
    pendingCompanies.value = []
    allCompanies.value = []
  } finally {
    loading.value = false
  }
}

async function approveCompany(id) {
  await axios.post(`/api/superuser/companies/${id}/approve`)
  fetchCompanies()
}
async function rejectCompany(id) {
  await axios.post(`/api/superuser/companies/${id}/reject`)
  fetchCompanies()
}
async function deactivateCompany(id) {
  await axios.post(`/api/superuser/companies/${id}/deactivate`)
  fetchCompanies()
}
async function activateCompany(id) {
  await axios.post(`/api/superuser/companies/${id}/activate`)
  fetchCompanies()
}

onMounted(fetchCompanies)
onMounted(fetchUsers)
onMounted(fetchLogs)
onMounted(() => {
  fetchCategories()
  fetchFeatures()
  fetchAnnouncement()
  fetchTickets()
  fetchSubscriptions()
  fetchBusinesses()
})
</script>

<style scoped>
.superuser-dashboard-container {
  max-width: 1100px;
  margin: 2rem auto;
  background: #fff;
  border-radius: 18px;
  box-shadow: 0 8px 32px rgba(102,126,234,0.08);
  padding: 2.5rem 2rem;
}
.dashboard-header {
  text-align: center;
  margin-bottom: 2.5rem;
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
  font-size: 2rem;
  color: white;
}
.dashboard-title {
  font-size: 2rem;
  font-weight: 700;
  color: #2d3748;
  margin: 0 0 0.25rem 0;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
.dashboard-subtitle {
  color: #718096;
  margin: 0;
  font-size: 1.1rem;
}
.dashboard-section {
  margin-bottom: 2.5rem;
}
.company-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 1rem;
  background: #f7fafc;
  border-radius: 10px;
  overflow: hidden;
}
.company-table th, .company-table td {
  padding: 0.85rem 1rem;
  text-align: left;
}
.company-table th {
  background: #667eea;
  color: #fff;
  font-weight: 600;
}
.company-table tr:nth-child(even) {
  background: #e9eaf7;
}
.status {
  padding: 0.35em 0.9em;
  border-radius: 8px;
  font-size: 0.95em;
  font-weight: 600;
  text-transform: capitalize;
}
.status.pending {
  background: #fff5e6;
  color: #d69e2e;
}
.status.active {
  background: #e6fffa;
  color: #2c7a7b;
}
.status.inactive {
  background: #fff5f5;
  color: #c53030;
}
.approve-btn, .reject-btn, .deactivate-btn, .activate-btn {
  border: none;
  border-radius: 8px;
  padding: 0.5em 1.1em;
  font-weight: 600;
  cursor: pointer;
  margin-right: 0.5em;
  transition: background 0.2s;
}
.approve-btn {
  background: linear-gradient(135deg, #48bb78, #38a169);
  color: #fff;
}
.reject-btn {
  background: linear-gradient(135deg, #e53e3e, #c53030);
  color: #fff;
}
.deactivate-btn {
  background: linear-gradient(135deg, #f6ad55, #ed8936);
  color: #fff;
}
.activate-btn {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: #fff;
}
.loading {
  color: #667eea;
  font-weight: 500;
  padding: 1.5rem 0;
  text-align: center;
}
.empty {
  color: #a0aec0;
  font-size: 1.1rem;
  padding: 1.5rem 0;
  text-align: center;
}
.impersonate-controls {
  margin-bottom: 1rem;
}
.impersonate-search {
  padding: 0.5rem 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
  width: 100%;
  max-width: 350px;
}
.impersonate-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 1rem;
  background: #f7fafc;
  border-radius: 10px;
  overflow: hidden;
}
.impersonate-table th, .impersonate-table td {
  padding: 0.7rem 1rem;
  text-align: left;
}
.impersonate-table th {
  background: #764ba2;
  color: #fff;
  font-weight: 600;
}
.impersonate-table tr:nth-child(even) {
  background: #e9eaf7;
}
.impersonate-btn {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 0.4rem 1.1rem;
  font-weight: 600;
  cursor: pointer;
}

.subscription-controls {
  margin-bottom: 1rem;
}
.subscription-search {
  padding: 0.5rem 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
  width: 100%;
  max-width: 350px;
}
.subscription-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 1rem;
  background: #f7fafc;
  border-radius: 10px;
  overflow: hidden;
}
.subscription-table th, .subscription-table td {
  padding: 0.7rem 1rem;
  text-align: left;
}
.subscription-table th {
  background: #38a169;
  color: #fff;
  font-weight: 600;
}
.subscription-table tr:nth-child(even) {
  background: #e9eaf7;
}
.change-plan-btn {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 0.4rem 1.1rem;
  font-weight: 600;
  cursor: pointer;
  margin-right: 0.5em;
}
.plan-modal-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.25);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}
.plan-modal {
  background: #fff;
  border-radius: 16px;
  padding: 2rem 2.5rem;
  max-width: 400px;
  width: 100%;
  box-shadow: 0 8px 32px rgba(102,126,234,0.12);
}
.plan-select {
  width: 100%;
  padding: 0.7rem 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
  margin-bottom: 1rem;
}
.plan-modal-actions {
  display: flex;
  gap: 0.7rem;
  justify-content: flex-end;
}
.save-plan-btn {
  background: linear-gradient(135deg, #48bb78, #38a169);
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 0.5rem 1.2rem;
  font-weight: 600;
  cursor: pointer;
}
.close-modal-btn {
  background: #e2e8f0;
  color: #2d3748;
  border: none;
  border-radius: 8px;
  padding: 0.5rem 1.2rem;
  font-weight: 600;
  cursor: pointer;
}

.support-controls {
  margin-bottom: 1rem;
}
.ticket-search {
  padding: 0.5rem 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
  width: 100%;
  max-width: 350px;
}
.ticket-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 1rem;
  background: #f7fafc;
  border-radius: 10px;
  overflow: hidden;
}
.ticket-table th, .ticket-table td {
  padding: 0.7rem 1rem;
  text-align: left;
}
.ticket-table th {
  background: #e53e3e;
  color: #fff;
  font-weight: 600;
}
.ticket-table tr:nth-child(even) {
  background: #e9eaf7;
}
.view-btn {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 0.4rem 1.1rem;
  font-weight: 600;
  cursor: pointer;
  margin-right: 0.5em;
}
.ticket-modal-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.25);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}
.ticket-modal {
  background: #fff;
  border-radius: 16px;
  padding: 2rem 2.5rem;
  max-width: 480px;
  width: 100%;
  box-shadow: 0 8px 32px rgba(102,126,234,0.12);
}
.ticket-message {
  background: #f7fafc;
  border-radius: 8px;
  padding: 1rem;
  margin: 1rem 0;
  color: #2d3748;
}
.ticket-reply-input {
  width: 100%;
  min-height: 60px;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  padding: 0.7rem 1rem;
  font-size: 1rem;
  margin-bottom: 0.7rem;
}
.ticket-modal-actions {
  display: flex;
  gap: 0.7rem;
  justify-content: flex-end;
}
.send-reply-btn {
  background: linear-gradient(135deg, #48bb78, #38a169);
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 0.5rem 1.2rem;
  font-weight: 600;
  cursor: pointer;
}
.close-modal-btn {
  background: #e2e8f0;
  color: #2d3748;
  border: none;
  border-radius: 8px;
  padding: 0.5rem 1.2rem;
  font-weight: 600;
  cursor: pointer;
}

.export-controls {
  margin-bottom: 1.5rem;
  display: flex;
  flex-wrap: wrap;
  gap: 0.7rem;
}
.export-btn {
  background: linear-gradient(135deg, #764ba2, #667eea);
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 0.5rem 1.2rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}
.export-btn:hover {
  background: linear-gradient(135deg, #667eea, #764ba2);
}

.global-settings-controls {
  margin-bottom: 1rem;
  display: flex;
  gap: 0.5rem;
}
.category-input {
  padding: 0.5rem 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
}
.add-category-btn {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 0.5rem 1.2rem;
  font-weight: 600;
  cursor: pointer;
}
.category-list {
  margin-bottom: 1.5rem;
}
.category-chip {
  display: inline-block;
  background: #e9eaf7;
  color: #764ba2;
  border-radius: 16px;
  padding: 0.4em 1em 0.4em 0.9em;
  margin: 0 0.5em 0.5em 0;
  font-size: 0.98em;
  position: relative;
}
.remove-category-btn {
  background: none;
  border: none;
  color: #c53030;
  font-size: 1.1em;
  margin-left: 0.5em;
  cursor: pointer;
}
.feature-toggles {
  margin-bottom: 1.5rem;
}
.feature-toggle-row {
  margin-bottom: 0.7rem;
}
.announcement-section {
  margin-bottom: 1.5rem;
}
.announcement-input {
  width: 100%;
  min-height: 60px;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  padding: 0.7rem 1rem;
  font-size: 1rem;
  margin-bottom: 0.7rem;
}
.save-announcement-btn {
  background: linear-gradient(135deg, #48bb78, #38a169);
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 0.5rem 1.2rem;
  font-weight: 600;
  cursor: pointer;
}

.audit-log-controls {
  margin-bottom: 1rem;
}
.log-search {
  padding: 0.5rem 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
  width: 100%;
  max-width: 350px;
}
.log-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 1rem;
  background: #f7fafc;
  border-radius: 10px;
  overflow: hidden;
}
.log-table th, .log-table td {
  padding: 0.7rem 1rem;
  text-align: left;
}
.log-table th {
  background: #48bb78;
  color: #fff;
  font-weight: 600;
}
.log-table tr:nth-child(even) {
  background: #e9eaf7;
}

.user-management-controls {
  margin-bottom: 1rem;
}
.user-search {
  padding: 0.5rem 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
  width: 100%;
  max-width: 350px;
}
.user-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 1rem;
  background: #f7fafc;
  border-radius: 10px;
  overflow: hidden;
}
.user-table th, .user-table td {
  padding: 0.7rem 1rem;
  text-align: left;
}
.user-table th {
  background: #764ba2;
  color: #fff;
  font-weight: 600;
}
.user-table tr:nth-child(even) {
  background: #e9eaf7;
}
</style>

<style>
.super-user-dashboard {
  padding: 20px;
  background-color: #f0f0f0;
}
</style>
