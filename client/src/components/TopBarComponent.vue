<template>
  <header class="topbar" v-if="!isAuthRoute">
    <!-- Left: Page title -->
    <div class="topbar-left">
      <div class="page-info">
        <h2 class="page-title">{{ pageTitle }}</h2>
        <span class="page-date">{{ currentDate }}</span>
      </div>
    </div>

    <!-- Right: Actions -->
    <div class="topbar-right">

      <!-- Notifications Bell -->
      <div class="topbar-item" ref="notifRef">
        <button class="icon-btn" @click="toggleNotifs" :class="{ active: showNotifs }">
          <i class="fas fa-bell"></i>
          <span v-if="unreadNotifs > 0" class="badge">{{ unreadNotifs > 9 ? '9+' : unreadNotifs }}</span>
        </button>

        <transition name="dropdown">
          <div v-if="showNotifs" class="dropdown notif-dropdown" @click.stop>
            <div class="dropdown-header">
              <span class="dropdown-title"><i class="fas fa-bell"></i> Notifications</span>
              <button v-if="unreadNotifs > 0" class="btn-mark-all" @click="markAllRead">Mark all read</button>
            </div>

            <div class="notif-list" v-if="notifications.length">
              <div
                v-for="notif in notifications"
                :key="notif.id"
                class="notif-item"
                :class="{ unread: !notif.read }"
                @click="markRead(notif)"
              >
                <div class="notif-icon" :class="'notif-' + notif.type">
                  <i :class="notifIcon(notif.type)"></i>
                </div>
                <div class="notif-body">
                  <p class="notif-text">{{ notif.message }}</p>
                  <span class="notif-time">{{ notif.time }}</span>
                </div>
                <div v-if="!notif.read" class="notif-dot"></div>
              </div>
            </div>

            <div v-else class="notif-empty">
              <i class="fas fa-check-circle"></i>
              <p>All caught up!</p>
            </div>

            <div class="dropdown-footer">
              <router-link to="/reports" @click="showNotifs = false">View all activity</router-link>
            </div>
          </div>
        </transition>
      </div>

      <!-- Divider -->
      <div class="topbar-divider"></div>

      <!-- Profile -->
      <div class="topbar-item" ref="profileRef">
        <button class="profile-btn" @click="toggleProfile" :class="{ active: showProfile }">
          <div class="avatar">{{ userInitials }}</div>
          <div class="profile-info">
            <span class="profile-name">{{ userName }}</span>
            <span class="profile-role">{{ userRoleLabel }}</span>
          </div>
          <i class="fas fa-chevron-down chevron" :class="{ rotated: showProfile }"></i>
        </button>

        <transition name="dropdown">
          <div v-if="showProfile" class="dropdown profile-dropdown" @click.stop>
            <div class="profile-header">
              <div class="profile-avatar-lg">{{ userInitials }}</div>
              <div>
                <p class="profile-fullname">{{ userName }}</p>
                <p class="profile-email">{{ userEmail }}</p>
                <span class="role-chip">{{ userRoleLabel }}</span>
              </div>
            </div>

            <div class="profile-menu">
              <router-link class="profile-menu-item" to="/profile" @click="showProfile = false">
                <i class="fas fa-user-circle"></i>
                <span>My Profile</span>
              </router-link>
              <router-link class="profile-menu-item" to="/settings" @click="showProfile = false">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
              </router-link>
              <router-link v-if="isAdmin || isSuperUser" class="profile-menu-item" to="/admin-customization" @click="showProfile = false">
                <i class="fas fa-sliders-h"></i>
                <span>Admin Panel</span>
              </router-link>
            </div>

            <div class="profile-divider"></div>

            <button class="profile-logout" @click="handleLogout">
              <i class="fas fa-sign-out-alt"></i>
              <span>Sign Out</span>
            </button>
          </div>
        </transition>
      </div>

    </div>
  </header>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

const route  = useRoute()
const router = useRouter()

// ── Auth guard ──────────────────────────────────────────────────────────────
const isAuthRoute = computed(() => route.meta?.layout === 'auth' || route.path === '/login')

// ── User data ───────────────────────────────────────────────────────────────
const userName    = ref('')
const userEmail   = ref('')
const userRole    = ref('')
const isAdmin     = ref(false)
const isSuperUser = ref(false)

