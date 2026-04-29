// src/router/index.js
import { createRouter, createWebHistory } from 'vue-router'
//import users pages(cashier)
import DashboardPage from '../pages/Users/DashboardPage.vue'
import InventoryPage from '../pages/Users/InventoryPage.vue'
import ProductsPage from '../pages/Users/ProductsPage.vue'
import SalesPage from '../pages/Users/SalesPage.vue'
import ReportsPage from '../pages/Users/ReportPage.vue'
import ExpensePage from '../pages/Users/ExpensePage.vue'
const SettingsPage = () => import('../pages/Users/SettingsPage.vue')

//import superuser pages
import SuperUserDashboard from '../pages/superuser/SuperUserDashboard.vue'
import SystemLogsPage from '../pages/superuser/SystemLogsPage.vue'
import UserManagementPage from '../pages/superuser/UserManagementPage.vue'
import GlobalSettingsPage from '../pages/superuser/GlobalSettingsPage.vue'
import SuperUserSettingsPage from '../pages/superuser/SuperUserSettingsPage.vue'
import SubscriptionManagementPage from '../pages/superuser/SubscriptionsPage.vue'
import DataExportPage from '../pages/superuser/DataExportPage.vue'
import AuditLogsPage from '../pages/superuser/AuditLogsPage.vue'
import ImpersonatePage from '../pages/superuser/ImpersonatePage.vue'
import SupportPage from '../pages/superuser/SupportPage.vue'
import ProfilePage from '../pages/superuser/ProfilePage.vue'
import DiagnosticsPage from '../pages/superuser/DiagnosticsPage.vue'

import LoginPage from '../pages/Auth/LoginPage.vue'
import SignupPage from '../pages/Auth/SignupPage.vue'

import AdminCustomizationPage from '../pages/Admin/AdminCustomizationPage.vue'
import WarehousesPage from '../pages/Admin/WarehousesPage.vue'
import BusinessCategoriesPage from '../pages/Admin/BusinessCategoriesPage.vue'
import ManageUsersPage from '../pages/Admin/ManageUsersPage.vue'
import AdminSettingsPage from '../pages/Admin/AdminSettingsPage.vue'
import PromotionsPage from '../pages/Admin/PromotionsPage.vue'
import AccountsManagementPage from '../pages/Admin/AccountsManagementPage.vue'
import AdminAuditLogsPage from '../pages/Admin/AdminAuditLogsPage.vue'
import MessagingPage from '../pages/admin/MessagingPage.vue'
// New admin company profile page (to add)
const CompanyProfilePage = () => import('../pages/Admin/CompanyProfilePage.vue')

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

// Subscription feature gating (for admin/cashier company packages)
const SUBSCRIPTION_FEATURE_CACHE_KEY = 'companySubscriptionFeaturesCache'
const SUBSCRIPTION_FEATURE_CACHE_TTL_MS = 60 * 1000
const IS_DEV = typeof import.meta !== 'undefined' && Boolean(import.meta.env?.DEV)

const debugLog = (...args) => {
  if (IS_DEV) {
    console.log(...args)
  }
}

const getAuthToken = () => {
  return localStorage.getItem('authToken') || localStorage.getItem('token') || ''
}

const normalizeFeature = (feature) => String(feature || '')
  .trim()
  .toLowerCase()
  .replace(/[\s-]+/g, '_')

const extractFeatureKeys = (rawFeatures = []) => {
  if (!rawFeatures) return []

  if (typeof rawFeatures === 'object' && !Array.isArray(rawFeatures)) {
    return Object.entries(rawFeatures)
      .filter(([, isEnabled]) => Boolean(isEnabled))
      .map(([key]) => normalizeFeature(key))
      .filter(Boolean)
  }

  if (!Array.isArray(rawFeatures)) return []

  return rawFeatures
    .flatMap((feature) => {
      if (typeof feature === 'string') return [feature]
      if (!feature || typeof feature !== 'object') return []

      return [
        feature.slug,
        feature.key,
        feature.code,
        feature.name,
        feature.feature,
        feature.id,
      ].filter(Boolean)
    })
    .map(normalizeFeature)
    .filter(Boolean)
}

