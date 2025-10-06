// src/router/index.js
import { createRouter, createWebHistory } from 'vue-router'
import DashboardPage from '../pages/DashboardPage.vue'
import InventoryPage from '../pages/InventoryPage.vue'
import ProductsPage from '../pages/ProductsPage.vue'
import SalesPage from '../pages/SalesPage.vue'
import ReportsPage from '../pages/ReportPage.vue'
import ExpensePage from '../pages/ExpensePage.vue'

import LoginPage from '../pages/Auth/LoginPage.vue'
import SignupPage from '../pages/Auth/SignupPage.vue'

import UnauthorizedPage from '../components/UnauthorizedPage.vue'
import NotFoundPage from '../components/NotFoundPage.vue'

// Add these imports for  superuser pages
import UserManagementPage from '../pages/superuser/UserManagementPage.vue'
import SystemLogsPage from '../pages/superuser/SystemLogsPage.vue'
import SettingsPage from '../pages/superuser/SettingsPage.vue'
import SuperUserDashboard from '../pages/superuser/SuperUserDashboard.vue'

// Helper function to get user authentication and role info
const getUserInfo = () => {
  try {
    const authToken = localStorage.getItem('authToken')
    const isLoggedIn = localStorage.getItem('isLoggedIn')
    const userData = JSON.parse(localStorage.getItem('userData') || '{}')
    
    const isAuth = !!(authToken && isLoggedIn === 'true')
    const role = (userData?.role?.name || '').toLowerCase()
    
    return {
      isAuth,
      role,
      userData
    }
  } catch (error) {
    console.error("Error parsing user data:", error)
    return {
      isAuth: false,
      role: '',
      userData: null
    }
  }
}

// Role hierarchy for permissions
const ROLES = {
  CASHIER: 'cashier',
  ADMIN: 'admin',
  SUPERUSER: 'superuser'
}

// Helper function to check if user has required role
const hasRole = (userRole, requiredRole) => {
  const roleHierarchy = {
    [ROLES.CASHIER]: [ROLES.CASHIER],
    [ROLES.ADMIN]: [ROLES.CASHIER, ROLES.ADMIN],
    [ROLES.SUPERUSER]: [ROLES.CASHIER, ROLES.ADMIN, ROLES.SUPERUSER]
  }
  
  return roleHierarchy[userRole]?.includes(requiredRole) || false
}

