<template>
  <div v-if="isOpen" class="chat-overlay" @click="closeSidePopup">
    <div class="chat-popup" @click.stop>
      <!-- Header -->
      <div class="chat-header">
        <div class="chat-info">
          <h3 class="chat-title">{{ recipientName }}</h3>
          <p class="chat-subtitle">{{ isLoading ? 'Loading...' : chatStatus }}</p>
        </div>
        <button @click="closeSidePopup" class="btn-close">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <!-- Messages Container -->
      <div class="chat-messages" ref="messagesContainer">
        <div v-if="loading" class="loading-state">
          <div class="spinner"></div>
          <p>Loading conversation...</p>
        </div>

        <div v-else-if="messages.length === 0" class="empty-state">
          <i class="fas fa-comments"></i>
          <p>No messages yet. Start a conversation!</p>
        </div>

        <div v-else>
          <div v-for="msg in messages" :key="msg.id" class="message" :class="{ 'sent': isSentByMe(msg) }">
            <div class="message-bubble">
              <p class="message-text">{{ msg.message }}</p>
              <span class="message-time">{{ formatTime(msg.created_at) }}</span>
              <i v-if="isSentByMe(msg) && msg.is_read" class="fas fa-check-double read-indicator"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Message Input -->
      <div class="chat-input-section">
        <div class="input-wrapper">
          <textarea
            v-model="newMessage"
            @keydown.enter.ctrl="sendMessage"
            placeholder="Type a message... (Ctrl+Enter to send)"
            class="message-input"
            rows="2"
          ></textarea>
          <button @click="sendMessage" :disabled="!newMessage.trim() || sending" class="btn-send">
            <i v-if="sending" class="fas fa-spinner fa-spin"></i>
            <i v-else class="fas fa-paper-plane"></i>
          </button>
        </div>
        <p class="hint-text">Ctrl+Enter to send</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch, nextTick, computed } from 'vue'
import axios from 'axios'

// Props & Emits
const props = defineProps({
  isOpen: { type: Boolean, required: true },
  chatId: { type: [Number, String], default: null },
  recipientId: { type: [Number, String], default: null },
  recipientName: { type: String, default: 'Chat' }
})

const emit = defineEmits(['close', 'message-sent'])

// State
const messages = ref([])
const loading = ref(false)
const sending = ref(false)
const newMessage = ref('')
const messagesContainer = ref(null)
const currentUserId = ref(null)

// Computed
const chatStatus = computed(() => {
  return messages.value.length > 0 ? `${messages.value.length} messages` : 'Ready to chat'
})

// Methods
const isSentByMe = (msg) => msg.sender_id === currentUserId.value

const formatTime = (dt) => {
  if (!dt) return ''
  const d = new Date(dt)
  return d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
}

const scrollToBottom = async () => {
  await nextTick()
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
}

const closeSidePopup = () => emit('close')

async function loadChat() {
  if (!props.chatId) return

  loading.value = true
  try {
    const res = await axios.get(`/api/super/chats/${props.chatId}`)
    messages.value = res.data.messages || []
    await scrollToBottom()
  } catch (e) {
    console.error('Failed to load chat', e)
  } finally {
    loading.value = false
  }
}

async function sendMessage() {
  if (!newMessage.value.trim() || !props.chatId) return

  sending.value = true
  try {
    const res = await axios.post(`/api/super/chats/${props.chatId}/messages`, {
      message: newMessage.value
    })
    messages.value.push(res.data)
    newMessage.value = ''
    await scrollToBottom()
    emit('message-sent')
  } catch (e) {
    console.error('Failed to send message', e)
    alert('Failed to send message')
  } finally {
    sending.value = false
  }
}

// Initialize current user ID from localStorage
onMounted(() => {
  try {
    const user = JSON.parse(localStorage.getItem('userData'))
    currentUserId.value = user?.id
  } catch (e) {
    console.warn('Failed to get user ID', e)
  }
})

// Watchers
watch(
  () => props.isOpen,
  async (newVal) => {
    if (newVal && props.chatId) {
      await loadChat()
    }
  }
)

