<!-- SettingsPage.vue -->
<template>
  <div class="settings-page">
    <div class="page-header">
      <h1 class="page-title">System Settings</h1>
      <p class="page-description">Configure system-wide settings and preferences</p>
    </div>
    
    <div class="content-wrapper">
      <div class="settings-sections">
        <div class="settings-section">
          <h3 class="section-title">General Settings</h3>
          <div class="setting-group">
            <label class="setting-label">
              Company Name
              <input 
                type="text" 
                v-model="settings.companyName"
                class="setting-input"
              />
            </label>
            <label class="setting-label">
              Currency
              <select v-model="settings.currency" class="setting-select">
                <option value="USD">USD ($)</option>
                <option value="EUR">EUR (€)</option>
                <option value="GBP">GBP (£)</option>
                <option value="KES">KES (KSh)</option>
              </select>
            </label>
            <label class="setting-label">
              Time Zone
              <select v-model="settings.timezone" class="setting-select">
                <option value="UTC">UTC</option>
                <option value="America/New_York">Eastern Time</option>
                <option value="Europe/London">London</option>
                <option value="Africa/Nairobi">Nairobi</option>
              </select>
            </label>
          </div>
        </div>
        
        <div class="settings-section">
          <h3 class="section-title">Security Settings</h3>
          <div class="setting-group">
            <label class="setting-checkbox">
              <input 
                type="checkbox" 
                v-model="settings.enforceStrongPasswords"
              />
              Enforce Strong Passwords
            </label>
            <label class="setting-checkbox">
              <input 
                type="checkbox" 
                v-model="settings.enableTwoFactor"
              />
              Enable Two-Factor Authentication
            </label>
            <label class="setting-label">
              Session Timeout (minutes)
              <input 
                type="number" 
                v-model="settings.sessionTimeout"
                class="setting-input"
                min="5"
                max="480"
              />
            </label>
          </div>
        </div>
        
        <div class="settings-section">
          <h3 class="section-title">POS Settings</h3>
          <div class="setting-group">
            <label class="setting-checkbox">
              <input 
                type="checkbox" 
                v-model="settings.printReceipts"
              />
              Auto-print Receipts
            </label>
            <label class="setting-label">
              Tax Rate (%)
              <input 
                type="number" 
                v-model="settings.taxRate"
                class="setting-input"
                min="0"
                max="100"
                step="0.01"
              />
            </label>
          </div>
        </div>
        
        <div class="settings-actions">
          <button class="btn btn-primary" @click="saveSettings">
            <i class="fas fa-save"></i>
            Save Settings
          </button>
          <button class="btn btn-secondary" @click="resetSettings">
            <i class="fas fa-undo"></i>
            Reset to Defaults
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const settings = ref({
  companyName: 'Mobiz POS System',
  currency: 'USD',
  timezone: 'UTC',
  enforceStrongPasswords: true,
  enableTwoFactor: false,
  sessionTimeout: 60,
  printReceipts: true,
  taxRate: 8.25
})

const saveSettings = () => {
  console.log('Saving settings:', settings.value)
  // Here you would make an API call to save settings
}

const resetSettings = () => {
  console.log('Resetting settings to defaults')
  // Reset to default values
}

onMounted(() => {
  console.log('Settings page mounted')
})
</script>