const userInitials = computed(() => {
  if (!userName.value) return '?'
  return userName.value.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2)
})

const userRoleLabel = computed(() => {
  const map = {
    superuser: 'Super User', super_user: 'Super User',
    admin: 'Administrator', administrator: 'Administrator',
    cashier: 'Cashier', user: 'User'
  }
  return map[userRole.value] || (userRole.value ? userRole.value.charAt(0).toUpperCase() + userRole.value.slice(1) : 'User')
})

function loadUser() {
  try {
    const data = JSON.parse(localStorage.getItem('userData'))
    if (data) {
      userName.value  = data.name  || ''
      userEmail.value = data.email || ''
      const role = (data.role?.name || '').toLowerCase()
      userRole.value    = role
      isAdmin.value     = ['admin', 'administrator'].includes(role)
      isSuperUser.value = ['superuser', 'super_user', 'super user'].includes(role)
    }
  } catch { /* silent */ }
}

// ── Page title ───────────────────────────────────────────────────────────────
const pageTitles = {
  '/dashboard':           'Dashboard',
  '/sales':               'POS Sales',
  '/products':            'Products',
  '/inventory':           'Inventory',
  '/reports':             'Reports',
  '/expenses':            'Expenses',
  '/settings':            'Settings',
  '/accounts-management': 'Accounts Management',
  '/manage-users':        'Manage Users',
  '/promotions':          'Promotions',
  '/warehouses':          'Warehouses',
  '/profile':             'My Profile',
  '/superuser':           'Super User Dashboard',
  '/user-management':     'User Management',
  '/audit-logs':          'Audit Logs',
  '/admin-customization': 'Admin Customization',
}

const pageTitle = computed(() => {
  const path = route.path
  for (const [key, val] of Object.entries(pageTitles)) {
    if (path.startsWith(key)) return val
  }
  return 'Mobiz POS'
})

