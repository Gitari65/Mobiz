<template>
  <div class="su-support">
    <header class="su-header">
      <h1>Support & Communication</h1>
      <p class="muted">View tickets from all businesses and reply / resolve them.</p>
    </header>

    <section class="controls">
      <input v-model="q" type="search" placeholder="Search users to message..." class="search" @input="fetchUsers" />
    </section>

    <section v-if="loading" class="loading">Loading...</section>
    <section v-else>
      <table class="users-table">
        <thead>
          <tr><th>Name</th><th>Email</th><th>Role</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <tr v-for="user in filteredUsers" :key="user.id">
            <td>{{ user.name }}</td>
            <td>{{ user.email }}</td>
            <td>{{ formatRole(user.role?.name) }}</td>
            <td>
              <button @click="openChatWithUser(user)" class="btn-message">
                <i class="fas fa-comment"></i> Message
              </button>
            </td>
          </tr>
          <tr v-if="filteredUsers.length === 0">
            <td colspan="4" class="empty">No users found</td>
          </tr>
        </tbody>
      </table>
    </section>

    <!-- Chat Popup -->
    <ChatSidePopup
      :isOpen="chatPopupOpen"
      :chatId="currentChatId"
      :recipientId="currentRecipientId"
      :recipientName="currentRecipientName"
      @close="closeChatPopup"
      @message-sent="onMessageSent"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import ChatSidePopup from '../../components/ChatSidePopup.vue'

const users = ref([])
const q = ref('')
const chatPopupOpen = ref(false)
const currentChatId = ref(null)
const currentRecipientId = ref(null)
const currentRecipientName = ref('')

// Computed
const filteredUsers = computed(() => {
  if (!q.value.trim()) return users.value
  const query = q.value.trim().toLowerCase()
  return users.value.filter(u =>
    (u.name && u.name.toLowerCase().includes(query)) ||
    (u.email && u.email.toLowerCase().includes(query))
  )
})

// Methods
const formatRole = (role) => {
  if (!role) return '-'
  return role.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')
}

async function fetchUsers() {
  loading.value = true
  try {
    const res = await axios.get('/api/super/users', { params: { q: q.value, per_page: 50 } })
    users.value = res.data.data || []
  } catch (e) {
    users.value = []
  } finally {
    loading.value = false
  }
}

async function openChatWithUser(user) {
  try {
    // Create or get existing chat
    const res = await axios.post('/api/super/chats', {
      recipient_id: user.id,
      subject: `Support Chat with ${user.name}`
    })
    currentChatId.value = res.data.id
    currentRecipientId.value = user.id
    currentRecipientName.value = user.name
    chatPopupOpen.value = true
  } catch (e) {
    console.error('Failed to open chat', e)
  }
}

function closeChatPopup() {
  chatPopupOpen.value = false
  setTimeout(() => {
    currentChatId.value = null
    currentRecipientId.value = null
    currentRecipientName.value = ''
  }, 300)
}

function onMessageSent() {
  // Optional: refresh notifications or do any other action
  console.log('Message sent')
}

onMounted(() => {
  fetchUsers()
})
</script>

<style scoped>
.su-support { max-width: 1100px; margin: 2rem auto; background:#fff; padding:1.5rem; border-radius:12px; box-shadow:0 10px 30px rgba(2,6,23,0.06); }
.su-header h1 { margin:0; font-size:1.25rem }
.muted { color:#6b7280; margin:0 0 1rem 0 }
.controls { margin-bottom:1rem }
.search { width:100%; max-width:420px; padding:0.5rem 0.75rem; border:1px solid #e6e6f0; border-radius:8px }
.users-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
.users-table th, .users-table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; }
.users-table th { background: #f9fafb; font-weight: 600; }
.btn-message { background: #667eea; color: #fff; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; font-weight: 500; transition: all 0.2s ease; }
.btn-message:hover { background: #5a67d8; transform: translateY(-1px); }
.empty { text-align: center; color: #a0aec0; }
</style>
