import { createApp } from 'vue'
import './style.css'
import App from './App.vue'
import router from './router'
import axios from 'axios'

const app = createApp(App)

// Configure axios baseURL to point to backend
axios.defaults.baseURL = 'http://127.0.0.1:8000'

// If token exists on startup, set Authorization header
const existingToken = localStorage.getItem('authToken')
if (existingToken) {
  axios.defaults.headers.common['Authorization'] = `Bearer ${existingToken}`
}

// Add auth token to headers for future requests (interceptor still kept)
axios.interceptors.request.use(config => {
  config.metadata = {
    ...(config.metadata || {}),
    startedAt: Date.now()
  }

  const token = localStorage.getItem('authToken')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

const requestMetrics = new Map()

const recordRequestMetric = (method, endpoint, durationMs) => {
  const key = `${method} ${endpoint}`
  const existing = requestMetrics.get(key) || { count: 0, totalMs: 0, maxMs: 0 }
  const next = {
    count: existing.count + 1,
    totalMs: existing.totalMs + durationMs,
    maxMs: Math.max(existing.maxMs, durationMs)
  }
  requestMetrics.set(key, next)

  if (next.count % 10 === 0) {
    const avgMs = Math.round(next.totalMs / next.count)
    console.log('[HTTP METRIC]', { endpoint: key, count: next.count, avg_ms: avgMs, max_ms: next.maxMs })
  }
}

axios.interceptors.response.use((response) => {
  const startedAt = response?.config?.metadata?.startedAt
  if (startedAt) {
    const durationMs = Date.now() - startedAt
    const method = String(response?.config?.method || 'GET').toUpperCase()
    const endpoint = response?.config?.url || 'unknown-url'
    const serverMs = response?.headers?.['x-request-duration-ms']

    recordRequestMetric(method, endpoint, durationMs)
    if (durationMs >= 800) {
      console.warn('[HTTP SLOW]', {
        method,
        endpoint,
        status: response.status,
        client_ms: durationMs,
        server_ms: serverMs ? Number(serverMs) : null
      })
    }
  }

  return response
}, (error) => {
  const config = error?.config
  const startedAt = config?.metadata?.startedAt
  if (startedAt) {
    const durationMs = Date.now() - startedAt
    const method = String(config?.method || 'GET').toUpperCase()
    const endpoint = config?.url || 'unknown-url'
    recordRequestMetric(method, endpoint, durationMs)
  }

  return Promise.reject(error)
})

window.__mobizRequestMetrics = requestMetrics

app.use(router)
app.mount('#app')
