// src/utils/auth.js
/**
 * Authentication utility functions
 * Centralized authentication state management
 */

// Role constants
export const ROLES = {
  CASHIER: 'cashier',
  ADMIN: 'admin',
  SUPERUSER: 'superuser'
}

// Default redirect paths for each role
export const ROLE_REDIRECTS = {
  [ROLES.SUPERUSER]: '/super-user',
  [ROLES.ADMIN]: '/', // Dashboard with admin features
  [ROLES.CASHIER]: '/' // Dashboard with limited features
}

/**
 * Get user authentication and role information
 * @returns {Object} Authentication state and user data
 */
export const getUserInfo = () => {
  try {
    const authToken = localStorage.getItem('authToken')
    const isLoggedIn = localStorage.getItem('isLoggedIn')
    const userData = JSON.parse(localStorage.getItem('userData') || '{}')
    const sessionExpiry = localStorage.getItem('sessionExpiry')
    
    // Check if session has expired
    if (sessionExpiry && new Date().getTime() > parseInt(sessionExpiry)) {
      clearAuthData()
      return {
        isAuth: false,
        role: '',
        userData: null,
        isExpired: true
      }
    }
    
    const isAuth = !!(authToken && isLoggedIn === 'true')
    const role = (userData?.role?.name || '').toLowerCase()
    
    return {
      isAuth,
      role,
      userData: isAuth ? userData : null,
      isExpired: false
    }
  } catch (error) {
    console.error("Error parsing user data:", error)
    clearAuthData() // Clear corrupted data
    return {
      isAuth: false,
      role: '',
      userData: null,
      isExpired: false
    }
  }
}

/**
 * Set authentication data in localStorage
 * @param {Object} user - User data object
 * @param {string} token - Authentication token
 * @param {boolean} rememberMe - Whether to remember the user
 */
export const setAuthData = (user, token, rememberMe = false) => {
  try {
    localStorage.setItem('authToken', token)
    localStorage.setItem('isLoggedIn', 'true')
    localStorage.setItem('userData', JSON.stringify(user))
    
    if (rememberMe) {
      localStorage.setItem('rememberMe', 'true')
      // Set longer expiry for remember me (30 days)
      const expiryTime = new Date().getTime() + (30 * 24 * 60 * 60 * 1000)
      localStorage.setItem('sessionExpiry', expiryTime.toString())
    } else {
      // Regular session (24 hours)
      const expiryTime = new Date().getTime() + (24 * 60 * 60 * 1000)
      localStorage.setItem('sessionExpiry', expiryTime.toString())
    }
    
    console.log('Authentication data set successfully:', {
      user: user.name,
      role: user.role.name,
      rememberMe,
      token: token.substring(0, 20) + '...'
    })
    
    return true
  } catch (error) {
    console.error('Error setting auth data:', error)
    return false
  }
}

/**
 * Clear all authentication data
 */
export const clearAuthData = () => {
  const itemsToRemove = [
    'authToken',
    'isLoggedIn',
    'userData',
    'rememberMe',
    'sessionExpiry'
  ]
  
  itemsToRemove.forEach(item => {
    localStorage.removeItem(item)
  })
  
  console.log('Authentication data cleared')
}

/**
 * Check if user has required role (with hierarchy)
 * @param {string} userRole - Current user's role
 * @param {string} requiredRole - Required role
 * @returns {boolean} Whether user has the required role
 */
export const hasRole = (userRole, requiredRole) => {
  if (!userRole || !requiredRole) return false
  
  const roleHierarchy = {
    [ROLES.CASHIER]: [ROLES.CASHIER],
    [ROLES.ADMIN]: [ROLES.CASHIER, ROLES.ADMIN],
    [ROLES.SUPERUSER]: [ROLES.CASHIER, ROLES.ADMIN, ROLES.SUPERUSER]
  }
  
  const normalizedUserRole = userRole.toLowerCase()
  const normalizedRequiredRole = requiredRole.toLowerCase()
  
  return roleHierarchy[normalizedUserRole]?.includes(normalizedRequiredRole) || false
}

/**
 * Check if user is excluded from accessing a route
 * @param {string} userRole - Current user's role
 * @param {Array} excludeRoles - Roles to exclude
 * @returns {boolean} Whether user is excluded
 */
export const isRoleExcluded = (userRole, excludeRoles) => {
  if (!excludeRoles || !Array.isArray(excludeRoles)) return false
  
  return excludeRoles.includes(userRole.toLowerCase())
}

/**
 * Get the default redirect path for a role
 * @param {string} role - User's role
 * @returns {string} Redirect path
 */
export const getRoleRedirect = (role) => {
  const normalizedRole = role.toLowerCase()
  return ROLE_REDIRECTS[normalizedRole] || '/'
}

/**
 * Format role name for display
 * @param {string} roleName - Raw role name
 * @returns {string} Formatted role name
 */
export const formatRoleName = (roleName) => {
  if (!roleName) return 'Unknown Role'
  
  const formatted = roleName.charAt(0).toUpperCase() + roleName.slice(1).toLowerCase()
  
  // Handle special cases
  const specialCases = {
    'Superuser': 'Super User',
    'Admin': 'Administrator'
  }
  
  return specialCases[formatted] || formatted
}

/**
 * Check if current session is valid
 * @returns {boolean} Whether session is valid
 */
export const isSessionValid = () => {
  const { isAuth, isExpired } = getUserInfo()
  return isAuth && !isExpired
}

/**
 * Refresh session expiry
 * @param {boolean} rememberMe - Whether user chose remember me
 */
export const refreshSession = (rememberMe = false) => {
  const { isAuth } = getUserInfo()
  
  if (isAuth) {
    const expiryTime = rememberMe 
      ? new Date().getTime() + (30 * 24 * 60 * 60 * 1000) // 30 days
      : new Date().getTime() + (24 * 60 * 60 * 1000) // 24 hours
    
    localStorage.setItem('sessionExpiry', expiryTime.toString())
    console.log('Session refreshed')
  }
}

/**
 * Validate user permissions for a specific action
 * @param {string} action - Action to check
 * @param {string} userRole - User's role
 * @returns {boolean} Whether user can perform action
 */
export const canPerformAction = (action, userRole = null) => {
  const { role } = userRole ? { role: userRole } : getUserInfo()
  
  const permissions = {
    // Cashier permissions
    [ROLES.CASHIER]: [
      'view_dashboard',
      'view_own_sales'
    ],
    
    // Admin permissions (includes cashier permissions)
    [ROLES.ADMIN]: [
      'view_dashboard',
      'view_own_sales',
      'view_all_sales',
      'manage_products',
      'manage_inventory',
      'view_reports',
      'manage_expenses',
      'admin_customization'
    ],
    
    // SuperUser permissions (includes all permissions)
    [ROLES.SUPERUSER]: [
      'view_dashboard',
      'view_own_sales',
      'view_all_sales',
      'manage_products',
      'manage_inventory',
      'view_reports',
      'manage_expenses',
      'admin_customization',
      'manage_users',
      'view_system_logs',
      'system_settings',
      'super_admin_access'
    ]
  }
  
  return permissions[role]?.includes(action) || false
}

// Export default object with all functions
export default {
  ROLES,
  ROLE_REDIRECTS,
  getUserInfo,
  setAuthData,
  clearAuthData,
  hasRole,
  isRoleExcluded,
  getRoleRedirect,
  formatRoleName,
  isSessionValid,
  refreshSession,
  canPerformAction
}