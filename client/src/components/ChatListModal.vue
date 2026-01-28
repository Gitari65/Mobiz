<template>
  <div v-if="isOpen" class="chat-list-overlay" @click="closeModal">
    <div class="chat-list-modal" @click.stop>
      <!-- Header -->
      <div class="modal-header">
        <h2 class="modal-title">
          <i class="fas fa-comments"></i> Messages
        </h2>
        <button @click="closeModal" class="btn-close">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <!-- Search -->
      <div class="search-section">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search conversations..."
          class="search-input"
        />
      </div>

      <!-- Chats List -->
      <div class="chats-container">
        <div v-if="loading" class="loading">
          <div class="spinner"></div>
          <p>Loading conversations...</p>
        </div>

        <div v-else-if="filteredChats.length === 0" class="empty">
          <i class="fas fa-inbox"></i>
          <p>No conversations yet</p>
        </div>

        <div v-else>
          <div
            v-for="chat in filteredChats"
            :key="chat.id"
            class="chat-item"
            @click="openChat(chat)"
          >
            <div class="chat-avatar">
              {{ getInitials(getOtherUser(chat).name) }}
            </div>
            <div class="chat-content">
              <div class="chat-header">
                <h3 class="chat-name">{{ getOtherUser(chat).name }}</h3>
                <span class="chat-time">{{ formatTime(chat.updated_at) }}</span>
              </div>
              <p class="chat-preview">
                {{ getLastMessage(chat) }}
              </p>
            </div>
            <div v-if="hasUnreadMessages(chat)" class="unread-indicator"></div>
          </div>
        </div>
      </div>

      <!-- New Chat Button -->
      <div class="modal-footer">
        <button class="btn-new-chat" @click="showNewChatForm = true">
          <i class="fas fa-plus"></i> New Conversation
        </button>
      </div>

      <!-- New Chat Form Modal -->
      <div v-if="showNewChatForm" class="form-overlay" @click="showNewChatForm = false">
        <div class="form-modal" @click.stop>
          <h3>Start New Conversation</h3>
          <div class="form-group">
            <label>Select User</label>
            <select v-model="newChatUserId" class="form-select">
              <option value="">Choose a user...</option>
              <option v-for="user in availableUsers" :key="user.id" :value="user.id">
                {{ user.name }} ({{ user.email }})
              </option>
            </select>
          </div>
          <div class="form-actions">
            <button @click="startNewChat" :disabled="!newChatUserId || startingChat" class="btn-start">
              <i v-if="startingChat" class="fas fa-spinner fa-spin"></i>
              <i v-else class="fas fa-comments"></i>
              {{ startingChat ? 'Starting...' : 'Start Chat' }}
            </button>
            <button @click="showNewChatForm = false" class="btn-cancel">Cancel</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Chat Side Popup -->
    <ChatSidePopup
      :isOpen="showChatPopup"
      :chatId="selectedChat?.id"
      :recipientId="selectedChat ? getOtherUser(selectedChat).id : null"
      :recipientName="selectedChat ? getOtherUser(selectedChat).name : ''"
      @close="closeChatPopup"
      @message-sent="onMessageSent"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'
import ChatSidePopup from './ChatSidePopup.vue'

const props = defineProps({
  isOpen: { type: Boolean, required: true }
})

const emit = defineEmits(['close', 'message-sent'])

// State
const chats = ref([])
const users = ref([])
const loading = ref(false)
const searchQuery = ref('')
const showNewChatForm = ref(false)
const newChatUserId = ref('')
const startingChat = ref(false)
const currentUserId = ref(null)
const refreshInterval = ref(null)

// Chat Popup State
const showChatPopup = ref(false)
const selectedChat = ref(null)

// Computed
const filteredChats = computed(() => {
  if (!searchQuery.value.trim()) return chats.value
  const query = searchQuery.value.trim().toLowerCase()
  return chats.value.filter(c =>
    getOtherUser(c).name.toLowerCase().includes(query) ||
    getOtherUser(c).email.toLowerCase().includes(query)
  )
})

