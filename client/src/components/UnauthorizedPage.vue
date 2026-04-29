<!-- UnauthorizedPage.vue -->
<template>
  <div class="unauthorized-page">
    <div class="unauthorized-container">
      <div class="unauthorized-content">
        <div class="error-code">403</div>
        <h1 class="error-title">Access Denied</h1>
        <p class="error-description">
          {{ descriptionText }}
        </p>

        <div v-if="isSubscriptionBlock" class="reason-card">
          <p><strong>Required feature:</strong> {{ featureLabel }}</p>
          <p class="reason-note">Your current subscription package does not include this module.</p>
        </div>

        <div class="error-illustration">
          <i class="fas fa-lock"></i>
        </div>

        <div class="action-buttons">
          <router-link v-if="isSubscriptionBlock && isAdmin" to="/company-profile" class="btn btn-accent">
            <i class="fas fa-credit-card"></i>
            Manage Subscription
          </router-link>

          <router-link to="/" class="btn btn-primary">
            <i class="fas fa-home"></i>
            Go to Dashboard
          </router-link>

          <button @click="goBack" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i>
            Go Back
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()

const role = computed(() => {
  try {
    const userData = JSON.parse(localStorage.getItem('userData') || '{}')
    return String(userData?.role?.name || '').toLowerCase()
  } catch (_e) {
    return ''
  }
})

const isAdmin = computed(() => ['admin', 'administrator'].includes(role.value))
const reason = computed(() => String(route.query.reason || ''))
const featureLabel = computed(() => String(route.query.feature_label || route.query.feature || 'This module'))

const isSubscriptionBlock = computed(() => reason.value === 'subscription_feature' || reason.value === 'subscription_feature_check_failed')

const descriptionText = computed(() => {
  if (reason.value === 'subscription_feature') {
    return `Your current subscription does not include ${featureLabel.value}.`
  }
  if (reason.value === 'subscription_feature_check_failed') {
    return 'We could not verify your subscription access for this module right now.'
  }
  if (reason.value === 'role_required' || reason.value === 'role_excluded') {
    return "Your account role does not have permission to access this resource."
  }
  return "You don't have permission to access this resource."
})

const goBack = () => {
  router.back()
}
</script>

<style scoped>
.unauthorized-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #e94057 0%, #f27121 100%);
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}

.unauthorized-container {
  width: 100%;
  max-width: 500px;
  padding: 20px;
}

.unauthorized-content {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 24px;
  padding: 60px 40px;
  text-align: center;
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
}

.error-code {
  font-size: 120px;
  font-weight: 900;
  background: linear-gradient(135deg, #e94057, #f27121);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin: 0;
  line-height: 1;
}

.error-title {
  font-size: 32px;
  font-weight: 700;
  color: #2d3748;
  margin: 16px 0 12px 0;
}

.error-description {
  color: #718096;
  font-size: 16px;
  margin: 0 0 32px 0;
  line-height: 1.6;
}

.reason-card {
  margin: 0 auto 20px;
  max-width: 420px;
  background: #fff7ed;
  border: 1px solid #fdba74;
  border-radius: 12px;
  padding: 12px 14px;
  color: #9a3412;
  text-align: left;
}

.reason-card p {
  margin: 0;
}

.reason-note {
  font-size: 14px;
  margin-top: 6px !important;
}

.error-illustration {
  margin: 32px 0;
  font-size: 64px;
  color: #fed7d7;
}

.action-buttons {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-top: 32px;
}

.btn {
  padding: 12px 24px;
  border-radius: 12px;
  font-size: 16px;
  font-weight: 600;
  border: none;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  text-decoration: none;
}

.btn-primary {
  background: linear-gradient(135deg, #e94057, #f27121);
  color: white;
  box-shadow: 0 8px 25px rgba(233, 64, 87, 0.3);
}

.btn-accent {
  background: linear-gradient(135deg, #0ea5e9, #2563eb);
  color: white;
  box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
}

.btn-accent:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 35px rgba(37, 99, 235, 0.4);
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 35px rgba(233, 64, 87, 0.4);
}

.btn-secondary {
  background: #e2e8f0;
  color: #2d3748;
}

.btn-secondary:hover {
  background: #cbd5e0;
  transform: translateY(-2px);
}

@media (max-width: 640px) {
  .unauthorized-content {
    padding: 40px 24px;
  }

  .error-code {
    font-size: 80px;
  }

  .error-title {
    font-size: 24px;
  }
}
</style>

