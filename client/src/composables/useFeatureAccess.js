/**
 * Feature Access Control Composable
 * 
 * Provides utilities for checking subscription features on the client-side.
 * Works in conjunction with backend feature middleware for complete access control.
 */

import { ref, computed } from 'vue'

const FEATURE_CACHE_KEY = 'companySubscriptionFeaturesCache'
const FEATURE_CACHE_TTL_MS = 60 * 1000 // 1 minute

const getAuthToken = () => {
  return localStorage.getItem('authToken') || localStorage.getItem('token') || ''
}

/**
 * Normalize feature name for comparison
 */
export const normalizeFeature = (feature) => {
  return String(feature || '')
    .trim()
    .toLowerCase()
    .replace(/[\s-]+/g, '_')
}

export const extractFeatureKeys = (rawFeatures = []) => {
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

/**
 * Read cached features from localStorage
 */
export const readCachedFeatures = ({ allowStale = false } = {}) => {
  try {
    const raw = localStorage.getItem(FEATURE_CACHE_KEY)
    if (!raw) return null
    
    const parsed = JSON.parse(raw)
    if (!parsed?.ts || !Array.isArray(parsed.features)) return null
    if ((parsed.token || '') !== getAuthToken()) return null
    if (!allowStale && Date.now() - parsed.ts > FEATURE_CACHE_TTL_MS) return null
    
    return parsed.features
  } catch (_e) {
    return null
  }
}

/**
 * Write features to localStorage cache
 */
export const writeCachedFeatures = (features) => {
  const normalized = extractFeatureKeys(features)
  localStorage.setItem(FEATURE_CACHE_KEY, JSON.stringify({
    ts: Date.now(),
    token: getAuthToken(),
    features: normalized
  }))
}

/**
 * Fetch company subscription features from API
 */
export const fetchSubscriptionFeatures = async ({ forceRefresh = false } = {}) => {
  const cached = forceRefresh ? null : readCachedFeatures()
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
        writeCachedFeatures([])
        return []
      }

      if (response.status === 401 || response.status === 419) {
        const stale = readCachedFeatures({ allowStale: true })
        if (!forceRefresh && stale) return stale
      }
      throw new Error(`Failed to load subscription (${response.status})`)
    }

    const data = await response.json()
    const rawFeatures = Array.isArray(data?.subscription?.features)
      ? data.subscription.features
      : []
    const features = extractFeatureKeys(rawFeatures)

    writeCachedFeatures(features)
    return features
  } catch (error) {
    const stale = readCachedFeatures({ allowStale: true })
    if (!forceRefresh && stale) {
      console.warn('Using stale feature cache due to temporary fetch issue', error)
      return stale
    }
    throw error
  }
}

/**
 * Check if user has at least one of the required features
 */
export const hasAnyFeature = (availableFeatures, requiredFeatures) => {
  const allowed = new Set((availableFeatures || []).map(normalizeFeature))
  
  // Wildcard feature means access to everything
  if (allowed.has('*') || allowed.has('all')) return true

  // Check if user has at least one required feature
  if (!Array.isArray(requiredFeatures)) {
    requiredFeatures = [requiredFeatures]
  }

  return requiredFeatures.some((required) => {
    const normalized = normalizeFeature(required)
    return allowed.has(normalized)
  })
}

/**
 * Check if user has a specific feature
 */
export const hasFeature = (availableFeatures, feature) => {
  return hasAnyFeature(availableFeatures, [feature])
}

/**
 * Vue composable for feature access control
 */
export const useFeatureAccess = () => {
  const features = ref([])
  const loading = ref(false)
  const error = ref(null)

  const loadFeatures = async () => {
    loading.value = true
    error.value = null
    try {
      features.value = await fetchSubscriptionFeatures()
    } catch (err) {
      error.value = err.message
      console.error('Failed to load features:', err)
    } finally {
      loading.value = false
    }
  }

  const hasAccess = (requiredFeatures) => {
    return hasAnyFeature(features.value, requiredFeatures)
  }

  const refreshFeatures = async () => {
    loading.value = true
    error.value = null
    try {
      features.value = await fetchSubscriptionFeatures({ forceRefresh: true })
    } catch (err) {
      error.value = err.message
      console.error('Failed to refresh features:', err)
    } finally {
      loading.value = false
    }
  }

  const clearCache = () => {
    localStorage.removeItem(FEATURE_CACHE_KEY)
    features.value = []
  }

  return {
    features: computed(() => features.value),
    loading: computed(() => loading.value),
    error: computed(() => error.value),
    loadFeatures,
    refreshFeatures,
    hasAccess,
    clearCache,
  }
}