const featureAliases = {
  sales: ['sales', 'pos_sales', 'point_of_sale'],
  products: ['products', 'product_management', 'catalog'],
  inventory: ['inventory', 'stock', 'stock_management'],
  reports: ['reports', 'analytics', 'reporting'],
  expenses: ['expenses', 'expense_tracking', 'finance'],
  promotions: ['promotions', 'marketing', 'discounts'],
  accounts: ['accounts', 'accounting', 'ledger'],
  admin_customization: ['admin_customization', 'customization', 'settings_admin'],
  user_management: ['user_management', 'users', 'staff_management', 'team_management'],
  mpesa: ['mpesa', 'mobile_money', 'm_pesa'],
  sms: ['sms', 'messaging'],
  credit: ['credit', 'credit_system'],
  purchases: ['purchases', 'purchase_orders', 'po'],
  warehouse: ['warehouse', 'warehouses', 'warehouse_management'],
  stock_transfers: ['stock_transfers', 'stock_transfer'],
  customer_management: ['customer_management', 'customers'],
  suppliers: ['suppliers', 'vendor_management'],
  tax_configuration: ['tax_configuration', 'tax', 'tax_settings'],
  invoicing: ['invoicing', 'invoices'],
  price_groups: ['price_groups', 'pricing', 'price_tiers'],
  data_export: ['data_export', 'exports', 'data_exports'],
  audit_logs: ['audit_logs', 'audit', 'logs'],
  printer_config: ['printer_config', 'printer', 'printer_settings'],
  returns: ['returns', 'product_returns'],
  advanced_settings: ['advanced_settings', 'settings'],
}

const routeFeatureRequirements = {
  '/products': ['products', 'inventory'],
  '/inventory': ['inventory'],
  '/reports': ['reports'],
  '/expenses': ['expenses'],
  '/promotions': ['promotions'],
  '/accounts': ['accounts'],
  '/admin-customization': ['admin_customization'],
  '/users': ['user_management'],
  '/customers': ['customer_management'],
  '/suppliers': ['suppliers'],
  '/purchases': ['purchases'],
  '/warehouse': ['warehouse'],
  '/warehouses': ['warehouse'],
  '/stock-transfers': ['stock_transfers'],
  '/credit': ['credit'],
  '/invoices': ['invoicing'],
  '/returns': ['returns'],
  '/audit-logs': ['audit_logs'],
  '/settings': ['advanced_settings'],
  '/payment-methods': ['mpesa', 'sms'],
  '/sms': ['sms'],
  '/printer-settings': ['printer_config'],
}

const featureDisplayLabels = {
  sales: 'Sales',
  products: 'Products',
  inventory: 'Inventory',
  reports: 'Reports',
  expenses: 'Expenses',
  promotions: 'Promotions',
  accounts: 'Accounts',
  admin_customization: 'Admin Customization',
  user_management: 'Manage Users',
  mpesa: 'M-Pesa Integration',
  sms: 'SMS Notifications',
  credit: 'Credit System',
  purchases: 'Purchase Orders',
  warehouse: 'Warehouse Management',
  stock_transfers: 'Stock Transfers',
  customer_management: 'Customer Management',
  suppliers: 'Supplier Management',
  tax_configuration: 'Tax Configuration',
  invoicing: 'Invoicing',
  price_groups: 'Price Groups',
  data_export: 'Data Export',
  audit_logs: 'Audit Logs',
  printer_config: 'Printer Configuration',
  returns: 'Product Returns',
  advanced_settings: 'Advanced Settings',
}

const getRouteRequiredFeatures = (path) => routeFeatureRequirements[path] || null
const alwaysOpenSubscriptionPaths = new Set(['/', '/sales'])

const redirectUnauthorized = (next, payload = {}) => {
  next({
    path: '/unauthorized',
    query: payload,
  })
}

// Foundational features that should always be accessible to company admins
const alwaysOpenAdminPaths = new Set(['/products', '/inventory', '/reports'])

const hasAnyFeature = (availableFeatures, requiredFeatures) => {
  const allowed = new Set((availableFeatures || []).map(normalizeFeature))
  if (allowed.has('*') || allowed.has('all')) return true

  return requiredFeatures.some((requiredFeature) => {
    const normalizedRequired = normalizeFeature(requiredFeature)
    const aliases = featureAliases[normalizedRequired] || [normalizedRequired]
    return aliases.some((candidate) => allowed.has(candidate))
  })
}