const availableUsers = computed(() => {
  return users.value.filter(u => u.id !== currentUserId.value)
})

// Methods
const closeModal = () => {
  searchQuery.value = ''
  emit('close')
}

const getOtherUser = (chat) => {
  return chat.initiator_id === currentUserId.value ? chat.recipient : chat.initiator
}

const getInitials = (name) => {
  return name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

const getLastMessage = (chat) => {
  if (!chat.messages || chat.messages.length === 0) {
    return 'No messages yet'
  }
  const lastMsg = chat.messages[chat.messages.length - 1]
  return lastMsg.message.substring(0, 50) + (lastMsg.message.length > 50 ? '...' : '')
}

const hasUnreadMessages = (chat) => {
  if (!chat.messages) return false
  return chat.messages.some(m => !m.is_read && m.sender_id !== currentUserId.value)
}

const formatTime = (dt) => {
  if (!dt) return ''
  const d = new Date(dt)
  const now = new Date()
  const diff = now - d

  if (diff < 60000) return 'just now'
  if (diff < 3600000) return Math.floor(diff / 60000) + 'm'
  if (diff < 86400000) return Math.floor(diff / 3600000) + 'h'
  if (diff < 604800000) return Math.floor(diff / 86400000) + 'd'

  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
}

async function loadChats() {
  loading.value = true
  try {
    const res = await axios.get('/api/super/chats')
    chats.value = res.data.data || []
  } catch (e) {
    console.error('Failed to load chats', e)
    chats.value = []
  } finally {
    loading.value = false
  }
}

// Silent refresh for chats without showing loading spinner
async function refreshChats() {
  try {
    const res = await axios.get('/api/super/chats')
    chats.value = res.data.data || []
  } catch (e) {
    console.debug('Failed to refresh chats', e)
  }
}

async function loadUsers() {
  try {
    const res = await axios.get('/api/super/chats/available-users')
    users.value = res.data.data || []
  } catch (e) {
    console.error('Failed to load available users', e)
    users.value = []
  }
}

function openChat(chat) {
  selectedChat.value = chat
  showChatPopup.value = true
}

async function startNewChat() {
  if (!newChatUserId.value) return

  startingChat.value = true
  try {
    const res = await axios.post('/api/super/chats', {
      recipient_id: newChatUserId.value
    })
    chats.value.unshift(res.data)
    newChatUserId.value = ''
    showNewChatForm.value = false
    selectedChat.value = res.data
    showChatPopup.value = true
  } catch (e) {
    console.error('Failed to start chat', e)
    alert('Failed to start conversation: ' + (e.response?.data?.error || 'Unknown error'))
  } finally {
    startingChat.value = false
  }
}

const closeChatPopup = () => {
  showChatPopup.value = false
  selectedChat.value = null
}

const onMessageSent = () => {
  loadChats()
  emit('message-sent')
}

onMounted(() => {
  try {
    const user = JSON.parse(localStorage.getItem('userData'))
    currentUserId.value = user?.id
  } catch (e) {
    console.warn('Failed to get user ID', e)
  }
  loadChats()
  loadUsers()
})

// Watch for modal opening and refresh data
watch(
  () => props.isOpen,
  (newVal) => {
    if (newVal) {
      loadChats()
      loadUsers()
      // Start auto-refresh every 10 seconds using silent refresh
      refreshInterval.value = setInterval(() => {
        refreshChats()
      }, 10000)
    } else {
      // Stop auto-refresh when modal closes
      if (refreshInterval.value) {
        clearInterval(refreshInterval.value)
        refreshInterval.value = null
      }
    }
  }
)
</script>

<style scoped>
.chat-list-overlay {
  position: fixed;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: flex-end;
  z-index: 1900;
  animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.chat-list-modal {
  position: fixed;
  bottom: 0;
  right: 0;
  width: 100%;
  max-width: 380px;
  height: 100vh;
  max-height: 600px;
  background: #fff;
  border-radius: 16px 16px 0 0;
  box-shadow: 0 -5px 40px rgba(0, 0, 0, 0.16);
  display: flex;
  flex-direction: column;
  animation: slideUp 0.3s ease;
}

@keyframes slideUp {
  from { transform: translateY(100%); }
  to { transform: translateY(0); }
}

.modal-header {
  padding: 1rem 1.5rem;
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  border-radius: 16px 16px 0 0;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-shrink: 0;
}

.modal-title {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.btn-close {
  background: none;
  border: none;
  color: #fff;
  font-size: 1.25rem;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 6px;
  transition: background 0.2s ease;
}

.btn-close:hover {
  background: rgba(255, 255, 255, 0.2);
}

.search-section {
  padding: 0.75rem 1.5rem;
  border-bottom: 1px solid #e5e7eb;
  flex-shrink: 0;
}

.search-input {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  font-size: 0.95rem;
  font-family: inherit;
}

.search-input:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
  outline: none;
}

.chats-container {
  flex: 1;
  overflow-y: auto;
}

.loading, .empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: #a0aec0;
  text-align: center;
  padding: 2rem 1rem;
}

.spinner {
  width: 30px;
  height: 30px;
  border: 3px solid #e5e7eb;
  border-left: 3px solid #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty i {
  font-size: 2.5rem;
  margin-bottom: 1rem;
}

.chat-item {
  padding: 0.75rem 1.5rem;
  display: flex;
  gap: 1rem;
  align-items: center;
  cursor: pointer;
  transition: background 0.2s ease;
  border-bottom: 1px solid #f3f4f6;
}

.chat-item:hover {
  background: #f9fafb;
}

.chat-avatar {
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  flex-shrink: 0;
}

.chat-content {
  flex: 1;
  min-width: 0;
}

.chat-header {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: 0.5rem;
  margin-bottom: 0.25rem;
}

.chat-name {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 500;
  color: #1f2937;
}

.chat-time {
  font-size: 0.75rem;
  color: #9ca3af;
  flex-shrink: 0;
}

.chat-preview {
  margin: 0;
  font-size: 0.85rem;
  color: #6b7280;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.unread-indicator {
  width: 10px;
  height: 10px;
  background: #667eea;
  border-radius: 50%;
  flex-shrink: 0;
}

.modal-footer {
  padding: 1rem 1.5rem;
  border-top: 1px solid #e5e7eb;
  flex-shrink: 0;
}

.btn-new-chat {
  width: 100%;
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  border: none;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.btn-new-chat:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.form-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
}

.form-modal {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  width: 100%;
  max-width: 350px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.form-modal h3 {
  margin: 0 0 1rem 0;
  font-size: 1.1rem;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  font-weight: 600;
  margin-bottom: 0.5rem;
  color: #374151;
  font-size: 0.9rem;
}

.form-select {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 0.95rem;
  font-family: inherit;
}

.form-select:focus {
  border-color: #667eea;
  outline: none;
}

.form-actions {
  display: flex;
  gap: 0.75rem;
  margin-top: 1.5rem;
}

.btn-start {
  flex: 1;
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  border: none;
  padding: 0.75rem;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
}

.btn-start:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-cancel {
  flex: 1;
  background: #f3f4f6;
  border: none;
  padding: 0.75rem;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
}

/* Scrollbar */
.chats-container::-webkit-scrollbar {
  width: 4px;
}

.chats-container::-webkit-scrollbar-track {
  background: transparent;
}

.chats-container::-webkit-scrollbar-thumb {
  background: #cbd5e0;
  border-radius: 2px;
}

.chats-container::-webkit-scrollbar-thumb:hover {
  background: #a0aec0;
}

@media (max-width: 480px) {
  .chat-list-modal {
    max-width: 100%;
  }
}
</style>
