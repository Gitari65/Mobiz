<!-- UnauthorizedPage.vue -->
<template>
  <div class="unauthorized-page">
    <div class="unauthorized-content">
      <div class="unauthorized-icon">
        <i class="fas fa-ban"></i>
      </div>
      <h1 class="unauthorized-title">Access Denied</h1>
      <p class="unauthorized-message">
        You don't have permission to access this page. Please contact your administrator if you believe this is an error.
      </p>
      <div class="unauthorized-actions">
        <button class="btn btn-primary" @click="goBack">
          <i class="fas fa-arrow-left"></i>
          Go Back
        </button>
        <button class="btn btn-secondary" @click="goHome">
          <i class="fas fa-home"></i>
          Go to Dashboard
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'

const router = useRouter()

const goBack = () => {
  router.go(-1)
}

const goHome = () => {
  router.push('/')
}
</script>

