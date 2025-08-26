// src/router/index.js
import { createRouter, createWebHistory } from 'vue-router'
import DashboardPage from '../pages/DashboardPage.vue'
import InventoryPage from '../pages/InventoryPage.vue'
import ProductsPage from '../pages/ProductsPage.vue'
import SalesPage from '../pages/SalesPage.vue'
import ReportsPage from '../pages/ReportPage.vue'
import ExpensePage from '../pages/ExpensePage.vue'
import LoginPage from '../pages/LoginPage.vue'

// Helper function to check if user is authenticated
const isAuthenticated = () => {
  const authToken = localStorage.getItem('authToken')
  const isLoggedIn = localStorage.getItem('isLoggedIn')
  return !!(authToken && isLoggedIn === 'true')
}

const routes = [
  { 
    path: '/login', 
    name: 'Login', 
    component: LoginPage,
    
    meta: { 
      layout: 'auth',
      requiresGuest: true 
    }
  },
  { 
    path: '/', 
    name: 'Dashboard', 
    component: DashboardPage,
    meta: { 
      requiresAuth: true 
    }
  },
  { 
    path: '/inventory', 
    name: 'Inventory', 
    component: InventoryPage,
    meta: { 
      requiresAuth: true 
    }
  },
  { 
    path: '/products', 
    name: 'Products', 
    component: ProductsPage,
    meta: { 
      requiresAuth: true 
    }
  },
  { 
    path: '/sales', 
    name: 'Sales', 
    component: SalesPage,
    meta: { 
      requiresAuth: true 
    }
  },
  { 
    path: '/reports', 
    name: 'Reports', 
    component: ReportsPage,
    meta: { 
      requiresAuth: true 
    }
  },
  { 
    path: '/expenses', 
    name: 'Expenses', 
    component: ExpensePage,
    meta: { 
      requiresAuth: true 
    }
  },
  // Catch-all redirect
  {
    path: '/:pathMatch(.*)*',
    redirect: (to) => {
      return isAuthenticated() ? '/' : '/login'
    }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// Navigation guards
router.beforeEach((to, from, next) => {
  const authenticated = isAuthenticated()
  
  // Check if route requires authentication
  if (to.meta.requiresAuth && !authenticated) {
    next('/login')
    return
  }
  
  // Check if route requires guest (like login page)
  if (to.meta.requiresGuest && authenticated) {
    next('/')
    return
  }
  
  next()
})

export default router
