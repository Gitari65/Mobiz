<template>
  <div class="gs-page">
    <!-- Header -->
    <header class="gs-header">
      <div class="gs-title">
        <i class="fas fa-cog"></i>
        <div>
          <h1>Global Settings</h1>
          <p class="muted">Manage platform-wide configuration and preferences</p>
        </div>
      </div>
      <button class="btn-refresh" @click="fetchSettings" :disabled="loading">
        <i class="fas fa-sync-alt" :class="{ 'spin': loading }"></i>
      </button>
    </header>

    <!-- Content -->
    <section class="gs-content">
      <div v-if="loading" class="loading">
        <div class="spinner"></div>
        <p>Loading settings...</p>
      </div>

      <div v-else-if="error" class="error-message">
        <i class="fas fa-exclamation-circle"></i>
        <span>{{ error }}</span>
        <button @click="fetchSettings" class="btn-retry">Retry</button>
      </div>

      <div v-else class="settings-grid">
        <!-- Category Sections -->
        <div v-for="(categorySettings, category) in groupedSettings" :key="category" class="category-section">
          <button class="category-header" @click="toggleCategory(category)">
            <i class="fas" :class="expandedCategories[category] ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
            <span class="category-name">{{ formatCategory(category) }}</span>
            <span class="setting-count">{{ categorySettings.length }} settings</span>
          </button>

          <div v-if="expandedCategories[category]" class="category-body">
            <div v-for="setting in categorySettings" :key="setting.id" class="setting-row">
              <div class="setting-label">
                <label :for="`setting-${setting.id}`">{{ setting.key }}</label>
                <p v-if="setting.description" class="setting-desc">{{ setting.description }}</p>
              </div>

              <div class="setting-control">
                <!-- Boolean Toggle -->
                <label v-if="isBoolean(setting.value)" class="toggle-switch">
                  <input
                    :id="`setting-${setting.id}`"
                    type="checkbox"
                    :checked="setting.value === 'true' || setting.value === true"
                    @change="updateSetting(setting, $event.target.checked)"
                  />
                  <span class="toggle-slider"></span>
                </label>

                <!-- Number Input -->
                <input
                  v-else-if="isNumber(setting.value)"
                  :id="`setting-${setting.id}`"
                  type="number"
                  :value="setting.value"
                  @blur="updateSetting(setting, $event.target.value)"
                  class="input-number"
                />

                <!-- Select/Dropdown -->
                <select
                  v-else-if="category.includes('theme') || setting.key.includes('theme')"
                  :id="`setting-${setting.id}`"
                  :value="setting.value"
                  @change="updateSetting(setting, $event.target.value)"
                  class="input-select"
                >
                  <option value="light">Light</option>
                  <option value="dark">Dark</option>
                  <option value="auto">Auto</option>
                </select>

                <!-- Text Input (default) -->
                <textarea
                  v-else
                  :id="`setting-${setting.id}`"
                  :value="setting.value"
                  @blur="updateSetting(setting, $event.target.value)"
                  class="input-text"
                  :rows="setting.value?.toString().split('\n').length || 2"
                ></textarea>
              </div>

              <div class="setting-actions">
                <button @click="deleteSetting(setting.id)" class="btn-delete" title="Delete">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Add New Setting -->
      <div class="add-setting-section">
        <h3>Add New Setting</h3>
        <form @submit.prevent="addSetting" class="add-setting-form">
          <div class="form-row">
            <div class="form-group">
              <label>Key *</label>
              <input v-model="newSetting.key" type="text" required placeholder="setting_key" />
            </div>

            <div class="form-group">
              <label>Category</label>
              <input v-model="newSetting.category" type="text" placeholder="email, payment, etc." />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Value *</label>
              <textarea v-model="newSetting.value" required placeholder="Setting value" rows="2"></textarea>
            </div>

            <div class="form-group">
              <label>Description</label>
              <textarea v-model="newSetting.description" placeholder="Setting description" rows="2"></textarea>
            </div>
          </div>

          <div class="form-row">
            <label class="checkbox-label">
              <input v-model="newSetting.is_public" type="checkbox" />
              <span>Public (visible to users)</span>
            </label>
          </div>

          <button type="submit" class="btn-add">
            <i class="fas fa-plus"></i> Add Setting
          </button>
        </form>
      </div>
    </section>

    <!-- Alert Toast -->
    <div v-if="alert.show" class="alert-toast" :class="`alert-${alert.type}`">
      <i :class="getAlertIcon(alert.type)"></i>
      <span>{{ alert.message }}</span>
      <button @click="alert.show = false" class="alert-close">
        <i class="fas fa-times"></i>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import axios from 'axios'

