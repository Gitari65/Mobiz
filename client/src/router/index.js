// src/router/index.js
import { createRouter, createWebHistory } from 'vue-router'
//import users pages(cashier)
import DashboardPage from '../pages/Users/DashboardPage.vue'
import InventoryPage from '../pages/Users/InventoryPage.vue'
import ProductsPage from '../pages/Users/ProductsPage.vue'
import SalesPage from '../pages/Users/SalesPage.vue'
import ReportsPage from '../pages/Users/ReportPage.vue'
import ExpensePage from '../pages/Users/ExpensePage.vue'

//import superuser pages
import SuperUserDashboard from '../pages/superuser/SuperUserDashboard.vue'
import SystemLogsPage from '../pages/superuser/SystemLogsPage.vue'
import UserManagementPage from '../pages/superuser/UserManagementPage.vue'
import GlobalSettingsPage from '../pages/superuser/GlobalSettingsPage.vue'
import SettingsPage from '../pages/superuser/SettingsPage.vue'
import SubscriptionManagementPage from '../pages/superuser/SubscriptionsPage.vue'
import DataExportPage from '../pages/superuser/DataExportPage.vue'
import AuditLogsPage from '../pages/superuser/AuditLogsPage.vue'
import ImpersonatePage from '../pages/superuser/ImpersonatePage.vue'
import SupportPage from '../pages/superuser/SupportPage.vue' // <-- added

import LoginPage from '../pages/Auth/LoginPage.vue'
import SignupPage from '../pages/Auth/SignupPage.vue'

import AdminCustomizationPage from '../pages/admin/AdminCustomizationPage.vue'
import WarehousesPage from '../pages/admin/WarehousesPage.vue'
import BusinessCategoriesPage from '../pages/admin/BusinessCategoriesPage.vue'
import ManageUsersPage from '../pages/admin/ManageUsersPage.vue'
import AdminSettingsPage from '../pages/admin/AdminSettingsPage.vue'

import UnauthorizedPage from '../components/UnauthorizedPage.vue'
import NotFoundPage from '../components/NotFoundPage.vue'



// Helper function to get user authentication and role info
const getUserInfo = () => {
  try {
    const authToken = localStorage.getItem('authToken');
    const isLoggedIn = localStorage.getItem('isLoggedIn');
    const userData = JSON.parse(localStorage.getItem('userData') || '{}');

    // Treat explicit isLoggedIn flag as authoritative (fixes redirect loop when token is empty)
    const isAuth = (isLoggedIn === 'true') || (!!authToken && authToken.length > 0);
    const role = (userData?.role?.name || '').toLowerCase();

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
      title: 'Dashboard - Mobiz POS',
      requiresRole: ROLES.CASHIER
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
  {
    path: '/global-settings',
    name: 'GlobalSettings',
    component: GlobalSettingsPage,
    meta: {
      requiresAuth: true,
      requiresRole: ROLES.SUPERUSER,
      title: 'Global Settings - Mobiz POS'
    }
  },
  {
    path: '/subscriptions',
    name: 'SubscriptionManagement',
    component: SubscriptionManagementPage,
    meta: {
      requiresAuth: true,
      requiresRole: ROLES.SUPERUSER,
      title: 'Subscription Management - Mobiz POS'
    }
  },
  {
    path: '/data-export',
    name: 'DataExport',
    component: DataExportPage,
    meta: {
      requiresAuth: true,
      requiresRole: ROLES.SUPERUSER,
      title: 'Data Export - Mobiz POS'
    }
  },
  {
    path: '/audit-logs',
    name: 'AuditLogs',
    component: AuditLogsPage,
    meta: {
      requiresAuth: true,
      requiresRole: ROLES.SUPERUSER,
      title: 'Audit Logs - Mobiz POS'
    }
  },
  {
    path: '/support',
    name: 'Support',
    component: SupportPage,
    meta: {
      requiresAuth: true,
      requiresRole: ROLES.SUPERUSER,
      title: 'Support & Communication - Mobiz POS'
    }
  },
  {
    path: '/impersonate',
    name: 'Impersonate',
    component: ImpersonatePage,
    meta: {
      requiresAuth: true,
      requiresRole: ROLES.SUPERUSER,
      title: 'Impersonate User - Mobiz POS'
    }
  },
  // --- Compatibility redirects for old /superuser/* links used in some components ---
  {
    path: '/superuser',
    redirect: '/super-user'
  },
  {
    path: '/superuser/users',
    redirect: '/user-management'
  },
  {
    path: '/superuser/user-management', // <-- added missing alias used by the sidebar
    redirect: '/user-management'
  },
  {
    path: '/superuser/audit-logs',
    redirect: '/audit-logs'
  },
  {
    path: '/superuser/global-settings',
    redirect: '/global-settings'
  },
  {
    path: '/superuser/data-export',
    redirect: '/data-export'
  },
  {
    path: '/superuser/support',
    redirect: '/support' // <-- now points to the new /support route
  },
  {
    path: '/superuser/subscriptions',
    redirect: '/subscriptions'
  },
  {
    path: '/superuser/impersonate',
    redirect: '/impersonate'
  },

  // Admin-only pages (also accessible to superuser)
  {
    path: '/warehouses',
    name: 'Warehouses',
    component: WarehousesPage,
    meta: {
      requiresAuth: true,
      requiresRole: ROLES.ADMIN,
      title: 'Warehouses - Mobiz POS'
    }
  },
  {
    path: '/business-categories',
    name: 'BusinessCategories',
    component: BusinessCategoriesPage,
    meta: {
      requiresAuth: true,
      requiresRole: ROLES.ADMIN,
      title: 'Business Categories - Mobiz POS'
    }
  },
  {
    path: '/users',
    name: 'ManageUsers',
    component: ManageUsersPage,
    meta: {
      requiresAuth: true,
      requiresRole: ROLES.ADMIN,
      title: 'Manage Users - Mobiz POS'
    }
  },
  {
    path: '/settings',
    name: 'AdminSettings',
    component: AdminSettingsPage,
    meta: {
      requiresAuth: true,
      requiresRole: ROLES.ADMIN,
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
      const { isAuth, role } = getUserInfo()
      
      // If user is authenticated, redirect to their role landing page instead of automatically showing 404
      if (isAuth) {
        // role is normalized to lower-case in getUserInfo()
        if (role === 'superuser') return '/super-user'
        return '/' // default authenticated landing
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
    const redirectPath = role === 'superuser' ? '/super-user' : '/'
    return next(redirectPath)
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