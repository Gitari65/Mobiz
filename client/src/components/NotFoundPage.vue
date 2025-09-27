<!-- NotFoundPage.vue -->
<template>
  <div class="not-found-page">
    <div class="not-found-content">
      <div class="not-found-icon">
        <i class="fas fa-search"></i>
      </div>
      <h1 class="not-found-title">404 - Page Not Found</h1>
      <p class="not-found-message">
        The page you're looking for doesn't exist or has been moved.
      </p>
      <div class="not-found-actions">
        <button class="btn btn-primary" @click="goHome">
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

const goHome = () => {
  router.push('/')
}
</script>
