import SuperUserDashboard from '../pages/SuperUserDashboard.vue'
// src/router/index.js
import { createRouter, createWebHistory } from 'vue-router'
import DashboardPage from '../pages/DashboardPage.vue'
import InventoryPage from '../pages/InventoryPage.vue'
import ProductsPage from '../pages/ProductsPage.vue'
import SalesPage from '../pages/SalesPage.vue'
import ReportsPage from '../pages/ReportPage.vue'
import ExpensePage from '../pages/ExpensePage.vue'

import LoginPage from '../pages/LoginPage.vue'
import SignupPage from '../pages/SignupPage.vue'

// Helper function to check if user is authenticated
const isAuthenticated = () => {
  const authToken = localStorage.getItem('authToken');
  const isLoggedIn = localStorage.getItem('isLoggedIn');
  let role = null;
  try {
    const userData = JSON.parse(localStorage.getItem('userData') || '{}');
    role = userData?.role?.name || null;
  } catch (error) {
    console.error("Error parsing userData from localStorage:", error);
  }
  return {
    isAuth: !!(authToken && isLoggedIn === 'true'),
    role: role
  };
}

import AdminCustomizationPage from '../pages/AdminCustomizationPage.vue'

const routes = [
  {
    path: '/super-user', // Changed from /superuser
    name: 'SuperUserDashboard',
    component: SuperUserDashboard,
    meta: {
      requiresAuth: true,
      requiresSuperUser: true
    }
  },
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
    path: '/signup',
    name: 'Signup',
    component: SignupPage,
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
  {
    path: '/admin-customization',
    name: 'AdminCustomization',
    component: AdminCustomizationPage,
    meta: {
      requiresAuth: true
      // You can add a custom meta like requiresAdmin: true for further restriction
    }
  },
  // Catch-all redirect
  {
    path: '/:pathMatch(.*)*',
    redirect: (to) => {
      return isAuthenticated().isAuth ? '/' : '/login'
    }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// Navigation guards
router.beforeEach((to, from, next) => {
  const isAuth = localStorage.getItem('isLoggedIn') === 'true'
  const user = JSON.parse(localStorage.getItem('userData') || '{}')
  const role = (user?.role?.name || '').toLowerCase()

  console.log("isAuth:", isAuth)
  console.log("Role:", role)

  if (to.meta.requiresAuth && !isAuth) {
    return next('/login')
  }

  // Example: protect superuser routes
  if (to.meta.role === 'superuser' && role !== 'superuser') {
    return next('/') 
  }

  next()
})


export default router