/**
 * Feature availability matrix for UI/UX
 * Maps features to user-friendly descriptions and icons
 */
export const FEATURE_DESCRIPTIONS = {
  sales: {
    name: 'Sales Management',
    description: 'Process and record sales transactions',
    icon: 'fas fa-cash-register',
    category: 'Sales & Transactions',
  },
  mpesa: {
    name: 'M-Pesa Integration',
    description: 'Accept M-Pesa mobile money payments',
    icon: 'fas fa-mobile',
    category: 'Sales & Transactions',
  },
  credit: {
    name: 'Credit System',
    description: 'Manage customer credit and debt',
    icon: 'fas fa-handshake',
    category: 'Sales & Transactions',
  },
  promotions: {
    name: 'Promotions',
    description: 'Create and manage sales promotions',
    icon: 'fas fa-tag',
    category: 'Sales & Transactions',
  },
  inventory: {
    name: 'Inventory Management',
    description: 'Track stock levels and movements',
    icon: 'fas fa-boxes',
    category: 'Inventory & Stock',
  },
  purchases: {
    name: 'Purchase Orders',
    description: 'Create and manage purchase orders',
    icon: 'fas fa-shopping-cart',
    category: 'Inventory & Stock',
  },
  warehouse: {
    name: 'Warehouse Management',
    description: 'Manage multiple warehouse locations',
    icon: 'fas fa-warehouse',
    category: 'Inventory & Stock',
  },
  stock_transfers: {
    name: 'Stock Transfers',
    description: 'Transfer stock between locations',
    icon: 'fas fa-exchange-alt',
    category: 'Inventory & Stock',
  },
  customer_management: {
    name: 'Customer Management',
    description: 'Manage customer profiles and history',
    icon: 'fas fa-users',
    category: 'Customers & Suppliers',
  },
  suppliers: {
    name: 'Supplier Management',
    description: 'Manage supplier information',
    icon: 'fas fa-truck',
    category: 'Customers & Suppliers',
  },
  sms: {
    name: 'SMS Notifications',
    description: 'Send SMS to customers and suppliers',
    icon: 'fas fa-sms',
    category: 'Customers & Suppliers',
  },
  tax_configuration: {
    name: 'Tax Configuration',
    description: 'Configure and manage tax settings',
    icon: 'fas fa-percentage',
    category: 'Financial & Tax',
  },
  expenses: {
    name: 'Expense Tracking',
    description: 'Record and track business expenses',
    icon: 'fas fa-receipt',
    category: 'Financial & Tax',
  },
  invoicing: {
    name: 'Invoicing',
    description: 'Create and send professional invoices',
    icon: 'fas fa-file-invoice',
    category: 'Financial & Tax',
  },
  price_groups: {
    name: 'Price Groups',
    description: 'Manage customer-specific pricing tiers',
    icon: 'fas fa-layer-group',
    category: 'Financial & Tax',
  },
  reports: {
    name: 'Reports & Analytics',
    description: 'Generate sales, inventory, and financial reports',
    icon: 'fas fa-chart-bar',
    category: 'Reporting & Analytics',
  },
  data_export: {
    name: 'Data Export',
    description: 'Export data to CSV/Excel',
    icon: 'fas fa-download',
    category: 'Reporting & Analytics',
  },
  audit_logs: {
    name: 'Audit Logs',
    description: 'View transaction and user activity logs',
    icon: 'fas fa-history',
    category: 'Reporting & Analytics',
  },
  user_management: {
    name: 'User Management',
    description: 'Manage users and assign roles',
    icon: 'fas fa-user-tie',
    category: 'System & Administration',
  },
  printer_config: {
    name: 'Printer Configuration',
    description: 'Configure receipt printer settings',
    icon: 'fas fa-print',
    category: 'System & Administration',
  },
  returns: {
    name: 'Product Returns',
    description: 'Process and manage product returns',
    icon: 'fas fa-undo',
    category: 'System & Administration',
  },
  advanced_settings: {
    name: 'Advanced Settings',
    description: 'Access advanced system configuration',
    icon: 'fas fa-cogs',
    category: 'System & Administration',
  },
}

/**
 * Get features by category for UI grouping
 */
export const getFeaturesByCategory = () => {
  const grouped = {}
  
  Object.entries(FEATURE_DESCRIPTIONS).forEach(([featureKey, feature]) => {
    const category = feature.category
    if (!grouped[category]) {
      grouped[category] = []
    }
    grouped[category].push({ key: featureKey, ...feature })
  })

  return grouped
}