const currentDate = computed(() =>
  new Date().toLocaleDateString('en-KE', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
)

// ── Notifications (API-backed) ────────────────────────────────────────────────
const showNotifs    = ref(false)
const notifications = ref([])
const unreadNotifs  = computed(() => notifications.value.filter(n => !n.read).length)
let pollTimer = null

async function fetchNotifications() {
  if (isAuthRoute.value) return
  try {
    const res = await axios.get('/api/notifications')
    notifications.value = res.data.notifications || []
  } catch { /* silent — user may not be logged in yet */ }
}

async function markRead(notif) {
  if (notif.read) return
  notif.read = true // optimistic
  try {
    await axios.post(`/api/notifications/${notif.id}/read`)
  } catch { notif.read = false }
}

async function markAllRead() {
  notifications.value.forEach(n => (n.read = true)) // optimistic
  try {
    await axios.post('/api/notifications/read-all')
  } catch { fetchNotifications() }
}

function notifIcon(type) {
  return { info: 'fas fa-info-circle', warning: 'fas fa-exclamation-triangle', success: 'fas fa-check-circle', error: 'fas fa-times-circle' }[type] || 'fas fa-bell'
}

function toggleNotifs() {
  showNotifs.value = !showNotifs.value
  if (showNotifs.value) {
    showProfile.value = false
    fetchNotifications()
  }
}

// ── Profile dropdown ──────────────────────────────────────────────────────────
const showProfile = ref(false)

function toggleProfile() {
  showProfile.value = !showProfile.value
  if (showProfile.value) showNotifs.value = false
}

async function handleLogout() {
  clearInterval(pollTimer)
  try { await axios.post('/api/logout').catch(() => {}) } finally {
    localStorage.clear()
    router.push('/login')
  }
}

// ── Click-outside ─────────────────────────────────────────────────────────────
const notifRef   = ref(null)
const profileRef = ref(null)

function handleClickOutside(e) {
  if (notifRef.value   && !notifRef.value.contains(e.target))   showNotifs.value  = false
  if (profileRef.value && !profileRef.value.contains(e.target)) showProfile.value = false
}

onMounted(() => {
  loadUser()
  fetchNotifications()
  // Poll every 60 s for new notifications
  pollTimer = setInterval(fetchNotifications, 60_000)
  document.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
  clearInterval(pollTimer)
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
/* ═══════════════════════════════════ TOP BAR ══════════════════════════════════ */
.topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 1.5rem;
  height: 64px;
  background: rgba(255, 255, 255, 0.97);
  backdrop-filter: blur(20px);
  border-bottom: 1px solid rgba(102, 126, 234, 0.12);
  box-shadow: 0 2px 20px rgba(0, 0, 0, 0.06);
  position: sticky;
  top: 0;
  z-index: 500;
  flex-shrink: 0;
}

/* Left */
.topbar-left { display: flex; align-items: center; gap: 1rem; }
.page-title  { margin: 0; font-size: 1.1rem; font-weight: 700; color: #1a202c; line-height: 1.2; }
.page-date   { font-size: 0.72rem; color: #a0aec0; display: block; margin-top: 1px; }

/* Right */
.topbar-right   { display: flex; align-items: center; gap: 0.5rem; }
.topbar-divider { width: 1px; height: 32px; background: #e2e8f0; margin: 0 0.5rem; }
.topbar-item    { position: relative; }

/* Bell button */
.icon-btn {
  position: relative;
  background: transparent;
  border: none;
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 1rem;
  color: #718096;
  transition: all 0.2s ease;
}
.icon-btn:hover, .icon-btn.active {
  background: linear-gradient(135deg, rgba(102,126,234,0.1), rgba(118,75,162,0.1));
  color: #667eea;
}

.badge {
  position: absolute;
  top: 5px; right: 5px;
  background: linear-gradient(135deg, #e53e3e, #c53030);
  color: white;
  font-size: 0.58rem;
  font-weight: 700;
  min-width: 16px;
  height: 16px;
  border-radius: 8px;
  padding: 0 3px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid white;
  line-height: 1;
}

/* Profile button */
.profile-btn {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  background: transparent;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 0.4rem 0.75rem 0.4rem 0.4rem;
  cursor: pointer;
  transition: all 0.2s ease;
}
.profile-btn:hover, .profile-btn.active {
  background: linear-gradient(135deg, rgba(102,126,234,0.08), rgba(118,75,162,0.08));
  border-color: rgba(102,126,234,0.3);
}

.avatar {
  width: 32px; height: 32px;
  border-radius: 8px;
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  font-size: 0.72rem;
  font-weight: 700;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}

.profile-info { display: flex; flex-direction: column; text-align: left; line-height: 1.2; }
.profile-name { font-size: 0.82rem; font-weight: 600; color: #2d3748; white-space: nowrap; max-width: 120px; overflow: hidden; text-overflow: ellipsis; }
.profile-role { font-size: 0.68rem; color: #a0aec0; }
.chevron      { font-size: 0.62rem; color: #a0aec0; transition: transform 0.2s ease; }
.chevron.rotated { transform: rotate(180deg); }

/* ═══════════════════════════════ SHARED DROPDOWN ═════════════════════════════ */
.dropdown {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  background: white;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.14);
  border: 1px solid rgba(102,126,234,0.1);
  z-index: 999;
  overflow: hidden;
}

.dropdown-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem 0.75rem;
  border-bottom: 1px solid #f0f4f8;
}
.dropdown-title { font-size: 0.85rem; font-weight: 700; color: #2d3748; display: flex; align-items: center; gap: 0.5rem; }
.dropdown-title i { color: #667eea; }

.dropdown-footer { padding: 0.7rem 1.25rem; border-top: 1px solid #f0f4f8; text-align: center; }
.dropdown-footer a { font-size: 0.8rem; color: #667eea; text-decoration: none; font-weight: 600; }
.dropdown-footer a:hover { text-decoration: underline; }

/* Dropdown transition */
.dropdown-enter-active, .dropdown-leave-active { transition: all 0.18s ease; transform-origin: top right; }
.dropdown-enter-from, .dropdown-leave-to { opacity: 0; transform: scale(0.92) translateY(-6px); }

/* ═══════════════════════════════ NOTIFICATIONS ═══════════════════════════════ */
.notif-dropdown { width: 340px; }

.btn-mark-all {
  background: none; border: none;
  font-size: 0.75rem; color: #667eea; cursor: pointer; font-weight: 600;
  padding: 0.2rem 0.5rem; border-radius: 6px; transition: background 0.2s;
}
.btn-mark-all:hover { background: rgba(102,126,234,0.08); }

.notif-list { max-height: 300px; overflow-y: auto; }
.notif-list::-webkit-scrollbar { width: 4px; }
.notif-list::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 4px; }

.notif-item {
  display: flex; align-items: flex-start; gap: 0.75rem;
  padding: 0.85rem 1.25rem; cursor: pointer; position: relative;
  border-bottom: 1px solid #f7fafc; transition: background 0.15s;
}
.notif-item:last-child { border-bottom: none; }
.notif-item:hover { background: #f8fafc; }
.notif-item.unread { background: rgba(102,126,234,0.04); }

.notif-icon {
  width: 36px; height: 36px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.88rem; flex-shrink: 0;
}
.notif-info    { background: rgba(49,130,206,0.12); color: #3182ce; }
.notif-warning { background: rgba(237,137,54,0.12); color: #ed8936; }
.notif-success { background: rgba(72,187,120,0.12); color: #48bb78; }
.notif-error   { background: rgba(229,62,62,0.12);  color: #e53e3e; }

.notif-body { flex: 1; min-width: 0; }
.notif-text { margin: 0; font-size: 0.82rem; color: #2d3748; line-height: 1.4; }
.notif-time { font-size: 0.7rem; color: #a0aec0; margin-top: 0.2rem; display: block; }
.notif-dot  { width: 8px; height: 8px; border-radius: 50%; background: linear-gradient(135deg, #667eea, #764ba2); flex-shrink: 0; margin-top: 5px; }

.notif-empty { padding: 2rem; text-align: center; color: #a0aec0; }
.notif-empty i { font-size: 2rem; color: #48bb78; display: block; margin-bottom: 0.5rem; }
.notif-empty p { margin: 0; font-size: 0.85rem; }

/* ═══════════════════════════════ PROFILE DROPDOWN ════════════════════════════ */
.profile-dropdown { width: 260px; }

.profile-header {
  display: flex; align-items: center; gap: 0.85rem;
  padding: 1.25rem;
  background: linear-gradient(135deg, rgba(102,126,234,0.06), rgba(118,75,162,0.06));
  border-bottom: 1px solid #f0f4f8;
}
.profile-avatar-lg {
  width: 46px; height: 46px; border-radius: 12px;
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white; font-size: 1rem; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; box-shadow: 0 4px 12px rgba(102,126,234,0.4);
}
.profile-fullname { margin: 0; font-size: 0.9rem; font-weight: 700; color: #2d3748; line-height: 1.2; }
.profile-email    { margin: 0.15rem 0 0.4rem; font-size: 0.7rem; color: #a0aec0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 160px; }
.role-chip {
  display: inline-block;
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white; font-size: 0.62rem; font-weight: 700;
  padding: 0.15rem 0.6rem; border-radius: 50px;
  text-transform: uppercase; letter-spacing: 0.5px;
}

.profile-menu { padding: 0.5rem 0; }
.profile-menu-item {
  display: flex; align-items: center; gap: 0.75rem;
  padding: 0.7rem 1.25rem; text-decoration: none;
  color: #4a5568; font-size: 0.85rem; font-weight: 500;
  transition: all 0.15s ease;
}
.profile-menu-item i { width: 16px; text-align: center; color: #a0aec0; font-size: 0.88rem; transition: color 0.15s; }
.profile-menu-item:hover { background: rgba(102,126,234,0.06); color: #667eea; }
.profile-menu-item:hover i { color: #667eea; }

.profile-divider { height: 1px; background: #f0f4f8; margin: 0.25rem 0; }

.profile-logout {
  display: flex; align-items: center; gap: 0.75rem;
  width: 100%; padding: 0.75rem 1.25rem;
  background: none; border: none;
  font-size: 0.85rem; font-weight: 600; color: #e53e3e;
  cursor: pointer; transition: background 0.15s; text-align: left;
}
.profile-logout:hover { background: rgba(229,62,62,0.06); }

/* Responsive */
@media (max-width: 640px) {
  .page-date, .profile-info { display: none; }
  .profile-btn { padding: 0.4rem; border-radius: 10px; }
  .notif-dropdown  { width: 300px; right: -40px; }
  .profile-dropdown { right: -10px; }
}
</style>