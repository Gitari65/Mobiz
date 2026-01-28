// src/services/api.js
/**
 * API Service for communicating with Laravel backend
 */

import axios from 'axios' // added

// Use backend base URL (no extra /api prefix)
const BASE_URL = 'http://127.0.0.1:8000'

// Default headers for all requests
const defaultHeaders = {
  'Content-Type': 'application/json',
  'Accept': 'application/json',
}

// Add auth token to headers if available
const getAuthHeaders = () => {
  const token = localStorage.getItem('authToken')
  return token ? { ...defaultHeaders, 'Authorization': `Bearer ${token}` } : defaultHeaders
}

/**
 * Generic API request handler
 * @param {string} endpoint - API endpoint
 * @param {Object} options - Fetch options
 * @returns {Promise} API response
 */
const apiRequest = async (endpoint, options = {}) => {
  try {
    const url = `${BASE_URL}${endpoint}`
    const config = {
      headers: getAuthHeaders(),
      ...options,
    }

    console.log(`API Request: ${config.method || 'GET'} ${url}`)

    const response = await fetch(url, config)
    const data = await response.json()

    if (!response.ok) {
      throw {
        status: response.status,
        message: data.error || data.message || 'Request failed',
        data
      }
    }

    return {
      success: true,
      data,
      status: response.status
    }
  } catch (error) {
    console.error('API Request failed:', error)
    
    if (error.status) {
      return {
        success: false,
        error: error.message,
        status: error.status,
        data: error.data
      }
    } else {
      return {
        success: false,
        error: 'Network error or server unavailable',
        status: 0
      }
    }
  }
}

// Authentication API calls
export const authAPI = {
  /**
   * Login user
   * @param {string} email - User email
   * @param {string} password - User password
   * @returns {Promise} Login response
   */
  login: async (email, password) => {
    const result = await apiRequest('/login', {
      method: 'POST',
      body: JSON.stringify({ email, password })
    })

    if (result.success && result.data) {
      const payload = result.data
      const user = payload.user || payload.data?.user || null
      const token = payload.token || payload.plainTextToken || payload.data?.token || null
      const role = payload.role || (user ? (user.role?.name || null) : null)
      const mustChange = payload.must_change_password ?? payload.data?.must_change_password ?? false

      if (user) {
        // Persist user session info
        localStorage.setItem('userData', JSON.stringify({ ...user, role: { name: role } }))
        localStorage.setItem('isLoggedIn', 'true')
      }

      if (token) {
        localStorage.setItem('authToken', token)
        // Ensure axios (used across app) sends Authorization header immediately
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
      }

      return {
        success: true,
        user,
        role,
        token,
        must_change_password: mustChange
      }
    }

    return {
      success: false,
      message: result.error || 'Login failed',
      status: result.status
    }
  },

  /**
   * Register new user
   * @param {Object} userData - User registration data
   * @returns {Promise} Registration response
   */
  register: async (userData) => {
    return await apiRequest('/register', {
      method: 'POST',
      body: JSON.stringify(userData)
    })
  },

  /**
   * Verify user (SuperUser only)
   * @param {number} userId - User ID to verify
   * @returns {Promise} Verification response
   */
  verifyUser: async (userId) => {
    return await apiRequest(`/verify-user/${userId}`, {
      method: 'POST'
    })
  },

  /**
   * Get unverified users (SuperUser only)
   * @returns {Promise} Unverified users list
   */
  getUnverifiedUsers: async () => {
    return await apiRequest('/unverified-users')
  }
}

// User management API calls
export const userAPI = {
  /**
   * Get current user profile
   * @returns {Promise} User profile
   */
  getProfile: async () => {
    return await apiRequest('/user/profile')
  },

  /**
   * Update user profile
   * @param {Object} userData - Updated user data
   * @returns {Promise} Update response
   */
  updateProfile: async (userData) => {
    return await apiRequest('/user/profile', {
      method: 'PUT',
      body: JSON.stringify(userData)
    })
  },

  /**
   * Get all users (Admin/SuperUser only)
   * @returns {Promise} Users list
   */
  getAllUsers: async () => {
    return await apiRequest('/users')
  },

  /**
   * Create new user (Admin/SuperUser only)
   * @param {Object} userData - New user data
   * @returns {Promise} Creation response
   */
  createUser: async (userData) => {
    return await apiRequest('/users', {
      method: 'POST',
      body: JSON.stringify(userData)
    })
  },

  /**
   * Update user (Admin/SuperUser only)
   * @param {number} userId - User ID
   * @param {Object} userData - Updated user data
   * @returns {Promise} Update response
   */
  updateUser: async (userId, userData) => {
    return await apiRequest(`/users/${userId}`, {
      method: 'PUT',
      body: JSON.stringify(userData)
    })
  },

  /**
   * Delete user (Admin/SuperUser only)
   * @param {number} userId - User ID
   * @returns {Promise} Deletion response
   */
  deleteUser: async (userId) => {
    return await apiRequest(`/users/${userId}`, {
      method: 'DELETE'
    })
  }
}

