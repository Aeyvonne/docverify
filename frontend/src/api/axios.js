import axios from 'axios'

// Instance axios configurée pour le backend Laravel + Sanctum
const api = axios.create({
  baseURL: '/api',
  withCredentials: true, // nécessaire pour Sanctum (cookies CSRF)
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
})

// Intercepteur de requête : injecte le token Bearer si présent
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Intercepteur de réponse : redirige vers /login si 401
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token')
      localStorage.removeItem('auth_user')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export default api
