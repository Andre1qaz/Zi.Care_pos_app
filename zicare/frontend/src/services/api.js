import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api/v1',
  headers: { 'Content-Type': 'application/json' }
})

function readStoredToken() {
  return localStorage.getItem('token') ?? sessionStorage.getItem('token')
}

api.interceptors.request.use((config) => {
  const token = readStoredToken()
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Endpoint publik yang boleh membalas 401 tanpa memaksa logout/redirect.
const PUBLIC_PATHS = [
  '/auth/login',
  '/auth/status',
  '/auth/refresh',
  '/auth/verify-2fa',
  '/auth/forgot-password',
  '/auth/reset-password'
]

api.interceptors.response.use(
  (response) => response,
  (error) => {
    const isPublicRequest = PUBLIC_PATHS.some((path) => error.config?.url?.includes(path))

    if (error.response?.status === 401 && !isPublicRequest) {
      for (const store of [localStorage, sessionStorage]) {
        store.removeItem('token')
        store.removeItem('user')
      }
      window.location.href = '/login?expired=1'
    }
    return Promise.reject(error)
  }
)

export default api