// State
const loading = ref(false)
const error = ref(null)
const settings = ref([])
const expandedCategories = reactive({})

const newSetting = reactive({
  key: '',
  category: '',
  value: '',
  description: '',
  is_public: false
})

const alert = reactive({
  show: false,
  type: 'info',
  message: ''
})

// Computed
const groupedSettings = computed(() => {
  const grouped = {}
  settings.value.forEach(s => {
    const cat = s.category || 'other'
    if (!grouped[cat]) grouped[cat] = []
    grouped[cat].push(s)
  })
  return grouped
})

// Methods
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

const formatCategory = (cat) => {
  return cat.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')
}

const isBoolean = (val) => {
  return val === 'true' || val === 'false' || val === true || val === false
}

const isNumber = (val) => {
  return !isNaN(val) && val !== '' && val !== null && val !== undefined
}

async function fetchSettings() {
  loading.value = true
  error.value = null
  try {
    const res = await axios.get('/api/super/settings')
    settings.value = res.data.data || res.data.settings || []
    // Auto-expand first category
    const firstCat = Object.keys(groupedSettings.value)[0]
    if (firstCat) expandedCategories[firstCat] = true
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to fetch settings'
    settings.value = []
    showAlert('error', error.value)
  } finally {
    loading.value = false
  }
}

function toggleCategory(cat) {
  expandedCategories[cat] = !expandedCategories[cat]
}

async function updateSetting(setting, newValue) {
  try {
    await axios.put(`/api/super/settings/${setting.id}`, {
      value: newValue.toString(),
      category: setting.category,
      description: setting.description,
      is_public: setting.is_public
    })
    setting.value = newValue
    showAlert('success', `${setting.key} updated`)
  } catch (e) {
    showAlert('error', `Failed to update ${setting.key}`)
  }
}

async function deleteSetting(id) {
  if (!confirm('Are you sure you want to delete this setting?')) return
  try {
    await axios.delete(`/api/super/settings/${id}`)
    settings.value = settings.value.filter(s => s.id !== id)
    showAlert('success', 'Setting deleted')
  } catch (e) {
    showAlert('error', 'Failed to delete setting')
  }
}

async function addSetting() {
  if (!newSetting.key || !newSetting.value) {
    showAlert('warning', 'Key and Value are required')
    return
  }
  try {
    const res = await axios.post('/api/super/settings', newSetting)
    settings.value.push(res.data)
    Object.assign(newSetting, { key: '', category: '', value: '', description: '', is_public: false })
    showAlert('success', 'Setting added')
  } catch (e) {
    showAlert('error', e.response?.data?.message || 'Failed to add setting')
  }
}

// Lifecycle
onMounted(() => {
  fetchSettings()
})
</script>

<style scoped>
.gs-page { max-width: 1200px; margin: 2rem auto; padding: 1.5rem; font-family: 'Inter', sans-serif; }

