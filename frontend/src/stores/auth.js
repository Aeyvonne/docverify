import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/api/axios'

export const useAuthStore = defineStore('auth', () => {
  // ── État ──────────────────────────────────────────────────────────────
  const user  = ref(JSON.parse(localStorage.getItem('auth_user') || 'null'))
  const token = ref(localStorage.getItem('auth_token') || null)

  // ── Getters ───────────────────────────────────────────────────────────
  const isAuthenticated = computed(() => !!token.value)
  const isAdmin         = computed(() => user.value?.role === 'admin')
  const isEmetteur      = computed(() => user.value?.role === 'emetteur')
  const isCertified     = computed(() => user.value?.is_certified === true)
  // Particulier = inscrit avec type_institution 'particulier' — pas de demande de certification possible
  const isParticulier   = computed(() => user.value?.type_institution === 'particulier')

  // ── Actions ───────────────────────────────────────────────────────────

  /** Connexion : stocke token + user en mémoire et localStorage */
  async function login(email, password) {
    const { data } = await api.post('/login', { email, password })
    token.value = data.token
    user.value  = data.user
    localStorage.setItem('auth_token', data.token)
    localStorage.setItem('auth_user',  JSON.stringify(data.user))
    return data
  }

  /** Déconnexion côté serveur + nettoyage local */
  async function logout() {
    try {
      await api.post('/logout')
    } catch (_) {
      // silencieux si le token est déjà invalide
    } finally {
      token.value = null
      user.value  = null
      localStorage.removeItem('auth_token')
      localStorage.removeItem('auth_user')
      // Vider le cache du store documents pour éviter les fuites cross-session
      const { useDocumentsStore } = await import('@/stores/documents')
      useDocumentsStore().reset()
    }
  }

  /** Rafraîchit les données de l'utilisateur connecté */
  async function fetchMe() {
    const { data } = await api.get('/me')
    user.value = data
    localStorage.setItem('auth_user', JSON.stringify(data))
  }

  return { user, token, isAuthenticated, isAdmin, isEmetteur, isCertified, isParticulier, login, logout, fetchMe }
})