// POS/Sales API calls
export const salesAPI = {
  /**
   * Get sales data
   * @param {Object} filters - Filter options
   * @returns {Promise} Sales data
   */
  getSales: async (filters = {}) => {
    const queryString = new URLSearchParams(filters).toString()
    return await apiRequest(`/sales${queryString ? '?' + queryString : ''}`)
  },

  /**
   * Create new sale
   * @param {Object} saleData - Sale data
   * @returns {Promise} Sale creation response
   */
  createSale: async (saleData) => {
    return await apiRequest('/sales', {
      method: 'POST',
      body: JSON.stringify(saleData)
    })
  }
}

// Products API calls
export const productsAPI = {
  /**
   * Get all products
   * @returns {Promise} Products list
   */
  getProducts: async () => {
    return await apiRequest('/products')
  },

  /**
   * Create new product
   * @param {Object} productData - Product data
   * @returns {Promise} Creation response
   */
  createProduct: async (productData) => {
    return await apiRequest('/products', {
      method: 'POST',
      body: JSON.stringify(productData)
    })
  },

  /**
   * Update product
   * @param {number} productId - Product ID
   * @param {Object} productData - Updated product data
   * @returns {Promise} Update response
   */
  updateProduct: async (productId, productData) => {
    return await apiRequest(`/products/${productId}`, {
      method: 'PUT',
      body: JSON.stringify(productData)
    })
  },

  /**
   * Delete product
   * @param {number} productId - Product ID
   * @returns {Promise} Deletion response
   */
  deleteProduct: async (productId) => {
    return await apiRequest(`/products/${productId}`, {
      method: 'DELETE'
    })
  }
}

// Inventory API calls
export const inventoryAPI = {
  /**
   * Get inventory data
   * @returns {Promise} Inventory data
   */
  getInventory: async () => {
    return await apiRequest('/inventory')
  },

  /**
   * Update inventory
   * @param {number} productId - Product ID
   * @param {Object} inventoryData - Inventory update data
   * @returns {Promise} Update response
   */
  updateInventory: async (productId, inventoryData) => {
    return await apiRequest(`/inventory/${productId}`, {
      method: 'PUT',
      body: JSON.stringify(inventoryData)
    })
  }
}

// Reports API calls
export const reportsAPI = {
  /**
   * Get sales reports
   * @param {Object} filters - Report filters
   * @returns {Promise} Sales report data
   */
  getSalesReport: async (filters = {}) => {
    const queryString = new URLSearchParams(filters).toString()
    return await apiRequest(`/reports/sales${queryString ? '?' + queryString : ''}`)
  },

  /**
   * Get inventory reports
   * @returns {Promise} Inventory report data
   */
  getInventoryReport: async () => {
    return await apiRequest('/reports/inventory')
  }
}

// System API calls (SuperUser only)
export const systemAPI = {
  /**
   * Get system logs
   * @param {Object} filters - Log filters
   * @returns {Promise} System logs
   */
  getLogs: async (filters = {}) => {
    const queryString = new URLSearchParams(filters).toString()
    return await apiRequest(`/system/logs${queryString ? '?' + queryString : ''}`)
  },

  /**
   * Get system settings
   * @returns {Promise} System settings
   */
  getSettings: async () => {
    return await apiRequest('/system/settings')
  },

  /**
   * Update system settings
   * @param {Object} settings - Updated settings
   * @returns {Promise} Update response
   */
  updateSettings: async (settings) => {
    return await apiRequest('/system/settings', {
      method: 'PUT',
      body: JSON.stringify(settings)
    })
  }
}

// Create axios instance for direct API calls
const axiosInstance = axios.create({
  baseURL: BASE_URL,
  headers: defaultHeaders
})

// Add interceptor to include auth token
axiosInstance.interceptors.request.use((config) => {
  const token = localStorage.getItem('authToken')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
}, (error) => {
  return Promise.reject(error)
})

// Export axios instance as default, and named APIs for backward compatibility
export default axiosInstance

// Also export individual APIs for backward compatibility
export const apis = {
  authAPI,
  userAPI,
  salesAPI,
  productsAPI,
  inventoryAPI,
  reportsAPI,
  systemAPI
}

// Utility function to handle API errors in components
export const handleAPIError = (error) => {
  console.error('API Error:', error)
  
  const errorMessages = {
    401: 'Authentication required. Please log in.',
    403: 'You do not have permission to perform this action.',
    404: 'The requested resource was not found.',
    422: 'Invalid data provided. Please check your input.',
    500: 'Server error. Please try again later.',
    0: 'Network error. Please check your connection.'
  }
  
  return errorMessages[error.status] || error.error || 'An unexpected error occurred.'
}