.gs-header { display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 2rem; }
.gs-title { display: flex; align-items: center; gap: 1rem; }
.gs-title i { font-size: 1.75rem; color: #667eea; background: rgba(102,126,234,0.1); padding: 0.75rem; border-radius: 12px; }
.gs-title h1 { margin: 0; font-size: 1.5rem; }
.muted { color: #6b7280; margin: 0; }

.btn-refresh { background: #f3f4f6; border: none; width: 44px; height: 44px; border-radius: 10px; cursor: pointer; }
.btn-refresh:hover { background: #e5e7eb; }
.btn-refresh.spin i { animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.gs-content { background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: 0 6px 20px rgba(0,0,0,0.06); }

.loading, .error-message { text-align: center; padding: 2rem; }
.spinner { width: 40px; height: 40px; border: 3px solid #e5e7eb; border-left: 3px solid #667eea; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 1rem; }
.error-message { color: #991b1b; background: #fee2e2; border-radius: 8px; display: flex; align-items: center; justify-content: center; gap: 1rem; }
.btn-retry { background: #dc2626; color: #fff; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; }

.settings-grid { display: flex; flex-direction: column; gap: 1rem; margin-bottom: 2rem; }

.category-section { border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; }
.category-header { width: 100%; padding: 1rem 1.5rem; background: #f9fafb; border: none; cursor: pointer; display: flex; align-items: center; gap: 0.75rem; font-weight: 600; color: #374151; transition: all 0.2s ease; }
.category-header:hover { background: #f3f4f6; }
.category-name { flex: 1; text-align: left; }
.setting-count { color: #9ca3af; font-size: 0.85rem; font-weight: 400; }

.category-body { padding: 1rem 1.5rem; border-top: 1px solid #e5e7eb; }
.setting-row { display: grid; grid-template-columns: 200px 1fr 40px; gap: 1rem; align-items: center; padding: 1rem 0; border-bottom: 1px solid #f3f4f6; }
.setting-row:last-child { border-bottom: none; }

.setting-label label { font-weight: 600; color: #374151; }
.setting-desc { color: #6b7280; font-size: 0.85rem; margin: 0.25rem 0 0 0; }

.setting-control { display: flex; align-items: center; }
.toggle-switch { position: relative; display: inline-block; width: 50px; height: 28px; }
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-slider { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: #cbd5e0; border-radius: 14px; transition: all 0.3s ease; cursor: pointer; }
.toggle-slider::before { content: ''; position: absolute; height: 22px; width: 22px; left: 3px; bottom: 3px; background: white; border-radius: 50%; transition: all 0.3s ease; }
.toggle-switch input:checked + .toggle-slider { background: #667eea; }
.toggle-switch input:checked + .toggle-slider::before { transform: translateX(22px); }

.input-number, .input-select, .input-text { width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 0.95rem; font-family: inherit; transition: all 0.2s ease; }
.input-number:focus, .input-select:focus, .input-text:focus { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.1); outline: none; }

.setting-actions { display: flex; gap: 0.5rem; }
.btn-delete { background: none; border: none; color: #dc2626; cursor: pointer; padding: 0.5rem; border-radius: 6px; transition: all 0.2s ease; }
.btn-delete:hover { background: rgba(220,38,38,0.1); }

.add-setting-section { background: #f9fafb; border: 2px dashed #e5e7eb; border-radius: 10px; padding: 1.5rem; margin-top: 2rem; }
.add-setting-section h3 { margin: 0 0 1rem 0; color: #374151; }
.add-setting-form { display: flex; flex-direction: column; gap: 1rem; }
.form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; }
.form-group { display: flex; flex-direction: column; gap: 0.5rem; }
.form-group label { font-weight: 600; color: #374151; font-size: 0.9rem; }
.form-group input, .form-group textarea { padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px; font-family: inherit; }
.form-group input:focus, .form-group textarea:focus { border-color: #667eea; outline: none; }

.checkbox-label { display: flex; align-items: center; gap: 0.5rem; cursor: pointer; }
.checkbox-label input { width: 18px; height: 18px; cursor: pointer; }

.btn-add { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s ease; }
.btn-add:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(102,126,234,0.3); }

.alert-toast { position: fixed; bottom: 2rem; right: 2rem; display: flex; align-items: center; gap: 1rem; padding: 1rem 1.5rem; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); z-index: 3000; animation: slideIn 0.3s ease; }
.alert-success { background: #d1fae5; color: #065f46; }
.alert-error { background: #fee2e2; color: #991b1b; }
.alert-warning { background: #fef3c7; color: #78350f; }
@keyframes slideIn { from { transform: translateX(400px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
</style>