const readCachedSubscriptionFeatures = ({ allowStale = false } = {}) => {
  try {
    const raw = localStorage.getItem(SUBSCRIPTION_FEATURE_CACHE_KEY)
    if (!raw) return null
    const parsed = JSON.parse(raw)
    if (!parsed?.ts || !Array.isArray(parsed.features)) return null
    const activeToken = getAuthToken()
    if ((parsed.token || '') !== activeToken) return null
    if (!allowStale && Date.now() - parsed.ts > SUBSCRIPTION_FEATURE_CACHE_TTL_MS) return null
    return parsed.features
  } catch (_e) {
    return null
  }
}

const writeCachedSubscriptionFeatures = (features) => {
  const normalized = extractFeatureKeys(features)
  localStorage.setItem(SUBSCRIPTION_FEATURE_CACHE_KEY, JSON.stringify({
    ts: Date.now(),
    token: getAuthToken(),
    features: normalized
  }))
}

const fetchCompanySubscriptionFeatures = async ({ forceRefresh = false } = {}) => {
  const cached = forceRefresh ? null : readCachedSubscriptionFeatures()
  if (cached) return cached

  const token = getAuthToken()
  const headers = token ? { Authorization: `Bearer ${token}` } : {}

  try {
    const response = await fetch('/api/company/subscription', {
      method: 'GET',
      headers,
      credentials: 'include'
    })

    if (!response.ok) {
      if (response.status === 404) {
        writeCachedSubscriptionFeatures([])
        return []
      }

      // If auth/session is not fully ready yet, don't hard-fail gate checks.
      if (response.status === 401 || response.status === 419) {
        const stale = readCachedSubscriptionFeatures({ allowStale: true })
        if (!forceRefresh && stale) return stale
        throw new Error(`Subscription auth not ready (${response.status})`)
      }

      throw new Error(`Failed to load company subscription (${response.status})`)
    }

    const data = await response.json()
    const rawFeatures = Array.isArray(data?.subscription?.features)
      ? data.subscription.features
      : []

    const features = extractFeatureKeys(rawFeatures)

    writeCachedSubscriptionFeatures(features)
    return features
  } catch (error) {
    const stale = readCachedSubscriptionFeatures({ allowStale: true })
    if (!forceRefresh && stale) {
      console.warn('Using stale subscription feature cache due to fetch issue', error)
      return stale
    }
    throw error
  }
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

  // POS Sales - Available to both admin and cashier
  {
    path: '/sales',
    name: 'Sales',
    component: SalesPage,
    meta: {
      requiresAuth: true,
      requiresRole: ROLES.CASHIER,
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
  // Company Profile - Admin and SuperUser
  {
    path: '/company-profile',
    name: 'CompanyProfile',
    component: CompanyProfilePage,
    meta: {
      requiresAuth: true,
      requiresRole: ROLES.ADMIN,
      title: 'Company Profile - Mobiz POS'
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

  // Promotions - Admin only
  {
    path: '/promotions',
    name: 'Promotions',
    component: PromotionsPage,
    meta: {
      requiresAuth: true,
      requiresRole: ROLES.ADMIN,
      title: 'Promotions & Discounts - Mobiz POS'
    }
  },

  // Accounts Management - Admin only
  {
    path: '/accounts',
    name: 'AccountsManagement',
    component: AccountsManagementPage,
    meta: {
      requiresAuth: true,
      requiresRole: ROLES.ADMIN,
      title: 'Accounts Management - Mobiz POS'
    }
  },

  // Admin Audit Logs - company-scoped transaction and activity monitoring
  {
    path: '/admin/audit-logs',
    name: 'AdminAuditLogs',
    component: AdminAuditLogsPage,
    meta: {
      requiresAuth: true,
      requiresRole: ROLES.ADMIN,
      title: 'Company Audit Logs - Mobiz POS'
    }
  },

  // Messaging (SMS/Email) - Admin only
  {
    path: '/messaging',
    name: 'Messaging',
    component: MessagingPage,
    meta: {
      requiresAuth: true,
      requiresRole: ROLES.ADMIN,
      requiredFeatures: ['sms'],
      title: 'Messaging Center - Mobiz POS'
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
    path: '/superuser/settings',
    name: 'SuperUserSettings',
    component: SuperUserSettingsPage,
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
    path: '/diagnostics',
    name: 'SuperUserDiagnostics',
    component: DiagnosticsPage,
    meta: {
      requiresAuth: true,
      requiresRole: ROLES.SUPERUSER,
      title: 'Request Diagnostics - Mobiz POS'
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
  {
    path: '/profile',
    name: 'Profile',
    component: ProfilePage,
    meta: {
      requiresAuth: true,
      requiresRole: ROLES.CASHIER,
      title: 'My Profile - Mobiz POS'
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
    path: '/superuser/diagnostics',
    redirect: '/diagnostics'
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
  {
    path: '/superuser/profile',
    redirect: '/profile'
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
    name: 'Settings',
    component: SettingsPage,
    meta: {
      requiresAuth: true,
      requiresRole: ROLES.CASHIER, // Allow all roles (Cashier, Admin, SuperUser)
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
router.beforeEach(async (to, from, next) => {
  const { isAuth, role, userData } = getUserInfo()
  
  // Set page title
  if (to.meta.title) {
    document.title = to.meta.title
  }
  
  debugLog('Navigation Guard:', {
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
    debugLog('Redirecting authenticated user from guest route to dashboard')
    const redirectPath = role === 'superuser' ? '/super-user' : '/'
    return next(redirectPath)
  }

  // Handle authentication requirement
  if (to.meta.requiresAuth && !isAuth) {
    debugLog('Redirecting unauthenticated user to login')
    return next('/login')
  }

  // Handle role exclusions (like cashier not accessing POS sales)
  if (to.meta.excludeRoles && to.meta.excludeRoles.includes(role)) {
    debugLog(`Role ${role} is excluded from ${to.path}, redirecting to unauthorized`)
    return redirectUnauthorized(next, {
      reason: 'role_excluded',
      path: to.path,
      role,
    })
  }

  // Handle specific role requirements
  if (to.meta.requiresRole && !hasRole(role, to.meta.requiresRole)) {
    debugLog(`Role ${role} doesn't have access to ${to.path} (requires ${to.meta.requiresRole}), redirecting to unauthorized`)
    return redirectUnauthorized(next, {
      reason: 'role_required',
      path: to.path,
      role,
      required_role: to.meta.requiresRole,
    })
  }

  // Enforce subscription feature access for admin/cashier routes.
  // Superuser is intentionally not limited by company subscription features.
  const isAdmin = role === ROLES.ADMIN
  const bypassSubscriptionFeatureGuard = alwaysOpenSubscriptionPaths.has(to.path)
    || (isAdmin && alwaysOpenAdminPaths.has(to.path))
  const requiredFeatures = bypassSubscriptionFeatureGuard
    ? null
    : (to.meta.requiredFeatures || getRouteRequiredFeatures(to.path))
  const isCompanyScopedRole = role === ROLES.ADMIN || role === ROLES.CASHIER
  if (requiredFeatures && isCompanyScopedRole) {
    try {
      let availableFeatures = await fetchCompanySubscriptionFeatures()

      if (import.meta.env.DEV) {
        debugLog('Subscription guard feature check', {
          path: to.path,
          role,
          requiredFeatures,
          availableFeatures,
        })
      }

      // One forced re-fetch before blocking, to avoid cache/auth timing false negatives.
      if (!hasAnyFeature(availableFeatures, requiredFeatures)) {
        availableFeatures = await fetchCompanySubscriptionFeatures({ forceRefresh: true })

        if (import.meta.env.DEV) {
          debugLog('Subscription guard forced refresh result', {
            path: to.path,
            requiredFeatures,
            availableFeatures,
          })
        }
      }

      if (!hasAnyFeature(availableFeatures, requiredFeatures)) {
        debugLog(`Route ${to.path} blocked by subscription package. Required: ${requiredFeatures.join(', ')}`)
        const primaryFeature = requiredFeatures[0]
        return redirectUnauthorized(next, {
          reason: 'subscription_feature',
          path: to.path,
          role,
          feature: primaryFeature,
          feature_label: featureDisplayLabels[primaryFeature] || primaryFeature,
        })
      }
    } catch (error) {
      // Avoid false negatives caused by transient auth/cache/network timing on first load.
      // Backend feature middleware still enforces true access control.
      console.warn('Subscription feature check failed; allowing navigation and relying on backend enforcement', error)
    }
  }

  // Log successful navigation
  debugLog(`Navigation allowed: ${from.path} -> ${to.path}`)
  next()
})

// After navigation guard for additional logging
router.afterEach((to, from) => {
  debugLog(`Navigation completed: ${from.path} -> ${to.path}`)
})

// Handle navigation errors
router.onError((error) => {
  console.error('Router error:', error)
})

export default router