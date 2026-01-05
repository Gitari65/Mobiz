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
  const token = localStorage.getItem('authToken')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

app.use(router)
app.mount('#app')