const routes = [
  {
    path: '/superuser',
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
      requiresGuest: true,
      title: 'Login - Mobiz POS'
    }
  },
  {
    path: '/signup',
    name: 'Signup',
    component: SignupPage,
    meta: {
      layout: 'auth',
      requiresGuest: true,
      title: 'Sign Up - Mobiz POS'
    }
  },

  // Dashboard - Available to all authenticated users
  {
    path: '/',
    name: 'Dashboard',
    component: DashboardPage,
    meta: {
      requiresAuth: true,
      title: 'Dashboard - Mobiz POS'
    }
  },

  // POS Sales - Available to admin and superuser (excluding cashier based on sidebar logic)
  {
    path: '/sales',
    name: 'Sales',
    component: SalesPage,
    meta: {
      requiresAuth: true,
      excludeRoles: [ROLES.CASHIER],
      title: 'POS Sales - Mobiz POS'
    }
  },

  // Products - Admin and SuperUser only
  {
    path: '/products',
    name: 'Products',
    component: ProductsPage,
    meta: {
      requiresAuth: true,
      requiresRole: ROLES.ADMIN,
      title: 'Products - Mobiz POS'
    }
  },

  // Inventory - Admin and SuperUser only
  {
    path: '/inventory',
    name: 'Inventory',
    component: InventoryPage,
    meta: {
      requiresAuth: true,
      requiresRole: ROLES.ADMIN,
      title: 'Inventory - Mobiz POS'
    }
  },

  // Reports - Admin and SuperUser only
  {
    path: '/reports',
    name: 'Reports',
    component: ReportsPage,
    meta: {
      requiresAuth: true,
      requiresRole: ROLES.ADMIN,
      title: 'Reports - Mobiz POS'
    }
  },

  // Expenses - Admin only (can be accessed by superuser due to hierarchy)
  {
    path: '/expenses',
    name: 'Expenses',
    component: ExpensePage,
    meta: {
      requiresAuth: true,
      requiresRole: ROLES.ADMIN,
      title: 'Expenses - Mobiz POS'
    }
  },

  // Admin Customization - Admin only
  {
    path: '/admin-customization',
    name: 'AdminCustomization',
    component: AdminCustomizationPage,
    meta: {
      requiresAuth: true,
      requiresRole: ROLES.ADMIN,
      title: 'Admin Customization - Mobiz POS'
    }
  },

  // SuperUser routes - SuperUser only
  {
    path: '/super-user',
    name: 'SuperUserDashboard',
    component: SuperUserDashboard,
    meta: {
      requiresAuth: true,
      requiresRole: ROLES.SUPERUSER,
      title: 'Super User Dashboard - Mobiz POS'
    }
  },
  {
    path: '/user-management',
    name: 'UserManagement',
    component: UserManagementPage,
    meta: {
      requiresAuth: true,
      requiresRole: ROLES.SUPERUSER,
      title: 'User Management - Mobiz POS'
    }
  },
  {
    path: '/system-logs',
    name: 'SystemLogs',
    component: SystemLogsPage,
    meta: {
      requiresAuth: true,
      requiresRole: ROLES.SUPERUSER,
      title: 'System Logs - Mobiz POS'
    }
  },
  {
    path: '/settings',
    name: 'Settings',
    component: SettingsPage,
    meta: {
      requiresAuth: true,
      requiresRole: ROLES.SUPERUSER,
      title: 'Settings - Mobiz POS'
    }
  },

  // Unauthorized access page
  {
    path: '/unauthorized',
    name: 'Unauthorized',
    component: () => import('../components/UnauthorizedPage.vue'),
    meta: {
      requiresAuth: true,
      title: 'Unauthorized Access - Mobiz POS'
    }
  },

  // 404 Not Found page
  {
    path: '/not-found',
    name: 'NotFound',
    component: () => import('../components/NotFoundPage.vue'),
    meta: {
      title: 'Page Not Found - Mobiz POS'
    }
  },

  // Catch-all redirect
  {
    path: '/:pathMatch(.*)*',
    redirect: (to) => {
      const { isAuth } = getUserInfo()
      
      // If user is authenticated but route doesn't exist, go to 404
      if (isAuth) {
        return '/not-found'
      }
      
      // If not authenticated, go to login
      return '/login'
    }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// Enhanced navigation guards
router.beforeEach((to, from, next) => {
  const { isAuth, role, userData } = getUserInfo()
  
  // Set page title
  if (to.meta.title) {
    document.title = to.meta.title
  }
  
  console.log('Navigation Guard:', {
    to: to.path,
    isAuth,
    role,
    requiresAuth: to.meta.requiresAuth,
    requiresGuest: to.meta.requiresGuest,
    requiresRole: to.meta.requiresRole,
    excludeRoles: to.meta.excludeRoles
  })

  // Handle guest-only routes (login, signup)
  if (to.meta.requiresGuest && isAuth) {
    console.log('Redirecting authenticated user from guest route to dashboard')
    return next('/')
  }

  // Handle authentication requirement
  if (to.meta.requiresAuth && !isAuth) {
    console.log('Redirecting unauthenticated user to login')
    return next('/login')
  }

  // Handle role exclusions (like cashier not accessing POS sales)
  if (to.meta.excludeRoles && to.meta.excludeRoles.includes(role)) {
    console.log(`Role ${role} is excluded from ${to.path}, redirecting to unauthorized`)
    return next('/unauthorized')
  }

  // Handle specific role requirements
  if (to.meta.requiresRole && !hasRole(role, to.meta.requiresRole)) {
    console.log(`Role ${role} doesn't have access to ${to.path} (requires ${to.meta.requiresRole}), redirecting to unauthorized`)
    return next('/unauthorized')
  }

  // Log successful navigation
  console.log(`Navigation allowed: ${from.path} → ${to.path}`)
  next()
})

// After navigation guard for additional logging
router.afterEach((to, from) => {
  console.log(`Navigation completed: ${from.path} → ${to.path}`)
})

// Handle navigation errors
router.onError((error) => {
  console.error('Router error:', error)
})

export default router