<template>
  <div class="app-container" :class="{ 'auth-layout': isAuthRoute }">
    <!-- Sidebar only shows on authenticated routes -->
    <Sidebar v-if="!isAuthRoute" />
    
    <!-- Main content area -->
    <main class="main-content" :class="{ 'full-width': isAuthRoute }">
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import Sidebar from './components/SideBarComponent.vue'

const route = useRoute()

// Check if current route is an auth route (like login)
const isAuthRoute = computed(() => {
  return route.meta?.layout === 'auth' || route.path === '/login'
})
</script>

<style scoped>
/* Modern App Layout Styles */
.app-container {
  display: flex;
  height: 100vh;
  width: 100vw;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
  overflow: hidden;
  position: fixed;
  top: 0;
  left: 0;
}

/* Main app layout with sidebar */
.main-content {
  flex: 1;
  padding: 1.5rem;
  background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
  overflow-y: auto;
  overflow-x: hidden;
  position: relative;
  height: 100vh;
  max-height: 100vh;
  box-sizing: border-box;
}

/* Auth layout (login page) - full width without sidebar */
.app-container.auth-layout {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 0;
  margin: 0;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
}

.main-content.full-width {
  padding: 0;
  background: transparent;
  overflow: hidden;
  width: 100vw;
  height: 100vh;
  max-height: 100vh;
  max-width: 100vw;
  flex: none;
  position: relative;
}

/* Smooth scrollbar for main content */
.main-content:not(.full-width)::-webkit-scrollbar {
  width: 6px;
}

.main-content:not(.full-width)::-webkit-scrollbar-track {
  background: rgba(0, 0, 0, 0.05);
  border-radius: 3px;
}

.main-content:not(.full-width)::-webkit-scrollbar-thumb {
  background: rgba(102, 126, 234, 0.3);
  border-radius: 3px;
}

.main-content:not(.full-width)::-webkit-scrollbar-thumb:hover {
  background: rgba(102, 126, 234, 0.5);
}

/* Responsive design for main app (not login) */
@media (max-width: 1024px) {
  .main-content:not(.full-width) {
    padding: 1rem;
  }
}

@media (max-width: 768px) {
  .main-content:not(.full-width) {
    padding: 0.75rem;
  }
}

@media (max-width: 480px) {
  .main-content:not(.full-width) {
    padding: 0.5rem;
  }
}

/* Global transitions for non-auth routes */
.app-container:not(.auth-layout) * {
  transition-duration: 0.2s;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}
</style>