watch(
  () => props.chatId,
  async (newVal) => {
    if (newVal && props.isOpen) {
      await loadChat()
    }
  }
)
</script>

<style scoped>
.chat-overlay {
  position: fixed;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
  background: rgba(0, 0, 0, 0.4);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: flex-end;
  z-index: 2000;
  animation: overlayFadeIn 0.2s ease;
}

@keyframes overlayFadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.chat-popup {
  position: fixed;
  bottom: 0;
  right: 0;
  width: 100%;
  max-width: 420px;
  height: 100vh;
  max-height: 600px;
  background: #fff;
  border-radius: 16px 16px 0 0;
  box-shadow: 0 -5px 40px rgba(0, 0, 0, 0.16);
  display: flex;
  flex-direction: column;
  animation: popupSlideUp 0.3s ease;
}

@keyframes popupSlideUp {
  from { transform: translateY(100%); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

.chat-header {
  padding: 1rem 1.5rem;
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  border-radius: 16px 16px 0 0;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-shrink: 0;
}

.chat-info { flex: 1; }
.chat-title { margin: 0; font-size: 1.1rem; font-weight: 600; }
.chat-subtitle { margin: 0.25rem 0 0 0; font-size: 0.85rem; opacity: 0.9; }

.btn-close { background: none; border: none; color: #fff; font-size: 1.25rem; cursor: pointer; padding: 0.5rem; border-radius: 6px; transition: all 0.2s ease; }
.btn-close:hover { background: rgba(255, 255, 255, 0.2); }

.chat-messages {
  flex: 1;
  overflow-y: auto;
  padding: 1rem 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  background: #f9fafb;
}

.loading-state, .empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: #a0aec0;
  text-align: center;
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

.message {
  display: flex;
  margin-bottom: 0.5rem;
  animation: messageSlideIn 0.2s ease;
}

@keyframes messageSlideIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.message.sent {
  justify-content: flex-end;
}

.message-bubble {
  max-width: 85%;
  padding: 0.75rem 1rem;
  border-radius: 12px;
  position: relative;
  word-wrap: break-word;
}

.message.sent .message-bubble {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  border-bottom-right-radius: 4px;
}

.message:not(.sent) .message-bubble {
  background: #e5e7eb;
  color: #1f2937;
  border-bottom-left-radius: 4px;
}

.message-text { margin: 0 0 0.25rem 0; font-size: 0.95rem; }
.message-time { font-size: 0.75rem; opacity: 0.7; }
.read-indicator { margin-left: 0.5rem; font-size: 0.75rem; }

.chat-input-section {
  padding: 1rem 1.5rem;
  background: #fff;
  border-top: 1px solid #e5e7eb;
  flex-shrink: 0;
}

.input-wrapper {
  display: flex;
  gap: 0.75rem;
  align-items: flex-end;
}

.message-input {
  flex: 1;
  padding: 0.75rem 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 10px;
  font-size: 0.95rem;
  font-family: inherit;
  resize: none;
  max-height: 100px;
  transition: all 0.2s ease;
}

.message-input:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
  outline: none;
}

.btn-send {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  border: none;
  width: 40px;
  height: 40px;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.2s ease;
  flex-shrink: 0;
}

.btn-send:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
}

.btn-send:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.hint-text { font-size: 0.75rem; color: #a0aec0; margin: 0.5rem 0 0 0; text-align: center; }

/* Scrollbar Styling */
.chat-messages::-webkit-scrollbar {
  width: 4px;
}

.chat-messages::-webkit-scrollbar-track {
  background: transparent;
}

.chat-messages::-webkit-scrollbar-thumb {
  background: #cbd5e0;
  border-radius: 2px;
}

.chat-messages::-webkit-scrollbar-thumb:hover {
  background: #a0aec0;
}

/* Responsive */
@media (max-width: 768px) {
  .chat-popup {
    max-width: 100%;
    height: 80vh;
    border-radius: 20px 20px 0 0;
  }
}
</style>
